<?php
/**
 * Deschide documentul din librărie într-o pagină care declanșează fereastra de tipărire (nu descărcare).
 */
require_once __DIR__ . '/config.php';
require_once 'includes/librarie_documente_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Parametru invalid.');
}

librarie_documente_ensure_tables($pdo);
$doc = librarie_documente_get($pdo, $id);
if (!$doc) {
    header('HTTP/1.1 404 Not Found');
    exit('Document inexistent.');
}

$url_doc = 'descarca-librarie-document.php?id=' . (int)$id . '&print=1';
$url_doc_esc = htmlspecialchars($url_doc, ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipărire - <?php echo htmlspecialchars($doc['nume_document']); ?></title>
</head>
<body>
<iframe id="doc-frame" src="<?php echo $url_doc_esc; ?>" style="width:100%;height:100vh;border:0;" title="Document pentru tipărire"></iframe>
<script>
(function() {
    var frame = document.getElementById('doc-frame');
    function doPrint() {
        try {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } catch (e) {
            window.print();
        }
    }
    frame.addEventListener('load', function() {
        setTimeout(doPrint, 500);
    });
})();
</script>
</body>
</html>
