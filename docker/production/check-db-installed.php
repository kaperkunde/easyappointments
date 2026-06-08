#!/usr/bin/env php
<?php

/**
 * Check database connectivity and whether Easy!Appointments is installed.
 *
 * Exit codes:
 *   0 - Database reachable and ea_users table exists (matches is_app_installed()).
 *   1 - Database reachable but app is not installed yet.
 *   2 - Database is not reachable.
 */

$config_path = '/var/www/html/config.php';

if (!is_readable($config_path)) {
    fwrite(STDERR, "config.php not found.\n");
    exit(2);
}

require $config_path;

mysqli_report(MYSQLI_REPORT_OFF);

$connection = @new mysqli(
    Config::DB_HOST,
    Config::DB_USERNAME,
    Config::DB_PASSWORD,
    Config::DB_NAME,
);

if ($connection->connect_errno) {
    exit(2);
}

$result = $connection->query("SHOW TABLES LIKE 'ea_users'");

if ($result && $result->num_rows > 0) {
    $connection->close();
    exit(0);
}

$connection->close();
exit(1);
