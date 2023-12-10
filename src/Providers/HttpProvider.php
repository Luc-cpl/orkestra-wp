<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\HttpProvider as CoreProvider;
use OrkestraWP\Listeners\AdminDispatch;
use OrkestraWP\Listeners\ApiDispatch;
use OrkestraWP\Middleware\AuthMiddleware;

class HttpProvider extends CoreProvider
{
	/**
	 * @var class-string[]
	 */
	public array $listeners = [
		AdminDispatch::class,
		ApiDispatch::class,
	];

	public function register(App $app): void
	{
		parent::register($app);
		$app->bind('middleware.auth', AuthMiddleware::class);
	}
}
