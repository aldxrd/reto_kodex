<?php

declare(strict_types=1);

namespace Kodex\Controllers;

use Kodex\Auth\AuthService;
use Kodex\Http\Request;
use Kodex\Http\Response;
use Kodex\Http\Validator;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /** POST /api/auth/login */
    public function login(Request $request): Response
    {
        $v = new Validator($request->json());
        $email = $v->email('email');
        $password = $v->string('password', 1, 200);
        $v->validate();

        return Response::data($this->auth->login((string) $email, (string) $password, $request->ip));
    }

    /** GET /api/auth/me */
    public function me(Request $request): Response
    {
        return Response::data($request->user?->toArray());
    }

    /** POST /api/auth/logout */
    public function logout(Request $request): Response
    {
        $this->auth->logout($request);

        return Response::noContent();
    }
}
