<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

Auth::loginUsingId(User::where('email', 'student@example.com')->first()->id);
$request = Request::create('/admin/dashboard');
$request->setUserResolver(function () {
    return Auth::user();
});
$middleware = new EnsureUserHasRole;
$response = $middleware->handle($request, function () {
    return response('ok');
}, 'admin');

echo get_class($response)."\n";
if (method_exists($response, 'getTargetUrl')) {
    echo $response->getTargetUrl();
}
