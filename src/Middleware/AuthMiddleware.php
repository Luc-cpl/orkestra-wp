<?php

namespace OrkestraWP\Middleware;

use OrkestraWP\Repositories\Auth;
use League\Route\Http\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Participant in processing a server request and response.
 *
 * An HTTP middleware component participates in processing an HTTP message:
 * by acting on the request, generating the response, or forwarding the
 * request to a subsequent middleware and possibly acting on its response.
 */
class AuthMiddleware implements MiddlewareInterface
{
	/**
	 * @param string|string[] $role
	 */
	public function __construct(
		protected Auth $auth,
		protected string|array $role = 'logged_in'
	) {
	}

	/**
	 * Process an incoming server request.
	 *
	 * Processes an incoming server request in order to produce a response.
	 * If unable to produce the response itself, it may delegate to the provided
	 * request handler to do so.
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$auth = $this->auth;
		$user = $auth->user;
		$roles = is_string($this->role) ? [$this->role] : $this->role;

		if (!$user && in_array('guest', $roles, true)) {
			return $handler->handle($request);
		}

		if (!$user) {
			throw new ForbiddenException('You are not allowed to access this resource.');
		}

		if ($this->role === 'logged_in') {
			return $handler->handle($request);
		}

		foreach ($roles as $role) {
			if (in_array($role, $user->roles, true)) {
				return $handler->handle($request);
			}
		}

		throw new ForbiddenException('You are not allowed to access this resource.');
	}
}
