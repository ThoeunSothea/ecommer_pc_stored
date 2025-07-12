<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Security check: only logged in admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db = new Database();
$conn = $db->getConnection();

// Handle status update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = 'សំណើរបស់អ្នកមិនត្រឹមត្រូវទេ។';
        header('Location: orders.php');
        exit;
    }

    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];

    if (!in_array($status, $allowedStatuses)) {
        $_SESSION['error_message'] = 'ស្ថានភាពមិនត្រឹមត្រូវទេ!';
        header('Location: orders.php');
        exit;
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param('si', $status, $orderId);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'បានធ្វើបច្ចុប្បន្នភាពស្ថានភាពដោយជោគជ័យ!';
    } else {
        $_SESSION['error_message'] = 'មានបញ្ហាក្នុងការធ្វើបច្ចុប្បន្នភាព';
    }

    header('Location: orders.php');
    exit;
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');

// Pagination variables
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Base query
$query = "SELECT o.order_id, o.order_code,  o.status, o.total_amount, u.username, u.email,o.created_at
          FROM orders o
          LEFT JOIN users u ON o.user_id  = u.user_id 
          WHERE 1=1 ";

$params = [];
$types = '';

// Filter by status
$allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
if ($statusFilter && in_array($statusFilter, $allowedStatuses)) {
    $query .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

// Search filter
if ($searchQuery !== '') {
    $query .= " AND (o.order_code LIKE ? OR u.username LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

// Count total rows
$countQuery = "SELECT COUNT(*) as total FROM ($query) as temp";
$countStmt = $conn->prepare($countQuery);

if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRows = $totalResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $perPage);

// Add pagination limit
$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['error_message'], $_SESSION['success_message']);
?>

<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<!DOCTYPE html>
<html lang="km">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="stylesheet" href="../../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
<title>គ្រប់គ្រងការកម្មង់</title>

</head>
<body>
<div class="main-content">

    <header class="admin-header">
            <h1><i class="fas fa-clipboard-list"></i> គ្រប់គ្រងការកម្មង់</h1>
            <div class="user-info">
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="../../auth/logout.php" class="btn-logout">ចាកចេញ</a>
            </div>
        </header>

    <?php if ($error_message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <form method="get" class="search-form" novalidate>
        <label for="status">ស្ថានភាព៖</label>
            <select id="status" name="status" onchange="this.form.submit()">
                <option value="">ទាំងអស់</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>កំពុងរង់ចាំ</option>
                <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>កំពុងដំណើរការ</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>បានបញ្ចប់</option>
                <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>បានលុបចោល</option>
            </select>

        <input type="text" id="search" name="search" placeholder="ស្វែងរកតាមលេខកូដ, ឈ្មោះអតិថិជន" value="<?= htmlspecialchars($searchQuery) ?>">

        <button type="submit">ស្វែងរក</button>
    </form>

    <table>
    <thead>
        <tr>
        <th>លេខកូដ</th>
        <th>អតិថិជន</th>
        <th>អ៊ីមែល</th>
        <th>កាលបរិច្ឆេទ</th>
        <th>ស្ថានភាព</th>
        <th>សរុប</th>
        <th>សកម្មភាព</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($orders->num_rows > 0): ?>
        <?php while ($order = $orders->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($order['order_code']) ?></td>
            <td><?= htmlspecialchars($order['username']) ?></td>
            <td><?= htmlspecialchars($order['email']) ?: '<em>N/A</em>' ?></td>
            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
            <td>
            <span class="badge <?= htmlspecialchars($order['status']) ?>">
                <?php
                $statusTexts = [
                    'pending' => 'កំពុងរង់ចាំ',
                    'processing' => 'កំពុងដំណើរការ',
                    'completed' => 'បានបញ្ចប់',
                    'cancelled' => 'បានលុបចោល',
                ];
                echo $statusTexts[$order['status']] ?? htmlspecialchars($order['status']);
                ?>
            </span>
            </td>
            <td>$<?= number_format($order['total_amount'], 2) ?></td>
            <td>
            <button class="btn-edit" data-id="<?= $order['order_id'] ?>" data-status="<?= $order['status'] ?>">
                ✏️ កែសម្រួល
            </button>
            </td>
        </tr>
        <?php endwhile; ?>
        <?php else: ?>
        <tr><td colspan="7" style="text-align:center;">មិនមានការកម្មង់ទេ</td></tr>
        <?php endif; ?>
    </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <ul class="pagination">
        <?php if ($page > 1): ?>
        <li><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; មុន</a></li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="<?= $i === $page ? 'active' : '' ?>"><a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <li><a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">បន្ទាប់ &raquo;</a></li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>

    <!-- Modal -->
    <div id="statusModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal" role="document">
        <div class="modal-header">
        <h5 id="modalTitle">ធ្វើបច្ចុប្បន្នភាពស្ថានភាព</h5>
        <button type="button" class="modal-close-btn" aria-label="Close modal" id="modalCloseBtn">&times;</button>
        </div>
        <form id="statusForm" method="POST" action="orders.php">
        <input type="hidden" name="order_id" id="modalOrderId" value="">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="modal-body">
            <label for="modalStatus">ស្ថានភាព៖</label>
            <select id="modalStatus" name="status" required>
            <option value="pending">កំពុងរង់ចាំ | pending</option>
            <option value="processing">កំពុងដំណើរការ | processing</option>
            <option value="completed">បានបញ្ចប់ | completed</option>
            <option value="cancelled">បានលុបចោល | cancelled</option>
            </select>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="modalCancelBtn">បោះបង់</button>
            <button type="submit" name="update_status" class="btn-primary">រក្សាទុក</button>
        </div>
        </form>
    </div>
    </div>
    
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('statusModal');
  const modalCloseBtn = document.getElementById('modalCloseBtn');
  const modalCancelBtn = document.getElementById('modalCancelBtn');
  const modalOrderIdInput = document.getElementById('modalOrderId');
  const modalStatusSelect = document.getElementById('modalStatus');
  const editButtons = document.querySelectorAll('.btn-edit');

  function openStatusModal(orderId, currentStatus) {
    modalOrderIdInput.value = orderId;
    modalStatusSelect.value = currentStatus;
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeStatusModal() {
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
  }

  editButtons.forEach(button => {
    button.addEventListener('click', () => {
      const orderId = button.getAttribute('data-id');
      const currentStatus = button.getAttribute('data-status');
      openStatusModal(orderId, currentStatus);
    });
  });

  modalCloseBtn.addEventListener('click', closeStatusModal);
  modalCancelBtn.addEventListener('click', closeStatusModal);

  modal.addEventListener('click', e => {
    if (e.target === modal) closeStatusModal();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal.style.display === 'flex') {
      closeStatusModal();
    }
  });
});
</script>

</body>
</html>
