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
use App\Models\InvoiceModel;
use Barryvdh\DomPDF\PDF;




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
        Log::info('Begin Notif InvoiceGenerate');
        
        $invoice = $request->invoice_no;
    
        // Ambil data header invoice
        $dataH = DB::connection('mtr')
            ->table('mvm.mvm_invoice_user_h')
            ->where('invoice_no', $invoice)
            ->first();
    
        // Ambil data detail invoice
        $dataD = DB::connection('mtr')
            ->table('mvm.mvm_invoice_user_d')
            ->where('invoice_no', $invoice)
            ->get();
    
        // Jika invoice tidak ditemukan
        if (!$dataH) {
            return response()->json([
                'status'    => 404,
                'success'   => false,
                'message'   => 'Invoice tidak ditemukan',
                'data'      => null
            ], 404);
        }
    

        $responseData = [
            'invoice_no'    => $dataH->invoice_no,
            'status' => $dataH->status ?? '-',
            'invoice_date'          => $dataH->created_date ?? '-',
            'jasa_total'         => $dataH->jasa_total ?? 0,
            'part_total'         => $dataH->part_total ?? 0,
            'pph'         => $dataH->pph ?? 0,
            'ppn'         => $dataH->ppn ?? 0,
            'remark'          => $dataH->remark ?? '-',
            'items'         => $dataD->map(function($item) {
                return [
                    'tipe'      => $item->tipe_harga ?? '-',
                    'nama'      => $item->nama ?? '-',
                    'qty'       => $item->qty ?? 0,
                    'harga'     => $item->harga ?? 0,
                  'subtotal' => ($item->qty ?? 0) * ($item->harga ?? 0),

                ];
            }),
            'pdf_url' => url('api/v1/user/invoice-pdf/' . $dataH->invoice_no, [], true)
        ];
    
        Log::info('End Notif InvoiceGenerate');
    
        return response()->json([
            'status'    => 200,
            'success'   => true,
            'message'   => 'Request Success',
            'data'      => $responseData
        ], 200);
    }


    public function InvoiceGeneratePDF($data)
    {
        Log::info('Begin Notif InvoiceGeneratePDF');

        $service =  DB::connection('mtr')
        ->table('mvm.mvm_service_user_h')
        ->where('invoice_no', $data)
        ->first();




        $invoice = DB::connection('mtr')
        ->table('mvm.mvm_invoice_user_h')
        ->where('invoice_no', $data)
        ->first();


     
             $invoice_detail = DB::connection('mtr')
            ->table('mvm.mvm_invoice_user_d')
            ->where('invoice_no', $data)
            ->get();
            
            $config =  InvoiceModel::GetConfig();
    


            $pdf = app('dompdf.wrapper');
            $pdf->loadView('pdf/invoice_generate_user', [
                'invoice'        => $invoice,
                'invoice_detail' => $invoice_detail,
                'bengkel'        => $service,
                'config'         => $config
            ])->setPaper('a4', 'landscape');
    
            // Render PDF
            $pdf->render();
            $canvas = $pdf->getDomPDF()->getCanvas();
    
            // Ambil ukuran halaman PDF
            $w = $canvas->get_width();
            $h = $canvas->get_height();
    
            // Tambahkan watermark (logo perusahaan)
            $imageURL = storage_path('data/image/logo_pdf.png');
            $imgWidth = 300;
            $imgHeight = 200;
    
            // Set opacity logo watermark
            $canvas->set_opacity(0.1);
    
            // Posisi tengah halaman
            $x = ($w - $imgWidth) / 2;
            $y = ($h - $imgHeight) / 2;
    
            // Tambahkan gambar watermark
            $canvas->image($imageURL, $x, $y, $imgWidth, $imgHeight);
    
            // Download PDF dengan nama file sesuai invoice
            return $pdf->download($data . '.pdf');


        
    }
    
  
  
  


}