<?php

return [
    'google_vision_api_key' => env('GOOGLE_VISION_API_KEY'),
    'google_application_credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'confidence_threshold' => (float) env('IDENTITY_CONFIDENCE_THRESHOLD', 0.75),
];
