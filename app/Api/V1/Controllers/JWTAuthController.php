<?php

namespace App\Api\V1\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Profile;
use DB;

class JWTAuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|between:2,100',
            'last_name' => 'required|between:2,100',
            'middle_name' => 'required|between:2,100',
            'phone_no' => 'required|digits:11',
            'email' => 'required|email|unique:users|max:50',
            'password' => 'required|confirmed|string|min:6',
        ]);

        $user = new User;

        $user->first_name = $request->get("first_name");
        $user->last_name = $request->get("last_name");
        $user->middle_name = $request->get("middle_name");
        $user->email = $request->get("email");
        $user->phone_no = $request->get("phone_no");
        $user->email_verified_token = rand(1000000, 1000000000000);
        $user->password = bcrypt($request->password);

        //start :: check if email already exists in database
        $check_email_exists = User::where('email', $user->email)->get();

        if (count($check_email_exists) > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Email Already Exists'

            ], 200);
        }
        //end :: check if email already exists in database

        // //start :: send mail using template
        // if ($request->only('platform') == 'APP') {
        //     $link = 'http://127.0.0.1:8000' . '/verify-email/' . rand(10, 1000000);
        // } else {
        //     $link = 'http://127.0.0.1:8000' . '/verify-email/' . $user->email_verified_token;
        // }

        // Mail::to(request('email'))->send(new VerifyEmail($link));
        // //end :: send mail using template

        // // check for failures
        // if (!Mail::failures()) {
        //     //if mail was sent successfully, then proceed with insertion

        if (!$user->save()) {
            return response()->json([
                'status' => false,
                'message' => 'Registration Failed'

            ], 200);
        }

        $insertedId = $user->id;
        $dateEntered = date('Y-m-d');
        //update users table,add member_id
        $member_id = "AJO-" . rand(1000000000, 9999999999);

        //update member_id in users table
        DB::table('users')->where('id', $user->id)->update(['member_id' => $member_id]);

        $profile = new Profile;
        $profile->user_id = $insertedId;
        $profile->last_name = $user->last_name;
        $profile->first_name = $user->first_name;
        $profile->middle_name = $user->middle_name;
        $profile->email = $user->email;
        $profile->phone_no = $user->phone_no;
        $profile->date_entered = $dateEntered;
        $profile->created_at = date('Y-m-d h:i:s');

        if ($profile->save()) {
            return response()->json([
                'message' => 'Check your mail to complete your registration!',
                'user' => $user
            ], 201);
        }
        // } else {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Registration Failed,Please try again!'
        //     ], 200);
        // }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only(['email', 'password']);

        $token = Auth::guard()->attempt($credentials);

        if (!$token) {
            //throw new AccessDeniedHttpException();
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
            ], 200);
        }

        //start :: check if email already exists in database
        $check_email_exists = User::where('email', $request->email)->get();

        if (count($check_email_exists) == 0) {
            return response()->json([
                'status' => false,
                'message' => 'User does not exist!'

            ], 200);
        }



        // if ($validator->fails()) {
        //     return response()->json($validator->errors(), 422);
        // }

        // if (!$token = auth()->attempt($validator->validated())) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        return $this->createNewToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $fetchRecord = Auth::user()->first_name;
        $fetchRecord1 = Auth::user()->last_name;
        $fetchRecord2 = Auth::user()->email;
        $fetchRecord3 = Auth::user()->id;

        return response()->json([
            'status' => true,
            'name' => $fetchRecord . " " . $fetchRecord1,
            'token' => $token,
            'email' => $fetchRecord2,
            'user_id' => $fetchRecord3,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
