if exist D:\Prod\deploy_dev.log del D:\Prod\deploy_dev.log
php ftp-deployment.phar ftp-deploy_dev.ini
if exist var\cache rmdir var\cache /s /q
if exist var\log rmdir var\log /s /q
if exist var\sessions\prod rmdir var\sessions\prod /s /q
pause
