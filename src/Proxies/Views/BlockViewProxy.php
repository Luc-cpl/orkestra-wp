<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;
use Twig\Environment;

class BlockViewProxy extends AbstractViewProxy
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
		return $this->wpRender('block', $name, $context);
	}
}
