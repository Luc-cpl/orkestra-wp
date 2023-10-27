<?php

namespace OrkestraWP\Proxies;

use Orkestra\Proxies\ConfigProxy as CoreProxy;

class ConfigProxy extends CoreProxy
{
	public function get(string $key): mixed
	{
		return match ($key) {
			'assets' => plugins_url('assets', $this->service->get('root')),
			default  => $this->service->get($key),
		};
	}
}