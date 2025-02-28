<?php
session_start();

// Enable error reporting for debugging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log'); // Ensure this path exists or change to another one
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Database credentials
$host = "localhost";
$dbname = "thriftique";
$username = "root";
$passwordDB = ""; // Use a different variable name for the DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $passwordDB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die(json_encode(["error" => true, "message" => "Database connection failed."]));
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        echo json_encode(["error" => true, "message" => "Email and Password are required."]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, password FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(["error" => true, "message" => "Invalid email or password."]);
        exit();
    }

    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_name'] = $user['first_name'];
    echo json_encode(["error" => false, "message" => "Login successful!", "admin_name" => $user['first_name']]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="container">
        <h2>Admin Login</h2>
        <form id="loginForm">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="admin@example.com" required>
            </div>
            <div class="input-group password-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="********" required minlength="8">
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>
            </div>
            <button type="submit">Login</button>
            <p class="register-link">Don't have an account? <a href="adminregister.php">Register</a></p>
        </form>
    </div>
    

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const passwordField = document.getElementById("password");
            const togglePassword = document.getElementById("togglePassword");

            togglePassword.addEventListener("click", function () {
                const isPassword = passwordField.type === "password";
                passwordField.type = isPassword ? "text" : "password";
                this.classList.toggle("fa-eye-slash", isPassword);
                this.classList.toggle("fa-eye", !isPassword);
            });

            document.getElementById('loginForm').addEventListener('submit', async function (event) {
                event.preventDefault();

                const formData = new FormData(this);
                try {
                    const response = await fetch("login.php", {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    alert(data.message);
                    if (!data.error) {
                        window.location.href = 'dashboard.html';
                    }
                } catch (error) {
                    alert('Login failed. Please check your server.');
                }
            });
        });
    </script>
</body>
</html>