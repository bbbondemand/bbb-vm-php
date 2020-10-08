<?php

namespace BBBondemand\Facades;

use Illuminate\Support\Facades\Facade;

class VM extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'BBBondemand';
    }
}
