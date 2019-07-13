<?php
// Parse link destination

namespace Kaoken\MarkdownIt\Helpers;

use Kaoken\MarkdownIt\Common\Utils;

trait ParseLinkDestination
{
    /**
     * ParseLinkDestination constructor.
     * @param string   $str
     * @param integer  $pos
     * @param integer  $max
     * @return object
     */
    public function parseLinkDestination($str, $pos, $max)
    {
        $lines = 0;
        $start = $pos;
        $result = new \stdClass();
        $result->ok = false;
        $result->pos = 0;
        $result->lines = 0;
        $result->str = '';

        if( $pos === $max ) return $result;

        if ($str[$pos] === '<') {
            $pos++;
            while ($pos < $max) {
                $ch= $str[$pos];
                if ($str[$pos] === "\n") { return $result; }
                if ($ch === '>') {
                    $result->pos = $pos + 1;
                    $result->str = $this->utils->unescapeAll(substr($str, $start + 1, $pos-($start+1)));
                    $result->ok = true;
                    return $result;
                }
                if ($ch === '\\' && $pos + 1 < $max) {
                    $pos += 2;
                    continue;
                }

                $pos++;
            }

            // no closing '>'
            return $result;
        }

        // this should be ... } else { ... branch

        $level = 0;
        while ($pos < $max) {
            $ch = $str[$pos];

            if ($ch === ' ') { break; }

            // ascii control characters
            $code = ord($ch);
            if ($code < 0x20 || $code === 0x7F) { break; }

            if ($ch === '\\' && $pos + 1 < $max) {
                $pos += 2;
                continue;
            }

            if ($ch === '(' ) {
                $level++;
            }

            if ($ch === ')' ) {
                if ($level === 0) { break; }
                $level--;
            }

            $pos++;
        }

        if ($start === $pos) { return $result; }
        if ($level !== 0) { return $result; }

        $result->str = $this->utils->unescapeAll(substr($str, $start, $pos-$start));
        $result->lines = $lines;
        $result->pos = $pos;
        $result->ok = true;
        return $result;
    }
}