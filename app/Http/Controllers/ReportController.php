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

    public function HistoryService(Request $request)
    {
        Log::info('Begin HistoryService');
    
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
    
        Log::info('End HistoryService');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => $service,
        ], 200);
    }
    

    public function Invoice(Request $request)
    {  
        Log::info('Begin Invoice');
    
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
    
        Log::info('End Invoice');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => $invoiceList, // Mengembalikan data hasil query, bukan array kosong
        ], 200);
    }
    
  

  
  


}