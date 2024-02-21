<?php

require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

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

        if (!isset($data['amount']) || !isset($data['description']) || !isset($data['category'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Invalid request. Please provide amount, description, and category."));
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, amount, description, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$decoded->user_id, $data['amount'], $data['description'], $data['category']]);
        $expenseId = $pdo->lastInsertId();

        if ($expenseId) {
            $successMessage = "Expense added successfully.";

            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode(array("success" => true, "message" => $successMessage));
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "An error occurred while adding the expense."));
        }
    } catch (Exception $e) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(array("error" => "Invalid or expired token"));
    }
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Method Not Allowed"));
}

?>
