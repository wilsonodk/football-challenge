# Football Challenge

A web app to run a NCAA football pickem fantasy game.

## Database Connection

Create two files, `db-dev.php` and `db-prod.php`, to setup the database.

```php
<?php
// Database connection
define('DB_HOST', ''); // database hostname, oftentimes localhost
define('DB_PORT', ''); // database port, oftentimes NULL
define('DB_NAME', ''); // database name
define('DB_USER', ''); // database username
define('DB_PASS', ''); // database password
define('DB_SOCK', ''); // database socket, oftentimes NULL
define('DB_PFIX', 'fc_'); // database table prefix, default fc_
?>
```
