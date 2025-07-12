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
$categoryResult = $conn->query("SELECT category_id AS id, name FROM categories WHERE status = 'active'");

$categories = [];
if ($categoryResult && $categoryResult instanceof mysqli_result) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
    $categoryResult->free();
}



$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $discount = (float)$_POST['discount'];
    $featured = isset($_POST['featured']) ? 1 : 0;

    // Validate
    if (empty($name)) {
        $errors['name'] = 'ឈ្មោះផលិតផលគឺចាំបាច់';
    }
    if ($price <= 0) {
        $errors['price'] = 'តម្លៃត្រូវតែធំជាង 0';
    }
    if ($quantity < 0) {
        $errors['quantity'] = 'ចំនួនស្តុកមិនអាចតិចជាង 0 បានទេ';
    }
    if ($discount < 0 || $discount > 100) {
        $errors['discount'] = 'អត្រាបញ្ចុះតម្លៃត្រូវតែរវាង 0 និង 100';
    }

    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $errors['image'] = 'ប្រភេទឯកសារមិនត្រឹមត្រូវ។ សូមជ្រើសរើស JPEG, PNG ឬ GIF។';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = 'product_' . time() . '.' . $ext;

            $uploadDir = __DIR__ . '/../../assets/images/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $imageName;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $errors['image'] = 'មានបញ្ហាក្នុងការផ្ទុករូបភាព';
            }
        }
    } else {
        $errors['image'] = 'រូបភាពផលិតផលគឺចាំបាច់';
    }

    // ✅ Insert to DB
    if (empty($errors)) {
    // 0) Whitelist status from the form
    $allowedStatuses = ['active','inactive','out_of_stock'];
    $status = $_POST['status'] ?? '';
    if (! in_array($status, $allowedStatuses, true)) {
        $status = 'active';  // or whatever default you prefer
    }

    // 1) Get connection
    $conn = $db->getConnection();

    // 2) Prepare
    $query = "
      INSERT INTO products
        (name, description, price, quantity, image, category_id, discount, featured, status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param('ssdisidis',$name,$description,$price, $quantity, $imageName,$categoryId,$discount,$featured,$status);

    // 4) Execute + redirect / error
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'ផលិតផលត្រូវបានបន្ថែមដោយជោគជ័យ!';
        $stmt->close();
        $conn->close();
        header('Location: manage_products.php');
        exit;
    } else {
        echo "បញ្ចូលបរាជ័យ៖ " . $stmt->error;
    }

    // 5) Cleanup
    $stmt->close();
    $conn->close();
}

}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បន្ថែមផលិតផល</title>
    <link rel="stylesheet" href="../../assets/css/product.css">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>


<div class="main-content">
        <header class="admin-header">
            <h1><i class="fas fa-plus-circle"></i> បន្ថែមផលិតផល</h1>
            <div class="user-info">
                <span><?= $_SESSION['username'] ?></span>
                <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
            </div>
        </header>
        <div class="form-container">
            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">ឈ្មោះផលិតផល *</label>
                    <input type="text" id="name" name="name" value="<?= $_POST['name'] ?? '' ?>" required>
                    <?php if (!empty($errors['name'])): ?>
                        <span class="error"><?= $errors['name'] ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="description">ព័ត៌មានលម្អិត</label>
                    <textarea id="description" name="description" rows="4"><?= $_POST['description'] ?? '' ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">តម្លៃ *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?= $_POST['price'] ?? '' ?>" required>
                        <?php if (!empty($errors['price'])): ?>
                            <span class="error"><?= $errors['price'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="quantity">ចំនួនស្តុក *</label>
                        <input type="number" id="quantity" name="quantity" min="0" value="<?= $_POST['quantity'] ?? 0 ?>" required>
                        <?php if (!empty($errors['quantity'])): ?>
                            <span class="error"><?= $errors['quantity'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">ប្រភេទ *</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- ជ្រើសរើសប្រភេទ --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['id'] ?? '') ?>"
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="discount">អត្រាបញ្ចុះតម្លៃ (%)</label>
                        <input type="number" id="discount" name="discount" min="0" max="100" value="<?= $_POST['discount'] ?? 0 ?>">
                        <?php if (!empty($errors['discount'])): ?>
                            <span class="error"><?= $errors['discount'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                
                        
                <div class="form-group">
                    <label for="status">ស្ថានភាព</label>
                    <select name="status" id="status">
                        <option value="">-- ជ្រើសរើសស្ថានភាព --</option>
                        <option value="active"   <?= (isset($_POST['status']) && $_POST['status']=='active')   ? 'selected' : '' ?>>សកម្ម | active</option>
                        <option value="inactive" <?= (isset($_POST['status']) && $_POST['status']=='inactive') ? 'selected' : '' ?>>អសកម្ម | inactive</option>
                        <option value="out_of_stock" <?= (isset($_POST['status']) && $_POST['status']=='out_of_stock') ? 'selected' : '' ?>>អស់ពីស្តុក | out_of_stock</option>
                    </select>

                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured" <?= isset($_POST['featured']) ? 'checked' : '' ?>> 
                        ផលិតផលពេញនិយម
                    </label>
                </div>

                <div class="form-group">
                    <label for="image">រូបភាពផលិតផល *</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                    <?php if (!empty($errors['image'])): ?>
                        <span class="error"><?= $errors['image'] ?></span>
                    <?php endif; ?>
                    <div class="image-preview" id="imagePreview">
                        <img src="" alt="Image Preview" class="image-preview__image">
                        <span class="image-preview__default-text">រូបភាពមិនទាន់បានជ្រើសរើស</span>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> រក្សាទុក
                    </button>
                    <a href="manage_products.php" class="btn-cancel">
                        <i class="fas fa-times"></i> បោះបង់
                    </a>
                </div>
            </form>
        </div>

</div>

<script src="../../assets/JS/admin.js"></script>
<script src="../../assets/JS/product.js"></script>
<script>
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = imagePreview.querySelector('.image-preview__image');
    const previewDefaultText = imagePreview.querySelector('.image-preview__default-text');

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            previewDefaultText.style.display = 'none';
            previewImage.style.display = 'block';
            reader.addEventListener('load', function() {
                previewImage.setAttribute('src', this.result);
            });
            reader.readAsDataURL(file);
        } else {
            previewDefaultText.style.display = null;
            previewImage.style.display = null;
            previewImage.setAttribute('src', '');
        }
    });
</script>
</body>
</html>
<?php ob_end_flush(); ?>
