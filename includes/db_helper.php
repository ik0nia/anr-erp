<?php
/**
 * Database Helper — wrappers minimale pentru PDO boilerplate.
 * Funcții pure: $pdo in → result out.
 * Excepțiile PDO sunt lăsate să se propage (rethrow).
 */

/**
 * Fetch o singură linie. Returnează array asociativ sau null.
 */
function db_fetch_one(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row !== false ? $row : null;
}

/**
 * Fetch toate liniile. Returnează array de array-uri asociative.
 */
function db_fetch_all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Execută INSERT/UPDATE/DELETE.
 * INSERT: returnează lastInsertId.
 * UPDATE/DELETE: returnează rowCount.
 */
function db_execute(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if (stripos(ltrim($sql), 'INSERT') === 0) {
        return (int)$pdo->lastInsertId();
    }
    return $stmt->rowCount();
}
