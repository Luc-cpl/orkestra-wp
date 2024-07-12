<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\Services\Http\Facades\RouteDefinitionFacade;

class HttpViewProxy extends AbstractViewProxy
{
	public function render($name, array $context = []): string
	{
		$route = $context['route'] ?? false;

		if (!$route instanceof RouteDefinitionFacade) {
			return $this->defaultView->render($name, $context);
		}

		$type = $route->type();

		if (!$this->isWPType($type)) {
			// As this is not a WP call, we render the template as normal then exit.
			$this->app->hookRegister('http.router.response.after', fn () => exit);
			return $this->defaultView->render($name, $context);
		}

		$content = $this->wpRender($name, $context);

		$this->app->hookRegister('view.content', fn () => $content);

		return $this->isWPType($type) ? '' : $content;
	}

	protected function isWPType(string $type): bool
	{
		/** @var string[] */
		$wpTypes = $this->app->hookQuery('view.wp_types', [
			'api',
			'admin',
			'block',
		]);

		return in_array($type, $wpTypes, true);
	}
}
