# Audit Tehnic — ERP ANR Bihor

**Data auditului:** 2026-03-14
**Versiune platformă:** 2.0
**Fișiere analizate:** 185
**Funcții identificate:** ~200+
**Tabele DB:** ~40

---

## 1. Arhitectură generală

Aplicația este un CRM/ERP flat PHP fără framework:
- **Entrypoints** în root (God page pattern: POST + SQL + redirect + HTML în același fișier)
- **Helpers** în `includes/` (logică business, CRUD, ensure_tables)
- **Bootstrap:** `config.php` → PDO → session → auth_helper → csrf_helper → platform_helper → require_login()

### Dependențe externe (composer.json)
- phpoffice/phpword ^1.1
- phpmailer/phpmailer ^6.9
- mpdf/mpdf ^8.0
- setasign/fpdf ^1.8
- phpoffice/phpspreadsheet ^2.2

PSR-4 autoload declarat dar gol; proiectul folosește require/include manual.

---

## 2. Harta modulelor

| Modul | Entrypoints | Helper | Tabele DB |
|-------|-------------|--------|-----------|
| Auth | login, logout, recuperare-parola, reset-parola, schimba-parola | auth_helper | utilizatori, password_reset_tokens |
| Membri | membri, membru-profil, import-membri-csv, export_membri | membri_processing, membri_form, membri_alerts, excel_import, membri_import_helper, cnp_validator, file_helper | membri |
| Contacte | contacte, contacte-adauga, contacte-edit, contacte-import | contacte_helper | contacte |
| Incasări | incasari-salveaza, incasari-cauta-membri, incasari-chitanta-pdf/print, incasari-dashboard-salveaza | incasari_helper, cotizatii_helper | incasari, incasari_serii, incasari_setari, cotizatii_* |
| Registratură | registratura, registratura-adauga, registratura-edit, registratura-sumar | registratura_helper | registratura, setari |
| Notificări | notificari, notificare-view | notificari_helper | notificari, notificari_utilizatori |
| Newsletter | newsletter-view, cron_newsletter | newsletter_helper, mailer_functions | newsletter, settings_email |
| Activități | activitati, activitati-istoric | activitati_helper | activitati |
| Liste prezență | lista-prezenta-create/edit/print/pdf/docx | liste_helper | liste_prezenta |
| Documente | generare-documente, genereaza-document, descarca-document | document_helper | documente_template |
| Librărie doc | librarie-documente, descarca-librarie-document | librarie_documente_helper | librarie_documente |
| Taskuri | todo, todo-adauga, todo-edit | (inline) | taskuri |
| Voluntariat | voluntariat | voluntariat_helper | voluntari, voluntariat_activitati, voluntariat_participanti |
| BPA | ajutoare-bpa, bpa-tabel-docx/pdf/print | bpa_helper | bpa_gestiune, bpa_tabele_distributie, bpa_tabel_distributie_randuri |
| Administrativ | administrativ | administrativ_helper | administrativ_* (11 tabele) |
| Registru interacțiuni | registru-interactiuni-v2 | registru_interactiuni_v2_helper | registru_interactiuni_v2, registru_interactiuni_v2_subiecte |
| Setări | setari, setare-admin | platform_helper | setari, settings_email |
| Rapoarte | rapoarte | (inline) | agregate |

---

## 3. Duplicări de cod identificate

### 3.1 Funcția `calculeaza_varsta()` — 5 definiții

| Fișier | Linie | Return pe empty | Include liste_helper? |
|--------|-------|-----------------|----------------------|
| includes/liste_helper.php | 16 | `null` | N/A (canonical) |
| rapoarte.php | 105 | `null` | NU |
| membru-profil.php | 176 | `null` | NU |
| membri.php | 340 | `'-'` (string) | NU |
| aniversari.php | 20 | `'-'` (string) | NU (funcția se numește `calculeaza_varsta_aniversari`) |

**Observație:** Două variante returnează `'-'` pe input gol, trei returnează `null`. Diferența este funcțională (afectează afișarea în HTML).

### 3.2 Parsare dată multi-format — 5 implementări

| Fișier | Tip | Formate acceptate |
|--------|-----|-------------------|
| includes/membri_import_helper.php:139 | funcție (`membri_import_parse_date`) | d.m.Y, Y-m-d, d/m/Y, m/d/Y |
| includes/excel_import.php:224 | inline | d.m.Y, Y-m-d, d/m/Y |
| includes/contacte_helper.php:173 | inline (ternary chain) | d.m.Y, Y-m-d, d/m/Y |
| contacte-adauga.php:34 | inline | Y-m-d, d.m.Y (doar 2 formate!) |
| contacte-edit.php:58 | inline | Y-m-d, d.m.Y (doar 2 formate!) |

