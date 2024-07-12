<?php

namespace OrkestraWP\Proxies\Views;

class WPViewProxy extends AbstractViewProxy
{
	public function render($name, array $context = []): string
	{
		return $this->wpRender('block', $name, $context);
	}
}
