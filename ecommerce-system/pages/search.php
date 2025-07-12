<?php
ob_start();
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

// 1. Grab and sanitize input
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search_term === '') {
    header('Location: products.php');
    exit;
}

// 2. Prepare SQL for name-only partial match
$conn = $db->getConnection();

$sql = "
    SELECT * FROM products 
    WHERE status = 'active' 
      AND name LIKE ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
$like_term = "%{$search_term}%";
$stmt->bind_param('s', $like_term);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>

<link rel="stylesheet" href="../assets/css/style.css">
<br>
<div class="container search-results">
  <div class="search-header">
    <h2>
      លទ្ធផលស្វែងរកសម្រាប់ 
      "<span class="search-term"><?= htmlspecialchars($search_term, ENT_QUOTES) ?></span>"
    </h2>
  </div>

  <?php if ($result->num_rows > 0): ?>
    <div class="search-grid">
      <?php while ($p = $result->fetch_assoc()): 
        $id    = (int)$p['product_id'];
        $name  = htmlspecialchars($p['name'], ENT_QUOTES);
        $img   = htmlspecialchars($p['image'] ?: 'no-image.png', ENT_QUOTES);
        $price = (float)$p['price'];
        $disc  = (float)$p['discount'];

        // Optional: Highlight matching term
        $name_display = preg_replace(
          "/(" . preg_quote($search_term, '/') . ")/i",
          '<span class="highlight">$1</span>',
          $name
        );
      ?>
      <div class="product-card">
        <div class="product-image">
          <img 
            src="<?= BASE_URL ?>/assets/images/products/<?= rawurlencode($img) ?>" 
            alt="<?= $name ?>" 
            loading="lazy"
          >
        </div>
        <div class="product-info">
          <h3 class="product-name"><?= $name_display ?></h3>
          <div class="product-price">
            <?php if ($disc > 0):
              $sale = $price * (1 - $disc / 100);
            ?>
              <span class="original-price">$<?= number_format($price, 2) ?></span>
              <span class="discounted-price">$<?= number_format($sale, 2) ?></span>
            <?php else: ?>
              <span class="price-only">$<?= number_format($price, 2) ?></span>
            <?php endif; ?>
          </div>
          <a 
            href="<?= BASE_URL ?>/pages/product.php?id=<?= $id ?>" 
            class="btn btn-primary"
          >មើលលម្អិត</a>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="no-results">
      <h3>មិនមានលទ្ធផលសម្រាប់ "<?= htmlspecialchars($search_term, ENT_QUOTES) ?>"</h3>
      <p>សូម​ព្យាយាម​ស្វែងរក​ជាមួយពាក្យផ្សេងទៀត</p>
      <a href="<?= BASE_URL ?>/pages/products.php" class="btn btn-outline-primary">មើលផលិតផលទាំងអស់</a>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php ob_end_flush(); ?>
