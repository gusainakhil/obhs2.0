<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/connection.php';

function respond(bool $success, array $payload = [], int $status = 200): void
{
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body(): array
{
    $decoded = json_decode((string) file_get_contents('php://input'), true);
    return is_array($decoded) ? $decoded : $_POST;
}

if (!isset($_SESSION['user_id'], $_SESSION['station_id'])) {
    respond(false, ['message' => 'Authentication required.'], 401);
}

$userId = (int) $_SESSION['user_id'];
$stationId = (int) $_SESSION['station_id'];
$action = (string) ($_GET['action'] ?? 'list');

try {
    if ($action === 'list') {
        $stmt = $mysqli->prepare('SELECT id, name, category, unit, current_quantity, low_stock_threshold, created_at, updated_at FROM OBHS_chemicals WHERE station_id = ? ORDER BY name ASC');
        $stmt->bind_param('i', $stationId);
        $stmt->execute();
        $chemicals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $entriesStmt = $mysqli->prepare('SELECT e.id, e.chemical_id, e.entry_date, e.opening_quantity, e.used_quantity, e.received_quantity, e.closing_quantity, e.notes, e.created_at FROM OBHS_chemical_daily_entries e WHERE e.station_id = ? ORDER BY e.entry_date DESC, e.id DESC');
        $entriesStmt->bind_param('i', $stationId);
        $entriesStmt->execute();
        $entries = $entriesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $entriesStmt->close();
        respond(true, ['chemicals' => $chemicals, 'entries' => $entries]);
    }

    $data = body();

    if ($action === 'create_chemical' || $action === 'update_chemical') {
        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'Cleaning'));
        $unit = trim((string) ($data['unit'] ?? 'L'));
        $quantity = (float) ($data['quantity'] ?? 0);
        $threshold = (float) ($data['threshold'] ?? 10);
        if ($name === '' || mb_strlen($name) > 120 || !in_array($unit, ['L', 'kg', 'pcs'], true) || $quantity < 0 || $threshold < 0) {
            respond(false, ['message' => 'Invalid chemical details.'], 422);
        }
        if ($action === 'create_chemical') {
            $stmt = $mysqli->prepare('INSERT INTO OBHS_chemicals (station_id, name, category, unit, current_quantity, low_stock_threshold, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssddi', $stationId, $name, $category, $unit, $quantity, $threshold, $userId);
        } else {
            $id = (int) ($data['id'] ?? 0);
            $stmt = $mysqli->prepare('UPDATE OBHS_chemicals SET name = ?, category = ?, unit = ?, current_quantity = ?, low_stock_threshold = ? WHERE id = ? AND station_id = ?');
            $stmt->bind_param('sssddii', $name, $category, $unit, $quantity, $threshold, $id, $stationId);
        }
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($action === 'update_chemical' && $affected === 0) {
            $checkStmt = $mysqli->prepare('SELECT id FROM OBHS_chemicals WHERE id = ? AND station_id = ?');
            $checkStmt->bind_param('ii', $id, $stationId);
            $checkStmt->execute();
            $exists = (bool) $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            if (!$exists) respond(false, ['message' => 'Chemical not found.'], 404);
        }
        respond(true, ['message' => $action === 'create_chemical' ? 'Chemical added.' : 'Chemical updated.']);
    }

    if ($action === 'delete_chemical') {
        $id = (int) ($data['id'] ?? 0);
        $stmt = $mysqli->prepare('DELETE FROM OBHS_chemicals WHERE id = ? AND station_id = ?');
        $stmt->bind_param('ii', $id, $stationId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        respond($affected > 0, ['message' => $affected > 0 ? 'Chemical removed.' : 'Chemical not found.'], $affected > 0 ? 200 : 404);
    }

    if ($action === 'create_entry') {
        $chemicalId = (int) ($data['chemical_id'] ?? 0);
        $entryDate = (string) ($data['entry_date'] ?? '');
        $used = (float) ($data['used'] ?? 0);
        $received = (float) ($data['received'] ?? 0);
        $notes = trim((string) ($data['notes'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate) || $used < 0 || $received < 0 || mb_strlen($notes) > 255) respond(false, ['message' => 'Invalid daily entry.'], 422);
        $mysqli->begin_transaction();
        $stockStmt = $mysqli->prepare('SELECT current_quantity FROM OBHS_chemicals WHERE id = ? AND station_id = ? FOR UPDATE');
        $stockStmt->bind_param('ii', $chemicalId, $stationId);
        $stockStmt->execute();
        $row = $stockStmt->get_result()->fetch_assoc();
        $stockStmt->close();
        if (!$row) throw new RuntimeException('Chemical not found.');
        $opening = (float) $row['current_quantity'];
        $closing = $opening - $used + $received;
        if ($closing < 0) { $mysqli->rollback(); respond(false, ['message' => 'Used quantity exceeds available stock.'], 422); }
        $entryStmt = $mysqli->prepare('INSERT INTO OBHS_chemical_daily_entries (chemical_id, station_id, entry_date, opening_quantity, used_quantity, received_quantity, closing_quantity, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $entryStmt->bind_param('iisddddsi', $chemicalId, $stationId, $entryDate, $opening, $used, $received, $closing, $notes, $userId);
        $entryStmt->execute();
        $entryStmt->close();
        $updateStmt = $mysqli->prepare('UPDATE OBHS_chemicals SET current_quantity = ? WHERE id = ? AND station_id = ?');
        $updateStmt->bind_param('dii', $closing, $chemicalId, $stationId);
        $updateStmt->execute();
        $updateStmt->close();
        $mysqli->commit();
        respond(true, ['message' => 'Daily entry saved.', 'opening' => $opening, 'closing' => $closing]);
    }

    if ($action === 'delete_entry') {
        $entryId = (int) ($data['id'] ?? 0);
        $mysqli->begin_transaction();
        $stmt = $mysqli->prepare('SELECT id, chemical_id, opening_quantity FROM OBHS_chemical_daily_entries WHERE id = ? AND station_id = ? FOR UPDATE');
        $stmt->bind_param('ii', $entryId, $stationId);
        $stmt->execute();
        $entry = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$entry) throw new RuntimeException('Entry not found.');
        $latestStmt = $mysqli->prepare('SELECT id FROM OBHS_chemical_daily_entries WHERE chemical_id = ? AND station_id = ? ORDER BY created_at DESC, id DESC LIMIT 1');
        $latestStmt->bind_param('ii', $entry['chemical_id'], $stationId);
        $latestStmt->execute();
        $latest = $latestStmt->get_result()->fetch_assoc();
        $latestStmt->close();
        if ((int) ($latest['id'] ?? 0) !== $entryId) { $mysqli->rollback(); respond(false, ['message' => 'Only the latest entry can be deleted.'], 409); }
        $deleteStmt = $mysqli->prepare('DELETE FROM OBHS_chemical_daily_entries WHERE id = ? AND station_id = ?');
        $deleteStmt->bind_param('ii', $entryId, $stationId);
        $deleteStmt->execute();
        $deleteStmt->close();
        $restoreStmt = $mysqli->prepare('UPDATE OBHS_chemicals SET current_quantity = ? WHERE id = ? AND station_id = ?');
        $restoreStmt->bind_param('dii', $entry['opening_quantity'], $entry['chemical_id'], $stationId);
        $restoreStmt->execute();
        $restoreStmt->close();
        $mysqli->commit();
        respond(true, ['message' => 'Entry removed and stock restored.']);
    }

    respond(false, ['message' => 'Unknown action.'], 404);
} catch (mysqli_sql_exception $exception) {
    $mysqli->rollback();
    $message = $exception->getCode() === 1062 ? 'A chemical with this name already exists.' : 'Database operation failed.';
    error_log('Chemical inventory API: ' . $exception->getMessage());
    respond(false, ['message' => $message], $exception->getCode() === 1062 ? 409 : 500);
} catch (Throwable $exception) {
    $mysqli->rollback();
    error_log('Chemical inventory API: ' . $exception->getMessage());
    respond(false, ['message' => $exception->getMessage()], 400);
}
