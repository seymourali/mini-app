<?php

session_start();

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Response.php';
require_once __DIR__ . '/../src/services/UserService.php';

// Generate a CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST request from the form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if CSRF token is present in the session and POST data
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        Response::json([
            'status' => 'error',
            'message' => 'Invalid CSRF token.',
            'fields' => []
        ], 403);
    }
    
    // Initialize errors array
    $errors = [];

    // Sanitize and validate full name
    $fullName = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if (empty(trim($fullName))) {
        $errors['full_name'] = 'Full name is required.';
    }

    // Sanitize and validate email format
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }

    // Sanitize company name
    $company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (!empty($errors)) {
        Response::json([
            'status' => 'fail',
            'message' => 'Validation failed.',
            'fields' => $errors
        ], 400);
    }

    // Use a service layer to handle the business logic
    $userService = new UserService();
    $result = $userService->registerUser($fullName, $email, $company);

    Response::json($result['data'], $result['code']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold text-center mb-6">Registration Form</h1>
        <form id="registrationForm" class="space-y-4">
            <!-- CSRF token field -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="full_name" name="full_name" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                <div id="full_name_error" class="mt-1 text-sm text-red-600"></div>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="text" id="email" name="email" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                <div id="email_error" class="mt-1 text-sm text-red-600"></div>
            </div>
            <div>
                <label for="company" class="block text-sm font-medium text-gray-700">Company (Optional)</label>
                <input type="text" id="company" name="company"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                <div id="company_error" class="mt-1 text-sm text-red-600"></div>
            </div>
            <div id="message" class="text-center font-semibold mt-4"></div>
            <button type="submit" id="submitBtn"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Register
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="list.php" class="text-indigo-600 hover:text-indigo-800 text-sm">View User List</a>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#registrationForm').on('submit', function(e) {
                e.preventDefault();
                console.log('submitted');
                const form = $(this);
                const submitBtn = $('#submitBtn');
                const messageDiv = $('#message');

                // Clear previous messages and errors
                $('.text-red-600').text('');
                messageDiv.removeClass('text-green-600 text-red-600').text('');

                // Disable button and show loading state
                submitBtn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: 'index.php',
                    method: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        messageDiv.text(response.message).addClass('text-green-600');
                        form[0].reset();
                    },
                    error: function(xhr) {
                        for (const field in xhr.responseJSON.fields) {
                            $(`#${field}_error`).text(xhr.responseJSON.fields[field]);
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text('Register');
                    }
                });
            });
        });
    </script>
</body>
</html>
