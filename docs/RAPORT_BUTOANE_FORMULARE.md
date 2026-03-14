# Raport: Butoane și formulare care execută acțiuni pe date

**Data:** 2026-02  
**Context:** Butoanele care execută acțiuni pe date (salvare task, interacțiune, profil membru etc.) păreau „compromise”: mesaje incorecte, date necontorizate, salvare care nu se aplică. Butoanele cu linkuri sau deschidere formulare funcționau corect.

---

## 1. Analiza situației

### 1.1 Ce funcționează vs ce nu

| Tip acțiune | Comportament observat |
|-------------|------------------------|
| Linkuri (navigare) | OK |
| Deschidere formulare / modale | OK |
| **Submit formular POST** (salvare task, interacțiune, profil, adăugare task) | Blocat sau mesaj greșit / redirect eșuat |

### 1.2 Modificări din istoric care influențează fluxul

1. **`js/form-validation.js`**  
   - Se atașează la **toate** formularele `form[method="post"]`.  
   - La submit se apela `validateForm(form)`. Dacă returna `false`, se apela `e.preventDefault()` și **submit-ul era blocat**.  
   - Erau excluse doar anumite formulare (profil membru, finalizare task etc.) prin **listă neagră** (blacklist).  
   - **Consecință:** Orice formular POST care nu era în lista de excludere trecea prin validare strictă (CNP, email, telefon ≥10 cifre, câmpuri required). Dacă un singur câmp eșua (ex: telefon opțional cu <10 cifre), **întregul formular era blocat** și datele nu ajungeau la server.

2. **`js/form-ux-enhancements.js`**  
   - `beforeunload`: afișa „Leave site? Changes that you made may not be saved” când exista orice formular marcat „modificat”.  
   - La bifarea unui task, un alt formular (ex. Registru Interacțiuni) putea rămâne marcat modificat → la submit se afișa dialogul → utilizatorul apăsa Cancel → navigarea (și salvarea) era anulată.  
   - **Remediu deja aplicat:** La submit se setează `window.__formSubmitting` și în `beforeunload` nu se mai afișează dialogul când acest flag e setat.

3. **Redirect după POST**  
   - Unele pagini nu aveau `ob_start()` sau curățare buffer înainte de `header('Location: ...')`.  
   - Dacă se trimitea orice output înainte (inclusiv BOM, spațiu), `header()` eșua și răspunsul era 200 cu HTML în loc de 302 → utilizatorul rămânea pe aceeași pagină, cu impresia că „nu s-a salvat”, deși uneori salvarea se făcuse în DB.

---

## 2. Cauza principală

**Validarea client-side din `form-validation.js` era aplicată în mod implicit la toate formularele POST (abordare blacklist).**

- Formularele **neexcluse** treceau prin:  
  - câmpuri `required` goale  
  - CNP (dacă există câmp `cnp`)  
  - email (orice `input[type="email"]` cu valoare invalidă)  
  - **telefon:** orice `input[type="tel"]` cu valoare dar cu mai puțin de 10 cifre → `validateForm` returna `false` → `preventDefault()` → **formularul nu era trimis**.

Exemple direct afectate:

- **Registru Interacțiuni (dashboard):** are câmp `input type="tel"` (opțional). Dacă utilizatorul introducea un număr scurt sau parțial, validarea îl respingea și **întregul formular** era blocat → interacțiunea nu se salva și indicatorul nu se actualiza.
- **Adăugare task:** formularul nu avea tel/email, dar orice alt câmp care cădea în regulile stricte (sau viitoare extensii) putea bloca submit-ul.
- **Profil membru:** a fost exclus ulterior; inițial și acesta era blocat de validarea CNP/email/tel.

Practic, **butoanele nu erau defecte**, ci **submit-ul era oprit în JavaScript** înainte de a ajunge la server.

---

## 3. Soluții implementate

### 3.1 Validare: trecere de la blacklist la whitelist (opt-in)

**Fișier:** `js/form-validation.js`

- **Înainte:** Toate formularele POST erau validate; se excludeau doar anumite formulare (profil membru, finalizare task etc.).
- **Acum:** Validarea **blocantă** (cu `preventDefault()`) se aplică **doar** formularelor care au atributul **`data-validate="strict"`**.
- Formularele **fără** acest atribut se trimit **mereu** la server; validarea se face pe server (PHP).

**Beneficii:**

