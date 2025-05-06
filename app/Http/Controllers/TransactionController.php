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
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\PDF;


class TransactionController extends Controller
{


    public function OrderServiceUser(Request $request)
    {
        Log::info('Begin OrderServiceUser');
    
        // Validasi request
        $this->validate($request, [
            'username'       => 'required|string',
            'nopol'          => 'required|string',
            'tipe'           => 'required|string|in:PICKUP,DELIVERY',
            'lat'            => 'required|numeric',
            'lon'            => 'required|numeric',
            'alamat'         => 'required|string',
            'remark_alamat'  => 'nullable|string',
            'jadwal_service' => 'required|date',
            'keluhan'        => 'nullable|string',
        ]);
        
        $today = Carbon::now()->format('Y-m-d'); 
        
        $latestOrder = DB::connection('mtr')
            ->table('mvm.mvm_service_direct_staging')
            ->whereDate('created_date', $today)
            ->count();
        
        $seq = str_pad($latestOrder + 1, 3, '0', STR_PAD_LEFT);
        $orderNumber = "ORD-" . Carbon::now()->format('Ymd') . "-{$seq}";
    
        $id = DB::connection('mtr')->table('mvm.mvm_service_direct_staging')->insertGetId([
            'order_number'       => $orderNumber,
            'created_by'       => $request->username,
            'nopol'          => $request->nopol,
            'type'           => $request->tipe,
            'lat'            => $request->lat,
            'lon'            => $request->lon,
            'address'         => $request->alamat,
            'remark_address'  => $request->remark_alamat,
            'jadwal_service' => $request->jadwal_service,
            'keluhan'        => $request->keluhan,
            'status'         => 'MENUNGGU VERIFIKASI',

        ]);

        $datafeedback = DB::connection('mtr')
        ->table('mvm.mvm_service_direct_staging as a')
        ->leftJoin('mst.mst_user_vehicle as b', function ($join) use ($request) {
            $join->on('a.nopol', '=', 'b.nopol')
                 ->where('a.created_by', '=', DB::connection('mtr')->raw("b.create_by"));
        })
        ->where('a.id', $id)
        ->select(
            'a.id', 'a.nopol', 'a.type', 'a.lat', 'a.lon', 'a.address', 'a.remark_address',
            'a.jadwal_service', 'a.keluhan', 'a.created_by', 'a.created_date',
            'a.status', 'a.order_number',
            'b.type as tipe_kendaraan', 'b.nomesin', 'b.norangka', 'b.tahun'
        )
        ->first();
        
        
    
        Log::info('OrderServiceUser created with ID: ' . $id);
        Log::info('End OrderServiceUser');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Order berhasil dalam bentuk draft',
            'data'     =>  $datafeedback
        ], 200);
    }
    
 
    public function OrderConfirm(Request $request)
    {
        Log::info('Begin OrderConfirm');
    
        $username = $request->username;
        $order = $request->order_number;
    
        // Ambil data order
        $cariBengkel = DB::connection('mtr')
            ->table('mvm.mvm_service_direct_staging')
            ->where('order_number', $order)
            ->first();
    
        if (!$cariBengkel) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Order tidak ditemukan',
            ], 404);
        }
    
        // Ambil daftar bengkel yang tersedia
        $bengkels = DB::connection('mtr')
            ->table('mst.mst_user_access')
            ->where('role', 'BENGKEL')
            ->where('is_avaliable', true)
            ->select('username', 'lat', 'lon')
            ->get();
    
        function getDistance($lat1, $lon1, $lat2, $lon2)
        {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                    cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            return $dist * 60 * 1.1515 * 1.609344; // dalam kilometer
        }
    
        $terdekat = null;
        $minDistance = INF;
        foreach ($bengkels as $bengkel) {
            $distance = getDistance(
                $cariBengkel->lat,
                $cariBengkel->lon,
                $bengkel->lat,
                $bengkel->lon
            );
    
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $terdekat = $bengkel;
            }
        }
    
        if (!$terdekat) {
            return response()->json([
                'status' => 404,
                'success' => false,
                'message' => 'Tidak ada bengkel tersedia',
            ], 404);
        }

            DB::connection('mtr')
            ->table('mvm.mvm_service_direct_staging')
            ->where('order_number', $order)
            ->where('created_by', $username)
            ->update([
                'status' => 'VERIFIKASI BERHASIL',
                'paired_bengkel' => $terdekat->username,
                'updated_date' => Carbon::now()->format('Y-m-d H:i')
            ]);
    
        Log::info("OrderConfirm updated. Order: {$order}, User: {$username}, Bengkel: {$terdekat->username}");
    
        Log::info('End OrderConfirm');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Order anda sudah di pairing ke pihak bengkel',
            'data'     => [
                'paired_bengkel' => $terdekat->username,
                'distance_km' => round($minDistance, 2)
            ],
        ], 200);
    }

    

    public function OrderListVerifyBengkel(Request $request)
    {
        Log::info('Begin OrderListVerifyBengkel');
    
        $data = DB::connection('mtr')
                ->table('mvm.mvm_service_direct_staging')
                ->where('paired_bengkel', $request->username)
                ->where('is_verify_bengkel', false)
                ->get();

    
        Log::info('End OrderListVerifyBengkel');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'List Order verifikasi',
            'data'     => $data,
        ], 200);
    }


    public function OrderVerifyBengkel(Request $request)
    {
        Log::info('Begin OrderVerifyBengkel');
    
        // Ambil data berdasarkan paired_bengkel dan order_number
        $data = DB::connection('mtr')
                ->table('mvm.mvm_service_direct_staging')
                ->where('paired_bengkel', $request->username)
                ->where('order_number', $request->order_number)
                ->first();
    
        // Cek apakah data ditemukan
        if (!$data) {
            Log::warning('Order tidak ditemukan: ' . $request->order_number);
    
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Order anda tidak ditemukan',
            ], 404);
        }
    
        DB::connection('mtr')
            ->table('mvm.mvm_service_direct_staging')
            ->where('paired_bengkel', $request->username)
            ->where('order_number', $request->order_number)
            ->update(['is_verify_bengkel' => true , 'updated_date' => Carbon::now()->format('Y-m-d H:i')]);


            $serviceNumber = 'SRV-' . $data->nopol . '-' . date('Ymd') . '00' . $data->id;
            $datainsert = [
                'service_number'   => $serviceNumber,
                'order_number'     => $data->order_number,
                'tanggal_service'  => $data->jadwal_service,
                'status'           => 'SERVICE TERJADWAL', 
                'nopol'            => $data->nopol,
                'user_bengkel'     => $data->paired_bengkel,
                'user_order'       => $data->created_by,
                'created_by'       => $data->paired_bengkel,
            ];
        
            // Insert ke mvm_service_user_h
            DB::connection('mtr')
                ->table('mvm.mvm_service_user_h')
                ->insert($datainsert);
    
        Log::info('End OrderVerifyBengkel');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Order verifikasi berhasil, service terbentuk',
            'data'     => $serviceNumber ,
        ], 200);
    }


    public function ActivityServiceList(Request $request)
    {
        Log::info('Begin ActivityServiceList');
    
        $username = $request->username;
    
        $dataList = DB::connection('mtr')
            ->table('mvm.mvm_v_activity_service_list')
            ->where('created_by', $username)
            ->get();
    
        Log::info('End ActivityServiceList');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Order verifikasi berhasil, service terbentuk',
            'data'    => $dataList,
        ]);
    }

    public function ActivityServicedetail(Request $request)
    {
        Log::info('Begin ActivityServicedetail');
    
        $username = $request->username;
        $order_number = $request->order_number;
    
        $dataDetail = DB::connection('mtr')
            ->table('mvm.mvm_v_activity_service_detail')
            ->where('created_by', $username)
            ->where('order_number', $order_number)
            ->first();
    
        Log::info('End ActivityServicedetail');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Detail Data',
            'data'    => $dataDetail,
        ]);
    }

    

    public function OrderServiceUpdate(Request $request)
    {
        Log::info('Begin OrderServiceUpdate');
    
        $username         = $request->username;
        $order_number     = $request->order_number;
        $jadwal_service   = $request->jadwal_service;
        $is_cancel        = $request->is_cancel;
        $remark_pembatalan = $request->remark_pembatalan;
    
        $updateData = [
            'updated_date' => Carbon::now()->format('Y-m-d H:i')
        ];
    
        // Jika pembatalan
        if ($is_cancel === true || $is_cancel === 'true') {
            $updateData['status'] = 'PEMBATALAN';
            $updateData['remark_pembatalan'] = $remark_pembatalan;
        }
    
        // Jika ada jadwal service
        if (!empty($jadwal_service)) {
            $updateData['jadwal_service'] = $jadwal_service;
        }
    
        // Update data
        DB::connection('mtr')
            ->table('mvm.mvm_service_direct_staging')
            ->where('created_by', $username)
            ->where('order_number', $order_number)
            ->update($updateData);
    
        // Ambil data terbaru
        $dataList = DB::connection('mtr')
            ->table('mvm.mvm_v_activity_service_list')
            ->where('created_by', $username)
            ->get();
    
        Log::info('End OrderServiceUpdate');
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Order verifikasi berhasil, service terbentuk',
            'data'    => $dataList,
        ]);
    }


    

}