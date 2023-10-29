<?php

namespace OrkestraWP\Proxies;

use Orkestra\Configuration as CoreProxy;

class Configuration extends CoreProxy
{
	public function get(string $key): mixed
	{
		return match ($key) {
			'assets' => plugins_url('assets', $this->get('root')),
			default  => parent::get($key),
		};
	}
}