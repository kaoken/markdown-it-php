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
    public function escape(StateInline &$state, bool $silent=false): bool
    {
        $pos = $state->pos;
        $max = $state->posMax;

        if ($state->src[$pos] !== '\\') return false;

        $pos++;

        // '\' at the end of the inline block
        if ($pos >= $max) return false;
        $ch1 = $state->src[$pos];

        if ($ch1 === "\n") {
            if (!$silent) {
                $state->push('hardbreak', 'br', 0);
            }

            $pos++;
            // skip leading whitespaces from next line
            while ($pos < $max) {
                $ch1 = $state->src[$pos];
                if (!$state->md->utils->isSpace($ch1)) break;
                $pos++;
            }

            $state->pos = $pos;
            return true;
        }
        $escapedStr = $state->src[$pos];


//        The following is converted for PHP
//        ----------------------------------------
//        if (ch1 >= 0xD800 && ch1 <= 0xDBFF && pos + 1 < max) {
//            ch2 = state.src.charCodeAt(pos + 1);
//
//            if (ch2 >= 0xDC00 && ch2 <= 0xDFFF) {
//                escapedStr += state.src[pos + 1];
//                pos++;
//            }
//        }
        $aBytes[] = ord($state->src[$pos]);
        $byteCount = $state->md->utils->getByteCountUtf8($aBytes[0]);
        if($byteCount > 1){
            for($i=$pos;$i<$byteCount-1 && $pos + $byteCount-1 < $max;$i++){
                $charCode = ord($state->src[$i]);
                if($charCode < 0x90 || 0xBF < $charCode){
                    break;
                }
                $aBytes[] = $charCode;
            }
            if(count($aBytes) === $byteCount){
                for($i=1;$i<count($aBytes);$i++){
                    $escapedStr .= $state->src[$pos + $i];
                }
                $pos += $byteCount-1;
            }
        }

        $origStr = '\\' . $escapedStr;

        if (!$silent) {
            $token = $state->push('text_special', '', 0);

            if (ord($ch1) < 256 && $this->ESCAPED[ord($ch1)] !== 0) {
                $token->content = $escapedStr;
            } else {
                $token->content = $origStr;
            }

            $token->markup = $origStr;
            $token->info   = 'escape';
        }

        $state->pos = $pos + 1;
        return true;
    }
}