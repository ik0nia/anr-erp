<?php
/**
 * Deschide un document generat într-un iframe și lansează dialogul de print.
 */
require_once __DIR__ . '/../config.php';

$url = trim((string)($_GET['url'] ?? ''));
if ($url === '') {
    header('HTTP/1.1 400 Bad Request');
    exit('Parametru lipsa.');
}

$parts = @parse_url($url);
$path = (string)($parts['path'] ?? '');
if ($path === '' || strpos($path, '/util/descarca-document.php') !== 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('URL invalid.');
}

// Forțăm inline pentru previzualizare/print și păstrăm token-ul primit.
$query = [];
if (!empty($parts['query'])) {
    parse_str((string)$parts['query'], $query);
}
$query['inline'] = '1';
$safe_url = '/util/descarca-document.php?' . http_build_query($query);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print document generat</title>
</head>
<body style="margin:0">
<iframe id="doc-frame" src="<?php echo htmlspecialchars($safe_url, ENT_QUOTES, 'UTF-8'); ?>" style="width:100%;height:100vh;border:0;" title="Document generat pentru print"></iframe>
<script>
(function () {
    var frame = document.getElementById('doc-frame');
    function doPrint() {
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (e) {
            window.print();
        }
    }
    frame.addEventListener('load', function () {
        setTimeout(doPrint, 450);
    });
})();
</script>
</body>
</html>
