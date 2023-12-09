<?php

namespace OrkestraWP\Proxies;

use Closure;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;

final class HooksProxy implements HooksInterface
{
	public function call(string $tag, mixed ...$args): void
	{
		do_action($tag, ...$args);
	}

	public function query(string $tag, mixed $value, mixed ...$args): mixed
	{
		return apply_filters($tag, $value, ...$args);
	}

	public function register(string $tag, callable $callback, int $priority = 10): bool
	{
		$reflection = new \ReflectionFunction(Closure::fromCallable($callback));
		$args = $reflection->getNumberOfParameters();
		return add_filter($tag, $callback, $priority, $args);
	}

	public function remove(string $tag, callable $callback, int $priority = 10): bool
	{
		return remove_filter($tag, $callback, $priority) && remove_action($tag, $callback, $priority);
	}

	public function removeAll(string $tag, int|bool $priority = false): bool
	{
		remove_all_filters($tag, $priority);
		remove_all_actions($tag, $priority);
		return true;
	}

	public function has(string $tag, callable|false $callable = false): bool
	{
		return has_filter($tag, $callable) || has_action($tag, $callable);
	}

	public function did(string $tag): int
	{
		return did_action($tag);
	}

	public function doing(string $tag): bool
	{
		return doing_filter($tag) || doing_action($tag);
	}

	public function current(): string
	{
		return current_action();
	}
}
