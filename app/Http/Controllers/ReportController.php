<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use DB;

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
    
        $query = DB::table('mvm.v_service_history')
                ->where('mst_bengkel_id', $usebengkel->id)
                ->orderBy('id', 'desc'); 

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
    
        $params = $request->param;
        $serviceno = $params['service_no'];

        if (empty($serviceno)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Service No is required',
            ], 400);
        }

        
        $query = DB::table('mvm.v_service_history')->where('service_no', $serviceno)->first();
        $sdetail  = DB::table('mvm.v_service_detail_history')->where('id',$query->mvm_service_vehicle_h_id)->get();
        
        $sdetail = $sdetail->map(function ($item) {
            if ($item->detail_type == 'Upload') {
                $item->url = env('APP_URL') . '/api/v1/get-image-service-detail/' . $item->unique_data;
            } else {
                $item->url = null;
            }
            return $item;
        });

        Log::info('End HistoryServiceBengkelDetail');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => [ 'service' => $query, 'detail_service' =>  $sdetail],
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