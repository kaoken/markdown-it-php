<?php

namespace Kaoken\MarkdownIt\Plugins\Emoji;


use Kaoken\MarkdownIt\Token;

class Render
{
    /**
     * @param Token[]  $tokens
     * @param interger $idx
     * @param null $options
     * @param null $env
     * @return string
     */
    function emoji_html(&$tokens, $idx, $options=null, $env=null)
    {
        return $tokens[$idx]->content;
    }
}