<?php

namespace App\Http\Controllers;

use App\Jobs\OTPVerificationJob;
use App\Models\OTP;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'is_active' => 1,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Registration successful',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Registration failed'], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', trim($request->email))->first();

        if (!$user) {
            return response()->json([
                'message' => 'Account does not exist.'
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is not activated.'
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        // Create access token for the user
        $token = $user->createToken('authToken')->accessToken;

        return response()->json([
            'user' => $user,
            'message' => 'Login successful',
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function getProfile(Request $request)
    {
        return response()->json(auth()->guard('api')->user());
    }

    // public function sendOtp(Request $request)
    // {
    //     try {
    //         return DB::transaction(function () use ($request) {
    //             $expired_at = now()->addMinutes(10);
    //             $otp = random_int(100000, 999999);
    //             $email = trim($request['email']);
    //             $user_id = (int)$request['user_id'];

    //             $checkExists = User::where('id', $user_id)
    //                 ->first();

    //             if (!$checkExists) {
    //                 return response()->json([
    //                     'message' => 'Account does not exist!'
    //                 ], 500);
    //             }

    //             if ($checkExists->is_active == 1) {
    //                 return response()->json([
    //                     'message' => 'Account is already activated!'
    //                 ], 500);
    //             }

    //             OTP::updateOrCreate([
    //                 'email' => $email,
    //                 'user_id' => $user_id
    //             ], [
    //                 'user_id' => $user_id,
    //                 'expired_at' => $expired_at,
    //                 'email' => $email,
    //                 'otp' => $otp
    //             ]);

    //             dispatch(new OTPVerificationJob($email, [
    //                 'email' => $email,
    //                 'otp' => $otp
    //             ]));

    //             return response()->json([
    //                 'message' => 'OTP has been sent, please check your email!',
    //                 'expired_at' => $expired_at->toDateTimeString(),
    //             ]);
    //         });
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // public function verifyOtp(Request $request)
    // {
    //     try {
    //         return DB::transaction(function () use ($request) {
    //             $otp = trim($request['otp']);
    //             $user_id = (int)$request['user_id'];

    //             $checkOTPValid = OTP::where('user_id', $user_id)
    //                 ->where('otp', $otp)
    //                 ->first();

    //             if (!$checkOTPValid) {
    //                 return response()->json([
    //                     'message' => 'Invalid OTP code!'
    //                 ], 500);
    //             }

    //             if (now() > Carbon::parse($checkOTPValid->expired_at)) {
    //                 return response()->json([
    //                     'message' => 'OTP code has expired!'
    //                 ], 500);
    //             }

    //             $checkExists = User::where('id', $user_id)
    //                 ->first();

    //             if (!$checkExists) {
    //                 return response()->json([
    //                     'message' => 'User does not exist!'
    //                 ], 500);
    //             }

    //             $active = User::where('id', $user_id)->update(['is_active' => 1]);

    //             if ($active) {
    //                 $checkOTPValid->delete();

    //                 return response()->json([
    //                     'message' => 'Verification successful, please log in!'
    //                 ]);
    //             }
    //         });
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
