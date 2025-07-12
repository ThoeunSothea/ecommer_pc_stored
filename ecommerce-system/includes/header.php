<?php
if (!defined('ALLOW_ACCESS')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int)$item['quantity'];
    }
}


if (!isset($pageTitle)) {
    $pageTitle = 'បច្ចេកវិទ្យាខ្មែរដែលមានចក្ខុវិស័យ';
}


$displayName = '';
if (isset($_SESSION['username'])) {
    $displayName = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="km" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= isset($pageDescription) ? htmlspecialchars($pageDescription) : 'ហាងអនឡាញលក់ផលិតផលគុណភាពខ្ពស់' ?>">
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="preload" href="/assets/css/auth.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" as="style">
    

    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <?php if (isset($isAdminPage) && $isAdminPage): ?>
        <link rel="stylesheet" href="../assets/css/admin.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
    <link rel="canonical" href="<?= htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>">
</head>
<body>
    
    <header class="site-header">
        <div class="container">
            <div class="header-top">

                <div class="logo">
                    <a href="#" aria-label="ទំព័រដើម">
                        <img src="https://sdmntprwestcentralus.oaiusercontent.com/files/00000000-5bd0-61fb-b285-224adc6bc797/raw?se=2025-07-11T06%3A23%3A42Z&sp=r&sv=2024-08-04&sr=b&scid=ee29148c-cf69-516b-9cd5-c6bd427e0a6f&skoid=add8ee7d-5fc7-451e-b06e-a82b2276cf62&sktid=a48cca56-e6da-484e-a814-9c849652bcb3&skt=2025-07-10T19%3A08%3A23Z&ske=2025-07-11T19%3A08%3A23Z&sks=b&skv=2024-08-04&sig=m6hZIsiUzpZOXQe0QmRDkNFpk%2BSdzDN86vsavtFgV1c%3D" alt="ឡូហ្គោហាងអនឡាញរបស់យើង" width="150" height="50" >
                        <span class="logo_text" style="color: #fff;">បច្ចេកវិទ្យាខ្មែរដែលមានចក្ខុវិស័យ</span>
                    </a>
                </div>
                

                <div class="header-search">
                    <form action="search.php" method="GET" role="search">
                        <label for="search-input" class="visually-hidden">ស្វែងរកផលិតផល</label>
                        <input type="text" 
                               id="search-input" 
                               name="q" 
                               placeholder="ស្វែងរកផលិតផល..." 
                               value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                               aria-label="ស្វែងរកផលិតផល">
                        <button type="submit" aria-label="ស្វែងរក">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                

                <div class="header-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>

                        <div class="account-dropdown" aria-haspopup="true">
                            <a href="account.php" class="account-link">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                <span><?= $displayName ?></span>
                            </a>
                            <div class="dropdown-content" role="menu">
                                <a href="account.php" role="menuitem">គណនីរបស់ខ្ញុំ</a>
                                <a href="order_complete.php" role="menuitem">ការកម្មង់របស់ខ្ញុំ</a>
                                <a href="../auth/logout.php" role="menuitem">ចាកចេញ</a>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <a href="http://localhost/Mid-Exam/ecommerce-system/admin/psges/dashboard.php" role="menuitem">ផ្ទាំងគ្រប់គ្រង</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>

                        <a href="../auth/logout.php" class="auth-link">
                            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                            <span>ចូលប្រើប្រាស់</span>
                        </a>
                    <?php endif; ?>
                    

                    <a href="cart.php" class="cart-link" aria-label="Cart (<?= $cartCount ?> items)">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="cart-count" aria-live="polite"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>


                </div>
            </div>
            

            <nav class="main-nav" aria-label="ម៉ឺនុយសំខាន់">
                <button class="mobile-menu-toggle" aria-expanded="false" aria-controls="primary-menu">
                    <span class="hamburger"></span>
                    <span class="sr-only">ម៉ឺនុយ</span>
                </button>
                
                <ul id="primary-menu">
                    <li><a href="main.php" <?= basename($_SERVER['PHP_SELF']) === 'main.php' ? 'class="active"' : '' ?>>ផ្ទាំងដើម</a></li>
                    <li><a href="products.php" <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'class="active"' : '' ?>>ផលិតផលទាំងអស់</a></li>
                    <li><a href="categories.php" <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'class="active"' : '' ?>>ប្រភេទផលិតផល</a></li>
                    <li><a href="about.php" <?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'class="active"' : '' ?>>អំពីយើង</a></li>
                    <li><a href="contact.php" <?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'class="active"' : '' ?>>ទំនាក់ទំនង</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main-content">