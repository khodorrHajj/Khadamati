<?php

return [
    'google_application_credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'confidence_threshold' => (float) env('IDENTITY_CONFIDENCE_THRESHOLD', 0.75),
];
