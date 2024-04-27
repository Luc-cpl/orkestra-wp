<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;
use Orkestra\Services\Http\Facades\RouteDefinitionFacade;
use Twig\Environment;

class HttpViewProxy extends AbstractViewProxy
{
	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
		protected Environment    $twig,
	) {
		parent::__construct($app, $hooks, $twig);
	}

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
