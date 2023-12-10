<?php

namespace OrkestraWP\Listeners;

use Orkestra\App;
use Orkestra\Services\Http\Interfaces\RouterInterface;
use Orkestra\Services\Http\Entities\ParamDefinition;

use Laminas\Diactoros\ServerRequestFactory;
use Orkestra\Services\Hooks\Interfaces\ListenerInterface;
use WP_REST_Request;

class ApiDispatch implements ListenerInterface
{
	public function __construct(
		protected App			  $app,
		protected RouterInterface $router,
	) {
	}

	public function hook(): string
	{
		return 'rest_api_init';
	}

	/**
	 * Here we hook into the router boot event and register
	 * our api routes into WordPress.
	 */
	public function handle(): void
	{
		$type   = 'api';
		$router = $this->router;
		$routes = $router->getRoutesByDefinitionType($type);

		$wpRoutes = [];

		foreach ($routes as $route) {
			$originalGroupName = $route->getParentGroup()?->getPrefix() ?? '';
			// if exist, remove the /api/ from the start of group name
			$groupName = substr($originalGroupName, 0, 5) === '/api/'
				? substr($originalGroupName, 5)
				: $originalGroupName;
			$groupName = '/' . trim($groupName, '/');
			$namespace = $this->app->slug() . $groupName;

			$path = substr($route->getPath(), strlen($originalGroupName));
			$path = $this->getWPPath($path);

			$wpRoutes[$path] = $wpRoutes[$path] ?? [
				'namespace'   => $namespace,
				'accept_json' => true,
				'args'        => [],
			];

			$wpRoutes[$path]['args'][] = [
				'methods'             => $route->getMethod(),
				'description'         => $route->getDefinition()->description(),
				'args'                => $this->formatArgs(
					$route->getDefinition()->params()
				),
				'callback'            => function (WP_REST_Request $wpRequest) use ($router, $route, $namespace) {
					$server = $_SERVER;

					$prefix = '/' . rest_get_url_prefix() . '/' . $namespace;
					$uri = substr($server['REQUEST_URI'], strlen($prefix));
					$uri = $route->getParentGroup()?->getPrefix() . $uri;

					$server['REQUEST_URI']    = $uri;
					$server['REQUEST_METHOD'] = $wpRequest->get_method();

					$request = ServerRequestFactory::fromGlobals(
						$server,
						$wpRequest->get_query_params(),
						array_merge(
							(array) $wpRequest->get_body_params(),
							(array) $wpRequest->get_json_params()
						)
					);

					$response = $router->dispatch($request);

					return json_decode($response->getBody());
				},
				/** Allow all request so we can handle on our middleware */
				'permission_callback' => fn () => true,
			];
		}

		foreach ($wpRoutes as $r => $wpRoute) {
			register_rest_route($wpRoute['namespace'], $r, $wpRoute['args']);
		}
	}

	protected function getWPPath(string $path): string
	{
		$path = str_replace('{', '(?P<', $path);
		$path = str_replace('}', '>[^/]+)', $path);
		$path = preg_replace('/:.*/', '>[^/]+)', $path);
		/** @var string */
		return $path;
	}

	/**
	 * @param ParamDefinition[] $params
	 * @return mixed[]
	 */
	protected function formatArgs(array $params): array
	{
		$formatted = [];

		foreach ($params as $param) {
			$formatted[$param->name] = [
				'type'        => $param->type,
				'description' => $param->description,
				'required'    => $param->required,
				'default'     => $param->default,
				'enum'        => $param->enum,
			];
		}

		return $formatted;
	}
}
