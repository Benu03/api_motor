<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class InvoiceModel extends Model
{
 
  protected $connection = 'mtr';

  public static function GetListinvoiceBengkel($username)
  {

    $result = DB::table('mvm.mvm_invoice_h')
                ->where('create_by',$username)
                ->whereIn('status',['PROSES','REQUEST'])
                ->get();

    return $result;   
  }


  public static function GetServiceToinvoiceBengkel($IdBengkel)
  {

    $result = DB::table('mvm.v_service_bengkel_invoice')
                ->select('service_no', 'branch', 'nopol','last_km','tanggal_service','jasa_name as jasa','part_name as part')
                ->where('mst_bengkel_id',$IdBengkel)
                ->get();

    return $result;   
  }


  public static function getBengkel($username)
  {

    $result = DB::table('mst.mst_bengkel')
                ->where('pic_bengkel',$username)
                ->first();

    return $result;   
  }

  
  public static function GetDetailinvoiceBengkel($invoice)
  {

      if (!is_numeric($invoice)) {

          $result = DB::table('mvm.v_invoice_detail_bengkel_mbl')
                      ->where('invoice_no', $invoice)
                      ->get();
      } else {

          $result = DB::table('mvm.v_invoice_detail_bengkel_mbl')
                      ->where('id_invoice', $invoice)
                      ->get();
      }
  
      return $result;
  }


  public static function GetInvoice($invoice)
  {
      $query = DB::table('mvm.mvm_invoice_h')
          ->select('invoice_no', 'status', 'created_date', 'jasa_total', 'part_total', 'pph', 
              DB::raw('jasa_total + part_total as total'));
  
      if (!is_numeric($invoice)) {
          $query->where('invoice_no', $invoice);
      } else {
          $query->where('id_invoice', $invoice);
      }
  
      return $query->first();
  }


  public static function CheckServiceInvoice($serviceList)
  {
      $result = DB::table('mvm.mvm_invoice_d')
                  ->whereIn('service_no', $serviceList)
                  ->pluck('service_no')
                  ->toArray();

      return $result; 
  }
  
  
  public static function ChekcInvoicceNo($invoice_no)
  {

    $result = DB::table('mvm.mvm_invoice_h')
                ->where('invoice_no',$invoice_no)
                ->first();

    return $result;   
  }

 

  public static function GetConfig()
  {

    $result = DB::connection('cp')->table('cp.konfigurasi')->first();

    return $result;   
  }

  



  public static function GetinvoiceDataRequest($invoice_no)
  {

    $result = DB::table('mvm.mvm_invoice_h')->select('id','invoice_no','status','created_date','create_by','pph','jasa_total','part_total','ppn')
                ->where('invoice_no',$invoice_no)
                ->first();

    return $result;   
  }

  public static function InvoiceGeneratepdf($invoiceNo)
  {

    $result = DB::table('mvm.v_invoice_generate')->where('invoice_no',$invoiceNo)->distinct()->orderby('service_no','ASC')->orderby('jasa','ASC')->get();

    return $result;   
  }



  

  public static function InsertInvoiceH($dataInvoiceH)
  {

    $id = DB::table('mvm.mvm_invoice_h')->insertGetId($dataInvoiceH);
    return $id;

  }

    
  public static function GetServiceDataInvoice($invoice_no)
  {

    $result = DB::table('mvm.mvm_invoice_d')
    ->selectRaw(" service_no, 
                 SUM(CASE WHEN price_type = 'Jasa' THEN price_bengkel_to_ts3 ELSE 0 END) as jasa, 
                 SUM(CASE WHEN price_type = 'Part' THEN price_bengkel_to_ts3 ELSE 0 END) as part")
    ->where('invoice_no', $invoice_no)
    ->groupBy('service_no')
    ->get();


  
      return $result;
  }

  public static function GetServiceDataInvoiceDetail($invoice)
  {
      $result = DB::table('mvm.mvm_invoice_d as d')
          ->selectRaw("
              d.service_no, 
              b.regional,
              b.area,
              b.branch,
              b.type,
              b.nopol,
              a.last_km,
              a.tanggal_service,
              SUM(CASE WHEN d.price_type = 'Jasa' THEN d.price_bengkel_to_ts3 ELSE 0 END) as jasa, 
              SUM(CASE WHEN d.price_type = 'Part' THEN d.price_bengkel_to_ts3 ELSE 0 END) as part
          ")
          ->leftJoin('mvm.mvm_service_vehicle_h as a', 'd.service_no', '=', 'a.service_no')
          ->leftJoin('mvm.v_spk_detail as b', 'a.mvm_spk_d_id', '=', 'b.id')
          ->where('d.invoice_no', $invoice)
          ->groupBy(
              'd.service_no', 
              'b.regional', 
              'b.area', 
              'b.branch', 
              'b.type', 
              'b.nopol', 
              'a.last_km', 
              'a.tanggal_service'
          )
          ->get();
  
      return $result;
  }
  


}