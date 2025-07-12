<?php
ob_start();
define('ALLOW_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$userData = [];

// Fetch user data
try {
    $dbPdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $dbPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $dbPdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        throw new Exception("រកមិនឃើញគណនីអ្នកប្រើប្រាស់");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $errors[] = "កំហុសប្រព័ន្ធ: មិនអាចទាញយកព័ត៌មានគណនី";
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Token មិនត្រឹមត្រូវ";
    }

    // Update profile
    if (isset($_POST['update_profile']) && empty($errors)) {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');

        if (empty($username)) $errors[] = "ឈ្មោះមិនអាចទទេ";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "អ៊ីមែលមិនត្រឹមត្រូវ";

        if (empty($errors)) {
            try {
                $stmt = $dbPdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
                $stmt->execute([$username, $email, $phone, $address, $_SESSION['user_id']]);

                $_SESSION['username'] = $username;
                $_SESSION['email']    = $email;
                $_SESSION['success_message'] = "ព័ត៌មានគណនីបានធ្វើបច្ចុប្បន្នភាពដោយជោគជ័យ!";
                header('Location: account.php');
                exit;
            } catch (PDOException $e) {
                error_log("Update error: " . $e->getMessage());
                $errors[] = "កំហុសប្រព័ន្ធ: មិនអាចធ្វើបច្ចុប្បន្នភាពព័ត៌មាន";
            }
        }
    }

    // Change password
    if (isset($_POST['change_password']) && empty($errors)) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) $errors[] = "សូមបញ្ចូលពាក្យសម្ងាត់បច្ចុប្បន្ន";
        if (strlen($newPassword) < 8) $errors[] = "ពាក្យសម្ងាត់ថ្មីត្រូវតែយ៉ាងហោចណាស់ ៨ តួអក្សរ";
        if ($newPassword !== $confirmPassword) $errors[] = "ពាក្យសម្ងាត់ថ្មីមិនដូចគ្នា";

        if (empty($errors)) {
            try {
                $stmt = $dbPdo->prepare("SELECT password FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($currentPassword, $user['password'])) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $dbPdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);

                    $_SESSION['success_message'] = "ពាក្យសម្ងាត់បានផ្លាស់ប្តូរដោយជោគជ័យ!";
                    header('Location: account.php');
                    exit;
                } else {
                    $errors[] = "ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវ";
                }
            } catch (PDOException $e) {
                error_log("Password change error: " . $e->getMessage());
                $errors[] = "កំហុសប្រព័ន្ធ: មិនអាចផ្លាស់ប្តូរពាក្យសម្ងាត់";
            }
        }
    }
}


?>
<style>
    /* Account Page Styles */
.container.account-page {
  max-width: 600px;
  margin: 3rem auto;
  padding: 2rem;
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border-radius: 0.5rem;
}
.container.account-page h2 {
  font-size: 2rem;
  text-align: center;
  margin-bottom: 1.5rem;
  color: #2c3e50;
}
.container.account-page h4 {
  font-size: 1.25rem;
  margin-top: 2rem;
  margin-bottom: 1rem;
  color: #2c3e50;
}

/* Alerts */
.alert {
  padding: 0.75rem 1rem;
  border-radius: 0.25rem;
  margin-bottom: 1.5rem;
  font-size: 0.95rem;
  position: relative;
  transition: opacity 0.3s ease;
}
.alert-danger {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}
.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

/* Form Layout */
form#form {
  display: grid;
  gap: 1.5rem;
}
form#form .mb-3 {
  display: flex;
  flex-direction: column;
}
form#form label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #555;
}
form#form input,
form#form textarea {
  padding: 0.75rem 1rem;
  border: 1px solid #ccc;
  border-radius: 0.25rem;
  font-size: 1rem;
  transition: border-color 0.3s ease;
  font-family: 'Khmer OS', 'Arial', sans-serif;
}
form#form input:focus,
form#form textarea:focus {
  border-color: #3498db;
  outline: none;
}
form#form .invalid {
  border-color: #e74c3c !important;
}

