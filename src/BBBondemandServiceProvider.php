<?php declare(strict_types=1);

namespace BBBondemand;

use Illuminate\Support\ServiceProvider;

class BBBondemandServiceProvider extends ServiceProvider {
    public function register(): void {

    }

    public function boot(): void {
        $this->app->bind('BBBondemand', function ($app) {
            $conf = [
                'customerId' => getenv('VM_CUSTOMER_ID'),
                'customerApiToken' => getenv('VM_CUSTOMER_API_TOKEN'),
                'baseApiUrl' => getenv('VM_BASE_API_URL'),
            ];
            return Vm::mk($conf);
        });
    }
}