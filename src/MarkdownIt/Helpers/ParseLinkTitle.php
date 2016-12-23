<?php
// Parse link title
//

namespace Kaoken\MarkdownIt\Helpers;

use Kaoken\MarkdownIt\Common\Utils;

trait ParseLinkTitle
{
    public function parseLinkTitle($str, $pos, $max)
    {
        $lines = 0;
        $start = $pos;
        $result = new \stdClass();
        $result->ok = false;
        $result->pos = 0;
        $result->lines = 0;
        $result->str = '';


        if ($pos >= $max) {
            return $result;
        }

        $marker = $str[$pos];

        if ($marker !== '"' && $marker !== '\'' && $marker !== '(' ) {
            return $result;
        }

        $pos++;

        // if opening marker is "(", switch it to closing marker ")"
        if ($marker === '(') {
            $marker = ')';
        }

        while ($pos < $max) {
            $code = $str[$pos];
            if ($code === $marker) {
                $result->pos = $pos + 1;
                $result->lines = $lines;
                $result->str = $this->utils->unescapeAll(substr($str, $start + 1, $pos-($start+1)));
                $result->ok = true;
                return $result;
            } else if ($code === "\n") {
                $lines++;
            } else if ($code === '\\' && $pos + 1 < $max) {
                $pos++;
                if ($str[$pos] === "\n") {
                    $lines++;
                }
            }
            $pos++;
        }
        return $result;
    }
}