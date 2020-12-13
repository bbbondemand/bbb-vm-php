<?php declare(strict_types=1);
namespace BBBondemand\Facades;

use Illuminate\Support\Facades\Facade;

class Vm extends Facade {
    protected static function getFacadeAccessor(): string {
        return 'BBBondemand';
    }
}
