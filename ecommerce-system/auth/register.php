<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($username)) {
        $errors['username'] = 'ឈ្មោះអ្នកប្រើប្រាស់គឺចាំបាច់';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'ឈ្មោះអ្នកប្រើប្រាស់ត្រូវតែមានយ៉ាងហោចណាស់ 4 តួអក្សរ';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'ឈ្មោះអ្នកប្រើប្រាស់អាចមានតែអក្សរ លេខ និង underscore (_)';
    }

    if (empty($email)) {
        $errors['email'] = 'អ៊ីម៉ែលគឺចាំបាច់';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'អ៊ីម៉ែលមិនត្រឹមត្រូវ';
    }

    if (empty($password)) {
        $errors['password'] = 'ពាក្យសម្ងាត់គឺចាំបាច់';
    } elseif (strlen($password) < 8) {  
        $errors['password'] = 'ពាក្យសម្ងាត់ត្រូវតែមានយ៉ាងហោចណាស់ 8 តួអក្សរ';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'ពាក្យសម្ងាត់ត្រូវតែមានយ៉ាងហោចណាស់ 1 អក្សរធំ និង 1 លេខ';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'ពាក្យសម្ងាត់មិនដូចគ្នា';
    }

    if (empty($fullName)) {
        $errors['full_name'] = 'ឈ្មោះពេញគឺចាំបាច់';
    }

    if (!empty($phone) && !preg_match('/^[0-9]{9,15}$/', $phone)) {
        $errors['phone'] = 'លេខទូរស័ព្ទមិនត្រឹមត្រូវ';
    }

    
    if (empty($errors)) {
        try {
            $query = "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1";
            $stmt = $db->getConnection()->prepare($query);
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors['register'] = 'ឈ្មោះអ្នកប្រើប្រាស់ ឬអ៊ីម៉ែលនេះមានរួចហើយ';
            }
        } catch (Exception $e) {
            $errors['database'] = 'មានបញ្ហាក្នុងការតភ្ជាប់ទៅកាន់មូលដ្ឋានទិន្នន័យ';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
    
  
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, full_name, phone, address, role) 
                      VALUES (?, ?, ?, ?, ?, ?, 'customer')";
            $stmt = $db->getConnection()->prepare($query);
            $stmt->bind_param('ssssss', $username, $email, $hashedPassword, $fullName, $phone, $address);
            $stmt->execute();
            
            if ($stmt->affected_rows === 1) {
                $_SESSION['success_message'] = 'អ្នកបានចុះឈ្មោះដោយជោគជ័យ! សូមចូលប្រើប្រាស់។';
                header('Location: login.php');
                exit;
            } else {
                $errors['database'] = 'មានបញ្ហាក្នុងការចុះឈ្មោះ';
            }
        } catch (Exception $e) {
            $errors['database'] = 'មានបញ្ហាក្នុងការចុះឈ្មោះ';
            error_log('Registration error: ' . $e->getMessage());
        }
    }
}

?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចុះឈ្មោះ</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        h1,form,label,input,button,.auth-links{
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }
        #address{
            font-family: 'Khmer OS', 'Arial', sans-serif;
            resize: none;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            width: 100%;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 5px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-user-plus"></i> ចុះឈ្មោះថ្មី</h1>
            
            <?php if (!empty($errors['register'])): ?>
                <div class="alert alert-danger">
                    <?= $errors['register'] ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">ឈ្មោះអ្នកប្រើប្រាស់ *</label>
                    <input type="text" id="username" name="username" value="<?= $_POST['username'] ?? '' ?>">
                    <?php if (!empty($errors['username'])): ?>
                        <span class="error"><?= $errors['username'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">អ៊ីម៉ែល *</label>
                    <input type="email" id="email" name="email" value="<?= $_POST['email'] ?? '' ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error"><?= $errors['email'] ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">ពាក្យសម្ងាត់ *</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password">
                            <i class="fas fa-eye toggle-password" data-target="password"></i>
                        </div>
                        <?php if (!empty($errors['password'])): ?>
                            <span class="error"><?= $errors['password'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">បញ្ជាក់ពាក្យសម្ងាត់ *</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password">
                            <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                        </div>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <span class="error"><?= $errors['confirm_password'] ?></span>
                        <?php endif; ?>
                    </div>

                </div>
                
                <div class="form-group">
                    <label for="full_name">ឈ្មោះពេញ</label>
                    <input type="text" id="full_name" name="full_name" value="<?= $_POST['full_name'] ?? '' ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">លេខទូរស័ព្ទ</label>
                        <input type="tel" id="phone" name="phone" value="<?= $_POST['phone'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">អាសយដ្ឋាន</label>
                    <textarea id="address" name="address" rows="4" cols="63" style="width: 450px; height: 111px;"><?= $_POST['address'] ?? '' ?></textarea>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> ចុះឈ្មោះ
                </button>
            </form>
            
            <div class="auth-links">
                <p>រួចរាល់ហើយ? <a href="login.php">ចូលប្រើប្រាស់</a></p>
            </div>
        </div>
    </div>
</body>
</html>
<script>
document.querySelectorAll('.toggle-password').forEach(function (icon) {
    icon.addEventListener('click', function () {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);

        if (input.type === 'password') {
            input.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
});
</script>
