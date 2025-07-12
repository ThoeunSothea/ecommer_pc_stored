<?php
require_once __DIR__ .'/../../includes/config.php';
require_once __DIR__ .'/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID មិនត្រឹមត្រូវ!");
}

$id = (int) $_GET['id'];

$conn = $db->getConnection();
$stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: manage_products.php?deleted=1");
    exit;
} else {
    echo "លុបមិនបានទេ!";
}

?>