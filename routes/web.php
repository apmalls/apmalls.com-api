<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/reset-password/{token}', function (
    Request $request,
    string $token
) {

    return redirect()->away(

        env('FRONTEND_URL')
        . '/reset-password?token='
        . $token
        . '&email='
        . urlencode($request->query('email'))

    );

})->name('password.reset');
