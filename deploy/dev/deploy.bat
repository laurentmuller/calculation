if exist D:\Prod\deploy_dev.log del D:\Prod\deploy_dev.log

copy env.ini ..\..\
copy deploy.ini ..\..\
copy ..\deployment.phar ..\..\
cd ..\..

php deployment.phar deploy.ini

if exist env.ini del env.ini /q
if exist deploy.ini del deploy.ini /q
if exist deployment.phar del deployment.phar /q
if exist var\cache rmdir var\cache /s /q
if exist var\log rmdir var\log /s /q
if exist var\sessions\prod rmdir var\sessions\prod /s /q

cd deploy/dev
pause
