<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username)) {
        $errors['username'] = 'ឈ្មោះអ្នកប្រើប្រាស់គឺចាំបាច់';
    }

    if (empty($password)) {
        $errors['password'] = 'ពាក្យសម្ងាត់គឺចាំបាច់';
    }

    if (empty($errors)) {
        $query = "SELECT user_id, username, password, role FROM users WHERE username = ?";
        $stmt = $db->getConnection()->prepare($query);
        
        if (!$stmt) {
            $errors['login'] = 'កំហុសក្នុងការរៀបចំសំណួរ';
            error_log('Prepare failed: ' . $db->getConnection()->error);
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    if ($user['role'] === 'admin') {
                        header('Location: /Mid-Exam/ecommerce-system/admin/psges/dashboard.php');
                    } else {
                        header('Location: /Mid-Exam/ecommerce-system/pages/main.php');
                    }
                    exit();
                } else {
                    $errors['login'] = 'ឈ្មោះអ្នកប្រើប្រាស់ ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ';
                }
            } else {
                $errors['login'] = 'ឈ្មោះអ្នកប្រើប្រាស់ ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចូលប្រើប្រាស់</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        h1, form, label, input, .auth-links {
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 5px;
            padding-left: 10px;
            
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-sign-in-alt"></i> ចូលប្រើប្រាស់</h1>

            <?php if (!empty($errors['login'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($errors['login']) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">ឈ្មោះអ្នកប្រើប្រាស់</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <?php if (!empty($errors['username'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['username']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group password-group">
                    <label for="password">ពាក្យសម្ងាត់</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> ចូលប្រើប្រាស់
                </button>
            </form>

            <div class="auth-links">
                <a href="register.php">ចុះឈ្មោះថ្មី</a>
                <a href="forgot_password.php">ភ្លេចពាក្យសម្ងាត់?</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const icon = this;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
