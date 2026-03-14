<?php
/**
 * Deschide tabelul BPA (PDF cu antet din setări) pentru tipărire.
 */
require_once __DIR__ . '/config.php';
require_once 'includes/document_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ajutoare-bpa.php');
    exit;
}
$url_pdf = 'bpa-tabel-pdf.php?id=' . (int)$id;
?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tipărire Tabel BPA</title>
</head>
<body>
<iframe id="doc-frame" src="<?php echo htmlspecialchars($url_pdf); ?>" style="width:100%;height:100vh;border:0;" title="Tabel BPA pentru tipărire"></iframe>
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
        setTimeout(doPrint, 800);
    });
})();
</script>
</body>
</html>
