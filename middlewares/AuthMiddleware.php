<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

function requireRole(string $role): void
{
    requireAnyRole([$role]);
}

function requireAnyRole(array $roles): void
{
    requireLogin();

    $currentRole = (string) ($_SESSION['role'] ?? '');

    if (!in_array($currentRole, $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}