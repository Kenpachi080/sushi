<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class AuthService
{
    public function validate($email, $phone)
    {
        $validateEmail = User::where('email', '=', $email)->first();
        if ($validateEmail) {
            return response(['Exception' => 'Эта электронная почта уже используется.'], 409);
        }
        $validatePhone = User::where('phone', '=', $phone)->first();
        if ($validatePhone) {
            return response(['Exception' => 'Этот номер телефона уже используется.'], 409);
        }
    }

    public function token()
    {
        $token = Str::random(60);
        return $token;
    }
}

?>
