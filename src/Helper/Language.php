<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Helper;

class Language
{
    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de'],
        'DK' => ['id' => 2, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8', 'iso' => 'da'],
    ];

    public static function buildDefaultLanguageConfiguration(
        string $identifier,
        string $base
    ): array {
        $configuration = self::buildLanguageConfiguration($identifier, $base);
        $configuration['typo3Language'] = 'default';
        $configuration['flag'] = 'global';
        unset($configuration['fallbackType'], $configuration['fallbacks']);

        return $configuration;
    }

    public static function buildLanguageConfiguration(
        string $identifier,
        string $base,
        array $fallbackIdentifiers = [],
        string $fallbackType = null
    ): array {
        $preset = self::resolveLanguagePreset($identifier);

        $configuration = [
            'languageId' => $preset['id'],
            'title' => $preset['title'],
            'navigationTitle' => $preset['title'],
            'base' => $base,
            'locale' => $preset['locale'],
            'iso-639-1' => $preset['iso'] ?? '',
            'hreflang' => $preset['hrefLang'] ?? '',
            'direction' => $preset['direction'] ?? '',
            'typo3Language' => $preset['iso'] ?? '',
            'flag' => $preset['iso'] ?? '',
            'fallbackType' => $fallbackType ?? (empty($fallbackIdentifiers) ? 'strict' : 'fallback'),
        ];

        if (!empty($fallbackIdentifiers)) {
            $fallbackIds = array_map(
                function (string $fallbackIdentifier) {
                    $preset = $this->resolveLanguagePreset($fallbackIdentifier);

                    return $preset['id'];
                },
                $fallbackIdentifiers
            );
            $configuration['fallbackType'] = $fallbackType ?? 'fallback';
            $configuration['fallbacks'] = implode(',', $fallbackIds);
        }

        return $configuration;
    }

    protected static function resolveLanguagePreset(string $identifier)
    {
        if (!isset(static::LANGUAGE_PRESETS[$identifier])) {
            throw new \LogicException(
                sprintf('Undefined preset identifier "%s"', $identifier),
                1533893665
            );
        }

        return static::LANGUAGE_PRESETS[$identifier];
    }
}
