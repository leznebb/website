<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/firebase.php';

$employeeId = $_GET['id'] ?? '';

if ($employeeId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Employee ID is required.'], JSON_PRETTY_PRINT);
    exit;
}

try {
    $document = $firestore->collection('employees')->document($employeeId)->snapshot();

    if (!$document->exists()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Employee not found.'], JSON_PRETTY_PRINT);
        exit;
    }

    $employee = $document->data();
    $employee['id'] = $document->id();

    echo json_encode(['success' => true, 'employee' => $employee], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}