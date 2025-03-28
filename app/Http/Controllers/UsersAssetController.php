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

    // public function GetBengkelDistance(Request $request)
    // {
    //     Log::info('Begin GetBengkelDistance');
    
    //     $username = $request->input('username'); 
    //     $params   = $request->input('param'); 
    //     $lat      = $params['lat'] ?? null; 
    //     $lon      = $params['lon'] ?? null; 
    
    //     if (empty($username) || empty($lat) || empty($lon)) {
    //         return response()->json([
    //             'status'  => 400,
    //             'success' => false,
    //             'message' => 'Username and position are required',
    //         ], 400);
    //     }
        

    //     $bengkels = DB::table('mst.mst_user_access')
    //               ->where('role', 'BENGKEL')
    //               ->get(['lat', 'lon', 'fullname','username']); 

    //     $distances = [];

    //     foreach ($bengkels as $bengkel) {
    //         $bengkelLat = $bengkel->lat;
    //         $bengkelLon = $bengkel->lon;

    //         try {
                
    //             $url = "http://router.project-osrm.org/route/v1/driving/{$lon},{$lat};{$bengkelLon},{$bengkelLat}?overview=false";

    //             $response = Http::timeout(10)->get($url);

    //             if (!$response->successful()) {
    //                 continue; // Lewati jika gagal
    //             }

    //             $data = $response->json();
    //             $distance_km = !empty($data['routes']) ? $data['routes'][0]['distance'] / 1000 : null;

    //             // Simpan hasil ke array
    //             $distances[] = [
    //                 'username' => $bengkel->username,
    //                 'name'     => $bengkel->fullname,
    //                 'lat'      => $bengkelLat,
    //                 'lon'      => $bengkelLon,
    //                 'distance' => $distance_km,
    //             ];
    //         } catch (\Exception $e) {
    //             Log::error("Error fetching distance for {$bengkel->username}: " . $e->getMessage());
    //         }
    //     }

    //     usort($distances, fn($a, $b) => $a['distance'] <=> $b['distance']);

    //     $top8 = array_slice($distances, 0, 8);

    //     Log::info('End GetBengkelDistance');

    //     return response()->json([
    //         'status'   => 200,
    //         'success'  => true,
    //         'message'  => 'Request Success',
    //         'data' => $top8,
    //     ], 200);
    // }
    

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
                    )) AS distance
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
    

}