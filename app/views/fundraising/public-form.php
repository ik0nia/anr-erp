<?php if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__, 3)); ?>
<!DOCTYPE html>
<html lang="ro">
<head><meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formular 230 - <?php echo htmlspecialchars(get_platform_name()); ?></title>
    <link href="/css/tailwind.css?v=<?php echo @filemtime(APP_ROOT . '/css/tailwind.css') ?: '1'; ?>" rel="stylesheet">
    <script src="https://unpkg.com/lucide@0.344.0"></script>
    <style>
        body {
            background-color: #808080;
        }

        #form-public-230 input[type="text"],
        #form-public-230 input[type="email"] {
            border: 3px solid #000 !important;
            background-color: #fff !important;
            color: #000 !important;
        }

        #form-public-230 fieldset {
            border: 3px solid #000 !important;
        }

        #signature-container-public {
            border: 3px solid #000 !important;
            background-color: #fff !important;
        }
    </style>
</head>
<body class="text-black min-h-screen">
<main class="max-w-4xl mx-auto px-4 py-6 sm:py-8" role="main">
    <section class="bg-white rounded-xl shadow border border-slate-200 overflow-hidden">
        <header class="p-6 sm:p-8 border-b border-slate-200">
            <div class="flex items-center justify-center">
                <a href="https://anrbihor.ro/"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center rounded focus:outline-none focus:ring-2 focus:ring-amber-500"
                   aria-label="Deschide site-ul Asociatia Nevazatorilor Bihor">
                    <img src="<?php echo htmlspecialchars(defined('PLATFORM_LOGO_URL') ? PLATFORM_LOGO_URL : ''); ?>"
                         alt="Logo <?php echo htmlspecialchars(get_platform_name()); ?>"
                         class="h-16 sm:h-20 w-auto object-contain">
                </a>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-center">Formular 230</h1>
            <p class="mt-2 text-sm text-black text-center">
                Completează formularul pentru redirecționarea a 3.5% către Asociația Nevăzătorilor Bihor.
            </p>
        </header>

        <div class="p-6 sm:p-8">
            <?php if (empty($template_activ)): ?>
                <div class="mb-4 p-4 rounded-lg border-l-4 border-amber-600 bg-amber-100 text-amber-900" role="status" aria-live="polite">
                    Formularul este temporar indisponibil. Administratorul nu a configurat încă template-ul PDF.
                </div>
            <?php endif; ?>
            <?php if ($eroare !== ''): ?>
                <div class="mb-4 p-4 rounded-lg border-l-4 border-red-600 bg-red-100 text-red-900" role="alert" aria-live="assertive">
                    <?php echo htmlspecialchars($eroare); ?>
                </div>
            <?php endif; ?>
            <?php if ($succes !== ''): ?>
                <div class="mb-4 p-4 rounded-lg border-l-4 border-emerald-600 bg-emerald-100 text-emerald-900" role="status" aria-live="polite">
                    <?php echo htmlspecialchars($succes); ?>
                    <?php if ($warning !== ''): ?>
                        <p class="mt-1 text-sm"><?php echo htmlspecialchars($warning); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/fundraising/formular-230" class="space-y-4" id="form-public-230" <?php echo empty($template_activ) ? 'aria-disabled="true"' : ''; ?>>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="trimite_formular_230_public" value="1">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="f230-nume">Nume <span class="text-red-600">*</span></label>
                        <input id="f230-nume" type="text" name="nume" required value="<?php echo htmlspecialchars((string)$valori['nume']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="f230-initiala">Inițiala tatălui</label>
                        <input id="f230-initiala" type="text" name="initiala_tatalui" maxlength="3" value="<?php echo htmlspecialchars((string)$valori['initiala_tatalui']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="f230-prenume">Prenume <span class="text-red-600">*</span></label>
                        <input id="f230-prenume" type="text" name="prenume" required value="<?php echo htmlspecialchars((string)$valori['prenume']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="f230-cnp">CNP <span class="text-red-600">*</span></label>
                        <input id="f230-cnp" type="text" name="cnp" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" required value="<?php echo htmlspecialchars((string)$valori['cnp']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="f230-telefon">Telefon <span class="text-red-600">*</span></label>
                        <input id="f230-telefon" type="text" name="telefon" required value="<?php echo htmlspecialchars((string)$valori['telefon']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="f230-email">Email <span class="text-red-600">*</span></label>
                        <input id="f230-email" type="email" name="email" required value="<?php echo htmlspecialchars((string)$valori['email']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    </div>
                </div>

                <fieldset class="rounded-lg border border-black p-3">
                    <legend class="px-1 text-sm font-medium">Adresă</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-2">
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-localitate">Localitatea <span class="text-red-600">*</span></label>
                            <input id="f230-localitate" type="text" name="localitate" required value="<?php echo htmlspecialchars((string)$valori['localitate']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-judet">Județ <span class="text-red-600">*</span></label>
                            <input id="f230-judet" type="text" name="judet" required value="<?php echo htmlspecialchars((string)$valori['judet']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-cp">Cod poștal</label>
                            <input id="f230-cp" type="text" name="cod_postal" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" value="<?php echo htmlspecialchars((string)$valori['cod_postal']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-strada">Strada <span class="text-red-600">*</span></label>
                            <input id="f230-strada" type="text" name="strada" required value="<?php echo htmlspecialchars((string)$valori['strada']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-numar">Număr <span class="text-red-600">*</span></label>
                            <input id="f230-numar" type="text" name="numar" required value="<?php echo htmlspecialchars((string)$valori['numar']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-bloc">Bloc</label>
                            <input id="f230-bloc" type="text" name="bloc" maxlength="10" value="<?php echo htmlspecialchars((string)$valori['bloc']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-scara">Scară</label>
                            <input id="f230-scara" type="text" name="scara" maxlength="10" value="<?php echo htmlspecialchars((string)$valori['scara']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-etaj">Etaj</label>
                            <input id="f230-etaj" type="text" name="etaj" maxlength="10" value="<?php echo htmlspecialchars((string)$valori['etaj']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="f230-apartament">Apartament</label>
                            <input id="f230-apartament" type="text" name="apartament" maxlength="10" value="<?php echo htmlspecialchars((string)$valori['apartament']); ?>" class="w-full px-3 py-2 rounded-lg border border-black bg-white text-black focus:ring-2 focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </fieldset>

                <div>
                    <label class="block text-sm font-medium mb-1">Semnătură <span class="text-red-600">*</span></label>
                    <p class="text-xs text-black mb-2">Semnați cu mouse-ul sau cu degetul. Culoarea semnăturii este albastru închis, fundal transparent.</p>
                    <div id="signature-container-public" class="rounded-lg border border-black bg-white p-2">
                        <canvas id="signature-pad-public" class="w-full rounded bg-white" style="height: 180px;" aria-label="Zonă semnătură formular 230"></canvas>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <button type="button" id="signature-clear-public" class="px-3 py-1.5 text-sm bg-gray-100 text-black rounded hover:bg-gray-200 border border-black" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>Șterge semnătura</button>
                    </div>
                    <input type="hidden" name="signature_data" id="signature-data-public" value="<?php echo htmlspecialchars((string)$valori['signature_data']); ?>">
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="gdpr_acord" value="1" <?php echo !empty($valori['gdpr_acord']) ? 'checked' : ''; ?> class="w-4 h-4 text-amber-600 border-black rounded focus:ring-amber-500" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        Acord GDPR <span class="text-red-600">*</span>
                    </label>
                </div>

                <div class="pt-2 flex flex-wrap gap-3">
                    <button type="submit" id="btn-submit-public" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-emerald-500 disabled:opacity-60 disabled:cursor-not-allowed" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        <i data-lucide="send" class="w-4 h-4" aria-hidden="true"></i>
                        Trimite formularul
                    </button>
                    <button type="reset" id="btn-reset-form-public" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-slate-500 disabled:opacity-60 disabled:cursor-not-allowed" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                        <i data-lucide="rotate-ccw" class="w-4 h-4" aria-hidden="true"></i>
                        Resetează
                    </button>
                </div>
            </form>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" id="btn-share-generic" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
                    Distribuie
                </button>
                <button type="button" id="btn-share-whatsapp" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium focus:ring-2 focus:ring-green-500 disabled:opacity-60 disabled:cursor-not-allowed" <?php echo empty($template_activ) ? 'disabled' : ''; ?>>
                    <i data-lucide="message-circle" class="w-4 h-4" aria-hidden="true"></i>
                    Distribuie pe Whatsapp
                </button>
            </div>
        </div>
        <footer class="px-6 sm:px-8 py-4 border-t border-slate-200 bg-slate-50">
            <div class="flex justify-center">
                <a href="https://anrbihor.ro/"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white rounded-lg font-medium focus:ring-2 focus:ring-slate-500"
                   aria-label="Inapoi la site-ul Asociatia Nevazatorilor Bihor">
                    <i data-lucide="arrow-left" class="w-4 h-4" aria-hidden="true"></i>
                    Inapoi la site
                </a>
            </div>
        </footer>
    </section>
</main>

<script>
(function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    var templateActive = <?php echo !empty($template_activ) ? 'true' : 'false'; ?>;
    var publicUrl = <?php echo json_encode((string)$public_url); ?>;
    var shareMessage = 'Te rog, completeaza si tu Formularul 230 pentru Asociatia Nevazatorilor Bihor pentru a redirectiona 3,5% pentru nevazatori. Ai aici linkul formularului online, se poate completa de pe telefon: ' + publicUrl;

    var shareBtn = document.getElementById('btn-share-generic');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            if (navigator.share) {
                navigator.share({
                    title: 'Formular 230 - Asociatia Nevazatorilor Bihor',
                    text: shareMessage,
                    url: publicUrl
                }).catch(function () {});
            } else if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shareMessage).then(function () {
                    alert('Link copiat. Il poti distribui mai departe.');
                }).catch(function () {
                    alert(shareMessage);
                });
            } else {
                alert(shareMessage);
            }
        });
    }

    var waBtn = document.getElementById('btn-share-whatsapp');
    if (waBtn) {
        waBtn.addEventListener('click', function () {
            var waUrl = 'https://wa.me/?text=' + encodeURIComponent(shareMessage);
            window.open(waUrl, '_blank', 'noopener');
        });
    }

    var canvas = document.getElementById('signature-pad-public');
    var hidden = document.getElementById('signature-data-public');
    var clearBtn = document.getElementById('signature-clear-public');
    if (!templateActive) {
        var formDisabled = document.getElementById('form-public-230');
        if (formDisabled) {
            formDisabled.addEventListener('submit', function (e) {
                e.preventDefault();
                alert('Formularul nu este disponibil în acest moment.');
            });
        }
    }
    if (templateActive && canvas && hidden) {
        var ctx = canvas.getContext('2d');
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var drawing = false;
        var hasStroke = false;
        var color = '#0000FF';

        function resizeCanvas() {
            var cssWidth = canvas.clientWidth || 600;
            var cssHeight = 180;
            canvas.width = Math.floor(cssWidth * ratio);
            canvas.height = Math.floor(cssHeight * ratio);
            canvas.style.height = cssHeight + 'px';
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = 2.2;
            ctx.strokeStyle = color;
            if (hidden.value) {
                var img = new Image();
                img.onload = function () {
                    ctx.drawImage(img, 0, 0, cssWidth, cssHeight);
                };
                img.src = hidden.value;
            }
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function pointFromEvent(e) {
            var rect = canvas.getBoundingClientRect();
            var x, y;
            if (e.touches && e.touches.length) {
                x = e.touches[0].clientX - rect.left;
                y = e.touches[0].clientY - rect.top;
            } else {
                x = e.clientX - rect.left;
                y = e.clientY - rect.top;
            }
            return { x: x, y: y };
        }

        function startDraw(e) {
            drawing = true;
            hasStroke = true;
            var p = pointFromEvent(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }
        function moveDraw(e) {
            if (!drawing) return;
            var p = pointFromEvent(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }
        function stopDraw() {
            if (!drawing) return;
            drawing = false;
            ctx.closePath();
            if (hasStroke) hidden.value = canvas.toDataURL('image/png');
        }

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', moveDraw);
        window.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', moveDraw, { passive: false });
        window.addEventListener('touchend', stopDraw);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hidden.value = '';
                hasStroke = false;
            });
        }

        var form = document.getElementById('form-public-230');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!hidden.value) {
                    e.preventDefault();
                    alert('Semnătura este obligatorie.');
                    return;
                }
                var submitBtn = document.getElementById('btn-submit-public');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('aria-busy', 'true');
                    submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin" aria-hidden="true"></i> Se trimite...';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        }

        var resetBtn = document.getElementById('btn-reset-form-public');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                setTimeout(function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    hidden.value = '';
                    hasStroke = false;
                }, 0);
            });
        }
    }
})();
</script>
</body>
</html>
