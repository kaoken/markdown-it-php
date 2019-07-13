<?php
// Process html entity - &#123;, &#xAF;, &quot;, ...

namespace Kaoken\MarkdownIt\RulesInline;

use Kaoken\MarkdownIt\Common\Utils;

class Entity
{
    /**
     * @param StateInline $state
     * @param boolean     $silent
     * @return bool
     */
    public function entity(&$state, $silent=false)
    {
        $pos = $state->pos;
        $max = $state->posMax;

        if ($state->src[$pos] !== '&') { return false; }

        if ($pos + 1 < $max) {
            $ch = $state->src[$pos + 1];

            if ($ch === '#') {
                if (preg_match("/^&#((?:x[a-f0-9]{1,6}|[0-9]{1,7}));/i", substr($state->src,$pos), $match)) {
                    if (!$silent) {
                        $code = strtolower($match[1][0]) === 'x' ? intval(substr($match[1],1),16) : intval($match[1], 10);
                        $state->pending .= $state->md->utils->isValidEntityCode($code) ? $state->md->utils->fromCodePoint($code) : $state->md->utils->fromCodePoint(0xFFFD);
                    }
                    $state->pos += strlen($match[0]);
                    return true;
                }
            } else {
                if (preg_match("/^(&[a-z][a-z0-9]{1,31};)/i", substr($state->src, $pos), $match)) {
                    $decode = html_entity_decode($match[1], ENT_COMPAT|ENT_HTML5);
                    if ($decode !== $match[1]) {
                        if (!$silent) { $state->pending .= $decode; }
                        $state->pos += strlen($match[0]);
                        return true;
                    }
                }
            }
        }

        if (!$silent) { $state->pending .= '&'; }
        $state->pos++;
        return true;
    }
}