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
                $mtr->post('post-service-process', 'ServiceController@PostServiceProcess');
                $mtr->post('post-upload-service-process', 'ServiceController@PostUploadService');
                $mtr->post('get-upload-service-temp-process/{filename}', 'ServiceController@getUploadTempService');
                $mtr->post('delete-upload-service-temp-process/{filename}', 'ServiceController@DelUploadTempService');
                $mtr->post('get-upload-service-process/{filename}', 'ServiceController@getUploadService');
                $mtr->post('get-list-upload-service-temp-process', 'ServiceController@getListUploadService');
                

                
            });
        });

});
