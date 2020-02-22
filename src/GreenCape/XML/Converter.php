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
 * @package         GreenCape\Xml
 * @author          Niels Braczek <nbraczek@bsds.de>
 * @author          Marat A. Denenberg
 * @copyright   (C) 2014-2015 GreenCape, Niels Braczek <nbraczek@bsds.de>
 * @license         http://opensource.org/licenses/MIT The MIT license (MIT)
 * @link            http://greencape.github.io
 * @since           File available since Release 1.0.0
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

    public $data = [];

    private $stack         = [];
    private $declaration   = '';
    private $tag_value     = '';
    private $comment       = [];
    private $comment_index = 0;

    /**
     * Converter constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        switch (gettype($data)) {
            case 'string':
                $this->xml = ($this->isFile($data) ? file_get_contents($data) : $data);
                if (!empty($this->xml) && $this->xml[0] == '<') {
                    $this->parse();
                }
                break;

            case 'array':
                $this->data = $data;
                break;
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
     * @throws \ErrorException
     */
    private function parse()
    {
        $this->stack[] =& $this->data;
        $stream        = new Stream(trim($this->xml));

        while (!$stream->isEmpty()) {
            if ($stream->matches('<?')) {
                $this->handleDeclaration($stream);
            } elseif ($stream->matches('<!--')) {
                $this->handleComment($stream);
            } elseif ($stream->matches('<!doctype', 'i')) {
                $this->handleDoctype($stream);
            } elseif ($stream->matches('</')) {
                $this->handleElementClose($stream);
            } elseif ($stream->matches('<')) {
                $this->handleElementOpen($stream);
            } else {
                $this->tag_value = $stream->readTo('<');
            }
        }

        unset($this->xml);
    }

    /**
     * @param Stream $stream
     */
    private function handleDeclaration(Stream $stream)
    {
        // Skip '<?'
        $stream->next(2);
        $this->declaration = $stream->readTo('?');

        // Skip '?' . '>'
        $stream->next(2);
    }

    /**
     * @param Stream $stream
     */
    private function handleComment(Stream $stream)
    {
        // Skip '<!--'
        $stream->next(4);
        $comment = '';

        do {
            $comment .= $stream->next();
        } while (!$stream->matches('-->'));

        $this->comment[$this->comment_index++] = trim($comment);

        // Skip '-->'
        $stream->next(3);
    }

    /**
     * @param Stream $stream
     */
    private function handleDoctype(Stream $stream)
    {
        // Skip '<!doctype'
        $stream->next(9);
        $stream->readTo('>');

        // Skip '>'
        $stream->next();
    }

    /**
     * @param Stream $stream
     *
     * @throws \ErrorException
     */
    private function handleElementClose(Stream $stream)
    {
        // Skip '</'
        $stream->next(2);
        $element = $stream->readTo('>');

        // Skip '>'
        $stream->next();
        $this->closeElement($stream, $element);
    }

    /**
     * @param Stream $stream
     *
     * @throws \ErrorException
     */
    private function handleElementOpen(Stream $stream)
    {
        // Skip '<'
        $stream->next();
        $element = $stream->readTo('>');

        // Skip '>'
        $stream->next();

        $isEmpty = false;

        if (substr($element, -1) === '/') {
            $isEmpty = true;
            $element = substr($element, 0, -1);
        }

        $tmp      = preg_split('~\s+~', $element, 2);
        $tag_name = array_shift($tmp);

        $node            = [];
        $node[$tag_name] = [];

        if (!empty($this->comment)) {
            $node['#comment'] = $this->comment;
            $this->comment    = [];
            $this->comment_index = 0;
        }

        if (!empty($tmp)) {
            preg_match_all('~\s*([^= ]+)(?:=(["\']?)(.*?)\2)?~sm', $tmp[0], $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $node["@{$match[1]}"] = count($match) > 2 ? $match[3] : true;
            }
        }

        $current =& $this->stack[count($this->stack) - 1];

        if (empty($current)) {
            $current       = $node;
            $this->stack[] =& $current[$tag_name];
        } else {
            if ($this->isAssoc($current)) {
                $current = [$current, $node];
            } else {
                $current[] = $node;
            }

            $this->stack[] =& $current[count($current) - 1][$tag_name];
        }

        if ($isEmpty) {
            $this->closeElement($stream, $tag_name);
        }
    }

    /**
     * @param Stream $stream
     * @param        $tag_name
     *
     * @throws \ErrorException
     */
    private function closeElement(Stream $stream, $tag_name)
    {
        $child =& $this->stack[count($this->stack) - 1];
        array_pop($this->stack);

        $last = count($this->stack) - 1;

        if (isset($this->stack[$last][$tag_name]) || isset(end($this->stack[$last])[$tag_name])) {
            if (empty($child)) {
                $this->tag_value = trim($this->tag_value);
                $child           = strlen($this->tag_value) > 0 ? $this->tag_value : null;
            }

            $this->tag_value = '';
        } else {
            throw new \ErrorException("Syntax error in XML data. Please check line # {$stream->line()}.");
        }
    }

    /**
     * @param $array
     *
     * @return bool
     */
    private function isAssoc($array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (!empty($this->declaration) ? "<?{$this->declaration}?>\n" : "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n") . $this->traverse($this->data);
    }

    // ### Iterator: foreach access ###

    /**
     * @param     $node
     * @param int $level
     *
     * @return string
     */
    private function traverse($node, $level = 0)
    {
        $xml        = '';
        $attributes = '';
        $indent     = str_repeat('    ', $level);

        if (!empty($node['#comment'])) {
            foreach ($node['#comment'] as $comment) {
                $comment = "{$indent}<!-- {$comment} -->";
                $comment = $this->applyIndentation($comment, $indent);
                $xml     .= "\n" . $comment . "\n";
            }
            unset($node['#comment']);
        }

        if (count($node) > 1) {
            foreach ($node as $key => $value) {
                if ($key[0] !== '@') {
                    continue;
                }

                $attributes .= ' ' . substr($key, 1);

                if (!is_bool($value)) {
                    $attributes .= '="' . $value . '"';
                }

                unset($node[$key]);
            }
        }

        foreach ($node as $tag => $data) {
            switch (gettype($data)) {
                case 'array':
                    $xml .= "{$indent}<{$tag}{$attributes}>\n";

                    if ($this->isAssoc($data)) {
                        $xml .= $this->traverse($data, $level + 1);
                    } else {
                        foreach ($data as $child) {
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

    /**
     *
     */
    public function rewind()
    {
        reset($this->data);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * @return bool|float|int|string|null
     */
    public function key()
    {
        return key($this->data);
    }

    // ### ArrayAccess: key/value access ###

    /**
     * @return mixed|void
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $key = key($this->data);

        return ($key !== null && $key !== false);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * @return mixed|string
     */
    public function version()
    {
        return preg_match('#version="(.*)"#U', $this->declaration, $match) ? $match[1] : '1.0';
    }

    /**
     * @return mixed|string
     */
    public function encoding()
    {
        return preg_match('#encoding="(.*)"#U', $this->declaration, $match) ? $match[1] : 'utf-8';
    }
}
