<?php
/**
 * Mapper template PDF pentru Formular 230.
 * Permite mapare manuală procentuală a câmpurilor [230...].
 */
require_once __DIR__ . '/../app/bootstrap.php';
require_once APP_ROOT . '/app/services/FundraisingService.php';
if (!function_exists('require_login')) {
    require_once APP_ROOT . '/includes/auth_helper.php';
}
if (!function_exists('csrf_field') || !function_exists('csrf_require_valid')) {
    require_once APP_ROOT . '/includes/csrf_helper.php';
}

require_login();

$eroare = '';
$succes = '';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Conexiunea la baza de date nu este disponibilă.';
    exit;
}
try {
    fundraising_f230_ensure_schema($pdo);
} catch (Throwable $e) {
    error_log('f230-template-mapper ensure_schema error: ' . $e->getMessage());
    $eroare = 'Nu s-a putut inițializa schema Fundraising. Verifică baza de date și drepturile utilizatorului SQL.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salveaza_mapare_template_f230'])) {
    try {
        csrf_require_valid();
        $res = fundraising_f230_save_template_map($pdo, $_POST);
        if (!empty($res['success'])) {
            $succes = 'Maparea template-ului a fost salvată.';
        } else {
            $eroare = (string)($res['error'] ?? 'Maparea nu a putut fi salvată.');
        }
    } catch (Throwable $e) {
        error_log('f230-template-mapper save error: ' . $e->getMessage());
        $eroare = 'Maparea nu a putut fi salvată din cauza unei erori interne. Reîncearcă.';
    }
}

try {
    $setari_modul = fundraising_f230_get_settings($pdo);
} catch (Throwable $e) {
    $setari_modul = [
        'template_exists' => false,
        'template_page_count' => 1,
        'template_sha256' => '',
        'template_preview_url' => '/util/f230-template-preview.php',
        'template_map_defaults_by_tag' => [],
    ];
    $eroare = 'Mapper-ul nu a putut încărca setările template-ului. Reîncearcă după un upload nou.';
}
$taguri_f230 = fundraising_f230_taguri_display();
$template_exists = !empty($setari_modul['template_exists']);
$template_page_count = (int)($setari_modul['template_page_count'] ?? 1);
if ($template_page_count < 1) {
    $template_page_count = 1;
}

