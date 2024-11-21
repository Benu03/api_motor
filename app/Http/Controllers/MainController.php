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




class MainController extends Controller
{

  

    public function tes(Request $request)
    {  
      
  
      log::info('End Notif Detail ');
      return response()->json(
          [   'status'       =>  200,
              'success'   =>  true,
              'message'   =>  'Request Success',
              'data'      =>  []
          ], 200);
    }

  

  
  


}
