<?php

define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = new Database();
$conn = $db->getConnection();

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$categoryName = 'ផលិតផលទាំងអស់';
$products = [];
$groupedProducts = [];

if ($categoryId > 0) {

    $stmt = $conn->prepare("SELECT name FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $stmt->bind_result($categoryName);
    $stmt->fetch();
    $stmt->close();


    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'active' ORDER BY created_at DESC");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} else {

    $sql = "SELECT c.category_id, c.name AS category_name, p.*
            FROM categories c
            JOIN products p ON c.category_id = p.category_id
            WHERE p.status = 'active'
            ORDER BY c.name ASC, p.created_at DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $catId = $row['category_id'];
        $catName = $row['category_name'];
        if (!isset($groupedProducts[$catId])) {
            $groupedProducts[$catId] = ['name' => $catName, 'products' => []];
        }
        $groupedProducts[$catId]['products'][] = $row;
    }
}
$conn->close();

$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/products.css">
<link rel="stylesheet" href="../assets/css/product.css">

<br>
<div class="container">
  <nav aria-label="breadcrumb" class="breadcrumb-nav">
    <ol>
      <li><a href="<?= BASE_URL ?>/pages/main.php">ទំព័រដើម</a></li>
      <?php if ($categoryId > 0): ?>
        <li><a href="<?= BASE_URL ?>/pages/categories.php">ប្រភេទផលិតផល</a></li>
        <li class="active" aria-current="page"><?= $categoryName ?></li>
      <?php else: ?>
        <li class="active" aria-current="page">ផលិតផលទាំងអស់</li>
      <?php endif; ?>
    </ol>
  </nav>

  <h1><?= htmlspecialchars($categoryName) ?></h1>

  <?php if ($categoryId > 0): ?>
    <div class="products-grid">
      <?php foreach ($products as $p): ?>
        <div class="product-card">
          <div class="product-image">
            <img src="<?= BASE_URL ?>/assets/images/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
          </div>
          <div class="product-info">
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <p class="price">$<?= number_format($p['price'], 2) ?></p>
          </div>
          <div class="product-actions">
              <button class="btn-wishlist" data-id="<?= $p['product_id'] ?>"><i class="far fa-heart"></i></button>
              <button class="btn-quickview" data-id="<?= $p['product_id'] ?>"><i class="far fa-eye"></i></button>
              <form method="POST" action="cart.php" class="add-to-cart-form" style="display:none;">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                <input type="hidden" name="quantity" value="1">
              </form>
              <button class="btn-add-to-cart" data-id="<?= $p['product_id'] ?>"><i class="fas fa-shopping-cart"></i></button>
            </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <?php foreach ($groupedProducts as $cat): ?>
      <h2><?= htmlspecialchars($cat['name']) ?></h2>
      <div class="products-grid">
        <?php foreach ($cat['products'] as $p): ?>
          <div class="product-card">
            <div class="product-image">
              <img src="<?= BASE_URL ?>/assets/images/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.src='<?= BASE_URL ?>/assets/images/products/no-image.png'">
            </div>
            <div class="product-info">
              <h3><?= htmlspecialchars($p['name']) ?></h3>
              <p class="price">$<?= number_format($p['price'], 2) ?></p>
            </div>
            <div class="product-actions">
              <button class="btn-wishlist" data-id="<?= $p['product_id'] ?>"><i class="far fa-heart"></i></button>
              <button class="btn-quickview" data-id="<?= $p['product_id'] ?>"><i class="far fa-eye"></i></button>
              <form method="POST" action="cart.php" class="add-to-cart-form" style="display:none;">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                <input type="hidden" name="quantity" value="1">
              </form>
              <button class="btn-add-to-cart" data-id="<?= $p['product_id'] ?>"><i class="fas fa-shopping-cart"></i></button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {

    document.querySelectorAll('.btn-wishlist').forEach(function(btn) {
      btn.addEventListener('click', function() {
        this.classList.toggle('active');
        this.animate([
          { transform: 'scale(1)', color: '#888' },
          { transform: 'scale(1.2)', color: '#e74c3c' },
          { transform: 'scale(1)', color: '#e74c3c' }
        ], {
          duration: 300,
          easing: 'ease-out'
        });
      });
    });


    document.querySelectorAll('.btn-quickview').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const productId = this.dataset.id;
        alert('Quick view for product ID: ' + productId);
      });
    });


    document.querySelectorAll('.btn-add-to-cart').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const form = this.closest('.product-card').querySelector('.add-to-cart-form');
        this.animate([
          { transform: 'scale(1)', backgroundColor: '#3498db' },
          { transform: 'scale(1.2)', backgroundColor: '#2ecc71' },
          { transform: 'scale(1)', backgroundColor: '#3498db' }
        ], {
          duration: 300,
          easing: 'ease-out'
        });
        if (form) form.submit();
      });
    });
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>