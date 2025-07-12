<?php
// reset_password.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$errors = [];
$success = false;

if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: forgot_password.php');
    exit;
}


$token = $_GET['token'];

// Check if token is valid
$query = "SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW()";
$stmt = $db->getConnection()->prepare($query);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $errors['token'] = 'តំណនេះមិនត្រឹមត្រូវ ឬបានផុតកំណត់។ សូមស្នើសុំតំណថ្មី។';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    if (empty($password)) {
        $errors['password'] = 'ពាក្យសម្ងាត់គឺចាំបាច់';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'ពាក្យសម្ងាត់ត្រូវតែមានយ៉ាងហោចណាស់ 6 តួអក្សរ';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'ពាក្យសម្ងាត់មិនដូចគ្នា';
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $query = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->bind_param('ss', $hashedPassword, $token);
        $stmt->execute();
        
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>កំណត់ពាក្យសម្ងាត់ថ្មី</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-key"></i> កំណត់ពាក្យសម្ងាត់ថ្មី</h1>
            
            <?php if (!empty($errors['token'])): ?>
                <div class="alert alert-danger">
                    <?= $errors['token'] ?>
                </div>
            <?php elseif ($success): ?>
                <div class="alert alert-success">
                    ពាក្យសម្ងាត់របស់អ្នកត្រូវបានកំណត់ឡើងវិញដោយជោគជ័យ! <a href="login.php">ចូលប្រើប្រាស់ឥឡូវនេះ</a>។
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        មានបញ្ហាក្នុងការកំណត់ពាក្យសម្ងាត់ឡើងវិញ។ សូមពិនិត្យមើលកំហុសខាងក្រោម។
                    </div>
                <?php endif; ?>
                
                <form action="reset_password.php?token=<?= $token ?>" method="POST">
                    <div class="form-group">
                        <label for="password">ពាក្យសម្ងាត់ថ្មី *</label>
                        <input type="password" id="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?>
                            <span class="error"><?= $errors['password'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">បញ្ជាក់ពាក្យសម្ងាត់ថ្មី *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <span class="error"><?= $errors['confirm_password'] ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn-reset">
                        <i class="fas fa-save"></i> រក្សាទុកពាក្យសម្ងាត់ថ្មី
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>