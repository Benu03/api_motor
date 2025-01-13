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
        Log::info('Begin PostServiceProcess');
    
        // Validasi Input
        $validated = $this->validate($request, [
            'param.spk_d_id'        => 'required|integer',
            'param.tanggal_service' => 'required|date',
            'param.nama_driver'     => 'required|string',
            'param.last_km'         => 'required|numeric',
            'param.mekanik'         => 'required|string',
            'param.nama_stnk'       => 'required|string',
            'param.nopol'           => 'required|string',
            'param.remark_driver'   => 'required|string',
            'param.pic_branch'      => 'required|string',
            'param.pekerjaan_data'  => 'required',
            'param.part_data'       => 'required',
   
        ]);

    
        $bengkel = DB::connection('mtr')->table('mst.mst_bengkel')
            ->where('pic_bengkel', $request->username)
            ->first();
    
        if (!$bengkel) {
            return response()->json(['success' => false, 'message' => 'Bengkel tidak ditemukan'], 404);
        }
    
        $service_no = 'MVM-' . $request->param['nopol'] . '-' . date("Ymd");
    
        try {
            DB::beginTransaction();
    
            $service_id = DB::connection('mtr')->table('mvm.mvm_service_vehicle_h')->insertGetId([
                'mvm_spk_d_id'   => $request->param['spk_d_id'],
                'tanggal_service' => $request->param['tanggal_service'],
                'nama_driver'     => $request->param['nama_driver'],
                'last_km'         => $request->param['last_km'],
                'mekanik'         => $request->param['mekanik'],
                'created_date'    => Carbon::now(),
                'user_created'    => $request->username,
                'service_no'      => $service_no,
                'remark_driver'   => $request->param['remark_driver'],
                'pic_branch'      => $request->param['pic_branch'],
            ]);
    
       
            foreach ($request->param['pekerjaan_data'] as $key => $data) {
                $datajobs = [
                    'mvm_service_vehicle_h_id' => $service_id,
                    'detail_type' => 'Pekerjaan',
                    'unique_data' => $data['id'],
                    'value_data' => $data['remark'],
                    'source' => 'mst_price_service (Jasa)',
                    'created_date' => date("Y-m-d H:i:s"), 
                    'user_created' => $request->username
                ];
                DB::connection('mtr')->table('mvm.mvm_service_vehicle_d')->insert($datajobs);
            }

            foreach ($request->param['part_data'] as $key => $data) {
                $datapart = [
                    'mvm_service_vehicle_h_id' => $service_id,
                    'detail_type' => 'Spare Part',
                    'unique_data' => $data['id'], 
                    'value_data' => $data['remark'],
                    'source' => 'mst_price_service (Part)',
                    'created_date' => date("Y-m-d H:i:s"), 
                    'user_created' => $request->username
                ];
                DB::connection('mtr')->table('mvm.mvm_service_vehicle_d')->insert($datapart);
            }
            
    
            $dataUpload = DB::connection('mtr')->table('mvm.mvm_temp_upload_service')
                ->where('spk_d_id', $request->param['spk_d_id'])
                ->get();
    
    
            foreach ($dataUpload as $data) {
                $destinationPath = storage_path('data/service/' . date("Y") . '/' . date("m") . '/') . $service_no;
                $urlfile = env('APP_URL').'/api/v1/get-upload-service-process/'.$data->filename;
               
                $dataUploadInsert = [
                    'mvm_service_vehicle_h_id' => $service_id,
                    'detail_type' => 'Upload',
                    'unique_data' => $data->filename . '.' . $data->ext,
                    'value_data' => $data->remark,
                    'source' => $destinationPath,
                    'created_date' => Carbon::now(),
                    'user_created' => $request->username,
                    'url_upload' => $urlfile
                ];
                DB::connection('mtr')->table('mvm.mvm_service_vehicle_d')->insert($dataUploadInsert);
    

                $sourcePath = $data->path . '/' . $data->filename . '.' . $data->ext;
                if (!file_exists($destinationPath)) {
                    if (!is_dir($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
                }

                if (file_exists($sourcePath)) {
                    if (rename($sourcePath, $destinationPath)) {
                        $logMessage = "Success: File moved from $sourcePath to $destinationPath.";
                    } else {
     
                        $logMessage = "Error: Failed to move file from $sourcePath to $destinationPath.";
                    }
                } else {
                    $logMessage = "Error: File not found in temp folder: $sourcePath.";
                    ;
                }



            }
    
 
            DB::connection('mtr')->table('mvm.mvm_spk_d')
                ->where('id', $request->param['spk_d_id'])
                ->update([
                    'tanggal_service' => $request->param['tanggal_service'],
                    'status_service' => 'SERVICE',
                    'updated_at' =>Carbon::now(),
                    'update_by' => $request->username,
                ]);
    
    
            DB::connection('mtr')->table('mst.mst_vehicle')
                ->where('nopol', $request->param['nopol'])
                ->update([
                    'tgl_last_service' => $request->param['tanggal_service'],
                    'nama_stnk' => $request->param['nama_stnk'],
                    'last_km' => $request->param['last_km'],
                    'updated_at' => Carbon::now(),
                    'update_by' => $request->username,
                ]);
    
            DB::connection('mtr')->table('mvm.mvm_gps_process')
                ->where('nopol', $request->param['nopol'])
                ->where('status', 'pemasangan')
                ->whereNull('service_no')
                ->update([
                    'status' => 'service',
                    'service_no' => $service_no,
                ]);
    
            DB::connection('mtr')->table('mvm.mvm_service_history')->insert([
                'mvm_service_vehicle_h_id' => $service_id,
                'mst_bengkel_id' => $bengkel->id,
                'pic_branch' => $request->param['pic_branch'],
            ]);
    
            DB::commit();
            Log::info('End PostServiceProcess');
    

            return response()->json([
                'status' => 200,
                'success' => true,
                'message' => 'Request Success',
                'data' =>  [
                    'service_no' => $service_no,
                    'log_message' => $logMessage 
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PostServiceProcess: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error Submit Process: ' . $e->getMessage()], 500);
        }
    
       
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
            $urlimage = env('APP_URL').'/api/v1/get-upload-service-temp-process/'.$service_no . '-' . $key ;

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



            $data = DB::table('mvm.mvm_temp_upload_service')->select('spk_d_id', 'filename','ext', 'remark', 'url_file')
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
    

    public function getUploadTempService($filename)
    {
        Log::info('Begin getUploadTempService');
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
    
            $path = $data->path.'/'.$filename.'.'.$data->ext;
      
            if (!File::exists($path)) {
                return response()->json([
                    'status'  => 404,
                    'success' => false,
                    'message' => 'File not found on server',
                    'data'    => []
                ], 404);
            }
    
            $file = File::get($path);
            Log::info('End getUploadTempService');
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
            Log::info('End getUploadTempService');
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    
    public function DelUploadTempService($filename)
    {
        Log::info('Begin DelUploadTempService');
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
    
            $filePath = $data->path . '/' . $data->filename . '.' . $data->ext;
        
                // Check if the file exists on the server
                if (!File::exists($filePath)) {
                    return response()->json([
                        'status'  => 404,
                        'success' => false,
                        'message' => 'File not found on server',
                        'data'    => []
                    ], 404);
                }

                // Delete the file from the server
                File::delete($filePath);
                
                // Delete the record from the database
                DB::table('mvm.mvm_temp_upload_service')
                    ->where('filename', $filename)
                    ->delete();
                
                Log::info('File deleted successfully: ' . $filePath);

                // Return success response
                return response()->json([
                    'status'  => 200,
                    'success' => true,
                    'message' => 'File deleted successfully',
                    'data'    => []
        ], 200);
            
        } catch (\Exception $e) {
            Log::info('End DelUploadTempService');
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }






    public function getUploadService($filename)
    {
        Log::info('Begin getUploadTempService');
        try {
            
            $data = DB::table('mvm.mvm_service_vehicle_d')
            ->where('detail_type', 'Upload')
            ->whereRaw('unique_data ILIKE ?', ["%$filename%"])
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
    
            $path = $data->source.'/'.$data->unique_data;
      
            if (!File::exists($path)) {
                return response()->json([
                    'status'  => 404,
                    'success' => false,
                    'message' => 'File not found on server',
                    'data'    => []
                ], 404);
            }
    
            $file = File::get($path);
            Log::info('End getUploadTempService');
            $fileExtension = pathinfo($data->unique_data, PATHINFO_EXTENSION);
            if (in_array($fileExtension, $allowedImageExtensions)) {
                return response($file, 200)->header('Content-Type', 'image/jpeg');
            } elseif (in_array($fileExtension, $allowedVideoExtensions)) {
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
            Log::info('End getUploadTempService');
            return response()->json([
                'status'  => 500,
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

       


}
