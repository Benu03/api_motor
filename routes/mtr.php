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
                //service bengkel
                $mtr->post('get-list-service-bengkel', 'ServiceController@GetListService');
                $mtr->post('get-detail-service-bengkel', 'ServiceController@GetDetailService');
                $mtr->post('post-service-process', 'ServiceController@PostServiceProcess');
                $mtr->post('post-upload-service-process', 'ServiceController@PostUploadService');
                $mtr->post('get-upload-service-temp-process/{filename}', 'ServiceController@getUploadTempService');
                $mtr->post('delete-upload-service-temp-process/{filename}', 'ServiceController@DelUploadTempService');
                $mtr->post('get-upload-service-process/{filename}', 'ServiceController@getUploadService');
                $mtr->post('get-list-upload-service-temp-process', 'ServiceController@getListUploadService');
                
                //invoice bengkel
                $mtr->post('get-list-invoice-bengkel', 'InvoiceController@GetListInvoice');
                $mtr->post('get-list-service-invoice-create-bengkel', 'InvoiceController@GetServiceToInvoice');
                $mtr->post('get-detail-invoice-bengkel', 'InvoiceController@GetDetailInvoice');
                $mtr->post('post-invoice-process-bengkel', 'InvoiceController@PostInvoiceProcess');
                $mtr->post('post-invoice-send-process-bengkel', 'InvoiceController@PostInvoiceSendProcess');
                $mtr->post('post-generate-pdf-invoice', 'InvoiceController@GeneratepdfInvoice');


                $mtr->post('get-list-verify-order-bengkel', 'TransactionController@OrderListVerifyBengkel');
                $mtr->post('post-verify-order-bengkel', 'TransactionController@OrderVerifyBengkel');

                $mtr->group(['prefix' => 'report/'], function () use ($mtr) 
                {
                    $mtr->post('history-service-bengkel', 'ReportController@HistoryServiceBengkel');
                    $mtr->post('history-service-bengkel-detail', 'ReportController@HistoryServiceBengkelDetail');
                    $mtr->post('invoice-bengkel', 'ReportController@InvoiceBengkel');
                    $mtr->post('invoice-bengkel-detail', 'ReportController@InvoiceBengkelDetail');

                });

                
                $mtr->get('bantuan', 'FetchController@getBantuan');
                $mtr->get('list-promo', 'FetchController@getListPromo');
                $mtr->get('list-katalog', 'FetchController@getListKatalog');
                $mtr->post('user-position', 'UsersAssetController@UserPositioin');


                
                $mtr->group(['prefix' => 'user/'], function () use ($mtr) 
                {
                    $mtr->post('list-vehicle', 'UsersAssetController@UserListVehicle');
                    $mtr->post('post-position', 'UsersAssetController@UserPositioin');
                    $mtr->post('detail-vehicle/{id}', 'UsersAssetController@UserVehicleDetail');
                    $mtr->post('delete-vehicle', 'UsersAssetController@UserDeleteVehicle');
                    $mtr->post('list-bengkel-distance', 'UsersAssetController@GetBengkelDistance');
                    $mtr->post('list-bengkel-name', 'UsersAssetController@GetBengkelName');


                    $mtr->post('add-vehicle', 'UsersAssetController@UserAddVehicle');
                    $mtr->post('edit-vehicle', 'UsersAssetController@UsereditVehicle');


                    $mtr->post('list-service', 'TransactionController@ListServiceUser');
                    $mtr->post('order-service', 'TransactionController@OrderServiceUser');
                    $mtr->post('order-confirm', 'TransactionController@OrderConfirm');


                    $mtr->post('activity-service-list', 'TransactionController@ActivityServiceList');
                    $mtr->post('activity-service-detail', 'TransactionController@ActivityServicedetail');
                    $mtr->post('order-service-update', 'TransactionController@OrderServiceUpdate');
                    

                    $mtr->post('riwayat-list', 'RiwayatController@RiwayatListB2C');
                    $mtr->post('riwayat-detail', 'RiwayatController@RiwayatDetailB2C');

                    
                });



            });
        });

});

$mtr->get('api/v1/get-image-service-detail/{data}', 'ReportController@GetImageServiceDetail');