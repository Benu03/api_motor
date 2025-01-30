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


class InvoiceController extends Controller
{

    public function GetListInvoice(Request $request)
    {  
      
        Log::info('Begin GetListInvoice');

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
        $dataInvoice = InvoiceModel::GetListinvoiceBengkel($username);  
        $count = [
            'PROSES' => 0,
            'REQUEST' => 0,
        ];
        
        foreach ($dataInvoice as $invoice) {
            if (isset($invoice->status)) {
                if ($invoice->status === 'PROSES') {
                    $count['PROSES']++;
                } elseif ($invoice->status === 'REQUEST') {
                    $count['REQUEST']++;
                }
            }
        }
        
        Log::info('End GetListInvoice');
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => [
                'count_data' => $count,
                'list_invoice' => $dataInvoice
            ],
        ], 200);
    }

  
    public function GetServiceToInvoice(Request $request)
    {  
      
        Log::info('Begin GetServiceToInvoice');

        $username = $request->username;
     
        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    

        $idbengkel = InvoiceModel::getBengkel($username);
        $serviceList = InvoiceModel::GetServiceToinvoiceBengkel($idbengkel->id);  
        
          
        Log::info('End GetServiceToInvoice');
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $serviceList,
        ], 200);

    }


    
  
  


}
