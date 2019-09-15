<?php
/**
 * Copyright (c) 2014-2015 Vitaly Puzrin, Alex Kocharin.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
/**
 * Copyright (c) 2016 kaoken
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 *
 *
 * use javascript version 3.0.2
 * @see https://github.com/markdown-it/markdown-it-footnote
 */
// Process footnotes
//
namespace Kaoken\MarkdownIt\Plugins;


use Exception;
use Kaoken\MarkdownIt\MarkdownIt;
use Kaoken\MarkdownIt\RulesInline\StateInline;
use Kaoken\MarkdownIt\Token;

class MarkdownItFootnote
{
////////////////////////////////////////////////////////////////////////////////
// Renderer partials
    /**
     * @param MarkdownIt $md
     * @throws Exception
     */
    function plugin($md)
    {

        /**
         * @param Token[] $tokens
         * @param integer $idx
         * @param object  $options
         * @param object  $env
         * @param object  $slf
         * @return string
         */
        $md->renderer->rules->footnote_ref = function (&$tokens, $idx, $options, $env, $slf) {
            $fn = $slf->rules->footnote_anchor_name;
            $id      = $fn($tokens, $idx, $options, $env, $slf);
            $fn = $slf->rules->footnote_caption;
            $caption = $fn($tokens, $idx, $options, $env, $slf);
            $refid   = $id;

            if ( isset($tokens[$idx]->meta->subId) && $tokens[$idx]->meta->subId > 0) {
                $refid .= ':' . $tokens[$idx]->meta->subId;
            }

            return '<sup class="footnote-ref"><a href="#fn' . $id . '" id="fnref' . $refid . '">' . $caption . '</a></sup>';
        };
        /**
         * @param Token[] $tokens
         * @param integer $idx
         * @param object  $options
         * @return string
         */
        $md->renderer->rules->footnote_block_open = function(&$tokens, $idx, &$options) {
            return ($options->xhtmlOut ? "<hr class=\"footnotes-sep\" />\n" : "<hr class=\"footnotes-sep\">\n") .
                "<section class=\"footnotes\">\n" .
                "<ol class=\"footnotes-list\">\n";
        };
        $md->renderer->rules->footnote_block_close = function () {
            return "</ol>\n</section>\n";
        };
        /**
         * @param Token[] $tokens
         * @param integer $idx
         * @param object  $options
         * @param object  $env
         * @param $slf
         * @return string
         */
        $md->renderer->rules->footnote_open = function (&$tokens, $idx, $options, $env, $slf) {
            $fn = $slf->rules->footnote_anchor_name;
            $id = $fn($tokens, $idx, $options, $env, $slf);

            if ( isset($tokens[$idx]->meta->subId) && $tokens[$idx]->meta->subId > 0) {
                $id .= ':' . $tokens[$idx]->meta->subId;
            }

            return '<li id="fn' . $id . '" class="footnote-item">';
        };
        $md->renderer->rules->footnote_close = function () {
            return "</li>\n";
        };
        /**
         * @param Token[] $tokens
         * @param integer $idx
         * @param object  $options
         * @param $env
         * @param $slf
         * @return string
         */
        $md->renderer->rules->footnote_anchor = function (&$tokens, $idx, $options, $env, $slf) {
            $fn = $slf->rules->footnote_anchor_name;
            $id = $fn($tokens, $idx, $options, $env, $slf);

            if ( isset($tokens[$idx]->meta->subId) && $tokens[$idx]->meta->subId > 0) {
                $id .= ':' . $tokens[$idx]->meta->subId;
            }

            /* ↩ with escape code to prevent display as Apple Emoji on iOS */
            return ' <a href="#fnref' . $id . '" class="footnote-backref">↩︎</a>';
        };

        // helpers (only used in other rules, no $tokens are attached to those)
        /**
         * @param Token[] $tokens
         * @param integer $idx
         * @param null $options
         * @param null $env
         * @param null $slf
         * @return string
         */
        $md->renderer->rules->footnote_caption = function ($tokens, $idx, $options=null, $env=null, $slf=null) {
            $n = (string)($tokens[$idx]->meta->id + 1);

            if ( isset($tokens[$idx]->meta->subId) && $tokens[$idx]->meta->subId > 0) {
                $n .= ':' . $tokens[$idx]->meta->subId;
            }

            return '[' . $n . ']';
        };
        /**
         * @param Token[] $tokens
         * @param integer $idx
         * @param $options
         * @param $env
         * @return string
         */
        $md->renderer->rules->footnote_anchor_name  = function ($tokens, $idx, $options, $env, $slf=null) {
            $n = (string)($tokens[$idx]->meta->id + 1);
            $prefix = '';

            if ( isset($env->docId) && is_string($env->docId) ) {
                $prefix = '-' . $env->docId . '-';
            }

            return $prefix . $n;
        };

        /**
         * Process footnote block definition
         * @param \Kaoken\MarkdownIt\RulesBlock\StateBlock $state
         * @param $startLine
         * @param $endLine
         * @param $silent
         * @return bool
         */
        $footnote_def = function($state, $startLine, $endLine, $silent=false)
        {
            $start = $state->bMarks[$startLine] + $state->tShift[$startLine];
            $max = $state->eMarks[$startLine];

            // line should be at least 5 chars - "[^x]:"
            if ($start + 4 > $max) { return false; }

            if ($state->src[$start] !== '[') { return false; }
            if ($state->src[$start+1] !== '^') { return false; }

            for ($pos = $start + 2; $pos < $max; $pos++) {
                if ($state->src[$pos] === ' ') { return false; }
                if ($state->src[$pos] === ']') {
                    break;
                }
            }

            if ($pos === $start + 2) { return false; } // no empty footnote labels
            if ($pos + 1 >= $max || $state->src[++$pos] !== ':') { return false; }
            if ($silent) { return true; }
            $pos++;

            if (!isset($state->env->footnotes)) { $state->env->footnotes = new \stdClass(); }
            if (!isset($state->env->footnotes->refs)) { $state->env->footnotes->refs =[]; }
            $label = substr($state->src, $start + 2, $pos - 2 - ($start + 2));
            $state->env->footnotes->refs[':' . $label] = -1;

            $token       = $state->createToken('footnote_reference_open', '', 1);
            $token->meta = new \stdClass();
            $token->meta->label  = $label;
            $token->level = $state->level++;
            $state->tokens[] = $token;

            $oldBMark = $state->bMarks[$startLine];
            $oldTShift = $state->tShift[$startLine];
            $oldSCount = $state->sCount[$startLine];
            $oldParentType = $state->parentType;

            $posAfterColon = $pos;
            $initial = $offset = $state->sCount[$startLine] + $pos - ($state->bMarks[$startLine] + $state->tShift[$startLine]);

            while ($pos < $max) {
                $ch = $state->src[$pos];

                if ($state->md->utils->isSpace($ch)) {
                    if ($ch === "\t") {
                        $offset += 4 - $offset % 4;
                    } else {
                        $offset++;
                    }
                } else {
                    break;
                }

                $pos++;
            }

            $state->tShift[$startLine] = $pos - $posAfterColon;
            $state->sCount[$startLine] = $offset - $initial;

            $state->bMarks[$startLine] = $posAfterColon;
            $state->blkIndent += 4;
            $state->parentType = 'footnote';

            if ($state->sCount[$startLine] < $state->blkIndent) {
                $state->sCount[$startLine] += $state->blkIndent;
            }

            $state->md->block->tokenize($state, $startLine, $endLine, true);

            $state->parentType = $oldParentType;
            $state->blkIndent -= 4;
            $state->tShift[$startLine] = $oldTShift;
            $state->sCount[$startLine] = $oldSCount;
            $state->bMarks[$startLine] = $oldBMark;

            $token       = $state->createToken('footnote_reference_close', '', -1);
            $token->level = --$state->level;
            $state->tokens[] = $token;

            return true;
        };

        /**
         * Process inline footnotes (^[...])
         * @param StateInline $state
         * @param boolean $silent
         * @return bool
         */
        $footnote_inline = function($state, $silent)
        {

            $max = $state->posMax;
            $start = $state->pos;

            if ($start + 2 >= $max) { return false; }
            if ($state->src[$start] !== '^') { return false; }
            if ($state->src[$start+1] !== '[') { return false; }

            $labelStart = $start + 2;
            $labelEnd = $state->md->helpers->parseLinkLabel($state, $start + 1);

            // parser failed to find ']', so it's not a valid note
            if ($labelEnd < 0) { return false; }

            // We found the end of the link, and know for a fact it's a valid link;
            // so all that's left to do is to call tokenizer.
            //
            if (!$silent) {
                if (!isset($state->env->footnotes)) { $state->env->footnotes = new \stdClass(); }
                if (!isset($state->env->footnotes->list)) { $state->env->footnotes->list = []; }
                $footnoteId = count($state->env->footnotes->list);

                $tokens = [];
                $state->md->inline->parse(
                    substr($state->src, $labelStart, $labelEnd-$labelStart),
                    $state->md,
                    $state->env,
                    $tokens
                );

                $token      = $state->push('footnote_ref', '', 0);
                $token->meta = new \stdClass();
                $token->meta->id = $footnoteId;

                $state->env->footnotes->list[$footnoteId] = new \stdClass();
                $state->env->footnotes->list[$footnoteId]->content =  substr($state->src, $labelStart, $labelEnd);
                $state->env->footnotes->list[$footnoteId]->tokens = $tokens;
            }

            $state->pos = $labelEnd + 1;
            $state->posMax = $max;
            return true;
        };

        /**
         * Process footnote references ([^...])
         * @param StateInline $state
         * @param boolean $silent
         * @return bool
         */
        $footnote_ref = function($state, $silent)
        {
            $max = $state->posMax;
            $start = $state->pos;

            // should be at least 4 chars - "[^x]"
            if ($start + 3 > $max) { return false; }

            if (!isset($state->env->footnotes) || !isset($state->env->footnotes->refs)) { return false; }
            if ($state->src[$start] !== '[') { return false; }
            if ($state->src[$start+1] !== '^') { return false; }

            for ($pos = $start + 2; $pos < $max; $pos++) {
                if ($state->src[$pos] === ' ') { return false; }
                if ($state->src[$pos] === "\n") { return false; }
                if ($state->src[$pos] === ']') {
                    break;
                }
            }

            if ($pos === $start + 2) { return false; } // no empty footnote labels
            if ($pos >= $max) { return false; }
            $pos++;

            $label = substr($state->src, $start + 2, $pos - 1 - ($start + 2));
            if ( !isset($state->env->footnotes->refs[':' . $label]) ) { return false; }

            if (!$silent) {
                if (!isset($state->env->footnotes->list)) { $state->env->footnotes->list = []; }

                if ($state->env->footnotes->refs[':' . $label] < 0) {
                    $footnoteId = count($state->env->footnotes->list);
                    $state->env->footnotes->list[$footnoteId] = new \stdClass();
                    $state->env->footnotes->list[$footnoteId]->label = $label;
                    $state->env->footnotes->list[$footnoteId]->count = 0;
                    $state->env->footnotes->refs[':' . $label] = $footnoteId;
                } else {
                    $footnoteId = $state->env->footnotes->refs[':' . $label];
                }

                $footnoteSubId = $state->env->footnotes->list[$footnoteId]->count;
                $state->env->footnotes->list[$footnoteId]->count++;

                $token      = $state->push('footnote_ref', '', 0);
                $token->meta = new \stdClass();
                $token->meta->id = $footnoteId;
                $token->meta->subId = $footnoteSubId;
                $token->meta->label = $label;
            }

            $state->pos = $pos;
            $state->posMax = $max;
            return true;
        };

        /**
         * Glue footnote tokens to end of token stream
         * @param StateInline $state
         */
        $footnote_tail = function($state) {
            $insideRef = false;
            $refTokens = [];
            if (!isset($state->env->footnotes)) { return; }

            $state->tokens = array_filter($state->tokens, function ($tok) use(&$insideRef, &$refTokens, &$current, &$currentLabel) {
                if ($tok->type === 'footnote_reference_open') {
                    $insideRef = true;
                    $current = [];
                    $currentLabel = $tok->meta->label;
                    return false;
                }
                if ($tok->type === 'footnote_reference_close') {
                    $insideRef = false;
                    // prepend ':' to avoid conflict with Object.prototype members
                    $refTokens[':' . $currentLabel] = $current;
                    return false;
                }
                if ($insideRef) { $current[] =$tok; }
                return !$insideRef;
            });

            if (!isset($state->env->footnotes->list)) { return; }
            $list = &$state->env->footnotes->list;

            $token = $state->createToken('footnote_block_open', '', 1);
            $state->tokens[] = $token;

            for ($i = 0, $l = count($list); $i < $l; $i++) {
                $token      = $state->createToken('footnote_open', '', 1);
                $token->meta = new \stdClass();
                $token->meta->id = $i;
                $token->meta->label =  $list[$i]->label ??  '';
                $state->tokens[] = $token;

                if ( isset($list[$i]->tokens) ) {
                    $tokens = [];

                    $token          = $state->createToken('paragraph_open', 'p', 1);
                    $token->block    = true;
                    $tokens[] = $token;

                    $token          = $state->createToken('inline', '', 0);
                    $token->children = $list[$i]->tokens;
                    $token->content  = $list[$i]->content;
                    $tokens[] = $token;

                    $token          = $state->createToken('paragraph_close', 'p', -1);
                    $token->block   = true;
                    $tokens[] = $token;

                } else if ( isset($list[$i]->label) ) {
                    $tokens = $refTokens[':' . $list[$i]->label];
                }

                $state->tokens = array_merge($state->tokens, $tokens);
                if ($state->tokens[count($state->tokens) - 1]->type === 'paragraph_close') {
                    $lastParagraph = array_pop($state->tokens);
                } else {
                    $lastParagraph = null;
                }

                $t = isset($list[$i]->count) && $list[$i]->count > 0 ? $list[$i]->count : 1;
                for ($j = 0; $j < $t; $j++) {
                    $token      = $state->createToken('footnote_anchor', '', 0);
                    $token->meta = new \stdClass();
                    $token->meta->id = $i;
                    $token->meta->subId = $j;
                    $token->meta->label = $list[$i]->label ?? '';
                    $state->tokens[] = $token;
                }

                if ($lastParagraph) {
                    $state->tokens[] = $lastParagraph;
                }

                $token = $state->createToken('footnote_close', '', -1);
                $state->tokens[] = $token;
            }

            $token = $state->createToken('footnote_block_close', '', -1);
            $state->tokens[] = $token;
        };

        $md->block->ruler->before('reference', 'footnote_def', $footnote_def, [ 'alt'=> [ 'paragraph', 'reference' ] ]);
        $md->inline->ruler->after('image', 'footnote_inline', $footnote_inline);
        $md->inline->ruler->after('footnote_inline', 'footnote_ref', $footnote_ref);
        $md->core->ruler->after('inline', 'footnote_tail', $footnote_tail);
    }
}