<?php
// admin/invoice.php
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/auth.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];

$query = "
    SELECT o.*, u.username, u.email, u.phone
    FROM orders AS o
    LEFT JOIN users AS u
         ON o.user_id = u.user_id
    WHERE o.order_id = ?
";
$stmt = $db->getConnection()->prepare($query);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows === 0) {
    header('Location: orders.php');
    exit;
}

$order = $result->fetch_assoc();

// Get order items
$query = "
    SELECT
        oi.*,
        p.name
    FROM order_items AS oi
    JOIN products AS p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
";
$stmt = $db->getConnection()->prepare($query);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$items = $stmt->get_result();


// Calculate subtotal
$subtotal = 0;
while ($item = $items->fetch_assoc()) {
    $subtotal += $item['price'] * $item['quantity'];
}
$items->data_seek(0); // Reset pointer for reuse
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>វិក្កយបត្រ #<?= $order['order_code'] ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="logo">
                <h1>ហាងអនឡាញរបស់យើង</h1>
                <p>វិក្កយបត្រ</p>
            </div>
            
            <div class="invoice-info">
                <div class="info-row">
                    <span>លេខវិក្កយបត្រ៖</span>
                    <strong>#<?= $order['order_code'] ?></strong>
                </div>
                <div class="info-row">
                    <span>កាលបរិច្ឆេទ៖</span>
                    <strong><?= date('d/m/Y', strtotime($order['created_at'])) ?></strong>
                </div>
                <div class="info-row">
                    <span>ស្ថានភាព៖</span>
                    <strong class="status <?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </strong>
                </div>
            </div>
        </div>
        
        <div class="invoice-details">
            <div class="from-to">
                <div class="from">
                    <h3>ពី៖</h3>
                    <p>ហាងអនឡាញរបស់យើង</p>
                    <p>ផ្ទះលេខ 123, ផ្លូវព្រះមុន្នីវង្ស</p>
                    <p>រាជធានីភ្នំពេញ</p>
                    <p>ទូរស័ព្ទ៖ 012 345 678</p>
                    <p>អ៊ីម៉ែល៖ info@ourstore.com</p>
                </div>
                
                <div class="to">
                    <h3>ទៅ៖</h3>
                    <div class="info-row">
                        <span class="label">ឈ្មោះ:</span>
                        <span class="value"><?= htmlspecialchars($order['username'] ?? $order['username'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">អ៊ីម៉ែល:</span>
                        <span class="value"><?= htmlspecialchars($order['email'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">ទូរស័ព្ទ:</span>
                        <span class="value"><?= htmlspecialchars($order['phone'] ?? '') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">អាសយដ្ឋាន:</span>
                        <span class="value"><?= nl2br(htmlspecialchars($order['shipping_address'] ?? '')) ?></span>
                    </div>
                </div>

            </div>
        </div>
        
        <div class="invoice-items">
            <table>
                <thead>
                    <tr>
                        <th>ល.រ</th>
                        <th>ផលិតផល</th>
                        <th>តម្លៃ</th>
                        <th>ចំនួន</th>
                        <th>សរុប</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= $item['name'] ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right">សរុបរង៖</td>
                        <td>$<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right">ដឹកជញ្ជូន៖</td>
                        <td>$5.00</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right grand-total">សរុប៖</td>
                        <td class="grand-total">$<?= number_format($order['total_amount'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="invoice-footer">
            <div class="payment-method">
                <h3>វិធីសាបរយៈ៖</h3>
                <p>
                    <?= 
                    $order['payment_method'] === 'cash_on_delivery' ? 'ទូទាត់ពេលទទួលឥវ៉ាន់' : 
                    ($order['payment_method'] === 'bank_transfer' ? 'ផ្ទេរប្រាក់តាមធនាគារ' : 'ABA Pay')
                    ?>
                </p>
            </div>
            
            <div class="thank-you">
                <p>សូមអរគុណសម្រាប់ការទិញឥវ៉ាន់ជាមួយយើង!</p>
            </div>
            
            <div class="print-actions">
                <button onclick="window.print()" class="btn-print">
                    <i class="fas fa-print"></i> បោះពុម្ព
                </button>
            </div>
        </div>
    </div>

    <script>
        // Automatically trigger print dialog when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
        // assets/js/invoice.js
        document.addEventListener('DOMContentLoaded', function() {
        // បង្ហាញផ្ទាំងពុម្ពតាមលំអិញ បើសិនជាគ្មានប៊ូតុង
        const printBtn = document.querySelector('.btn-print');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
            window.print();
            });
        }

        // អាចបន្ថែម logic ផ្សេងៗ (ex: calculate tax, discounts) នៅទីនេះបានទៀត
        });

    </script>
</body>
</html>