Last stable version: 11.5.41

Default: PHP 8.2 / MariaDB 10.4

# System Requirements:
-------------------------------------------------------------------------------------------------

Operating System 	Linux, Microsoft Windows or macOS (this also includes hosting on all common cloud environments)
Webserver 	        Apache httpd, Nginx, Microsoft IIS, Caddy Server
Supported Browsers 	Chrome (latest)
                    Edge (latest)
                    Firefox (latest)
                    Safari (latest)
Composer 	        Composer >= 2.1
Database 	        MariaDB >= 10.3 <= 11.0
                    MySQL >= 8.0.17
                    PostgreSQL >= 10.0
                    SQLite >= 3.8.3
Hardware 	        RAM >= 256 MB
PHP 	            PHP >= 8.1.0 <= 8.4.99 

# Quality Assurance (PHP >= 7.4)
-------------------------------------------------------------------------------------------------

# https://github.com/TYPO3/testing-framework (TYPO3 v.11)
"typo3/testing-framework": "^7.0"

# https://github.com/TYPO3/coding-standards/blob/v0.6.1/composer.json (PHP 7.2)
"typo3/coding-standards": "^0.6"

# https://github.com/martin-helmich/typo3-typoscript-lint/blob/v3.1.1/composer.json (PHP 7.4)
"helmich/typo3-typoscript-lint": "^3.1"

# https://github.com/phpmd/phpmd/blob/2.15.0/composer.json (PHP 5.3)
"phpmd/phpmd": "^2.15"

# https://github.com/squizlabs/PHP_CodeSniffer/blob/3.7.2/composer.json (PHP 5.4)
"squizlabs/php_codesniffer": "^3.7"

Links:
- https://get.typo3.org/version/11#system-requirements
