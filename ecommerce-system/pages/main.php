<?php
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';   
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$db = new Database();


$sql = "SELECT *
        FROM products
        WHERE featured = 1
        ORDER BY created_at DESC
        LIMIT 8";
$products = $db->fetchAll($sql);


$assetsDir = __DIR__ . '/../assets/images/products/';
?>
<!DOCTYPE html>
<html lang="km">
<head>
  <meta charset="UTF-8">
  <title>ហាងអនឡាញ - ផលិតផលពេញនិយម</title>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
</head>
<body>
      <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img src="../uploads/categories/686bf5d171fe0_Laptops.jpg" alt=""></div>
                <div class="swiper-slide"><img src="../uploads/categories/686bf70ade98e_Computers.jpg" alt=""></div>
                <div class="swiper-slide"><img src="../uploads/categories/686c00bdc95d2_Books.jpg" alt=""></div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
  
  <div class="hero-section">
    <div class="hero-content">
      <h1>សូមស្វាគមន៍មកកាន់ហាងអនឡាញរបស់យើង</h1>
      <p>រកឃើញផលិតផលដ៏ល្អនិងថ្លៃសមរម្យសម្រាប់អ្នក</p>
      <a href="products.php" class="btn btn-primary">ទិញផលិតផលឥឡូវនេះ</a>
    </div>
  </div>

  <div class="search-container container">
    <form action="search.php" method="GET" class="search-form">
      <input type="text" name="q" placeholder="ស្វែងរកផលិតផល..." required>
      <button type="submit" class="btn btn-search">
        <i class="fas fa-search"></i> ស្វែងរក
      </button>
    </form>
  </div>

  <br>
  
  <div class="container">
    <section class="featured-products">
      <h2 class="section-title">ផលិតផលពេញនិយម</h2>
      <div class="products-grid">

        <?php if (!empty($products)): ?>
          <?php foreach ($products as $product): ?>
            <?php

              $id            = (int)     $product['product_id'];
              $name          = htmlspecialchars($product['name']);
              $price         = (float)   $product['price'];
              $discount      = (int)     $product['discount'];
              $quantity      = (int)     $product['quantity'];
              $rawImage      = $product['image'];
              

              $candidates = [];
              if ($rawImage) {
                $candidates[] = $rawImage;
                $candidates[] = preg_replace('/\.\w+$/', '.png', $rawImage);
                $candidates[] = preg_replace('/\.\w+$/', '.jpg', $rawImage);
              }
              $candidates[] = 'no-image.png';
              $candidates[] = 'no-image.jpg';

              $filename = null;
              foreach ($candidates as $fn) {
                if (file_exists($assetsDir . $fn)) {
                  $filename = $fn;
                  break;
                }
              }

              $imageUrl = BASE_URL . '/assets/images/products/' . rawurlencode($filename);

              $discountedPrice = $discount > 0
                ? $price * (1 - $discount / 100)
                : $price;
            ?>

            <div class="product-card">
              <div class="product-badge">
                <?php if ($discount > 0): ?>
                  <span class="badge-discount">-<?= $discount ?>%</span>
                <?php endif; ?>
                <?php if ($quantity <= 0): ?>
                  <span class="badge-out">អស់ពីស្តុក</span>
                <?php endif; ?>
              </div>

              <div class="product-image">
                <img
                  src="<?= $imageUrl ?>"
                  alt="<?= $name ?>"
                  onerror="this.onerror=null;this.src='<?= BASE_URL ?>/assets/images/products/no-image.png'">
                <div class="product-actions">
                  <button class="btn-wishlist" data-id="<?= $id ?>">
                    <i class="far fa-heart"></i>
                  </button>
                  <button class="btn-quickview" data-id="<?= $id ?>">
                    <i class="far fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="product-info">
                <h3 class="product-title"><?= $name ?></h3>
                <div class="product-price">
                  <?php if ($discount > 0): ?>
                    <span class="original-price">$<?= number_format($price,2) ?></span>
                    <span class="discounted-price">$<?= number_format($discountedPrice,2) ?></span>
                  <?php else: ?>
                    <span class="current-price">$<?= number_format($price,2) ?></span>
                  <?php endif; ?>
                </div>
                <a href="../pages/products.php?product_id=<?= $id ?>" class="btn btn-add-to-cart">
                  <i class="fas fa-shopping-cart"></i> ទិញឥវ៉ាន់
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="no-products">មិនមានផលិតផលពេញនិយមសម្រាប់បង្ហាញទេ។</p>
        <?php endif; ?>

      </div>
    </section>
  </div>

  <?php require_once __DIR__ . '/../includes/footer.php'; ?>


  <script src="<?= BASE_URL ?>/assets/JS/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <div id="quickviewModal"></div>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".mySwiper", {
            slidesPerView: 1,
            spaceBetween: 0,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            }
        });
    </script>
</body>
</html>
