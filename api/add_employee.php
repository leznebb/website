<?php
header('Content-Type: application/json; charset=utf-8');

function sendResponse(bool $success, string $message, array $extra = [], int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_PRETTY_PRINT);
    exit;
}

set_exception_handler(function (Throwable $exception): void {
    sendResponse(false, 'PHP exception occurred.', ['error' => $exception->getMessage()], 500);
});

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    sendResponse(false, 'This endpoint only accepts POST requests.', [], 405);
}

require_once __DIR__ . '/../config/firebase.php';

$rawData = file_get_contents('php://input');

if ($rawData === false || trim($rawData) === '') {
    sendResponse(false, 'The request body is empty.', [], 400);
}

$data = json_decode($rawData, true);

if (!is_array($data)) {
    sendResponse(false, 'The submitted data is not valid JSON.', ['json_error' => json_last_error_msg()], 400);
}

$requiredFields = ['id', 'name', 'department', 'position', 'shift', 'schedule', 'payType', 'payRate'];

foreach ($requiredFields as $field) {
    if (!array_key_exists($field, $data)) {
        sendResponse(false, "Missing field: {$field}", [], 400);
    }
}

$employeeId = trim((string)$data['id']);
$name = trim((string)$data['name']);
$department = trim((string)$data['department']);
$position = trim((string)$data['position']);
$employmentType = trim((string)($data['employmentType'] ?? 'Full-Time'));
$shift = trim((string)$data['shift']);
$schedule = trim((string)$data['schedule']);
$payType = trim((string)$data['payType']);
$payRate = (float)($data['payRate'] ?? 0);
$vacation = (int)($data['vacation'] ?? 0);
$sick = (int)($data['sick'] ?? 0);

if ($employeeId === '' || $name === '' || $department === '' || $position === '' || $shift === '' || $schedule === '' || $payType === '') {
    sendResponse(false, 'One or more required fields are empty.', [], 400);
}

if ($payRate < 0) {
    sendResponse(false, 'Pay rate cannot be negative.', [], 400);
}

try {
    $employeeReference = $firestore->collection('employees')->document($employeeId);
    $employeeSnapshot = $employeeReference->snapshot();

    if ($employeeSnapshot->exists()) {
        sendResponse(false, 'Employee ID already exists.', [], 409);
    }

    $employeeData = [
        'name' => $name,
        'department' => $department,
        'position' => $position,
        'employmentType' => $employmentType,
        'status' => 'Absent',
        'shift' => $shift,
        'schedule' => $schedule,
        'payType' => $payType,
        'payRate' => $payRate,
        'vacation' => $vacation,
        'sick' => $sick,
        'hoursWeek' => 0,
        'hoursMonth' => 0
    ];

    $employeeReference->set($employeeData);

    sendResponse(true, 'Employee added successfully.', [
        'employee' => array_merge(['id' => $employeeId], $employeeData)
    ]);
} catch (Throwable $e) {
    sendResponse(false, 'Firestore could not save the employee.', [
        'error' => $e->getMessage(),
        'type' => get_class($e)
    ], 500);
}