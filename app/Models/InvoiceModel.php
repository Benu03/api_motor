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


}