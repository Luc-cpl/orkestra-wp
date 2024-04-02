<?php

namespace OrkestraWP\Providers;

use InvalidArgumentException;
use Orkestra\Interfaces\ProviderInterface;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;
use Orkestra\App;

class BlocksProvider implements ProviderInterface
{
	public function register(App $app): void
	{
		$app->config()->set('validation', [
			'blocks' => function ($value) {
				$value = $value ?? [];
				if (!is_array($value)) {
					return 'The blocks config must be an array';
				}

				return true;
			},
			'blocks_root' => function ($value) {
				$value = $value ?? '';
				if (!is_string($value)) {
					return 'The blocks root must be a string';
				}

				return true;
			},
		]);

		$app->config()->set('definition', [
			'blocks' => [false, 'The blocks to register with the app'],
			'blocks_root' => [false, 'The blocks root directory'],
		]);
	}

	public function boot(App $app): void
	{
		$app->runIfAvailable(HooksInterface::class, function (HooksInterface $hooks) use ($app) {
			$hooks->register('init', function () use ($app) {
				/** @var mixed[] */
				$blocks = $app->config()->get('blocks', []);
				/** @var string */
				$root = $app->config()->get('blocks_root', $app->config()->get('root'));
				$this->registerBlocks($app, $root, $blocks);
			});
		});
	}

	/**
	 * @param mixed[] $blocks
	 */
	protected function registerBlocks(App $app, string $root, array $blocks): void
	{
		/** @var callable $callback */
		foreach ($blocks as $key => $callback) {
			if (!is_string($key) && !is_string($callback)) {
				throw new InvalidArgumentException("The block \"$key\" must point to a block directory");
			}

			if (!is_string($key)) {
				/** @var string */
				$key = $callback;
				$callback = null;
			}

			$class = $callback && is_string($callback) ? explode('::', $callback)[0] : null;

			if ($class && !class_exists($class)) {
				throw new InvalidArgumentException("The block \"$key\" must point to a valid class");
			}

			$path = $root . ltrim($key, '/');
			$args = empty($callback) ? [] : [
				'render_callback' => function ($attributes, $content) use ($app, $callback): string {
					return $this->runCallback($app, $callback, [$attributes, $content]);
				},
			];

			register_block_type($path, $args);
		}
	}

	/**
	 * @param mixed[] $args
	 */
	protected function runCallback(App $app, callable|string $callback, array $args): string
	{
		if (is_string($callback)) {
			/** @var array{class-string,?string} */
			$handler = explode('::', $callback);
			$callback = $app->get($handler[0]);
			/** @var callable */
			$callback = isset($handler[1]) ? [$callback, $handler[1]] : $callback;
		}
		/** @var string */
		return call_user_func_array($callback, $args);
	}
}
