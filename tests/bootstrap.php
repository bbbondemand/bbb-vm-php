<?php declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

function d(...$args): void {
    var_dump(...$args);
    exit;
}