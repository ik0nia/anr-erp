# Analiză: Modulul Registru Interacțiuni

## Rol și scop

Modulul **Registru Interacțiuni** servește la înregistrarea și centralizarea contactelor cu persoane (apeluri telefonice și vizite la sediu). Scopul este:
- **Evidență**: cine a sunat / a venit, când, despre ce subiect, cu ce notițe.
- **Contorizare**: număr apeluri și vizite (în special „azi”) pentru raportare rapidă.
- **Legătură cu ToDo**: opțiunea de a crea un task activ din interacțiune (urmărire ulterioară).

Utilizatorii sunt cei care lucrează la sediul asociației și au nevoie de un jurnal simplu de interacțiuni.

---

## Design și locuri de afișare

1. **Dashboard (index.php)**  
   - Coloana din dreapta (layout 3 coloane): bloc „Registru Interacțiuni” cu formular scurt (tip Apel/Vizită, persoană, telefon opțional, subiect din dropdown + câmp „Alt subiect”, notițe, checkbox task ToDo) și buton Salvează.  
   - Sub formular: rezumat „Rezumat” cu taskuri active/finalizate și „Registru Interacțiuni” – apeluri azi / vizite azi.  
   - Link către pagina dedicată „Registru Interacțiuni”.

2. **Pagina dedicată (registru-interactiuni.php)**  
   - Header cu titlu și buton „Adaugă interacțiune”.  
   - Rezumat: apeluri azi, vizite azi, total în listă (ultimele 100).  
   - Tabel: Data/Ora, Tip, Persoană, Telefon, Subiect, Notițe, Utilizator.  
   - Click pe rând → modal editare.  
   - Modal adăugare deschis din buton sau la redirect după eroare.

3. **Setări**  
   - Administrare subiecte pentru dropdown: creare subiecte noi, dezactivare (nu ștergere) subiecte. Subiectele active apar în dropdown în formularul de interacțiuni.

4. **Rapoarte**  
   - Secțiune „Registru Interacțiuni”: toate categoriile (tip + subiect) și numărul total de interacțiuni contorizate.

---

## Mod de funcționare

- **Tip**: `apel` sau `vizita` (ENUM în DB).  
- **Persoană**: obligatoriu (text).  
- **Telefon**: opțional.  
- **Subiect**: fie din lista de subiecte (subiect_id → `registru_interactiuni_subiecte`), fie liber prin câmpul „Alt subiect” (subiect_alt). Câmpul „Alt subiect” este mereu vizibil.  
- **Notițe**: text liber.  
- **Task activ**: checkbox; dacă e bifat, se creează un rând în `taskuri` și se leagă prin `task_id` în registru. Detaliile taskului includ notițele și subiectul (din dropdown sau alt subiect).  
- **Data/Ora**: pe pagina dedicată se setează explicit; pe dashboard se folosește data/ora curentă la salvare.  
- **Contorizare**: `DATE(data_ora) = azi` pentru „apeluri azi” și „vizite azi”.  
- **Afișare listă**: ultimele 100 înregistrări, ordonate descrescător după `data_ora`.

Tabele: `registru_interactiuni` (înregistrări) și `registru_interactiuni_subiecte` (subiecte cu ordine și activ). Crearea tabelelor se face prin helper (`ensure_registru_tables`).

---

## Principii păstrate la rescriere

- Același format și locuri de afișare (dashboard coloana 3, pagina dedicată, setări, rapoarte).  
- Același flux: POST → validare → INSERT/UPDATE → redirect sau afișare eroare.  
- Accesibilitate (ARIA, etichete, structură semantică).  
- Telefon opțional; câmp „Alt subiect” mereu afișat.  
- Subiecte administrabile din Setări (tab Dashboard): creare și dezactivare, fără ștergere fizică.

---

## Checklist testare funcționalitate

După rescriere, verificați:

1. **Dashboard (index.php)**  
   - Formularul din coloana dreaptă: selectați Apel sau Vizită, completați Persoana, opțional Telefon, Subiect (dropdown + Alt subiect), Notițe, eventual bifați „Rămâne task activ”. Salvați → mesaj succes și contor „Apeluri azi” / „Vizite azi” actualizat.

2. **Pagina Registru Interacțiuni (registru-interactiuni.php)**  
   - Buton „Adaugă interacțiune” → modal cu același formular (cu Data/Ora). Salvare → redirect cu succes.  
   - Click pe un rând din tabel → modal editare; modificați și salvați → „Interacțiunea a fost actualizată cu succes”.  
   - Rezumat: Apeluri azi, Vizite azi, Total în listă (ultimele 100).

3. **Setări – tab Dashboard (setari.php?tab=dashboard)**  
   - Adăugați un subiect nou → apare în listă cu status Activ.  
   - Dezactivați un subiect → status Dezactivat; acel subiect nu mai apare în dropdown-ul de pe Dashboard/Registru.  
   - Activați din nou → revine în dropdown.

4. **Rapoarte – tab Registru Interacțiuni (rapoarte.php?tab=interactiuni)**  
   - Se afișează Total apeluri, Total vizite, Total interacțiuni și tabelul de categorii (subiect → număr interacțiuni), inclusiv „Alt subiect”.
