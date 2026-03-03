<?php

return [
    'project_id' => env('FIREBASE_PROJECT_ID', 'your-firebase-project-id'),
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase_credentials.json')),
    ],
];
