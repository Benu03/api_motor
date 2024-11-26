<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ServiceModel extends Model
{
 
  protected $connection = 'mtr';

  public static function GetListServiceBengkel($username)
  {

    $bengkel 	= DB::table('mst.mst_bengkel')->where('pic_bengkel',$username)->first();

    $result = DB::table('mvm.v_spk_detail')
                ->select('id', 'nopol', 'status_service','tanggal_service','tanggal_schedule','tgl_last_service')
                ->where('spk_status','ONPROGRESS')
                ->wherein('status_service',['ONSCHEDULE'])
                ->where('mst_bengkel_id',$bengkel->id)
                ->orderBy('tanggal_schedule', 'desc')
                ->get();
    return $result;   
  }


  
  public static function GetDetailServiceBengkel($username, $id_service)
  {

        $result = DB::table('mvm.v_spk_detail')
                    ->where('id',$id_service)
                    ->get();
        return $result;   
  }


  



}



