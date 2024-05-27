<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Providers\HooksProvider as CoreHooksProvider;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;

use OrkestraWP\Proxies\HooksProxy;

class HooksProvider extends CoreHooksProvider
{
	public function register(App $app): void
	{
		parent::register($app);
		$app->bind(HooksInterface::class, HooksProxy::class);
	}
}
