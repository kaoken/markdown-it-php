<?php
namespace Kaoken\MarkdownIt;

/**
 * new Token(type, tag, nesting)
 *
 * Create new token and fill passed properties.
 **/
class Token
{
    /**
     * Type of the token (string, e.g. "paragraph_open")
     * @var string
     **/
    public $type     = '';

    /**
     * html tag $name, e.g. "p"
     * @var string
     **/
    public $tag      = '';

    /**
     * Html attributes. Format: `[ [ $name1, $value1 ], [ $name2, $value2 ] ]`
     * @var array
     **/
    public $attrs    = null;

    /**
     * Source map info. Format: `[ line_begin, line_end ]`
     * @var array
     **/
    public $map      = null;

    /**
     * Level change (number in {-1, 0, 1} set), where:
     *
     * -  `1` means the tag is opening
     * -  `0` means the tag is self-closing
     * - `-1` means the tag is closing
     * @var integer
     **/
    public $nesting  = 0;

    /**
     * nesting level, the same as `state.level`
     * @var integer
     **/
    public $level    = 0;

    /**
     * An array of child nodes (inline and img tokens)
     * @var Token[]
     **/
    public $children = null;

    /**
     * In a case of self-closing tag (code, html, fence, etc.),
     * it has contents of 	protected $tag.
     * @var string
     **/
	public $content  = '';

    /**
     * '*' or '_' for emphasis, fence string for fence, etc.
     * @var string
     **/
    public $markup   = '';

    /**
     * fence infostring
     * @var string
     **/
    public $info     = '';

    /**
     * A place for plugins to store an arbitrary data
     * @var object
     **/
    public $meta     = null;

    /**
     * True for block-level tokens, false for inline tokens.
     * Used in renderer to calculate line breaks
     * @var boolean
     **/
    public $block    = false;

    /**
     * If it's true, ignore 	protected $element when rendering. Used for tight lists
     * to hide paragraphs.
     * @var boolean
     **/
    public $hidden  = false;

    /**
     * Token constructor.
     * @param string  $type
     * @param string  $tag
     * @param integer $nesting
     */
    public function __construct($type, $tag, $nesting)
    {
        $this->type = $type;
        $this->tag = $tag;
        $this->nesting = $nesting;
    }

    /**
     * Search attribute index by $name.
     * @param $name
     * @return int
     */
    public function attrIndex($name)
    {
        if (!isset($this->attrs)) { return -1; }

        for ($i = 0, $len = count($this->attrs); $i < $len; $i++) {
            if ($this->attrs[$i][0] === $name) { return $i; }
        }
        return -1;
    }


    /**
     * Add `[ $name, $value ]` attribute to list. Init  $attrs if necessary
     * @param array $attrData
     */
    public function attrPush($attrData)
    {
        $this->attrs[] = $attrData;
    }


    /**
     * Set `$name` attribute to `$value`. Override old $value if exists.
     * @param string $name
     * @param mixed $value
     */
    public function attrSet($name, $value)
    {
        $idx = $this->attrIndex($name);
        $attrData = [ $name, $value ];

        if ($idx < 0) {
            $this->attrPush($attrData);
        } else {
            $this->attrs[$idx] = $attrData;
        }
    }


    /**
     * Get the $value of attribute `$name`, or null if it does not exist.
     * @param string $name
     * @return mixed
     */
    public function attrGet($name)
    {
        $idx = $this->attrIndex($name);
        $value = null;
        if ($idx >= 0) {
            $value = $this->attrs[$idx][1];
        }
        return $value;
    }


    /**
     * Join $value to existing attribute via space. Or create new attribute if not
     * exists. Useful to operate with token classes.
     * @param string $name
     * @param mixed $value
     */
    public function attrJoin($name, $value)
    {
        $idx = $this->attrIndex($name);
        
        if ($idx < 0) {
            $this->attrPush([ $name, $value ]);
        } else {
            $this->attrs[$idx][1] = $this->attrs[$idx][1] . ' ' . $value;
        }
    }
}