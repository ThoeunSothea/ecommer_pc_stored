<?php
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/auth.php';

//  ត្រួតពិនិត្យ Order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id']; //  This is correct

if ($orderId <= 0) {
    die("Invalid order ID.");
}

//  ទាញព័ត៌មាន Order
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT o.*, u.username, u.email, u.phone 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.user_id 
          WHERE o.order_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: orders.php');
    exit;
}

$order = $result->fetch_assoc();

//  ទាញ Order Items
$query = "SELECT oi.*, p.name, p.image 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.product_id 
          WHERE oi.order_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result();

//  គណនា Subtotal
$subtotal = 0;
while ($item = $items->fetch_assoc()) {
    $subtotal += $item['price'] * $item['quantity'];
}
$items->data_seek(0); // Reset pointer for later display
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>ព័ត៌មានលម្អិតការកម្មង់</title>
    <link rel="stylesheet" href="../../assets/css/orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        button{
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="main-content">
    <header class="admin-header">
        <h1><i class="fas fa-file-alt"></i> ព័ត៌មានលម្អិតការកម្មង់ #<?= $order['order_code'] ?></h1>
        <div class="user-info">
            <span><?= $_SESSION['username'] ?></span>
            <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
        </div>
    </header>

    <div class="order-details-container">
        <!-- Order Info -->
        <div class="order-info">
            <div class="info-section">
                <h2><i class="fas fa-info-circle"></i> ព័ត៌មានការកម្មង់</h2>
                <div class="info-row"><span>លេខកូដ:</span><strong><?= $order['order_code'] ?></strong></div>
                <div class="info-row"><span>កាលបរិច្ឆេទ:</span><strong><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></strong></div>
                <div class="info-row"><span>ស្ថានភាព:</span>
                    <strong class="status-badge <?= $order['status'] ?>"><?= ucfirst($order['status']) ?></strong>
                </div>
                <div class="info-row"><span>វិធីទូទាត់:</span>
                    <strong>
                        <?= 
                        $order['payment_method'] === 'cash_on_delivery' ? 'ទូទាត់ពេលទទួលឥវ៉ាន់' :
                        ($order['payment_method'] === 'bank_transfer' ? 'ផ្ទេរប្រាក់តាមធនាគារ' : 'ABA Pay') 
                        ?>
                    </strong>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="info-section">
                <h2><i class="fas fa-user"></i> អតិថិជន</h2>
                <?php
                if (!empty($order['user_id'])) {

                    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?"); 
                    $stmt->bind_param("i", $order['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $customer = $result->fetch_assoc();
                    
                    if ($customer) {
                        ?>
                        <div class="info-row">
                            <span>ឈ្មោះ:</span>
                            <strong><?= htmlspecialchars($customer['username'] ?? $customer['name'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="info-row">
                            <span>អ៊ីម៉ែល:</span>
                            <strong><?= htmlspecialchars($customer['email'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="info-row">
                            <span>ទូរស័ព្ទ:</span>
                            <strong><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></strong>
                        </div>
                        <?php
                        // បន្ថែមព័ត៌មានផ្សេងៗទៀត
                        if (!empty($customer['address'])) {
                            echo '<div class="info-row"><span>អាសយដ្ឋាន:</span><strong>'.htmlspecialchars($customer['address']).'</strong></div>';
                        }
                    } else {
                        echo '<div class="info-row"><span>ព័ត៌មាន:</span><strong>រកមិនឃើញអតិថិជន (ID: '.htmlspecialchars($order['customer_id']).')</strong></div>';
                    }
                } else {
                    echo '<div class="info-row"><span>ព័ត៌មាន:</span><strong>គ្មាន ID អតិថិជន</strong></div>';
                }
                ?>
            </div>

            <!-- Shipping -->
            <div class="info-section">
                <h2><i class="fas fa-truck"></i> ដឹកជញ្ជូន</h2>
                <div class="shipping-address"><?= nl2br($order['shipping_address']) ?></div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="order-items">
            <h2><i class="fas fa-boxes"></i> ផលិតផលក្នុងកម្មង់</h2>
            <div class="items-table">
                <div class="items-header">
                    <div class="header-item">រូបភាព​ និង ឈ្មោះផលិតផល​</div>
                    <div class="header-item">តម្លៃ</div>
                    <div class="header-item">ចំនួន</div>
                    <div class="header-item">សរុប</div>
                </div>
               <?php 
                $subtotal = 0; // Initialize subtotal before the loop
                while ($item = $items->fetch_assoc()): 
                    $itemTotal = $item['price'] * $item['quantity'];
                    $subtotal += $itemTotal;
                ?>
                    <div class="item-row">
                        <div class="item-product">
                            <img src="../../assets/images/products/<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                            <div class="product-info"><h3><?= $item['name'] ?></h3></div>
                        </div>
                        <div class="item-price">$<?= number_format($item['price'], 2) ?></div>
                        <div class="item-quantity"><?= $item['quantity'] ?></div>
                        <div class="item-total">$<?= number_format($itemTotal, 2) ?></div>
                    </div>
                <?php endwhile; ?>

                <div class="order-totals">
                    <div class="total-row"><span>សរុបរង៖</span><span>$<?= number_format($subtotal, 2) ?></span></div>
                    <div class="total-row"><span>ដឹកជញ្ជូន៖</span><span>$5.00</span></div>
                    <div class="total-row grand-total"><span>សរុប:</span><span>$<?= number_format($subtotal + 5.00, 2) ?></span></div>
                </div>

            </div>
        </div>

        <!-- Notes -->
        <div class="order-notes">
            <h2><i class="fas fa-sticky-note"></i> ចំណាំ</h2>
            <p><?= !empty($order['notes']) ? nl2br($order['notes']) : 'មិនមានចំណាំ' ?></p>
        </div>

        <!-- Action buttons -->
        <div class="order-actions">
            <a href="orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> ត្រឡប់ក្រោយ</a>

            <?php if (!in_array($order['status'], ['completed', 'cancelled'])): ?>
                <button class="btn-update"
                        onclick="openStatusModal(<?= (int)$order['order_id'] ?>, '<?= htmlspecialchars($order['status'], ENT_QUOTES) ?>')">
                    <i class="fas fa-edit"></i> បច្ចុប្បន្នភាពស្ថានភាព
                </button>
            <?php endif; ?>


            <a href="invoice.php?id=<?= $order['order_id'] ?>" class="btn-invoice" target="_blank">
                <i class="fas fa-file-invoice"></i> វិក្កយបត្រ
            </a>
        </div>
    </div>
</div>

<!-- Modal for status update -->
<!-- Modal -->
<div class="modal" id="statusModal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeStatusModal()">&times;</span>
        <h2>បច្ចុប្បន្នភាពស្ថានភាព</h2>

        <form action="orders.php" method="POST">
            <input type="hidden" name="order_id" id="modalOrderId" value="">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-group">
                    <label for="modalStatus">ស្ថានភាព:</label>
                    <select id="modalStatus" name="status" class="form-control" required>
                        <option value="pending">កំពុងរង់ចាំ</option>
                        <option value="processing">កំពុងដំណើរការ</option>
                        <option value="completed">បានបញ្ចប់</option>
                        <option value="cancelled">បានលុបចោល</option>
                    </select>
            </div>
            <div class="form-actions">
                    <button type="submit" name="update_status" class="btn-save">
                    <i class="fas fa-save"></i> រក្សាទុក
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeStatusModal()">
                    <i class="fas fa-times"></i> បោះបង់
                    </button>
            </div>
        </form>
    </div>
</div>


</body>
</html>

<script>
function openStatusModal(orderId, currentStatus) {
  const modal = document.getElementById('statusModal');
  document.getElementById('modalOrderId').value = orderId;
  document.getElementById('modalStatus').value = currentStatus;
  modal.style.display = 'block';
}

function closeStatusModal() {
  document.getElementById('statusModal').style.display = 'none';
}

window.onclick = function(event) {
  const modal = document.getElementById('statusModal');
  if (event.target === modal) {
    closeStatusModal();
  }
}


</script>