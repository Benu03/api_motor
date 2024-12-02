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
use DB;




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

                    
                if (empty($dataService)) {
                    Log::error('Data service not found for ID: ' . $id_service);
                    return response()->json([
                        'status' => 404,
                        'success' => false,
                        'message' => 'Service data not found',
                        'data' => []
                    ], 404);
                }

                $part = DB::connection('mtr')
                ->table('mst.v_service_item_motor')
                ->where('price_service_type', 'Part')
                ->where('mst_regional_id', $dataService[0]->mst_regional_id)
                ->where('mst_client_id', $dataService[0]->mst_client_id) 
                ->get();
            
                 $jobs = DB::connection('mtr')
                ->table('mst.v_service_item_motor')
                ->where('price_service_type', 'Jasa')
                ->where('mst_regional_id', $dataService[0]->mst_regional_id)
                ->where('mst_client_id', $dataService[0]->mst_client_id)
                ->get();

                $gps = DB::connection('mtr')->table('mst.mst_vehicle_gps')
                    ->where('nopol', $dataService[0]->nopol)
                    ->first();

                Log::info('End GetDetailService', [
                    'id_service' => $id_service,
                    'username' => $username
                ]);

                return response()->json([
                    'status' => 200,
                    'success' => true,
                    'message' => 'Request Success',
                    'data' => [
                        'service' => $dataService,
                        'part' => $part,
                        'jobs' => $jobs,
                        'gps' => $gps
                    ]
                ], 200);
    }
    
  

    public function PostServiceProcess(Request $request)
    {
        
    }
    
  


}
