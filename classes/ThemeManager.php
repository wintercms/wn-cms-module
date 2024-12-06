<?php

namespace Cms\Classes;

use System\Classes\Extensions\ExtensionManager;
use System\Classes\Extensions\Source\ExtensionSource;
use System\Classes\Extensions\WinterExtension;
use System\Models\Parameter;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Facades\File;

/**
 * Theme manager
 *
 * @package winter\wn-cms-module
 * @author Alexey Bobkov, Samuel Georges
 */
class ThemeManager implements ExtensionManager
{
    use \Winter\Storm\Support\Traits\Singleton;

    /**
     * Returns a collection of themes installed via the update gateway
     * @return array
     */
    public function getInstalled()
    {
        return Parameter::get('system::theme.history', []);
    }

    /**
     * Checks if a theme has ever been installed before.
     * @param  string  $name Theme code
     * @return boolean
     */
    public function isInstalled($name): bool
    {
        return array_key_exists($name, Parameter::get('system::theme.history', []));
    }

    /**
     * Flags a theme as being installed, so it is not downloaded twice.
     * @param string $code Theme code
     * @param string|null $dirName
     */
    public function setInstalled($code, $dirName = null)
    {
        if (!$dirName) {
            $dirName = strtolower(str_replace('.', '-', $code));
        }

        $history = Parameter::get('system::theme.history', []);
        $history[$code] = $dirName;
        Parameter::set('system::theme.history', $history);
    }

    /**
     * Flags a theme as being uninstalled.
     * @param string $code Theme code
     */
    public function setUninstalled($code)
    {
        $history = Parameter::get('system::theme.history', []);
        if (array_key_exists($code, $history)) {
            unset($history[$code]);
        }

        Parameter::set('system::theme.history', $history);
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
        // TODO: Implement list() method.
        return [];
    }

    public function create(): Theme
    {
        // TODO: Implement create() method.
    }

    public function install(ExtensionSource|WinterExtension|string $extension): Theme
    {
        // TODO: Implement install() method.
    }

    public function getExtension(WinterExtension|ExtensionSource|string $extension): ?WinterExtension
    {
        // TODO: Implement getExtension() method.
    }

    public function enable(WinterExtension|string $extension): Theme
    {
        // TODO: Implement enable() method.
    }

    public function disable(WinterExtension|string $extension): Theme
    {
        // TODO: Implement disable() method.
    }

    public function update(WinterExtension|string $extension): Theme
    {
        // TODO: Implement update() method.
    }

    public function refresh(WinterExtension|string $extension): Theme
    {
        // TODO: Implement refresh() method.
    }

    public function rollback(WinterExtension|string $extension, string $targetVersion): Theme
    {
        // TODO: Implement rollback() method.
    }

    /**
     * Completely delete a theme from the system.
     * @param WinterExtension|string $theme Theme code/namespace
     * @return mixed
     * @throws ApplicationException
     */
    public function uninstall(WinterExtension|string $theme): mixed
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
            $this->setUninstalled($themeCode);
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
}
