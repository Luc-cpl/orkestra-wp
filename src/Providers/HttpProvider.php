<?php

namespace OrkestraWP\Providers;

use Orkestra\Providers\HttpProvider as CoreProvider;
use OrkestraWP\Listeners\AdminDispatch;
use OrkestraWP\Listeners\ApiDispatch;

class HttpProvider extends CoreProvider
{
	/**
	 * @var class-string[]
	 */
	public array $listeners = [
		AdminDispatch::class,
		ApiDispatch::class,
	];
}
