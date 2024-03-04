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
class TicketController extends Controller
{
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
            	$tickets=(int)$us->ticket+$validatedData['tickets'];
            	
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
                $fcmToken=$user->fcm;
                $optionBuilder = new OptionsBuilder();
                $optionBuilder->setTimeToLive(60*20);
                $notificationBuilder = new PayloadNotificationBuilder('Bus');
                $notificationBuilder->setBody('Ticket charged successfully')->setSound('default');
                $dataBuilder = new PayloadDataBuilder();
                
                $dataBuilder->addData(['data'=>$ticket]);
                $option = $optionBuilder->build();
                $notification = $notificationBuilder->build();
                $data = $dataBuilder->build();
                $downstreamResponse = FCM::sendTo($fcmToken, $option, $notification, $data);
                if($downstreamResponse->numberSuccess()==1){
                }
                if($downstreamResponse->numberFailure()==1){
                }
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
}
