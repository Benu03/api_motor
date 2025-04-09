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
use DB;
use Barryvdh\DomPDF\PDF;


class FetchController extends Controller
{

    public function getListPromo(Request $request)
    {  
      
        Log::info('Begin getListPromo');

        $promoPath = base_path('public/image/promo');

        $promoBaseUrl = request()->getSchemeAndHttpHost() . '/image/promo';
    
        if (file_exists($promoPath) && is_dir($promoPath)) {
            $files = scandir($promoPath); // Ambil daftar file dalam folder
    
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($promoPath . '/' . $file)) {
                    $promoList[] = [
                        'filename' => $file,
                        'url'      => $promoBaseUrl . '/' . $file
                    ];
                }
            }
        } else {
            Log::warning('Folder promo tidak ditemukan atau bukan direktori.');
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

        $promoPath = base_path('public/image/katalog');
        // $promoUrl  = url('/image/katalog');
        $promoBaseUrl = request()->getSchemeAndHttpHost() . '/image/promo';
        $promoList = [];
    
        if (file_exists($promoPath) && is_dir($promoPath)) {
            $files = scandir($promoPath); // Ambil daftar file dalam folder
    
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($promoPath . '/' . $file)) {
                    $promoList[] = [
                        'filename' => $file,
                        'desc' =>  "dummy data product",
                        'desc' =>  756000,
                        'url'      => $promoBaseUrl . '/' . $file
                    ];
                }
            }
        } else {
            Log::warning('Folder katalog tidak ditemukan atau bukan direktori.');
        }
    
        Log::info('End getListKatalog');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $promoList,
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