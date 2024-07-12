<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;
use Orkestra\Services\View\Interfaces\ViewInterface;
use Orkestra\Services\View\HtmlTag;
use Orkestra\Services\View\Twig\OrkestraExtension;
use Twig\Environment;

abstract class AbstractViewProxy implements ViewInterface
{
	/**
	 * @var array<string, array<string, bool|string|int|float|mixed[]>>
	 */
	private array $extraAttributes = [];

	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
		protected Environment    $twig,
		protected ViewInterface  $defaultView,
	) {
		//
	}

	abstract public function render(string $name, array $context = []): string;

	/**
	 * @param mixed[] $context
	 */
	protected function wpRender(string $name, array $context = []): string
	{
		$name       = explode('.', $name, 1)[0] . '.twig';
		$content    = $this->twig->render($name, $context);
		$headData   = $this->twig->getExtension(OrkestraExtension::class)->getHead();
		$footerData = $this->twig->getExtension(OrkestraExtension::class)->getFooter();

		$this->hooks->register('admin_enqueue_scripts', fn () => $this->enqueueAssets($headData));
		$this->hooks->register('admin_enqueue_scripts', fn () => $this->enqueueAssets($footerData, true));

		$this->hooks->register('wp_enqueue_scripts', fn () => $this->enqueueAssets($headData));
		$this->hooks->register('wp_enqueue_scripts', fn () => $this->enqueueAssets($footerData, true));

		$this->hooks->register('script_loader_tag', $this->setExtraAttributes(...));

		return $content;
	}

	/**
	 * Enqueue assets from the head and footer
	 *
	 * @param HtmlTag[] $data
	 * @param boolean   $footer
	 */
	private function enqueueAssets(array $data, bool $footer = false): void
	{
		$currentScriptHandle = null;
		$currentStyleHandle = null;

		foreach ($data as $tag) {
			// Skip if not a script or link tag
			if (!in_array($tag->tag, ['script', 'link'])) {
				continue;
			}

			/** @var string */
			$src     = $tag->getAttribute('src') ?? $tag->getAttribute('href');
			$script  = $tag->tag === 'script';
			$content = $tag->content;

			/** @var string */
			$version = $tag->getAttribute('version');

			/** @var string[] */
			$dependencies = $tag->getAttribute('dependencies') ?? [];

			if (empty($src) && empty($content)) {
				continue;
			}

			// Inline script
			if (!empty($content) && $script) {
				// Register a initial script if needed
				if (!$currentScriptHandle) {
					$currentScriptHandle = hash('xxh32', $content);
					wp_register_script($currentScriptHandle, false, $dependencies, $version, $footer);
					wp_enqueue_script($currentScriptHandle);
				}

				wp_add_inline_script($currentScriptHandle, $content, 'after');
				continue;
			}

			// Inline style
			if (!empty($content)) {
				// Register a initial script if needed
				if (!$currentStyleHandle) {
					$currentStyleHandle = hash('xxh32', $content);
					wp_register_style($currentStyleHandle, false, $dependencies, $version);
					wp_enqueue_style($currentStyleHandle);
				}

				wp_add_inline_style($currentStyleHandle, $content);
				continue;
			}

			$handle = hash('xxh32', $src);

			if ($script) {
				$dependencies = $currentScriptHandle ? [...$dependencies, $currentScriptHandle] : $dependencies;
				wp_enqueue_script(
					$handle,
					$src,
					$dependencies,
					$version,
					$footer
				);

				$attributes = $tag->attributes;

				unset(
					$attributes['src'],
					$attributes['href'],
					$attributes['version'],
					$attributes['dependencies']
				);

				if (!empty($attributes)) {
					$this->extraAttributes[$handle] = $attributes;
				}

				$currentScriptHandle = $handle;
				continue;
			}

			$dependencies = $currentStyleHandle ? [...$dependencies, $currentStyleHandle] : $dependencies;
			wp_enqueue_style(
				$handle,
				$src,
				$dependencies,
				$version,
			);
			$currentStyleHandle = $handle;
		}
	}

	private function setExtraAttributes(string $tag, string $handle): string
	{
		if (!isset($this->extraAttributes[$handle])) {
			return $tag;
		}

		$attributes = $this->extraAttributes[$handle];
		$newAttributes = '';

		foreach ($attributes as $key => $value) {
			if (str_contains($tag, " $key=") || str_contains($tag, " $key ")) {
				/** @var string $tag */
				$tag = preg_replace("/ $key=\"[^\"]*\"/", '', $tag);
				/** @var string $tag */
				$tag = preg_replace("/ $key /", '', $tag);
			}
			if (is_array($value)) {
				$value = json_encode($value);
			}
			$newAttributes .= match ($value) {
				false => '',
				true  => " $key",
				default => sprintf(' %s="%s"', $key, $value),
			};
		}

		return str_replace('>', $newAttributes . '>', $tag);
	}
}
