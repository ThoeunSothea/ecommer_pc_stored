<?php
ob_start();
session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

defined('ALLOW_ACCESS') or die('Direct access not allowed');

// Admin access check
require_once __DIR__ . '/../../includes/auth.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$settings = [];

// Get current settings
try {
    $settings = $db->fetchOne("SELECT * FROM settings LIMIT 1");
} catch (Exception $e) {
    $error_message = "Failed to load settings: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $site_name = trim($_POST['site_name']);
        $site_email = trim($_POST['site_email']);
        $currency = trim($_POST['currency']);
        $tax_rate = floatval($_POST['tax_rate']);
        $shipping_cost = floatval($_POST['shipping_cost']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

        // Basic validation
        if (empty($site_name)) {
            throw new Exception("Site name is required");
        }

        if (!filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Update or insert settings
        if ($settings) {
            $query = "UPDATE settings SET 
                      site_name = ?, 
                      site_email = ?, 
                      currency = ?, 
                      tax_rate = ?, 
                      shipping_cost = ?, 
                      maintenance_mode = ?,
                      updated_at = NOW()";
            $params = [$site_name, $site_email, $currency, $tax_rate, $shipping_cost, $maintenance_mode];
        } else {
            $query = "INSERT INTO settings 
                     (site_name, site_email, currency, tax_rate, shipping_cost, maintenance_mode, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $params = [$site_name, $site_email, $currency, $tax_rate, $shipping_cost, $maintenance_mode];
        }

        $db->query($query, $params);
        $success_message = "Settings updated successfully!";
        
        // Refresh settings
        $settings = $db->fetchOne("SELECT * FROM settings LIMIT 1");

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Create settings table if not exists (one-time setup)
try {
    $db->query("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_name VARCHAR(100) NOT NULL DEFAULT 'My E-commerce',
            site_email VARCHAR(100) NOT NULL,
            currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            maintenance_mode TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (Exception $e) {
    $error_message = "Database setup error: " . $e->getMessage();
}
?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link href="../../assets/css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .container{
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        /* Form Group Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
        }

        /* Input Field Styles */
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
            background-color: #f9f9f9;
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
            background-color: #fff;
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }

        /* Select Dropdown Styles */
        .form-group select {
            appearance: none;
            background-position: right 10px center;
            background-size: 15px;
        }

        /* Checkbox Styles */
        .checkbox {
            display: flex;
            align-items: center;
        }

        .checkbox label {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 0;
        }

        .checkbox input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Button Styles */
        .btn-primary {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Khmer OS', 'Arial', sans-serif;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

    </style>
</head>
<body>
       
    <div class="container">
        <h1>System Settings</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'My E-commerce') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Site Email</label>
                <input type="email" name="site_email" value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Currency</label>
                <select name="currency">
                    <option value="USD" <?= ($settings['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                    <option value="EUR" <?= ($settings['currency'] ?? 'USD') === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                    <option value="KHR" <?= ($settings['currency'] ?? 'USD') === 'KHR' ? 'selected' : '' ?>>KHR (៛)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tax Rate (%)</label>
                <input type="number" step="0.01" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate'] ?? 0) ?>">
            </div>
            
            <div class="form-group">
                <label>Shipping Cost</label>
                <input type="number" step="0.01" name="shipping_cost" value="<?= htmlspecialchars($settings['shipping_cost'] ?? 0) ?>">
            </div>
            
            <div class="form-group checkbox">
                <label>
                    <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                    Maintenance Mode
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
    
    
</body>
</html>
<?php ob_end_flush(); ?>