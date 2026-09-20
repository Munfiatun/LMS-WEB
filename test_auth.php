<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Auth::loginUsingId(App\Models\User::where('email', 'student@example.com')->first()->id);
$request = Illuminate\Http\Request::create('/admin/dashboard');
$request->setUserResolver(function() { return Auth::user(); });
$middleware = new App\Http\Middleware\EnsureUserHasRole();
$response = $middleware->handle($request, function() { return response('ok'); }, 'admin');

echo get_class($response) . "\n";
if (method_exists($response, 'getTargetUrl')) {
    echo $response->getTargetUrl();
}

