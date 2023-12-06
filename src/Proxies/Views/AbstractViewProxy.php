<?php

namespace OrkestraWP\Proxies\Views;

use Orkestra\App;
use Orkestra\Interfaces\HooksInterface;
use Orkestra\Interfaces\ViewInterface;
use Orkestra\Services\View\HtmlTag;
use Orkestra\Services\View\Twig\OrkestraExtension;
use Orkestra\Services\View\View;
use Twig\Environment;

abstract class AbstractViewProxy implements ViewInterface
{
	protected View $defaultView;

	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
		protected Environment    $twig,
	) {
		$this->defaultView = $app->get(View::class, ['twig' => $twig]);
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
			/** @var string $src */
			$src     = $tag->getAttribute('src') ?? $tag->getAttribute('href');
			$content = $tag->content;
			/** @var string $version */
			$version = $tag->getAttribute('version');

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
				wp_enqueue_script(
					"$slug-$scriptIndex",
					$src,
					$scriptIndex === 0 ? [] : ["$slug-{($scriptIndex - 1)}"],
					$version,
					$footer
				);
				$scriptIndex++;
				continue;
			}
			wp_enqueue_style(
				"$slug-$styleIndex",
				$src,
				$styleIndex === 0 ? [] : ["$slug-{($styleIndex - 1)}"],
				$version,
			);
			$styleIndex++;
		}
	}
}
