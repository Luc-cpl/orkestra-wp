<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\RouterProvider as CoreProvider;
use Orkestra\Interfaces\HooksInterface;

use OrkestraWP\Events\RouterDispatch;

use League\Route\Http\Exception\NotFoundException;

class RouterProvider extends CoreProvider
{
	public function register(App $app): void
	{
		parent::register($app);
		$app->singleton(RouterDispatch::class, RouterDispatch::class);
	}
	/**
	 * Here we can use the container to resolve and start services.
	 * 
	 * @param App $app
	 * @return void
	 */
	public function boot(App $app): void
	{
		$app->get(RouterDispatch::class);
		$app->runIfAvailable(HooksInterface::class, function (HooksInterface $hooks) use ($app) {
			// Run our router after all plugins are loaded
			$hooks->register('plugins_loaded', function () use ($app) {
				/**
				 * In WordPress environment, we need to bypass
				 * the router on not found routes as this is
				 * handled by WordPress itself.
				 */
				try {
					parent::boot($app);
				} catch (NotFoundException $th) {
				}
			});
		});
	}
}
