<?php
/**
 * GreenCape XML Converter
 *
 * Copyright (c) 2014, Niels Braczek <nbraczek@bsds.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of GreenCape or Niels Braczek nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     GreenCape\Manifest
 * @subpackage  Unittests
 * @author      Niels Braczek <nbraczek@bsds.de>
 * @copyright   (C) 2014 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license     http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2.0 (GPLv2)
 * @link        http://www.greencape.com/
 * @since       File available since Release 0.1.0
 */

class ConverterTest extends PHPUnit_Framework_TestCase
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
