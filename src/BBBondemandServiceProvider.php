<?php

namespace BBBondemand;

use Illuminate\Support\ServiceProvider;

class BBBondemandServiceProvider extends ServiceProvider
{
	public function register()
	{

	}

	public function boot()
	{
		$this->app->bind('BBBondemand', function($app) {
			return new VM();
		});
	}
}