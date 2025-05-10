<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Helper;

use HeikoHardt\Behat\TYPO3Extension\Helper\Exception;

class Filesystem
{
    /**
     * Check if directory exist and remove trailing slash (if exist)
     *
     * @param string $directoryPath
     * @return string
     * @throws Exception
     */
    public static function checkDirectoryPath(
        string $directoryPath
    ): string {
        $directoryPath = trim($directoryPath);
        $directoryPath = substr($directoryPath, -1, 1) === '/'
            ? substr($directoryPath, 0, -1)
            : $directoryPath;
        if (!is_dir($directoryPath)) {
            throw new Exception('Directory not found: ' . $directoryPath, 1630663362);
        }

        return $directoryPath;
    }

    /**
     * Creates directories, recursively if required.
     *
     * @param string $directory Absolute path to directories to create
     * @throws Exception
     */
    public static function createDirectory($directory): void
    {
        if (is_dir($directory)) {
            return;
        }
        @mkdir($directory, 0777, true);
        clearstatcache();
        if (!is_dir($directory)) {
            throw new Exception(
                'Directory "' . $directory . '" could not be created',
                1404038665
            );
        }
    }

    public static function clearDirectory(
        string $directory
    ): void {
        $files = array_diff(scandir($directory), ['.', '..']);
        foreach ($files as $file) {
            (is_dir($directory . '/' . $file) && !is_link($directory . '/' . $file))
                ? self::removeDirectory($directory . '/' . $file)
                : unlink($directory . '/' . $file);
        }
    }

    public static function removeDirectory(
        string $directory
    ): bool {
        self::clearDirectory($directory);

        return rmdir($directory);
    }

    /**
     * Link TYPO3 CMS core from "parent" instance.
     * For functional and acceptance tests.
     * Original: https://github.com/TYPO3/testing-framework/blob/main/Classes/Core/Testbase.php#L189
     *
     * @param string $instancePath Absolute path to test instance
     * @throws Exception
     */
    public static function setUpInstanceCoreLinks(
        $originDirectoryPath,
        $testingDirectoryPath
    ): void {
        $linksToSet = [
            $originDirectoryPath . '/public/' => $testingDirectoryPath . '/typo3_src',
            $originDirectoryPath . '/public/typo3/sysext/' => $testingDirectoryPath . '/typo3/sysext',
        ];

        chdir($testingDirectoryPath);

        self::createDirectory($testingDirectoryPath . '/typo3');

        foreach ($linksToSet as $from => $to) {
            $success = symlink(realpath($from), $to);
            if (!$success) {
                throw new Exception(
                    'Creating link failed: from ' . $from . ' to: ' . $to,
                    1376657199
                );
            }
        }

        // We can't just link the entry scripts here, because acceptance tests will make use of them
        // and we need Composer Mode to be turned off, thus they need to be rewritten to use the SystemEnvironmentBuilder
        // of the testing framework.
        $entryPointsToSet = [
            $testingDirectoryPath . '/typo3/sysext/backend/Resources/Private/Php/backend.php' =>
                $testingDirectoryPath . '/typo3/index.php',
            $testingDirectoryPath . '/typo3/sysext/frontend/Resources/Private/Php/frontend.php' =>
                $testingDirectoryPath . '/index.php',
            $testingDirectoryPath . '/typo3/sysext/install/Resources/Private/Php/install.php' =>
                $testingDirectoryPath . '/typo3/install.php',
        ];

        $autoloadFile = isset($GLOBALS['_composer_autoload_path'])
            ? $GLOBALS['_composer_autoload_path']
            : $originDirectoryPath . '/vendor/autoload.php';

        foreach ($entryPointsToSet as $source => $target) {
            if (($entryPointContent = file_get_contents($source)) === false) {
                throw new \UnexpectedValueException(sprintf('Source file (%s) was not found.', $source), 1636244753);
            }
            $entryPointContent = (string)preg_replace(
                '/__DIR__ \. \'[^\']+\'/',
                self::findShortestPathCode($target, $autoloadFile),
                $entryPointContent
            );
            $entryPointContent = (string)preg_replace(
                '/\\\\TYPO3\\\\CMS\\\\Core\\\\Core\\\\SystemEnvironmentBuilder::run\(/',
                '\TYPO3\TestingFramework\Core\SystemEnvironmentBuilder::run(',
                $entryPointContent
            );
            if ($entryPointContent === '') {
                throw new \UnexpectedValueException(
                    sprintf('Error while customizing the source file (%s).', $source),
                    1636244910
                );
            }
            file_put_contents($target, $entryPointContent);
        }
    }

