<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DB;
use App\Models\ServiceModel;
use Illuminate\Support\Facades\Cache;

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
    
    
        $query = DB::table('mvm.mvm_service_vehicle_h')
        ->select([
            'id as id_service',
            'service_no',
            'mvm_spk_d_id',
            'tanggal_service',
            'nama_driver',
            'last_km',
            'mekanik',
            'user_created',
            'created_date',
            'remark_driver',
            'pic_branch',
            'remark_pic_branch',
            'pic_branch_date_post',
            'remark_admin_client',
            'admin_client_date_post'
        ])
        ->where('user_created', $username)
        ->orderBy('tanggal_service', 'desc');

                    

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
    
        $id_service = $request->param['id_service'] ?? null; 
    
        if (empty($id_service)) {
            Log::error('id_service is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'id_service No is required',
            ], 400);
        }
    
        // Gunakan cache untuk menghindari query berulang jika data tidak berubah
        $cacheKey = "service_detail_{$id_service}";
        $dataService = Cache::remember($cacheKey, 300, function () use ($id_service) {
            return DB::table('mvm.mvm_service_vehicle_h')
                ->select([
                    'id as id_service',
                    'service_no',
                    'mvm_spk_d_id',
                    'tanggal_service',
                    'nama_driver',
                    'last_km',
                    'mekanik',
                    'user_created',
                    'created_date',
                    'remark_driver',
                    'pic_branch',
                    'remark_pic_branch',
                    'pic_branch_date_post',
                    'remark_admin_client',
                    'admin_client_date_post'
                ])
                ->where('id', $id_service)
                ->first();
        });
    
        if (!$dataService) {
            Log::error('Data service not found for ID: ' . $id_service);
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Service data not found',
                'data' => []
            ], 404);
        }
    
        $part = DB::table('mvm.mvm_service_vehicle_d as a')
        ->leftJoin('mst.mst_price_service as b', DB::raw('CAST(a.unique_data AS BIGINT)'), '=', 'b.id')
        ->select([
            DB::raw('CAST(a.unique_data AS BIGINT) AS id'),
            'b.kode_new',
            'b.service_name',
            'a.value_data AS remark'
        ])
        ->where('a.detail_type', 'Spare Part')
        ->where('a.mvm_service_vehicle_h_id', $id_service)
        ->get();
    
    // Query Pekerjaan
    $jobs = DB::table('mvm.mvm_service_vehicle_d as a')
        ->leftJoin('mst.mst_price_service as b', DB::raw('CAST(a.unique_data AS BIGINT)'), '=', 'b.id')
        ->select([
            DB::raw('CAST(a.unique_data AS BIGINT) AS id'),
            'b.kode_new',
            'b.service_name',
            'a.value_data AS remark'
        ])
        ->where('a.detail_type', 'Pekerjaan')
        ->where('a.mvm_service_vehicle_h_id', $id_service)
        ->get();
    
        // Query Upload
        $upload = DB::table('mvm.mvm_service_vehicle_d')
            ->select([
                'unique_data as file_name',
                'value_data as remark',
                'url_upload'
            ])
            ->where('detail_type', 'Upload')
            ->where('mvm_service_vehicle_h_id', $id_service)
            ->get();
    
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
                'upload' => $upload,
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