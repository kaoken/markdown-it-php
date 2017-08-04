<?php
namespace Kaoken\MarkdownIt\Rules;

use Kaoken\MarkdownIt\Common\Utils;
use Kaoken\MarkdownIt\Renderer;
use Kaoken\MarkdownIt\Token;

class DefaultRules
{
    public $utils = null;

    public function __construct()
    {
        $this->utils = Utils::getInstance();
    }

    /**
     * @param Token[]  $tokens
     * @param integer  $idx
     * @param object   $options
     * @param object   $env
     * @param Renderer $slf
     * @return string
     */
    public function code_inline(array &$tokens, $idx, $options, $env, $slf)
    {
        $token = $tokens[$idx];

        return  '<code' . $slf->renderAttrs($token) . '>' .
        htmlspecialchars($tokens[$idx]->content) . '</code>';
    }


    /**
     * @param Token[]  $tokens
     * @param integer  $idx
     * @param object   $options
     * @param object   $env
     * @param Renderer $slf
     * @return string
     */
    public function code_block(array &$tokens, $idx, $options, $env, $slf)
    {
        $token = $tokens[$idx];

        return  '<pre' . $slf->renderAttrs($token) . '><code>' .
        htmlspecialchars($tokens[$idx]->content) .
        "</code></pre>\n";
    }


    /**
     * @param Token[]  $tokens
     * @param integer  $idx
     * @param object   $options
     * @param object   $env
     * @param Renderer $slf
     * @return bool|string
     */
    public function fence(array &$tokens, $idx, $options, $env, $slf)
    {
        $token = $tokens[$idx];
        $info = $token->info ? trim($this->utils->unescapeAll( $token->info )) : '';
        $langName = '';

        if ($info) {
            $langName = preg_split("/\s+/",$info)[0]; // /g
        }

        if ( isset($options->highlight) ) {
            $fn = $options->highlight;
            if( empty( $highlighted = $fn($token->content, $langName)) )
                $highlighted = htmlspecialchars($token->content);
        } else {
            $highlighted = htmlspecialchars($token->content);
        }

        if (strpos($highlighted, '<pre') === 0) {
            return $highlighted . "\n";
        }

        // If language exists, inject class gently, without modifying original token.
        // May be, one day we will add .clone() for $token and simplify this part, but
        // now we prefer to keep things local.
        if ($info) {
            $i        = $token->attrIndex('class');
            $tmpAttrs = $token->attrs ? $token->attrs.slice() : [];

            if ($i < 0) {
                $tmpAttrs[] = [ 'class', $options->langPrefix . $langName ];
            } else {
                $tmpAttrs[$i][1] .= ' ' . $options->langPrefix . $langName;
            }

            // Fake $token just to render attributes
            $tmpToken = new \stdClass();
            $tmpToken->attrs = $tmpAttrs;

            return  '<pre><code' . $slf->renderAttrs($tmpToken) . '>'
            . $highlighted
            . "</code></pre>\n";
        }


        return  '<pre><code' . $slf->renderAttrs($token) . '>'
        . $highlighted
        . "</code></pre>\n";
    }

    /**
     * @param Token[]  $tokens
     * @param integer  $idx
     * @param object   $options
     * @param object   $env
     * @param Renderer $slf
     * @return mixed
     */
    public function image(array &$tokens, $idx, $options, $env, $slf)
    {
        $token = $tokens[$idx];

        // "alt" attr MUST be set, even if empty. Because it's mandatory and
        // should be placed on proper position for tests.
        //
        // Replace content with actual value

        $token->attrs[$token->attrIndex('alt')][1] =
            $slf->renderInlineAsText($token->children, $options, $env);

        return $slf->renderToken($tokens, $idx, $options);
    }


    /**
     * @param Token[] $tokens
     * @param integer $idx
     * @param object  $options
     * @return string
     */
    public function hardbreak(array &$tokens, $idx, $options, $env=null) {
        return $options->xhtmlOut ? "<br />\n" : "<br>\n";
    }

    /**
     * @param Token[] $tokens
     * @param integer $idx
     * @param object  $options
     * @return string
     */
    public function softbreak(array &$tokens, $idx, $options, $env=null)
    {
        return $options->breaks ? ($options->xhtmlOut ? "<br />\n" : "<br>\n") : "\n";
    }


    /**
     * @param Token[] $tokens
     * @param integer $idx
     * @return string
     */
    public function text(array &$tokens, $idx, $options=null, $env=null)
    {
        return htmlspecialchars($tokens[$idx]->content);
    }

    /**
     * @param Token[] $tokens
     * @param integer $idx
     * @param object|null $options
     * @param object|null $env
     * @return string
     */
    public function html_block($tokens, $idx, $options=null, $env=null)
    {
        return $tokens[$idx]->content;
    }

    /**
     * @param Token[] $tokens
     * @param integer $idx
     * @param object|null $options
     * @param object|null $env
     * @return string
     */
    public function html_inline(array &$tokens, $idx, $options=null, $env=null)
    {
        return $tokens[$idx]->content;
    }
}