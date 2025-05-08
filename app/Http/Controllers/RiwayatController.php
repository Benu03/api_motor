<?php


namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;




class RiwayatController extends Controller
{


    public function RiwayatListB2C(Request $request)
    {    
        log::info('End Notif RiwayatListB2C ');
      
        $username = $request->username;
        $threeMonthsAgo = Carbon::today()->subMonths(3);
        
        
        $dataList = DB::connection('mtr')
        ->table('mvm.mvm_v_riwayat_list')
        ->where('created_by', $username)
        ->whereDate('created_date', '>=', $threeMonthsAgo->toDateString())
        ->get();
  
      log::info('End Notif RiwayatListB2C ');
      return response()->json(
          [   'status'       =>  200,
              'success'   =>  true,
              'message'   =>  'Request Success',
              'data'      =>  $dataList
          ], 200);
    }
    
    public function RiwayatDetailB2C(Request $request)
    {    
        Log::info('Start Notif RiwayatDetailB2C');
    
        $username = $request->username;
        $service_number = $request->service_number;
    
        // Ambil data utama
        $dataList = DB::connection('mtr')
            ->table('mvm.mvm_v_riwayat_list')
            ->where('created_by', $username)
            ->where('service_number', $service_number)
            ->first();
    
        // Ambil detail part dan jasa
        $partjasa = DB::connection('mtr')
            ->table('mvm.mvm_v_riwayat_detail')
            ->where('service_number', $service_number)
            ->get();
    
        Log::info('End Notif RiwayatDetailB2C');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => [
                'header' => $dataList,
                'detail' => $partjasa
            ]
        ], 200);
    }
    

  

    
    public function InvoiceGenerate(Request $request)
    {  
      
  
      log::info('End Notif InvoiceGenerate');
      return response()->json(
          [   'status'       =>  200,
              'success'   =>  true,
              'message'   =>  'Request Success',
              'data'      =>  []
          ], 200);
    }

  
  
  


}