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

    public function GetDetailInvoice(Request $request)
    {  
        Log::info('Begin GetDetailInvoice');

        $username = $request->username;
        $invoice =  $request->param['invoice']; 

        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    

        if (empty($invoice)) {
            Log::error('Invoice is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Invoice is required',
            ], 400);
        }


        $detailInvoice = InvoiceModel::GetDetailinvoiceBengkel($invoice);  

        Log::info('End GetDetailInvoice');
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $detailInvoice,
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

    private function generateInvoiceNumber()
    {
        do {
            $invoice_no = 'INV-' . date("Ymd") . '-' . mt_rand(10000, 99999);
            $existingInvoiceNo = InvoiceModel::ChekcInvoicceNo($invoice_no);
        } while ($existingInvoiceNo);

        return $invoice_no;
    }
    
    public function PostInvoiceProcess(Request $request)
    {  
      
        Log::info('Begin PostInvoiceProcess');

        $username = $request->username;
     
        if (empty($username)) {
            Log::error('Username is missing');
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }

        $serviceList = $request->param['service_list'];
        $invoice_no = $this->generateInvoiceNumber();

        $dataInvoiceH = [
            'invoice_no' => $invoice_no,
            'invoice_type' => 'BENGKEL TO TS3',
            'status' => 'DRAFT',
            'created_date' =>  Carbon::now(),
            'create_by' => $username,
            'remark' => $request->param['remark'],
        ];
        
 
        $serviceListArray = explode(',', $serviceList);
        Log::info($serviceListArray);
        try
        {                 
            DB::beginTransaction();
            $idinvoiceH = InvoiceModel::InsertInvoiceH($dataInvoiceH);
            Log::info($serviceList);
            $serviceDetails = DB::table('mvm.v_service_detail_invoice_bengkel')
            ->whereIn('service_no', $serviceListArray)
            ->Where('detail_type', '<>','Upload')
            ->get()
            ->groupBy('detail_type');

            $service_jasa = $serviceDetails['Pekerjaan'] ?? [];
            $service_part = $serviceDetails['Spare Part'] ?? [];
 
            foreach ($service_jasa as $val) {
                $price = DB::table('mst.mst_price_service')->where('id',intval($val->unique_data))->first();
                if ($price) {
                    DB::table('mvm.mvm_invoice_d')->insert([
                        'mvm_invoice_h_id' => $idinvoiceH,
                        'service_no' => $val->service_no,
                        'created_date' => Carbon::now(),
                        'create_by' => $username,
                        'mst_price_service_id' => $price->kode,
                        'price_bengkel_to_ts3' => $price->price_bengkel_to_ts3,
                        'invoice_no' => $invoice_no,
                        'price_type' => $price->price_service_type,
                        'price_ts3_to_client' => $price->price_ts3_to_client,
                    ]);
                }
            }

            foreach ($service_part as $val) {
                $price = DB::table('mst.mst_price_service')->where('id',intval($val->unique_data))->first();
                if ($price) {
                    DB::table('mvm.mvm_invoice_d')->insert([
                        'mvm_invoice_h_id' => $idinvoiceH,
                        'service_no' => $val->service_no,
                        'created_date' =>Carbon::now(),
                        'create_by' => $username,
                        'mst_price_service_id' => $price->kode,
                        'price_bengkel_to_ts3' => $price->price_bengkel_to_ts3,
                        'invoice_no' => $invoice_no,
                        'price_type' => $price->price_service_type,
                        'price_ts3_to_client' => $price->price_ts3_to_client,
                    ]);
                }
            }

            $sumTotalInvoice = DB::table('mvm.mvm_invoice_d')
            ->selectRaw("ROUND((SUM(CASE WHEN price_type = 'Jasa' THEN price_bengkel_to_ts3 ELSE 0 END) * 2) / 100) as pph, 
                         SUM(CASE WHEN price_type = 'Jasa' THEN price_bengkel_to_ts3 ELSE 0 END) as jasa, 
                         SUM(CASE WHEN price_type = 'Part' THEN price_bengkel_to_ts3 ELSE 0 END) as part")
            ->where('invoice_no', $invoice_no)
            ->first();

            DB::table('mvm.mvm_invoice_h')->where('invoice_no', $invoice_no)->update([
                'status' =>'REQUEST',
                'pph' => $sumTotalInvoice->pph ?? 0,
                'jasa_total' => $sumTotalInvoice->jasa ?? 0,
                'part_total' => $sumTotalInvoice->part ?? 0,
            ]);

            DB::commit();

        }                     
        catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Error in PostInvoiceProcess: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error Submit Process: ' . $e->getMessage(),
            ], 500);
        }

        $service_data_invoice =  InvoiceModel::GetServiceDataInvoice($invoice_no);

        Log::info('End PostInvoiceProcess');
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     =>[
                            'invoice_no' => $invoice_no,
                            'jasa' => (int) $sumTotalInvoice->jasa,
                            'part' => (int) $sumTotalInvoice->part,
                            'pph' => (int) $sumTotalInvoice->pph,
                            'total' => $sumTotalInvoice->jasa + $sumTotalInvoice->part,
                            'service_list' => $service_data_invoice
                         ],
        ], 200);

    }
    

    
  
  


}