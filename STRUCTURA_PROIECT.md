# Structura proiectului

Generat automat la data: 2026-03-23.

```text
./
├── .github/
│   └── workflows/
│       └── deploy.yml
├── api/
│   ├── bpa-nr-registratura.php
│   ├── cauta-membri.php
│   ├── cauta-voluntari.php
│   ├── genereaza-document.php
│   ├── incasari-cauta-membri.php
│   ├── incasari-dashboard-salveaza.php
│   ├── incasari-salveaza.php
│   ├── incasari-sterge.php
│   ├── incasari-update.php
│   ├── log-actiune-membru.php
│   ├── log-print-document.php
│   ├── registru-v2-stats.php
│   └── trimite-email-document.php
├── app/
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── recuperare-parola.php
│   │   ├── reset-parola.php
│   │   └── schimba-parola.php
│   ├── controllers/
│   │   ├── activitati/
│   │   │   ├── index.php
│   │   │   └── istoric.php
│   │   ├── administrativ/
│   │   │   └── index.php
│   │   ├── aniversari/
│   │   │   └── index.php
│   │   ├── bpa/
│   │   │   └── index.php
│   │   ├── comunicare/
│   │   │   └── index.php
│   │   ├── contacte/
│   │   │   ├── import.php
│   │   │   ├── index.php
│   │   │   ├── store.php
│   │   │   └── update.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── formular-230/
│   │   │   └── index.php
│   │   ├── fundraising/
│   │   │   └── index.php
│   │   ├── generare-documente/
│   │   │   └── index.php
│   │   ├── import/
│   │   │   ├── actualizeaza-csv.php
│   │   │   └── membri-csv.php
│   │   ├── incasari/
│   │   │   └── index.php
│   │   ├── librarie-documente/
│   │   │   └── index.php
│   │   ├── liste-prezenta/
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   ├── log-activitate/
│   │   │   └── index.php
│   │   ├── membri/
│   │   │   ├── index.php
│   │   │   ├── profil.php
│   │   │   └── store.php
│   │   ├── newsletter/
│   │   │   ├── index.php
│   │   │   └── view.php
│   │   ├── notificari/
│   │   │   ├── index.php
│   │   │   └── view.php
│   │   ├── rapoarte/
│   │   │   └── index.php
│   │   ├── registratura/
│   │   │   ├── index.php
│   │   │   ├── store.php
│   │   │   ├── sumar.php
│   │   │   └── update.php
│   │   ├── registru-interactiuni-v2/
│   │   │   └── index.php
│   │   ├── setari/
│   │   │   └── index.php
│   │   ├── tickete/
│   │   │   ├── edit.php
│   │   │   └── index.php
│   │   ├── todo/
│   │   │   ├── edit.php
│   │   │   ├── index.php
│   │   │   └── store.php
│   │   └── voluntariat/
│   │       └── index.php
│   ├── services/
│   │   ├── ActivitatiService.php
│   │   ├── AdministrativService.php
│   │   ├── AniversariService.php
│   │   ├── BpaService.php
│   │   ├── ComunicareService.php
│   │   ├── ContacteService.php
│   │   ├── DashboardService.php
│   │   ├── DocumenteService.php
│   │   ├── Formular230Service.php
│   │   ├── LibrarieDocumenteService.php
│   │   ├── ListePrezentaService.php
│   │   ├── MembriService.php
│   │   ├── NotificariService.php
│   │   ├── RapoarteService.php
│   │   ├── RegistraturaService.php
│   │   ├── RegistruInteractiuniService.php
│   │   ├── SetariService.php
│   │   ├── TaskService.php
│   │   └── VoluntariatService.php
│   ├── views/
│   │   ├── activitati/
│   │   │   ├── index.php
│   │   │   └── istoric.php
│   │   ├── administrativ/
│   │   │   └── index.php
│   │   ├── aniversari/
│   │   │   └── index.php
│   │   ├── bpa/
│   │   │   └── index.php
│   │   ├── comunicare/
│   │   │   ├── _filtre_membri.php
│   │   │   └── index.php
│   │   ├── contacte/
│   │   │   ├── adauga.php
│   │   │   ├── edit.php
│   │   │   ├── import.php
│   │   │   └── index.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── formular-230/
│   │   │   └── index.php
│   │   ├── fundraising/
│   │   │   └── index.php
│   │   ├── generare-documente/
│   │   │   └── index.php
│   │   ├── import/
│   │   │   ├── actualizeaza-csv.php
│   │   │   └── membri-csv.php
│   │   ├── incasari/
│   │   │   └── index.php
│   │   ├── layout/
│   │   │   ├── footer.php
│   │   │   ├── header.php
│   │   │   └── sidebar.php
│   │   ├── librarie-documente/
│   │   │   └── index.php
│   │   ├── liste-prezenta/
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   ├── log-activitate/
│   │   │   └── index.php
│   │   ├── membri/
│   │   │   ├── form.php
│   │   │   ├── index.php
│   │   │   ├── print.php
│   │   │   ├── profil.php
│   │   │   └── profil.php.bak
│   │   ├── newsletter/
│   │   │   ├── index.php
│   │   │   └── view.php
│   │   ├── notificari/
│   │   │   ├── index.php
│   │   │   └── view.php
│   │   ├── partials/
│   │   │   ├── alert.php
│   │   │   ├── contacte-form-fields.php
│   │   │   ├── membri_form.php
│   │   │   ├── membri_processing.php
│   │   │   └── membru-profil-form.php
│   │   ├── rapoarte/
│   │   │   └── index.php
│   │   ├── registratura/
│   │   │   ├── adauga.php
│   │   │   ├── edit.php
│   │   │   ├── index.php
│   │   │   └── sumar.php
│   │   ├── registru-interactiuni-v2/
│   │   │   └── index.php
│   │   ├── setari/
│   │   │   └── index.php
│   │   ├── tickete/
│   │   │   ├── edit.php
│   │   │   └── index.php
│   │   ├── todo/
│   │   │   ├── adauga.php
│   │   │   ├── edit.php
│   │   │   └── index.php
│   │   └── voluntariat/
│   │       └── index.php
│   └── bootstrap.php
├── cron/
│   ├── aniversari-notificare.php
│   ├── backup-database.php
│   └── newsletter.php
├── css/
│   ├── input.css
│   └── tailwind.css
├── docs/
│   ├── ACTUALIZARE_PLATFORMA_ONLINE.md
│   ├── ANALIZA_REGISTRU_INTERACTIUNI.md
│   ├── AUDIT_CRM_2026.md
│   ├── AUDIT_CRM_2026_ACTUALIZAT.md
│   ├── CONVENTIONS.md
│   ├── DEPANARE_SALVARE_PROFIL.md
│   ├── DOCUMENTATIE_SCHEMA.md
│   ├── IMPLEMENTARE_PRE_PRODUCTIE.md
│   ├── MIGRARE_XAMPP_LA_HOSTING.md
│   ├── MINIRAPORT_ACCESIBILITATE.md
│   ├── MINIRAPORT_REMEDIERE_PROFIL_MEMBRU.md
│   ├── RAPORT_ACCESIBILITATE_SCREEN_READERS.md
│   ├── RAPORT_ANALIZA_CRM_ECHIPA_TESTARE.md
│   ├── RAPORT_BUTOANE_FORMULARE.md
│   ├── RAPORT_COMPATIBILITATE_MOBILE.md
│   ├── RAPORT_CSRF_MODULE.md
│   ├── RAPORT_DUPLICATE_CSV.md
│   ├── RAPORT_LOGGING_COMPLET.md
│   ├── RAPORT_MODIFICARI_SET2.md
│   ├── REGRESSION_CHECKLIST.md
│   ├── TECHNICAL_AUDIT.md
│   └── UPDATE_LOG.md
├── includes/
│   ├── activitati_helper.php
│   ├── administrativ_helper.php
│   ├── auth_helper.php
│   ├── bpa_helper.php
│   ├── cnp_validator.php
│   ├── contacte_helper.php
│   ├── cotizatii_helper.php
│   ├── csrf_helper.php
│   ├── date_helper.php
│   ├── db_helper.php
│   ├── document_helper.php
│   ├── documente_modal.php
│   ├── excel_import.php
│   ├── file_helper.php
│   ├── header_user_menu_modal.php
│   ├── incasari_dashboard_modal.php
│   ├── incasari_helper.php
│   ├── incasari_modal.php
│   ├── librarie_documente_helper.php
│   ├── liste_helper.php
│   ├── log_helper.php
│   ├── mailer_functions.php
│   ├── membri_alerts.php
│   ├── membri_import_helper.php
│   ├── newsletter_helper.php
│   ├── notificari_helper.php
│   ├── platform_helper.php
│   ├── registratura_helper.php
│   ├── registru_interactiuni_v2_helper.php
│   ├── sidebar_user_menu.php
│   ├── tickete_helper.php
│   └── voluntariat_helper.php
├── js/
│   ├── accessibility-enhancements.js
│   ├── form-ux-enhancements.js
│   ├── form-validation.js
│   ├── mobile-navigation.js
│   └── theme-toggle.js
├── util/
│   ├── bpa-tabel-docx.php
│   ├── bpa-tabel-pdf.php
│   ├── bpa-tabel-print.php
│   ├── descarca-document.php
│   ├── descarca-librarie-document.php
│   ├── descarca-notificare-atasament.php
│   ├── export_membri.php
│   ├── incasari-chitanta-pdf.php
│   ├── incasari-chitanta-print.php
│   ├── lista-prezenta-docx.php
│   ├── lista-prezenta-pdf.php
│   ├── lista-prezenta-print.php
│   ├── print-bpa-tabel.php
│   └── print-librarie-document.php
├── .gitignore
├── .htaccess
├── composer.json
├── composer.lock
├── footer.php
├── header.php
├── package-lock.json
├── package.json
├── robots.txt
├── router.php
├── sidebar.php
├── STRUCTURA_PROIECT.md
└── tailwind.config.js
```
