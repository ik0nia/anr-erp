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
- `caz_social` - Indicator caz social (`0`/`1`), folosit în filtrele din Management Membri și în indicatorii din Rapoarte

**Indexuri:**
- `idx_dosarnr` - Pentru căutare după număr dosar
- `idx_status` - Pentru filtrare după status
- `idx_cnp` - Pentru validare CNP unic

---

### Tabel: `cotizatii_scutiri`
**Scop:** Evidența membrilor scutiți de la plata cotizației, sincronizată între:
- `Management Membri > Profil membru > Observații`
- `Setări > Cotizații > Membri scutiți de la plata cotizației`

**Coloane principale:**
- `id` - Primary Key
- `membru_id` - Referință către membru
- `tip_scutire` - Tip scutire (`temporar`, `permanent`; în UI există și opțiunea `Nu`, care șterge scutirea)
- `data_scutire_de_la` - Data de început scutire (pentru temporar)
- `data_scutire_pana_la` - Data de sfârșit scutire (pentru temporar)
- `scutire_permanenta` - Flag compatibilitate (`1` pentru permanent)
- `motiv` - Motivul scutirii
- `created_at` - Data creării

**Reguli business:**
- membrul este considerat **scutit activ** dacă:
  - `tip_scutire = permanent`, sau
  - `tip_scutire = temporar` și data curentă este în intervalul `[data_scutire_de_la, data_scutire_pana_la]`.
- scutirea activă înlocuiește obligația anuală de plată cotizație pentru perioada respectivă.
- setarea scutirii din profilul membrului actualizează automat lista din Setări (și invers), folosind aceeași sursă de date.

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
- `informatii_suplimentare_chitanta_image_path` - Calea imaginii tip carte de vizită (5.5cm x 8.5cm) afișată pe chitanță
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
- `documente_antet_source` - Sursa antetului documente (`html` sau `image`) pentru print-urile generale din platformă
- `documente_antet_image_path` - Calea imaginii uploadate pentru antet documente (alternativă la editor)
- `documente_antet_image_alt` - Text alternativ (ALT) pentru imaginea de antet (accesibilitate)

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
- `status_achizitie` - Status business (`fara_status`, `achizitie_aprobata`, `comandat`, `pe_viitor`, `achizitie_neaprobata`)
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
- `nr_registratura` - Număr alocat automat din modulul `registratura` la salvarea listei
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
**Scop:** Participanți pentru liste prezență (membri, contacte, manual)

**Coloane principale:**
- `id` - Primary Key
- `lista_id` - FK către `liste_prezenta`
- `membru_id` - FK către `membri` (NULL permis pentru contacte/manual)
- `contact_id` - ID participant din `contacte` (NULL permis)
- `ordine` - Ordine în listă
- `nume_manual` - Nume text pentru participant introdus manual

**Foreign Keys:**
- `fk_lista` → `liste_prezenta(id)` ON DELETE CASCADE
- `fk_membru` → `membri(id)` ON DELETE CASCADE

**Indexuri:**
- Index compus pe `(lista_id, membru_id)` pentru performanță
- Index opțional pe `contact_id` (unde schema este migrată dinamic)

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

### Tabel: `documente_generate`
**Scop:** Evidență documente generate pentru membri (format final PDF), utilizată în profilul membrului.

**Coloane principale:**
- `id` - Primary Key
- `membru_id` - ID membru asociat documentului
- `template_id` - ID template folosit (opțional)
- `template_nume` - Nume template la momentul generării
- `tip_template` - Tip template sursă (`docx` / `pdf`)
- `fisier_pdf` - Numele fișierului PDF generat (stocat în `uploads/documente_generate`)
- `fisier_docx` - Numele DOCX intermediar/generat (opțional)
- `nr_inregistrare` - Număr registratură alocat la generare (dacă există)
- `created_by` - Utilizatorul care a generat documentul
- `trimis_email_at` - Timestamp marcare trimitere email
- `trimis_whatsapp_at` - Timestamp marcare deschidere trimitere WhatsApp
- `created_at` - Data generării
- `updated_at` - Data ultimei actualizări

**Indexuri:**
- `idx_doc_gen_membru_data` - listare rapidă pe profil membru
- `idx_doc_gen_template` - analiză după template

---

### Tabel: `documente_template`
**Scop:** Template-uri sursă pentru modulul „Generare documente”.

**Coloane principale:**
- `id` - Primary Key
- `nume_afisare` - Nume prietenos template (afișat în UI)
- `nume_fisier` - Fișier sursă template (`.docx` / `.pdf`) stocat în `uploads/documente_template`
- `foloseste_antet_platforma_erp` - Flag (`0/1`) care controlează aplicarea antetului ERP la documentul final generat
- `activ` - Flag activ/inactiv (template disponibil la generare)
- `created_at` - Data adăugării
- `updated_at` - Data ultimei modificări

