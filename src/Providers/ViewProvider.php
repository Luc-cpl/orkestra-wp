<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\ViewProvider as CoreProvider;
use Orkestra\Interfaces\ViewInterface;

use OrkestraWP\Proxies\ViewProxy as View;

class ViewProvider extends CoreProvider
{
	/**
	 * Register services with the container.
	 *
	 * @param App $app
	 * @return void
	 */
	public function register(App $app): void
	{
		parent::register($app);
		$app->bind(ViewInterface::class, View::class);
	}

	/**
	 * Here we can use the container to resolve and start services.
	 *
	 * @param App $app
	 * @return void
	 */
	public function boot(App $app): void
	{
	}
}
