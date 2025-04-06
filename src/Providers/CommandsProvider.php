<?php

namespace OrkestraWP\Providers;

use Orkestra\App;
use Orkestra\Handlers\CommandsHandler as CoreCommandsHandler;
use Orkestra\Providers\CommandsProvider as CoreProvider;
use OrkestraWP\Handlers\CommandsHandler;

class CommandsProvider extends CoreProvider
{
	/**
	 * Register services with the container.
	 *
	 * @param App $app
	 * @return void
	 */
	public function register(App $app): void
	{
		parent::register($app);
		$app->bind(CoreCommandsHandler::class, CommandsHandler::class);
	}
}
