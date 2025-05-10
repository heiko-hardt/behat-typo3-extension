Last stable version: 9.5.31

Default: PHP 7.4 / MySQL 5.6

# System Requirements:
-------------------------------------------------------------------------------------------------

Operating System 	Linux, Microsoft Windows or macOS (this also includes hosting on all common cloud environments)
Webserver 	        Apache httpd, Nginx, Microsoft IIS, Caddy Server
Supported Browsers 	Chrome (latest)
                    Edge (latest)
                    Firefox (latest)
                    Internet Explorer >= 11
                    Safari (latest)
Database 	        MariaDB >= 10.0 <= 10.3
                    Microsoft SQL Server
                    MySQL >= 5.0 <= 5.7
                    PostgreSQL
                    SQLite
Hardware 	        RAM >= 256 MB
PHP 	            PHP >= 7.2 <= 7.4 

# Quality Assurance (PHP >= 7.2)
-------------------------------------------------------------------------------------------------

# https://github.com/TYPO3/testing-framework (TYPO3 v.9)
"typo3/testing-framework": "^4.15"

# https://github.com/TYPO3/coding-standards/blob/v0.6.1/composer.json (PHP 7.2)
"typo3/coding-standards": "^0.6"

# https://github.com/martin-helmich/typo3-typoscript-lint/blob/v2.5.2/composer.json (PHP 7.2)
"helmich/typo3-typoscript-lint": "^2.5"

# https://github.com/phpmd/phpmd/blob/2.15.0/composer.json (PHP 5.3)
"phpmd/phpmd": "^2.15"

# https://github.com/squizlabs/PHP_CodeSniffer/blob/3.7.2/composer.json (PHP 5.4)
"squizlabs/php_codesniffer": "^3.7"

Links:
- https://get.typo3.org/version/9#system-requirements
