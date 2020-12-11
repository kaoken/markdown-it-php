<?php
// Process escaped chars and hardbreaks

namespace Kaoken\MarkdownIt\RulesInline;

use Kaoken\MarkdownIt\Common\Utils;


class Escape
{
    protected array $ESCAPED = [];
    public function __construct()
    {
        $this->ESCAPED = array_fill(0, 256, 0);
        foreach(str_split('\\!"#$%&\'()*+,./:;<=>?@[]^_`{|}~-') as $val){
            $this->ESCAPED[ord($val)] = 1;
        }
    }


    /**
     * @param StateInline $state
     * @param boolean $silent
     * @return bool
     */
    public function escape(StateInline &$state, $silent=false): bool
    {
        $pos = $state->pos;
        $max = $state->posMax;

        if ($state->src[$pos] !== '\\') { return false; }

        $pos++;

        if ($pos < $max) {
            $ch = $state->src[$pos];

            if (($x = ord($ch)) < 256 && $this->ESCAPED[$x] !== 0) {
                if (!$silent) { $state->pending .= $state->src[$pos]; }
                $state->pos += 2;
                return true;
            }

            if ($ch === "\n") {
                if (!$silent) {
                    $state->push('hardbreak', 'br', 0);
                }

                $pos++;
                // skip leading whitespaces from next line
                while ($pos < $max) {
                    $ch = $state->src[$pos];
                    if (!$state->md->utils->isSpace($ch)) { break; }
                    $pos++;
                }

                $state->pos = $pos;
                return true;
            }
        }

        if (!$silent) { $state->pending .= '\\'; }
        $state->pos++;
        return true;
    }
}