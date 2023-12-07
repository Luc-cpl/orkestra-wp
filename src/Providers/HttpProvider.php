<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\HttpProvider as CoreProvider;
use OrkestraWP\Events\AdminDispatch;
use OrkestraWP\Events\ApiDispatch;
use OrkestraWP\Middlewares\AuthMiddleware;

class HttpProvider extends CoreProvider
{
	public function register(App $app): void
	{
		parent::register($app);
		$app->bind('middlewares.auth', AuthMiddleware::class);
	}
	/**
	 * Here we can use the container to resolve and start services.
	 * 
	 * @param App $app
	 * @return void
	 */
	public function boot(App $app): void
	{
		// Start to listen WP hooks
		$app->get(AdminDispatch::class);
		$app->get(ApiDispatch::class);

		parent::boot($app);
	}
}
