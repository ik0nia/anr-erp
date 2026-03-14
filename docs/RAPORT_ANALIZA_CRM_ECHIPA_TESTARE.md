# Raport de analiză CRM – Echipa Seniori Testeri (Platforme Web CRM)

**Client:** Asociația Nevăzătorilor Bihor  
**Platformă:** CRM ANR Bihor (web)  
**Data raportului:** 7 februarie 2026  
**Scop:** Analiză din perspectiva unei echipe de seniori testeri specializați în platforme web CRM și propuneri de module noi pentru creșterea utilității în cadrul asociației.

---

## 1. Definirea echipei de seniori testeri

Echipa este definită ca un grup de **seniori testeri specializați în platforme web de tip CRM**, cu responsabilități clare:

| Rol | Responsabilități |
|-----|------------------|
| **Testare funcțională** | Verificare fluxuri business (membri, contacte, documente, registratură, activități, rapoarte). |
| **Testare de accesibilitate (A11y)** | Conformitate WCAG 2.1 AA, testare cu screen readers (NVDA, JAWS, VoiceOver), navigare tastatură, contrast. |
| **Testare de securitate** | CSRF, validare input, protecție SQL injection, gestionare sesiuni și token-uri. |
| **Testare UX / compatibilitate** | Experiență pe dispozitive mobile, dark/light theme, formulare și mesaje de eroare. |
| **Testare performanță și integritate date** | Timp răspuns, volum date, backup/restore, consistență FK și date. |

**Deliverables:** planuri de testare, rapoarte de defecte, checklist-uri de release, recomandări de îmbunătățire și validare a noilor module.

---

## 2. Rezumatul analizei platformei

### 2.1 Stack tehnologic

- **Backend:** PHP (procedural + PDO), MySQL/MariaDB  
- **Frontend:** HTML5, Tailwind CSS, Lucide icons, JavaScript (modular: accesibilitate, form-ux, validare, theme-toggle, mobile-navigation)  
- **Autentificare:** sesiuni PHP, `utilizatori` + `password_reset_tokens`, roluri administrator / operator  
- **Comunicare:** email (PHPMailer / funcții custom), cron pentru newsletter  
- **Documente:** PhpWord (DOCX), mPDF (PDF), template-uri .docx, librărie documente, generare/descărcare cu token  

Platforma **nu folosește Next.js** în acest proiect; este o aplicație PHP clasică cu layout-uri incluse (`header.php`, `sidebar.php`, `footer.php`).

### 2.2 Module existente (inventar)

| Modul | Descriere scurtă | Fișiere principale |
|-------|------------------|---------------------|
| **Dashboard** | Pagina principală: taskuri active, interacțiuni rapide, căutare membru, librărie, aniversări zilei | `index.php` |
| **Membri** | CRUD membri, profil membru, avertizări GDPR/CI/certificat, export, import Excel | `membri.php`, `membru-profil.php`, `membri_form.php`, `membri_processing.php`, `export_membri.php`, `contacte-import.php` (Excel) |
| **Contacte** | CRUD contacte, tipuri (Partener, Furnizor, Beneficiar etc.), import | `contacte.php`, `contacte-adauga.php`, `contacte-edit.php`, `contacte-import.php` |
| **Activități** | Planificare activități, status, legătură cu liste prezență | `activitati.php`, `activitati-istoric.php` |
| **Liste prezență** | Creare/editare liste, legare membri, PDF/print | `lista-prezenta-create.php`, `lista-prezenta-edit.php`, `lista-prezenta-pdf.php`, `lista-prezenta-print.php` |
| **Registratura** | Înregistrare documente (nr., tip, detalii, opțional membru, fișier) | `registratura.php`, `registratura-adauga.php`, `registratura-edit.php`, `registratura-sumar.php` |
| **Registru interacțiuni** | Apeluri și vizite, subiecte, opțiune creare task ToDo | `registru-interactiuni-v2.php`, helper v2, `api-registru-v2-stats.php` |
| **ToDo** | Taskuri cu dată, urgență, finalizare, filtrare pe utilizator | `todo.php`, `todo-adauga.php`, `todo-edit.php` |
| **Generare documente** | Template-uri DOCX, generare pe membru, descărcare/trimite email cu token | `generare-documente.php`, `genereaza-document.php`, `descarca-document.php`, `trimite-email-document.php` |
| **Librărie documente** | Documente generale (instituție, nume), descărcare/print | `librarie-documente.php`, `descarca-librarie-document.php` |
| **Rapoarte** | Statistici membri (status, grad handicap, sex), rapoarte newsletter, interacțiuni | `rapoarte.php` |
| **Notificări** | Notificări interne (titlu, importanță, atașament), opțiune trimitere email | `notificari.php`, `notificare-view.php`, `descarca-notificare-atasament.php` |
| **Aniversări** | Aniversări zilei (membri + contacte), notificare zilnică (cron) | `aniversari.php`, `aniversari-notificare-zilnica.php` |
| **Newsletter** | Trimitere email pe categorii contacte, draft/trimis/programat, atașament, cron | `newsletter-view.php`, `cron_newsletter.php`, `includes/newsletter_helper.php` |
| **Setări** | Nume platformă, logo, utilizatori (admin), subiecte interacțiuni, import Excel, email | `setari.php` |
| **Autentificare** | Login, logout, recuperare/reset parolă, schimbare parolă, instalare auth | `login.php`, `logout.php`, `recuperare-parola.php`, `reset-parola.php`, `schimba-parola.php`, `install-auth.php`, `setare-admin.php` |
| **Log activitate** | Audit trail acțiuni | `log-activitate.php`, `log-print-document.php` |
| **Backup** | Backup bază de date (script) | `backup_database.php` |

