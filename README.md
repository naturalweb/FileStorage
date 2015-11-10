FileStorage
===========

### Requirements:
  * PHP 5.3+

### Installation

- [API on Packagist](https://packagist.org/packages/naturalweb/filestorage)
- [API on GitHub](https://github.com/naturalweb/FileStorage)

In the `require` key of `composer.json` file add the following

    "naturalweb/filestorage": "0.1"

Run the Composer update comand

    $ composer update

### Usage
```php
$filestorage->save($name, $source, $folder, $override = false);
```