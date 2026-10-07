<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/firebase.php';

try {
    $employees = [];
    $documents = $firestore->collection('employees')->documents();

    foreach ($documents as $document) {
        if (!$document->exists()) continue;
        $employee = $document->data();
        $employee['id'] = $document->id();
        $employees[] = $employee;
    }

    echo json_encode(['success' => true, 'employees' => $employees], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}