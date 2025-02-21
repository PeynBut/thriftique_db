<?php
session_start();

ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log'); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$host = "localhost";
$dbname = "thriftique";
$username = "root"; 
$password = ""; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => true, "message" => "Database connection failed."]));
}

// Handle registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!isset($data['firstName'], $data['lastName'], $data['email'], $data['password'])) {
        echo json_encode(["error" => true, "message" => "All fields are required."]);
        exit();
    }

    $firstName = trim($data['firstName']);
    $lastName = trim($data['lastName']);
    $email = trim($data['email']);
    $password = password_hash(trim($data['password']), PASSWORD_DEFAULT);

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(["error" => true, "message" => "Email already registered."]);
        exit();
    }

    // Insert new admin
    $stmt = $pdo->prepare("INSERT INTO admins (first_name, last_name, email, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt->execute([$firstName, $lastName, $email, $password])) {
        echo json_encode(["error" => false, "message" => "Registration successful!"]);
    } else {
        echo json_encode(["error" => true, "message" => "Failed to register. Try again."]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="container"> 
        <h2>Create an Account</h2>
        <form id="registerForm">
            <div class="form-group">
                <label for="firstName">First Name</label>
                <input type="text" id="firstName" name="firstName" required>
            </div>
            <div class="form-group">
                <label for="lastName">Last Name</label>
                <input type="text" id="lastName" name="lastName" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required minlength="8">
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirmPassword" name="confirmPassword" required minlength="8">
                    <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" id="submitBtn">Register</button>
            </div>
        </form>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">Passwords do not match!</div>

    <script>
        function showToast(message) {
            const toast = document.getElementById("toast");
            toast.textContent = message;
            toast.classList.add("show");
            setTimeout(() => {
                toast.classList.remove("show");
            }, 3000);
        }

        function togglePasswordVisibility(inputId, toggleId) {
            const inputField = document.getElementById(inputId);
            const toggleIcon = document.getElementById(toggleId);

            toggleIcon.addEventListener("click", function () {
                if (inputField.type === "password") {
                    inputField.type = "text";
                    toggleIcon.classList.replace("fa-eye", "fa-eye-slash");
                } else {
                    inputField.type = "password";
                    toggleIcon.classList.replace("fa-eye-slash", "fa-eye");
                }
            });
        }

        togglePasswordVisibility("password", "togglePassword");
        togglePasswordVisibility("confirmPassword", "toggleConfirmPassword");

        document.getElementById('registerForm').addEventListener('submit', async function(event) {
            event.preventDefault();

            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const confirmPassword = document.getElementById('confirmPassword').value.trim();

            if (password !== confirmPassword) {
                showToast("Passwords do not match!");
                return;
            }

            const formData = { firstName, lastName, email, password };

            try {
                const response = await fetch("adminregister.php", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                alert(data.message);

                if (!data.error) {
                    window.location.href = 'login.php';
                }
            } catch (error) {
                console.error("Fetch error:", error);
                alert('Registration failed. Please check your server.');
            }
        });
    </script>
</body>
</html>
