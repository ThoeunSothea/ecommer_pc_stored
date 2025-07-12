<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

$pageTitle = 'ប្រភេទផលិតផល';
$currentPage = basename($_SERVER['PHP_SELF']);
$message = '';
$editCategory = null;
$editMode = false;

try {
    $db = new Database();

    // Handle edit mode
    if (isset($_GET['edit'])) {
        $editId = intval($_GET['edit']);
        $editCategory = $db->fetchOne("SELECT * FROM categories WHERE category_id = ?", [$editId]);
        $editMode = true;
    }

    // Handle POST (add/edit category)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $imagePath = null;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (in_array($_FILES['image']['type'], $validTypes)) {
                $uploadDir = __DIR__ . '/../../uploads/categories/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
                $targetFile = $uploadDir . $imageName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = 'uploads/categories/' . $imageName;
                }
            } else {
                $message = "❌ ប្រភេទរូបភាពមិនត្រឹមត្រូវ!";
            }
        }

        if (isset($_POST['edit_id']) && intval($_POST['edit_id']) > 0) {
            // Edit existing category
            $editId = intval($_POST['edit_id']);
            if ($imagePath) {
                $db->query("UPDATE categories SET name=?, description=?, image_path=? WHERE category_id=?", [$name, $description, $imagePath, $editId]);
            } else {
                $db->query("UPDATE categories SET name=?, description=? WHERE category_id=?", [$name, $description, $editId]);
            }
            header("Location: $currentPage?msg=edited");
            exit;
        } else {
            // Add new category
            if (!empty($name)) {
                $db->query("INSERT INTO categories (name, description, image_path, created_at) VALUES (?, ?, ?, NOW())", [$name, $description, $imagePath]);
                header("Location: $currentPage?msg=added");
                exit;
            }
        }
    }

    // Handle delete
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $count = $db->fetchValue("SELECT COUNT(*) FROM products WHERE category_id = ?", [$id]);

        if ($count > 0) {
            $message = "❌ មិនអាចលុបបានទេ។ មានផលិតផលនៅក្នុងប្រភេទនេះ។";
        } else {
            // Delete associated image file
            $imagePath = $db->fetchValue("SELECT image_path FROM categories WHERE category_id = ?", [$id]);
            if ($imagePath && file_exists(__DIR__ . '/../../' . $imagePath)) {
                unlink(__DIR__ . '/../../' . $imagePath);
            }

            $db->query("DELETE FROM categories WHERE category_id = ?", [$id]);
            header("Location: $currentPage?msg=deleted");
            exit;
        }
    }

    // Flash messages
    if (isset($_GET['msg'])) {
        if ($_GET['msg'] === 'deleted') $message = "🗑️ លុបបានជោគជ័យ!";
        if ($_GET['msg'] === 'edited') $message = "✏️ កែសម្រួលបានជោគជ័យ!";
        if ($_GET['msg'] === 'added') $message = "✅ បន្ថែមប្រភេទបានជោគជ័យ!";
    }

    // Load categories
    $categories = $db->fetchAll("SELECT * FROM categories ORDER BY category_id DESC");

} catch (Exception $e) {
    die("បញ្ហា៖ " . $e->getMessage());
}
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<link rel="stylesheet" href="/ecommerce-system/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


