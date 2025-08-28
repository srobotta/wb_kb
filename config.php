<?php

$CFG = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . 'config.ini');

if (!is_array($CFG)) {
    echo "Could not parse ini file.\n";
    exit(1);
}

// Autoloader for the vendor directory.
require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Autoloader for the classes of this project.
spl_autoload_register(function($className) {
    if (strpos($className, 'KnowledgeBase\\') === 0) {
        // Remove the leading KnowledgeBase\ and transform all backslashes to directory separators.
        // KnowledgeBase\Kb\Article -> Kb/Article.php
        $file = str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 13)) . '.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $file;
    }
});