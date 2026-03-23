# Evidență Update CRM – Primul UPDATE major

**Proiect:** crm-anr-bihor  
**Mediu:** localhost (dezvoltare). După finalizare, modificările se vor transpanta pe hosting (live).  
**Standard:** accesibilitate WCAG 2.1 AA, design existent, cod responsabil.

---

## Ordinea implementării (reordonată după logică de dezvoltare)

| Ord | Cerință | Descriere scurtă |
|-----|---------|-------------------|
| 0 | Pregătire | Evidență (acest fișier), `PLATFORM_VERSION` în config |
| 1 | #2 | Sidebar: eliminare afișare versiune |
| 2 | #3 | Setări: după numele paginii afișare „Versiune CRM: X.XX” |
| 3 | #11 | Sidebar: aliniere text butoane la stânga; submeniu la mijloc |
| 4 | #1 | Aniversari: nu afișa membri cu status dosar Decedat; contacte fără Beneficiari (deja) |
| 5 | #8 | Toate câmpurile de căutare: Enter execută căutarea |
| 6 | #7 | Liste prezență: Enter în câmp căutare execută căutarea (nu salvare) |
| 7 | #13 | Sidebar: mutare „Librărie documente” în submeniul Administrativ |
| 8 | #12 | Librărie documente: Print = fereastră tipărire; eliminare Editează; drag-drop reordonare |
| 9 | #5 | Membri – profil: Nr. Dosar (6 car.), Data Dosar editabile |
| 10 | #6 | Activități: „Până la ora”, „Ora de început”, „Activități Programate”, separatoare săpt/lună |
| 11 | #10 | Liste prezență: afișare rezultate căutare participanți; checkbox creare activitate la salvare |
| 12 | #9 | Liste prezență: câmpuri adăugare manuală, nr. curent doar persoane, fără rând gol la print, Renunță text alb (tema întunecată) |
| 13 | #15–17 | BPA: tabel cu antet setări; tema întunecată alb; buton Caută + Enter = căutare |
| 14 | #16 | Liste participare: documente cu antet din setări |
| 15 | #18 | Administrativ: „Lista achiziții”, tab Echipa – Editare, Calendar (listă, notificări, formular nou) |
| 16 | #19–20 | Administrativ: CD/AG – antet, liste prezență, documente |
| 17 | #22–23 | Administrativ: Parteneriate (formular dreapta), Proceduri (stânga listă, dreapta formular) |
| 18 | #21 | Juridic ANR: checkbox „Creează procedură internă” |
| 19 | #24 | Contacte: import cu fereastră mapare câmpuri |
| 20 | #4 + #14 | Voluntari: formular, profil, contract, adeverință, setări tab Voluntari, gestionare activități |

---

## Changelog (modificări efectuate)

*La fiecare modificare se adaugă mai jos: data, fișiere modificate, descriere.*

### 2025-02-15