### 2.3 Baza de date (rezumat)

- **Schema principală:** `membri`, `log_activitate`, `taskuri`, `activitati`, `liste_prezenta`, `liste_prezenta_membri`, `documente_template`, `registratura`, `registru_interactiuni` (legacy), `registru_interactiuni_subiecte`.
- **Schema extinsă (fișiere separate):** utilizatori, password_reset_tokens, registru_interactiuni_v2 (+ subiecte), contacte, newsletter, notificari, setari, librarie documente etc.
- **Documentație:** `DOCUMENTATIE_SCHEMA.md`, `schema.sql` și multiple `schema_*.sql` pentru migrări.

---

## 3. Evaluare calitate (perspectiva echipei de testare)

### 3.1 Puncte forte

- **Securitate:** Parole cu `password_hash`/`password_verify`, prepared statements în majoritatea interogărilor, protecție CSRF implementată la formulare POST, escape output cu `htmlspecialchars`.
- **Accesibilitate:** Rapoarte dedicate (MINIRAPORT_ACCESIBILITATE, RAPORT_ACCESIBILITATE_SCREEN_READERS); skip links, aria-label pe butoane/selecturi, focus în modals, tabele cu scope/aria-sort; declarată conformitate WCAG 2.1 AA.
- **Audit trail:** Log activitate pentru acțiuni importante; log pentru print documente.
- **Integrări interne:** Activități ↔ liste prezență, registru interacțiuni ↔ taskuri ToDo, aniversări (membri + contacte).
- **Validare:** CNP cu cifră de control, validare email, tip MIME pentru upload-uri (file_helper).
- **UX:** Tema dark/light, layout responsive, mesaje de succes/eroare, formulare cu validare.

### 3.2 Risc / îmbunătățiri (aliniate cu AUDIT_CRM_2026_ACTUALIZAT)

- **Foreign keys:** Unele relații (liste_prezenta_membri, activitati.lista_prezenta_id, etc.) lipsesc din schema principală; există `fix_critical_issues.sql` / `fix_foreign_keys.php`. Recomandare: aplicare și menținere consistență referențială.
- **Gestionare erori:** Unificare mesaje utilizator (generice) și logging detaliat în toate modulele.
- **Import:** Doar membri/contacte; extindere la alte entități (ex. activități) dacă este nevoie.
- **Backup:** Script existent; recomandare: cron și documentație pentru restore.

Echipa de testare recomandă: rezolvarea FK, un checklist de release (sec + a11y + smoke) și teste automate (ex. PHPUnit pentru helperi critici, teste E2E pentru fluxuri principale).

---

## 4. Propuneri de module noi pentru creșterea utilității

Următoarele propuneri sunt prioritizate pentru **utilitate în cadrul asociației** (membri, activități, parteneri, transparență, raportare).

### 4.1 Modul **Evenimente și calendar**

- **Scop:** Un singur calendar vizual (lună/săptămână) pentru activități și evenimente, cu posibilitate de evenimente recurente și reminder-uri.
- **Utilitate:** Oferă o vedere unificată a întâlnirilor, sesiunilor și evenimentelor; reduce dublarea și uitarile.
- **Sugestie implementare:** Tabel `evenimente` (sau extindere `activitati`) cu tip (activitate / eveniment intern / eveniment public), recurență, reminder; interfață calendar (HTML+JS sau integrare ușoară cu librărie calendar); notificări opționale (email/notificare internă).

### 4.2 Modul **Donații și sponsori**

- **Scop:** Înregistrarea donațiilor (numerar, obiecte, servicii) și a sponsorilor (companii/individi), cu sume, date și documente justificative.
- **Utilitate:** Transparență și raportare pentru conducere și parteneri; suport pentru rapoarte anuale și cereri de finanțare.
- **Sugestie implementare:** Tabele `donatii` (sumă, tip, donator_id, data, document_path, notă) și `sponsori`/`donatori` (nume, contact, tip); raport „Donații pe perioadă” și export (Excel/PDF).

