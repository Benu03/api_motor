<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\ServiceModel;




class ServiceController extends Controller
{

  

    public function GetListService(Request $request)
    {  
        Log::info('Begin GetListService');

        $username = $request->username;
    
        if (empty($username)) {
        
            Log::error('Username is missing');
    
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    
        Log::info('Username received: ' . $username);
        $dataService = ServiceModel::GetListServiceBengkel($username);       
        Log::info('End GetListService');
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => [
                'list_service' => $dataService
            ],
        ], 200);
    }


    public function GetDetailService(Request $request)
    {  
        Log::info('Begin GetDetailService');
    
        $username = $request->username;
        $id_service =  $request->param['id_service']; 

        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    

        if (empty($id_service)) {
            Log::error('id_service is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'id_service is required',
            ], 400);
        }
    
        if (!is_numeric($id_service)) {
            Log::error('Invalid id_service format');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'id_service must be a valid number',
            ], 400);
        }
    

        $dataService = ServiceModel::GetDetailServiceBengkel($username, $id_service);  
    
        Log::info('End GetDetailService');
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $dataService,  
        ], 200);
    }
    
  
  


}
