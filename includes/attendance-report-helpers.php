<?php

function attendanceReportGetGradeDay($grade)
{
    static $gradeMapping = [
        'A' => 'Monday',
        'B' => 'Tuesday',
        'C' => 'Wednesday',
        'D' => 'Thursday',
        'E' => 'Friday',
        'F' => 'Saturday',
        'G' => 'Sunday',
    ];

    return $gradeMapping[(string) $grade] ?? '';
}

function attendanceReportDisplayFullAddress($fullLocationRaw)
{
    $fullLocationRaw = trim((string) $fullLocationRaw);
    if ($fullLocationRaw === '') {
        return '';
    }

    $address = '';
    $decoded = json_decode($fullLocationRaw, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $address = trim((string) ($decoded['formattedAddress'] ?? ''));
    } elseif (preg_match('/formattedAddress:\s*(.*?)(?:,\s*subregion\s*:|,\s*timezone\s*:|$)/i', $fullLocationRaw, $matches)) {
        $address = trim($matches[1]);
    } else {
        $address = $fullLocationRaw;
    }

    $address = preg_replace('/,\s*India\s*$/i', '', $address);

    return trim((string) $address);
}

function attendanceReportParseLocation($rawLocation)
{
    $location = html_entity_decode((string) $rawLocation, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $location = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $location);
    $location = trim((string) preg_replace('/\s+/', ' ', $location));

    $latitude = '';
    $longitude = '';
    $locationName = $location;

    if (preg_match('/lati\s*:\s*(-?[\d.]+)\s*longi\s*:\s*(-?[\d.]+)\s*(.+)?/iu', $location, $matches)) {
        $latitude = trim($matches[1]);
        $longitude = trim($matches[2]);
        $locationName = trim((string) ($matches[3] ?? ''));
    } elseif (preg_match('/^(-?[\d.]+),\s*(-?[\d.]+),\s*(.+)$/u', $location, $matches)) {
        $latitude = trim($matches[1]);
        $longitude = trim($matches[2]);
        $locationName = trim($matches[3]);
    }

    return [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'location_name' => $locationName,
    ];
}

function attendanceReportResolveImagePath($directory, $filename, $fallbackUrl, array &$cache)
{
    $filename = ltrim(str_replace('\\', '/', trim((string) $filename)), '/');

    if ($filename === '' || strpos($filename, '..') !== false) {
        return $fallbackUrl;
    }

    $path = rtrim($directory, '/') . '/' . $filename;

    if (!array_key_exists($path, $cache)) {
        $cache[$path] = is_file($path);
    }

    return $cache[$path] ? $path : $fallbackUrl;
}

function attendanceReportFormatDate($createdAt, $showTime)
{
    $timestamp = strtotime((string) $createdAt);
    if (!$timestamp) {
        return '';
    }

    return date($showTime ? 'd/m/Y H:i:s' : 'd/m/Y', $timestamp);
}

function attendanceReportBuildCheckpointData($row, $stationId, $showTime, array &$attendancePhotoCache)
{
    $parsedLocation = attendanceReportParseLocation($row['location'] ?? '');

    return [
        'photo_path' => attendanceReportResolveImagePath(
            'uploads/attendence',
            $row['photo'] ?? '',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/No_image_available.svg/1024px-No_image_available.svg.png',
            $attendancePhotoCache
        ),
        'latitude' => $parsedLocation['latitude'],
        'longitude' => $parsedLocation['longitude'],
        'location_name' => $parsedLocation['location_name'],
        'display_full_address' => attendanceReportDisplayFullAddress($row['fullLocation'] ?? ''),
        'display_date' => attendanceReportFormatDate($row['created_at'] ?? '', $showTime),
    ];
}

function attendanceReportFetchData($mysqli, $stationId, $selectedGrade, $selectedTrainFrom, $selectedTrainTo, $dateFrom, $dateTo)
{
    if ($selectedGrade === '' || $selectedTrainFrom === '' || $selectedTrainTo === '') {
        return [];
    }

    $dateFromStart = $dateFrom . ' 00:00:00';
    $dateToNextDay = strtotime($dateTo . ' +1 day');
    $dateToExclusive = $dateToNextDay
        ? date('Y-m-d', $dateToNextDay) . ' 00:00:00'
        : $dateTo . ' 23:59:59';

    $query = "SELECT
                ba.employee_id,
                ba.employee_name,
                ba.train_no,
                ba.type_of_attendance,
                ba.location,
                ba.photo,
                ba.created_at,
                ba.fullLocation,
                be.photo AS employee_photo
              FROM base_attendance ba
              LEFT JOIN base_employees be ON ba.employee_id = be.employee_id AND be.station = ?
              WHERE ba.station_id = ?
              AND ba.grade = ?
              AND ba.train_no IN (?, ?)
              AND ba.created_at >= ?
              AND ba.created_at < ?
              ORDER BY ba.employee_name, ba.train_no, FIELD(ba.type_of_attendance, 'Start of journey', 'Mid of journey', 'End of journey')";

    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log('Attendance report data prepare failed: ' . $mysqli->error);
        return [];
    }

    $stmt->bind_param(
        'iisssss',
        $stationId,
        $stationId,
        $selectedGrade,
        $selectedTrainFrom,
        $selectedTrainTo,
        $dateFromStart,
        $dateToExclusive
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $attendanceData = [];
    $employeePhotoCache = [];
    $attendancePhotoCache = [];
    $showTime = (int) $stationId !== 23;

    while ($row = $result->fetch_assoc()) {
        $employeeId = $row['employee_id'];

        if (!isset($attendanceData[$employeeId])) {
            $attendanceData[$employeeId] = [
                'employee_name' => $row['employee_name'],
                'employee_id' => $row['employee_id'],
                'employee_photo_path' => attendanceReportResolveImagePath(
                    'uploads/employee',
                    $row['employee_photo'] ?? '',
                    'https://uxwing.com/wp-content/themes/uxwing/download/peoples-avatars/default-profile-picture-male-icon.png',
                    $employeePhotoCache
                ),
                'train_from' => [],
                'train_to' => [],
            ];
        }

        $checkpointData = attendanceReportBuildCheckpointData($row, $stationId, $showTime, $attendancePhotoCache);

        if ((string) $row['train_no'] === (string) $selectedTrainFrom) {
            $attendanceData[$employeeId]['train_from'][$row['type_of_attendance']] = $checkpointData;
        } elseif ((string) $row['train_no'] === (string) $selectedTrainTo) {
            $attendanceData[$employeeId]['train_to'][$row['type_of_attendance']] = $checkpointData;
        }
    }

    $stmt->close();

    return $attendanceData;
}

function attendanceReportBuildCellText($data, $stationId)
{
    if (!$data) {
        return 'No Data';
    }

    $lines = [];

    if (!empty($data['latitude'])) {
        $lines[] = 'Lati: ' . $data['latitude'];
        $lines[] = 'Longi: ' . $data['longitude'];
    }

    if ((string) $stationId !== '25') {
        $lines[] = 'Location: ' . ($data['location_name'] ?: 'NA');
    } elseif (!empty($data['display_full_address'])) {
        $lines[] = 'Location: ' . $data['display_full_address'];
    }

    if (!empty($data['display_date'])) {
        $lines[] = 'Date: ' . $data['display_date'];
    }

    return implode("\n", $lines);
}
