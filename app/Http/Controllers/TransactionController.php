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


class TransactionController extends Controller
{


    public function OrderServiceUser(Request $request)
    {
        Log::info('Begin OrderServiceUser');
    
        

        
        Log::info('End OrderServiceUser');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'order berhasil dilakukan',
            'data'     => [],
        ], 200);
    }


    public function ListServiceUser(Request $request)
    {
        Log::info('Begin ListServiceUser');
    
        

        
        Log::info('End ListServiceUser');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Kendaraan berhasil diperbarui',
            'data'     => [],
        ], 200);
    }
    
    

}