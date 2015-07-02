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
 * @package     GreenCape\Xml
 * @author      Niels Braczek <nbraczek@bsds.de>
 * @copyright   (C) 2012-2015 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license     http://opensource.org/licenses/MIT The MIT license (MIT)
 * @link        http://greencape.github.io
 * @since       File available since Release 1.1.0
 */

namespace GreenCape\Xml;

/**
 * XML Stream Class
 *
 * @package GreenCape\Xml
 * @author  Niels Braczek <nbraczek@bsds.de>
 * @since   Class available since Release 1.1.0
 */
class Stream
{
	private $data = null;
	private $line = 1;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function matches($string, $modifier = '')
	{
		$stream = substr($this->data, 0, strlen($string));
		if (strpos($modifier, 'i') !== false)
		{
			$stream = strtolower($stream);
			$string = strtolower($string);
		}
		return $stream == $string;
	}

	public function current()
	{
		return $this->data[0];
	}

	public function next($length = 1)
	{
		$c = substr($this->data, 0, $length);
		$this->data = substr($this->data, $length);

		for ($i = 0; $i < strlen($c); ++$i)
		{
			if ($c[$i] == "\n")
			{
				$this->line++;
			}
		}
		return $c;
	}

	public function flush()
	{
		$r = $this->data;
		$this->data = null;

		return $r;
	}

	public function isEmpty()
	{
		return empty($this->data);
	}

	public function line()
	{
		return $this->line;
	}

	public function readTo($char)
	{
		$result = '';
		while ($this->current() != $char)
		{
			$result .= $this->next();
		}

		return $result;
	}
}
