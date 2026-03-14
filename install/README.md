# Installer CRM ANR Bihor

Installer automat pas cu pas pentru platforma CRM ANR Bihor.

## Cum se folosește

### 1. Upload fișiere pe hosting
- Upload toate fișierele platformei în folderul dorit pe hosting
- Asigură-te că ai inclus folderul `install/`

### 2. Creare baza de date (opțional)
**Installer-ul poate crea automat baza de date**, dar depinde de permisiunile utilizatorului MySQL:

**Opțiunea 1: Creare automată (recomandat să încerci mai întâi)**
- Lasă installer-ul să încerce să creeze baza de date automat
- Dacă ai permisiuni CREATE DATABASE, va funcționa automat

**Opțiunea 2: Creare manuală (dacă nu ai permisiuni)**
- Accesează cPanel sau phpMyAdmin
- Creează manual o bază de date nouă (ex: `crm_anr_bihor`)
- Notează numele, utilizatorul și parola
- Apoi continuă cu installer-ul

### 3. Rulare installer
- Accesează în browser: `https://domeniul-tau.ro/crm-anr-bihor/install/`
- Urmează pașii installer-ului:

#### Pasul 1: Verificare cerințe
- Verifică automat PHP versiune și extensii
- Verifică permisiuni folder uploads/

#### Pasul 2: Configurare baza de date
- Introdu datele de conexiune MySQL
- Installer-ul testează conexiunea

#### Pasul 3: Creare tabele
- Se creează automat toate tabelele din fișierele schema SQL
- Procesul durează câteva secunde

#### Pasul 4: Utilizator administrator
- Creează contul de administrator
- Folosește o parolă sigură

#### Pasul 5: Configurare platformă
- Configurează URL-ul platformei
- Setează numele platformei

#### Pasul 6: Instalare pachete Composer
- Se instalează automat dependențele PHP
- Dacă Composer nu e disponibil, poți uploada manual folderul `vendor/`

#### Pasul 7: Generare CSS
- Se compilează CSS-ul Tailwind
- Dacă npm nu e disponibil, poți uploada manual `css/tailwind.css`

#### Pasul 8: Finalizare
- Se creează fișierul `config.php`
- Platforma este gata de utilizare

### 4. Securitate
**IMPORTANT:** După instalare, șterge sau protejează folderul `install/`:
- Prin FTP: șterge folderul `install/`
- Prin SSH: `rm -rf install/`

## Cerințe

- PHP 7.4 sau mai nou
- MySQL 5.7+ sau MariaDB 10.2+
- Extensii PHP: PDO, PDO_MySQL, mbstring, GD, ZIP, cURL
- Composer (opțional, pentru instalare automată pachete)
- npm (opțional, pentru generare CSS)

## Suport

Dacă întâmpini probleme:
1. Verifică log-urile de eroare PHP
2. Verifică permisiunile fișierelor și folderelor
3. Verifică că baza de date este creată și accesibilă
4. Contactează administratorul sistemului
