<?php
session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}


$query = " SELECT * FROM products ORDER BY product_id DESC ";
$stmt = $db->query($query);
$result = $stmt->get_result();

if (!$result) {
    die("Query Error: " . $db->getConnection()->error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $productId = intval($_POST['product_id']);
    $qty = intval($_POST['quantity']);

    if ($productId > 0 && $qty > 0) {
        $db = new Database(); // ប្រសិនបើមិនមាន $db ចាប់ដើមទេ
        $db->query("UPDATE products SET quantity = quantity + ? WHERE product_id = ?", [$qty, $productId]);
        $_SESSION['success_message'] = "✅ បន្ថែមស្តុកបានជោគជ័យ!";
        header("Location: " . basename($_SERVER['PHP_SELF']));
        exit;
    } else {
        $_SESSION['success_message'] = "❌ ទិន្នន័យមិនត្រឹមត្រូវ!";
    }
}

?>


<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
    <div class="alert alert-success">លុបទិន្នន័យជោគជ័យ!</div>
<?php endif; ?>


<link rel="stylesheet" href="../../assets/css/admin.css">
<link rel="stylesheet" href="../../assets/css/product.css">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>គ្រប់គ្រងផលិតផល</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>


<div class="admin-container">
    <main class="content">
        <header class="admin-header">
        <h1><i class="fas fa-box"></i> បញ្ជីផលិតផល</h1>
        <div class="user-info">
            <span><?= $_SESSION['username'] ?></span>
            <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
        </div>
        </header>
        

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-success">
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <a href="add_product.php" class="btn-add"><i class="fas fa-plus"></i> បន្ថែមផលិតផល</a>

        <table class="product-table">
            <thead>
                <tr>
                    <th>លេខកូដ</th>
                    <th>រូបភាព</th>
                    <th>ឈ្មោះ</th>
                    <th>តម្លៃ</th>
                    <th>ស្តុក</th>
                    <th>សកម្មភាព</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($product['product_id']) ?></td>
                    <td><img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" width="100" alt="Product Image"></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td>$<?= number_format($product['price'], 2) ?></td>
                    <td><?= htmlspecialchars($product['quantity']) ?></td>
                    <td>
                        <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> កែប្រែ</a>
                        <a href="delete_product.php?id=<?= $product['product_id'] ?>" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash-alt"></i> លុប</a>
                        <a class="btn btn-success btn-sm" onclick="openStockModal(<?= $product['product_id'] ?>)">
                            <i class="fas fa-plus"></i> បន្ថែមស្តុក
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

            </tbody>
                <!-- Modal HTML -->
                <div id="stockModal" style="display:none; background:#fff; border:1px solid #ccc; padding:20px;">
                    <form method="POST" action="">
                        <h1><i class="fas fa-plus"></i> បន្ថែមចំនួនស្តុក</h1>
                        <input type="hidden" name="product_id" id="modalProductId">
                        <label>បញ្ចូលចំនួនស្តុក:</label>
                        <input type="number" name="quantity" min="1" required>
                        <br><br>
                        <button type="submit" name="add_stock">✅ បន្ថែម</button>
                        <button type="button" onclick="closeModal()">បិទ</button>
                    </form>
                </div>

        </table>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const deleteLinks = document.querySelectorAll('.btn-delete');
        deleteLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');

                Swal.fire({
                    title: 'ព្យាយាមលុប?',
                    text: "សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'លុប!',
                    cancelButtonText: 'បោះបង់',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
        });
    });

    function openStockModal(productId) {
        document.getElementById('modalProductId').value = productId;
        document.getElementById('stockModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('stockModal').style.display = 'none';
    }
</script>


</body>
</html>
