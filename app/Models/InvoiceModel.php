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




  



}



