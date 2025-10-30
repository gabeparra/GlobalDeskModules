<?php

return [
    'name' => 'KnowledgeBaseAPI',
    
    // Enable or disable the API
    'enabled' => true,
    
    // API rate limiting (requests per minute)
    'rate_limit' => 60,
    
    // Require authentication for API access
    'require_auth' => true,
    
    // Allowed origins for CORS
    'cors_origins' => ['*'],
    
    // Include article content in list responses
    'include_content_in_list' => false,
];
