<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\RouterProvider as CoreProvider;
use Orkestra\Services\RouterService as Router;
use Orkestra\Router\Middlewares\JsonMiddleware;
use Orkestra\Router\Strategy\ApplicationStrategy;
use Orkestra\Interfaces\HooksInterface;

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;

class RouterProvider extends CoreProvider
{
	/**
	 * Here we can use the container to resolve and start services.
	 * 
	 * @param App $app
	 * @return void
	 */
	public function boot(App $app): void
	{
		$app->runIfAvailable(HooksInterface::class, function (HooksInterface $hooks) use ($app) {
			// Run our router after all plugins are loaded
			$hooks->register('plugins_loaded', function () use ($app) {
				$router  = $app->get(Router::class);
				$request = $app->get(ServerRequestInterface::class);

				$strategy = $app->get(ApplicationStrategy::class);
				$router->setStrategy($strategy);

				$router->middleware($app->get(JsonMiddleware::class));

				$configFile = $app->config()->get('routes');

				(require $configFile)($router);

				/**
				 * In WordPress environment, we need to bypass
				 * the router on not found routes as this is
				 * handled by WordPress itself.
				 */
				try {
					$response = $router->dispatch($request);
					// send the response to the browser
					(new SapiEmitter)->emit($response);
				} catch (NotFoundException $th) {
				}
			});
		});
	}
}
