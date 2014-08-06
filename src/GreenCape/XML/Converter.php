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
 * @author      Marat A. Denenberg
 * @copyright   (C) 2014 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license     http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2.0 (GPLv2)
 * @link        http://www.greencape.com/
 * @since       File available since Release 1.0.0
 */

namespace GreenCape\Xml;

/**
 * XML Converter Class
 *
 * @package GreenCape\Xml
 * @author  Niels Braczek <nbraczek@bsds.de>
 * @author  Marat A. Denenberg
 * @since   Class available since Release 1.0.0
 */
class Converter implements \Iterator, \ArrayAccess
{
	public $xml = '';

	public $data = array();

	private $stack = array();
	private $declaration = '';
	private $tag_value = '';
	private $comment = array();
	private $comment_index = 0;
	private $doctype = '';

	public function __construct($data = array())
	{
		switch (gettype($data))
		{
			case 'string':
				$this->xml = ($this->isFile($data) ? file_get_contents($data) : $data);
				if (!empty($this->xml) && $this->xml[0] == '<')
				{
					@$this->parse();
				}
				break;

			case 'array':
				$this->data = $data;
				break;
		}
	}

	public function __toString()
	{
		return (!empty($this->declaration) ? "<?{$this->declaration}?>\n" : "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n") . $this->traverse($this->data);
	}

	private function traverse($node, $level = 0)
	{
		$xml        = '';
		$attributes = '';
		$indent     = str_repeat('    ', $level);

		if (!empty($node['#comment']))
		{
			foreach ($node['#comment'] as $comment)
			{
				$comment = "{$indent}<!-- {$comment} -->";
				$comment = $this->applyIndentation($comment, $indent);
				$xml .= "\n" . $comment . "\n";
			}
			unset($node['#comment']);
		}
		if (count($node) > 1)
		{
			foreach ($node as $key => $value)
			{
				if ($key[0] != '@')
				{
					continue;
				}
				$attributes .= ' ' . substr($key, 1);
				if (!is_bool($value))
				{
					$attributes .= '="' . $value . '"';
				}
				unset($node[$key]);
			}
		}
		foreach ($node as $tag => $data)
		{
			switch (gettype($data))
			{
				case 'array':
					$xml .= "{$indent}<{$tag}{$attributes}>\n";
					if ($this->is_assoc($data))
					{
						$xml .= $this->traverse($data, $level + 1);
					}
					else
					{
						foreach ($data as $child)
						{
							$xml .= $this->traverse($child, $level + 1);
						}
					}
					$xml .= "{$indent}</{$tag}>\n";
					break;

				case 'NULL':
					$xml .= "{$indent}<{$tag}{$attributes} />\n";
					break;

				default:
					$xml .= "{$indent}<{$tag}{$attributes}>{$data}</{$tag}>\n";
					break;
			}
		}

		return $xml;
	}

	private function is_assoc($array)
	{
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}

	private function parse()
	{
		$this->stack[] =& $this->data;
		$stream = new Stream(trim($this->xml));

		while (!$stream->isEmpty())
		{
			if ($stream->matches('<?'))
			{
				$this->handleDeclaration($stream);
			}
			elseif ($stream->matches('<!--'))
			{
				$this->handleComment($stream);
			}
			elseif ($stream->matches('<!doctype', 'i'))
			{
				$this->handleDoctype($stream);
			}
			elseif ($stream->matches('</'))
			{
				$this->handleElementClose($stream);
			}
			elseif ($stream->matches('<'))
			{
				$this->handleElementOpen($stream);
			}
			else
			{
				$this->tag_value = $stream->readTo('<');
			}
		}

		unset($this->xml);
	}

	private function handleDeclaration(Stream $stream)
	{
		// Skip '<?'
		$stream->next(2);
		$this->declaration = $stream->readTo('?');

		// Skip '?' . '>'
		$stream->next(2);
	}

	private function handleDoctype(Stream $stream)
	{
		// Skip '<!doctype'
		$stream->next(9);
		$this->doctype = $stream->readTo('>');

		// Skip '>'
		$stream->next();
	}