/* Buttons */
form#form .btn-primary,
form#form .btn-warning {
  width: fit-content;
  padding: 0.5rem 1.25rem;
  border: none;
  border-radius: 0.25rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s ease;
  font-family: 'Khmer OS', 'Arial', sans-serif;
}
form#form .btn-primary {
  background: #3498db;
  color: #fff;
}
form#form .btn-primary:hover {
  background: #2980b9;
}
form#form .btn-warning {
  background: #e67e22;
  color: #fff;
}
form#form .btn-warning:hover {
  background: #d35400;
}

/* Responsive */
@media (max-width: 600px) {
  .container.account-page {
    margin: 2rem 1rem;
    padding: 1.5rem;
  }
}

</style>
<div class="container account-page">
    <h2>គណនីរបស់ខ្ញុំ</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <form method="post" id="form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
        <input type="hidden" name="update_profile" value="1">

        <div class="mb-3">
            <label for="username">ឈ្មោះ</label>
            <input id="username" type="text" name="username" class="form-control" value="<?= htmlspecialchars($userData['username'] ?? '', ENT_QUOTES) ?>" required>
        </div>
        <div class="mb-3">
            <label for="email">អ៊ីមែល</label>
            <input id="email" type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['email'] ?? '', ENT_QUOTES) ?>" required>
        </div>
        <div class="mb-3">
            <label for="phone">លេខទូរស័ព្ទ</label>
            <input id="phone" type="text" name="phone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="mb-3">
            <label for="address">អាសយដ្ឋាន</label>
            <textarea id="address" name="address" class="form-control"><?= htmlspecialchars($userData['address'] ?? '', ENT_QUOTES) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">ធ្វើបច្ចុប្បន្នភាព</button>
    </form>

    <h4 class="mt-4">ផ្លាស់ប្តូរពាក្យសម្ងាត់</h4>
    <form method="post" id="form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
        <input type="hidden" name="change_password" value="1">

        <div class="mb-3">
            <label for="current_password">ពាក្យសម្ងាត់បច្ចុប្បន្ន</label>
            <input id="current_password" type="password" name="current_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="new_password">ពាក្យសម្ងាត់ថ្មី</label>
            <input id="new_password" type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="confirm_password">បញ្ជាក់ពាក្យសម្ងាត់ថ្មី</label>
            <input id="confirm_password" type="password" name="confirm_password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-warning">ផ្លាស់ប្តូរពាក្យសម្ងាត់</button>
    </form>
</div>

<!-- Include account.js -->
<script src="<?= BASE_URL ?>/assets/js/account.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('form#form');
  
  forms.forEach(form => {
    form.addEventListener('submit', e => {
      let valid = true;
      
      // Clear previous invalid marks
      form.querySelectorAll('.invalid').forEach(el => {
        el.classList.remove('invalid');
      });

      // Validate each required field
      form.querySelectorAll('input, textarea').forEach(field => {
        if (field.hasAttribute('required') && !field.value.trim()) {
          field.classList.add('invalid');
          valid = false;
        }
        if (field.type === 'email' && field.value) {
          const emailPattern = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
          if (!emailPattern.test(field.value)) {
            field.classList.add('invalid');
            valid = false;
          }
        }
        if (field.name === 'new_password' && field.value) {
          if (field.value.length < 8) {
            field.classList.add('invalid');
            valid = false;
            alert('ពាក្យសម្ងាត់ថ្មីត្រូវមានយ៉ាងហោចណាស់ 8 តួអក្សរ');
          }
        }
        if (field.name === 'confirm_password' && field.value) {
          const newPwd = form.querySelector('input[name="new_password"]').value;
          if (newPwd !== field.value) {
            field.classList.add('invalid');
            valid = false;
            alert('ពាក្យសម្ងាត់បញ្ជាក់មិនត្រូវគ្នា');
          }
        }
      });

      if (!valid) {
        e.preventDefault();
        const firstError = form.querySelector('.invalid');
        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });
});

</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php ob_end_flush(); ?>
