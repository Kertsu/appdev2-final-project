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
            return $this->error(null, 'User not found', 404);
        }
        return $this->success($user);
    }

    public function destroy(){
        $user = User::where('id', Auth::user()->id)->first();
        $user->delete();
        return $this->success(null, 'Account deleted successfully');
    }
}
