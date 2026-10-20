<?php
/**
 * Root Login Redirector
 * Forwards requests to auth/login.php preserving all parameters (e.g. role, return_url).
 */
require_once __DIR__ . '/config.php';

$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . url('auth/login.php' . $queryString), true, 302);
exit;
