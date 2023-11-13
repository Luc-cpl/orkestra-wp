<?php

namespace OrkestraWP\Events;

use Orkestra\App;
use Orkestra\Interfaces\HooksInterface;
use Orkestra\Services\Router\Router;
use Psr\Http\Message\ServerRequestInterface;

class RouterDispatch
{
	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
	) {
		$app->hookRegister('router.dispatch', $this->handleAdmin(...));
	}

	/**
	 * Here we hook into the router dispatch event and change
	 * current request and routes to respond to WordPress admin pages.
	 */
	protected function handleAdmin(ServerRequestInterface $request, Router $router): ServerRequestInterface
	{
		$this->hooks->register('admin_menu', fn () => $this->registerWPAdmin($router));

		if (!is_admin()) {
			$this->hooks->register('init', $this->renderFullPage(...));
		}

		if (is_admin() && isset($_GET['page']) && str_starts_with($_GET['page'], $this->app->slug() . '.')) {
			$path = str_replace($this->app->slug(), '', $_GET['page']);
			$path = str_replace('.', '/', $path);

			$originalServer = $_SERVER;

			$_SERVER = array_merge($_SERVER, [
				'REQUEST_URI' => $path,
			]);

			$request = $this->app->get(ServerRequestInterface::class);

			$_SERVER = $originalServer;
		}

		return $request;
	}

	/**
	 * Search for wp-admin routes and add in WordPress
	 */
	protected function registerWPAdmin(Router $router): void
	{
		$routes = $router->getRoutes();

		foreach ($routes as $route) {
			// Only add GET routes to WordPress admin menu
			if ($route->getMethod() !== 'GET') {
				continue;
			}

			$group = $route->getParentGroup();

			if (
				$route->getConfig('type') !== 'admin' &&
				(!$group || $group->getConfig('type') !== 'admin')
			) {
				continue;
			}

			if (!$group || $group->getPrefix() === $route->getPath()) {
				$this->addMenuPage($route);
				continue;
			}

			$this->addSubMenuPage($route);
		}
	}

	/*
	 * Add a menu page to WordPress
	 */
	protected function addMenuPage($route): void
	{
		$path = str_replace('/', '.', $route->getPath());
		add_menu_page(
			$route->getConfig('title'),
			$route->getConfig('menu_title', $route->getConfig('title')),
			$route->getConfig('capability'),
			$this->app->slug() . $path,
			$this->getRenderedView(...),
			$route->getConfig('icon', ''),
			$route->getConfig('position', null)
		);
	}

	/*
	 * Add a submenu page to WordPress
	 */
	protected function addSubMenuPage($route): void
	{
		$path   = str_replace('/', '.', $route->getPath());
		$parent = str_replace('/', '.', $route->getParentGroup()->getPrefix());
		add_submenu_page(
			$this->app->slug() . $parent,
			$route->getConfig('title'),
			$route->getConfig('menu_title', $route->getConfig('title')),
			$route->getConfig('capability'),
			$this->app->slug() . $path,
			$this->getRenderedView(...),
			$route->getConfig('position', null)
		);
	}

	protected function renderFullPage(): void
	{
		$content = $this->app->hookQuery('view.full_content', '');

		if (empty($content)) {
			return;
		}

		echo $content;
		exit;
	}

	/**
	 * At this point our router should have already dispatched
	 * the route and a view should be already rendered in the
	 * view provider. We just need to get the rendered view and
	 * echo it out.
	 */
	protected function getRenderedView(): void
	{
		echo $this->app->hookQuery('view.content', '');
	}
}
