<?php

namespace OrkestraWP\Proxies;

use Orkestra\Configuration as CoreProxy;

class Configuration extends CoreProxy
{
	public function get(string $key, mixed $default = null): mixed
	{
		/** @var string */
		$root = $this->get('root');
		return match ($key) {
			'assets' => plugins_url('public/assets', $root),
			default  => parent::get($key),
		};
	}
}
