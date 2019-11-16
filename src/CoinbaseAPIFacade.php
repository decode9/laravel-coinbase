<?php

namespace decode9\coinbase;

use Illuminate\Support\Facades\Facade;

class CoinbaseAPIFacade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'coinbase';
	}
}
