<?php

declare(strict_types=1);

namespace HeikoHardt\Behat\TYPO3Extension\Tests\Unit\Helper;

use HeikoHardt\Behat\TYPO3Extension\Helper\Env;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Env::class)]
class EnvTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        // Backup original environment
        $this->originalEnv = [
            'TEST_VAR' => getenv('TEST_VAR'),
            'BASE_URL' => getenv('BASE_URL'),
            'DB_HOST' => getenv('DB_HOST'),
            'DB_USER' => getenv('DB_USER'),
            'DB_PASS' => getenv('DB_PASS'),
            'EMPTY_VAR' => getenv('EMPTY_VAR'),
        ];
    }

    protected function tearDown(): void
    {
        // Restore original environment
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function unsetEnv(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }

    // ==================== Basic Resolution Tests ====================

    public function testResolveSimpleEnvironmentVariable(): void
    {
        $this->setEnv('TEST_VAR', 'test_value');

        $result = Env::resolve('%env(TEST_VAR)%');

        $this->assertSame('test_value', $result);
    }

    public function testResolveMultipleEnvironmentVariables(): void
    {
        $this->setEnv('DB_USER', 'admin');
        $this->setEnv('DB_HOST', 'localhost');

        $result = Env::resolve(
            'User: %env(DB_USER)% on %env(DB_HOST)%'
        );

        $this->assertSame('User: admin on localhost', $result);
    }

    public function testResolveWithMixedCase(): void
    {
        $this->setEnv('MyVar', 'value');

        $result = Env::resolve('%env(MyVar)%');

        $this->assertSame('value', $result);
    }

    public function testResolveReturnsOriginalWhenVariableNotSet(): void
    {
        $this->unsetEnv('NONEXISTENT');

        $result = Env::resolve('%env(NONEXISTENT)%');

        $this->assertSame('%env(NONEXISTENT)%', $result);
    }

    public function testResolveFromDifferentSources(): void
    {
        // Test $_ENV
        unset($_SERVER['TEST_VAR']);
        putenv('TEST_VAR');
        $_ENV['TEST_VAR'] = 'from_env_array';

        $result = Env::resolve('%env(TEST_VAR)%');
        $this->assertSame('from_env_array', $result);

        // Test $_SERVER
        unset($_ENV['TEST_VAR']);
        $_SERVER['TEST_VAR'] = 'from_server_array';

        $result = Env::resolve('%env(TEST_VAR)%');
        $this->assertSame('from_server_array', $result);
    }

    // ==================== Default Value Tests ====================

    public function testResolveWithDefaultWhenVariableNotSet(): void
    {
        $this->unsetEnv('MISSING_VAR');

        $result = Env::resolve('%env(MISSING_VAR):-default_value%');

        $this->assertSame('default_value', $result);
    }

    public function testResolveIgnoresDefaultWhenVariableIsSet(): void
    {
        $this->setEnv('EXISTING_VAR', 'actual_value');

        $result = Env::resolve('%env(EXISTING_VAR):-default_value%');

        $this->assertSame('actual_value', $result);
    }

    public function testResolveWithUrlAsDefault(): void
    {
        $this->unsetEnv('API_URL');

        $result = Env::resolve('%env(API_URL):-https://api.example.com%');

        $this->assertSame('https://api.example.com', $result);
    }

    public function testResolveWithNumericDefault(): void
    {
        $this->unsetEnv('PORT');

        $result = Env::resolve('%env(PORT):-8080%');

        $this->assertSame('8080', $result);
    }

    public function testResolveWithEmptyStringDefault(): void
    {
        $this->unsetEnv('OPTIONAL_VAR');

        $result = Env::resolve('%env(OPTIONAL_VAR):-%');

        $this->assertSame('', $result);
    }

    public function testResolveMultipleVariablesWithDefaults(): void
    {
        $this->unsetEnv('DB_USER');
        $this->unsetEnv('DB_PASS');
        $this->setEnv('DB_HOST', 'prod.db.com');

        $result = Env::resolve(
            '%env(DB_USER):-root%:%env(DB_PASS):-secret%@%env(DB_HOST):-localhost%'
        );

        $this->assertSame('root:secret@prod.db.com', $result);
    }

    // ==================== Suffix Tests ====================

    public function testResolveWithSuffix(): void
    {
        $this->setEnv('BASE_URL', 'https://example.com');

        $result = Env::resolve('%env(BASE_URL)%/api/v1');

        $this->assertSame('https://example.com/api/v1', $result);
    }

    public function testResolveWithSuffixRemovesTrailingSlash(): void
    {
        $this->setEnv('BASE_URL', 'https://example.com/');

        $result = Env::resolve('%env(BASE_URL)%/api/v1');

        $this->assertSame('https://example.com/api/v1', $result);
    }

    public function testResolveWithMultipleSuffixSlashes(): void
    {
        $this->setEnv('BASE_URL', 'https://example.com');

        $result = Env::resolve('%env(BASE_URL)%/api/v1/users/123');

        $this->assertSame('https://example.com/api/v1/users/123', $result);
    }

    public function testResolveWithoutSuffixKeepsTrailingSlash(): void
    {
        $this->setEnv('BASE_URL', 'https://example.com/');

        $result = Env::resolve('%env(BASE_URL)%');

        $this->assertSame('https://example.com/', $result);
    }

    // ==================== Combined Default + Suffix Tests ====================

    public function testResolveWithDefaultAndSuffix(): void
    {
        $this->unsetEnv('API_URL');

        $result = Env::resolve('%env(API_URL):-http://localhost:8080%/api/users');

        $this->assertSame('http://localhost:8080/api/users', $result);
    }

    public function testResolveWithDefaultAndSuffixWhenVariableSet(): void
    {
        $this->setEnv('API_URL', 'https://prod.example.com');

        $result = Env::resolve('%env(API_URL):-http://localhost%/api/users');

        $this->assertSame('https://prod.example.com/api/users', $result);
    }

    public function testResolveComplexDsnWithDefaultsAndSuffixes(): void
    {
        $this->unsetEnv('DB_USER');
        $this->setEnv('DB_HOST', 'db.prod.com');
        $this->unsetEnv('DB_NAME');

        $result = Env::resolve(
            'mysql://%env(DB_USER):-root%@%env(DB_HOST):-localhost%/%env(DB_NAME):-myapp%'
        );

        $this->assertSame('mysql://root@db.prod.com/myapp', $result);
    }

    // ==================== Exception Tests ====================

    public function testResolveThrowsExceptionWhenVariableNotSetAndThrowOnMissingTrue(): void
    {
        $this->unsetEnv('REQUIRED_VAR');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable "REQUIRED_VAR" is not set');

        Env::resolve('%env(REQUIRED_VAR)%', throwOnMissing: true);
    }

    public function testResolveDoesNotThrowWhenDefaultProvided(): void
    {
        $this->unsetEnv('OPTIONAL_VAR');

        $result = Env::resolve(
            '%env(OPTIONAL_VAR):-default%',
            throwOnMissing: true
        );

        $this->assertSame('default', $result);
    }

    public function testResolveDoesNotThrowWhenVariableIsSet(): void
    {
        $this->setEnv('EXISTING_VAR', 'value');

        $result = Env::resolve(
            '%env(EXISTING_VAR)%',
            throwOnMissing: true
        );

        $this->assertSame('value', $result);
    }

    // ==================== Edge Cases ====================

    public function testResolveEmptyString(): void
    {
        $result = Env::resolve('');

        $this->assertSame('', $result);
    }

    public function testResolveStringWithoutPlaceholders(): void
    {
        $result = Env::resolve('No placeholders here');

        $this->assertSame('No placeholders here', $result);
    }

    public function testResolveWithEmptyVariableValue(): void
    {
        $this->setEnv('EMPTY_VAR', '');

        $result = Env::resolve('%env(EMPTY_VAR):-fallback%');

        // Empty string should trigger default
        $this->assertSame('fallback', $result);
    }

    public function testResolveWithSpecialCharactersInDefault(): void
    {
        $this->unsetEnv('SPECIAL');

        $result = Env::resolve('%env(SPECIAL):-user@host:port%');

        $this->assertSame('user@host:port', $result);
    }

    public function testResolveWithWhitespaceInString(): void
    {
        $this->setEnv('VAR1', 'value1');
        $this->setEnv('VAR2', 'value2');

        $result = Env::resolve('prefix %env(VAR1)% middle %env(VAR2)% suffix');

        $this->assertSame('prefix value1 middle value2 suffix', $result);
    }

    // ==================== Real-World Scenario Tests ====================

    public function testTypo3BaseUrlScenario(): void
    {
        $this->setEnv('TYPO3_BASE_PROTOCOL', 'https');
        $this->setEnv('TYPO3_BASE_DOMAIN', 'example.com');

        $result = Env::resolve(
            '%env(TYPO3_BASE_PROTOCOL)%://%env(TYPO3_BASE_DOMAIN)%/'
        );

        $this->assertSame('https://example.com/', $result);
    }

    public function testBehatApiTestingScenario(): void
    {
        $this->setEnv('API_BASE_URL', 'https://api.staging.com');

        $result = Env::resolve('%env(API_BASE_URL)%/users/123/profile');

        $this->assertSame('https://api.staging.com/users/123/profile', $result);
    }

    public function testLocalDevelopmentWithDefaults(): void
    {
        $this->unsetEnv('APP_URL');
        $this->unsetEnv('API_PORT');

        $result = Env::resolve(
            '%env(APP_URL):-http://localhost%:%env(API_PORT):-3000%/api'
        );

        $this->assertSame('http://localhost:3000/api', $result);
    }

    #[DataProvider('provideComplexScenarios')]
    public function testComplexScenarios(array $env, string $input, string $expected): void
    {
        foreach ($env as $key => $value) {
            if ($value === null) {
                $this->unsetEnv($key);
            } else {
                $this->setEnv($key, $value);
            }
        }

        $result = Env::resolve($input);

        $this->assertSame($expected, $result);
    }

    public static function provideComplexScenarios(): array
    {
        return [
            'database DSN with all defaults' => [
                'env' => [
                    'DB_DRIVER' => null,
                    'DB_USER' => null,
                    'DB_PASS' => null,
                    'DB_HOST' => null,
                    'DB_PORT' => null,
                    'DB_NAME' => null,
                ],
                'input' => '%env(DB_DRIVER):-mysql%://%env(DB_USER):-root%:%env(DB_PASS):-password%@%env(DB_HOST):-localhost%:%env(DB_PORT):-3306%/%env(DB_NAME):-app%',
                'expected' => 'mysql://root:password@localhost:3306/app',
            ],
            'database DSN mixed' => [
                'env' => [
                    'DB_DRIVER' => 'postgresql',
                    'DB_USER' => 'admin',
                    'DB_PASS' => null,
                    'DB_HOST' => 'prod.db.com',
                    'DB_PORT' => null,
                    'DB_NAME' => 'production',
                ],
                'input' => '%env(DB_DRIVER):-mysql%://%env(DB_USER):-root%:%env(DB_PASS):-password%@%env(DB_HOST):-localhost%:%env(DB_PORT):-3306%/%env(DB_NAME):-app%',
                'expected' => 'postgresql://admin:password@prod.db.com:3306/production',
            ],
            'API endpoints with base URL' => [
                'env' => [
                    'API_BASE' => 'https://api.example.com',
                ],
                'input' => '%env(API_BASE)%/users, %env(API_BASE)%/posts, %env(API_BASE)%/comments',
                'expected' => 'https://api.example.com/users, https://api.example.com/posts, https://api.example.com/comments',
            ],
            'multi-environment config' => [
                'env' => [
                    'ENV' => 'production',
                    'DEBUG' => null,
                    'LOG_LEVEL' => null,
                ],
                'input' => 'env=%env(ENV)% debug=%env(DEBUG):-false% log=%env(LOG_LEVEL):-info%',
                'expected' => 'env=production debug=false log=info',
            ],
        ];
    }
}
