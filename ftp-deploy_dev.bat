if exist D:\Prod\deploy_dev.log del D:\Prod\deploy_dev.log
SET SYMFONY_ENV=prod
php ftp-deployment.phar ftp-deploy_dev.ini
SET SYMFONY_ENV=
php composer.phar update
REM if exist D:\Prod\Temp del /Q D:\Prod\Temp\*.*
if exist var\cache rmdir var\cache /s /q
if exist var\log rmdir var\log /s /q
if exist var\sessions\prod rmdir var\sessions\prod /s /q
pause
