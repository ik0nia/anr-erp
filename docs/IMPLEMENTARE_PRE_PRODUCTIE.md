# 🚀 Ghid implementare pre-producție - CRM ANR Bihor

Acest ghid conține pașii necesari pentru pregătirea platformei înainte de utilizarea cu date reale.

---

## ⚡ ACȚIUNI CRITICE (OBLIGATORIU)

### 1. Rezolvare Foreign Keys

**Pași:**
1. Deschideți phpMyAdmin: `http://localhost/phpmyadmin`
2. Selectați baza de date `crm-anr-bihorxampp`
3. Rulați scriptul `fix_critical_issues.sql` (sau copiați conținutul în SQL tab)
4. Verificați că nu există erori
5. Verificați că toate FK-urile au fost create (vezi secțiunea "Verificare finală" din script)

**Verificare:**
```sql
SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE CONSTRAINT_SCHEMA = 'crm-anr-bihorxampp'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

---

### 2. Implementare Protecție CSRF

**Pași:**

1. **Helper-ul CSRF este deja creat** în `includes/csrf_helper.php`

2. **Adăugare în formulare:**
   - Deschideți fiecare fișier PHP cu formulare POST
   - Adăugați `<?php require_once 'includes/csrf_helper.php'; ?>` la început (după `require_once 'config.php'`)
   - Adăugați `<?php echo csrf_field(); ?>` în fiecare form (după tag-ul `<form>`)
   - Adăugați `csrf_require_valid();` la începutul procesării POST

**Exemplu implementare:**

**Înainte:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_membru'])) {
    // procesare...
}
```

**După:**
```php
require_once 'includes/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adauga_membru'])) {
    csrf_require_valid(); // Adăugat aici
    // procesare...
}
```

**În formular:**
```html
<form method="post">
    <?php echo csrf_field(); ?>
    <!-- restul câmpurilor -->
</form>
```

**Fișiere care necesită CSRF:**
- `membri.php` (formular adăugare)
- `membru-profil-form.php` (formular editare)
- `activitati.php` (formulare adăugare/editare)
- `lista-prezenta-create.php`
- `lista-prezenta-edit.php`
- `registratura-adauga.php`
- `registratura-edit.php`
- `registru-interactiuni.php`
- `contacte-adauga.php`
- `contacte-edit.php`
- `todo-adauga.php`
- `todo.php` (finalizare task)
- `setari.php` (toate formularele)
- `social-hub.php`
- `index.php` (formulare interacțiuni, finalizare task)
- `schimba-parola.php`
- `recuperare-parola.php`
- `reset-parola.php`

---

### 3. Configurare Backup Automat

**Opțiunea 1: Cron job (Linux/Mac)**

1. Deschideți crontab: `crontab -e`
2. Adăugați linia:
```bash
0 2 * * * /usr/bin/php /path/to/crm-anr-bihor/backup_database.php >> /var/log/crm_backup.log 2>&1
```
(Backup zilnic la 2:00 AM)

**Opțiunea 2: Task Scheduler (Windows)**

1. Deschideți Task Scheduler
2. Creați task nou
3. Trigger: Daily, la 2:00 AM
4. Acțiune: Start program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\crm-anr-bihor\backup_database.php`

**Opțiunea 3: Manual din Setări**

Adăugați buton "Backup acum" în `setari.php` care apelează `backup_database.php`

**Verificare backup:**
- Verificați directorul `backups/` - ar trebui să existe fișiere `.sql` sau `.sql.gz`
- Testați restore: importați un backup în phpMyAdmin

---

### 4. Securizare Fișiere Sensibile

**Creați `.htaccess` în root:**
```apache
# Protejează fișierele sensibile
<FilesMatch "^(backup_database\.php|setare-admin\.php|install-auth\.php|fix_critical_issues\.sql)$">
    Require ip 127.0.0.1
    # Sau: Require valid-user (pentru autentificare HTTP)
</FilesMatch>

# Protejează directorul backups
<Directory "backups">
    Require all denied
</Directory>
```

**Sau mutați fișierele sensibile:**
- `backup_database.php` → mutați în afara document root
- `setare-admin.php` → ștergeți după utilizare
- `install-auth.php` → ștergeți după utilizare

---

## 📋 ACȚIUNI RECOMANDATE

### 5. Optimizare Performanță

**Adăugare indexuri (deja în `fix_critical_issues.sql`):**
- Rulați secțiunea "2. ADĂUGARE INDEXURI" din script

**Verificare query-uri lente:**
```sql
-- Activează slow query log în MySQL
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

