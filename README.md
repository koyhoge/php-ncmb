# PHP SDK for 'NIFTY Cloud mobile backend'

php-ncmb: Unofficial PHP SDK for [NIFTY Cloud mobile backend (NCMB)](http://mb.cloud.nifty.com/).

## Install

Add line into 'require' section of your 'composer.json':

```json
     "koyhoge/ncmb-php": "*"
```

then hit command:

```
  $ composer install
```


## Initialize

```php
    use Ncmb\NCMB;

    $appkey = YOUR_APPLICATION_KEY;
    $clientkey = YOUR_CLIENT_KEY;

    NCMB::initialize($appkey, $clientkey);
```

## Store data

```php
    use Ncmb\Object;
    use NCMB\GeoPoint;

    $className = 'FooBar';
    $foobar = new Object($className);
    $foobar->stringKey = 'This is String';
    $foobar->intKey = 1;
    $foobar->dateKey = new DateTime();
    $foobar->geoKey = new GeoPoint(43.223, 133.392);
    $foobar->save();
```

## Find data

```php
    use \Ncmb\Query;

    $className = 'FooBar';
    $query = new Query($className);
    $query->equalTo('intKey', 1);
    $foundObj = $query->first();
```

## File

```php
    use \Ncmb\File;

    // put file
    $file = File::createFromData('hogehoge', 'hoge.txt');
    $file->save();

    // get file
    $file = File::createFromServer('hoge.txt');
    $data = $file->getData();
```

## Script

```php
    use \Ncmb\Script;

    $result = Script::execute('hello.js', ['query' => ['name' => 'NCMB']]);
```

## LICENSE

MIT LICENSE. see also LICENSE file.
