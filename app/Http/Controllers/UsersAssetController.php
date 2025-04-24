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


class UsersAssetController extends Controller
{

    public function UserPositioin(Request $request)
    {  
        Log::info('Begin UserPositioin');
        $username = $request->input('username'); 
        $params   = $request->input('param'); 
        $lat      = $params['lat'] ?? null; 
        $lon      = $params['lon'] ?? null; 
    
        if (empty($username) || empty($lat) || empty($lon)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username and position are required',
            ], 400);
        }


        DB::table('mst.mst_user_access')
        ->where('username', $username)
        ->update(['lat' => $lat, 'lon' => $lon]);


        Log::info('End UserPositioin');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message' => 'Location updated successfully',
            'data'     =>[]
        ], 200);

    }
    

    public function UserListVehicle(Request $request)
    {  
      
        Log::info('Begin UserListVehicle');
        $username = $request->input('username'); 

    
        if (empty($username)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username are required',
            ], 400);
        }


        $ListVehicle = DB::table('mst.mst_user_vehicle')->select('id','nopol','type','merk','tahun')->where('username', $username)->get();

        Log::info('End UserListVehicle');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $ListVehicle,
        ], 200);
    }


    public function UserVehicleDetail($id)
    {  
      
        Log::info('Begin UserVehicleDetail');
     
        $ListVehicle = DB::table('mst.mst_user_vehicle')->where('id', $id)->first();

        Log::info('End UserVehicleDetail');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Request Success',
            'data'     => $ListVehicle,
        ], 200);
    }


    public function UserDeleteVehicle(Request $request)
    {  
        Log::info('Begin UserDeleteVehicle');
    
        $username = $request->input('username'); 
        $id = $request->input('id_vehicle'); 
        
        if (empty($username)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username is required',
            ], 400);
        }
    
        $vehicle = DB::table('mst.mst_user_vehicle')
            ->where('username', $username)
            ->where('id', $id)
            ->first();
    
        if ($vehicle) {
            DB::table('mst.mst_user_vehicle')
                ->where('id', $id)
                ->update([
                    'deleted_by' => $username,
                    'deleted_date' => Carbon::now()
                ]);
    
            Log::info('Vehicle deleted', ['id' => $id, 'deleted_by' => $username]);
            
            return response()->json([
                'status'   => 200,
                'success'  => true,
                'message'  => 'Vehicle deleted successfully',
                'data'     =>[],
            ], 200);
        } 
    
        return response()->json([
            'status'  => 404,
            'success' => false,
            'message' => 'Vehicle not found',
        ], 404);
    }

    

    public function GetBengkelName(Request $request)
    {
        Log::info('Begin GetBengkelName');
    
        $username = $request->input('username'); 
        $params   = $request->input('param'); 
        $name     = $params['name'] ?? null; 
        $lat      = $params['lat'] ?? null; 
        $lon      = $params['lon'] ?? null; 
    
        // Validasi input
        if (empty($username) || empty($lat) || empty($lon)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username and position are required',
            ], 400);
        }
    
        if (empty($name)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Name must be empty',
            ], 400);
        }    

        $bengkels = DB::select("
            SELECT username, fullname, lat, lon, address,
                (6371 * acos(
                    cos((? * PI() / 180)) * cos((lat::FLOAT * PI() / 180)) 
                    * cos((lon::FLOAT * PI() / 180) - (? * PI() / 180)) 
                    + sin((? * PI() / 180)) * sin((lat::FLOAT * PI() / 180))
                )) AS distance
            FROM mst.mst_user_access
            WHERE fullname ILIKE ?
            and role = 'BENGKEL'
            ORDER BY distance ASC 
                LIMIT 10
        ", [$lat, $lon, $lat, "%$name%"]); // gunakan ILIKE untuk pencarian case-insensitive
    
        Log::info('End GetBengkelName');
    
        if (empty($bengkels)) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'No nearby workshops found',
            ], 404);
        }
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => $bengkels,
        ], 200);
    }
    

    public function GetBengkelDistance(Request $request)
    {
        Log::info('Begin GetBengkelDistance');
    
        $username = $request->input('username'); 
        $params   = $request->input('param'); 
        $lat      = $params['lat'] ?? null; 
        $lon      = $params['lon'] ?? null; 
    
        if (empty($username) || empty($lat) || empty($lon)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username and position are required',
            ], 400);
        }
    
        $bengkels = DB::select("
                SELECT username, fullname, lat, lon,
                    (6371 * acos(
                        cos((? * PI() / 180)) * cos((lat::FLOAT * PI() / 180)) 
                        * cos((lon::FLOAT * PI() / 180) - (? * PI() / 180)) 
                        + sin((? * PI() / 180)) * sin((lat::FLOAT * PI() / 180))
                    )) AS distance,address
                FROM mst.mst_user_access
                WHERE role = 'BENGKEL'
                ORDER BY distance ASC
                LIMIT 10
            ", [$lat, $lon, $lat]);
    
        Log::info('End GetBengkelDistance');
 
        if (empty($bengkels)) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'No nearby workshops found',
            ], 404);
        }
    
        return response()->json([
            'status'  => 200,
            'success' => true,
            'message' => 'Request Success',
            'data'    => $bengkels,
        ], 200);
    }
    

    public function UserAddVehicle(Request $request)
    {
        Log::info('Begin UserAddVehicle');
    
        $username = $request->input('username');
        $params   = $request->input('param');
    
        $nopol      = $params['nopol'] ?? null;
        $no_rangka  = $params['no_rangka'] ?? null;
        $no_mesin   = $params['no_mesin'] ?? null;
        $tipe       = $params['tipe'] ?? null;
        $tahun      = $params['tahun'] ?? null;
    

        if (empty($username) || empty($nopol)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username dan Nopol wajib diisi',
            ], 400);
        }
    
        $nopol_normalized = strtoupper(trim($nopol));
    
        $CheckNopol = DB::table('mst.mst_user_vehicle')
            ->where('username', $username)
            ->whereRaw("REPLACE(UPPER(nopol), ' ', '') = ?", [$nopol_normalized])
            ->first();
    
        if ($CheckNopol) {
            return response()->json([
                'status'   => 400,
                'success'  => false,
                'message'  => 'Kendaraan Anda sudah terdaftar',
                'data'     => $CheckNopol,
            ], 400);
        }
    
        // Jika belum, simpan data kendaraan
        DB::table('mst.mst_user_vehicle')->insert([
            'username'   => $username,
            'nopol'      => $nopol_normalized,
            'norangka'  => $no_rangka,
            'nomesin'   => $no_mesin,
            'type'       => $tipe,
            'tahun'      => $tahun,
            'created_date' =>  Carbon::now(),
            'create_by' => $username,
        ]);
    

        $ListVehicle = DB::table('mst.mst_user_vehicle')
            ->where('username', $username)
            ->get();
    
        Log::info('End UserAddVehicle');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Kendaraan berhasil ditambahkan',
            'data'     => $ListVehicle,
        ], 200);
    }


    public function UsereditVehicle(Request $request)
    {
        Log::info('Begin UsereditVehicle');
    
        $username = $request->input('username');
        $params   = $request->input('param');
    
        $nopol      = $params['nopol'] ?? null;
        $no_rangka  = $params['no_rangka'] ?? null;
        $no_mesin   = $params['no_mesin'] ?? null;
        $tipe       = $params['tipe'] ?? null;
        $tahun      = $params['tahun'] ?? null;
    
        // Validasi username dan nopol
        if (empty($username) || empty($nopol)) {
            return response()->json([
                'status'  => 400,
                'success' => false,
                'message' => 'Username dan Nopol wajib diisi',
            ], 400);
        }
    
        // Normalisasi nopol (hilangkan spasi dan ubah ke huruf besar)
        $nopol_normalized = strtoupper(str_replace(' ', '', $nopol));
    
        // Update kendaraan
        $affected = DB::table('mst.mst_user_vehicle')
            ->whereRaw("REPLACE(UPPER(nopol), ' ', '') = ?", [$nopol_normalized])
            ->where('username', $username)
            ->update([
                'norangka'   => $no_rangka,
                'nomesin'    => $no_mesin,
                'type'        => $tipe,
                'tahun'       => $tahun,

            ]);
    
        if ($affected === 0) {
            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Kendaraan tidak ditemukan atau data tidak berubah',
            ], 404);
        }
    
        $ListVehicle = DB::table('mst.mst_user_vehicle')
            ->where('username', $username)
            ->get();
    
        Log::info('End UsereditVehicle');
    
        return response()->json([
            'status'   => 200,
            'success'  => true,
            'message'  => 'Kendaraan berhasil diperbarui',
            'data'     => $ListVehicle,
        ], 200);
    }
    
    

}