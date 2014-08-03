<?php
/**
 * GreenCape XML Converter
 * Copyright (c) 2014, Niels Braczek <nbraczek@bsds.de>.
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *   * Neither the name of GreenCape or Niels Braczek nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
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
 * @package         GreenCape\Xml
 * @author          Niels Braczek <nbraczek@bsds.de>
 * @author          Marat A. Denenberg
 * @copyright   (C) 2014 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license         http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2.0 (GPLv2)
 * @link            http://www.greencape.com/
 * @since           File available since Release 0.1.0
 */

namespace GreenCape\Xml;

class Converter implements \Iterator, \ArrayAccess
{
	public $xml = '';

	public $data = array();

	private $stack = array();
	private $declaration = '';
	private $index = 0;
	private $line = 0;
	private $tag_name = '';
	private $tag_value = '';
	private $attribute_name = '';
	private $attribute_value = '';
	private $attributes = array();
	private $comment = array();
	private $comment_index = 0;
	private $syntax = 'syntax_tag_value';

	public function __construct($data = array())
	{
		switch (gettype($data))
		{
			case 'string':
				$this->xml = ($this->isFile($data) ? file_get_contents($data) : $data);
				if (!empty($this->xml) && $this->xml[0] == '<')
				{
					$this->parse();
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
			foreach ($node['#comment'] as $comment_index => $comment)
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
				$attributes .= ' ' . substr($key, 1) . '="' . $value . '"';
				unset($node[$key]);
			}
		}
		foreach ($node as $tag => $data)
		{
			if (empty($data))
			{
				$data = null;
			}
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
		$this->xml = str_replace("\t", ' ', $this->xml);

		$this->stack[] =& $this->data;

		for ($length = strlen($this->xml); $this->index < $length; $this->index++)
		{
			if ($this->syntax == 'syntax_comment')
			{
				if ($this->xml[$this->index] == "\n")
				{
					$this->line++;
				}
				$this->{$this->syntax}();
				continue;
			}

			switch ($this->xml[$this->index])
			{
				case '<':
					switch ($this->xml[$this->index + 1])
					{
						case '?':
							$this->index += 2;
							$this->syntax = 'syntax_declaration';
							break;

						case '/':
							$this->index += 2;
							$this->tag_name = '';
							$this->syntax   = 'syntax_tag_back_start';
							break;

						case '!':
							$this->index += 4;
							$this->syntax  = 'syntax_comment';
							break;

						default:
							$this->index += 1;
							$this->tag_name   = $this->tag_value = '';
							$this->attributes = array();
							$this->syntax     = 'syntax_tag_front_start';
							break;
					}
					break;

				case '/':
					switch ($this->xml[$this->index + 1])
					{
						case '>':
							$this->index += 1;
							$this->syntax = 'syntax_tag_front_end';
							break;
					}
					break;

				case '>':
					switch ($this->syntax)
					{
						case 'syntax_tag_front_start':
						case 'syntax_attribute_name':
							$this->syntax = 'syntax_tag_front_end';
							break;

						case 'syntax_comment':
							break;

						default:
							$this->xml    = substr($this->xml, $this->index);
							$this->index  = 0;
							$length       = strlen($this->xml);
							$this->syntax = 'syntax_tag_back_end';
							break;
					}
					break;

				case "\n":
					$this->line++;
					break;
			}

			$this->{$this->syntax}();
		}

		unset($this->xml);
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

	private function syntax_declaration()
	{
		if ($this->xml[$this->index] == '?' && $this->xml[$this->index + 1] == '>')
		{
			$this->index++;
			$this->syntax = 'syntax_tag_value';
		}
		else
		{
			$this->declaration .= $this->xml[$this->index];
		}
	}

	private function syntax_error()
	{
		error_log("Syntax error in XML data. Please check line # {$this->line}.");
	}

	private function syntax_tag_front_start()
	{
		switch ($this->xml[$this->index])
		{
			case ' ':
				$this->syntax         = 'syntax_attribute_name';
				$this->attribute_name = $this->attribute_value = '';
				break;

			default:
				$this->tag_name .= $this->xml[$this->index];
				break;
		}
	}

	private function syntax_tag_front_end()
	{
		$node                  = array();
		$node[$this->tag_name] = array();
		if (!empty($this->comment))
		{
			$node["#comment"] = $this->comment;
			$this->comment = array();
			$this->comment_index = 0;
		}
		if (!empty($this->attributes))
		{
			foreach ($this->attributes as $key => $value)
			{
				$node["@{$key}"] = $value;
			}
		}

		$current =& $this->stack[count($this->stack) - 1];
		if (empty($current))
		{
			$current       = $node;
			$this->stack[] =& $current[$this->tag_name];
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
			$this->stack[] =& $current[count($current) - 1][$this->tag_name];
		}

		$this->syntax = 'syntax_tag_value';
	}

	private function syntax_tag_back_start()
	{
		$this->tag_name .= $this->xml[$this->index];
	}

	private function syntax_tag_back_end()
	{
		$child =& $this->stack[count($this->stack) - 1];
		array_pop($this->stack);

		$last = count($this->stack) - 1;
		if (isset($this->stack[$last][$this->tag_name]) || isset(end($this->stack[$last])[$this->tag_name]))
		{
			if (empty($child))
			{
				$this->tag_value = trim($this->tag_value);
				$child = !empty($this->tag_value) ? $this->tag_value : null;
			}
			$this->tag_value = '';
			$this->syntax    = 'syntax_tag_value';
		}
		else
		{
			$this->syntax_error();
		}
	}

	private function syntax_tag_value()
	{
		$this->tag_value .= $this->xml[$this->index];
	}

	private function syntax_attribute_name()
	{
		switch ($this->xml[$this->index])
		{
			case '=':
			case ' ':
				break;

			case '"':
				$this->syntax = 'syntax_attribute_value';
				break;

			default:
				$this->attribute_name .= $this->xml[$this->index];
				break;
		}
	}

	private function syntax_attribute_value()
	{
		switch ($this->xml[$this->index])
		{
			case '"':
				$this->syntax = 'syntax_attribute_end';
				$this->index--;
				break;

			default:
				$this->attribute_value .= $this->xml[$this->index];
				break;
		}
	}

	private function syntax_attribute_end()
	{
		$this->attributes[$this->attribute_name] = $this->attribute_value;
		$this->syntax                            = 'syntax_tag_front_start';
	}

	private function syntax_comment()
	{
		if ($this->xml[$this->index] == '-' && $this->xml[$this->index + 1] == '-')
		{
			$this->index += 2;
			$this->comment[$this->comment_index] = trim($this->comment[$this->comment_index]);
			$this->comment_index++;
			$this->syntax = 'syntax_tag_value';
		}
		else
		{
			@$this->comment[$this->comment_index] .= $this->xml[$this->index];
		}
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
*@return mixed
	 */
	private function applyIndentation($text, $indent)
	{
		return preg_replace('~\s*\n\s*~', "\n{$indent}", $text);
}
}