**Observație:** contacte-adauga.php și contacte-edit.php nu acceptă formatul d/m/Y, spre deosebire de restul.

### 3.3 Closure `$formatDate` duplicată în document_helper.php

Aceeași closure definită de 2 ori în același fișier (liniile ~97 și ~255).

---

## 4. Schema DB la runtime

Următoarele helpers fac CREATE TABLE IF NOT EXISTS / ALTER TABLE la fiecare request:

| Helper | CREATE TABLE | ALTER TABLE |
|--------|-------------|-------------|
| auth_helper | 2 tabele | 0 |
| administrativ_helper | 11 tabele | 7 coloane |
| bpa_helper | 3 tabele | 1 ENUM modify |
| contacte_helper | 1 tabel | 1 coloană |
| cotizatii_helper | 4 tabele | 1 coloană |
| incasari_helper | 3 tabele | 2 coloane + FK removal |
| librarie_documente_helper | 1 tabel | 1 coloană |
| mailer_functions | 1 tabel | 0 |
| newsletter_helper | 1 tabel | 0 |
| notificari_helper | 2 tabele | 0 |
| registratura_helper | 2 tabele | 8 coloane |
| registru_interactiuni_v2_helper | 2 tabele | 0 |
| voluntariat_helper | 3+ tabele | 0 |

**Total:** ~36 CREATE TABLE, ~21 ALTER TABLE executate potențial la fiecare page load.

---

## 5. Cross-module side effects

### Creare taskuri din alte module
- index.php → INSERT taskuri (din interacțiuni)
- registratura-adauga.php → INSERT taskuri (din documente)
- registratura-edit.php → INSERT/DELETE taskuri
- administrativ_helper → INSERT taskuri (din juridic)
- notificari_helper → INSERT taskuri (din notificări)

### Email sending — 4 implementări diferite
1. `auth_helper.php` → `@mail()` direct
2. `mailer_functions.php` → PHPMailer cu SMTP settings din DB
3. `notificari_helper.php` → `mail()` cu MIME multipart manual
4. `newsletter_helper.php` → `mail()` cu MIME multipart manual

---

## 6. Securitate — observații

### Pozitive
- PDO prepared statements consistent
- CSRF activ pe toate POST-urile (csrf_require_valid)
- password_hash(PASSWORD_DEFAULT) + password_verify
- hash_equals pentru token comparison (timing-safe)
- Redirect validation pe login (previne open redirect)

### De investigat
- Fișierele din `includes/` sunt accesibile direct prin HTTP (lipsă .htaccess)
- `logs/` accesibil direct prin HTTP
- Niciun rate limiting pe login/recuperare parolă
- `exec()` apelat în document_helper (LibreOffice conversion)
- Upload-urile validate doar prin MIME type (finfo_file)
- Fișierele config vechi (`config x.php`, `config xx.php`, `config redenumit.php`) prezente în root

---

## 7. Zone clasificate după risc de refactor

### Risc RIDICAT — nu atinge fără teste
- config.php (bootstrap central)
- auth_helper.php (sesiuni + autentificare)
- membri.php (cel mai complex God page)
- setari.php (8 tab-uri, atinge 5+ module)
- administrativ.php (8 tab-uri, 11 tabele)
- incasari_helper.php (FK migration, serie auto, API extern)
- Schema runtime ALTER TABLE (ordinea contează)

### Risc MEDIU
- Email sending (4 implementări, side effect extern)
- Cross-module task creation (5 puncte de INSERT taskuri)
- document_helper.php (exec, ZipArchive, mail merge)
- voluntariat_helper.php (lanț lung de dependențe)
- God pages cu POST (redirect + mesaje + CSRF)

### Risc SCĂZUT
- Pagini display-only (rapoarte, log-activitate, activitati-istoric)
- API endpoints JSON (api-cauta-membri, api-cauta-voluntari)
- cnp_validator.php (funcții pure)
- liste_helper.php (o funcție, pură)
- file_helper.php (izolat, filesystem)
- Form components (membri_form, contacte-form-fields)

---

## 8. Prioritizare refactor recomandat

1. **Consolidare `calculeaza_varsta()`** — elimină 4 definiții duplicate (atenție la diferența null vs '-')
2. **Extrage funcție comună de parsare dată** — elimină 4 blocuri inline duplicate
3. **Centralizare email sending** prin mailer_functions.php
4. **Separare POST/HTML** în God pages (gradual, câte o pagină)
5. **Centralizare ensure_tables** într-un singur punct de apel
6. **Curățenie fișiere** — config vechi, fișiere dev/test
