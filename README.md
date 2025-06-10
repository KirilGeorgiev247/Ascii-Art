# Backend Ascii-Art

## Setup for macOs

To run your app you need to follow these steps:

```
npm install
```


```
composer install & composer run start
```

## Preresquisites

+ installed npmAdd commentMore actions
+ needed extensions enabled php.ini

## Setup for windows

```
winget install --id PHP.PHP -e
```

+ in php/php-ini-development uncomment openssl

+ add installer file of composer in the proj

```
php windows_setup/composer-setup.php
```

```
php composer.phar install
```

```
php composer.phar run start
```

+ uncomment extension=pdo_mysql in php.ini
+ uncomment extension=mysqli in php.ini

```
mysql -h 127.0.0.1 -P 3306 -u root -p
```

```
CREATE DATABASE ascii-art-db
```

```
php composer.phar require cboden/ratchet
```