- **Config:** Adăugat `PLATFORM_VERSION` în `config.php` (valoare `2.0`) pentru primul update major. Dacă pe hosting există deja `config.php` cu DB, se adaugă doar linia `define('PLATFORM_VERSION', '2.0');` sau se păstrează versiunea existentă.
- **Evidență:** Creat fișier `UPDATE_LOG.md` (acest document) pentru raport final și transplant pe hosting.
- **Sidebar (#2, #11, #13):** Eliminat afișarea versiunii din footer-ul sidebar-ului. Textul butoanelor din meniul principal aliniat la stânga (`justify-start`, `text-left`). Elementele din submeniul Administrativ aliniate la mijloc (`justify-center`, `text-center`). „Librărie documente” mutată din meniul principal în submeniul Administrativ (după ToDo List).
- **Setări (#3):** După titlul „Setări” se afișează „Versiune CRM: X.XX” (folosește `get_platform_version()` / `PLATFORM_VERSION`).
- **Aniversari (#1):** La afișarea aniversarilor din membri se exclud persoanele cu status dosar „Decedat” (clauză `AND (status_dosar IS NULL OR status_dosar != 'Decedat')` în interogările din `aniversari.php`). Calendarul lunar folosește același filtru. Aniversările din contacte excludeau deja categoria Beneficiari (neschimbat).
- **Căutare Enter (#7, #8):** În toate câmpurile de căutare (liste prezență create/edit, BPA, setări scutire, voluntariat), apăsarea tastei Enter execută căutarea (nu submitează formularul). Formularele GET (contacte, membri, index, administrativ proceduri) au deja submit la Enter. Adăugat buton „Caută” la câmpul de căutare membru din BPA (#17).
- **Librărie documente (#12):** Butonul Print deschide fereastra de tipărire (pagină intermediară `print-librarie-document.php` care încarcă documentul și apelează `window.print()`). Eliminat butonul „Editează” și modalul de editare. Adăugat coloana `ordine` în tabelul `librarie_documente`, reordonare via drag-and-drop și salvare prin POST `reordoneaza_ids[]`.
- **Membri – profil (#5):** Câmpuri editabile „Nr. Dosar” (max. 6 caractere) și „Data Dosar” în formularul de profil membru (`membru-profil-form.php`); salvate prin `membri_processing.php`.
- **Activități (#6):** Formular „Adaugă activitate”: câmp „Ora” redenumit în „Ora de început”, adăugat câmp „Până la ora” (opțional, format 24h). La salvare se persistă `ora_finalizare`. Tabel: titlu „Ziua curentă” schimbat în „Activități Programate”. Coloana ora afișează interval (ex. 09:00-14:30) când există „până la ora”. Inserate separatoare: înainte de prima activitate dintr-o lună – rând cu numele lunii și anul (bold, fundal evidențiat); înainte de prima activitate dintr-o săptămână – rând „Luna – Săptămâna [zi – zi]”.
- **Liste prezență (#9, #10):** Checkbox „La salvare, creează activitate în Activități Programate” înainte de butoanele Salvează; activitatea se creează doar când checkbox-ul este bifat (folosind data, ora și detaliile listei). Rezultatele căutării participanți sunt afișate corect (feedback „Se caută…”, gestionare eroare). Butonul „Renunță” are în tema întunecată text alb (`dark:text-white`).
- **Administrativ – Juridic ANR (#21):** Checkbox „Creează o procedură internă nouă (în tabul Proceduri interne)” – la salvare, dacă e bifat, se creează automat o procedură internă cu subiectul și conținutul din formular.
- **Administrativ – Parteneriate (#22–23):** Layout schimbat: listă parteneriate în stânga, formular adăugare în dreapta (`grid lg:grid-cols-2`).
- **Administrativ – Echipa (#18):** Buton „Editare” pentru fiecare angajat, membru Consiliul Director și membru Adunarea Generală. La click, formularul se pre-completează cu datele existente; butoane „Salvează modificările” și „Renunță”. Helpers: `administrativ_angajat_get`, `administrativ_cd_get`, `administrativ_ag_get`.
- **Contacte (#24):** Import cu mapare câmpuri – deja implementat în `contacte-import.php` (încarci fișier → vezi coloanele → mapezi fiecare coloană la câmp DB → import).

---

## Raport pentru transplant pe hosting (se completează la final)

1. **Fișiere PHP modificate:** `config.php`, `sidebar.php`, `setari.php`, `aniversari.php`, `lista-prezenta-create.php`, `lista-prezenta-edit.php`, `ajutoare-bpa.php`, `setari.php`, `voluntariat.php`, `librarie-documente.php`, `includes/librarie_documente_helper.php`, `membru-profil-form.php`, `activitati.php`, `administrativ.php`, `includes/administrativ_helper.php`.
2. **Fișiere noi create:** `UPDATE_LOG.md`, `print-librarie-document.php`.
3. **Modificări baza de date (SQL):** Tabelul `librarie_documente`: coloană `ordine INT NOT NULL DEFAULT 0` (se adaugă automat la prima încărcare prin helper). Tabelul `activitati`: coloana `ora_finalizare` (dacă lipsește, se adaugă la prima salvare).
4. **Config:** Pe hosting, în `config.php`, definiți `PLATFORM_VERSION` (ex. `2.0`) dacă nu există.
5. **Pași deploy:** Backup DB + fișiere → încărcare fișiere → rulare SQL dacă e cazul → testare → golire cache (dacă există).

---

*Document actualizat pe măsură ce se implementează cerințele.*
