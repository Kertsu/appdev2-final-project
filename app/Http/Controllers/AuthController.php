<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
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

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('authToken-' . $user->username)->plainTextToken,
        ]);
    }

    public function register(StoreUserRequest $request)
    {
        $validatedData = $request->validated();

        $verification_code = generate_otp();
        $user = User::create([
            'email' => $validatedData['email'],
            'username' => $validatedData['username'],
            'password' => Hash::make($validatedData['password']),
            'verification_code' => Hash::make($verification_code),
            'verification_code_expires_at' => Carbon::now()->addMinutes(10)
        ]);

        // sendOTP($user->email, $verification_code);

        return $this->success([
            'user' => $user,
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

        // sendOTP($user->email, $verification_code);

        return $this->success([
            'user' => $user,
        ]);
    }
}