    /**
     * Returns PHP code that, when executed in $from, will return the path to $to
     * Copied from Composer sources and adapted for limited use case here
     * Original: https://github.com/TYPO3/testing-framework/blob/main/Classes/Core/Testbase.php#L127
     *
     * @see https://github.com/composer/composer
     * @throws \InvalidArgumentException
     */
    public static function findShortestPathCode(
        string $from,
        string $to
    ): string {
        if ($from === $to) {
            return '__FILE__';
        }

        $commonPath = $to;
        while (strpos($from . '/', $commonPath . '/') !== 0 && '/' !== $commonPath && preg_match(
            '{^[a-z]:/?$}i',
            $commonPath
        ) !== false && '.' !== $commonPath) {
            $commonPath = str_replace('\\', '/', \dirname($commonPath));
        }

        if ('/' === $commonPath || '.' === $commonPath || 0 !== strpos($from, $commonPath)) {
            return var_export($to, true);
        }

        $commonPath = rtrim($commonPath, '/') . '/';
        if (strpos($to, $from . '/') === 0) {
            return '__DIR__ . ' . var_export(substr($to, \strlen($from)), true);
        }
        $sourcePathDepth = substr_count(substr($from, \strlen($commonPath)), '/');
        $commonPathCode = "__DIR__ . '" . str_repeat('/..', $sourcePathDepth) . "'";
        $relTarget = substr($to, \strlen($commonPath));

        return $commonPathCode . ($relTarget !== '' ? ' . ' . var_export('/' . $relTarget, true) : '');
    }

    public static function setUpInstanceHtaccess(
        $originDirectoryPath,
        $testingDirectoryPath,
        $absoluteFilePath = null
    ): bool {
        if ($absoluteFilePath && is_file($absoluteFilePath)) {
            return copy(
                $absoluteFilePath,
                $testingDirectoryPath . '/.htaccess'
            );
        } elseif (is_file($originDirectoryPath . '/vendor/typo3/cms-install/Resources/Private/FolderStructureTemplateFiles/root-htaccess')) {
            return copy(
                $originDirectoryPath . '/vendor/typo3/cms-install/Resources/Private/FolderStructureTemplateFiles/root-htaccess',
                $testingDirectoryPath . '/.htaccess'
            );
        } elseif (is_file($originDirectoryPath . '/public/typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/root-htaccess')) {
            return copy(
                $originDirectoryPath . '/public/typo3/sysext/install/Resources/Private/FolderStructureTemplateFiles/root-htaccess',
                $testingDirectoryPath . '/.htaccess'
            );
        }
        return false;
    }

    /**
     * Link test extensions to the typo3conf/ext folder of the instance.
     * For functional and acceptance tests.
     * Original: https://github.com/TYPO3/testing-framework/blob/main/Classes/Core/Testbase.php#L250
     *
     * @param string $originDirectoryPath Absolute path to origin instance
     * @param string $testingDirectoryPath Absolute path to test instance
     * @param array $extensionPaths Contains paths to extensions relative to document root
     * @throws Exception
     */
    public static function linkTestExtensionsToInstance(
        string $originDirectoryPath,
        string $testingDirectoryPath,
        array $extensionPaths
    ): void {
        foreach ($extensionPaths as $extensionPath) {
            $absoluteExtensionPath = $originDirectoryPath . '/public/typo3conf/ext/' . $extensionPath;
            if (!is_dir($absoluteExtensionPath)) {
                throw new Exception(
                    'Test extension path ' . $absoluteExtensionPath . ' not found',
                    1376745645
                );
            }
            $destinationPath = $testingDirectoryPath . '/typo3conf/ext/' . basename($absoluteExtensionPath);
            $success = symlink($absoluteExtensionPath, $destinationPath);
            if (!$success) {
                throw new Exception(
                    'Can not link extension folder: ' . $absoluteExtensionPath . ' to ' . $destinationPath,
                    1376657142
                );
            }
        }
    }
}
