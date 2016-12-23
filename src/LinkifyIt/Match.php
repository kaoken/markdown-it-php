<?php
/**
Copyright (c) 2016 Kaoken, Vitaly Puzrin.

This software is released under the MIT License.
http://opensource.org/licenses/mit-license.php
 */

namespace Kaoken\LinkifyIt;
use Kaoken\MarkdownIt\RulesCore\Linkify;

/**
 * class Match
 *
 * Match result. Single element of array, returned by [[LinkifyIt#match]]
 **/
class Match
{
    /**
     * Prefix (protocol) for matched string.
     * @var string
     **/
	public $schema;
    /**
     * First position of matched string.
     * @var integer
     **/
	public $index;
    /**
     * Next position after matched string.
     * @var integer
     **/
	public $lastIndex;
    /**
     * Matched string.
     * @var string
     **/
	public $raw;
    /**
     * Notmalized text of matched string.
     * @var string
     **/
	public $text;
    /**
     * Normalized url of matched string.
     * @var string
     **/
	public $url;
    /**
     * Match constructor.
     * @param LinkifyIt $self
     * @param $shift
     */
    public function __construct($self, $shift)
    {
        $start = $self->getIndex();
        $end   = $self->getLastIndex();
        $text  = substr($self->getTextCache(), $start, $end-$start);

        $this->schema    = strtolower($self->getSchema());
        $this->index     = $start + $shift;
        $this->lastIndex = $end + $shift;
        $this->raw       = $text;
        $this->text      = $text;
        $this->url       = $text;
        $self->normalizeFromCompiled($this->schema,$this);
    }
}