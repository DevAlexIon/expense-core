<?php

require '../vendor/autoload.php';

use Firebase\JWT\JWT;

include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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

        $userId = $decoded->user_id;
        $stmt = $pdo->prepare("SELECT * FROM expenses WHERE user_id = ?");
        $stmt->execute([$userId]);
        $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode($expenses);
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
