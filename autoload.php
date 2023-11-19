<?php
function classAutoloader($className)
{
    $classDirectories = [
        __DIR__ . '/Services/',
        __DIR__ . '/Models/'
    ];

    $classFilePath = str_replace('\\', '/', $className) . '.php';

    foreach ($classDirectories as $directory) {
        $file = $directory . $classFilePath;

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Регистрация функции автозагрузки
spl_autoload_register('classAutoloader');
?>