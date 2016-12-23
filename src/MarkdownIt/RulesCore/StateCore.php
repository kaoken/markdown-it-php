<?php
// Core state object
//

namespace Kaoken\MarkdownIt\RulesCore;

use Kaoken\MarkdownIt\Token;

class StateCore
{
    /**
     * @var string
     */
    public $src = '';
    /**
     * @var object
     */
    public $env;
    /**
     * @var \Kaoken\MarkdownIt\Token[]
     */
    public $tokens = [];
    public $inlineMode = false;
    /**
     * @var \Kaoken\MarkdownIt\MarkdownIt
     */
    public $md = null; // link to parser instance

    /**
     * StateCore constructor.
     * @param string $src
     * @param \Kaoken\MarkdownIt\MarkdownIt $md
     * @param object|null $env
     */
    public function __construct($src, $md, $env=null) {
        $this->src = $src;
        $this->env = $env;
        $this->tokens = [];
        $this->inlineMode = false;
        $this->md = $md;
    }

    /**
     * @param string  $type
     * @param string  $tag
     * @param integer $nesting
     * @return \Kaoken\MarkdownIt\Token
     */
    public function createToken($type, $tag, $nesting)
    {
        return new Token($type, $tag, $nesting);
    }
}