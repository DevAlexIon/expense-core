<?php

require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $headers = getallheaders();

    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(array("error" => "Unauthorized"));
        exit();
    }

    $token = explode(" ", $headers['Authorization'])[1];

    try {
        $decoded = JWT::decode($token, "my_secret_key_123", array('HS256'));

        $user_id = $decoded->user_id;

        if (!isset($data['balance'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Invalid request. Balance cannot be empty."));
            exit();
        }

        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$data['balance'], $user_id]);

        if ($stmt->rowCount() > 0) {
            $successMessage = "Balance updated successfully.";

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(array("success" => true, "message" => $successMessage));
        } else {
            http_response_code(404);
            header("Content-Type: application/json");
            echo json_encode(array("error" => "User not found."));
        }
    } catch (Exception $e) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'Invalid or expired token'));
    }
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Method Not Allowed"));
}

?>
