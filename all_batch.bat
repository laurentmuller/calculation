@ECHO OFF
ECHO -------------------------------------- START BATCH %time% ---------------------------      && ^
ECHO -------------------------------------- PHP-CS-FIXER -------------------------------------- && ^
.\vendor-bin\php-cs-fixer\vendor\bin\php-cs-fixer.bat fix --diff --quiet                        && ^
ECHO -------------------------------------- PHP-PSALM ----------------------------------------- && ^
.\vendor-bin\psalm\vendor\bin\psalm.bat --no-progress src                                       && ^
ECHO -------------------------------------- PHP-STAN ------------------------------------------ && ^
.\vendor-bin\phpstan\vendor\bin\phpstan.bat analyse --no-progress --memory-limit=2G             && ^
ECHO -------------------------------------- PHP-RECTOR ---------------------------------------- && ^
.\vendor-bin\rector\vendor\bin\rector.bat process --dry-run                                     && ^
ECHO -------------------------------------- PHP-TWIG-CS --------------------------------------- && ^
.\vendor-bin\twigcs\vendor\bin\twigcs.bat --severity error --display blocking templates         && ^
ECHO -------------------------------------- PHP-UNIT ------------------------------------------ && ^
.\vendor-bin\phpunit\vendor\bin\simple-phpunit.bat                                              && ^
ECHO -------------------------------------- END BATCH %time% -----------------------------
