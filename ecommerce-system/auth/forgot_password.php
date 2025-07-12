<?php
// forgot_password.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $message = 'សូមបញ្ចូលអ៊ីម៉ែលរបស់អ្នក';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'អ៊ីម៉ែលមិនត្រឹមត្រូវ';
    } else {
        
        // Check if email exists in the database
        $query = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            // Generate reset token
            $token = bin2hex(random_bytes(32)); // Secure random token
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiry time

            // Store token and expiry in database
            $query = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?";
            $stmt = $db->getConnection()->prepare($query);
            $stmt->bind_param('sss', $token, $expires, $email);
            $stmt->execute();
            
            // Create the reset link
            $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
            
            // Send reset email (use PHP mail function for demo purposes)
            $to = $email;
            $subject = "Password Reset Request";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
            $headers .= "From: no-reply@yourdomain.com" . "\r\n"; // Update with your email

            // Construct the email message
            $messageContent = "
                <html>
                <head>
                    <title>Password Reset Request</title>
                </head>
                <body>
                    <p>សូមពិនិត្យមើលអ៊ីម៉ែលរបស់អ្នកសម្រាប់តំណរក្រោយពាក្យសម្ងាត់។</p>
                    <p>Click on the following link to reset your password:</p>
                    <a href='$resetLink'>$resetLink</a>
                </body>
                </html>
            ";

            // Send the email
            if (mail($to, $subject, $messageContent, $headers)) {
                $message = "សូមពិនិត្យមើលអ៊ីម៉ែលរបស់អ្នកសម្រាប់តំណរក្រោយពាក្យសម្ងាត់។";
            } else {
                $message = "បរាជ័យក្នុងការផ្ញើអ៊ីម៉ែល។ សូមព្យាយាមម្ដងទៀត។";
            }
        } else {
            $message = 'អ៊ីម៉ែលនេះមិនត្រូវបានរកឃើញទេ។';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ភ្លេចពាក្យសម្ងាត់</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1><i class="fas fa-key"></i> ភ្លេចពាក្យសម្ងាត់</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert <?= strpos($message, 'សូមពិនិត្យ') !== false ? 'alert-success' : 'alert-danger' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">អ៊ីម៉ែល</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane"></i> ផ្ញើតំណរក្រោយពាក្យសម្ងាត់
                </button>
            </form>
            
            <div class="auth-links">
                <a href="login.php">ត្រលប់ទៅការចូលប្រើប្រាស់</a>
            </div>
        </div>
    </div>
</body>
</html>
