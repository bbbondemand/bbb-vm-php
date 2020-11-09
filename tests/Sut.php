<?php declare(strict_types=1);
namespace BBBondemand;

class Sut
{
    public static function customerId(): string
    {
        return getenv('VM_CUSTOMER_ID');
    }

    public static function apiServerBaseUrl(): string
    {
        return Vm::API_SERVER_BASE_URI;
    }
}