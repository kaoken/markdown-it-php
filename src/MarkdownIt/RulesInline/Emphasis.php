<?php
// Process *this* and _that_
//

namespace Kaoken\MarkdownIt\RulesInline;


use Exception;
use Kaoken\MarkdownIt\Common\ArrayObj;

class Emphasis
{
    /**
     * Insert each $marker as a separate text $token, and add it to delimiter list
     * @param StateInline $state
     * @param boolean $silent
     * @return bool
     * @throws Exception
     */
    public function tokenize(&$state, $silent=false)
    {
        $start = $state->pos;
        $marker = $state->src[$start];

        if ($silent) { return false; }

        if ($marker !== '_' && $marker !== '*') { return false; }

        $scanned = $state->scanDelims($state->pos, $marker === '*');

        for ($i = 0; $i < $scanned->length; $i++) {
            $token          = $state->push('text', '', 0);
            $token->content = $marker;

            $state->delimiters[] = (object)[
                // Char code of the starting $marker (number)->
                //
                "marker" => $marker,

                // Total length of these series of $delimiters->
                //
                "length" => $scanned->length,

                // An amount of characters before this one that's equivalent to
                // current one-> In plain English: if this delimiter does not open
                // an emphasis, neither do previous `jump` characters->
                //
                // Used to skip sequences like "*****" in one step, for 1st asterisk
                // value will be 0, for 2nd it's 1 and so on->
                //
                "jump" =>   $i,

                // A position of the $token this delimiter corresponds to->
                //
                "token" =>  count($state->tokens) - 1,

                // If this delimiter is matched as a valid opener, `end` will be
                // equal to its position, otherwise it's `-1`->
                //
                "end" =>    -1,

                // Boolean flags that determine if this delimiter could open or close
                // an emphasis->
                //
                "open" =>   $scanned->can_open,
                "close" =>  $scanned->can_close
            ];
        }

        $state->pos += $scanned->length;

        return true;
    }

    /**
     *
     * @param StateInline $state
     * @param ArrayObj    $delimiters
     */
    private function emphasis(&$state, &$delimiters)
    {
        $max = $delimiters->length();

        for ($i = $max - 1; $i >= 0; $i--) {
            $startDelim = $delimiters[$i];

            if ($startDelim->marker !== '_' && $startDelim->marker !== '*') {
                continue;
            }

            // Process only opening markers
            if ($startDelim->end === -1) {
                continue;
            }

            $endDelim = $delimiters[$startDelim->end];

            // If the previous delimiter has the same marker and is adjacent to this one,
            // merge those into one strong delimiter->
            //
            // `<em><em>whatever</em></em>` -> `<strong>whatever</strong>`
            //
            $isStrong = $i > 0 &&
                $delimiters[$i - 1]->end === $startDelim->end + 1 &&
                $delimiters[$i - 1]->token === $startDelim->token - 1 &&
                $delimiters[$startDelim->end + 1]->token === $endDelim->token + 1 &&
                $delimiters[$i - 1]->marker === $startDelim->marker;

            $ch = $startDelim->marker;

            $token          = &$state->tokens[$startDelim->token];
            $token->type    = $isStrong ? 'strong_open' : 'em_open';
            $token->tag     = $isStrong ? 'strong' : 'em';
            $token->nesting = 1;
            $token->markup  = $isStrong ? $ch . $ch : $ch;
            $token->content = '';

            $token          = &$state->tokens[$endDelim->token];
            $token->type    = $isStrong ? 'strong_close' : 'em_close';
            $token->tag     = $isStrong ? 'strong' : 'em';
            $token->nesting = -1;
            $token->markup  = $isStrong ? $ch . $ch : $ch;
            $token->content = '';

            if ($isStrong) {
                $state->tokens[$delimiters[$i - 1]->token]->content = '';
                $state->tokens[$delimiters[$startDelim->end + 1]->token]->content = '';
                $i--;
            }
        }
    }

    /**
     * Walk through delimiter list and replace text tokens with tags
     * @param StateInline $state
     */
    public function postProcess(&$state)
    {
        $tokens_meta    = &$state->tokens_meta;
        $max            = $state->tokens_meta->length();

        $this->emphasis($state, $state->delimiters);

        for ($curr = 0; $curr < $max; $curr++) {
            if (!is_null($tokens_meta[$curr])  && !is_null($tokens_meta[$curr]->delimiters)) {
                $this->emphasis($state, $tokens_meta[$curr]->delimiters);
            }
        }
    }
}