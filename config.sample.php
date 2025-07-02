<?php
// Sample configuration file for Benasque 25 Conference Website
// Copy this file to config.php and customize the settings

return [
    // Conference details
    'conference_name' => 'Your Conference Name',
    'page_title' => 'Your Conference - Participants',
    
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
?>