	private function handleComment(Stream $stream)
	{
		// Skip '<!--'
		$stream->next(4);
		$comment = '';
		do
		{
			$comment .= $stream->next();
		}
		while (!$stream->matches('-->'));
		$this->comment[$this->comment_index++] = trim($comment);

		// Skip '-->'
		$stream->next(3);
	}

	private function handleElementOpen(Stream $stream)
	{
		// Skip '<'
		$stream->next();
		$element = $stream->readTo('>');

		// Skip '>'
		$stream->next();

		$isEmpty = false;
		if (substr($element, -1) == '/')
		{
			$isEmpty = true;
			$element = substr($element, 0, -1);
		}

		$tmp = preg_split('~\s+~', $element, 2);
		$tag_name = array_shift($tmp);

		$node            = array();
		$node[$tag_name] = array();
		if (!empty($this->comment))
		{
			$node["#comment"]    = $this->comment;
			$this->comment       = array();
			$this->comment_index = 0;
		}
		if (!empty($tmp))
		{
			preg_match_all('~\s*([^= ]+)(?:=(["\']?)(.*?)\2)?~sm', $tmp[0], $matches, PREG_SET_ORDER);
			foreach ($matches as $match)
			{
				$node["@{$match[1]}"] = count($match) > 2 ? $match[3] : true;
			}
		}

		$current =& $this->stack[count($this->stack) - 1];
		if (empty($current))
		{
			$current       = $node;
			$this->stack[] =& $current[$tag_name];
		}
		else
		{
			if ($this->is_assoc($current))
			{
				$current = array($current, $node);
			}
			else
			{
				$current[] = $node;
			}
			$this->stack[] =& $current[count($current) - 1][$tag_name];
		}

		if ($isEmpty)
		{
			$this->closeElement($stream, $tag_name);
		}
	}

	private function handleElementClose(Stream $stream)
	{
		// Skip '</'
		$stream->next(2);
		$element = $stream->readTo('>');

		// Skip '>'
		$stream->next();
		$this->closeElement($stream, $element);
	}

	private function closeElement(Stream $stream, $tag_name)
	{
		$child =& $this->stack[count($this->stack) - 1];
		array_pop($this->stack);

		$last = count($this->stack) - 1;
		if (isset($this->stack[$last][$tag_name]) || isset(end($this->stack[$last])[$tag_name]))
		{
			if (empty($child))
			{
				$this->tag_value = trim($this->tag_value);
				$child           = strlen($this->tag_value) > 0 ? $this->tag_value : null;
			}
			$this->tag_value = '';
		}
		else
		{
			$this->syntax_error($stream);
		}
	}

	// ### Iterator: foreach access ###

	public function rewind()
	{
		reset($this->data);
	}

	public function current()
	{
		return current($this->data);
	}

	public function key()
	{
		return key($this->data);
	}

	public function next()
	{
		return next($this->data);
	}

	public function valid()
	{
		$key = key($this->data);

		return ($key !== null && $key !== false);
	}

	// ### ArrayAccess: key/value access ###

	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
		{
			$this->data[] = $value;
		}
		else
		{
			$this->data[$offset] = $value;
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	public function version()
	{
		return preg_match('#version\="(.*)"#U', $this->declaration, $match) ? $match[1] : '1.0';
	}

	public function encoding()
	{
		return preg_match('#encoding\="(.*)"#U', $this->declaration, $match) ? $match[1] : 'utf-8';
	}

	private function syntax_error(Stream $stream)
	{
		error_log("Syntax error in XML data. Please check line # {$stream->line()}.");
	}

	/**
	 * @param $data
	 *
	 * @return bool
	 */
	private function isFile($data)
	{
		return file_exists($data);
	}

	/**
	 * @param $text
	 * @param $indent
	 *
	 * @return mixed
	 */
	private function applyIndentation($text, $indent)
	{
		return preg_replace('~\s*\n\s*~', "\n{$indent}", $text);
	}
}
