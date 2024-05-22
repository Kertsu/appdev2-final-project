<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use HttpResponsesTrait;
    public function show(string $username){
        $user = User::where('username', $username)->first();
        if(!$user){
            return $this->error(null, 'User not found', 400);
        }
        return $this->success($user);
    }
}
