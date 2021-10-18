<?php

return [

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', null),

    'upload_url' => env('CLOUDINARY_UPLOAD_URL', 'https://res.cloudinary.com/'),

    'auto_mapping_folder' => env('CLOUDINARY_AUTO_MAPPING_FOLDER', ''),

    'api_key' => env('CLOUDINARY_API_KEY', ''),

    'api_secret' => env('CLOUDINARY_API_SECRET', ''),

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET', ''),

    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL', ''),

    'url' => env('CLOUDINARY_URL', ''),

    'delivery_type' => env('CLOUDINARY_DELIVERY_TYPE', 'upload'),

    'default_transformations' => [
        'image' => [
            'crop' => 'fill',
            'dpr' => 'auto',
            'fetch_format' => 'auto',
            'gravity' => 'auto',
            'quality' => 'auto',
        ],
        'video' => [
            'crop' => 'fill',
            'dpr' => 'auto',
            'fetch_format' => 'auto',
            'quality' => 'auto',
        ],
    ],

];
