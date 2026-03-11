<?php
require 'config.php';

$userId     = "ADM001";
$fullName   = "Super Administrator";
$email      = "admin@gmail.com";
$password   = "admin123"; 
$role       = "Super Admin";
$department = "Executive Management";

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertQuery = $conn->prepare("INSERT INTO users (user_id, fullname, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
$insertQuery->bind_param("ssssss", $userId, $fullName, $email, $hashedPassword, $role, $department);

if ($insertQuery->execute()) {
    echo "<h1>Success!</h1>";
    echo "<p>Super Admin account created successfully.</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
    echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "<p style='color: red;'><strong>Important:</strong> Please delete this file (seed_admin.php) from your server now for security reasons!</p>";
} else {
    echo "<h1>Error Seeding Database</h1>";
    echo "Error: " . $conn->error;
}
?>