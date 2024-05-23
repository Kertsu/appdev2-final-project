<?php

use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

function generate_otp(){
    $verification_code = rand(100000, 999999);
    $verification_code_exist = User::where('verification_code', $verification_code)->first();

    if ($verification_code_exist){
        return generate_otp();
    }

    return $verification_code;
}

function sendOTP(string $email, string $verification_code){
    $details = [
        'title' => 'Welcome to Our Service',
        'body' => $verification_code,
        'url' => 'https://example.com'
    ];

    Mail::to($email)->send(new SendMail($details));

    return 'Email sent successfully';
}