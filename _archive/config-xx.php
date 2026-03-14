<?php
/**
 * Exemplu configurare pentru HOSTING (MySQL de la provider).
 * Copiază DOAR liniile de mai jos în config.php (înlocuiește define-urile DB_* existente).
 * Nu înlocui tot fișierul config.php – doar secțiunea de bază de date.
 *
 * Pe hosting, config.php este generat automat la finalul instalării (pasul 8).
 * Dacă ai suprascris config.php cu cel de pe PC (XAMPP), editează pe server
 * și pune valorile cu care te-ai conectat la pasul 2 al installer-ului.
 */

// Exemplu – înlocuiește cu valorile tale de la pasul 2 (cele cu care te-ai conectat la instalare):
define('DB_HOST', 'localhost');
define('DB_NAME', 'NUMEL_BAZEI_DE_DATE');   // ex: r74526anrb_internapp_crm
define('DB_USER', 'UTILIZATORUL_MYSQL');    // ex: r74526anrb_internapp_usr
define('DB_PASS', 'PAROLA_TA');            // parola MySQL (dacă conține ' folosește \' în interior)
