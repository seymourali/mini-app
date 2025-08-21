<?php

session_start();

// Redirect if not authenticated
if (!isset($_SESSION['auth'])) {
    http_response_code(401);
    die('Unauthorized');
}

// Include core application logic
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Response.php';
require_once __DIR__ . '/../src/services/UserService.php';

// Handle API request to fetch users
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the required DataTables parameters are present
    if (isset($_GET['draw']) && isset($_GET['start']) && isset($_GET['length']) && isset($_GET['search'])) {
        $userService = new UserService();
        $response = $userService->fetchUsers($_GET);
        
        Response::json($response);
    } else {
        Response::json([
            'success' => false,
            'message' => 'Invalid request parameters.'
        ], 400);
    }
} else {
    Response::json([
        'success' => false,
        'message' => 'Method not allowed.'
    ], 405);
}
