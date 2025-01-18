@ECHO OFF
SET STA__TIME=%TIME: =0%
ECHO ----- START BATCH %STA__TIME% ----------------------------------------------------- && ^
ECHO ----- SYMFONY --------------------------------------------------------------------- && ^
php bin/console lint:yaml translations config                                            && ^
php bin/console lint:twig templates                                                      && ^
php bin/console lint:xliff translations                                                  && ^
php bin/console lint:container                                                           && ^
php bin/console doctrine:schema:validate --skip-sync --no-interaction                    && ^
composer validate --strict                                                               && ^
ECHO ----- MARKDOWN -------------------------------------------------------------------- && ^
markdownlint-cli2                                                                        && ^
ECHO ----- PHP-CS-FIXER ---------------------------------------------------------------- && ^
.\vendor-bin\php-cs-fixer\vendor\bin\php-cs-fixer.bat fix --diff --dry-run               && ^
ECHO ----- PHP-PSALM ------------------------------------------------------------------- && ^
.\vendor-bin\psalm\vendor\bin\psalm.bat --config=psalm.xml                               && ^
ECHO ----- PHP-STAN -------------------------------------------------------------------- && ^
.\vendor-bin\phpstan\vendor\bin\phpstan.bat analyse --memory-limit=2G                    && ^
ECHO ----- PHP-RECTOR ------------------------------------------------------------------ && ^
.\vendor-bin\rector\vendor\bin\rector.bat --dry-run --config=rector.php                  && ^
ECHO ----- PHP-TWIG-CS-FIXER ----------------------------------------------------------- && ^
.\vendor-bin\twig-cs-fixer\vendor\bin\twig-cs-fixer.bat lint --config=.twig-cs-fixer.php && ^
ECHO ----- PHP-UNIT -------------------------------------------------------------------- && ^
.\vendor\bin\phpunit.bat
SET STA__TIME=%TIME: =0%
ECHO ----- END BATCH %STA__TIME% -------------------------------------------------------
cls
