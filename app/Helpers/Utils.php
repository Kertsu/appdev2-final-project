<?php

use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

function generate_otp()
{
    $verification_code = rand(100000, 999999);
    $verification_code_exist = User::where('verification_code', $verification_code)->first();

    if ($verification_code_exist) {
        return generate_otp();
    }

    return $verification_code;
}

function sendOTP(string $email, string $verification_code)
{
    $username = explode('@', $email)[0];
    $details = [
        'title' => 'Hello, ' . $username,
        'body' => "Thank you for signing up with Whisper Link! We are thrilled to welcome you onboard. 
        This verification code will expire in the next 10 minutes. Please do not share it with anyone: ",
        'verification_code' => $verification_code,
        'url' => 'https://whisper-link.vercel.app/login'
    ];

    Mail::to($email)->send(new SendMail($details));

    return 'Email sent successfully';
}
