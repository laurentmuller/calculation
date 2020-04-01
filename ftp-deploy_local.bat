if exist D:\Prod\deploy_local.log del D:\Prod\deploy_local.log
php ftp-deployment.phar ftp-deploy_local.ini
if exist var\cache rmdir var\cache /s /q
if exist var\log rmdir var\log /s /q
if exist var\sessions\prod rmdir var\sessions\prod /s /q
pause