---

### 6. Testare Completă

**Checklist testare:**

**Membri:**
- [ ] Adăugare membru nou
- [ ] Editare membru existent
- [ ] Ștergere membru (dacă există funcționalitate)
- [ ] Import Excel
- [ ] Export Excel
- [ ] Căutare membri
- [ ] Filtrare după status
- [ ] Avertizări GDPR/CI/Certificat

**Activități:**
- [ ] Creare activitate
- [ ] Editare activitate
- [ ] Creare listă prezență din activitate
- [ ] Creare activitate din listă prezență
- [ ] Print listă prezență
- [ ] PDF listă prezență

**Registratura:**
- [ ] Adăugare înregistrare
- [ ] Editare înregistrare
- [ ] Numerotare automată
- [ ] Asociere membru

**Registru Interacțiuni:**
- [ ] Înregistrare apel
- [ ] Înregistrare vizită
- [ ] Creare task din interacțiune
- [ ] Adăugare subiect nou

**Contacte:**
- [ ] Adăugare contact
- [ ] Editare contact
- [ ] Ștergere contact
- [ ] Import contacte

**Documente:**
- [ ] Upload template
- [ ] Generare document
- [ ] Descărcare document
- [ ] Trimite email cu document

**Autentificare:**
- [ ] Login
- [ ] Logout
- [ ] Recuperare parolă
- [ ] Reset parolă
- [ ] Schimbare parolă
- [ ] Rămâne logat (30 zile)
- [ ] Adăugare utilizator (admin)
- [ ] Management utilizatori

**Setări:**
- [ ] Actualizare logo
- [ ] Setări registratura
- [ ] Setări generare documente
- [ ] Subiecte interacțiuni

**Social Hub:**
- [ ] Distribuție conținut
- [ ] Upload imagini

**Rapoarte:**
- [ ] Vizualizare statistici
- [ ] Export date

---

### 7. Documentație Utilizator

Creați `Ghid_Utilizator.md` cu:
- Instrucțiuni login
- Ghid rapid pentru fiecare modul
- Screenshot-uri (opțional)
- FAQ

---

### 8. Configurare Email

**Pentru trimitere email-uri reale:**

1. Configurare SMTP în `php.ini` sau folosiți bibliotecă (ex: PHPMailer)
2. Actualizați funcțiile de email din `includes/auth_helper.php` și `includes/social_hub_helper.php`
3. Testați trimitere email:
   - Recuperare parolă
   - Confirmare utilizator nou
   - Trimite document

---

## 🔒 SECURITATE SUPLEMENTARĂ

### 9. Hardening Server

**Recomandări:**
- Actualizați PHP la ultima versiune stabilă
- Actualizați MySQL/MariaDB
- Configurare firewall
- SSL/HTTPS (obligatoriu pentru date personale)
- Rate limiting pentru login

### 10. Monitoring

**Configurare logging:**
- Log erori PHP în fișier separat
- Log accesări suspecte
- Alertă pentru erori critice

---

## ✅ CHECKLIST FINAL PRE-PRODUCȚIE

### Securitate
- [ ] CSRF implementat în toate formularele
- [ ] Foreign keys adăugate
- [ ] Validare input completă
- [ ] Fișiere sensibile protejate
- [ ] SSL/HTTPS configurat

### Baza de date
- [ ] Schema consolidată
- [ ] Foreign keys funcționale
- [ ] Indexuri optimizate
- [ ] Backup automat configurat
- [ ] Test restore reușit

### Funcționalități
- [ ] Toate modulele testate
- [ ] Integrare verificată
- [ ] Email-uri funcționale
- [ ] Import/Export testat

### Documentație
- [ ] README actualizat
- [ ] Ghid utilizator creat
- [ ] Documentație admin

---

## 🎯 DUPĂ IMPLEMENTARE

1. **Testare cu date sample** (100-200 membri)
2. **Testare performanță** (1000+ membri)
3. **Testare accesibilitate** (screen reader)
4. **Training utilizatori**
5. **Deploy producție**
6. **Monitorizare primele zile**

---

**Timp estimat implementare:** 5-7 zile lucrătoare  
**Prioritate:** 🔴 CRITIC pentru securitate și integritate date
