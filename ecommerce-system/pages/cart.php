<?php
define('ALLOW_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class CartHandler {
    private $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
    }

    public function addToCart(int $productId, int $quantity): string {
        $sql  = "SELECT * FROM products WHERE product_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            throw new Exception('ផលិតផលមិនត្រូវបានរកឃើញ');
        }

        $product = $res->fetch_assoc();

        if ($quantity > (int)$product['quantity']) {
            throw new Exception('ស្តុកមិនគ្រប់គ្រាន់');
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = [
                'product_id' => $productId,
                'name'       => $product['name'],
                'price'      => $product['price'],
                'discount'   => $product['discount'],
                'image'      => $product['image'],
                'quantity'   => $quantity,
                'stock'      => $product['quantity'],
            ];
        }

        return 'ផលិតផលត្រូវបានបញ្ចូលទៅកន្ត្រកដោយជោគជ័យ!';
    }
}

$db = new Database();
$conn = $db->getConnection();

// PROCESS POST REQUESTS FIRST to avoid headers sent error
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF token check
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }

        $handler = new CartHandler($conn);

        if (isset($_POST['add_to_cart'])) {
            $pid = (int)$_POST['product_id'];
            $qty = max(1, (int)$_POST['quantity']);
            $message = $handler->addToCart($pid, $qty);
            $_SESSION['success_message'] = $message;

        } elseif (isset($_POST['update_cart']) && isset($_POST['quantity']) && is_array($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $pid => $qty) {
                $pid = (int)$pid;
                $qty = max(0, (int)$qty);
                if (isset($_SESSION['cart'][$pid])) {
                    if ($qty > 0) {
                        $_SESSION['cart'][$pid]['quantity'] = $qty;
                    } else {
                        unset($_SESSION['cart'][$pid]);
                    }
                }
            }
            $_SESSION['success_message'] = 'កន្ត្រកត្រូវបានធ្វើបច្ចុប្បន្នភាពដោយជោគជ័យ!';

        } elseif (isset($_POST['remove_from_cart'])) {
            $productId = $_POST['product_id'] ?? null;
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));

            if ($productId && isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] -= $quantity;
                if ($_SESSION['cart'][$productId]['quantity'] <= 0) {
                    unset($_SESSION['cart'][$productId]);
                }
                $_SESSION['success_message'] = 'បានដកចេញពីកន្ត្រកដោយជោគជ័យ!'; // <-- Add success message here
            }
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }

    // Redirect back to referring page or cart.php
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
    exit;
}


if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
        $_SESSION['success_message'] = 'ផលិតផលត្រូវបានដកចេញពីកន្ត្រកដោយជោគជ័យ!';
    }
    header('Location: cart.php');
    exit;
}
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];  
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/order.css">
<link rel="stylesheet" href="../assets/css/style.css">

<br>
<br>
<div class="container cart-container">
<br>
    <h1 class="page-title">កន្ត្រកទិញឥវ៉ាន់</h1>
<br>
    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']); ?>
            <?php unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']); ?>
            <?php unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['cart'])): ?>
        <form action="cart.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="cart-table">
                <div class="cart-header">
                    <div class="header-item product">ផលិតផល</div>
                    <div class="header-item price">តម្លៃ</div>
                    <div class="header-item quantity">ចំនួន</div>
                    <div class="header-item total">សរុប</div>
                    <div class="header-item action">សកម្មភាព</div>
                </div>

                <?php
                $subtotal = 0;
                foreach ($_SESSION['cart'] as $item):
                    $discounted = $item['price'] * (1 - $item['discount']/100);
                    $lineTotal  = $discounted * $item['quantity'];
                    $subtotal  += $lineTotal;
                ?>
                <div class="cart-row">
                    <div class="cart-item product">
                        <img
                            src="<?= BASE_URL ?>/assets/images/products/<?= rawurlencode($item['image'] ?: 'no-image.png') ?>"
                            alt="<?= htmlspecialchars($item['name']) ?>"
                            onerror="this.src='<?= BASE_URL ?>/assets/images/products/no-image.png'">
                        <div class="product-info">
                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                            <?php if ((int)$item['discount'] > 0): ?>
                                <span class="original-price">$<?= number_format($item['price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="cart-item price">
                        $<?= number_format($discounted, 2) ?>
                    </div>

                    <div class="cart-item quantity">
                        <input
                            type="number"
                            name="quantity[<?= $item['product_id'] ?>]"
                            value="<?= $item['quantity'] ?>"
                            min="1"
                            max="<?= $item['stock'] ?>">
                    </div>

                    <div class="cart-item total">
                        $<?= number_format($lineTotal, 2) ?>
                    </div>

                    <div class="cart-item action">
                        <!-- Remove button -->
                        <a href="cart.php?remove=<?= $item['product_id'] ?>" class="remove-item" title="ដកចេញ">
                            <i class="fas fa-trash"></i>
                        </a>

                        <!-- Add 1 more button -->
                        <!-- Add 1 -->
                        <form method="POST" action="cart.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-add-more" title="បន្ថែម 1">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>

                        <!-- Remove 1 -->
                        <form method="POST" action="cart.php" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="remove_from_cart" value="1">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-remove" title="ដកចេញ 1">
                                <i class="fas fa-minus"></i>
                            </button>
                        </form>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>សរុបរង:</span>
                    <span>$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>ដឹកជញ្ជូន:</span>
                    <span>$5.00</span>
                </div>
                <div class="summary-row grand-total">
                    <span>សរុប:</span>
                    <span>$<?= number_format($subtotal + 5, 2) ?></span>
                </div>

                <div class="cart-actions">
                    <button type="submit" name="update_cart" class="btn btn-update">
                        <i class="fas fa-sync-alt"></i> ធ្វើបច្ចុប្បន្នភាព
                    </button>
                    <a href="checkout.php" class="btn btn-checkout">
                        <i class="fas fa-credit-card"></i> ទូទាត់
                    </a>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart fa-3x"></i>
            <h3>កន្ត្រកទិញឥវ៉ាន់របស់អ្នកទទេ</h3>
            <p>សូមបន្ថែមផលិតផលមួយចំនួនទៅកន្ត្រក!</p>
            <a href="main.php" class="btn btn-shop">បន្តទិញឥវ៉ាន់</a>
        </div>
    <?php endif; ?>
    <br>
</div>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
        if (!confirm('តើអ្នកពិតជាចង់លុបផលិតផលនេះពីកន្ត្រក?')) {
            e.preventDefault();
        }
        });
    });
});
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.btn-add-more, .btn-remove').forEach(button => {
    button.addEventListener('click', e => {
      e.preventDefault();    
      const form = button.closest('form');
      if (!form) return;
      button.disabled = true;
      form.submit();
    });
  });
});

</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
