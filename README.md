[![Latest Stable Version](https://poser.pugx.org/greencape/xml-converter/v/stable.png)](https://packagist.org/packages/greencape/xml-converter)
[![Build Status](https://api.travis-ci.org/GreenCape/xml-converter.svg?branch=master)](https://travis-ci.org/greencape/xml-converter)

# PHP XML Converter

A PHP XML parser class that provides an easy way to convert XML into native PHP
arrays and back again. It has no dependencies on any external libraries or
extensions bundled with PHP. The entire parser is concisely written in PHP.

This project is actively maintained. It is used in our production code. If you
spot an issue, please let us know through the Issues section on our Github
project page: https://github.com/greencape/xml-converter/issues

In short, this project makes sense for those who want to simplify their PHP
install and use, have a need for a simple XML parser, but don't much care
about speed.

## Requirements

PHP 5.4+

## Installation

### Composer

Simply add a dependency on `greencape/xml-converter` to your project's `composer.json` file if you use
[Composer](http://getcomposer.org/) to manage the dependencies of your project. Here is a minimal example of a
`composer.json` file that just defines a dependency on XML Converter:

    {
        "require": {
            "greencape/xml-converter": "*@dev"
        }
    }

For a system-wide installation via Composer, you can run:

    composer global require 'greencape/xml-converter=*'

Make sure you have `~/.composer/vendor/bin/` in your path.

## Usage Examples

### XML String to PHP Array

```php
<?php

$xml = new \GreenCape\Xml\Converter('<?xml version="1.0" encoding="ISO-8859-1"?>
<breakfast_menu>
	<food>
		<name>Waffles</name>
	</food>
</breakfast_menu>');
var_dump($xml->data);

?>
```

### XML File to PHP Array

```php
<?php
$xml = new \GreenCape\Xml\Converter('some_xml_file.xml');
var_dump($xml->data);

?>
```

### PHP Array to XML String

```php
<?php
$xml = new \GreenCape\Xml\Converter(array(
	'breakfast_menu' => array(
		array(
			'food' => array(
				'name' => 'Waffles'
			)
		)
	)
));
echo $xml;

?>
```