### 4.3 Modul **Voluntari**

- **Scop:** Bază de date voluntari (nume, contact, competențe, disponibilitate, ore realizate), legături la activități.
- **Utilitate:** Gestionează resurse umane voluntare și evidențiaza contribuția lor la activități.
- **Sugestie implementare:** Tabel `voluntari` (posibil legat de `contacte` prin tip sau tabel separat); câmpuri competențe, disponibilitate; legare la `activitati` (ex. `activitati_voluntari`); raport ore pe voluntar/perioadă.

### 4.4 Modul **Inventar / resurse**

- **Scop:** Inventarul de echipamente, materiale sau resurse (ex. echipamente pentru nevăzători, mobilier, IT).
- **Utilitate:** Evidență clară a resurselor asociației și alocarea lor la activități sau sedii.
- **Sugestie implementare:** Tabel `resurse` sau `inventar` (denumire, categorie, cantitate, stare, locație, notă); eventual împrumuturi legate de activități sau membri (tabel `imprumuturi`).

### 4.5 Modul **Cereri și programe (beneficii)**

- **Scop:** Înregistrarea cererilor membrilor pentru programe, beneficii sau servicii (ex. cerere de ajutor, program de transport).
- **Utilitate:** Urmărire status cereri și raportare pentru proiecte și parteneri.
- **Sugestie implementare:** Tabel `cereri` (membru_id, tip_cerere, data, status, notă, document); workflow simplu (Deschis / În lucru / Rezolvat / Respins); raport pe tip și status.

### 4.6 Rapoarte extinse și tablou de bord

- **Scop:** Pagină dedicată „Rapoarte” cu grafice (membri în timp, activități pe lună, interacțiuni, donații) și filtre (perioadă, status).
- **Utilitate:** Decizii bazate pe date pentru conducere și raportare externă.
- **Sugestie implementare:** Extindere `rapoarte.php` cu grafice (Chart.js sau similar), export Excel/PDF pentru toate rapoartele; KPI-uri pe dashboard (număr membri activi, evenimente luna curentă, donații an).

### 4.7 Comunicări și istoric (per membru/contact)

- **Scop:** Istoric centralizat de comunicări (apeluri, emailuri trimise, notificări) pe fiecare membru sau contact.
- **Utilitate:** Context complet la un click pentru fiecare persoană; mai puține informații pierdute între operatori.
- **Sugestie implementare:** Vizualizare pe profil membru/contact: interacțiuni v2 (filtrate după persoană), newsletter/notificări trimise (dacă sunt legate de email), eventual jurnal scurt „comunicări” (apel, email, notă).

### 4.8 Module opționale (prioritate secundară)

- **Transport / program:** Program de transport pentru membri (zile, rute, șoferi) – dacă este nevoie operațională.
- **Formulare online (cereri/înscrieri):** Formulare publice (cu link) care alimentează cereri sau contacte noi – util pentru evenimente și parteneri.
- **Integrări:** Email (deja parțial), SMS sau calendar extern – după nevoi și buget.

---

## 5. Priorizare recomandată

| Prioritate | Modul | Motiv |
|------------|--------|-------|
| **Înaltă** | Evenimente și calendar | Central pentru organizare; completează activitățile existente. |
| **Înaltă** | Rapoarte extinse și tablou de bord | Necesar pentru raportare și transparență. |
| **Medie** | Donații și sponsori | Important pentru transparență și raportare către parteneri. |
| **Medie** | Cereri și programe (beneficii) | Traceabilitate cereri membri. |
| **Medie** | Comunicări și istoric (per membru/contact) | Îmbunătățește experiența operatorilor. |
| **Scăzută** | Voluntari | Util dacă asociația lucrează cu voluntari organizați. |
| **Scăzută** | Inventar / resurse | Util dacă există nevoie de evidență formală a resurselor. |

---

## 6. Concluzii

- Platforma CRM ANR Bihor oferă deja un set solid de module (membri, contacte, activități, liste prezență, registratură, registru interacțiuni, ToDo, documente, rapoarte, notificări, newsletter, aniversări, setări) și bune practici de securitate și accesibilitate.
- Echipa de seniori testeri recomandă: finalizarea rezolvării FK și a gestionării erorilor, un plan de testare (funcțional, A11y, securitate) și adoptarea modulelor noi în ordinea priorităților de mai sus.
- Implementarea modulelor **Evenimente/calendar** și **Rapoarte extinse** poate crește rapid utilitatea zilnică și capacitatea de raportare a asociației.

---

*Raport întocmit din analiza codului, a schemei bazei de date și a documentației existente (AUDIT_CRM_2026_ACTUALIZAT, DOCUMENTATIE_SCHEMA, MINIRAPORT_ACCESIBILITATE).*
