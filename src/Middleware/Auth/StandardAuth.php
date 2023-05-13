<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use App\Auth;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class StandardAuth extends AbstractAuth
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Initialize the Auth for this request.
        $auth = new Auth(
            userRepo: $this->userRepo,
            session: $request->getAttribute(ServerRequest::ATTR_SESSION),
            environment: $this->environment,
        );
        $user = ($auth->isLoggedIn()) ? $auth->getLoggedInUser() : null;

        $request = $request
            ->withAttribute(ServerRequest::ATTR_AUTH, $auth)
            ->withAttribute(ServerRequest::ATTR_USER, $user);

        return parent::process($request, $handler);
    }
}
