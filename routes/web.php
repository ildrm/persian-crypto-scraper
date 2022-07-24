<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\CryptoController;

/**
 *
 */
$router->get('test', [
    'as' => 'test', 'uses' => 'CryptoController@test'
]);

/**
 *
 */
$router->group(['prefix' => 'list'], function () use ($router) {
    /**
     *
     */
    $router->get('coins', function () {
        $crypto = new CryptoController;
        return response()->json(['coins' => $crypto->getCoins()]);
    });

    /**
     *
     */
    $router->get('markets', function () {
        $crypto = new CryptoController;
        return response()->json(['markets' => $crypto->getMarkets()]);
    });
});

/**
 *
 */
$router->group(['prefix' => 'get'], function () use ($router) {
    /**
     *
     */
    $router->get('coin-name/{sign}', function ($sign=null) {
        $crypto = new CryptoController;
        return response()->json(['coin' => $crypto->getCoins(strtoupper($sign))]);
    });

    /**
     *
     */
    $router->get('coin-symbol/{name}', function ($name=null) {
        $crypto = new CryptoController;
        return response()->json(['coin' => $crypto->getCoins($name,true)]);
    });
});

/**
 *
 */
$router->group(['prefix' => 'market'], function () use ($router) {
    /**
     *
     */
    $router->get('list[/{sign}[/{sort}[/{order}]]]', function ($sign=null,$sort=null,$order=null) {
        $crypto = new CryptoController;
        return response()->json($crypto->getAllPrices($sign,$sort,$order));
    });

    /**
     *
     */
    $router->get('{market}[/{sign}]', function ($market=null, $sign=null) {
        $crypto = new CryptoController;
        return response()->json(['data' => $crypto->getPrice(strtolower($market), strtoupper($sign))]);
    });
});

/**
 *
 */
$router->group(['prefix' => 'analyse'], function () use ($router) {
    /**
     *
     */
    $router->get('{sign}', [
        'uses' => 'CryptoController@analyse'
    ]);
});

/**
 *
 */
$router->get('/', function () use ($router) {
    return response()->json([
        'version' => array(
            'api'=>'1.0.0',
            'lumen'=>$router->app->version()
        ),
        'swagger'=>$_SERVER['HTTP_HOST'].'/docs/index.htm'
    ]);
});
