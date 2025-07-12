<?php
ob_start();
define('ALLOW_ACCESS', true);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';



if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}


$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $discountedPrice = $item['price'] * (1 - $item['discount'] / 100);
    $subtotal += $discountedPrice * $item['quantity'];
}

$shipping = 5.00;
$total = $subtotal + $shipping;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $errors = [];
    $requiredFields = ['full_name', 'email', 'phone', 'address', 'payment_method'];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[$field] = 'ចាំបាច់ត្រូវតែបំពេញ';
        }
    }

    if (empty($errors)) {
        $orderCode = 'ORD-' . strtoupper(uniqid());
        $userId = $_SESSION['user_id'] ?? null;
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $paymentMethod = trim($_POST['payment_method']);
        $notes = trim($_POST['notes'] ?? '');

        $conn = $db->getConnection(); 
        $conn->begin_transaction();

        try {
            $orderQuery = "INSERT INTO orders (order_code, user_id, total_amount, shipping_address, payment_method, notes) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($orderQuery);
            if ($userId === null) {
                $nullUserId = null;
                $stmt->bind_param('sidsss', $orderCode, $nullUserId, $total, $address, $paymentMethod, $notes);
            } else {
                $stmt->bind_param('sidsss', $orderCode, $userId, $total, $address, $paymentMethod, $notes);
            }
            $stmt->execute();
            $orderId = $stmt->insert_id;

            $orderItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                               VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($orderItemQuery);

            foreach ($_SESSION['cart'] as $item) {
                $discountedPrice = $item['price'] * (1 - $item['discount'] / 100);
                $stmt->bind_param('iidd', $orderId, $item['product_id'], $item['quantity'], $discountedPrice);
                $stmt->execute();

                $updateStockQuery = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
                $stmt2 = $conn->prepare($updateStockQuery);
                $stmt2->bind_param('ii', $item['quantity'], $item['product_id']); 
                $stmt2->execute();
                $stmt2->close();

            }

            $conn->commit();
            unset($_SESSION['cart']);
            header('Location: order_complete.php?order_code=' . $orderCode);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = 'មានបញ្ហាក្នុងការបញ្ជាទិញ៖ ' . $e->getMessage();
        }
    }
}

$user = null;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT * FROM users WHERE user_id = ?";
    $conn = $db->getConnection();  
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    } else {
        die("Prepare failed: " . $conn->error);
    }
}

?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/style.css">

