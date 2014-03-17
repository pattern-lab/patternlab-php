## About the PHP Version of Pattern Lab

The PHP version of Pattern Lab is, at its core, a static site generator. It combines platform-agnostic assets, like the [Mustache](http://mustache.github.io/)-based patterns and the JavaScript-based viewer, with a PHP-based "builder" that transforms and dynamically builds the Pattern Lab site. By making it a static site generator, Pattern Lab strongly separates patterns, data, and presentation from build logic. 

## Demo

You can play with a demo of the front-end of Pattern Lab at [demo.pattern-lab.info](http://demo.pattern-lab.info).

## Getting Started

* [Requirements](http://pattern-lab.info/docs/requirements.html)
* [Installing the PHP Version of Pattern Lab](http://pattern-lab.info/docs/installation.html)
* [Upgrading the PHP Version of Pattern Lab](http://pattern-lab.info/docs/upgrading.html)
* [Generating the Pattern Lab Website for the First Time](http://pattern-lab.info/docs/first-run.html)
* [Editing the Pattern Lab Website Source Files](http://pattern-lab.info/docs/editing-source-files.html)
* [Using the Command-line Options](http://pattern-lab.info/docs/command-line.html)
* [Command Prompt on Windows](http://pattern-lab.info/docs/command-prompt-windows.html)

## Working with Patterns

Patterns are the core element of Pattern Lab. Understanding how they work is the key to getting the most out of the system. Patterns use [Mustache](http://mustache.github.io/) so please read [Mustache's docs](http://mustache.github.io/mustache.5.html) as well.

* [How Patterns Are Organized](http://pattern-lab.info/docs/pattern-organization.html)
* [Adding New Patterns](http://pattern-lab.info/docs/pattern-add-new.html)
* [Reorganizing Patterns](http://pattern-lab.info/docs/pattern-reorganizing.html)
* [Including One Pattern Within Another via Partials](http://pattern-lab.info/docs/pattern-including.html)
* [Managing Assets for a Pattern: JavaScript, images, CSS, etc.](http://pattern-lab.info/docs/pattern-managing-assets.html)
* [Modifying the Pattern Header and Footer](http://pattern-lab.info/docs/pattern-header-footer.html)
* [Using Pseudo-Patterns](http://pattern-lab.info/docs/pattern-pseudo-patterns.html)
* [Using Pattern Parameters](http://pattern-lab.info/docs/pattern-parameters.html)
* [Using Pattern State](http://pattern-lab.info/docs/pattern-states.html)
* ["Hiding" Patterns in the Navigation](http://pattern-lab.info/docs/pattern-hiding.html)
* [Adding Annotations](http://pattern-lab.info/docs/pattern-adding-annotations.html)
* [Viewing Patterns on a Mobile Device](http://pattern-lab.info/docs/pattern-mobile-view.html)

## Creating & Working With Dynamic Data for a Pattern

The PHP version of Pattern Lab utilizes Mustache as the template language for patterns. In addition to allowing for the [inclusion of one pattern within another](https://github.com/pattern-lab/patternlab-php/wiki/Including-One-Pattern-Within-Another) it also gives pattern developers the ability to include variables. This means that attributes like image sources can be centralized in one file for easy modification across one or more patterns. The PHP version of Pattern Lab uses a JSON file, `source/_data/data.json`, to centralize many of these attributes.

* [Introduction to JSON & Mustache Variables](http://pattern-lab.info/docs/data-json-mustache.html)
* [Overriding the Central `data.json` Values with Pattern-specific Values](http://pattern-lab.info/docs/data-pattern-specific.html)
* [Linking to Patterns with Pattern Lab's Default `link` Variable](http://pattern-lab.info/docs/data-link-variable.html)
* [Creating Lists with Pattern Lab's Default `listItems` Variable](http://pattern-lab.info/docs/data-listitems.html)

## Using Pattern Lab's Advanced Features

By default, the Pattern Lab assets can be manually generated and the Pattern Lab site manually refreshed but who wants to waste time doing that? Here are some ways that Pattern Lab can make your development workflow a little smoother:

* [Watching for Changes and Auto-Regenerating Patterns](http://pattern-lab.info/docs/advanced-auto-regenerate.html)
* [Auto-Reloading the Browser Window When Changes Are Made](http://pattern-lab.info/docs/advanced-reload-browser.html)
* [Multi-browser & Multi-device Testing with Page Follow](http://pattern-lab.info/docs/advanced-page-follow.html)
* [Keyboard Shortcuts](http://pattern-lab.info/docs/advanced-keyboard-shortcuts.html)
* [Special Pattern Lab-specific Query String Variables ](http://pattern-lab.info/docs/pattern-linking.html)
* [Preventing the Cleaning of public/](http://pattern-lab.info/docs/advanced-clean-public.html)
* [Generating CSS](http://pattern-lab.info/docs/advanced-generating-css.html)
* [Modifying the Pattern Lab Nav](http://pattern-lab.info/docs/advanced-pattern-lab-nav.html)
* [Integration with Compass](http://pattern-lab.info/docs/advanced-integration-with-compass.html)
