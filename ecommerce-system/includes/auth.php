<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

class Auth {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function register($full_name, $email, $password, $role = 'customer') {
        if (empty($full_name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'គ្រប់វាលត្រូវការបំពេញ'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'អ៊ីមែលមិនត្រឹមត្រូវ'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'ពាក្យសម្ងាត់ត្រូវតែ 6 តួរឡើង'];
        }

        $userExists = $this->db->fetchOne("SELECT user_id FROM users WHERE email = ?", [$email]);

        if ($userExists) {
            return ['success' => false, 'message' => 'អ៊ីមែលនេះបានចុះឈ្មោះរួចហើយ'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // You MUST add username when inserting since it's required in schema
        $username = explode('@', $email)[0] . rand(1000, 9999); // auto-generate username

        $insert = $this->db->query(
            "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)",
            [$username, $hashedPassword, $full_name, $email, $role]
        );

        return $insert
            ? ['success' => true, 'message' => 'ការចុះឈ្មោះជោគជ័យ']
            : ['success' => false, 'message' => 'កំហុសមួយបានកើតឡើង'];
    }

    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'អ៊ីមែល និងពាក្យសម្ងាត់ត្រូវការបំពេញ'];
        }

        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'អ៊ីមែលឬពាក្យសម្ងាត់មិនត្រឹមត្រូវ'];
        }

        // Session set
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        if ($user['role'] === 'admin') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = $user['full_name'];
        }

        return ['success' => true, 'role' => $user['role']];
    }

    public function isLoggedIn() {
        return !empty($_SESSION['logged_in']);
    }

    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }

    // Optional: You can add password reset later if you add columns like reset_token, reset_expires
}

$auth = new Auth($db);

// CSRF token init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