if (!$template_exists) {
    $eroare = $eroare !== '' ? $eroare : 'Nu există template PDF activ. Încarcă mai întâi un template în Fundraising > Setări.';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapper Formular 230</title>
    <link href="/css/tailwind.css?v=<?php echo @filemtime(APP_ROOT . '/css/tailwind.css') ?: '1'; ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.344.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
<main class="w-full max-w-[98vw] mx-auto px-3 sm:px-4 py-4 sm:py-6" role="main">
    <section class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
        <header class="px-5 sm:px-6 py-4 border-b border-slate-200">
            <h1 class="text-xl font-semibold">Mapper template PDF — Formular 230</h1>
            <p class="text-sm text-slate-600 mt-1">
                Selectează câmpul și fă click în preview pentru poziționare. Maparea salvată va fi folosită de motorul PDF Overlay.
            </p>
        </header>
        <div class="p-5 sm:p-6">
            <?php if ($eroare !== ''): ?>
                <div class="mb-4 p-3 rounded-lg border-l-4 border-red-600 bg-red-50 text-red-800" role="alert">
                    <?php echo htmlspecialchars($eroare); ?>
                </div>
            <?php endif; ?>
            <?php if ($succes !== ''): ?>
                <div class="mb-4 p-3 rounded-lg border-l-4 border-emerald-600 bg-emerald-50 text-emerald-800" role="status">
                    <?php echo htmlspecialchars($succes); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4" id="mapper-form-f230">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="salveaza_mapare_template_f230" value="1">
                <input type="hidden" name="template_map_json" id="template-map-json-f230" value="">

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
                    <aside class="xl:col-span-1 space-y-3">
                        <div>
                            <label for="map-tag-select-f230" class="block text-sm font-medium mb-1">Tag activ</label>
                            <select id="map-tag-select-f230" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                <?php foreach ($taguri_f230 as $t): ?>
                                    <?php $tag_bracket = (string)$t['tag']; $tag_raw = trim($tag_bracket, '[]'); ?>
                                    <option value="<?php echo htmlspecialchars($tag_raw); ?>">
                                        <?php echo htmlspecialchars($tag_bracket . ' — ' . (string)$t['descriere']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="map-page-f230" class="block text-xs font-medium mb-1">Pagină</label>
                                <input id="map-page-f230" type="number" min="1" step="1" class="w-full px-2 py-1.5 rounded border border-slate-300">
                            </div>
                            <div>
                                <label for="map-font-f230" class="block text-xs font-medium mb-1">Font (pt)</label>
                                <input id="map-font-f230" type="number" min="6" max="24" step="0.5" class="w-full px-2 py-1.5 rounded border border-slate-300">
                            </div>
                            <div>
                                <label for="map-w-f230" class="block text-xs font-medium mb-1">Lățime (%)</label>
                                <input id="map-w-f230" type="number" min="0.5" max="100" step="0.1" class="w-full px-2 py-1.5 rounded border border-slate-300">
                            </div>
                            <div>
                                <label for="map-h-f230" class="block text-xs font-medium mb-1">Înălțime (%)</label>
                                <input id="map-h-f230" type="number" min="0.5" max="100" step="0.1" class="w-full px-2 py-1.5 rounded border border-slate-300">
                            </div>
                        </div>

                        <p id="map-coords-f230" class="text-xs text-slate-600">Coordonate: x=0.00%, y=0.00%</p>
                        <div id="map-status-list-f230" class="space-y-1 max-h-64 overflow-auto pr-1"></div>
                    </aside>

                    <section class="xl:col-span-2 min-w-0">
                        <div class="border border-slate-200 rounded-lg bg-slate-50 p-3">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <p class="text-xs text-slate-600">Click pe suprafața preview pentru poziționare.</p>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="btn-prev-page-map-f230" class="px-2 py-1 text-xs rounded border border-slate-300">Pagina anterioară</button>
                                    <button type="button" id="btn-next-page-map-f230" class="px-2 py-1 text-xs rounded border border-slate-300">Pagina următoare</button>
                                </div>
                            </div>
                            <div id="pdf-map-canvas-wrap-f230" class="overflow-y-auto overflow-x-hidden border border-slate-300 rounded bg-white" style="max-height: 80vh;">
                                <div id="pdf-map-stage-f230" class="relative w-full">
                                    <canvas id="pdf-map-canvas-f230" class="block w-full h-auto" aria-label="Previzualizare PDF formular 230"></canvas>
                                    <div id="pdf-map-overlay-f230" class="absolute inset-0 pointer-events-none" aria-hidden="true"></div>
                                    <button type="button" id="pdf-map-click-layer-f230" class="absolute inset-0 w-full h-full opacity-0 cursor-text" aria-label="Selectează poziția tagului"></button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" class="px-4 py-2 rounded-lg border border-slate-300" onclick="window.close();">Închide</button>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium">
                        <i data-lucide="save" class="w-4 h-4" aria-hidden="true"></i>
                        Salvează maparea
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
(function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    var templateExists = <?php echo $template_exists ? 'true' : 'false'; ?>;
    if (!templateExists) return;

    var templateSha = <?php echo json_encode((string)($setari_modul['template_sha256'] ?? '')); ?>;
    var previewBaseUrl = <?php echo json_encode((string)($setari_modul['template_preview_url'] ?? '')); ?>;
    var pageCount = <?php echo (int)$template_page_count; ?>;
    var mapDefaults = <?php echo json_encode((array)($setari_modul['template_map_defaults_by_tag'] ?? []), JSON_UNESCAPED_UNICODE); ?>;

    var form = document.getElementById('mapper-form-f230');
    var selectTag = document.getElementById('map-tag-select-f230');
    var inputPage = document.getElementById('map-page-f230');
    var inputFont = document.getElementById('map-font-f230');
    var inputW = document.getElementById('map-w-f230');
    var inputH = document.getElementById('map-h-f230');
    var coordsText = document.getElementById('map-coords-f230');
    var statusList = document.getElementById('map-status-list-f230');
    var previewWrap = document.getElementById('pdf-map-canvas-wrap-f230');
    var previewCanvas = document.getElementById('pdf-map-canvas-f230');
    var previewStage = document.getElementById('pdf-map-stage-f230');
    var overlay = document.getElementById('pdf-map-overlay-f230');
    var clickLayer = document.getElementById('pdf-map-click-layer-f230');
    var prevBtn = document.getElementById('btn-prev-page-map-f230');
    var nextBtn = document.getElementById('btn-next-page-map-f230');
    var hiddenJson = document.getElementById('template-map-json-f230');

    var state = {
        currentPage: 1,
        activeTag: selectTag ? String(selectTag.value || '') : ''
    };
    var pdfDoc = null;

    function ensureTag(tag) {
        if (!mapDefaults[tag]) {
            var isSignature = tag === '230semnatura';
            mapDefaults[tag] = {
                tag: tag,
                page: state.currentPage,
                x_pct: 5,
                y_pct: 5,
                w_pct: isSignature ? 22 : 18,
                h_pct: isSignature ? 8 : 2.8,
                font_pt: 10
            };
        }
        return mapDefaults[tag];
    }

    function updateInputsFromTag() {
        var item = ensureTag(state.activeTag);
        if (!item) return;
        inputPage.value = item.page;
        inputFont.value = item.font_pt;
        inputW.value = item.w_pct;
        inputH.value = item.h_pct;
        coordsText.textContent = 'Coordonate: x=' + Number(item.x_pct).toFixed(2) + '%, y=' + Number(item.y_pct).toFixed(2) + '%';
    }

    function refreshPreview() {
        var page = Math.max(1, Math.min(pageCount, state.currentPage));
        state.currentPage = page;
        renderPdfPage(page);
    }

    function drawOverlay() {
        if (!overlay || !clickLayer) return;
        var rect = clickLayer.getBoundingClientRect();
        overlay.innerHTML = '';

        Object.keys(mapDefaults).forEach(function (tag) {
            var it = mapDefaults[tag];
            if (!it || parseInt(it.page, 10) !== state.currentPage) return;
            var d = document.createElement('div');
            d.className = 'absolute border-2 rounded';
            d.style.left = Number(it.x_pct) + '%';
            d.style.top = Number(it.y_pct) + '%';
            d.style.width = Number(it.w_pct) + '%';
            d.style.height = Number(it.h_pct) + '%';
            var active = (tag === state.activeTag);
            d.style.borderColor = active ? '#2563eb' : '#16a34a';
            d.style.background = active ? 'rgba(37,99,235,0.12)' : 'rgba(22,163,74,0.08)';
            var label = document.createElement('span');
            label.className = 'absolute -top-5 left-0 text-[10px] px-1 rounded bg-white border border-slate-300 text-slate-700';
            label.textContent = '[' + tag + ']';
            d.appendChild(label);
            overlay.appendChild(d);
        });

        renderStatus();
    }

    function renderStatus() {
        if (!statusList) return;
        statusList.innerHTML = '';
        var tags = Object.keys(mapDefaults);
        tags.sort();
        tags.forEach(function (tag) {
            var item = mapDefaults[tag];
            var row = document.createElement('div');
            row.className = 'text-xs flex items-center justify-between gap-2 px-2 py-1 rounded border';
            var ok = !!item;
            row.className += ok ? ' border-emerald-200 bg-emerald-50 text-emerald-800' : ' border-amber-200 bg-amber-50 text-amber-800';
            row.innerHTML = '<span>[' + tag + ']</span><span>' + (ok ? ('p.' + item.page) : 'nemapat') + '</span>';
            statusList.appendChild(row);
        });
    }

    function updateTagFromInputs() {
        var item = ensureTag(state.activeTag);
        if (!item) return;
        item.page = Math.max(1, Math.min(pageCount, parseInt(inputPage.value || '1', 10) || 1));
        item.font_pt = Math.max(6, Math.min(24, parseFloat(inputFont.value || '10') || 10));
        item.w_pct = Math.max(0.5, Math.min(100, parseFloat(inputW.value || '10') || 10));
        item.h_pct = Math.max(0.5, Math.min(100, parseFloat(inputH.value || '2') || 2));
        state.currentPage = item.page;
        refreshPreview();
        updateInputsFromTag();
    }

    if (selectTag) {
        selectTag.addEventListener('change', function () {
            state.activeTag = String(selectTag.value || '');
            updateInputsFromTag();
            state.currentPage = parseInt(ensureTag(state.activeTag).page || 1, 10);
            refreshPreview();
        });
    }

    [inputPage, inputFont, inputW, inputH].forEach(function (el) {
        if (!el) return;
        el.addEventListener('change', updateTagFromInputs);
        el.addEventListener('blur', updateTagFromInputs);
    });

    if (clickLayer) {
        clickLayer.addEventListener('click', function (e) {
            var rect = clickLayer.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            var xPct = ((e.clientX - rect.left) / rect.width) * 100;
            var yPct = ((e.clientY - rect.top) / rect.height) * 100;
            var item = ensureTag(state.activeTag);
            item.x_pct = Math.max(0, Math.min(100, xPct));
            item.y_pct = Math.max(0, Math.min(100, yPct));
            item.page = state.currentPage;
            coordsText.textContent = 'Coordonate: x=' + Number(item.x_pct).toFixed(2) + '%, y=' + Number(item.y_pct).toFixed(2) + '%';
            drawOverlay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            state.currentPage = Math.max(1, state.currentPage - 1);
            refreshPreview();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            state.currentPage = Math.min(pageCount, state.currentPage + 1);
            refreshPreview();
        });
    }

    window.addEventListener('resize', drawOverlay);

    function renderFallbackSheet() {
        if (!previewCanvas || !previewWrap || !previewStage) {
            drawOverlay();
            return;
        }
        var wrapRect = previewWrap.getBoundingClientRect();
        var width = Math.max(300, Math.floor(wrapRect.width - 2));
        var height = Math.floor(width * 1.4142);
        previewCanvas.width = width;
        previewCanvas.height = height;
        previewCanvas.style.width = width + 'px';
        previewCanvas.style.height = height + 'px';
        previewStage.style.width = width + 'px';
        previewStage.style.height = height + 'px';
        var ctx = previewCanvas.getContext('2d');
        if (ctx) {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);
            ctx.strokeStyle = '#cbd5e1';
            ctx.lineWidth = 1;
            ctx.strokeRect(0.5, 0.5, width - 1, height - 1);
        }
        drawOverlay();
    }

    function renderPdfPage(pageNo) {
        if (!window.pdfjsLib || !previewCanvas || !previewWrap || !previewStage) {
            renderFallbackSheet();
            return;
        }
        if (!pdfDoc) {
            drawOverlay();
            return;
        }
        pdfDoc.getPage(pageNo).then(function (page) {
            var wrapRect = previewWrap.getBoundingClientRect();
            var targetWidth = Math.max(300, Math.floor(wrapRect.width - 2));
            var baseViewport = page.getViewport({ scale: 1 });
            var scale = targetWidth / baseViewport.width;
            var viewport = page.getViewport({ scale: scale });
            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            var ctx = previewCanvas.getContext('2d');
            if (!ctx) {
                renderFallbackSheet();
                return;
            }

            previewCanvas.width = Math.floor(viewport.width * ratio);
            previewCanvas.height = Math.floor(viewport.height * ratio);
            previewCanvas.style.width = Math.floor(viewport.width) + 'px';
            previewCanvas.style.height = Math.floor(viewport.height) + 'px';
            previewStage.style.width = Math.floor(viewport.width) + 'px';
            previewStage.style.height = Math.floor(viewport.height) + 'px';
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.clearRect(0, 0, viewport.width, viewport.height);

            page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise.then(function () {
                drawOverlay();
            }).catch(function () {
                renderFallbackSheet();
            });
        }).catch(function () {
            renderFallbackSheet();
        });
    }

    function initPdfDocument() {
        if (!window.pdfjsLib) {
            renderFallbackSheet();
            return;
        }
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
        var loadingTask = window.pdfjsLib.getDocument(previewBaseUrl + '?t=' + encodeURIComponent(templateSha));
        loadingTask.promise.then(function (doc) {
            pdfDoc = doc;
            if (doc.numPages && doc.numPages > 0) {
                pageCount = doc.numPages;
            }
            state.currentPage = Math.max(1, Math.min(pageCount, state.currentPage));
            refreshPreview();
        }).catch(function (err) {
            var msg = 'Nu s-a putut încărca preview-ul PDF. Verifică template-ul și reîncarcă pagina.';
            if (err && err.message) {
                msg = msg + ' (' + err.message + ')';
            }
            alert(msg);
            renderFallbackSheet();
        });
    }

    if (form && hiddenJson) {
        form.addEventListener('submit', function (e) {
            var items = [];
            Object.keys(mapDefaults).forEach(function (tag) {
                var item = mapDefaults[tag];
                if (!item) return;
                items.push({
                    tag: tag,
                    page: parseInt(item.page || 1, 10),
                    x_pct: Number(item.x_pct || 0),
                    y_pct: Number(item.y_pct || 0),
                    w_pct: Number(item.w_pct || 0),
                    h_pct: Number(item.h_pct || 0),
                    font_pt: Number(item.font_pt || 10)
                });
            });
            hiddenJson.value = JSON.stringify({
                template_sha256: templateSha,
                items: items
            });
            if (!hiddenJson.value) {
                e.preventDefault();
                alert('Nu s-a putut genera payload-ul de mapare.');
            }
        });
    }

    if (state.activeTag) {
        updateInputsFromTag();
        state.currentPage = parseInt(ensureTag(state.activeTag).page || 1, 10);
    }
    initPdfDocument();
})();
</script>
</body>
</html>
