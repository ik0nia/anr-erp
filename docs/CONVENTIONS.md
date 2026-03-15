# Conventii de cod — ERP ANR Bihor (MVC Light)

## Structura directoare

```
public_html/
├── app/                          # Cod organizat MVC light
│   ├── bootstrap.php             # Incarca config, defineste APP_ROOT
│   ├── controllers/              # Request handling (citeste input, apeleaza service, include view)
│   │   └── <modul>/
│   │       ├── index.php         # GET list + POST delete
│   │       ├── store.php         # POST create
│   │       └── update.php        # POST update
│   ├── services/                 # Business logic (validare, CRUD, calcule)
│   │   └── <Modul>Service.php
│   ├── views/                    # HTML pur (primeste date prin variabile)
│   │   ├── <modul>/
│   │   │   ├── index.php         # View lista
│   │   │   ├── form.php          # View formular (shared add/edit)
│   │   │   └── adauga.php / edit.php
│   │   └── partials/             # Componente reutilizabile
│   │       ├── alert.php
│   │       ├── pagination.php
│   │       └── tab-bar.php
│   └── helpers/                  # Functii utilitare pure (fara DB, fara sesiuni)
│
├── includes/                     # Legacy helpers (wrappers compatibilitate)
├── config.php                    # Bootstrap (NU SE MODIFICA)
├── header.php, sidebar.php       # Layout (NU SE MODIFICA)
├── <modul>.php                   # Adaptoare subtiri → app/controllers/<modul>/
└── css/, js/, vendor/            # Assets
```

## Ce merge unde

| Layer | Ce face | Ce NU face |
|-------|---------|-----------|
| **Controller** | Citeste $_GET/$_POST, CSRF, apeleaza service, seteaza $data, include view, redirect PRG | SQL, echo HTML, business logic |
| **Service** | Validare, CRUD DB, logging, calcule. Primeste parametri typed, returneaza array | $_GET/$_POST/$_SESSION, HTML |
| **View** | HTML cu <?php echo ?>, loop-uri, conditii display | SQL, $_POST, business logic |
| **Helper** | Functii pure: formatare, parsare, validare | DB access, sesiuni, output |
| **Adaptor** (root .php) | `require config; require controller` (3-5 linii) | Logica, SQL, HTML |

## Naming

| Element | Conventie | Exemplu |
|---------|-----------|---------|
| Controller dir | kebab-case | `app/controllers/contacte/` |
| Controller file | actiune.php | `index.php`, `store.php`, `update.php` |
| Service | PascalCase + Service | `ContacteService.php` |
| View | modul/actiune.php | `views/contacte/index.php` |
| Partial | views/partials/nume.php | `views/partials/alert.php` |
| Functii CRUD | modul_verb() | `contacte_create($pdo, $data)` |
| Functii service | modul_actiune() | `contacte_list()`, `contacte_tipuri()` |

## Pattern controller

```php
<?php
require_once __DIR__ . '/../../bootstrap.php';
require_once APP_ROOT . '/app/services/ModulService.php';

modul_ensure_table($pdo);

// POST: actiune
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actiune'])) {
    csrf_require_valid();
    $result = modul_create($pdo, $_POST, $_SESSION['utilizator'] ?? 'Sistem');
    if ($result['success']) {
        header('Location: modul.php?succes=1');
        exit;
    }
    $eroare = $result['error'];
}

// GET: date pentru view
$data = modul_list($pdo, ...);

include APP_ROOT . '/header.php';
include APP_ROOT . '/sidebar.php';
include APP_ROOT . '/app/views/modul/index.php';
```

## Pattern service

```php
<?php
require_once __DIR__ . '/../bootstrap.php';

function modul_create(PDO $pdo, array $data, string $utilizator = 'Sistem'): array {
    // Validare
    if (empty($data['camp_obligatoriu'])) {
        return ['success' => false, 'error' => 'Campul X este obligatoriu.'];
    }
    // DB operation
    try {
        $stmt = $pdo->prepare('INSERT INTO ...');
        $stmt->execute([...]);
        log_activitate($pdo, 'modul: Creat ...', $utilizator);
        return ['success' => true, 'id' => (int)$pdo->lastInsertId(), 'error' => null];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Eroare la salvare.'];
    }
}
```

## Pattern adaptor (root entrypoint)

```php
<?php
// modul.php — Adaptor MVC light
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/controllers/modul/index.php';
```

## Module migrate

- [x] Contacte (Phase 1)
- [x] Todo (Phase 2)
- [x] Activitati (Phase 3)
- [x] Registratura (Phase 4)
- [x] Notificari (Phase 5)
- [ ] Voluntariat
- [ ] Librarie documente
- [ ] Rapoarte

## Zone protejate (NU SE MODIFICA fara aprobare)

- config.php
- auth_helper.php
- csrf_helper.php
- membri.php
- setari.php
- administrativ.php
- incasari_helper.php
- setup/install
- cron jobs
