<?php
ob_start();
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

if (empty($_GET['order_code'])) {
    header('Location: main.php');
    exit;
}

$orderCode = $_GET['order_code'];


$query = "SELECT o.*, u.username, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.user_id 
          WHERE o.order_code = ?";
$stmt = $db->getConnection()->prepare($query);
$stmt->bind_param('s', $orderCode);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if ($order) {

    $query = "SELECT oi.*, p.name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.product_id 
              WHERE oi.order_id = ?";
    $stmt = $db->getConnection()->prepare($query);
    $stmt->bind_param('i', $order['order_id']);
    $stmt->execute();
    $items = $stmt->get_result();
}
?>

<link rel="stylesheet" href="../assets/css/style.css">

<div class="container order-complete-container main-content">
    <div class="order-complete-card">
        <div class="order-complete-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h1>ការកម្មង់របស់អ្នកត្រូវបានជោគជ័យ!</h1>

        <?php if ($order): ?>
            <div class="order-summary">
                <div class="summary-row">
                    <span>លេខកូដកម្មង់៖</span>
                    <strong><?= htmlspecialchars($order['order_code']) ?></strong>
                </div>

                <div class="summary-row">
                    <span>កាលបរិច្ឆេទបញ្ចូល៖</span>
                    <strong><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></strong>
                </div>

                <div class="summary-row">
                    <span>កាលបរិច្ឆេទកម្មង់៖</span>
                    <strong>
                        <?= $order['order_date'] === '0000-00-00 00:00:00' ? 'N/A' : date('d/m/Y H:i', strtotime($order['order_date'])) ?>
                    </strong>
                </div>

                <div class="summary-row">
                    <span>ស្ថានភាពកម្មង់៖</span>
                    <strong class="status <?= htmlspecialchars($order['status']) ?>">
                        <?= ucfirst(htmlspecialchars($order['status'])) ?>
                    </strong>
                </div>

                <div class="summary-row">
                    <span>ស្ថានភាពទូទាត់៖</span>
                    <strong><?= ucfirst(htmlspecialchars($order['payment_status'] ?? 'pending')) ?></strong>
                </div>

                <div class="summary-row">
                    <span>វិធីសាបរយៈ៖</span>
                    <strong>
                        <?= 
                            $order['payment_method'] === 'cash_on_delivery' ? 'ទូទាត់ពេលទទួលឥវ៉ាន់' :
                            ($order['payment_method'] === 'bank_transfer' ? 'ផ្ទេរប្រាក់តាមធនាគារ' : 'ABA Pay')
                        ?>
                    </strong>
                </div>

                <div class="summary-row grand-total">
                    <span>សរុប៖</span>
                    <strong>$<?= number_format((float)$order['total_amount'], 2) ?></strong>
                </div>
            </div>

            <div class="order-items">
                <h3>ផលិតផលក្នុងកម្មង់</h3>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <?php
                        $imageFile = "../assets/images/products/" . htmlspecialchars($item['image']);
                        if (empty($item['image']) || !file_exists($imageFile)) {
                            $imageFile = "../assets/images/products/default.png";
                        }
                    ?>
                    <div class="order-item">
                        <img src="<?= $imageFile ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 100px;">
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <div class="item-meta">
                                <span>ចំនួន៖ <?= (int)$item['quantity'] ?></span>
                                <span>តម្លៃ៖ $<?= number_format((float)$item['price'], 2) ?></span>
                                <span>សរុប៖ $<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="order-actions">
                <a href="products.php" class="btn btn-continue">
                    <i class="fas fa-shopping-bag"></i> បន្តទិញឥវ៉ាន់
                </a>
                <a href="order_complete.php?order_code=<?= htmlspecialchars($order['order_code']) ?>" class="btn btn-details">
                    <i class="fas fa-file-alt"></i> ព័ត៌មានលម្អិត
                </a>
            </div>

            <div class="order-note">
                <p>យើងនឹងផ្ញើអ៊ីម៉ែលបញ្ជាក់កម្មង់ទៅកាន់ <strong><?= htmlspecialchars($order['email']) ?></strong>។ សូមពិនិត្យមើលក្នុងអ៊ីម៉ែលរបស់អ្នក។</p>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">ការកម្មង់នេះមិនមានទេ។ សូមត្រួតពិនិត្យលេខកូដកម្មង់របស់អ្នក។</div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const icon = document.querySelector('.order-complete-icon i');
    if (icon) {
        icon.style.opacity = 0;
        icon.animate([
            { transform: 'scale(0.5)', opacity: 0 },
            { transform: 'scale(1.2)', opacity: 1 },
            { transform: 'scale(1)', opacity: 1 }
        ], {
            duration: 800,
            easing: 'ease-out',
            fill: 'forwards'
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ob_end_flush(); ?>