**Notă business:**
- Dacă `foloseste_antet_platforma_erp = 1`, la generare se aplică antetul ERP din **Setări > Antet documente > editor HTML** pe PDF-ul final (sursa `image` este ignorată în acest flux).
- Dacă documentul DOCX generat conține deja antet (din template), suprapunerea antetului ERP pe PDF este omisă implicit pentru consistență între DOCX și PDF.
- Dacă `foloseste_antet_platforma_erp = 0`, se păstrează antetul/subsolul originale din fișierul template.

---

### Tabel: `fundraising_f230_formulare`
**Scop:** Centralizare formulare 230 colectate online/manual din modulul Fundraising.

**Coloane principale:**
- `id` - Primary Key
- `nume`, `initiala_tatalui`, `prenume` - Date identificare
- `cnp` - CNP validat (13 cifre + checksum)
- `localitate`, `judet`, `cod_postal`, `strada`, `numar`, `bloc`, `scara`, `etaj`, `apartament` - Adresă
- `telefon`, `email` - Date contact
- `gdpr_acord` - Confirmare acord GDPR (`0/1`)
- `semnatura_path` - Calea semnăturii PNG salvate în folder privat `F230PDF/signatures`
- `pdf_path`, `pdf_filename` - Calea și numele PDF-ului completat (folder privat `F230PDF`)
- `sursa` - Originea formularului (`online` / `manual`)
- `ip_adresa`, `user_agent` - Metadate tehnice captate la submit
- `created_at` - Data înregistrării

**Indexuri:**
- `idx_f230_cnp`
- `idx_f230_created`
- `idx_f230_sursa`

**Notă business:**
- template-ul PDF este configurat în `setari` (cheia `fundraising_f230_template_pdf`),
- data ultimului upload template este salvată în `setari` (cheia `fundraising_f230_template_uploaded_at`),
- statusul fallback-ului FPDI pentru template-ul curent este disponibil în UI pe baza existenței fișierului cache din `uploads/fundraising/fpdi-compatible/template-fpdi-[sha256].pdf`,
- maparea zonelor PDF (workflow mapper) este configurată în `setari` (cheia `fundraising_f230_template_mapping_json`),
- mesajul de confirmare email este configurat în `setari` (cheia `fundraising_f230_mesaj_confirmare_html`),
- fișierele PDF generate sunt stocate în `F230PDF` cu acces public blocat la nivel web.

---

### Tabel: `notificari`
**Scop:** Mesaje interne afișate utilizatorilor platformei (globale sau targetate pe un utilizator).

**Coloane principale:**
- `id` - Primary Key
- `titlu`, `continut` - Conținut notificare
- `importanta` - Nivel (`Normal`, `Important`, `Informativ`)
- `link_extern` - Link opțional asociat notificării
- `atasament_nume`, `atasament_path` - Fișier atașat (opțional)
- `trimite_email` - Flag trimitere email (`0/1`)
- `target_scope` - Public țintă (`all` / `user`)
- `target_user_id` - Utilizator țintă dacă `target_scope = user`
- `highlighted_by_comment` - Evidențiere notificare în listă când există comentariu cu broadcast (`0/1`)
- `created_by` - Utilizatorul care a creat notificarea
- `created_at` - Data creării

**Indexuri:**
- `idx_created_at`
- `idx_importanta`
- `idx_target_user_id`
- `idx_highlighted_by_comment`

---

### Tabel: `notificari_utilizatori`
**Scop:** Stare per utilizator pentru fiecare notificare (nou/citit/arhivat).

**Coloane principale:**
- `id` - Primary Key
- `notificare_id` - ID notificare
- `utilizator_id` - ID utilizator
- `status` - Status (`nou`, `citit`, `arhivat`)
- `citit_la`, `arhivat_la` - Timestamps de stare
- `created_at` - Data asocierii

**Indexuri:**
- `uk_notif_user` (unic pe `notificare_id`, `utilizator_id`)
- `idx_utilizator_status`
- `idx_notificare`

---

### Tabel: `notificari_comentarii`
**Scop:** Comentarii pe notificări în pagina de detaliu notificare.

**Coloane principale:**
- `id` - Primary Key
- `notificare_id` - ID notificare comentată
- `utilizator_id` - Utilizatorul care a adăugat comentariul
- `comentariu` - Text comentariu
- `notifica_toti` - Flag broadcast către utilizatori (`0/1`)
- `created_at` - Data comentariului

**Regulă business:**
- când `notifica_toti = 1`, notificarea comentată este evidențiată (`highlighted_by_comment = 1`) și se creează/activează vizibilitatea către toți utilizatorii platformei.

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
  └── liste_prezenta_membri (membru_id, nullable)
contacte
  └── liste_prezenta_membri (contact_id, nullable)
liste_prezenta
  └── liste_prezenta_membri (lista_id)
activitati
  └── liste_prezenta (lista_prezenta_id)

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
