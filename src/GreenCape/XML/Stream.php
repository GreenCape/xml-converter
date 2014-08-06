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
 * @package     GreenCape\Xml
 * @author      Niels Braczek <nbraczek@bsds.de>
 * @copyright   (C) 2014 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license     http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2.0 (GPLv2)
 * @link        http://www.greencape.com/
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
