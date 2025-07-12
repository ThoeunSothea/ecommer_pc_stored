<?php
require_once __DIR__ . '/../../includes/config.php';  
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // 
    exit;
}


$current_page = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="../../assets/css/admin.css">
<style>
        body {
        font-family: 'Khmer OS', 'Arial', sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
        color: #333;
    }
    .main-content {
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s;
    }

    /* Header Styles */
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        background-color: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .admin-header h1 {
        font-size: 24px;
        margin: 0;
        color: #2c3e50;
    }

    .admin-header h1 i {
        margin-right: 10px;
        color: #3498db;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-info span {
        font-weight: 600;
        color: #2c3e50;
    }

    .btn-logout {
        background-color: #e74c3c;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 14px;
        transition: background-color 0.3s;
    }

    .btn-logout:hover {
        background-color: #c0392b;
    }


</style>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="admin-profile">
            <img src="https://scontent.fpnh9-1.fna.fbcdn.net/v/t39.30808-1/505808234_1248136416871201_8245917890805287779_n.jpg?stp=cp6_dst-jpg_s160x160_tt6&_nc_cat=109&ccb=1-7&_nc_sid=e99d92&_nc_eui2=AeFZWWP4jlRyyclalhRevh9jjkKFlvavoamOQoWW9q-hqSL_moFez74C8jXKjfvIQIY6XPYu2UxieSIx93OR42ep&_nc_ohc=iD_77o09fZQQ7kNvwF7Lz-W&_nc_oc=AdkdZkVMf9B3aN40tkf1EPG2sv4D_ZT_NqB_px61FioTVMb1vwrhdAxHgXMiJ3T4MSg&_nc_zt=24&_nc_ht=scontent.fpnh9-1.fna&_nc_gid=wdOKgNfnK-Tbs60LESIF0g&oh=00_AfTHGJfSlXDkbQzgPtd06l3vFRU3i8MOkp75AW52EpdUWA&oe=6871C392" alt="Admin Avatar">
            <h3><?= htmlspecialchars($_SESSION['admin_name'] ?? 'អ្នកគ្រប់គ្រង') ?></h3>
            <small>Admin</small>
        </div>
    </div>
    
    <ul class="sidebar-menu">
        
        <li>
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> 
                <span>ផ្ទាំងដើម</span>
                <!-- <span class="notification-badge"></span> -->
            </a>
        </li>
        
        <li class="menu-category">គ្រប់គ្រង</li>
        
        <li class="<?= in_array($current_page, [ 'main.php']) ? 'active-parent' : '' ?>">
            <a href="http://localhost/Mid-Exam/ecommerce-system/pages/main.php" class="<?= in_array($current_page, ['main.php']) ? 'active' : '' ?>">
                <i class="fa-solid fa-house-user"></i>
                <span>គេហទំព័រដើម</span>
            </a>
        </li>

        <li class="<?= in_array($current_page, ['manage_products.php', 'add_product.php']) ? 'active-parent' : '' ?>">
            <a href="manage_products.php" class="<?= in_array($current_page, ['manage_products.php', 'add_product.php']) ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i> 
                <span>ផលិតផល</span>
            </a>
        </li>
        <li>
            <a href="categories.php" class="<?= $current_page === 'categories.php' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> 
                <span>ប្រភេទផលិតផល</span>
            </a>
        </li>
        <li class="<?= in_array($current_page, ['orders.php', 'order_details.php']) ? 'active-parent' : '' ?>">
            <a href="orders.php" class="<?= in_array($current_page, ['orders.php', 'order_details.php']) ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i> 
                <span>ការកម្មង់</span>
                <!-- <span class="notification-badge">12</span> -->
            </a>
        </li>
        
        <!-- <li>
            <a href="customers.php" class="<?= $current_page === 'customers.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> 
                <span>អតិថិជន</span>
            </a>
        </li> -->

        <li>
            <a href="users.php" class="<?= $current_page === 'users.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> 
                <span>អ្នកប្រើប្រាស់</span>
            </a>
        </li>
        
        <li class="menu-category">របាយការណ៍</li>
        
        <li>
            <a href="reports.php" class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> 
                <span>របាយការណ៍</span>
            </a>
        </li>
        
        <li class="menu-category">ការកំណត់</li>
        
        <li>
            <a href="settings.php" class="<?= $current_page === 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> 
                <span>ការកំណត់</span>
            </a>
        </li>
    </ul>
    
    <div class="sidebar-footer">
        <a href="../../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> 
            <span>ចាកចេញ</span>
        </a>
    </div>
</div>