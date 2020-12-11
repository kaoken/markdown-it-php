<?php
// heading (#, ##, ...)

namespace Kaoken\MarkdownIt\RulesBlock;

use Kaoken\MarkdownIt\Common\Utils;

class Heading
{
    /**
     * @param StateBlock $state
     * @param integer $startLine
     * @param integer $endLine
     * @param boolean $silent
     * @return bool
     */
    public function set(StateBlock &$state, int $startLine, int $endLine, $silent=false): bool
    {
        $pos = $state->bMarks[$startLine] + $state->tShift[$startLine];
        $max = $state->eMarks[$startLine];

        // if it's indented more than 3 spaces, it should be a code block
        if ($state->sCount[$startLine] - $state->blkIndent >= 4) { return false; }

        $ch  = $state->src[$pos];

        if ($ch !== '#' || $pos >= $max) { return false; }

        // count heading $level
        $level = 1;
        $ch = $state->src[++$pos];
        while ($ch === '#' && $pos < $max && $level <= 6) {
            $level++;
            $ch = $state->src[++$pos];
        }

        if ($level > 6 || ($pos < $max && !$state->md->utils->isSpace($ch))) { return false; }

        if ($silent) { return true; }

        // Let's cut tails like '    ###  ' from the end of string

        $max = $state->skipSpacesBack($max, $pos);
        $tmp = $state->skipCharsBack($max, '#', $pos); // #
        if ($tmp > $pos && $state->md->utils->isSpace($state->src[$tmp - 1])) {
            $max = $tmp;
        }

        $state->line = $startLine + 1;

        $token        = $state->push('heading_open', 'h' . (string)($level), 1);
        $token->markup = substr('########', 0, $level);
        $token->map    = [ $startLine, $state->line ];

        $token          = $state->push('inline', '', 0);
        $token->content  = trim(substr($state->src, $pos, $max-$pos));
        $token->map      = [ $startLine, $state->line ];
        $token->children = [];

        $token        = $state->push('heading_close', 'h' . (string)($level), -1);
        $token->markup = substr('########', 0, $level);

        return true;
    }
}