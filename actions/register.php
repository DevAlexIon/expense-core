<?php
include '../includes/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];

    if(empty($username) || empty($email) || empty($password)) {
        $response = array("success" => false, "message" => "All fields are required");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = array("success" => false, "message" => "Invalid email format");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user) {
            $response = array("success" => false, "message" => "Email already exists");
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            $response = array("success" => true, "message" => "Registration successful");
        }
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
