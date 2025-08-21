<?php
// list.php - The User List Page
session_start();

// Hardcoded login for demonstration as per the task requirements
$hardcoded_user = 'admin@example.com';
$hardcoded_pass = '123456';

// Check for logout request
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: list.php');
    exit;
}

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $hardcoded_user && $_POST['password'] === $hardcoded_pass) {
        $_SESSION['auth']=true;
        header('Location: list.php');
        exit;
    } else {
        $login_error = 'Invalid credentials.';
    }
}

// Redirect if not authenticated
if (!isset($_SESSION['auth'])) {
    require_once 'login_form.php';
    exit;
}

// Include core application logic for exports
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Response.php';
require_once __DIR__ . '/../src/services/UserService.php';

// Handle export requests
if (isset($_GET['export'])) {
    $userService = new UserService();
    $exportType = strtolower($_GET['export']);
    if ($exportType === 'xlsx') {
        $userService->exportToExcel();
    } elseif ($exportType === 'pdf') {
        $userService->exportToPdf();
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css"/>
    <script type="text/javascript" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="bg-white p-8 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Registered Users</h1>
            <div class="flex space-x-2">
                <a href="?export=xlsx" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md">Export to XLSX</a>
                <a href="?export=pdf" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md">Export to PDF</a>
                <a href="?logout=true" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-md">Logout</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table id="userTable" class="w-full text-left table-auto">
                <thead>
                    <tr>
                        <th class="py-2 px-4 bg-gray-200 font-semibold text-gray-600">ID</th>
                        <th class="py-2 px-4 bg-gray-200 font-semibold text-gray-600">Full Name</th>
                        <th class="py-2 px-4 bg-gray-200 font-semibold text-gray-600">Email</th>
                        <th class="py-2 px-4 bg-gray-200 font-semibold text-gray-600">Company</th>
                        <th class="py-2 px-4 bg-gray-200 font-semibold text-gray-600">Created At</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with server-side processing
            $('#userTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "api_registrations.php", // Pointing to the new API file
                    "type": "GET"
                },
                "columns": [
                    { "data": "id" },
                    { "data": "full_name" },
                    { "data": "email" },
                    { "data": "company" },
                    { "data": "created_at" }
                ]
            });
        });
    </script>
</body>
</html>

<!-- login_form.php snippet (to be included in list.php if not authenticated) -->
<!-- This is a separate file to keep list.php clean -->
<?php if (!isset($_SESSION['auth'])) : ?>
    <div class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold text-center mb-6">Admin Login</h1>
            <?php if (isset($login_error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($login_error) ?></span>
                </div>
            <?php endif; ?>
            <form action="list.php" method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border">
                </div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Login</button>
            </form>
        </div>
    </div>
<?php endif; ?>
