<?php
/**
 * Helper pentru numele și versiunea platformei
 */
if (!function_exists('get_platform_version')) {
    function get_platform_version() {
        return defined('PLATFORM_VERSION') ? PLATFORM_VERSION : '1';
    }
}

if (!function_exists('get_platform_name')) {
    function get_platform_name() {
        global $pdo;
        if (!isset($pdo)) {
            return defined('PLATFORM_NAME') ? PLATFORM_NAME : 'ERP ANR BIHOR';
        }
        try {
            $stmt = $pdo->prepare('SELECT valoare FROM setari WHERE cheie = ?');
            $stmt->execute(['platform_name']);
            $name = $stmt->fetchColumn();
            return $name ?: (defined('PLATFORM_NAME') ? PLATFORM_NAME : 'ERP ANR BIHOR');
        } catch (Exception $e) {
            return defined('PLATFORM_NAME') ? PLATFORM_NAME : 'ERP ANR BIHOR';
        }
    }
}
