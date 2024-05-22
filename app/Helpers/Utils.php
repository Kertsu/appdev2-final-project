<?php

use Illuminate\Support\Str;

use App\Models\User;

function generate_otp(){
    $verification_code = rand(100000, 999999);
    $verification_code_exist = User::where('verification_code', $verification_code)->first();

    if ($verification_code_exist){
        return generate_otp();
    }

    return $verification_code;
}