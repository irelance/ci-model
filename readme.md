#1. Summary
This project is an ORM extension of CodeIgniter3 framework

#2. Install
Change config in application/config/config.php to enable composer autoload
```php
$config['composer_autoload'] = dirname(APPPATH).DIRECTORY_SEPARATOR.'vendor/autoload.php';
```

#3. Features
- Base on object
- Relations support
- arrayable & jsonable