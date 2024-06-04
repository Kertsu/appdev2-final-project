<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use HttpResponsesTrait;
    public function show(string $username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return $this->error(null, 'User not found', 400);
        }
        return $this->success($user);
    }

    public function validate_username(string $username)
    {
        $user = User::where('username', $username)->first();
        if (!$user) {
            return $this->error(null, "Sorry, we couldn't find the user you were looking for.", 404);
        }
        return $this->success(["user" => $user]);
    }

    public function destroy()
    {
        $user = User::where('id', Auth::user()->id)->first();
        $user->delete();
        return $this->success(null, 'Account deleted successfully');
    }


    public function get_self()
    {
        $user = Auth::user();

        if (!$user->email_verified_at) {
            return $this->success([
                "user" => $user, "otpRequired" => true
            ]);
        }

        return $this->success([
            "user" => $user, "otpRequired" => false
        ]);
    }
}
