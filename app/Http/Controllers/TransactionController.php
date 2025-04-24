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
            'status'         => 'DRAFT',

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
    


    public function ListServiceUser(Request $request)
    {
        Log::info('Begin ListServiceUser');
    
        

        
        Log::info('End ListServiceUser');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Kendaraan berhasil diperbarui',
            'data'     => [],
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
                'status' => 'CONFIRM',
                'paired_bengkel' => $terdekat->username,
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


}