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
use App\Models\InvoiceModel;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\PDF;


class FetchController extends Controller
{

    public function getListPromo(Request $request)
    {  
      
        Log::info('Begin getListPromo');

        $promoBaseUrl = env('APP_URL') . '/image/promo';

        $data = DB::table('mst.mst_promo')
            ->where('is_active', true)
            ->get();
    
        $promoList = [];
    
        foreach ($data as $promo) {
            $promoList[] = [
                'id'       => $promo->id,
                'title'    => $promo->title,
                'filename' => $promo->file_name,
                'url'      => $promoBaseUrl . '/' . $promo->file_name
            ];
        }
    
        Log::info('End getListPromo');
 
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $promoList,
        ], 200);
    }


    public function getListKatalog(Request $request)
    {  
      
        Log::info('Begin getListKatalog');

     
                
        $katalogBaseUrl = env('APP_URL') . '/image/katalog';

        $data = DB::table('mst.mst_katalog')
        ->where('is_active', true)
        ->get();


        $katalogList = [];

        foreach ($data as $katalog) {
            $katalogList[] = [
                'id'       => $katalog->id,
                'title'    => $katalog->title,
                'harga' => $katalog->harga,
                'filename' => $katalog->file_name,
                'url'      => $katalogBaseUrl . '/' . $katalog->file_name
            ];
        }
    
      
    
        Log::info('End getListKatalog');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $katalogList,
        ], 200);
    }

   
     

    public function getBantuan(Request $request)
    {  
      
        Log::info('Begin getBantuan');

        $Bantuan = DB::table('mst.mst_bantuan')->where('is_active', true)->get();
        Log::info('End getBantuan');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $Bantuan,
        ], 200);
    }

  


}