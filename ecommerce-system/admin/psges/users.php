<?php
session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$pageTitle = 'បញ្ជីរអ្នកប្រើប្រាស់';

// Delete user handler
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $toDelete = (int) $_GET['id'];
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $toDelete);
        $stmt->execute();
        $conn->commit();
        header('Location: users.php?deleted=1');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: users.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Role update handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $userId = (int)$_POST['user_id'];
    $newRole = $_POST['role'];
    $validRoles = ['admin', 'customer', 'staff'];
    if (!in_array($newRole, $validRoles)) {
        die("Role មិនត្រឹមត្រូវ!");
    }
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->bind_param('si', $newRole, $userId);
    $stmt->execute();
    header('Location: users.php?updated=1');
    exit;
}

// Search functionality
$searchQuery = trim($_GET['search'] ?? '');

$sql = "SELECT user_id, username, full_name, phone, address, email, role, created_at FROM users WHERE 1";
$params = [];
$types = '';

if ($searchQuery !== '') {
    $sql .= " AND (username LIKE ? )";
    $term = "%{$searchQuery}%";
    $params = array_fill(0, 1, $term);
    $types = str_repeat('s', 1);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
<div class="main-content p-4">

    <header class="admin-header">
        <h1><i class="fas fa-users"></i> <?= htmlspecialchars($pageTitle) ?></h1>
        <div class="user-info">
            <span><?= htmlspecialchars($_SESSION['username'] ?? 'Unknown User') ?></span>
            <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
        </div>
    </header>

    <form method="get" action="users.php" class="form-group">
        <input type="text" name="search" placeholder="ស្វែងរក........" value="<?= htmlspecialchars($searchQuery) ?>" />
        <button type="submit">ស្វែងរក</button>
        <?php if ($searchQuery !== ''): ?>
            <a href="users.php" class="btn-clear">មើលទាំងអស់</a>
        <?php endif; ?>
    </form>
        
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created At</th>
                <th>សកម្មភាព</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                    <td class="address"><?= nl2br(htmlspecialchars($row['address'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td>
                        <form method="post" action="users.php" class="form-inline-role">
                            <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                            <select name="role" class="role-select">
                                <?php
                                    $roles = ['admin', 'customer'];
                                    foreach ($roles as $roleOption) {
                                        $selected = ($row['role'] === $roleOption) ? 'selected' : '';
                                        echo "<option value=\"{$roleOption}\" {$selected}>" . ucfirst($roleOption) . "</option>";
                                    }
                                ?>
                            </select>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                    <td>
                        <button class="btn-delete" data-id="<?= $row['user_id'] ?>">លុប</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" style="text-align:center;">គ្មានទិន្នន័យ</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Delete confirmation
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.dataset.id;
      Swal.fire({
        title: 'ព្យាយាមលុប?',
        text: 'សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'លុប',
        cancelButtonText: 'បោះបង់',
        customClass: {
            popup: 'khmer-os-font',
            confirmButton: 'khmer-os-font',
            cancelButton: 'khmer-os-font'
        }
        }).then(result => {
        if (result.isConfirmed) {
            window.location.href = `users.php?id=${id}`;
        }
        });

    });
  });

  // Delete success or error alerts
  const params = new URLSearchParams(window.location.search);
  if (params.get('deleted') === '1') {
    Swal.fire({
      title: 'លុបបានជោគជ័យ',
      icon: 'success',
      confirmButtonText: 'បិទ',
      customClass: { popup: 'khmer-os-font' }
    });
  } else if (params.get('error')) {
    Swal.fire({
        title: 'លុបបានជោគជ័យ',
        icon: 'success',
        confirmButtonText: 'បិទ',
        customClass: {
            popup: 'khmer-os-font',
            confirmButton: 'khmer-os-font',
            cancelButton: 'khmer-os-font'
        }
        });
}

  // Role change confirmation
  document.querySelectorAll('.role-select').forEach(select => {
    select.addEventListener('change', e => {
      e.preventDefault();
      const form = select.closest('form');
      const newRole = select.value;
        Swal.fire({
        title: 'តើអ្នកចង់ផ្លាស់ប្ដូរទៅជា "' + newRole + '" មែនទេ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'យល់ព្រម',
        cancelButtonText: 'បោះបង់',
        customClass: {
            popup: 'khmer-os-font',
            confirmButton: 'khmer-os-font',
            cancelButton: 'khmer-os-font'
        }
        }).then(result => {
        if (result.isConfirmed) {
            form.submit();
        } else {
            location.reload();
        }
        });
    });
  });
});
</script>

</body>
</html>
