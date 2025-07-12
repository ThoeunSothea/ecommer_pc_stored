<?php
ob_start();
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';


if (!defined('ALLOW_ACCESS')) {
    define('ALLOW_ACCESS', true);
}


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$stats = [];

$queries = [
    'total_products' => "SELECT COUNT(*) as count FROM products",
    'total_orders' => "SELECT COUNT(*) as count FROM orders",
    'total_customer' => "SELECT COUNT(*) as count FROM users",
    'total_revenue' => "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'",
    'pending_orders' => "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'",
    'low_stock' => "SELECT COUNT(*) as count FROM products WHERE quantity < 5"
];


foreach ($queries as $key => $query) {
    $row = $db->fetchOne($query);
    $stats[$key] = ($key === 'total_revenue') ? ($row['total'] ?? 0) : ($row['count'] ?? 0);
}


$recentOrders = $db->fetchAll(
    "SELECT o.*, u.username, u.email
     FROM orders o 
     LEFT JOIN users u ON o.user_id = u.user_id 
     ORDER BY o.created_at DESC 
     LIMIT 8"
);


$recentProducts = $db->fetchAll("SELECT * FROM products ORDER BY created_at DESC LIMIT 7");

?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ផ្ទាំងគ្រប់គ្រង</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
    

    <div class="main-content">
        <header class="admin-header">
            <h1><i class="fas fa-tachometer-alt"></i> ផ្ទាំងគ្រប់គ្រង</h1>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
            </div>
        </header>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #3498db;">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="stat-info">
                    <h3>ផលិតផលសរុប</h3>
                    <p><?= htmlspecialchars($stats['total_products']) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #2ecc71;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>ការកម្មង់សរុប</h3>
                    <p><?= htmlspecialchars($stats['total_orders']) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #e74c3c;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>អ្នកប្រើប្រាស់សរុប</h3>
                    <p><?= htmlspecialchars($stats['total_customer']) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background-color: #f39c12;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-info">
                    <h3>ចំណូលសរុប</h3>
                    <p>$<?= number_format($stats['total_revenue'], 2) ?></p>
                </div>
            </div>
        </div>

        <div class="dashboard-sections">
            <section class="recent-orders">
                <div class="section-header">
                    <h2><i class="fas fa-clipboard-list"></i> ការកម្មង់ថ្មីៗ</h2>
                    <a href="orders.php" class="btn-view-all">មើលទាំងអស់</a>
                </div>

                <div class="table-responsive" style="color: black;">
                    <table>
                        <thead>
                            <tr>
                                <th>លេខកូដ</th>
                                <th>អតិថិជន</th>
                                <th>កាលបរិច្ឆេទ</th>
                                <th>ស្ថានភាព</th>
                                <th>សរុប</th>
                                <th>សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_code']) ?></td>
                                        <td><?= htmlspecialchars($order['username'] ?? 'Guest') ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($order['created_at']))) ?></td>
                                        <td>
                                            <span class="status-badge <?= htmlspecialchars($order['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                                            
                                        <td>
                                            <a href="order_details.php?id=<?= isset($order['order_id']) ? (int)$order['order_id'] : 0 ?>" class="btn-view" title="មើលលម្អិត">
                                                <i class="fas fa-eye"></i>មើលលម្អិត
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">មិនមានការកម្មង់ថ្មីៗទេ។</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="recent-products">
                <div class="section-header">
                    <h2><i class="fas fa-boxes"></i> ផលិតផលថ្មីៗ</h2>
                    <a href="manage_products.php" class="btn-view-all">មើលទាំងអស់</a>
                </div>

                <div class="table-responsive" style="color: black;">
                    <table>
                        <thead>
                            <tr>
                                <th>រូបភាព</th>
                                <th>ឈ្មោះ</th>
                                <th>តម្លៃ</th>
                                <th>ស្តុក</th>
                                <th>សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentProducts)): ?>
                                <?php foreach ($recentProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" width="50" alt="Product Image">
                                        </td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td>$<?= number_format($product['price'], 2) ?></td>
                                        <td><?= htmlspecialchars($product['quantity']) ?></td>
                                        <td>
                                            <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> កែប្រែ</a>  
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">មិនមានផលិតផលថ្មីៗទេ។</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>

<?php ob_end_flush(); ?>
