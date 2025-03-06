<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DB;
use App\Models\ServiceModel;

class ReportController extends Controller
{

    public function HistoryServiceBengkel(Request $request)
    {
        Log::info('Begin HistoryServiceBengkel');
    
        $username = $request->username;
        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    
        $params = $request->param;
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
    
        $usebengkel = DB::table('mst.mst_bengkel')->where('pic_bengkel', $username)->first();
    
        if (!$usebengkel) {
            Log::error("Bengkel not found for username: $username");
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Bengkel not found',
            ], 404);
        }
 
        $query = DB::table('mvm.v_spk_detail')
                    ->select('id', 'nopol', 'status_service','tanggal_service','tanggal_schedule','tgl_last_service')
                    ->where('spk_status','ONPROGRESS')
                    ->where('mst_bengkel_id',$usebengkel->id)
                    ->orderBy('tanggal_schedule', 'desc');

                    

        if ($start_date && $end_date) {
             $query->whereBetween('tanggal_service', [$start_date, $end_date]);
        }
    
        $service = $query->get();


    
        Log::info('End HistoryServiceBengkel');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => $service,
        ], 200);
    }
    

    public function HistoryServiceBengkelDetail(Request $request)
    {
        Log::info('Begin HistoryServiceBengkelDetail');
    
        $username = $request->username;
        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    
        // $serviceno = $params['service_no']

        $id_service =  $request->param['id_service']; 

        if (empty($id_service)) {
            Log::error('id_service is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'id_service No is required',
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


                $part = ServiceModel::Getpart($regional = $dataService[0]->mst_regional_id, $client = $dataService[0]->mst_client_id);  
            
                $jobs = ServiceModel::Getjob($regional = $dataService[0]->mst_regional_id, $client = $dataService[0]->mst_client_id); 

                $upload = DB::table('mvm.mvm_temp_upload_service')->select('spk_d_id', 'filename','ext', 'remark', 'url_file')
                ->where('spk_d_id', $id_service)
                ->orderBy('created_date', 'desc')
                ->get();



                $gps = ServiceModel::Getgps($nopol = $dataService[0]->nopol);

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
                        // 'upload' => $upload,
                        'gps' => $gps
                    ]
                ], 200);
    }

    public function InvoiceBengkel(Request $request)
    {  
        Log::info('Begin InvoiceBengkel');
    
        // Validasi username
        $username = $request->username;
        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    
       
        $params = $request->param;
        $start_date = $params['start_date'] ?? null;
        $end_date = $params['end_date'] ?? null;
    
        // Query utama
        $query = DB::table('mvm.v_rekap_invoice')
                    ->where('invoice_type', 'BENGKEL TO TS3')
                    ->where('create_by', $username)
                    ->orderBy('id', 'desc'); // Urutkan dari terbaru ke lama
    
        // Jika ada rentang tanggal, tambahkan whereBetween
        if ($start_date && $end_date) {
            $query->whereBetween('created_date', [$start_date, $end_date]);
        }
    
        $invoiceList = $query->get();
    
        Log::info('End InvoiceBengkel');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => $invoiceList, // Mengembalikan data hasil query, bukan array kosong
        ], 200);
    }
    
  
    public function GetImageServiceDetail($data)
    {
        Log::info('Begin GetImageServiceDetail');
    
        $item = DB::table('mvm.mvm_service_vehicle_d')->where('unique_data', $data)->first();
    
        if (!$item) {
            return response()->json(['error' => 'File not found'], 404);
        }
    
        $filename = $item->unique_data;
        $filePath = $item->source;
    
        $fullPath = $filePath . '/' . $filename;

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $mimeType = mime_content_type($fullPath);
   
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $supportedExtensions = ['png', 'jpg', 'mp4'];
    
        if (in_array($fileExtension, $supportedExtensions)) {
            return response()->file($fullPath, ['Content-Type' => $mimeType]);
        } else {
            return response()->json(['error' => 'Unsupported file type'], 400);
        }
    }
    

  
  


}