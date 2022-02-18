<?php namespace Cms\Twig;

use Block;
use Event;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFilter as TwigSimpleFilter;
use Twig\TwigFunction as TwigSimpleFunction;

/**
 * The CMS Twig extension class implements the basic CMS Twig functions and filters.
 *
 * @package winter\wn-cms-module
 * @author Alexey Bobkov, Samuel Georges
 */
class Extension extends TwigExtension
{
    /**
     * Returns an array of functions to add to the existing list.
     */
    public function getFunctions(): array
    {
        $options = [
            'is_safe' => ['html'],
            'needs_context' => true,
        ];

        return [
            new TwigSimpleFunction('page', [$this, 'pageFunction'], $options),
            new TwigSimpleFunction('partial', [$this, 'partialFunction'], $options),
            new TwigSimpleFunction('content', [$this, 'contentFunction'], $options),
            new TwigSimpleFunction('component', [$this, 'componentFunction'], $options),
            new TwigSimpleFunction('placeholder', [$this, 'placeholderFunction'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Returns an array of filters this extension provides.
     */
    public function getFilters(): array
    {
        $options = [
            'is_safe' => ['html'],
            'needs_context' => true,
        ];

        return [
            new TwigSimpleFilter('page', [$this, 'pageFilter'], $options),
            new TwigSimpleFilter('theme', [$this, 'themeFilter'], $options),
        ];
    }

    /**
     * Returns an array of token parsers this extension provides.
     */
    public function getTokenParsers(): array
    {
        return [
            new PageTokenParser,
            new PartialTokenParser,
            new ContentTokenParser,
            new PutTokenParser,
            new PlaceholderTokenParser,
            new DefaultTokenParser,
            new FrameworkTokenParser,
            new SnowboardTokenParser,
            new ComponentTokenParser,
            new FlashTokenParser,
            new ScriptsTokenParser,
            new StylesTokenParser,
        ];
    }

    /**
     * Renders a page; used in the layout code to output the requested page.
     */
    public function pageFunction(array $context): string
    {
        return $context['this']['controller']->renderPage();
    }

    /**
     * Renders the requested partial with the provided parameters. Optionally throw an exception if the partial cannot be found
     */
    public function partialFunction(array $context, string $name, array $parameters = [], bool $throwException = false): string
    {
        return $context['this']['controller']->renderPartial($name, $parameters, $throwException);
    }

    /**
     * Renders the requested content file.
     */
    public function contentFunction(array $context, string $name, array $parameters = []): string
    {
        return $context['this']['controller']->renderContent($name, $parameters);
    }

    /**
     * Renders a component's default partial.
     */
    public function componentFunction(array $context, string $name, array $parameters = []): string
    {
        return $context['this']['controller']->renderComponent($name, $parameters);
    }

    /**
     * Renders registered assets of a given type or all types if $type not provided
     */
    public function assetsFunction(array $context, string $type = null): ?string
    {
        return $context['this']['controller']->makeAssets($type);
    }

    /**
     * Renders placeholder content, without removing the block, must be called before the placeholder tag itself
     */
    public function placeholderFunction(string $name, string $default = null): string
    {
        if (($result = Block::get($name)) === null) {
            return null;
        }

        $result = str_replace('<!-- X_WINTER_DEFAULT_BLOCK_CONTENT -->', trim($default), $result);
        return $result;
    }

    /**
     * Returns the relative URL for the provided page
     *
     * @param array $context The Twig context for the call (relies on $context['this']['controller'] to exist)
     * @param mixed $name Specifies the Cms Page file name.
     * @param array $parameters Route parameters to consider in the URL.
     * @param bool $routePersistence Set to false to exclude the existing routing parameters from the generated URL
     */
    public function pageFilter(array $context, $name, array $parameters = [], $routePersistence = true): string
    {
        return $context['this']['controller']->pageUrl($name, $parameters, $routePersistence);
    }

    /**
     * Converts supplied URL to a theme URL relative to the website root. If the URL provided is an
     * array then the files will be combined.
     *
     * @param array $context The Twig context for the call (relies on $context['this']['controller'] to exist)
     * @param mixed $url Specifies the input to be turned into a URL (arrays will be passed to the AssetCombiner)
     */
    public function themeFilter(array $context, $url): string
    {
        return $context['this']['controller']->themeUrl($url);
    }

    /**
     * Opens a layout block.
     */
    public function startBlock(string $name): string
    {
        Block::startBlock($name);
    }

    /**
     * Returns a layout block contents (or null if it doesn't exist) and removes the block.
     */
    public function displayBlock(string $name, string $default = null): ?string
    {
        if (($result = Block::placeholder($name)) === null) {
            return $default;
        }

        /**
         * @event cms.block.render
         * Provides an opportunity to modify the rendered block content
         *
         * Example usage:
         *
         *     Event::listen('cms.block.render', function ((string) $name, (string) $result) {
         *         if ($name === 'myBlockName') {
         *             return 'my custom content';
         *         }
         *     });
         *
         */
        if ($event = Event::fire('cms.block.render', [$name, $result], true)) {
            $result = $event;
        }

        $result = str_replace('<!-- X_WINTER_DEFAULT_BLOCK_CONTENT -->', trim($default), $result);
        return $result;
    }

    /**
     * Closes a layout block.
     */
    public function endBlock($append = true): void
    {
        Block::endBlock($append);
    }
}
