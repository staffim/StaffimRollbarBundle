# RollbarBundle

[![Build Status](https://travis-ci.org/staffim/StaffimRollbarBundle.svg?branch=master)](https://travis-ci.org/staffim/StaffimRollbarBundle) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/ff4df077-5079-4b33-8758-189b251fb3d5/mini.png)](https://insight.sensiolabs.com/projects/ff4df077-5079-4b33-8758-189b251fb3d5)

## About

Integrates [Rollbar](http://rollbar.com/) with your Symfony2 application.

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
