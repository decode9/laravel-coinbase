# laravel-coinbase
Coinbase Wallet API for Laravel

## Install

#### Install via Composer

```
composer require decode9/laravel-coinbase
```

Add the following lines to your `config/app.php`

```php
'providers' => [
        ...
        decode9\coinbase\CoinbaseServiceProvider::class,
        ...
    ],


 'aliases' => [
        ...
        'Coinbase' => decode9\coinbase\CoinbaseAPIFacade::class,
    ],
```

## Version

0.0.1
