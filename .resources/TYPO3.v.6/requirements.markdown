Last stable version: 6.2.31

Default: PHP 5.6 / MySQL 5.6

# System Requirements:
-------------------------------------------------------------------------------------------------

TYPO3 requires a web server, PHP and a database system.

    TYPO3 requires a web server which can run PHP (e.g. Apache or IIS).

    TYPO3 6.2 requires PHP 5.3.7 - 5.6.x

    TYPO3 can be used with a great many database systems. If you use MySQL, you will need to implement at least MySQL 5.1.

*TYPO3 requires composer v.1.x*

# Quality Assurance (PHP >= 5.6)
-------------------------------------------------------------------------------------------------

// # https://github.com/TYPO3/testing-framework
// "typo3/testing-framework": "^X.X"

// # https://github.com/TYPO3/coding-standards
// "typo3/coding-standards": "^X.X"

# https://github.com/martin-helmich/typo3-typoscript-lint/blob/v2.5.2/composer.json (PHP 5.5)
"helmich/typo3-typoscript-lint": "^1.5"

# https://github.com/phpmd/phpmd/blob/2.15.0/composer.json (PHP 5.3)
"phpmd/phpmd": "^2.15"

# https://github.com/squizlabs/PHP_CodeSniffer/blob/3.7.2/composer.json (PHP 5.4)
"squizlabs/php_codesniffer": "^3.7"

Links:
- https://docs.typo3.org/m/typo3/guide-installation/6.2/en-us/In-depth/SystemRequirements/Index.html
