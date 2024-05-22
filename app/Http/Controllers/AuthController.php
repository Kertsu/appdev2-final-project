<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use HttpResponsesTrait;


    public function login(LoginUserRequest $request)
    {
        $validatedData = $request->validated();

        if (!Auth::attempt($request->only(['email', 'password']))){
            return $this->error(null, 'Invalid credentials', 401);
        }

        $user = User::where('email', $validatedData['email'])->first();

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('authToken')->plainTextToken,
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
            'verification_code' => $verification_code
        ]);

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('authToken')->plainTextToken,
        ]);
    }


    public function logout()
    {
        return 'logout';
    }
}
