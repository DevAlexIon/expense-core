<?php

require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

session_start();

$inputJSON = file_get_contents('php://input');

$input = json_decode($inputJSON, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($input['email']) && isset($input['password'])) {
        $email = $input['email'];
        $password = $input['password'];

        if (empty($email) || empty($password)) {
            $response = array("error" => "Email and password are required");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $length = 32;
                    $secret_key = "my_secret_key_123";

                    $issued_at = time();
                    $expiration_time = $issued_at + 300;
                    $payload = array(
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'issued_at' => $issued_at,
                        'expiration_time' => $expiration_time
                    );
                    $jwt = JWT::encode($payload, $secret_key, 'HS256');

                    $response = array(
                        "success" => true,
                        "message" => "Login successful",
                        "token" => $jwt
                    );
                } else {
                    $response = array("error" => "Invalid email or password");
                }
            } else {
                $response = array("error" => "User not found");
            }
        }
    } else {
        $response = array("error" => "Email and password are required");
    }
} else {
    $response = array("error" => "Invalid request method");
}

header('Content-Type: application/json');
echo json_encode($response);

?>
