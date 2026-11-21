# Ubuntu/Debian Install Notes

```bash
sudo apt update
sudo apt install nginx mariadb-server freeradius freeradius-mysql php-fpm php-cli php-mysql php-mbstring php-curl php-xml php-zip unzip gzip ufw
```

Use PHP 8.2 or newer. Keep MariaDB bound to `127.0.0.1` and do not expose port `3306`.