<div class="main-content">
    <header class="admin-header">
        <h1><i class="fa-thin fa-list"></i> បញ្ជីប្រភេទផលិតផល </h1>
        <div class="user-info">
            <span><?= $_SESSION['username'] ?></span>
            <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
        </div>
    </header>

    <form action="" method="POST" enctype="multipart/form-data" class="form-add" id="category-form">
        <input type="hidden" name="edit_id" id="edit-id-field" value="<?= $editMode ? $editCategory['category_id'] : '' ?>">

        <input type="text" name="name" id="name-field" placeholder="ឈ្មោះប្រភេទ" required value="<?= $editMode ? htmlspecialchars($editCategory['name']) : '' ?>">

        <textarea class="description" name="description" id="description-field" placeholder="ពិពណ៌នាអំពីប្រភេទ"><?= $editMode ? htmlspecialchars($editCategory['description']) : '' ?></textarea>

        <div class="file-upload-wrapper">
            <label for="image-upload" class="custom-file-label">
                <i class="fas fa-upload"></i> ជ្រើសរូបភាព
            </label>

            <input type="file" name="image" id="image-upload" accept="image/*" class="file-input">

            <span id="file-name" class="file-name">
                <?= $editMode && $editCategory['image_path'] ? basename($editCategory['image_path']) : 'មិនទាន់ជ្រើសរូបភាព' ?>
            </span>

            <img id="image-preview"
                src="<?= $editMode && $editCategory['image_path'] ? '/ecommerce-system/' . $editCategory['image_path'] : '#' ?>"
                class="preview-image"
                style="<?= $editMode && $editCategory['image_path'] ? 'display:block;' : 'display:none;' ?>">
        </div>

        <button type="submit" id="submit-btn">
            <i class="fas fa-<?= $editMode ? 'edit' : 'plus-circle' ?>"></i>
            <?= $editMode ? 'កែសម្រួល' : 'បន្ថែម' ?>
        </button>

        <button type="button" id="btn-cancel-edit" style="<?= $editMode ? 'display:inline-block;' : 'display:none;' ?>; background-color:#6c757d;">
            បោះបង់
        </button>
    </form>

    <?php if (!empty($message)): ?>
        <div style="background-color:#fff3cd;color:#856404;padding:10px;border:1px solid #ffeeba;border-radius:4px;margin-bottom:20px;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>លេខ</th>
                <th>រូបភាព</th>
                <th>ឈ្មោះ</th>
                <th>ពិពណ៌នា</th>
                <th>កាលបរិច្ឆេទបង្កើត</th>
                <th>សកម្មភាព</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($categories): ?>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= $category['category_id'] ?></td>
                        <td>
                            <?php if (!empty($category['image_path'])): ?>
                                <img src="/MID-EXAM/ecommerce-system/<?= $category['image_path'] ?>" alt="រូបភាព" style="width: 60px;">
                            <?php else: ?> គ្មាន <?php endif; ?>
                        </td>
                        <td class="td-name"><?= htmlspecialchars($category['name']) ?></td>
                        <td class="td-description"><?= nl2br(htmlspecialchars($category['description'])) ?></td>
                        <td class="td-created-at"><?= $category['created_at'] ?></td>
                        <td>
                            <a href="#" class="btn-edit" data-id="<?= $category['category_id'] ?>">✏ កែ</a>
                            <a href="?delete=<?= $category['category_id'] ?>" onclick="return confirm('តើអ្នកចង់លុបមែនទេ?')" class="btn-delete">🗑 លុប</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">គ្មានទិន្នន័យ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('image-upload').addEventListener('change', function () {
        const fileNameDisplay = document.getElementById('file-name');
        const preview = document.getElementById('image-preview');
        const file = this.files[0];

        if (file) {
            fileNameDisplay.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            fileNameDisplay.textContent = 'មិនទាន់ជ្រើសរូបភាព';
            preview.style.display = 'none';
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('category-form');
        const nameField = document.getElementById('name-field');
        const descField = document.getElementById('description-field');
        const editIdField = document.getElementById('edit-id-field');
        const preview = document.getElementById('image-preview');
        const fileNameDisplay = document.getElementById('file-name');
        const submitBtn = document.getElementById('submit-btn');
        const cancelBtn = document.getElementById('btn-cancel-edit');

        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                const row = this.closest('tr');
                const id = this.dataset.id;
                const name = row.querySelector('.td-name').textContent.trim();
                const description = row.querySelector('.td-description').textContent.trim();
                const image = row.querySelector('img')?.getAttribute('src');

                nameField.value = name;
                descField.value = description;
                editIdField.value = id;

                if (image) {
                    preview.src = image;
                    preview.style.display = 'block';
                    fileNameDisplay.textContent = image.split('/').pop();
                }

                submitBtn.innerHTML = '<i class="fas fa-edit"></i> កែសម្រួល';
                cancelBtn.style.display = 'inline-block';

                form.scrollIntoView({ behavior: 'smooth' });
            });
        });

        cancelBtn.addEventListener('click', function () {
            nameField.value = '';
            descField.value = '';
            editIdField.value = '';
            document.getElementById('image-upload').value = '';
            preview.style.display = 'none';
            fileNameDisplay.textContent = 'មិនទាន់ជ្រើសរូបភាព';
            submitBtn.innerHTML = '<i class="fas fa-plus-circle"></i> បន្ថែម';
            cancelBtn.style.display = 'none';
        });
    });
</script>