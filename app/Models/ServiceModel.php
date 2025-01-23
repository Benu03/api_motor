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


  public static function Getpart($regional, $client)
  {

        $result = DB::table('mst.v_service_item_motor')
        ->where('price_service_type', 'Part')
        ->where('mst_regional_id', $regional)
        ->where('mst_client_id', $client) 
        ->get();

        return $result;   
  }

  public static function Getjob($regional, $client)
  {

        $result = DB::table('mst.v_service_item_motor')
        ->where('price_service_type', 'Jasa')
        ->where('mst_regional_id', $regional)
        ->where('mst_client_id', $client) 
        ->get();

        return $result;   
  }


  public static function Getgps($nopol)
  {
        $result = DB::table('mst.mst_vehicle_gps')
        ->where('nopol', $nopol)
        ->first();

        return $result;   
  }

  public static function GetBengkel($username)
  {

        $result = DB::table('mst.mst_bengkel')
        ->where('pic_bengkel', $username)
        ->first();

        return $result;   
  }





  

}



