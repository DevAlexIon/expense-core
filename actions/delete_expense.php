<?php
global $pdo;
require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
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
        $stmt->execute([$expenseId, $decoded->user_id]);
        $expense = $stmt->fetch();

        if(!$expense) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "Expense not found or unauthorized."));
            exit();
        }

        $stmt = $pdo->prepare(" DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$expenseId]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(array("success" => true, "message" => "Expense deleted successfully."));
        } else {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(array("error" => "An error occurred while deleting the expense."));
        }
    } catch (Exception $e) {
        http_response_code(401);
        header("Content-Type: application/json");
        echo json_encode(array("error" => "Invalid or expired token"));
    }
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(array("error" => "Method Not Allowed."));
}


?>