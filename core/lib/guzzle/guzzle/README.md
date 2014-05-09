Guzzle, PHP HTTP client and webservice framework
================================================

[![Composer Downloads](https://poser.pugx.org/guzzle/guzzle/d/total.png)](https://packagist.org/packages/guzzle/guzzle)
 [![Build Status](https://secure.travis-ci.org/guzzle/guzzle3.png?branch=master)](http://travis-ci.org/guzzle/guzzle3)

Guzzle is a PHP HTTP client and framework for building RESTful web service clients.

- Extremely powerful API provides all the power of cURL with a simple interface.
- Truly take advantage of HTTP/1.1 with persistent connections, connection pooling, and parallel requests.
- Service description DSL allows you build awesome web service clients faster.
- Symfony2 event-based plugin system allows you to completely modify the behavior of a request.

Get answers with: [Documentation](http://guzzle3.readthedocs.org/en/latest/), [Forums](https://groups.google.com/forum/?hl=en#!forum/guzzle), IRC ([#guzzlephp](irc://irc.freenode.net/#guzzlephp) @ irc.freenode.net)

### Installing via Composer

The recommended way to install Guzzle is through [Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php

# Add Guzzle as a dependency
php composer.phar require guzzle/guzzle:~3.9
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

# This is an older version

This repository is for Guzzle 3.x. Guzzle 4.0, the new version of Guzzle has been released and is available at https://github.com/guzzle/guzzle.