- Niciun formular nou sau existent nu mai este blocat accidental.  
- Butoanele de salvare (task, interacțiuni, setări, contacte etc.) funcționează din nou.  
- Pentru formulare unde vrei validare client strictă (ex. înregistrare, formular cu CNP/email/tel obligatorii), adaugi în HTML: `data-validate="strict"`.

**Versiune script:** `form-validation.js?v=5` (în `header.php`) pentru reîncărcare forțată.

### 3.2 Redirect după POST

- **index.php:**  
  - `ob_start()` la început.  
  - După **finalizare task** și după **adaugare interacțiune**: curățare buffer (`ob_end_clean`), `header('Location: ...')`, iar dacă `headers_sent()` este true, se afișează o pagină minimă cu meta refresh + `location.replace()`.
- **todo-adauga.php:**  
  - `ob_start()` la început.  
  - După salvare cu succes: aceeași logică de curățare buffer + redirect + fallback meta refresh/JS.
- **membru-profil.php:**  
  - Deja avea `ob_start()` și fallback redirect.

Astfel, după salvare, pagina face redirect corect (302 sau fallback) și indicatorii/lista se reîncarcă cu datele noi.

### 3.3 Beforeunload (deja aplicat)

- În `form-ux-enhancements.js`, la submit se setează `window.__formSubmitting = true`.  
- În `beforeunload`, dacă `window.__formSubmitting` este setat, nu se mai afișează „Leave site?”.  
- Evită anularea accidentală a navigării (și a salvării) când utilizatorul confirmă un task sau salvează o interacțiune.

---

## 4. Verificări făcute

- **Conexiune la baza de date:** Folosirea `$pdo` din `config.php` și interogările din `index.php`, `todo.php`, `todo-adauga.php` sunt corecte; nu s-a identificat problemă la nivel de conexiune sau SQL pentru acțiunile descrise.
- **CSRF:** Formularele POST incluse în analiză folosesc `csrf_field()` și pe server se apelează `csrf_require_valid()`. Comportamentul este corect; blocajul era la client (validare JS), nu la CSRF.

---

## 5. Recomandări pentru viitor

1. **Validare client:**  
   - Păstrați logica **opt-in**: doar formularele cu `data-validate="strict"` să fie validate cu `preventDefault()` în `form-validation.js`.  
   - Pentru orice formular nou care trebuie validat strict (ex. înregistrare, formular cu CNP/email obligatorii), adăugați în form: `data-validate="strict"`.

2. **Pagini noi cu POST + redirect:**  
   - La începutul scriptului: `ob_start()`.  
   - Înainte de `header('Location: ...')`: curățare buffer; dacă `headers_sent()` este true, folosiți același tip de fallback (meta refresh + `location.replace()`).

3. **Testare:**  
   - După orice modificare în `form-validation.js` sau în `form-ux-enhancements.js`, testați: adăugare task, finalizare task, adăugare interacțiune, salvare profil membru, și verificarea indicatorilor / listelor după redirect.

---

## 6. Rezumat

| Problemă raportată | Cauză | Soluție |
|-------------------|--------|---------|
| Task finalizat dar mesaj „nu s-a salvat”, apoi taskul dispare | Redirect 302 + mesaj de succes vs. dialog beforeunload / mesaj vechi | Fallback redirect + `__formSubmitting` la submit |
| Interacțiune adăugată dar nu se contorizează pe indicator | Formular blocat de validare (ex. tel cu <10 cifre) → POST nu pleca | Validare doar opt-in (`data-validate="strict"`) |
| Adăugare task, Salvare, dar nu se salvează | Același blocaj la submit + eventual redirect eșuat | Opt-in validation + `ob_start` și redirect fallback în `todo-adauga.php` |
| „Toate butoanele care executau mișcări de date compromise” | Un singur script (`form-validation.js`) bloca submit-ul la majoritatea formularelor POST | Trecere la whitelist: validare blocantă doar pentru formulare cu `data-validate="strict"` |

**Concluzie:** Comportamentul butoanelor este corect la nivel de HTML/PHP; problema era la **validarea JavaScript** aplicată implicit la toate formularele POST și la **redirect/feedback** după POST. După trecerea la validare opt-in și la îmbunătățirea redirect-urilor, toate acțiunile de salvare (task, interacțiuni, profil etc.) ar trebui să se execute și să se reflecte corect în interfață și în indicatori.
