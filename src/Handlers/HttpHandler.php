<?php

namespace OrkestraWP\Handlers;

use League\Route\Http\Exception\NotFoundException;
use Orkestra\Handlers\HttpHandler as CoreHttpHandler;
use Orkestra\Interfaces\HandlerInterface;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;

class HttpHandler extends CoreHttpHandler implements HandlerInterface
{
	/**
	 * Handle the current request.
	 * This should be called to handle the current request from the provider.
	 */
	public function handle(): void
	{
		$app = $this->app;

		$app->runIfAvailable(HooksInterface::class, function (HooksInterface $hooks) {
			// Run our router after all plugins are loaded
			$hooks->register('init', function () {
				/**
				 * In WordPress environment, we need to bypass
				 * the router on not found routes as this is
				 * handled by WordPress itself.
				 */
				try {
					parent::handle();
				} catch (NotFoundException) {
				}
			});
		});
	}
}
