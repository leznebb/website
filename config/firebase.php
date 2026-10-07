<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Firestore\FirestoreClient;


/*
|--------------------------------------------------------------------------
| Firebase service account key
|--------------------------------------------------------------------------
*/

$keyFile = __DIR__ . '/../private/serviceAccountKey.json';


if (!file_exists($keyFile)) {

    die('Firebase service account key not found.');

}


/*
|--------------------------------------------------------------------------
| Read the JSON credentials
|--------------------------------------------------------------------------
*/

$keyData = json_decode(
    file_get_contents($keyFile),
    true
);


if (!is_array($keyData)) {

    die('Firebase service account JSON is invalid.');

}


/*
|--------------------------------------------------------------------------
| Google authentication
|--------------------------------------------------------------------------
*/

$scopes = [
    'https://www.googleapis.com/auth/cloud-platform'
];


$credentials = new ServiceAccountCredentials(
    $scopes,
    $keyData
);


/*
|--------------------------------------------------------------------------
| Firestore connection
|--------------------------------------------------------------------------
*/

$firestore = new FirestoreClient([
    'projectId' => 'payroll-ee42b',
    'credentials' => $credentials
]);
