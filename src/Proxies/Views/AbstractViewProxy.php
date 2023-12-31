<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Services\Hooks\Interfaces\HooksInterface;
use Orkestra\Services\View\Interfaces\ViewInterface;
use Orkestra\Services\Http\Interfaces\RouteAwareInterface;
use Orkestra\Services\Http\Interfaces\RouteInterface;
use Orkestra\Services\View\HtmlTag;
use Orkestra\Services\View\Twig\OrkestraExtension;
use Orkestra\Services\View\View;
use Twig\Environment;

abstract class AbstractViewProxy implements ViewInterface, RouteAwareInterface
{
	protected View $defaultView;
	protected ?RouteInterface $route = null;

	/**
	 * @var array<string, array<string, bool|string|int|float|mixed[]>>
	 */
	private array $extraAttributes = [];

	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
		protected Environment    $twig,
	) {
		$this->defaultView = $app->get(View::class, ['twig' => $twig]);
	}

	public function setRoute(RouteInterface $route): self
	{
		$this->route = $route;
		return $this;
	}

	abstract public function render(string $name, array $context = []): string;

	/**
	 * @param mixed[] $context
	 */
	protected function wpRender(string $type, string $name, array $context = []): string
	{
		if (!$this->isWPType($type)) {
			// As this is not a WP call, we render the template as normal then exit.
			$this->app->hookRegister('http.router.response.after', fn () => exit);
			return $this->defaultView->render($name, $context);
		}

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

	protected function isWPType(string $type): bool
	{
		/** @var string[] */
		$wpTypes = $this->app->hookQuery('view.wp_types', [
			'api',
			'admin',
			'block',
		]);

		return in_array($type, $wpTypes, true);
	}

	/**
	 * Enqueue assets from the head and footer
	 *
	 * @param HtmlTag[] $data
	 * @param boolean   $footer
	 */
	private function enqueueAssets(array $data, bool $footer = false): void
	{
		$slug = $this->app->slug();

		$scriptIndex = 0;
		$styleIndex  = 0;

		foreach ($data as $tag) {
			// Skip if not a script or link tag
			if (!in_array($tag->tag, ['script', 'link'])) {
				continue;
			}

			$script  = $tag->tag === 'script';
			/** @var string */
			$src     = $tag->getAttribute('src') ?? $tag->getAttribute('href');
			$content = $tag->content;
			/** @var string */
			$version = $tag->getAttribute('version');
			/** @var string[] */
			$dependencies = $tag->getAttribute('dependencies') ?? [];

			if (empty($src) && empty($content)) {
				continue;
			}

			if (!empty($content) && $script) {
				wp_add_inline_script(
					"$slug-$scriptIndex",
					$content,
					$scriptIndex === 0 ? 'before' : 'after'
				);
				continue;
			}

			if (!empty($content)) {
				wp_add_inline_style(
					"$slug-$styleIndex",
					$content,
				);
				continue;
			}

			if ($script) {
				$handle = "$slug-$scriptIndex";
				$dependency = $slug . '-' . ($scriptIndex - 1);
				wp_enqueue_script(
					$handle,
					$src,
					$scriptIndex === 0 ? $dependencies : [...$dependencies, $dependency],
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

				$scriptIndex++;
				continue;
			}
			$dependency = $slug . '-' . ($styleIndex - 1);
			wp_enqueue_style(
				"$slug-$styleIndex",
				$src,
				$styleIndex === 0 ? $dependencies : [...$dependencies, $dependency],
				$version,
			);
			$styleIndex++;
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
