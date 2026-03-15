# Regression Checklist — ERP ANR Bihor

Această listă se folosește înainte și după fiecare patch de refactor.
Verificare manuală — fiecare punct trebuie confirmat ca funcțional.

---

## Golden Paths (MUST PASS)

### GP-01: Autentificare
- [ ] Login cu credențiale corecte → redirect la Dashboard
- [ ] Login cu credențiale greșite → mesaj eroare, rămâne pe login
- [ ] Login cu "Rămâne logat" bifat → sesiune persistă după restart browser
- [ ] Logout → redirect la login, sesiune distrusă
- [ ] Acces pagină protejată fără sesiune → redirect la login

### GP-02: Dashboard (index.php)
- [ ] Dashboard se încarcă fără erori PHP
- [ ] Lista taskuri active se afișează
- [ ] Finalizare task din dashboard → task marcat ca finalizat
- [ ] Formular interacțiuni rapide funcționează (POST + CSRF)
- [ ] Alerte documente expirate vizibile (dacă există)

### GP-03: Membri — CRUD complet
- [ ] Lista membri se încarcă cu paginare
- [ ] Sortare pe coloane funcționează
- [ ] Căutare membri funcționează
- [ ] Adaugă membru nou (cu CNP valid, upload CI) → apare în listă
- [ ] Editare membru → salvare → verifică log activitate
- [ ] Profil membru se încarcă (membru-profil.php)
- [ ] Export membri CSV funcționează

### GP-04: Contacte — CRUD complet
- [ ] Lista contacte se încarcă
- [ ] Adaugă contact (persoană fizică) → apare în listă
- [ ] Editare contact → salvare → verifică log
- [ ] Ștergere contact → dispare din listă
- [ ] Filtrare pe tip contact funcționează

### GP-05: Registratură
- [ ] Lista registratură se încarcă
- [ ] Adaugă document → nr intern auto-incrementat
- [ ] Editare document → salvare
- [ ] Creare task din document (checkbox) funcționează
- [ ] Sumar document se afișează corect

### GP-06: Încasări
- [ ] Adaugă încasare (tip cotizație, numerar) → salvare OK
- [ ] Chitanță PDF se generează și descarcă
- [ ] Chitanță print se deschide corect
- [ ] Căutare membri în formularul de încasări funcționează (AJAX)

### GP-07: Notificări
- [ ] Trimite notificare (cu text, fără atașament) → apare la destinatar
- [ ] Trimite notificare cu atașament → descărcare funcționează
- [ ] Marcare citit/necitit funcționează
- [ ] Arhivare notificare funcționează
- [ ] Badge-ul de notificări necitite din sidebar se actualizează

### GP-08: Setări
- [ ] Pagina setări se încarcă (toate tab-urile)
- [ ] Schimbă nume platformă → header se actualizează
- [ ] Adaugă utilizator nou → verifică email confirmare
- [ ] Schimbă parolă proprie → funcționează la re-login

### GP-09: Generare documente
- [ ] Upload template DOCX funcționează
- [ ] Generare document din membru → DOCX descărcat
- [ ] Conversie la PDF funcționează (dacă LibreOffice instalat)

### GP-10: Recuperare parolă
- [ ] Cerere recuperare cu email valid → email trimis (verifică mailer)
- [ ] Link reset cu token valid → formulă schimbare parolă
- [ ] Token expirat → mesaj eroare
- [ ] Parolă schimbată → login cu parola nouă funcționează

---

## High Risk Paths (SHOULD PASS)

### HR-01: Import membri CSV
- [ ] Upload CSV → mapare coloane → preview → import
- [ ] Membrii importați apar în listă cu datele corecte
- [ ] CNP invalid → warning (nu blochează importul)
- [ ] Duplicate pe CNP → tratate conform logicii existente

### HR-02: Administrativ — achiziții urgente
- [ ] Adaugă achiziție cu urgență mare → notificare generată
- [ ] Tab-uri funcționale: echipă, calendar, CD, AG, juridic, parteneriate, proceduri

### HR-03: Voluntariat — flux complet
- [ ] Adaugă voluntar → contact creat automat
- [ ] Generare contract voluntar → DOCX descărcat
- [ ] Adaugă activitate voluntariat → participanți

### HR-04: BPA — tabel distribuție
- [ ] Adaugă document gestiune → stoc calculat
- [ ] Creare tabel distribuție → PDF generat
- [ ] Statistici BPA afișate corect

### HR-05: Newsletter
- [ ] Salvare draft → programare → verifică cron processing
- [ ] Trimitere imediată → email ajunge la destinatar

### HR-06: Liste prezență
- [ ] Creare listă → selectare membri → salvare
- [ ] Print list funcționează
- [ ] Export PDF/DOCX funcționează

### HR-07: Registru interacțiuni v2
- [ ] Adaugă interacțiune (telefon/vizită) → apare în registru
- [ ] Statistici lunare/pe subiecte se calculează corect

---

## Smoke Tests Rapide (5 minute)

Verificare minimă după orice patch:

1. [ ] `https://erp.anrbihor.ro/login.php` — se încarcă, fără erori PHP
2. [ ] Login → Dashboard — se încarcă
3. [ ] Click pe "Membri" din sidebar — lista se încarcă
4. [ ] Click pe "Contacte" din sidebar — lista se încarcă
5. [ ] Click pe "Setări" din sidebar — se încarcă
6. [ ] Logout → redirect la login

---

## Verificări tehnice post-patch

- [ ] Zero erori PHP în `logs/` sau `error_log`
- [ ] Niciun warning "function already defined"
- [ ] Niciun "Class not found" sau "require failed"
- [ ] CSRF funcționează (submit formular → nu dă 403)
- [ ] Sesiunea persistă între pagini (nu cere re-login)

---

## Nu atinge încă (out of scope pentru refactor curent)

- Schema DB runtime migrations (ALTER TABLE în helpers)
- config.php (bootstrap central)
- Flow-ul de instalare (setup.php + install/)
- Sesiune "remember me"
- FGO.ro API integration
- LibreOffice exec() din document_helper
- Cron jobs (aniversari-notificare-zilnica, cron_newsletter)
