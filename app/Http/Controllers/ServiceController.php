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
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;


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

    public function PostUploadService(Request $request)
    {
        Log::info('Begin PostUploadService');
        $validated = $this->validate($request, [
            'username'   => 'required|string',
            'spk_d_id'   => 'required|integer',
            'file'       => 'required|file|mimes:jpg,jpeg,png,mp4|max:2048',
            'remark'     => 'required|string',
            'nopol'      => 'required|string',
        ]);

        try {
            $username = $request->input('username');
            $spk_d_id = $request->input('spk_d_id');
            $file = $request->file('file');
            $remark = $request->input('remark');
            $nopol = $request->input('nopol');

            $service_no = 'MVM-' . $nopol . '-' . date("Ymd");
            $destinationPath = storage_path('data/temp/service/' . date("Y") . '/' . date("m") . '/'. $service_no);

            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $key = time();
            $filename = $service_no . '-' . $key . '.' . $file->getClientOriginalExtension();

            $file->move($destinationPath, $filename);

            $filePath = $destinationPath;
            $remark = 'Uploaded by ' . $username;
            $urlimage = env('APP_URL').'/api/v1/get-upload-service-process/'.$service_no . '-' . $key ;

            DB::table('mvm.mvm_temp_upload_service')->insert([
                'spk_d_id'           => $spk_d_id, 
                'filename'     => $service_no . '-' . $key,
                'remark'       => $remark,
                'path'         => $filePath,
                'created_by'   => $username,
                'created_date' => Carbon::now(),
                'ext'    => $file->getClientOriginalExtension(),
                'url_file' => $urlimage
            ]);

            $data = DB::table('mvm.mvm_temp_upload_service')->select('spk_d_id', 'filename', 'remark', 'url_file')
                ->where('spk_d_id', $spk_d_id)
                ->orderBy('created_date', 'desc')
                ->get();

                Log::info('End PostUploadService');
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::info('End PostUploadService');
            return response()->json([
                'success' => false,
                'message' => 'Error uploading file: ' . $e->getMessage(),
            ], 500);
        }
    }
    

    public function getUploadService($filename)
    {
        Log::info('Begin getUploadService');
        try {
            
            $data = DB::table('mvm.mvm_temp_upload_service')
                ->where('filename', $filename)
                ->first();
    

            if (empty($data)) {
                return response()->json([
                    'status'  => 404,
                    'success' => false,
                    'message' => 'File not found',
                    'data'    => []
                ], 404);
            }
    
 
            $allowedImageExtensions = ['jpg', 'jpeg', 'png'];
            $allowedVideoExtensions = ['mp4'];
    
            $path = $data->path.'/'.$filename.$data->ext;

            if (!File::exists($path)) {
                return response()->json([
                    'status'  => 404,
                    'success' => false,
                    'message' => 'File not found on server',
                    'data'    => []
                ], 404);
            }
    
            $file = File::get($path);
            Log::info('End getUploadService');
            if (in_array($data->ext, $allowedImageExtensions)) {
                return response($file, 200)->header('Content-Type', 'image/jpeg');
            } elseif (in_array($data->ext, $allowedVideoExtensions)) {
                return response($file, 200)->header('Content-Type', 'video/mp4');
            } else {
                return response()->json([
                    'status'  => 400,
                    'success' => false,
                    'message' => 'Unsupported file type',
                    'data'    => []
                ], 400);
            }
        } catch (\Exception $e) {
            Log::info('End getUploadService');
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }
    


}
