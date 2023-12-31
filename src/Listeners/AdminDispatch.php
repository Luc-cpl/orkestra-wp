<?php

namespace OrkestraWP\Listeners;

use Orkestra\App;
use Orkestra\Services\Hooks\Interfaces\ListenerInterface;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;
use Orkestra\Services\Http\Interfaces\RouteInterface;
use Orkestra\Services\Http\Interfaces\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\ServerRequestFactory;

class AdminDispatch implements ListenerInterface
{
	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function hook(): string
	{
		return '{app}.http.router.dispatch';
	}

	/**
	 * Here we hook into the router dispatch event and change
	 * current request and routes to respond to WordPress admin pages.
	 */
	public function handle(ServerRequestInterface $request, RouterInterface $router): ServerRequestInterface
	{
		$this->hooks->register('admin_menu', fn () => $this->registerWPAdmin($router));

		if (is_admin() && isset($_GET['page']) && str_starts_with($_GET['page'], $this->app->slug() . '.')) {
			$path = str_replace($this->app->slug(), '', $_GET['page']);
			$path = str_replace('.', '/', $path);

			$request = ServerRequestFactory::fromGlobals(
				array_merge($_SERVER, [
					'REQUEST_URI' => $path,
				])
			);
		}

		return $request;
	}

	/**
	 * Search for wp-admin routes and add in WordPress
	 */
	protected function registerWPAdmin(RouterInterface $router): void
	{
		$type   = 'admin';
		$routes = $router->getRoutesByDefinitionType($type);

		foreach ($routes as $route) {
			// Only add GET routes to WordPress admin menu
			if ($route->getMethod() !== 'GET') {
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

	/*
	 * Add a menu page to WordPress
	 */
	protected function addMenuPage(RouteInterface $route): void
	{
		$path       = str_replace('/', '.', $route->getPath());
		$definition = $route->getDefinition();
		/** @var string $capability */
		$capability = $definition->meta('capability', 'manage_options');
		/** @var string $icon */
		$icon = $definition->meta('icon', '');
		/** @var float|int|null $position */
		$position = $definition->meta('position');
		add_menu_page(
			$definition->title(),
			$definition->title(),
			$capability,
			$this->app->slug() . $path,
			$this->getRenderedView(...),
			$icon,
			$position,
		);
	}

	/*
	 * Add a submenu page to WordPress
	 */
	protected function addSubMenuPage(RouteInterface $route): void
	{
		$path       = str_replace('/', '.', $route->getPath());
		$parent     = str_replace('/', '.', $route->getParentGroup()?->getPrefix() ?? '');
		$definition = $route->getDefinition();
		/** @var string $capability */
		$capability = $definition->meta('capability', 'manage_options');
		/** @var float|int|null $position */
		$position = $definition->meta('position');
		add_submenu_page(
			$this->app->slug() . $parent,
			$definition->title(),
			$definition->title(),
			$capability,
			$this->app->slug() . $path,
			$this->getRenderedView(...),
			$position,
		);
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
