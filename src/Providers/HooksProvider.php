<?php

namespace OrkestraWP\Providers;

use Orkestra\Interfaces\ProviderInterface;
use Orkestra\Interfaces\HooksInterface;
use Orkestra\App;

use OrkestraWP\Proxies\HooksProxy;

class HooksProvider implements ProviderInterface
{
	public function register(App $app): void
	{
		$app->singleton(HooksInterface::class, HooksProxy::class);
	}

	public function boot(App $app): void
	{

	}
}