<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Dingo\Api\Routing\Router;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app('Dingo\Api\Routing\Router');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


$api->version('v1', function ($api) {
    $api->get('hello', 'App\Api\V1\Controllers\HomeController@index');

    $api->group(['prefix' => 'auth'], function (Router $api) {

        $api->post('register', 'App\\Api\\V1\\Controllers\\JWTAuthController@register');
        $api->post('login', 'App\\Api\\V1\\Controllers\\JWTAuthController@login');
        $api->post('logout', 'App\\Api\\V1\\Controllers\\JWTAuthController@logout');
        $api->post('refresh', 'App\\Api\\V1\\Controllers\\JWTAuthController@refresh');
        $api->post('profile', 'App\\Api\\V1\\Controllers\\JWTAuthController@profile');




        // $api->post('signup', 'App\\Api\\V1\\Controllers\\SignUpController@signUp');
        // $api->post('signupVerifyEmail/{code}', 'App\\Api\\V1\\Controllers\\SignUpController@check_verification');
        // $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');

        // $api->post('recovery', 'App\\Api\\V1\\Controllers\\ForgotPasswordController@sendResetEmail');
        // $api->post('reset', 'App\\Api\\V1\\Controllers\\ResetPasswordController@resetPassword');

        // $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
        // $api->post('refresh', 'App\\Api\\V1\\Controllers\\RefreshController@refresh');
        // $api->get('me', 'App\\Api\\V1\\Controllers\\UserController@me');
    });


    $api->group(['middleware' => 'api.auth'], function ($api) {
        $api->post('create_group', 'App\Api\V1\Controllers\HomeController@index');
    });
});
