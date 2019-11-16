<?php

namespace decode9\coinbase;

use Illuminate\Support\ServiceProvider;

class CoinbaseServiceProvider extends ServiceProvider 
{
	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/coinbase.php' => config_path('coinbase.php')
		]);
	}

	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/config/coinbase.php', 'kraken');
		$this->app->bind('kraken', function() {
			return new CoinbaseAPI(config('coinbase'));
		});
	}
}
