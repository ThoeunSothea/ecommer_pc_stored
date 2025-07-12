<?php
ob_start();
session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$conn = $db->getConnection();

// Check product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$productId) {
    die("Invalid product ID!");
}

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found!");
}

// Fetch categories
$categoryResult = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $categoryResult->fetch_all(MYSQLI_ASSOC);

// Define status options

$statusOptions = [
  1 => 'សកម្ម | active',
  2 => 'អសកម្ម | inactive',
  3 => 'អស់ពីស្តុក | out_of_stock'
];


$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $discount = (float)$_POST['discount'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 'active';

    // Category validation
    $categoryId = null;
    if (!empty($_POST['category_id'])) {
        $categoryId = (int)$_POST['category_id'];
        $checkCat = $conn->prepare("SELECT category_id FROM categories WHERE category_id = ?");
        $checkCat->bind_param('i', $categoryId);
        $checkCat->execute();
        $checkCat->store_result();
        if ($checkCat->num_rows === 0) {
            $errors['category_id'] = 'Invalid category selected';
            $categoryId = null;
        }
        $checkCat->close();
    }

    // Validation
    if (empty($name)) $errors['name'] = 'Product name is required';
    if ($price <= 0) $errors['price'] = 'Invalid price';
    if ($quantity < 0) $errors['quantity'] = 'Quantity must be >= 0';
    if ($discount < 0 || $discount > 100) $errors['discount'] = 'Discount must be between 0 and 100';

    // Image processing
    $imageName = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $tmpPath = $_FILES['image']['tmp_name'];
        $fileType = mime_content_type($tmpPath);

        if (in_array($fileType, $allowedTypes)) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $newFileName = 'product_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadPath = __DIR__ . '/../../assets/images/products/' . $newFileName;

            if (move_uploaded_file($tmpPath, $uploadPath)) {
                $oldImagePath = __DIR__ . '/../../assets/images/products/' . $product['image'];
                if (!empty($product['image']) && file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                $imageName = $newFileName;
            } else {
                $errors['image'] = 'Failed to upload image!';
            }
        } else {
            $errors['image'] = 'Invalid image type!';
        }
    }

    if (empty($errors)) {
        if ($categoryId === null) {
            $query = "UPDATE products SET 
                        name = ?, description = ?, price = ?, quantity = ?, 
                        image = ?, category_id = NULL, discount = ?, 
                        featured = ?, status = ? 
                      WHERE product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssdisiiii',
                $name, $description, $price, $quantity,
                $imageName, $discount, $featured, $status, $productId);
        } else {
            $query = "UPDATE products SET 
                        name = ?, description = ?, price = ?, quantity = ?, 
                        image = ?, category_id = ?, discount = ?, 
                        featured = ?, status = ? 
                      WHERE product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ssdisidiii',
                $name, $description, $price, $quantity,
                $imageName, $categoryId, $discount, $featured, $status, $productId);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Product updated successfully!';
            header('Location: manage_products.php');
            exit;
        } else {
            $errors['db'] = 'Update failed: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>កែប្រែផលិតផល</title>
    <link rel="stylesheet" href="../../assets/css/product.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        form, label, option, select, textarea, input {
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }
        .error { color: red; font-size: 14px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="main-content">
    <h1>កែប្រែផលិតផល</h1>
    <div class="form-container">
        <form action="edit_product.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data" onsubmit="confirmEdit(event)">
            <div class="form-group">
                <label for="name">ឈ្មោះផលិតផល *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                <?= isset($errors['name']) ? '<span class="error">' . $errors['name'] . '</span>' : '' ?>
            </div>

            <div class="form-group">
                <label for="description">ព័ត៌មានលម្អិត</label>
                <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">តម្លៃ *</label>
                    <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
                    <?= isset($errors['price']) ? '<span class="error">' . $errors['price'] . '</span>' : '' ?>
                </div>
                <div class="form-group">
                    <label for="quantity">ចំនួនស្តុក *</label>
                    <input type="number" name="quantity" value="<?= $product['quantity'] ?>" required>
                    <?= isset($errors['quantity']) ? '<span class="error">' . $errors['quantity'] . '</span>' : '' ?>
                </div>
            </div>

            <div class="form-group">
                <label for="category_id">ប្រភេទ *</label>
                <select name="category_id">
                    <option value="">-- ជ្រើសរើសប្រភេទ --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['category_id'] ?>" <?= ($product['category_id'] == $category['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= isset($errors['category_id']) ? '<span class="error">' . $errors['category_id'] . '</span>' : '' ?>
            </div>

            <div class="form-group">
                <label for="discount">អត្រាបញ្ចុះតម្លៃ (%)</label>
                <input type="number" name="discount" min="0" max="100" value="<?= $product['discount'] ?>">
                <?= isset($errors['discount']) ? '<span class="error">' . $errors['discount'] . '</span>' : '' ?>
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="featured" <?= $product['featured'] ? 'checked' : '' ?>> ផលិតផលពេញនិយម</label>
            </div>

            <div class="form-group">
                <label for="status">ស្ថានភាព *</label>
                <select name="status" required>
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($product['status'] == $value) ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>រូបភាពថ្មី (បើមាន)</label>
                <input type="file" name="image" accept="image/*" onchange="previewSelectedImage(this)">
                <img id="productPreview"
                     src="../../assets/images/products/<?= htmlspecialchars($product['image'] ?? 'default.png') ?>"
                     width="150" alt="រូបភាពផលិតផល">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-Update"><i class="fas fa-save"></i> រក្សាទុក</button>
                <a href="manage_products.php" class="btn-cancel"><i class="fas fa-times"></i> បោះបង់</a>
            </div>
        </form>
        <?php if (!empty($errors['db'])): ?>
            <p class="error"><?= $errors['db'] ?></p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Function to preview selected image
function previewSelectedImage(input) {
    const preview = document.getElementById('productPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Main confirmation function (using SweetAlert)
async function confirmEdit(event) {
    event.preventDefault();
    
    const form = event.target;
    const productName = form.querySelector('input[name="name"]').value;
    const price = form.querySelector('input[name="price"]').value;
    const statusSelect = form.querySelector('select[name="status"]');
    const status = statusSelect.options[statusSelect.selectedIndex].text;
    
    const { isConfirmed } = await Swal.fire({
        title: 'អ្នកពិតជាចង់កែប្រែផលិតផលមែនទេ?',
        html: `<div style="text-align: left; margin: 10px 0;">
            <p><strong>ឈ្មោះ:</strong> ${productName}</p>
            <p><strong>តម្លៃ:</strong> ${price}៛</p>
            <p><strong>ស្ថានភាព:</strong> ${status}</p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save"></i> រក្សាទុក',
        cancelButtonText: '<i class="fas fa-times"></i> បោះបង់',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        reverseButtons: true
    });
    
    if (isConfirmed) {
        form.submit();
    }
}


</script>
</body>
</html>
<?php ob_end_flush(); ?>
