<?php

require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

$validCategories = array('Salary', 'Bonus', 'Investment', 'Rent', 'Other');

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
        $user_id = $decoded->user_id;

        if (!isset($data['amount']) || !isset($data['description']) || !isset($data['category'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Invalid request. Please provide amount, description, and category."));
            exit();
        }

        if (!in_array($data['category'], $validCategories)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Invalid category. Please choose one of: Food, Transport, Utilities, Entertainment, Other"));
            exit();
        }

        $stmt = $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id =?');
        $stmt->execute([$data['amount'], $user_id]);

        if($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('INSERT INTO income (user_id, amount, description, category) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_id, $data['amount'], $data['description'], $data['category']]);
            http_response_code(201);
            header('Content-Type: application/json');
            echo json_encode(array("success" => true, "message" => "Income added successfully!"));
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Failed to update the user balance."));
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