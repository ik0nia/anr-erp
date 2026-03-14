# Depanare: imposibilitatea de a salva modificările în profilul membrului

## Concluzie (audit dev + QA)

**Două cauze** au fost identificate și remediate:

1. **Client (JS):** validarea strictă CNP din `form-validation.js` bloca submit-ul formularului.
2. **Server (PHP):** în `membri_processing.php`, CNP-ul era tratat ca **obligatoriu și la actualizare**. Dacă câmpul CNP era gol (sau rămânea gol), serverul returna „CNP-ul este obligatoriu” și salvarea eșua – fără erori în consolă, doar refolosirea paginii cu datele neschimbate.

---

## 1. Modificări JavaScript (deja aplicate)

- În `js/form-validation.js`: formularul `#form-membru-profil` nu mai este validat la submit (nu se apelează `validateForm`), astfel că datele pleacă mereu la server.
- Validarea CNP pe client a fost exclusă pentru formularul de profil.

---

## 2. Modificări PHP – `membri_processing.php` (remediu principal)

- **La actualizare:** dacă câmpul CNP din formular este **gol**, se folosește CNP-ul existent din baza de date (nu se mai returnează „CNP-ul este obligatoriu”).
- **CNP obligatoriu** doar la **adăugare** membru nou; la **actualizare** CNP poate rămâne gol (se păstrează valoarea existentă sau se lasă gol).
- La actualizare, dacă CNP-ul nu s-a schimbat, nu se mai aplică validarea strictă; dacă s-a schimbat, se validează ca la adăugare.

Rezultat: salvarea din profilul membrului funcționează și când CNP-ul este gol sau incomplet.

---

## Ce poți testa

1. Deschide profilul unui membru (inclusiv unul cu CNP gol sau incomplet).
2. Modifică un câmp (ex. nume, telefon, adresă).
3. Apasă „Salvează modificările”.
4. Ar trebui să vezi redirecționare și mesajul „Datele membrului au fost actualizate cu succes.”

Dacă apare eroare, va fi afișată în caseta roșie pe pagină (ex. „CNP-ul este obligatoriu” doar la adăugare membru nou). În F12 → Network poți verifica că request-ul POST către `membru-profil.php` este trimis și primește răspuns 302 (redirect) la succes.
