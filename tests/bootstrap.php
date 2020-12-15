<?php declare(strict_types=1);

namespace {
    require __DIR__ . '/../vendor/autoload.php';

    function d(...$args): void {
        var_dump(...$args);
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        exit(1);
    }
}

namespace BBBondemand\Test {
    function startServer(): void {
        Server::start();
        register_shutdown_function(function () {
            Server::stop();
        });
    }
}


