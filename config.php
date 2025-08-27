<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib.php';
$CFG = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . 'config.ini');

if (!is_array($CFG)) {
    echo "Could not parse ini file.\n";
    exit(1);
}

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';