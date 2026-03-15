<?php
/**
 * Compatibility wrapper — Notificari helper
 *
 * sidebar.php si alte fisiere vechi fac require_once pe acest fisier.
 * Delegam totul catre NotificariService.php care contine logica reala.
 * Toate functiile existente raman disponibile (notificari_count_necitate etc.).
 */
require_once __DIR__ . '/../app/services/NotificariService.php';
