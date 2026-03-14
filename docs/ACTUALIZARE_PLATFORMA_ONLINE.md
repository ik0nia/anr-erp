# Actualizare platformă CRM ANR Bihor – online (host)

Platforma este configurată să folosească baza de date de pe host: **`r74526anrb_internapp_crm`**.

## Pași pentru actualizarea platformei online

### 1. Pregătire pe calculator
- Asigură-te că ai toate modificările salvate în proiect.
- Opțional: creează un backup al bazei de date de pe host (cPanel → phpMyAdmin → Export pentru `r74526anrb_internapp_crm`).

### 2. Încărcare fișiere pe server (FTP / cPanel File Manager)
- Conectează-te la hosting prin FTP (FileZilla, etc.) sau deschide **cPanel → File Manager**.
- Mergi în directorul unde este instalată aplicația CRM (ex: `public_html/crm-anr-bihor` sau `public_html/internapp_crm`).
- Încarcă (suprascrie) fișierele modificate:
  - `config.php` – **atenție**: pe server trebuie să aibă **parola corectă** pentru MySQL (vezi pasul 3).
  - Toate fișierele PHP modificate (membri.php, index.php, todo.php, log-activitate.php, etc.).
  - Fișierele `schema.sql`, `schema_update.sql`, `schema_update_simplu.sql` (pentru referință; le rulezi doar dacă e nevoie).

### 3. Configurare config.php pe server
- Pe server, deschide `config.php` în File Manager (Edit) sau descarcă-l, editează, apoi încarcă-l din nou.
- Verifică / completează:
  - `DB_HOST` = `localhost`
  - `DB_NAME` = `r74526anrb_internapp_crm`
  - `DB_USER` = `r74526anrb_internapp_usr` (sau utilizatorul MySQL din cPanel)
  - `DB_PASS` = parola utilizatorului MySQL (din cPanel → MySQL® Databases).
- Salvează fișierul.

### 4. Baza de date pe host
- Deschide **cPanel → phpMyAdmin** (sau MySQL Databases).
- Selectează baza de date **`r74526anrb_internapp_crm`**.
- Dacă baza sau tabelele nu există:
  - Tab **Import** sau **SQL**: rulează `schema.sql` (creare inițială).
- Dacă tabelul `membri` există dar lipsesc coloane (ex. `status_dosar`):
  - Tab **SQL**: copiază și rulează conținutul din `schema_update_simplu.sql` sau `schema_update.sql` (toate folosesc deja `USE r74526anrb_internapp_crm`).

### 5. Verificare după actualizare
- Deschide în browser URL-ul platformei (ex: `https://domeniul.ro/crm-anr-bihor/` sau calea unde e instalată).
- Testează:
  - Login
  - **Management Membri** – dacă apare eroare legată de tabel/coloană, rulează scripturile SQL din pasul 4.
  - Alte module (Rapoarte, ToDo, Log activitate, etc.).
- Mesajele de eroare din aplicație indică acum baza de date folosită (`r74526anrb_internapp_crm`) și nu mai conțin link către localhost/phpMyAdmin.

### 6. Mediu local (XAMPP) – opțional
- Pentru lucru pe calculator cu XAMPP, creează în rădăcina proiectului fișierul **`config.local.php`** (nu se încarcă pe server).
- Conținut exemplu:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'crm-anr-bihorxampp');
define('DB_USER', 'root');
define('DB_PASS', '');
```
- Atunci când `config.local.php` există, aceste valori au prioritate; pe server nu pui acest fișier, astfel că se folosește baza `r74526anrb_internapp_crm` din `config.php`.

---

**Rezumat:**  
Actualizezi fișierele pe server, verifici/completezi parola în `config.php`, rulezi SQL doar dacă lipsesc tabele/coloane, apoi verifici în browser că totul funcționează.
