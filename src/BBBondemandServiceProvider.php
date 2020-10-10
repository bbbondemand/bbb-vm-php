<?php

    namespace BBBondemand;

    use Illuminate\Support\ServiceProvider;

    class BBBondemandServiceProvider extends ServiceProvider
    {
        public function register(): void
        {

        }

        public function boot(): void
        {
            $this->app->bind('BBBondemand', function ($app) {
                return new VM();
            });
        }
    }