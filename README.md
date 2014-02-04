# RollbarBundle

## About

Intergrates [Rollbar](http://rollbar.com/) with your Symfony2 application.

The bundle is inspired from [ratchetio-bundle](https://github.com/ecoleman/ratchetio-bundle)

## Installation

Require the `staffim/rollbar-bundle` package in your composer.json and update your dependencies.

    $ composer require staffim/rollbar-bundle:*

Add the StaffimRollbarBundle to your application's kernel:

```php
    public function registerBundles()
    {
        $bundles = array(
            ...
            new Staffim\RollbarBundle\StaffimRollbarBundle(),
            ...
        );
        ...
    }
```

## Configuration

```yml
staffim_rollbar:
    # Rollbar access token
    access_token: ###
    # PHP error level (see http://php.net/manual/en/function.error-reporting.php)
    error_level: -1
    # Values from scrub.parameters are replaced by the key for specified exceptions
    scrub:
        exceptions:
            - PDOException
        parameters:
            key: %key%
```
