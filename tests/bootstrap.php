<?php declare(strict_types=1);
namespace BBBondemand\Test;

require __DIR__ . '/../vendor/autoload.php';

function d(...$args): void {
    var_dump(...$args);
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    exit;
}

function startServer(): void {
    require __DIR__ . '/Server.php';

    Server::start();
    register_shutdown_function(function () {
        Server::stop();
    });
}