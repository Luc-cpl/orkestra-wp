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

		$content = $this->wpRender($type, $name, $context);

		$this->app->hookRegister('view.content', fn () => $content);

		return $this->isWPType($type) ? '' : $content;
	}
}
