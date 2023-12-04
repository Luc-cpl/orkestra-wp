<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\HttpProvider as CoreProvider;
use Orkestra\Interfaces\HooksInterface;

use OrkestraWP\Events\AdminDispatch;

use League\Route\Http\Exception\NotFoundException;
use OrkestraWP\Events\ApiDispatch;
use OrkestraWP\Middlewares\AuthMiddleware;

class HttpProvider extends CoreProvider
{
	public function register(App $app): void
	{
		parent::register($app);
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

		$app->hookRegister('http.middlewares', fn ($middlewares) => array_merge($middlewares, [
			'auth' => AuthMiddleware::class,
		]));

		$app->runIfAvailable(HooksInterface::class, function (HooksInterface $hooks) use ($app) {
			// Run our router after all plugins are loaded
			$hooks->register('init', function () use ($app) {
				/**
				 * In WordPress environment, we need to bypass
				 * the router on not found routes as this is
				 * handled by WordPress itself.
				 */
				try {
					parent::boot($app);
				} catch (NotFoundException) {
				}
			});
		});
	}
}