<br>
<div class="container checkout-container">
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <ol>
        <li><a href="<?= BASE_URL ?>/pages/main.php">ទំព័រដើម</a></li>
        <li class="active" aria-current="page">ទូទាត់</li>
        </ol>
    </nav>

    <h1 class="page-title">ទូទាត់</h1>

    <?php if (!empty($errors['database'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($errors['database']) ?>
        </div>
    <?php endif; ?>

    <form action="checkout.php" method="POST" class="checkout-form">
        <div class="checkout-columns">
            <div class="checkout-details">
                <h2>ព័ត៌មានអតិថិជន</h2>

                <div class="form-group">
                    <label for="full_name">ឈ្មោះពេញ *</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($user['full_name'] ?? ($_POST['full_name'] ?? '')) ?>" required>
                    <?php if (!empty($errors['full_name'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['full_name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">អ៊ីម៉ែល *</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email'] ?? ($_POST['email'] ?? '')) ?>" required>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone">លេខទូរស័ព្ទ *</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?= htmlspecialchars($user['phone'] ?? ($_POST['phone'] ?? '')) ?>" required>
                    <?php if (!empty($errors['phone'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="address">អាសយដ្ឋាន *</label>
                    <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($user['address'] ?? ($_POST['address'] ?? '')) ?></textarea>
                    <?php if (!empty($errors['address'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['address']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="notes">ចំណាំបន្ថែម (បើមាន)</label>
                    <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="checkout-summary">
                <h2>ការកម្មង់របស់អ្នក</h2>

                <div class="order-summary">
                    <?php if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])): ?>
                        <?php foreach ($_SESSION['cart'] as $item):
                            $discountedPrice = $item['price'] * (1 - $item['discount'] / 100);
                        ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                    <span class="item-quantity">x<?= (int)$item['quantity'] ?></span>
                                </div>
                                <div class="item-price">
                                    $<?= number_format($discountedPrice * $item['quantity'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>ក្រឡាចត្រង្គទំនិញរបស់អ្នកទទេ</p>
                    <?php endif; ?>

                    <div class="order-total">
                        <div class="total-row">
                            <span>សរុបរង៖</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="total-row">
                            <span>ដឹកជញ្ជូន៖</span>
                            <span>$<?= number_format($shipping, 2) ?></span>
                        </div>
                        <div class="total-row grand-total">
                            <span>សរុប៖</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                </div>

                <div class="payment-method">
                    <h3>វិធីសាបរយៈ</h3>

                    <div class="form-group">
                        <input type="radio" id="payment_cod" name="payment_method" value="cash_on_delivery"
                               <?= (($_POST['payment_method'] ?? '') === 'cash_on_delivery') ? 'checked' : '' ?> required>
                        <label for="payment_cod">ទូទាត់ពេលទទួលឥវ៉ាន់</label>
                    </div>

                    <div class="form-group">
                        <input type="radio" id="payment_bank" name="payment_method" value="bank_transfer"
                               <?= (($_POST['payment_method'] ?? '') === 'bank_transfer') ? 'checked' : '' ?>>
                        <label for="payment_bank">ផ្ទេរប្រាក់តាមធនាគារ</label>
                    </div>

                    <div class="form-group">
                        <input type="radio" id="payment_aba" name="payment_method" value="aba_pay"
                               <?= (($_POST['payment_method'] ?? '') === 'aba_pay') ? 'checked' : '' ?>>
                        <label for="payment_aba">ABA Pay</label>
                    </div>

                    <?php if (!empty($errors['payment_method'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['payment_method']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" name="place_order" class="btn btn-place-order">
                    បញ្ជាទិញ
                </button>
            </div>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.checkout-form');

    form.addEventListener('submit', (e) => {
        // Clear previous error highlights
        form.querySelectorAll('.error').forEach(el => el.textContent = '');

        let valid = true;

        // Example validation for full_name
        const fullName = form.full_name.value.trim();
        if (fullName.length < 3) {
            showError('full_name', 'សូមបញ្ចូលឈ្មោះពេញត្រឹមត្រូវ');
            valid = false;
        }

        // Email validation
        const email = form.email.value.trim();
        if (!validateEmail(email)) {
            showError('email', 'សូមបញ្ចូលអ៊ីម៉ែលត្រឹមត្រូវ');
            valid = false;
        }

        // Phone validation (basic)
        const phone = form.phone.value.trim();
        if (phone.length < 8) {
            showError('phone', 'សូមបញ្ចូលលេខទូរស័ព្ទត្រឹមត្រូវ');
            valid = false;
        }

        // Address required
        const address = form.address.value.trim();
        if (address.length < 5) {
            showError('address', 'សូមបញ្ចូលអាសយដ្ឋាន');
            valid = false;
        }

        // Payment method required
        if (!form.payment_method.value) {
            showError('payment_method', 'សូមជ្រើសរើសវិធីសាបង់ប្រាក់');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        }

        function showError(fieldName, message) {
            const field = form.querySelector(`[name=${fieldName}]`);
            let errorEl = field.closest('.form-group').querySelector('.error');
            if (!errorEl) {
                errorEl = document.createElement('span');
                errorEl.classList.add('error');
                field.closest('.form-group').appendChild(errorEl);
            }
            errorEl.textContent = message;
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email.toLowerCase());
        }
    });
});

</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php ob_end_flush(); ?>
