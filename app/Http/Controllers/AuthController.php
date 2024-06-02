<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use HttpResponsesTrait;


    public function login(LoginUserRequest $request)
    {
        $validatedData = $request->validated();

        if (!Auth::attempt($request->only(['email', 'password']))) {
            return $this->error(null, 'Invalid credentials', 401);
        }

        $user = User::where('email', $validatedData['email'])->first();

        if (!$user->email_verified_at) {
            return $this->success([
                'user' => $user,
                "otpRequired" => true,
                'token' => $user->createToken('authToken-' . $user->username)->plainTextToken,
            ]);
        }

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('authToken-' . $user->username)->plainTextToken,
            "otpRequired" => false
        ]);
    }

    public function register(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        $verification_code = generate_otp();

        $username = explode('@', $validatedData['email'])[0];

        $user = User::create([
            'email' => $validatedData['email'],
            'username' => $username,
            'password' => Hash::make($validatedData['password']),
            'verification_code' => Hash::make($verification_code),
            'verification_code_expires_at' => Carbon::now()->addMinutes(10)
        ]);

        sendOTP($user->email, $verification_code);

        return $this->success([
            'message' => "We sent a verification code to your email address",
        ]);
    }


    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out successfully');
    }


    public function resend_verification_code(Request $request)
    {
        $request->validate(
            [
                'email' => ['required', 'email']
            ]
        );
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->error(null, 'User not found', 404);
        }

        $verification_code = generate_otp();
        $user->verification_code = Hash::make($verification_code);
        $user->verification_code_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        sendOTP($user->email, $verification_code);

        return $this->success([
            'user' => $user,
        ]);
    }

    public function verify_email(VerifyEmailRequest $request)
    {
        $validatedData = $request->validated();

        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            return $this->error(null, 'User not found', 404);
        }

        if (!Hash::check($validatedData['otp'], $user->verification_code)) {
            return $this->error(null, 'Invalid verification code', 401);
        }

        $user->email_verified_at = Carbon::now();
        $user->verification_code = null;
        $user->verification_code_expires_at = null;
        $user->save();

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('authToken-' . $user->username)->plainTextToken,
        ]);
    }
}
