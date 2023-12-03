<?php

namespace OrkestraWP\Proxies;

use Orkestra\App;
use Orkestra\Interfaces\HooksInterface;
use Orkestra\Services\Http\Interfaces\RouteInterface;
use Orkestra\Services\View\HtmlTag;
use Orkestra\Services\View\Twig\OrkestraExtension;
use Orkestra\Services\View\View;
use Twig\Environment;

class ViewProxy extends View
{
	public function __construct(
		protected App            $app,
		protected HooksInterface $hooks,
		protected Environment    $twig,
		protected RouteInterface $route,
	) {
		parent::__construct($twig);
	}

	public function render($name, array $context = []): string
	{
		$route = $this->route;
		$group = $route->getParentGroup();

		/** @var string[] */
		$wpTypes = $this->app->hookQuery('view.wp_types', [
			'api',
			'admin',
		]);

		$type = $route->getDefinition()->type();
		$type = empty($type) && $group ? $group->getDefinition()->type() : $type;

		if (!in_array($type, $wpTypes, true)) {
			// As this is not a WP call, we render the template as normal then exit.
			$this->app->hookRegister('http.router.response.after', fn () => exit);
			return parent::render($name, $context);
		}

		$name       = rtrim($name, '.twig') . '.twig';
		$content    = $this->twig->render($name, $context);
		$headData   = $this->twig->getExtension(OrkestraExtension::class)->getHead();
		$footerData = $this->twig->getExtension(OrkestraExtension::class)->getFooter();

		$this->hooks->register('admin_enqueue_scripts', fn () => $this->enqueueAssets($headData));
		$this->hooks->register('admin_enqueue_scripts', fn () => $this->enqueueAssets($footerData, true));

		$this->hooks->register('wp_enqueue_scripts', fn () => $this->enqueueAssets($headData));
		$this->hooks->register('wp_enqueue_scripts', fn () => $this->enqueueAssets($footerData, true));

		$this->app->hookRegister('view.content', fn () => $content);

		// Full content
		$htmlBlock   = $this->twig->getExtension(OrkestraExtension::class)->getHtmlBlock();
		$head        = new HtmlTag('head', [], join('', $headData));
		$body        = new HtmlTag('body', [], $content . join('', $footerData));
		$fullContent = '<!DOCTYPE html>' . $htmlBlock->setContent($head . $body);

		$this->app->hookRegister('view.full_content', fn () => $fullContent);

		return '';
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
