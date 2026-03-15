<?php
/**
 * Formular profil membru - Layout 3 coloane
 * Organizat specific pentru pagina de profil
 */
function render_formular_profil_membru($membru, $eroare = '', $istoric_modificari = []) {
    $is_edit = !empty($membru);
    $membru = $membru ?: [];
    
    // Funcție helper pentru a obține valoarea câmpului
    $val = function($field, $default = '') use ($membru, $is_edit) {
        if ($is_edit && isset($membru[$field])) {
            $value = $membru[$field];
            if ($value === null || $value === '') return $default;
            if (is_bool($value)) return $value ? '1' : '0';
            return htmlspecialchars($value);
        }
        return htmlspecialchars($_POST[$field] ?? $default);
    };
    
    $checked = function($field, $value) use ($membru, $is_edit) {
        if ($is_edit && isset($membru[$field]) && $membru[$field] == $value) {
            return 'checked';
        }
        if (!$is_edit && isset($_POST[$field]) && $_POST[$field] == $value) {
            return 'checked';
        }
        return '';
    };
    
    $selected = function($field, $value) use ($membru, $is_edit) {
        if ($is_edit && isset($membru[$field]) && $membru[$field] == $value) {
            return 'selected';
        }
        if (!$is_edit && isset($_POST[$field]) && $_POST[$field] == $value) {
            return 'selected';
        }
        return '';
    };
    
    $input_class = 'w-full px-3 py-2 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-slate-900 dark:text-white dark:bg-gray-700';
    ?>
    
    <form id="form-membru-profil" method="post" action="/membru-profil?id=<?php echo $membru['id']; ?>" 
          enctype="multipart/form-data" 
          class="space-y-6">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="membru_id" value="<?php echo $membru['id']; ?>">
        <input type="hidden" name="actualizeaza_membru" value="1">
        <input type="hidden" id="status_dosar" name="status_dosar" value="<?php echo $val('status_dosar', 'Activ'); ?>">
        
        <!-- Layout 3 coloane -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Coloana 1: Nume, Prenume, Data nașterii, Date de contact, Acord GDPR -->
            <div class="space-y-4">
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="user" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Date Personale
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="nume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">
                                    Nume <span class="text-red-600 dark:text-red-400">*</span>
                                </label>
                                <input type="text" id="nume" name="nume" value="<?php echo $val('nume'); ?>" required
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="prenume" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">
                                    Prenume <span class="text-red-600 dark:text-red-400">*</span>
                                </label>
                                <input type="text" id="prenume" name="prenume" value="<?php echo $val('prenume'); ?>" required
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="dosarnr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr. Dosar</label>
                                <input type="text" id="dosarnr" name="dosarnr" value="<?php echo $val('dosarnr'); ?>" maxlength="6"
                                       class="<?php echo $input_class; ?>"
                                       aria-label="Număr dosar (max. 6 caractere)">
                            </div>
                            <div>
                                <label for="dosardata" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data Dosar</label>
                                <input type="date" id="dosardata" name="dosardata" value="<?php echo $val('dosardata'); ?>"
                                       class="<?php echo $input_class; ?>"
                                       aria-label="Data dosar">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="datanastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data Nașterii</label>
                                <input type="date" id="datanastere" name="datanastere" value="<?php echo $val('datanastere'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="locnastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Loc. Nașterii</label>
                                <input type="text" id="locnastere" name="locnastere" value="<?php echo $val('locnastere'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="judnastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Jud. Nașterii</label>
                                <input type="text" id="judnastere" name="judnastere" value="<?php echo $val('judnastere'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="phone" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Date de Contact Membru
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="telefonnev" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon</label>
                                <input type="tel" id="telefonnev" name="telefonnev" value="<?php echo $val('telefonnev'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="telefonapartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon Aparținător</label>
                                <input type="tel" id="telefonapartinator" name="telefonapartinator" value="<?php echo $val('telefonapartinator'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $val('email'); ?>" 
                                   class="<?php echo $input_class; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="users" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Date Aparținător
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="nume_apartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nume Aparținător</label>
                                <input type="text" id="nume_apartinator" name="nume_apartinator" value="<?php echo $val('nume_apartinator'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="prenume_apartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Prenume Aparținător</label>
                                <input type="text" id="prenume_apartinator" name="prenume_apartinator" value="<?php echo $val('prenume_apartinator'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div>
                            <label for="telefonapartinator" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Telefon Aparținător</label>
                            <input type="tel" id="telefonapartinator" name="telefonapartinator" value="<?php echo $val('telefonapartinator'); ?>" 
                                   class="<?php echo $input_class; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="shield-check" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Acord GDPR
                    </h3>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" id="gdpr" name="gdpr" value="1" <?php echo $checked('gdpr', '1'); ?> 
                                   class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700">
                            <span class="ml-2 text-sm text-slate-800 dark:text-gray-200">Acord GDPR</span>
                        </label>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="file-text" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Notă
                    </h3>
                    <div>
                        <textarea id="notamembru" name="notamembru" rows="4" 
                                  class="<?php echo $input_class; ?>"><?php echo $val('notamembru'); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Coloana 2: Act de identitate, Domiciliu, Documente generate, Istoric modificări -->
            <div class="space-y-4">
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="id-card" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Act de Identitate
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="cnp" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">
                                    CNP <span class="text-red-600 dark:text-red-400">*</span>
                                </label>
                                <input type="text" id="cnp" name="cnp" value="<?php echo $val('cnp'); ?>" 
                                       maxlength="13" pattern="[0-9]{13}" inputmode="numeric" required
                                       class="<?php echo $input_class; ?>"
                                       aria-describedby="cnp-desc cnp-eroare">
                                <p id="cnp-desc" class="text-xs text-slate-600 dark:text-gray-400 mt-1">Exact 13 cifre</p>
                                <p id="cnp-eroare" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden" role="alert"></p>
                            </div>
                            <div>
                                <label for="sex" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Sex</label>
                                <select id="sex" name="sex" class="<?php echo $input_class; ?>">
                                    <option value="">Selectează</option>
                                    <option value="Masculin" <?php echo $selected('sex', 'Masculin'); ?>>Masculin</option>
                                    <option value="Feminin" <?php echo $selected('sex', 'Feminin'); ?>>Feminin</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="ciseria" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Seria C.I.</label>
                                <input type="text" id="ciseria" name="ciseria" value="<?php echo $val('ciseria'); ?>" maxlength="2"
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="cinumar" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Număr C.I.</label>
                                <input type="text" id="cinumar" name="cinumar" value="<?php echo $val('cinumar'); ?>" maxlength="7"
                                       inputmode="numeric" pattern="[0-9]{1,7}" class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="cielib" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">C.I. eliberat de</label>
                                <input type="text" id="cielib" name="cielib" value="<?php echo $val('cielib'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="cidataelib" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">C.I. data elib.</label>
                                <input type="date" id="cidataelib" name="cidataelib" value="<?php echo $val('cidataelib'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="cidataexp" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">C.I. data expirării</label>
                                <input type="date" id="cidataexp" name="cidataexp" value="<?php echo $val('cidataexp'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="locnastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Loc. Nașterii</label>
                                <input type="text" id="locnastere" name="locnastere" value="<?php echo $val('locnastere'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="judnastere" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Jud. Nașterii</label>
                                <input type="text" id="judnastere" name="judnastere" value="<?php echo $val('judnastere'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documente generate pentru acest membru -->
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="file-text" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Documente generate
                    </h3>
                    <?php
                    $documente_generate = [];
                    $doc_dir = APP_ROOT . DIRECTORY_SEPARATOR . 'documentegenerate';
                    if (is_dir($doc_dir)) {
                        $pattern = $doc_dir . DIRECTORY_SEPARATOR . '*-' . preg_replace('/\s+/', '', ($membru['nume'] ?? '')) . ($membru['prenume'] ?? '') . '-*.pdf';
                        foreach (glob($pattern) as $file_path) {
                            $basename = basename($file_path);
                            $documente_generate[] = [
                                'nume' => $basename,
                                'path' => $file_path,
                            ];
                        }
                    }
                    if (empty($documente_generate)): ?>
                        <p class="text-sm text-slate-600 dark:text-gray-400">Nu există documente generate salvate pentru acest membru.</p>
                    <?php else: ?>
                        <ul class="space-y-2 text-sm text-slate-800 dark:text-gray-200">
                            <?php foreach ($documente_generate as $doc): 
                                $nume_afisat = $doc['nume'];
                                $url = 'documentegenerate/' . rawurlencode($doc['nume']);
                            ?>
                            <li class="flex items-center justify-between gap-2">
                                <span class="truncate" title="<?php echo htmlspecialchars($nume_afisat); ?>">
                                    <?php echo htmlspecialchars($nume_afisat); ?>
                                </span>
                                <a href="<?php echo $url; ?>" target="_blank"
                                   class="inline-flex items-center px-2 py-1 text-xs rounded border border-amber-500 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/30"
                                   aria-label="Descarcă documentul <?php echo htmlspecialchars($nume_afisat); ?>">
                                    <i data-lucide="download" class="w-3 h-3 mr-1" aria-hidden="true"></i>
                                    Descarcă
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Istoric modificări pentru profil membru -->
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="history" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Istoric modificări
                    </h3>
                    <?php if (empty($istoric_modificari)): ?>
                        <p class="text-sm text-slate-600 dark:text-gray-400">Nu există înregistrări în istoricul de modificări pentru acest membru.</p>
                    <?php else: ?>
                        <div class="max-h-64 overflow-y-auto border border-slate-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800">
                            <ul class="divide-y divide-slate-200 dark:divide-gray-700 text-sm">
                                <?php foreach ($istoric_modificari as $log): ?>
                                <li class="px-3 py-2">
                                    <div class="flex justify-between items-center gap-2">
                                        <span class="text-xs text-slate-500 dark:text-gray-400">
                                            <?php
                                            $dt = $log['data_ora'] ?? '';
                                            $dt_out = $dt;
                                            if ($dt) {
                                                $ts = strtotime($dt);
                                                if ($ts) {
                                                    $dt_out = date('d.m.Y H:i', $ts);
                                                }
                                            }
                                            echo htmlspecialchars($dt_out);
                                            ?>
                                        </span>
                                        <span class="text-xs font-medium text-slate-700 dark:text-gray-200">
                                            <?php echo htmlspecialchars($log['utilizator'] ?? ''); ?>
                                        </span>
                                    </div>
                                    <p class="mt-1 text-slate-800 dark:text-gray-100">
                                        <?php echo nl2br(htmlspecialchars($log['actiune'] ?? '')); ?>
                                    </p>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="home" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Domiciliu
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="domloc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Localitatea</label>
                                <input type="text" id="domloc" name="domloc" value="<?php echo $val('domloc'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="judet_domiciliu" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Județ</label>
                                <input type="text" id="judet_domiciliu" name="judet_domiciliu" value="<?php echo $val('judet_domiciliu'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="codpost" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Cod Postal</label>
                                <input type="text" id="codpost" name="codpost" value="<?php echo $val('codpost'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-5 gap-2">
                            <div class="col-span-2">
                                <label for="domstr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Str.</label>
                                <input type="text" id="domstr" name="domstr" value="<?php echo $val('domstr'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="domnr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr.</label>
                                <input type="text" id="domnr" name="domnr" value="<?php echo $val('domnr'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="dombl" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Bl.</label>
                                <input type="text" id="dombl" name="dombl" value="<?php echo $val('dombl'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="domsc" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Sc.</label>
                                <input type="text" id="domsc" name="domsc" value="<?php echo $val('domsc'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-5 gap-2">
                            <div>
                                <label for="domet" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Et.</label>
                                <input type="text" id="domet" name="domet" value="<?php echo $val('domet'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="domap" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Ap.</label>
                                <input type="text" id="domap" name="domap" value="<?php echo $val('domap'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="tipmediuur" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Tip Mediu</label>
                                <select id="tipmediuur" name="tipmediuur" class="<?php echo $input_class; ?>">
                                    <option value="">Selectează</option>
                                    <option value="Urban" <?php echo $selected('tipmediuur', 'Urban'); ?>>Urban</option>
                                    <option value="Rural" <?php echo $selected('tipmediuur', 'Rural'); ?>>Rural</option>
                                </select>
                            </div>
                            <div>
                                <label for="primaria" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Primăria de Domiciliu</label>
                                <input type="text" id="primaria" name="primaria" value="<?php echo $val('primaria'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Coloana 3: Date handicap, Certificat, Grad, Atasament certificat -->
            <div class="space-y-4">
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="heart" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Date despre Handicap
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="hgrad" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Grad Handicap</label>
                                <select id="hgrad" name="hgrad" class="<?php echo $input_class; ?>">
                                    <option value="">Selectează</option>
                                    <option value="Grav cu insotitor" <?php echo $selected('hgrad', 'Grav cu insotitor'); ?>>Grav cu insotitor</option>
                                    <option value="Grav" <?php echo $selected('hgrad', 'Grav'); ?>>Grav</option>
                                    <option value="Accentuat" <?php echo $selected('hgrad', 'Accentuat'); ?>>Accentuat</option>
                                    <option value="Mediu" <?php echo $selected('hgrad', 'Mediu'); ?>>Mediu</option>
                                    <option value="Usor" <?php echo $selected('hgrad', 'Usor'); ?>>Usor</option>
                                    <option value="Alt handicap" <?php echo $selected('hgrad', 'Alt handicap'); ?>>Alt handicap</option>
                                    <option value="Asociat" <?php echo $selected('hgrad', 'Asociat'); ?>>Asociat</option>
                                    <option value="Fara handicap" <?php echo $selected('hgrad', 'Fara handicap'); ?>>Fara handicap</option>
                                </select>
                            </div>
                            <div>
                                <label for="hdur" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Valabilitate Certificat</label>
                                <select id="hdur" name="hdur" class="<?php echo $input_class; ?>">
                                    <option value="">Selectează</option>
                                    <option value="Permanent" <?php echo $selected('hdur', 'Permanent'); ?>>Permanent</option>
                                    <option value="Revizuibil" <?php echo $selected('hdur', 'Revizuibil'); ?>>Revizuibil</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="hmotiv" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Motiv Handicap</label>
                            <input type="text" id="hmotiv" name="hmotiv" value="<?php echo $val('hmotiv'); ?>" 
                                   class="<?php echo $input_class; ?>">
                        </div>
                        <div>
                            <label for="diagnostic" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Diagnostic</label>
                            <textarea id="diagnostic" name="diagnostic" rows="3" 
                                      class="<?php echo $input_class; ?>"><?php echo $val('diagnostic'); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i data-lucide="file-check" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Certificat Handicap
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="cenr" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Nr. Certificat Handicap</label>
                                <input type="text" id="cenr" name="cenr" value="<?php echo $val('cenr'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="cedata" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data Certificatului</label>
                                <input type="date" id="cedata" name="cedata" value="<?php echo $val('cedata'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                            <div>
                                <label for="ceexp" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">Data Expirării</label>
                                <input type="date" id="ceexp" name="ceexp" value="<?php echo $val('ceexp'); ?>" 
                                       class="<?php echo $input_class; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <i data-lucide="paperclip" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                            Atașament Act Identitate
                        </div>
                        <?php if (!empty($membru['doc_ci'])): ?>
                        <a href="uploads/ci/<?php echo htmlspecialchars($membru['doc_ci']); ?>" 
                           target="_blank" 
                           class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg flex items-center gap-1 transition-colors">
                            <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                            Descarcă Document
                        </a>
                        <?php endif; ?>
                    </h3>
                    <div>
                        <?php if (!empty($membru['doc_ci'])): ?>
                        <div class="mb-3 p-2 bg-white dark:bg-gray-800 rounded border border-slate-200 dark:border-gray-600">
                            <p class="text-sm text-slate-700 dark:text-gray-300 mb-1">Fișier actual:</p>
                            <p class="text-xs text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($membru['doc_ci']); ?></p>
                        </div>
                        <?php endif; ?>
                        <label for="doc_ci" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">
                            Încarcă fișier nou
                        </label>
                        <input type="file" id="doc_ci" name="doc_ci" accept="image/*,.pdf" 
                               class="<?php echo $input_class; ?>">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Maxim 5 MB (JPG, PNG, GIF, PDF)</p>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-gray-700/30 rounded-lg p-4 border border-slate-200 dark:border-gray-600">
                    <h3 class="text-md font-semibold text-slate-900 dark:text-white mb-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <i data-lucide="paperclip" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                            Atașament Certificat Handicap
                        </div>
                        <?php if (!empty($membru['doc_ch'])): ?>
                        <a href="uploads/ch/<?php echo htmlspecialchars($membru['doc_ch']); ?>" 
                           target="_blank" 
                           class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg flex items-center gap-1 transition-colors">
                            <i data-lucide="download" class="w-4 h-4" aria-hidden="true"></i>
                            Descarcă Document
                        </a>
                        <?php endif; ?>
                    </h3>
                    <div>
                        <?php if (!empty($membru['doc_ch'])): ?>
                        <div class="mb-3 p-2 bg-white dark:bg-gray-800 rounded border border-slate-200 dark:border-gray-600">
                            <p class="text-sm text-slate-700 dark:text-gray-300 mb-1">Fișier actual:</p>
                            <p class="text-xs text-slate-600 dark:text-gray-400"><?php echo htmlspecialchars($membru['doc_ch']); ?></p>
                        </div>
                        <?php endif; ?>
                        <label for="doc_ch" class="block text-sm font-medium text-slate-800 dark:text-gray-200 mb-1">
                            Încarcă fișier nou
                        </label>
                        <input type="file" id="doc_ch" name="doc_ch" accept="image/*,.pdf" 
                               class="<?php echo $input_class; ?>">
                        <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">Maxim 5 MB (JPG, PNG, GIF, PDF)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($eroare)): ?>
        <div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded text-red-800 dark:text-red-200 text-sm" role="alert">
            <?php echo htmlspecialchars($eroare); ?>
        </div>
        <?php endif; ?>
        
        <div class="flex flex-wrap gap-3 justify-end pt-4 border-t border-slate-200 dark:border-gray-700">
            <button type="button" onclick="window.history.back()" 
                    class="px-4 py-2 border border-slate-300 dark:border-gray-600 text-slate-700 dark:text-gray-300 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                Anulează
            </button>
            <button type="submit"
                    id="btn-salveaza-datele"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <i data-lucide="save" class="inline-block mr-2 w-4 h-4" aria-hidden="true"></i>
                Salvează Modificări
            </button>
        </div>
    </form>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validare CNP
        var cnpInput = document.getElementById('cnp');
        var cnpEroare = document.getElementById('cnp-eroare');
        var form = document.getElementById('form-membru-profil');
        
        if (cnpInput && cnpEroare) {
            cnpInput.addEventListener('input', function() {
                var val = this.value.replace(/\D/g, '');
                this.value = val;
                if (val.length > 0 && val.length !== 13) {
                    cnpEroare.textContent = 'CNP-ul trebuie să conțină exact 13 cifre.';
                    cnpEroare.classList.remove('hidden');
                    cnpInput.setAttribute('aria-invalid', 'true');
                } else {
                    cnpEroare.classList.add('hidden');
                    cnpInput.setAttribute('aria-invalid', 'false');
                }
            });
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    var val = cnpInput.value.replace(/\D/g, '');
                    // Permite submit-ul chiar dacă CNP-ul nu are 13 cifre - validarea se face pe server
                    // Doar afișează un warning, dar nu previne submit-ul
                    if (val.length > 0 && val.length !== 13) {
                        // Nu previne submit-ul, doar afișează warning
                        cnpEroare.textContent = 'CNP-ul trebuie să conțină exact 13 cifre. Verificarea se va face pe server.';
                        cnpEroare.classList.remove('hidden');
                        cnpInput.setAttribute('aria-invalid', 'true');
                        // Nu previne submit-ul - lasă serverul să valideze
                    }
                    
                    // Debug: log în consolă ce se trimite
                    console.log('DEBUG: Formular trimis');
                    console.log('DEBUG: GDPR checkbox value:', document.getElementById('gdpr') ? document.getElementById('gdpr').checked : 'nu există');
                    console.log('DEBUG: Membru ID:', document.querySelector('input[name="membru_id"]') ? document.querySelector('input[name="membru_id"]').value : 'nu există');
                });
            }
        }
        
        // Validare dimensiune fișiere
        ['doc_ci', 'doc_ch'].forEach(function(fieldId) {
            var field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        if (this.files[0].size > 5 * 1024 * 1024) {
                            alert('Fișierul depășește 5 MB.');
                            this.value = '';
                        }
                    }
                });
            }
        });
    });
    </script>
    <?php
}
