# Knyle Style Sheets

This is a PHP implementation of [Knyle Style Sheets](http://warpspire.com/kss) (KSS).
KSS attempts to provide a methodology for writing maintainable, documented CSS
within a team. Specifically, KSS is a documentation specification and styleguide
format. It is **not** a preprocessor, CSS framework, naming convention, or
specificity guideline.

* **[The Spec (What KSS is)](https://github.com/kneath/kss/blob/master/SPEC.md)**
* **[Example living styleguide](https://github.com/scaninc/kss-php/tree/master/example)**

## KSS in a nutshell

The methodology and ideas behind Knyle Style Sheets are contained in [SPEC.md](https://github.com/kneath/kss/blob/master/SPEC.md)
of the origin [ruby version](https://github.com/kneath/kss) of KSS. At its core,
KSS is a documenting syntax for CSS.

```css
/*
# Star Button

A button suitable for giving stars to someone.

Markup: <a class="button star $modifierClass">Button</a>

:hover              - Subtle hover highlight.
.stars--given       - A highlight indicating you've already given a star.
.stars--given:hover - Subtle hover highlight on top of stars-given styling.
.stars--disabled    - Dims the button to indicate it cannot be used.

Styleguide 2.1.3.
*/
a.button.star {
  ...
}
a.button.star:hover {
  ...
}
a.button.stars--given {
  ...
}
a.button.stars--given:hover {
  ...
}
a.button.stars--disabled {
  ...
}
```

## PHP Library

This repository includes a php library suitable for parsing SASS, SCSS, and CSS
documented with KSS guidelines. To use the library, include it in your project as
a composer dependency (see below). Then, create a parser and explore your KSS.

```php
<?php

require_once('../vendors/autoload.php');
$styleguide = new \Scan\Kss\Parser('public/stylesheets')

$section = $styleguide->getSection('2.1.1');
// Returns a \Scan\Kss\Section object

echo $section->getTitle();
// Echoes "Star Button"

echo $section->getDescription();
// echoes "A button suitable for giving stars to someone."

echo $section->getMarkup();
// echoes "<a class="button star $modifierClass">Button</a>"

$modifier = current($section->getModifiers());
// Returns a \Scan\Kss\Modifier object

echo $modifier->getName();
// echoes ':hover'

echo $modifier->getClassName();
// echoes 'psuedo-class-hover'

echo $modifier->getDescription();
// echoes 'Subtle hover highlight'

echo $modifier->getExampleHtml();
// echoes <a class="button stars stars-given">Button</a> for the .stars-given modifier
```

## Generating styleguides

The documenting syntax and php library are intended to generate styleguides automatically.
To do this, you'll need to leverage a small javascript library that generates
class styles for pseudo-class styles (`:hover`, `:disabled`, etc).

* [kss.coffee](https://github.com/scaninc/kss-php/blob/master/lib/Scan/kss.coffee)
* [kss.js](https://github.com/scaninc/kss-php/blob/master/example/public/js/kss.js) (compiled js)

For an example of how to generate a styleguide, check out the [`example`](https://github.com/scaninc/kss-php/tree/master/example)
php pages.

## Dependencies

The PHP version of KSS has dependencies managed by Composer. If you did not install
kss-php using composer, you must install these dependencies manually before using
the library by running the following commands:

```
$ composer install
```

If you do not yet have Composer, download it following the instructions on
http://getcomposer.org or run the following commands to install it globally on
your system:

```
$ curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

## Symfony2 Bundle

If your project uses [symfony2](http://symfony.com/), consider using the [KSS Bundle]
(https://github.com/scaninc/ScanKssBundle) as well. The KSS Bundle uses Twig templates
to make the styleguide block easier to customize and include in your views.
