<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$mtr->group(['middleware' => 'key_service'], function () use ($mtr) 
{
        $mtr->group(['prefix' => 'api/'], function () use ($mtr) 
        {
            
            $mtr->group(['prefix' => 'v1/'], function () use ($mtr) 
            {
                $mtr->post('get-list-service-bengkel', 'ServiceController@GetListService');
                $mtr->post('get-detail-service-bengkel', 'ServiceController@GetDetailService');
                
            });
        });

});
