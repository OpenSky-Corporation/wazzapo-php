<?php
// В своём проекте после `composer require wazzapo/wazzapo-php` достаточно require 'vendor/autoload.php'.
// Здесь примеры лежат рядом с исходниками SDK и берут их автозагрузчик.
$candidates = [__DIR__ . '/vendor/autoload.php', __DIR__ . '/../vendor/autoload.php'];
foreach ($candidates as $file) {
    if (file_exists($file)) {
        require $file;
        return;
    }
}
fwrite(STDERR, "Нет vendor/autoload.php: выполните composer install\n");
exit(1);
