<?php

namespace OrkestraWP\Controllers;

use Orkestra\App;
use DI\Attribute\Inject;
use OrkestraWP\Proxies\Views\BlockViewProxy;

/**
 * AbstractBlockController
 */
abstract class AbstractBlockController
{
	#[Inject]
	protected App $app;

	#[Inject]
	protected BlockViewProxy $view;

	/**
	 * Render a view
	 *
	 * @param string $name
	 * @param mixed[] $context
	 * @return string
	 */
	protected function render(string $name, array $context = []): string
	{
		return $this->view->render($name, $context);
	}
}
