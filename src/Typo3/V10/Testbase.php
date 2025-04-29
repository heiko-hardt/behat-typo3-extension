<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Typo3\V10;

use HeikoHardt\Behat\TYPO3Extension\Typo3\AbstractTestbase;
use TYPO3\TestingFramework\Core\Exception;

class Testbase extends AbstractTestbase
{
    public function setUpInstanceCoreLinks(
        $instancePath
    ): void {

        /* original ######################################################
        $linksToSet = [
            '../../../../' => $instancePath . '/typo3_src',
            'typo3_src/typo3/sysext/' => $instancePath . '/typo3/sysext',
        ];
        // ############################################################ */

        $linksToSet = [
            getenv('TYPO3_PATH_WEB') => $instancePath . '/typo3_src',
            getenv('TYPO3_PATH_WEB') . '/typo3/sysext/' => $instancePath . '/typo3/sysext',
        ];

        chdir($instancePath);

        $this->createDirectory($instancePath . '/typo3');
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
            $instancePath . '/typo3/sysext/backend/Resources/Private/Php/backend.php' => $instancePath . '/typo3/index.php',
            $instancePath . '/typo3/sysext/frontend/Resources/Private/Php/frontend.php' => $instancePath . '/index.php',
            $instancePath . '/typo3/sysext/install/Resources/Private/Php/install.php' => $instancePath . '/typo3/install.php',
        ];

        foreach ($entryPointsToSet as $source => $target) {
            if (($entryPointContent = file_get_contents($source)) === false) {
                throw new \UnexpectedValueException(sprintf('Source file (%s) was not found.', $source), 1636244753);
            }
            $entryPointContent = (string)preg_replace(
                '/__DIR__ \. \'[^\']+\'/',
                $this->findShortestPathCode($target, realpath(PHPUNIT_COMPOSER_INSTALL)),
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

    private function findShortestPathCode(string $from, string $to): string
    {
        if ($from === $to) {
            return '__FILE__';
        }

        $commonPath = $to;
        while (strpos($from . '/', $commonPath . '/') !== 0 && '/' !== $commonPath && preg_match('{^[a-z]:/?$}i', $commonPath) !== false && '.' !== $commonPath) {
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

}
