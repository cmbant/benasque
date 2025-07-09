<?php
// Sample configuration file for Conference Website
// Copy this file to config.php and customize the settings

return [
    // Conference details
    'conference_name' => 'Your Conference Name',
    'page_title' => 'Your Conference - Participants',
    'conference_start_date' => '2025-07-20', // Conference start date for week calculations
    'conference_end_date' => '2025-08-01', // Conference end date

    // System settings
    'database_name' => 'benasque25', // Database file name (without .db extension)
    'cache_version' => 'v3', // Cache-busting parameter for CSS/JS files
    'local_storage_prefix' => 'benasque25', // Prefix for localStorage keys

    // Virtual blackboard settings
    'virtual_blackboard_url' => 'https://docs.google.com/document/d/YOUR_DOCUMENT_ID/edit',
    'virtual_blackboard_title' => 'Virtual Blackboard',

    // Other customizable settings
    'max_arxiv_links' => 3,
    'max_photo_size' => '10M',
    'allowed_photo_types' => ['jpg', 'jpeg', 'png', 'gif'],

    // Photo resize settings
    'photo_max_width' => 300,
    'photo_max_height' => 300,
];
