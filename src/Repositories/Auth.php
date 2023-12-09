<?php

namespace OrkestraWP\Repositories;

class Auth
{
	public readonly ?\WP_User $user;
	public readonly bool $authenticated;

	public function __construct(?\WP_User $user = null)
	{
		if ($user) {
			$this->user = $user;
			$this->authenticated = $user->exists();
			return;
		}
		$user = wp_get_current_user();
		$this->user = $user->exists() ? $user : null;
		$this->authenticated = $user->exists();
	}

	public function authenticate(string $username, string $password): self
	{
		if (empty($username) || empty($password)) {
			return new self();
		}

		$isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);

		$user = $isEmail
			? get_user_by('email', $username)
			: get_user_by('login', $username);

		if (!$user) {
			return new self();
		}

		$authenticated = wp_check_password($password, $user->user_pass, $user->ID);

		// Login through WordPress
		if ($authenticated) {
			wp_set_current_user($user->ID, $user->user_login);
			wp_set_auth_cookie($user->ID);
			do_action('wp_login', $user->user_login, $user);
		}

		return new self($authenticated ? $user : null);
	}
}
