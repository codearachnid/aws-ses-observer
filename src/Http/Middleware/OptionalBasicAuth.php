<?php

declare(strict_types=1);

namespace codearachnid\AwsSesObserver\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = config('aws-ses-observer.http_auth_username');
        $password = config('aws-ses-observer.http_auth_password');

        if (empty($username) || empty($password)) {
            return $next($request);
        }

        $requestUsername = $request->getUser();
        $requestPassword = $request->getPassword();

        if ($requestUsername !== null
            && $requestPassword !== null
            && hash_equals((string) $username, $requestUsername)
            && hash_equals((string) $password, $requestPassword)) {
            return $next($request);
        }

        return new Response('Unauthorized.', 401, [
            'WWW-Authenticate' => 'Basic realm="SES Observer"',
        ]);
    }
}
