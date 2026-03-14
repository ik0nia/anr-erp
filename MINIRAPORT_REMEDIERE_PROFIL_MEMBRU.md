# Miniraport: Remediere salvare profil membru

## 1. Rezumat

**Problema raportată:** Modificările din formularul de profil al beneficiarilor (membri) nu se salvează.

**Cauze identificate (potențiale):**
- **JS:** `showFormLoading()` și listener-ele din `form-validation.js` / `form-ux-enhancements.js` aplicau efecte pe formularul de profil (loading pe buton, tracking modificări), cu risc de efecte secundare pe submit.
- **Redirect:** Dacă output era trimis înainte de `header('Location: ...')`, redirect-ul eșua și utilizatorul rămânea pe pagină fără feedback clar.
- **Config:** Posibilă nepotrivire `DB_NAME` între config și baza reală (ex. `crm-anr-bihorxampp` vs `crm-anr-bihor`).

**Măsuri aplicate:** Izolare completă a formularului `#form-membru-profil` față de scripturile globale (fără showFormLoading, fără tracking), hardening redirect cu fallback garantat și log diagnostic, documentare DB_NAME și cache-busting actualizat.

---

## 2. Modificări făcute

| Fișier | Modificare |
|--------|------------|
| **config.php** | Comentariu lângă `DB_NAME`: asigurați-vă că corespunde bazei din mediu. |
| **js/form-validation.js** | În listener-ul de submit: dacă `form.id === 'form-membru-profil'`, nu se apelează `showFormLoading(this)` și se permite submit-ul normal. Același skip în `initLoadingIndicators()`. |
| **js/form-ux-enhancements.js** | În `initFormChangeTracking()`: pentru formularul cu `id === 'form-membru-profil'` nu se adaugă listener-e de input/change (nici tracking). |
| **membru-profil.php** | La succes, după `headers_sent()`: log în `logs/debug-profil.txt` cu mesajul „SUCCESS dar redirect eșuat – headers_sent”; fallback cu meta refresh + `location.replace` deja existent, păstrat. |
| **header.php** | Cache-busting actualizat: `form-validation.js?v=7`, `form-ux-enhancements.js?v=3`. |

---

## 3. Verificări

- **CSRF:** Token și sesiunea rămân conform cu `includes/csrf_helper.php`; `csrf_require_valid()` este apelat la POST în `membru-profil.php`.
- **POST:** Request-ul trebuie să conțină `_csrf_token`, `actualizeaza_membru`, `membru_id`, `nume`, `prenume`; procesarea se face în `membri_processing.php` → `proceseaza_formular_membru()` → UPDATE pe `membri`.
- **Formular:** `membru-profil-form.php` – form `id="form-membru-profil"`, `method="post"`, `enctype="multipart/form-data"`, conține `csrf_field()`, hidden `membru_id`, `actualizeaza_membru` și câmpurile vizibile.

---

## 4. Rezultat așteptat

- La „Salvează modificările” pe profil: fie **redirect** cu `?succes=1` și mesaj de succes, fie **afișare clară** a erorii de validare/server (banner + bloc în conținut).
- Modificările salvate sunt **vizibile după reîncărcare** (sau după redirect).
- Dacă redirect-ul eșuează din cauza output-ului înainte de header, în `logs/debug-profil.txt` apare linia „SUCCESS dar redirect eșuat – headers_sent”, iar utilizatorul primește pagina minimă cu meta refresh / `location.replace`.

---

## 5. Recomandări

- Păstrare validării strict **doar opt-in** (`data-validate="strict"`); formularul de profil nu folosește acest atribut.
- **Nefolosire** `showFormLoading` pe formulare cu multe câmpuri hidden/critice; formularul de profil este exclus explicit.
- Verificare **periodică** a numelui bazei de date în `config.php` față de mediul real (development vs production).
- În toate mediile, asigurați-vă că se servesc **același cod** și că cache-ul browser este invalidat (query `?v=7` / `?v=3` pe scripturi).

---

## 6. Test E2E (manual)

1. Autentificare în CRM (dacă e cazul).
2. Deschide un profil membru (ex. **Membri** → click pe un membru).
3. Modifică **Nume** sau **Prenume** în formular.
4. Apasă **Salvează modificările**.
5. **Verifică:** fie redirect cu mesaj „Datele membrului au fost actualizate cu succes”, fie mesaj clar de eroare în banner / în conținut.
6. Reîncarcă pagina sau navighează din nou la profil și confirmă că modificările **persistă**.

Dacă redirect-ul nu se face din cauza output-ului înainte de header, verifică `logs/debug-profil.txt` pentru linia „SUCCESS dar redirect eșuat – headers_sent”.

---

*Document generat în urma implementării planului de remediere (variante A+B).*
