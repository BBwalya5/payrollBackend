<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'login':
                    $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
                    $stmt->execute([$data['username']]);
                    $user = $stmt->fetch();
                    
                    if ($user && verifyPassword($data['password'], $user['password'])) {
                        $token = generateToken($user['id'], $user['role']);
                        echo json_encode([
                            'success' => true,
                            'token' => $token,
                            'user' => [
                                'id' => $user['id'],
                                'username' => $user['username'],
                                'role' => $user['role']
                            ]
                        ]);
                    } else {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
                    }
                    break;
                    
                case 'register':
                    if (!isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                        break;
                    }
                    
                    $hashedPassword = hashPassword($data['password']);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
                    
                    try {
                        $stmt->execute([$data['username'], $hashedPassword, $data['email']]);
                        $userId = $pdo->lastInsertId();
                        $token = generateToken($userId, 'user');
                        
                        echo json_encode([
                            'success' => true,
                            'token' => $token,
                            'user' => [
                                'id' => $userId,
                                'username' => $data['username'],
                                'role' => 'user'
                            ]
                        ]);
                    } catch (PDOException $e) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                    }
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No action specified']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?> 