# 📚 Documentație Schema Bazei de Date - CRM ANR Bihor

**Versiune:** 1.0  
**Data:** 3 februarie 2026  
**Scop:** Documentație completă pentru structura bazei de date

---

## 📋 Structura Fișierelor SQL

Schema bazei de date este organizată în mai multe fișiere SQL pentru ușurința gestionării:

### Fișiere Principale:

1. **`schema.sql`** - Schema principală cu tabelele de bază
   - `membri` - Membrii asociației
   - `contacte` - Contacte externe
   - `activitati` - Activități organizate
   - `liste_prezenta` - Liste de prezență
   - `liste_prezenta_membri` - Legătura membri ↔ liste prezență
   - `taskuri` - Sarcini și task-uri
   - `registratura` - Registratura documentelor
   - `documente_template` - Template-uri pentru documente
   - `setari` - Setări platformă

2. **`schema_registru_interactiuni.sql`** - Modul registru interacțiuni
   - `registru_interactiuni` - Înregistrări apeluri/vizite
   - `registru_interactiuni_subiecte` - Subiecte pentru interacțiuni

3. **`schema_utilizatori.sql`** - Modul autentificare
   - `utilizatori` - Utilizatori platformă
   - `password_reset_tokens` - Token-uri resetare parolă

4. **`fix_critical_issues.sql`** - Script pentru rezolvare probleme critice
   - Adăugare Foreign Keys
   - Optimizare indexuri
   - Verificare integritate date

---

## 🔗 Ordinea de Rulare SQL

**IMPORTANT:** Rulați fișierele SQL în următoarea ordine:

```sql
1. schema.sql                    -- Schema principală
2. schema_registru_interactiuni.sql  -- Modul registru interacțiuni
3. schema_utilizatori.sql        -- Modul autentificare
4. fix_critical_issues.sql       -- Foreign Keys și optimizări (OPȚIONAL - vezi fix_foreign_keys.php)
```

**Sau folosiți scriptul PHP automatizat:**
- `fix_foreign_keys.php` - Adaugă automat Foreign Keys-urile lipsă (recomandat)

---

## 🗄️ Structura Tabelelor

### Tabel: `membri`
**Scop:** Stocare date membri asociație

**Coloane principale:**
- `id` - Primary Key
- `dosarnr` - Număr dosar
- `nume`, `prenume` - Nume complet
- `cnp` - CNP (unic)
- `email`, `telefonnev` - Date contact
- `status_dosar` - Status (Activ, Suspendat, Arhivă)
- `doc_ci`, `doc_ch` - Documente încărcate

**Indexuri:**
- `idx_dosarnr` - Pentru căutare după număr dosar
- `idx_status` - Pentru filtrare după status
- `idx_cnp` - Pentru validare CNP unic

---

### Tabel: `contacte`
**Scop:** Contacte externe (parteneri, furnizori, etc.)

**Coloane principale:**
- `id` - Primary Key
- `nume`, `prenume` - Nume contact
- `tip_contact` - Tip (partener, furnizor, etc.)
- `telefon`, `email` - Date contact
- `companie` - Nume companie

**Indexuri:**
- `idx_contacte_tip_nume` - Căutare după tip și nume

---

### Tabel: `incasari`
**Scop:** Evidență încasări (cotizații, donații, taxe participare, alte venituri).

**Coloane principale:**
- `id` - Primary Key
- `membru_id` - FK opțional către `membri` (pentru încasări de la membri)
- `contact_id` - FK opțional către `contacte` (pentru donatori/contacte externe)
- `tip` - Tip încasare (`cotizatie`, `donatie`, `taxa_participare`, `alte`)
- `anul` - An referință (folosit în special pentru cotizații)
- `suma` - Valoare încasată
- `mod_plata` - Mod plată (`numerar`, `chitanta_veche`, `card_pos`, `card_online`, `transfer_bancar`, `mandat_postal`)
- `data_incasare` - Data încasării
- `seria_chitanta`, `nr_chitanta` - Serie și număr chitanță (pentru metodele care emit chitanță)
- `reprezentand` - Descrierea încasării
- `observatii` - Observații interne
- `created_by` - Utilizatorul care a înregistrat încasarea

