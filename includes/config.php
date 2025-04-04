<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'salary_zenith');
define('DB_USER', 'root');
define('DB_PASS', '');

// API configuration
define('BASE_URL', 'http://localhost/payroll-system');
define('API_URL', BASE_URL . '/api');

// JWT configuration
define('JWT_SECRET', 'your-secret-key-here');
define('JWT_EXPIRATION', 3600); // 1 hour 