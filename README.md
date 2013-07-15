## About the PHP Version of Pattern Lab

The PHP version of Pattern Lab is, at its core, a static site generator. It combines platform-agnostic assets, like the [Mustache](http://mustache.github.io/)-based patterns and the JavaScript-based viewer, with a PHP-based "builder" that transforms and dynamically builds the Pattern Lab site. By making it a static site generator, the PHP version of Pattern Lab strongly separates patterns, data, and presentation from build logic. The PHP version should be seen as a reference for other developers to improve upon as they build their own Pattern Lab Builders in their language of choice.

## Getting Started

The PHP version of Pattern Lab should be relatively easy for anyone to get up and running. 

* [Requirements](https://github.com/pattern-lab/patternlab-php/wiki/Requirements)
* [Installing the PHP Version of Pattern Lab](https://github.com/pattern-lab/patternlab-php/wiki/Installing-the-PHP-Version-of-Pattern-Lab)
* [Generating the Pattern Lab Website for the First Time](https://github.com/pattern-lab/patternlab-php/wiki/Generating-the-Pattern-Lab-Website-for-the-First-Time)
* Setting Up Apache to use the Advanced Features
* [Using the Command-line Options](https://github.com/pattern-lab/patternlab-php/wiki/Using-the-Command-line-Options)

## Working with Patterns

Patterns are the core element of Pattern Lab. Understanding how they work is the key to getting the most out of the system. Patterns use [Mustache](http://mustache.github.io/) so please read [Mustache's docs](http://mustache.github.io/mustache.5.html) as well.

* [How Patterns Are Organized](https://github.com/pattern-lab/patternlab-php/wiki/How-Patterns-Are-Organized)
* Adding New Patterns
* [Reorganizing Patterns](https://github.com/pattern-lab/patternlab-php/wiki/Reorganizing-Patterns)
* ["Hiding" Patterns in the Navigation](https://github.com/pattern-lab/patternlab-php/wiki/Hiding-Patterns-in-the-Navigation)
* [Including One Pattern Within Another](https://github.com/pattern-lab/patternlab-php/wiki/Including-One-Pattern-Within-Another)
* [Linking Directly to a Pattern](https://github.com/pattern-lab/patternlab-php/wiki/Linking-Directly-to-a-Pattern)
* [Managing the Images and JavaScript for a Pattern](https://github.com/pattern-lab/patternlab-php/wiki/Managing-the-Images-and-JavaScript-for-a-Pattern)
* [Modifying the Styles for a Pattern](https://github.com/pattern-lab/patternlab-php/wiki/Modifying-the-Styles-for-a-Pattern)
* [Modifying the Standard Header & Footer for Patterns](https://github.com/pattern-lab/patternlab-php/wiki/Modifying-the-Standard-Header-&-Footer-for-Patterns)

## Creating & Working With Dynamic Data for a Pattern

The PHP version of Pattern Lab utilizes Mustache as the template language for patterns. In addition to allowing for the [inclusion of one pattern within another](https://github.com/pattern-lab/patternlab-php/wiki/Including-One-Pattern-Within-Another) it also gives pattern developers the ability to include variables. This means that attributes like image sources can be centralized in one file for easy modification across one or more patterns. The PHP version of Pattern Lab uses a JSON file, `source/data/data.json`, to centralize many of these attributes.

* [Introduction to JSON & Mustache Variables](http://github.com/pattern-lab/patternlab-php/wiki/Introduction-to-JSON-&-Mustache-Variables)
* [Overriding the Central `data.json` Values with Pattern-specific Values](https://github.com/pattern-lab/patternlab-php/wiki/Overriding-the-Central-%60data.json%60-Values-with-Pattern-specific-Values)
* [Linking to Patterns with Pattern Lab's Default `link` Variable](https://github.com/pattern-lab/patternlab-php/wiki/Linking-to-Patterns-with-Pattern-Lab's-Default-%60link%60-Variable)
* [Creating Lists with Pattern Lab's Default `listItems` Variable](https://github.com/pattern-lab/patternlab-php/wiki/Creating-Lists-with-Pattern-Lab's-Default-%60listItems%60-Variable)

## Using Pattern Lab's Advanced Features

By default, the Pattern Lab assets can be manually generated and the Pattern Lab site manually refreshed but who wants to waste time doing that? Here are some ways that the PHP version of Pattern Lab can make your development workflow a little smoother:

* [Watching for Changes and Auto-Regenerating Patterns](https://github.com/pattern-lab/patternlab-php/wiki/Watching-for-Changes-and-Auto-Regenerating-Patterns)
* [Auto-Reloading the Browser Window When Changes Are Made](https://github.com/pattern-lab/patternlab-php/wiki/Auto-Reloading-the-Browser-Window-When-Changes-Are-Made)
* [Multi-browser & Multi-device Testing with Page Follow](https://github.com/pattern-lab/patternlab-php/wiki/Multi-browser-&-Multi-device-Testing-with-Page-Follow)