---

### Tabel: `incasari_serii`
**Scop:** Manager serii și numerotare pentru chitanțe.

**Coloane principale:**
- `tip_serie` - Cheie logică serie (`donatii`, `incasari`)
- `serie` - Cod serie chitanță
- `nr_start` - Primul număr alocat seriei
- `nr_curent` - Următorul număr disponibil pentru emitere

**Notă de business:**
- Limita superioară a intervalului (`nr_final`) este salvată în `incasari_setari`, chei:
  - `incasari_serie_nr_final_donatii`
  - `incasari_serie_nr_final_incasari`

---

### Tabel: `incasari_setari`
**Scop:** Setări modul Încasări (key-value) pentru chitanțe și integrare.

**Exemple de chei folosite:**
- `logo_chitanta` - URL logo folosit pe chitanță
- `date_asociatie` - Date asociație afișate pe chitanță
- `template_chitanta` - Template chitanță (`standard` / `minimal`)
- `dimensiune_chitanta` - Dimensiune implicită (`a5` / `a4`)
- `fgo_api_key`, `fgo_api_url`, `fgo_merchant_name`, `fgo_merchant_tax_id`, `fgo_mediu`

---

### Tabel: `setari`
**Scop:** Setări generale platformă (key-value), utilizate transversal de module.

**Exemple de chei folosite:**
- `logo_url` - URL logo principal platformă
- `platform_name` - Nume platformă
- `email_asociatie` - Email asociație
- `cale_libreoffice` - Cale executabil LibreOffice pentru conversii documente
- `registratura_nr_pornire` - Număr de pornire registratură
- `newsletter_email` - Email expeditor newsletter
- `antet_asociatie_docx` - Fișier DOCX antet pentru fluxurile DOCX/PDF existente
- `documente_antet_html` - Antet HTML configurabil pentru print-uri și tabele (cu excluderile de business definite în aplicație)

---

### Tabel: `administrativ_achizitii`
**Scop:** Evidență necesar achiziții în modulul Administrativ (lista activă).

**Coloane principale:**
- `id` - Primary Key
- `denumire` - Denumirea produsului/serviciului
- `locatie` - Locație asociată (`Sediu`, `Centru`, `Alta`)
- `urgenta` - Nivel urgență (`normal`, `urgent`, `optional`)
- `furnizor` - Furnizor preferat (opțional)
- `ordine` - Ordine manuală de afișare
- `cumparat` - Flag bifare cumpărat (0/1)
- `data_cumparare` - Data marcării ca achiziționat
- `status_achizitie` - Status business (`achizitie_aprobata`, `comandat`, `pe_viitor`, `achizitie_neaprobata`)
- `data_adaugare` - Data adăugării în listă
- `added_by` - Utilizatorul care a adăugat produsul

**Regulă business:**
- La bifare (`cumparat = 1`), produsul este mutat în `administrativ_achizitii_istoric` și nu mai apare în lista activă.

---

### Tabel: `administrativ_achizitii_istoric`
**Scop:** Istoric cumpărări pentru produsele mutate din lista activă.

**Coloane principale:**
- `id` - Primary Key
- `achizitie_id` - Referință la produsul din lista activă
- `denumire` - Denumirea produsului cumpărat
- `data_cumparare` - Data cumpărării/marcării
- `status_achizitie` - Statusul existent în momentul mutării
- `locatie` - Locația produsului
- `urgenta` - Urgența setată
- `furnizor` - Furnizorul produsului
- `data_adaugare` - Data adăugării inițiale în listă
- `added_by` - Utilizatorul care a adăugat produsul în listă

---

### Tabel: `membri_legitimatii`
**Scop:** Istoric operațiuni legitimație membru (emitere/înlocuire) pe profilul fiecărui membru.

**Coloane principale:**
- `id` - Primary Key
- `membru_id` - FK către `membri`
- `data_actiune` - Data operațiunii legitimației
- `tip_actiune` - Tip operațiune (`legitimatie_membru_nou`, `inlocuire_legitimatie_plina`, `inlocuire_legitimatie_pierduta`)
- `utilizator` - Utilizatorul care a procesat operațiunea
- `created_at` - Timestamp de creare înregistrare

