Site Manager
===================================

Web service for managing sites with monitoring functionality.

## Features

- Site management and monitoring
- Server management
- Template system
- User access control
- Availability checks
- Queue-based processing

## Installation

1. Clone repository.

2. Run ```composer install``` or ```composer update```.

3. Configure environment dev or prod:
  ```
  php /path/to/yii-application/init
  ```

4. Configure the database connection in the config:
  ```
  /common/config/main-local.php
  ```

5. Run migration:
  ```
  php yii migrate
  ```

## Default user

Login: **demoadmin**

Password: **demoadmin**
