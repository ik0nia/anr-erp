# Migrare platformă CRM ANR Bihor: de pe XAMPP pe hosting

Ghid pas cu pas pentru mutarea platformei de pe calculator (XAMPP) pe un server de hosting, **fără** migrare de date din baza de date (instalare curată pe server).

---

## Pas 1: Pregătire pe calculator

1. Asigură-te că proiectul rulează corect local (XAMPP).
2. **Nu** includeți fișierul `config.php` în arhiva pentru upload (sau ștergeți-l din arhivă înainte de upload), **sau** pregătiți-vă să **nu suprascrieți** `config.php` pe server după ce a rulat instalarea.
3. Opțional: păstrați o copie a `config.hosting.example.php` ca referință pentru valorile DB_* pe server.

---

## Pas 2: Creare bază de date pe hosting

1. Conectați-vă la **cPanel** (sau panoul oferit de hosting).
2. Deschideți **MySQL® Databases** (sau echivalent).
3. **Creați o bază de date** (ex: `r74526anrb_internapp_crm`).
4. **Creați un utilizator MySQL** (ex: `r74526anrb_internapp_usr`) cu parolă puternică.
5. **Asociați utilizatorul la baza de date** și acordați toate privilegiile (ALL PRIVILEGES) pe acea bază.
6. Notați: **host** (de obicei `localhost`), **numele bazei**, **utilizatorul**, **parola**.

---

## Pas 3: Upload fișiere pe server

1. Încărcați **toate** fișierele proiectului (PHP, CSS, JS, foldere `includes/`, `install/`, `js/`, `css/`, fișierele `schema_*.sql`, `setup.php`, `composer.json`, `package.json` etc.) în folderul destinat (ex: `public_html/internapp/crm-anr-bihor/` sau calea indicată de hosting).
2. **Excludeți** din upload (sau nu suprascrieți ulterior):
   - fișierul **`config.php`** de pe PC (conține `root` și parolă goală pentru XAMPP). Pe server, `config.php` va fi generat de installer la **Pasul 8**.
3. Dacă ați uploadat deja `config.php` de pe PC, **ștergeți-l** pe server sau **editați-l** imediat după upload (vezi Pas 7) înainte de a accesa platforma.
4. Asigurați-vă că folderul **`uploads/`** există și are permisiuni de scriere (ex: 755 sau 775).

---

## Pas 4: Rulare installer pe server

1. Deschideți în browser: **`https://domeniul-tau.ro/cale/crm-anr-bihor/setup.php`**  
   (sau `.../install/` dacă hosting-ul nu redirecționează).
2. **Pasul 1** – Verificare cerințe: PHP 7.4+, extensii (pdo, pdo_mysql, mbstring, gd, zip, curl), folder `uploads/` scriibil.
3. **Pasul 2** – Configurare baza de date:
   - Host: `localhost` (sau valorile din cPanel)
   - Nume bază: ex. `r74526anrb_internapp_crm`
   - Utilizator: ex. `r74526anrb_internapp_usr`
   - Parolă: parola MySQL aleasă la Pas 2 din acest ghid
4. **Pasul 3** – Creare tabele: se rulează toate fișierele `schema_*.sql`. Dacă apar erori, verificați că toate fișierele `schema_*.sql` sunt prezente în root-ul proiectului pe server.
5. **Pasul 4** – Creare utilizator administrator: introduceți nume utilizator, parolă, email (ex: Administrator, parola dorită, merca.bhanvr@gmail.com).
6. **Pasul 5** – Configurare platformă: URL bază (ex. `https://domeniul.ro/internapp/crm-anr-bihor`) și nume platformă.
7. **Pasul 6** – Composer (opțional): dacă nu rulează, puteți rula manual `composer install` prin SSH.
8. **Pasul 7** – Build CSS (opțional): dacă nu rulează, puteți rula manual `npm run build:css`.
9. **Pasul 8** – Finalizare: installer-ul **generează** fișierul **`config.php`** pe server cu **DB_HOST, DB_NAME, DB_USER, DB_PASS** introduse la Pasul 2. **Acest fișier trebuie să rămână pe server.**

---

## Pas 5: După instalare – NU suprascrieți config.php

1. După ce instalarea s-a finalizat cu succes, **config.php** de pe server conține deja datele corecte pentru baza de date de pe hosting.
2. **Nu** reîncărcați proiectul de pe PC peste server (FTP/git) **fără** să excludeți **config.php**. Dacă suprascrieți cu fișierul de pe PC (root + fără parolă), veți primi:  
   **« Access denied for user 'root'@'localhost' (using password: NO) »**.
3. Dacă faceți update la cod: încărcați doar fișierele modificate și **nu** înlocuiți **config.php** cu cel de pe PC.

---

## Pas 6: Securitate după instalare

1. Ștergeți sau redenumiți **`setup.php`** din root.
2. Ștergeți sau redenumiți folderul **`install/`**.
3. Opțional: restricționați accesul la `config.php` prin `.htaccess` (nu permiteți acces direct din browser).

---

## Pas 7: Dacă apar « Access denied » / « root (using password: NO) »

Înseamnă că aplicația citește un **config.php** cu user `root` și fără parolă (config de pe XAMPP).

**Soluție:**

1. Pe server, deschideți **config.php** (FTP / cPanel File Manager / SSH).
2. Înlocuiți **doar** cele 4 linii de conexiune cu valorile de la **Pasul 2** ale installer-ului:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'r74526anrb_internapp_crm');   // numele bazei de pe hosting
   define('DB_USER', 'r74526anrb_internapp_usr');   // utilizatorul MySQL de pe hosting
   define('DB_PASS', 'PAROLA_TA');                  // parola MySQL
   ```

3. Salvați fișierul pe server și reîncărcați platforma în browser.

**Notă:** Toate scripturile încarcă acum config-ul cu `require_once __DIR__ . '/config.php';`, deci se folosește întotdeauna **config.php din același folder** cu scriptul (folderul platformei pe server). Dacă tot apare eroarea, verificați că ați editat **config.php** din folderul unde se află **index.php** (root-ul platformei pe server).

---

## Rezumat ordine operații

| Ordine | Acțiune |
|--------|--------|
| 1 | Creați baza de date și utilizatorul MySQL pe hosting. |
| 2 | Încărcați fișierele proiectului **fără** să suprascrieți config.php după instalare (sau nu uploadați config.php de pe PC). |
| 3 | Rulați **setup.php** și parcurgeți pașii 1–8. |
| 4 | La Pasul 8 se generează **config.php** cu datele corecte pe server. |
| 5 | Nu reîncărcați **config.php** de pe PC peste server. |
| 6 | Ștergeți **setup.php** și folderul **install/** după instalare. |
| 7 | Dacă tot apare eroare de conexiune, editați manual pe server **config.php** (liniile DB_*). |

Dacă respectați acești pași, platforma va folosi întotdeauna datele de conexiune de pe server și nu va mai încărca config-ul cu `root` / fără parolă de pe XAMPP.
