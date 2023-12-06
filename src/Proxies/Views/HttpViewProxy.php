<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Interfaces\HooksInterface;
use Orkestra\Services\Http\Interfaces\RouteInterface;
use Twig\Environment;

class HttpViewProxy extends AbstractViewProxy
{
	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
		protected Environment    $twig,
		protected RouteInterface $route,
	) {
		parent::__construct($app, $hooks, $twig);
	}

	public function render($name, array $context = []): string
	{
		$route = $this->route;
		$group = $route->getParentGroup();

		$type = $route->getDefinition()->type();
		$type = empty($type) && $group ? $group->getDefinition()->type() : $type;

		$content = $this->wpRender($type, $name, $context);

		$this->app->hookRegister('view.content', fn () => $content);

		return $this->isWPType($type) ? '' : $content;
	}
}
