<?php

use Dompdf\Dompdf;
use Dompdf\Options;

ini_set('display_errors', '0');
ini_set('memory_limit', '512M');
set_time_limit(180);

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Expose-Headers: Content-Disposition');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

function completeReportPdfApiError(int $httpStatus, string $message): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($httpStatus);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => false,
        'message' => $message,
    ]);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!in_array($requestMethod, ['GET', 'POST'], true)) {
    header('Allow: GET, POST, OPTIONS');
    completeReportPdfApiError(405, 'Use GET or POST to download the PDF report.');
}

session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['station_id'])) {
    completeReportPdfApiError(401, 'Please log in before downloading the PDF report.');
}

if (isset($_SESSION['status']) && (int) $_SESSION['status'] === 1) {
    completeReportPdfApiError(403, 'Your account is disabled.');
}

$loggedInStationId = (int) $_SESSION['station_id'];
$requestData = $requestMethod === 'GET' ? $_GET : $_POST;

if (
    $requestMethod === 'POST'
    && stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false
) {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (!is_array($requestData)) {
        completeReportPdfApiError(400, 'Send a valid JSON object.');
    }
}

$parameterAliases = [
    'up' => 'train_up',
    'down' => 'train_down',
    'grade' => 'grade',
    'from_date' => 'from',
    'to_date' => 'to',
];

$filters = [];

foreach ($parameterAliases as $parameter => $alias) {
    $value = $requestData[$parameter] ?? $requestData[$alias] ?? '';

    if (!is_scalar($value) || is_bool($value) || trim((string) $value) === '') {
        completeReportPdfApiError(
            400,
            'Required parameters: train_up, train_down, grade, from and to.'
        );
    }

    $filters[$parameter] = trim((string) $value);
}

$filters['grade'] = strtoupper(trim(explode('-', $filters['grade'], 2)[0]));

if (
    !preg_match('/^[0-9]{1,10}$/D', $filters['up'])
    || !preg_match('/^[0-9]{1,10}$/D', $filters['down'])
) {
    completeReportPdfApiError(400, 'train_up and train_down must contain valid train numbers.');
}

if (!preg_match('/^[A-G]$/D', $filters['grade'])) {
    completeReportPdfApiError(400, 'grade must be A through G, or a label such as D - Thursday.');
}

foreach (['from_date', 'to_date'] as $dateParameter) {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$dateParameter]);

    if (!$date || $date->format('Y-m-d') !== $filters[$dateParameter]) {
        completeReportPdfApiError(400, 'from and to must be valid YYYY-MM-DD dates.');
    }
}

if ($filters['from_date'] > $filters['to_date']) {
    completeReportPdfApiError(400, 'from must be on or before to.');
}

if (isset($requestData['station_id'])) {
    $requestedStationId = $requestData['station_id'];

    if (
        !is_scalar($requestedStationId)
        || (string) $requestedStationId !== (string) $loggedInStationId
    ) {
        completeReportPdfApiError(403, 'station_id must match the logged-in station.');
    }
}

$projectRoot = dirname(__DIR__);
$isolatedAutoloader = __DIR__ . '/pdf-runtime/vendor/autoload.php';

if (!is_file($isolatedAutoloader)) {
    completeReportPdfApiError(503, 'PDF runtime is not installed on the server.');
}

require_once $isolatedAutoloader;

if (!class_exists(Dompdf::class)) {
    completeReportPdfApiError(503, 'PDF runtime is unavailable on the server.');
}

$_GET = [
    'up' => $filters['up'],
    'down' => $filters['down'],
    'grade' => $filters['grade'],
    'from_date' => $filters['from_date'],
    'to_date' => $filters['to_date'],
];

// The existing report starts the session itself. Close this lock before including it.
session_write_close();

$originalWorkingDirectory = getcwd();
$reportBufferLevel = ob_get_level();
ob_start();

try {
    chdir($projectRoot);
    require $projectRoot . '/download-all-reports-pdf.php';
    $reportHtml = ob_get_clean();
} catch (Throwable $error) {
    while (ob_get_level() > $reportBufferLevel) {
        ob_end_clean();
    }

    error_log('Complete report HTML generation failed: ' . $error->getMessage());
    completeReportPdfApiError(500, 'Unable to prepare the complete report.');
} finally {
    chdir($originalWorkingDirectory);
}

// Hide the browser-only buttons and tune page breaks for server-side PDF output.
$pdfStyles = <<<'CSS'
<style>
    @page { margin: 20pt; }
    body { font-family: DejaVu Sans, sans-serif; padding: 0; }
    .no-print { display: none !important; }
    .section-divider { margin: 10px 0; }
    h1, h3, .header-info { page-break-after: avoid; }
    tr { page-break-inside: avoid; }
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    .employee-photo-thumbnail,
    .attendance-photo-thumbnail { height: auto; max-height: 72px; }
</style>
CSS;

$reportHtml = str_replace('</head>', $pdfStyles . '</head>', $reportHtml);
$reportHtml = preg_replace(
    '/<img\b[^>]*\bsrc=["\']https?:\/\/[^"\']*["\'][^>]*>/i',
    '<span>No photo</span>',
    $reportHtml
);

try {
    $pdfOptions = new Options();
    $pdfOptions->setDefaultFont('DejaVu Sans');
    $pdfOptions->setDefaultMediaType('print');
    $pdfOptions->setChroot($projectRoot);
    $pdfOptions->setIsRemoteEnabled(false);
    $pdfOptions->setIsPhpEnabled(false);
    $pdfOptions->setIsJavascriptEnabled(false);

    $dompdf = new Dompdf($pdfOptions);
    $dompdf->setBasePath($projectRoot . '/');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->loadHtml($reportHtml, 'UTF-8');
    $dompdf->render();
    $pdfContent = $dompdf->output();
} catch (Throwable $error) {
    error_log('Complete report PDF generation failed: ' . $error->getMessage());
    completeReportPdfApiError(500, 'Unable to generate the PDF report.');
}

$downloadFilename = sprintf(
    'Complete_Report_%s_%s_%s_%s_to_%s.pdf',
    $filters['up'],
    $filters['down'],
    $filters['grade'],
    $filters['from_date'],
    $filters['to_date']
);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
