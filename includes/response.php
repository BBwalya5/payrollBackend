<?php
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function sendError($message, $status = 400) {
    sendResponse([
        'status' => 'error',
        'message' => $message
    ], $status);
}

function sendSuccess($data = null, $message = 'Success') {
    sendResponse([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ]);
}
?> 