**Regulă business:**
- fiecare salvare din cardul „Legitimație membru” adaugă o intrare nouă (istoric),
- intrările sunt folosite în „Rapoarte > Borderou legitimații” și la print.

---

### Tabel: `activitati`
**Scop:** Activități organizate de asociație

**Coloane principale:**
- `id` - Primary Key
- `data_ora` - Data și ora activității
- `nume` - Nume activitate
- `locatie` - Locație
- `responsabili` - Responsabili
- `status` - Status (planificat, în desfășurare, finalizat)
- `lista_prezenta_id` - FK către `liste_prezenta` (NULL permis)

**Foreign Keys:**
- `fk_lista_prezenta` → `liste_prezenta(id)` ON DELETE SET NULL

---

### Tabel: `liste_prezenta`
**Scop:** Liste de prezență pentru activități

**Coloane principale:**
- `id` - Primary Key
- `tip_titlu` - Titlu listă
- `data_lista` - Data listei
- `detalii_activitate` - Detalii activitate asociată
- `activitate_id` - ID activitate asociată (opțional)
- `tip_lista` - Tip intern listă (ex: `socializare`) pentru rapoarte tematice
- `detalii_suplimentare_sus` - Descriere afișată înainte de tabel (ex: context activitate)
- `detalii_suplimentare_jos` - Descriere afișată după tabel
- `coloane_selectate` - JSON cu coloanele vizibile în listă (nr, nume, vârstă, localitate, semnătură etc.)
- `semnatura_stanga_nume` / `semnatura_stanga_functie` - Semnătură stânga
- `semnatura_centru_nume` / `semnatura_centru_functie` - Semnătură centru
- `semnatura_dreapta_nume` / `semnatura_dreapta_functie` - Semnătură dreapta
- `created_by` - Utilizatorul care a creat lista

**Relații:**
- One-to-Many cu `liste_prezenta_membri`
- Many-to-One cu `activitati` (via `activitate_id`)

---

### Tabel: `liste_prezenta_membri`
**Scop:** Legătură membri ↔ liste prezență

**Coloane principale:**
- `id` - Primary Key
- `lista_id` - FK către `liste_prezenta`
- `membru_id` - FK către `membri`
- `ordine` - Ordine în listă

**Foreign Keys:**
- `fk_lista` → `liste_prezenta(id)` ON DELETE CASCADE
- `fk_membru` → `membri(id)` ON DELETE CASCADE

**Indexuri:**
- Index compus pe `(lista_id, membru_id)` pentru performanță

---

### Tabel: `taskuri`
**Scop:** Sarcini și task-uri

**Coloane principale:**
- `id` - Primary Key
- `nume` - Nume task
- `data_ora` - Data și ora planificată
- `detalii` - Detalii task
- `nivel_urgenta` - Nivel urgență (normal, important, reprogramat)
- `finalizat` - Flag finalizare
- `data_finalizare` - Data finalizării

**Indexuri:**
- `idx_taskuri_finalizat_data` - Căutare după finalizat și dată

---

### Tabel: `registru_interactiuni`
**Scop:** Înregistrări apeluri telefonice și vizite

**Coloane principale:**
- `id` - Primary Key
- `tip` - Tip (apel, vizita)
- `persoana` - Nume persoană
- `telefon` - Număr telefon
- `subiect_id` - FK către `registru_interactiuni_subiecte` (NULL permis)
- `subiect_alt` - Subiect alternativ (text liber)
- `notite` - Notițe
- `task_activ` - Flag creare task automat
- `task_id` - FK către `taskuri` (NULL permis)
- `utilizator` - Utilizator care a înregistrat
- `data_ora` - Data și ora interacțiunii

**Foreign Keys:**
- `fk_subiect` → `registru_interactiuni_subiecte(id)` ON DELETE SET NULL
- `fk_task_interactiune` → `taskuri(id)` ON DELETE SET NULL

**Indexuri:**
- `idx_registru_tip_data` - Căutare după tip și dată

---

