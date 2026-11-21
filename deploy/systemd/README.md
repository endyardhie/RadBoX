# Systemd Notes

RadBoX MVP does not require a custom systemd service. Run it through Nginx + PHP-FPM.

Useful commands:

```bash
sudo systemctl status nginx php8.3-fpm mariadb freeradius
sudo systemctl reload nginx
sudo systemctl restart freeradius
```
