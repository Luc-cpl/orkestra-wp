<?php

namespace OrkestraWP\Repositories;

use Orkestra\App;
use DI\Attribute\Inject;

class Auth
{
	/**
	 * Inject to allow extending the Auth repository
	 */
	#[Inject()]
	protected App $app;

	public readonly ?\WP_User $user;
	public readonly bool $authenticated;

	public function __construct(?\WP_User $user = null)
	{
		if ($user) {
			$this->user = $user;
			$this->authenticated = $user->exists();
			return;
		}
		if (!function_exists('wp_get_current_user')) {
			$this->user = null;
			$this->authenticated = false;
			return;
		}
		$user = wp_get_current_user();
		$this->user = $user->exists() ? $user : null;
		$this->authenticated = $user->exists();
	}

	public function authenticate(string $username, string $password, bool $login = true): self
	{
		if (empty($username) || empty($password)) {
			return $this->app->get(static::class);
		}

		$isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);

		$user = $isEmail
			? get_user_by('email', $username)
			: get_user_by('login', $username);

		if (!$user) {
			return $this->app->get(static::class);
		}

		$authenticated = wp_check_password($password, $user->user_pass, $user->ID);

		if (!$authenticated) {
			return $this->app->get(static::class);
		}

		// Login through WordPress
		if ($login) {
			wp_set_current_user($user->ID, $user->user_login);
			wp_set_auth_cookie($user->ID);
			do_action('wp_login', $user->user_login, $user);
		}

		return $this->app->get(static::class, ['user' => $user]);
	}
}
