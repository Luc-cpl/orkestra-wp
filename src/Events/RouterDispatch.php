<?php

namespace OrkestraWP\Events;

use Orkestra\App;
use Orkestra\Interfaces\HooksInterface;
use Orkestra\Services\RouterService;
use Psr\Http\Message\ServerRequestInterface;

class RouterDispatch
{
	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
	) {
		$app->hookRegister('router.dispatch', $this->handleAdmin(...));
	}

	protected function handleAdmin(ServerRequestInterface $request, RouterService $router): ServerRequestInterface
	{

		$this->hooks->register('admin_menu', fn () => $this->registerWPAdmin($router));

		if (is_admin() && isset($_GET['page']) && str_starts_with($_GET['page'], $this->app->slug() . '.')) {
			$path = str_replace($this->app->slug(), '', $_GET['page']);
			$path = str_replace('.', '/', $path);

			$originalServer = $_SERVER;

			$_SERVER = array_merge($_SERVER, [
				'REQUEST_URI' => $path,
			]);

			$request = $this->app->get(ServerRequestInterface::class)
				->withAddedHeader('X-Request-Mode', 'content-only');

			$_SERVER = $originalServer;
		}

		return $request;
	}

	/**
	 * Search for wp-admin routes and add in WordPress
	 *
	 * @return void
	 */
	protected function registerWPAdmin(RouterService $router): void
	{
		$routes = $router->getRoutes();

		foreach ($routes as $route) {
			if ($route->getConfig('type') !== 'admin') {
				continue;
			}

			$group = $route->getParentGroup();

			if (!$group || $group->getPrefix() === $route->getPath()) {
				$this->addMenuPage($route);
				continue;
			}

			$this->addSubMenuPage($route);
		}
	}

	protected function addMenuPage($route): void
	{
		$path = str_replace('/', '.', $route->getPath());
		add_menu_page(
			$route->getConfig('title'),
			$route->getConfig('menu_title', $route->getConfig('title')),
			$route->getConfig('capability'),
			$this->app->slug() . $path,
			fn () => null,
			$route->getConfig('icon', ''),
			$route->getConfig('position', null)
		);
	}

	protected function addSubMenuPage($route): void
	{
		$path = str_replace('/', '.', $route->getPath());
		add_submenu_page(
			$route->getParentGroup()->getPrefix(),
			$route->getConfig('title'),
			$route->getConfig('menu_title', $route->getConfig('title')),
			$route->getConfig('capability'),
			$this->app->slug() . $path,
			fn () => null,
			$route->getConfig('position', null)
		);
	}
}
