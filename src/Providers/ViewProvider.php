<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\ViewProvider as CoreProvider;
use Orkestra\Services\View\Interfaces\ViewInterface;

use OrkestraWP\Proxies\Views\HttpViewProxy as View;

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
		$app->decorate(ViewInterface::class, function (ViewInterface $defaultView, App $app) {
			return $app->make(View::class, [
				'defaultView' => $defaultView
			]);
		});
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
