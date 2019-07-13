<?php
// Parse backticks

namespace Kaoken\MarkdownIt\RulesInline;

use Kaoken\MarkdownIt\Common\Utils;

class Backticks
{
    /**
     * @param StateInline $state
     * @param boolean     $silent
     * @return bool
     */
    public function backticks(&$state, $silent)
    {
        $pos = $state->pos;
        $ch = $state->src[$pos];

        if ($ch !== '`') { return false; }

        $start = $pos;
        $pos++;
        $max = $state->posMax;

        while ($pos < $max && $state->src[$pos] === '`') { $pos++; }

        $marker = substr($state->src, $start, $pos-$start);

        $matchStart = $matchEnd = $pos;

        while (($matchStart = strpos($state->src, '`', $matchEnd)) !== false) {
            $matchEnd = $matchStart + 1;

            while ($matchEnd < $max && $state->src[$matchEnd] === '`') { $matchEnd++; }

            if ($matchEnd - $matchStart === strlen($marker)) {
                if (!$silent) {
                    $token          = $state->push('code_inline', 'code', 0);
                    $token->markup  = $marker;
                    $token->content = preg_replace("/\n/", ' ', substr($state->src, $pos, $matchStart-$pos)); // /g
                    $token->content = preg_replace("/^ (.+) $/", "$1", $token->content); // /g
                }
                $state->pos = $matchEnd;
                return true;
            }
        }

        if (!$silent) { $state->pending .= $marker; }
        $state->pos += strlen($marker);
        return true;
    }
}