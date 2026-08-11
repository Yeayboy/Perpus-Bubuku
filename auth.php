<?php

session_start();

function requireLogin(): void
{
    if (empty($_SESSION['user'])) {

        header('Location: login.php');
        exit;
    }
}

function requireRole(array $roles): void
{

    requireLogin();

    if (!in_array($_SESSION['user']['role'], $roles, true)) {

        http_response_code(403);
        exit('Akses ditolak. Anda tidak memiliki izin.');
    }
}