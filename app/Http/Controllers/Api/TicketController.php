<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTicket;
use App\Http\Requests\Charge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Ticket;
use Laravel\Sanctum\PersonalAccessToken;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use FCM;
use App\Services\FirebaseService;
class TicketController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }
    public function store(StoreTicket $request){
    	try {
    		DB::beginTransaction();

            $validatedData = $request->validated();
            $user=$request->user();
            if($user->role!="super-admin"){
	            	return response()->json([
	                'message' => 'Unauthorized',
	            ], 422);
            }

            $us=User::find($validatedData['userId']);
            if(!$us){
	            	return response()->json([
	                'message' => 'User does not exists',
	            ], 422);
            }
            if(empty($us->tickets)){
            	$tickets=$validatedData['tickets'];
            }
            if(!empty($us->tickets)){
            	$tickets=(int)$us->tickets+$validatedData['tickets'];
            }
            $us->tickets=$tickets;
            $us->save();
        	$ticket=new Ticket;
        	$ticket->type="assign";
        	$ticket->userId=$validatedData['userId'];
        	$ticket->tickets=$validatedData['tickets'];
        	$ticket->save();

            DB::commit();
            return response()->json(['message' => 'Tickets assigned successfully']);
            

        } catch (\Exception $e) {
            // Log the error
            Log::error('Storing Ticket failed: ' . $e->getMessage());
            DB::rollBack();
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Storing ticket failed'.$e->getMessage(),
            ], 422);
        }
    }
    public function charge(Charge $request){
    	try {
    		DB::beginTransaction();

            $validatedData = $request->validated();
            $user=$request->user();
            if($user->role!="conductor"){
	            	return response()->json([
	                'message' => 'Unauthorized',
	            ], 422);
            }
            $token = PersonalAccessToken::findToken($validatedData['userToken']);
            if(!$token){
	            	return response()->json([
	                'message' => 'Unauthorized',
	            ], 422);
            }
			if ($token) {
				$us = $token->tokenable; // This is the authenticated user
			}
            
            if(empty($us->tickets)||(int)$us->tickets<$validatedData['quantity']){
            	return response()->json([
	                'message' => 'Tickets does not exists',
	            ], 422);
            }
            $remaining=(int)$us->tickets-$validatedData['quantity'];
            $us->tickets=$remaining;
            $us->save();
        	$ticket=new Ticket;
        	$ticket->type="charge";
        	$ticket->userId=$us->id;
        	$ticket->tickets=$validatedData['quantity'];
        	$ticket->save();
        	
            DB::commit();

            if(!empty($us->fcm)){
                $fcmToken=$us->fcm;
                

                $deviceToken = $fcmToken;
                $title = 'Bus';
                $body="Ticket charged successfully";
                $data = $ticket;

                // Send notification and capture the response
                $response = $this->firebaseService->sendNotification($deviceToken, $title, $body, $data);
            }
            return response()->json(['message' => 'Tickets charged successfully']);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Charging Ticket failed: ' . $e->getMessage());
            DB::rollBack();
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Charging ticket failed'.$e->getMessage(),
            ], 422);
        }
    }

    public function history(Request $request){
        try {
            $tickets=Ticket::all();
            foreach ($tickets as $key => $value) {
                $value->setAttribute('user',User::find($value->userId));
            }
            return response()->json(['data' => $tickets]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Get users Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to get all users '.$e->getMessage(),
            ], 422);
        }
    }
}
