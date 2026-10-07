<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/firebase.php';

$employeeId = $_GET['id'] ?? '';

if ($employeeId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Employee ID is required.']);
    exit;
}

try {
    $records = [];

    $attendanceDocuments = $firestore->collection('attendance')
        ->where('employeeId', '=', $employeeId)->documents();

    foreach ($attendanceDocuments as $document) {
        if (!$document->exists()) continue;
        $data = $document->data();
        $type = $data['status'] ?? 'worked';

        $records[] = [
            'date' => $data['date'] ?? '',
            'type' => strtolower($type),
            'label' => $data['label'] ?? ucfirst($type),
            'details' => $data['details'] ?? (
                isset($data['timeIn'])
                    ? 'Time In: ' . $data['timeIn'] . ' | Time Out: ' . ($data['timeOut'] ?? '--') . ' | Hours: ' . ($data['hoursWorked'] ?? 0)
                    : ''
            )
        ];
    }

    $leaveDocuments = $firestore->collection('leaves')
        ->where('employeeId', '=', $employeeId)->documents();

    foreach ($leaveDocuments as $document) {
        if (!$document->exists()) continue;
        $data = $document->data();
        $startDate = $data['startDate'] ?? '';
        $endDate = $data['endDate'] ?? $startDate;

        if ($startDate === '') continue;

        $current = new DateTime($startDate);
        $end = new DateTime($endDate);

        while ($current <= $end) {
            $records[] = [
                'date' => $current->format('Y-m-d'),
                'type' => strtolower($data['type'] ?? 'leave'),
                'label' => $data['label'] ?? ucfirst($data['type'] ?? 'Leave'),
                'details' => $data['reason'] ?? ''
            ];
            $current->modify('+1 day');
        }
    }

    usort($records, function ($a, $b) {
        return strcmp($a['date'], $b['date']);
    });

    echo json_encode(['success' => true, 'records' => $records], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}