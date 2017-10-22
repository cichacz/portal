# Simple portal app

### vhost:
```
<VirtualHost *:80>
    DocumentRoot "F:/Projects/portal/public"
    ServerName portal.dev
    ErrorLog "logs/portal-error.log"
    CustomLog "logs/portal-access.log" common
</VirtualHost>
```