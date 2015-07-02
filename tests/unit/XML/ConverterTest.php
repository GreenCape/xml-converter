<?php
/**
 * GreenCape XML Converter
 *
 * MIT License
 *
 * Copyright (c) 2012-2015, Niels Braczek <nbraczek@bsds.de>. All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and
 * to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions
 * of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @package     GreenCape\ManifestTest
 * @subpackage  Unittests
 * @author      Niels Braczek <nbraczek@bsds.de>
 * @copyright   (C) 2012-2015 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license     http://opensource.org/licenses/MIT The MIT license (MIT)
 * @link        http://greencape.github.io
 * @since       File available since Release 0.1.0
 */

namespace GreenCape\Xml;

class ConverterTest extends \PHPUnit_Framework_TestCase
{
	private $xmlFile = '';
	private $phpArray = array();

	public function __construct($name = null, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->xmlFile = __DIR__ . '/../../data/breakfast.xml';
		$this->phpArray  = array(
			'breakfast_menu' => array(
				'food' => array(
					'name' => 'Waffles',
					'@lang' => 'en',
				)
			)
		);
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
	}

	public function testXmlFileToPhpArray()
	{
		$xml = new \GreenCape\Xml\Converter($this->xmlFile);

		$this->assertEquals($this->phpArray, $xml->data);
	}

	public function provideXmlStrings()
	{
		return array(
			'breakfast_menu' => array(
				'xml' => file_get_contents($this->xmlFile),
				'php' => $this->phpArray
			),
			'comment' => array(
				'xml' => '<?xml version="1.0"?><root><!-- comment --><node>foo</node></root>',
				'php' => array('root' => array('node' => 'foo', '#comment' => array('comment')))
			),
			'empty element' => array(
				'xml' => '<?xml version="1.0"?><root><node foo="bar"></node></root>',
				'php' => array('root' => array('node' => '', '@foo' => 'bar'))
			),
			'zero' => array(
				'xml' => '<?xml version="1.0"?><root><node>0</node></root>',
				'php' => array('root' => array('node' => '0'))
			),
			'null' => array(
				'xml' => '<?xml version="1.0"?><root><node /></root>',
				'php' => array('root' => array('node' => null))
			),
			'tabs' => array(
				'xml' => '<?xml version="1.0"?><root><node	foo="bar">foobar</node></root>',
				'php' => array('root' => array('node' => 'foobar', '@foo' => 'bar'))
			),
		);
	}

	/**
	 * @dataProvider provideXmlStrings
	 * @param $xmlString
	 * @param $phpArray
	 */
	public function testXmlStringToPhpArray($xmlString, $phpArray)
	{
		$xml = new \GreenCape\Xml\Converter($xmlString);

		$this->assertEquals($phpArray, $xml->data);
	}

	/**
	 * @dataProvider provideXmlStrings
	 * @param $xmlString
	 * @param $phpArray
	 */
	public function testPhpArrayToXmlString($xmlString, $phpArray)
	{
		$xml = new \GreenCape\Xml\Converter($phpArray);

		$this->assertXmlStringEqualsXmlString($xmlString, (string) $xml);
	}

	public function provideManifests()
	{
		return array(
			'component' => array(__DIR__ . '/../../data/com_alpha.xml'),
			'module'    => array(__DIR__ . '/../../data/mod_alpha.xml'),
			'plugin'    => array(__DIR__ . '/../../data/plg_system_alpha.xml'),
			'template'  => array(__DIR__ . '/../../data/templateDetails.xml'),
			'language'  => array(__DIR__ . '/../../data/xx-XX.xml'),
		);
	}

	/**
	 * @dataProvider provideManifests
	 * @param string $file
	 */
	public function testManifest($file)
	{
		$xml = new \GreenCape\Xml\Converter($file);

		$this->assertXmlStringEqualsXmlFile($file, (string) $xml);
	}
}
