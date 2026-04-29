<?php

namespace App\Http\Controllers;

use App\Helper\JWTToken;
use App\Mail\OTPMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    function LoginPage()
    {
        return view('pages.auth.login-page');
    }
    function RegistrationPage()
    {
        return view('pages.auth.registration');
    }
    function SendOtpPage()
    {
        return view('pages.auth.send-otp-page');
    }
    function VerifyOtpPage()
    {
        return view('pages.auth.verify-otp-page');
    }
    function ResetPasswordPage()
    {
        return view('pages.auth.reset-pass-page');
    }
    function ProfilePage()
    {
        return view('pages.dashboard.profile');
    }

    function UserRegistration(Request $request)
    {
        $request->validate([
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'password' => 'required',
        ]);

        User::create([
            'firstName' => $request->input('firstName'),
            'lastName' => $request->input('lastName'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => $request->input('password')
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User Registration Successfully!'
        ], 200);
    }

    function userLogin(Request $request)
    {
        $count = User::where('email', '=', $request->input('email'))
            ->where('password', '=', $request->input('password'))
            ->select('id')->first();
        if ($count !== null) {
            // User Login->JWT Token Issue
            $email = $request->input('email');
            $token = JWTToken::CreateToken($email, $count->id);

            return response()->json([
                'status' => 'success',
                'message' => 'User Login Successfully!',
            ], 200)->cookie('token', $token, 60 * 24 * 30);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized!'
            ], 200);
        }
    }

    function SendOTPCode(Request $request)
    {
        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();
        if ($count == 1) {
            Mail::to($email)->send(new OTPMail($otp));

            User::where('email', '=', $email)->update(['otp' => $otp]);

            return response()->json([
                'status' => 'success',
                'message' => '4 digit OTP Code has been send to your email!',
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized!'
            ], 200);
        }
    }

    function VerifyOTP(Request $request)
    {
        $email = $request->input('email');
        $otp = $request->input('otp');
        $count = User::where('email', '=', $email)->where("otp", "=", $otp)->count();

        if ($count == 1) {

            $token = JWTToken::CreateTokenForSetPassword($email);

            User::where('email', '=', $email)->update(['otp' => "0"]);

            return response()->json([
                'status' => 'success',
                'message' => 'OTP Verification Successfully!'
            ], 200)->cookie('token', $token, 60 * 24 * 30);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized!'
            ], 200);
        }
    }
    function ResetPass(Request $request)
    {
        try {
            $email = $request->header('email');
            $password = $request->input('password');
            User::where('email', '=', $email)->update(['password' => $password]);

            return response()->json([
                'status' => 'success',
                'message' => 'Request Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Something Went Wrong!'
            ], 200);
        }
    }
    function UserLogout()
    {
        return redirect('/userLogin')->cookie('token', '', '-1');
    }
    function UserProfile(Request $request)
    {
        try {
            $email = $request->header('email');
            $user = User::where('email', '=', $email)->first();
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successfully!',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Your profile details is not found!'
            ], 200);
        }
    }
    function UpdateProfile(Request $request)
    {
        try {
            $email = $request->header('email');
            $firstName = $request->input('firstName');
            $lastName = $request->input('lastName');
            $phone = $request->input('phone');
            $password = $request->input('password');

            User::where('email', '=', $email)->update([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phone' => $phone,
                'password' => $password,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful!'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Somethings Went Wrong!'
            ], 200);
        }
    }
}
