<?php

namespace Cms\Classes;

use Illuminate\Console\View\Components\Error;
use Illuminate\Console\View\Components\Info;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use System\Classes\Extensions\ExtensionManager;
use System\Classes\Extensions\ExtensionManagerInterface;
use System\Classes\Extensions\Source\ExtensionSource;
use Winter\Storm\Foundation\Extension\WinterExtension;
use System\Models\Parameter;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Facades\File;

/**
 * Theme manager
 *
 * @package winter\wn-cms-module
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeManager extends ExtensionManager implements ExtensionManagerInterface
{
    /**
     * Returns a collection of themes installed via the update gateway
     * @return array
     */
    public function getInstalled(): array
    {
        return Parameter::get('system::theme.history', []);
    }

    /**
     * Checks if a theme has ever been installed before.
     * @param  string  $name Theme code
     * @return boolean
     */
    public function isInstalled(ExtensionSource|WinterExtension|string $name): bool
    {
        $code = $this->resolveIdentifier($name);
        return array_key_exists($code, Parameter::get('system::theme.history', []));
    }

    /**
     * Returns an installed theme's code from it's dirname.
     * @return string
     */
    public function findByDirName($dirName)
    {
        $installed = $this->getInstalled();
        foreach ($installed as $code => $name) {
            if ($dirName == $name) {
                return $code;
            }
        }

        return null;
    }

    public function list(): array
    {
        $themes = Theme::all();
        return array_combine(
            array_map(fn ($theme) => $theme->getIdentifier(), $themes),
            $themes
        );
    }

    public function create(string $extension): Theme
    {
        $this->renderComponent(Info::class, sprintf('Running command `create:theme %s`.', $extension));

        $result = Artisan::call('create:theme', [
            'theme' => $extension,
            '--uninspiring' => true,
        ], $this->getOutput());

        $result === 0
            ? $this->renderComponent(Info::class, 'Theme created successfully.')
            : $this->renderComponent(Error::class, 'Unable to create theme.');

        // Return an instance of the plugin
        return $this->get($extension);
    }

    public function install(ExtensionSource|WinterExtension|string $extension): Theme
    {
        $theme = $this->resolve($extension);
        $code = $theme->getIdentifier();

        $dirName = strtolower(str_replace('.', '-', $code));

        $history = Parameter::get('system::theme.history', []);
        $history[$code] = $dirName;
        Parameter::set('system::theme.history', $history);

        $this->renderComponent(Info::class, 'Theme <fg=yellow>' . $code . '</> installed successfully.');

        return $theme;
    }

    public function get(WinterExtension|ExtensionSource|string $extension): ?WinterExtension
    {
        if ($extension instanceof WinterExtension) {
            return $extension;
        }

        if ($extension instanceof ExtensionSource) {
            $extension = $extension->getCode();
        }

        if (is_string($extension)) {
            return Theme::load($extension);
        }

        return null;
    }

    public function enable(WinterExtension|string $extension, string|bool $flag = self::DISABLED_BY_USER): Theme
    {
        // TODO: Implement enable() method.
    }

    public function disable(WinterExtension|string $extension, string|bool $flag = self::DISABLED_BY_USER): Theme
    {
        // TODO: Implement disable() method.
    }

    public function update(WinterExtension|string|null $extension = null, bool $migrationsOnly = false): Theme
    {
        // TODO: Implement update() method.
    }

    public function refresh(WinterExtension|string|null $extension = null): Theme
    {
        // TODO: Implement refresh() method.
    }

    public function rollback(WinterExtension|string|null $extension = null, ?string $targetVersion = null): Theme
    {
        // TODO: Implement rollback() method.
    }

    /**
     * Completely delete a theme from the system.
     * @param WinterExtension|string $theme Theme code/namespace
     * @return mixed
     * @throws ApplicationException
     */
    public function uninstall(WinterExtension|string|null $theme = null): mixed
    {
        if (!$theme) {
            return false;
        }

        if (is_string($theme)) {
            $theme = Theme::load($theme);
        }

        if ($theme->isActiveTheme()) {
            throw new ApplicationException(trans('cms::lang.theme.delete_active_theme_failed'));
        }

        $theme->removeCustomData();

        /*
         * Delete from file system
         */
        $themePath = $theme->getPath();
        if (File::isDirectory($themePath)) {
            File::deleteDirectory($themePath);
        }

        /*
         * Set uninstalled
         */
        if ($themeCode = $this->findByDirName($theme->getDirName())) {
            $history = Parameter::get('system::theme.history', []);
            if (array_key_exists($themeCode, $history)) {
                unset($history[$themeCode]);
            }

            Parameter::set('system::theme.history', $history);
        }

        return true;
    }

    /**
     * @deprecated TODO: Remove this
     *
     * @param $theme
     * @return mixed
     * @throws ApplicationException
     */
    public function deleteTheme($theme): mixed
    {
        return $this->uninstall($theme);
    }

    public function availableUpdates(WinterExtension|string|null $extension = null): ?array
    {
        // TODO: Implement availableUpdates() method.
    }

    public function tearDown(): static
    {
        // TODO: Implement tearDown() method.
    }
}
