<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Register;
use App\Http\Requests\Login;
use App\Http\Requests\ForgotPassword;
use App\Http\Requests\ChangePassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;
use Mail;
class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Register $request)
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
            ]);
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json(['token' => $token, 'user' => $user]);

        } catch (\Exception $e) {
            Log::error('User registration failed: ' . $e->getMessage());

            DB::rollBack();
            
            return response()->json([
                'message' => 'User registration failed'.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Login $request)
    {
        try {

            $validatedData = $request->validated();
            $user = User::where('email', $validatedData['email'])->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'The credentials are invalid',
                ], 422);
            }
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json(['token' => $token, 'user' => $user]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('User Login failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'User login failed'.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $tickets=Ticket::where('userId',$request->user()->id)->orderBy('created_at','desc')->get();
        return response()->json(['user' => $request->user(),'history'=>$tickets]);
    }
    public function userById(Request $request)
    {
        $user=User::find($request->json('userId'));
        $tickets=null;
        if($user){
            $tickets=Ticket::where('userId',$user->id)->orderBy('created_at','desc')->get();
        }
        return response()->json(['user' => $user,'history'=>$tickets]);
    }

    /**
     * Logout the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function forgotPassword(ForgotPassword $request){
        try {

            $validatedData = $request->validated();
            $email=$validatedData['email'];
            $exists=User::where('email',$validatedData['email'])->exists();
            if($exists==false){
                return response()->json([
                    'message' => 'Email does not belong to any user',
                ], 422);
            }
            $otp=rand(1111,8888);
            Mail::send('mail',['otp'=>$otp], function($message) use($email){
                     $message->to($email)->subject('BUS');
                     $message->from('info@appautomovilestorres.es');
                    });
            
            return response()->json(['otp' => $otp]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Forgot Password Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to send email to your email address '.$e->getMessage(),
            ], 422);
        }
    }
    public function changePassword(ChangePassword $request){
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $exists=User::where('email',$validatedData['email'])->exists();
            if($exists==false){
                return response()->json([
                    'message' => 'Email does not belong to any user',
                ], 422);
            }
            $user=User::where('email',$validatedData['email'])->first();
            $user->password=Hash::make($validatedData['password']);
            $user->save();

            DB::commit();
            return response()->json(['message' => 'Password changed Successfully']);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Change Password Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            DB::rollBack();
            return response()->json([
                'message' => 'Unable to change password'.$e->getMessage(),
            ], 422);
        }
    }
    public function getAllUsers(Request $request){
        try {
            if(!empty($request->json('role'))){
                $users=User::where('role',$request->json('role'))->get();
            }
            if(empty($request->json('role'))){
                $users=User::all();
            }
            
            return response()->json(['users' => $users]);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Get users Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to get all users '.$e->getMessage(),
            ], 422);
        }
    }
    public function storeFcm(Request $request){
        try {
            $user=$request->user();
            $user->fcm=$request->json('fcm');
            $user->save();
            return response()->json(['message' => 'Stored Successfully']);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Store fcm Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to store fcm '.$e->getMessage(),
            ], 422);
        }
    }
    public function editUser(Request $request){
        try {
            $user=User::find($request->json('userId'));
            if(!empty($request->json('name'))){
                $user->name=$request->json('name');    
            }
            if(!empty($request->json('note'))){
                $user->note=$request->json('note');    
            }
            $user->save();
            return response()->json(['message' => 'Updated Successfully']);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Edit user Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to edit user '.$e->getMessage(),
            ], 422);
        }
    }
    public function deleteUser(Request $request){
        try {
            $user=User::find($request->json('userId'));
            $tickets=Ticket::where('userId',$user->id)->get();
            foreach ($tickets as $key => $value) {
                $value->delete();
            }
            $user->delete();
            return response()->json(['message' => 'Deleted Successfully']);

        } catch (\Exception $e) {
            // Log the error
            Log::error('Delete user Failed: ' . $e->getMessage());
            // Return a JSON response with an error message
            return response()->json([
                'message' => 'Unable to delete user '.$e->getMessage(),
            ], 422);
        }
    }
}