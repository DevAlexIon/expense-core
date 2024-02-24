<?php
require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] === "PATCH") {
    $data = json_decode(file_get_contents("php://input"), true);

    $headers = getallheaders();
    if(!isset($headers['Authorization'])) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(array("error" => "Unauthorized"));
        exit();
    }

    $token = explode(" ", $headers['Authorization'])[1];

    try {
        $decoded = JWT::decode($token, "my_secret_key_123", array('HS256'));

        if(!isset($_GET["id"])) {
            http_response_code(400);
            header("Content-Type: application/json");
            echo json_encode(array("error" => "Invalid request. Please provide an expense ID."));
            exit();
        }

        $expenseId = $_GET["id"];
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ? AND user_id = ?");
        $stmt->execute(([$expenseId, $decoded->user_id]));
        $expense = $stmt->fetch();

        if(!$expense) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Expense not found or unauthorized."));
            exit();
        }

        if(isset($data['amount']) && !filter_var($data['amount'], FILTER_VALIDATE_FLOAT)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Invalid amount. Amount must be a number."));
            exit();
        }

        if (isset($data['category'])) {
            if (!is_string($data['category'])) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array("error" => "Invalid category value. Category must be a string."));
                exit();
            }

            $validCategories = ['Food', 'Transport', 'Utilities', 'Entertainment', 'Other'];
            if (!in_array($data['category'], $validCategories)) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array("error" => "Invalid category value. Allowed values are: 'Food', 'Transport', 'Utilities', 'Entertainment', 'Other'."));
                exit();
            }

            $expense['category'] = $data['category'];
        }

        if (isset($data['description'])) {
            if (!is_string($data['description'])) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(array("error" => "Invalid description value. Description must be a string."));
                exit();
            }
            $expense['description'] = $data['description'];
        }

        $stmt = $pdo->prepare("UPDATE expenses SET amount = ?, category = ?, description = ? WHERE id = ?");
        $stmt->execute([$expense['amount'], $expense['category'], $expense['description'], $expenseId]);


        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(array("success" => true, "message" => "Expense updated successfully."));

    } catch (Exception $e) {
        http_response_code(401);
        echo 'Error: ' . $e->getMessage();
        header("Content-Type: application/json");
        echo json_encode(array("error" => "Invalid or expired token"));
    }
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Method Not Allowed."));
}
?>