### Tabel: `registratura`
**Scop:** Registratura documentelor

**Coloane principale:**
- `id` - Primary Key
- `nr_intern` - Număr intern
- `nr_inregistrare` - Număr înregistrare
- `data_ora` - Data și ora
- `utilizator` - Utilizator
- `tip_act` - Tip act
- `nr_document` - Număr document
- `continut_document` - Conținut
- `destinatar_document` - Destinatar
- `task_deschis` - Flag task deschis
- `task_id` - FK către `taskuri` (NULL permis)

**Foreign Keys:**
- `fk_task_registratura` → `taskuri(id)` ON DELETE SET NULL

---

### Tabel: `utilizatori`
**Scop:** Utilizatori platformă (autentificare)

**Coloane principale:**
- `id` - Primary Key
- `username` - Nume utilizator (unic)
- `parola_hash` - Hash parolă (bcrypt)
- `nume_complet` - Nume complet
- `email` - Email (unic)
- `functie` - Funcție în organizație
- `rol` - Rol (administrator, operator)
- `activ` - Flag activ/inactiv

**Indexuri:**
- `idx_username` - Căutare după username
- `idx_email` - Căutare după email
- `idx_activ` - Filtrare după status activ

---

### Tabel: `password_reset_tokens`
**Scop:** Token-uri pentru resetare parolă

**Coloane principale:**
- `id` - Primary Key
- `utilizator_id` - FK către `utilizatori`
- `token` - Token unic (64 caractere)
- `expira_la` - Data expirării
- `folosit` - Flag utilizare token

**Foreign Keys:**
- `fk_utilizator_reset` → `utilizatori(id)` ON DELETE CASCADE

**Indexuri:**
- `idx_token` - Căutare după token
- `idx_expira` - Curățare token-uri expirate

---

## 🔒 Integritate Referențială

### Foreign Keys Implementate:

1. **`liste_prezenta_membri`**
   - `fk_lista` → `liste_prezenta(id)` CASCADE
   - `fk_membru` → `membri(id)` CASCADE

2. **`activitati`**
   - `fk_lista_prezenta` → `liste_prezenta(id)` SET NULL

3. **`registru_interactiuni`**
   - `fk_subiect` → `registru_interactiuni_subiecte(id)` SET NULL
   - `fk_task_interactiune` → `taskuri(id)` SET NULL

4. **`registratura`**
   - `fk_task_registratura` → `taskuri(id)` SET NULL

5. **`password_reset_tokens`**
   - `fk_utilizator_reset` → `utilizatori(id)` CASCADE

### Verificare Foreign Keys:

Folosiți scriptul `fix_foreign_keys.php` pentru verificare și adăugare automată:
```
Accesați: http://localhost/crm-anr-bihor/fix_foreign_keys.php
(Doar administratori)
```

---

## 📊 Relații între Tabele

```
membri
  └── liste_prezenta_membri (membru_id)
        └── liste_prezenta (lista_id)
              └── activitati (lista_prezenta_id)

registru_interactiuni
  ├── registru_interactiuni_subiecte (subiect_id)
  └── taskuri (task_id)

registratura
  └── taskuri (task_id)

utilizatori
  └── password_reset_tokens (utilizator_id)
```

---

## 🛠️ Mentenanță

### Backup:
- Script: `backup_database.php`
- Recomandare: Backup zilnic automatizat

### Curățare Date:
- Token-uri expirate: Se șterg automat la utilizare
- Date orfane: Verificați cu `fix_critical_issues.sql` (secțiunea 3)

### Optimizare:
- Indexuri: Verificați cu `EXPLAIN` pentru query-uri lente
- Curățare periodică: Ștergere token-uri expirate, arhivare date vechi

---

## 📝 Note Importante

1. **CNP-ul este unic** - Nu permiteți duplicate în `membri.cnp`
2. **Username și Email sunt unici** - Pentru `utilizatori`
3. **CASCADE DELETE** - Ștergerea unui membru șterge automat participările la liste prezență
4. **SET NULL** - Ștergerea unei liste prezență nu șterge activitatea asociată

---

**Ultima actualizare:** 3 februarie 2026
