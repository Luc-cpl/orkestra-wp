<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Interfaces\HooksInterface;
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
		$route = $this->route;

		if (!$route) {
			return $this->defaultView->render($name, $context);
		}

		$group = $route->getParentGroup();

		$type = $route->getDefinition()->type();
		$type = empty($type) && $group ? $group->getDefinition()->type() : $type;

		$content = $this->wpRender($type, $name, $context);

		$this->app->hookRegister('view.content', fn () => $content);

		return $this->isWPType($type) ? '' : $content;
	}
}
