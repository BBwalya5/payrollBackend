<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
require_once __DIR__ . '/../config/config.php';

function generateToken($userId, $role) {
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // 1 hour
    
    $payload = array(
        'user_id' => $userId,
        'role' => $role,
        'iat' => $issuedAt,
        'exp' => $expirationTime
    );
    
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function validateToken() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No token provided']);
        exit();
    }

    $token = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = JWT::decode($token, JWT_SECRET, array('HS256'));
        return $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit();
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?> 