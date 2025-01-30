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

  
  public static function GetDetailinvoiceBengkel($id_invoice)
  {

    $result = DB::table('mvm.v_invoice_detail_bengkel_mbl')
                ->where('id_invoice',$id_invoice)
                ->get();

    return $result;   
  }

  
  
  


}



