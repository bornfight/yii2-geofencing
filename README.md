Yii2 GeoFencing
======================
An action filter that lets you allow or deny actions based on the visitors location.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist degordian/yii2-geofencing "~1.0"
```

or add

```
"degordian/yii2-geofencing": "~1.0"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, add it to your controller

```php
  public function behaviors() {
    return [
      'class' => GeoIpAccessControl::class,
      'isoCodes' => ['HR', 'SI', 'RS'], //ISO 3166-1 alpha-2 two letter country code
      'filterMode' => GeoIpFilterMode::ALLOW, //allows only if you are listed in isoCodes,
      #'filterMode' => GeoIpFilterMode::DENY //allows only if you are not listed in isoCodes
      'getIp' => function() {
        //you can provide a custom function used to get the clients IP
        //defaults to Yii::$app->request->getUserIP()
      },
      'getIsoCode' => function($ip) {
        //you can provide a custom function used to get the iso code from the clients IP
        //by default uses lysenkobv/yii2-geoip
      },
      'message' => 'The message to display if the content is denied'
    ]
  }
```
Since this extends ActionFilter, you can use 'only' and 'except' to fine tune the geofencing criteria
