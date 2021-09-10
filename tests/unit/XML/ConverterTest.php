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
 * @package         GreenCape\ManifestTest
 * @subpackage      Unittests
 * @author          Niels Braczek <nbraczek@bsds.de>
 * @copyright   (C) 2012-2015 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license         http://opensource.org/licenses/MIT The MIT license (MIT)
 * @link            http://greencape.github.io
 * @since           File available since Release 0.1.0
 */

namespace GreenCape\Xml;

use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    private $xmlFile = __DIR__ . '/../../data/breakfast.xml';
    private $phpArray = [
        'breakfast_menu' => [
            'food' => [
                'name'  => 'Waffles',
                '@lang' => 'en',
            ],
        ],
    ];

    public function testXmlFileToPhpArray(): void
    {
        $xml = new Converter($this->xmlFile);

        $this->assertEquals($this->phpArray, $xml->data);
    }

    public function provideXmlStrings(): array
    {
        return [
            'breakfast_menu' => [
                'xml' => file_get_contents($this->xmlFile),
                'php' => $this->phpArray,
            ],
            'comment'        => [
                'xml' => '<?xml version="1.0"?><root><!-- comment --><node>foo</node></root>',
                'php' => ['root' => ['node' => 'foo', '#comment' => ['comment']]],
            ],
            'empty element'  => [
                'xml' => '<?xml version="1.0"?><root><node foo="bar"></node></root>',
                'php' => ['root' => ['node' => '', '@foo' => 'bar']],
            ],
            'zero'           => [
                'xml' => '<?xml version="1.0"?><root><node>0</node></root>',
                'php' => ['root' => ['node' => '0']],
            ],
            'null'           => [
                'xml' => '<?xml version="1.0"?><root><node /></root>',
                'php' => ['root' => ['node' => null]],
            ],
            'tabs'           => [
                'xml' => '<?xml version="1.0"?><root><node	foo="bar">foobar</node></root>',
                'php' => ['root' => ['node' => 'foobar', '@foo' => 'bar']],
            ],
            'cdata'           => [
                'xml' => '<?xml version="1.0"?><root><node><![CDATA[<salutation>Hello World!</salutation>]]></node></root>',
                'php' => ['root' => ['node' => '<salutation>Hello World!</salutation>']],
            ],
        ];
    }

    /**
     * @dataProvider provideXmlStrings
     *
     * @param $xmlString
     * @param $phpArray
     */
    public function testXmlStringToPhpArray($xmlString, $phpArray): void
    {
        $xml = new Converter($xmlString);

        $this->assertEquals($phpArray, $xml->data);
    }

    /**
     * @dataProvider provideXmlStrings
     *
     * @param $xmlString
     * @param $phpArray
     */
    public function testPhpArrayToXmlString($xmlString, $phpArray): void
    {
        $xml = new Converter($phpArray);

        $this->assertXmlStringEqualsXmlString($xmlString, (string)$xml);
    }

    /**
     * @return array
     */
    public function provideManifests(): array
    {
        return [
            'component' => [__DIR__ . '/../../data/com_alpha.xml'],
            'module'    => [__DIR__ . '/../../data/mod_alpha.xml'],
            'plugin'    => [__DIR__ . '/../../data/plg_system_alpha.xml'],
            'template'  => [__DIR__ . '/../../data/templateDetails.xml'],
            'language'  => [__DIR__ . '/../../data/xx-XX.xml'],
        ];
    }

    /**
     * @dataProvider provideManifests
     *
     * @param  string  $file
     */
    public function testManifest(string $file): void
    {
        $xml = new Converter($file);

        $this->assertXmlStringEqualsXmlFile($file, (string)$xml);
    }
}
