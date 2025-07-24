<?php
require_once __DIR__ . '/api/db.php';
$pdo = connect();

$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Extract ID if present (e.g., /products/5)
$matches = [];
preg_match('/\/products\/(\d+)/', $requestUri, $matches);
$id = $matches[1] ?? null;

// Set JSON response header
header('Content-Type: application/json');

if ($requestMethod === 'GET') {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($product ?: ["message" => "Product not found"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM products");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
} elseif ($requestMethod === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['name'], $data['price'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing name or price"]);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
    $stmt->execute([$data['name'], $data['price']]);
    echo json_encode(["message" => "Product created", "id" => $pdo->lastInsertId()]);
} elseif ($requestMethod === 'PUT' && $id) {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE products SET name = ?, price = ? WHERE id = ?");
    $stmt->execute([$data['name'], $data['price'], $id]);
    echo json_encode(["message" => "Product updated"]);
} elseif ($requestMethod === 'DELETE' && $id) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(["message" => "Product deleted"]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
?>
