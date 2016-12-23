<?php
// Process autolinks '<protocol:...>'

namespace Kaoken\MarkdownIt\RulesInline;
use Kaoken\MarkdownIt\Common\Utils;


class AutoLink
{
    const EMAIL_RE    = "/^<([a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*)>/u";
    const AUTOLINK_RE = "/^<([a-zA-Z][a-zA-Z0-9+.\-]{1,31}):([^<>\x{00}-\x{20}]*)>/u";

    /**
     * @param StateInline $state
     * @param boolean     $silent
     * @return bool
     */
    public function autoLink(&$state, $silent=false)
    {
        $pos = $state->pos;

        if ($state->src[$pos] !== '<') { return false; }

        $tail = substr($state->src, $pos);

        if ( strpos($tail, '>') < 0) { return false; }

        if (preg_match(self::AUTOLINK_RE, $tail, $linkMatch)) {
            $url = substr($linkMatch[0], 1, -1);
            $fullUrl = $state->md->normalizeLink($url);
            if (!$state->md->validateLink($fullUrl)) { return false; }

            if (!$silent) {
                $token         = $state->push('link_open', 'a', 1);
                $token->attrs   = [ [ 'href', $fullUrl ] ];
                $token->markup  = 'autolink';
                $token->info    = 'auto';

                $token         = $state->push('text', '', 0);
                $token->content = $state->md->normalizeLinkText($url);

                $token         = $state->push('link_close', 'a', -1);
                $token->markup  = 'autolink';
                $token->info    = 'auto';
            }

            $state->pos += strlen($linkMatch[0]);
            return true;
        }

        if (preg_match(self::EMAIL_RE, $tail, $emailMatch)) {
            $url = substr($emailMatch[0], 1, -1);
            $fullUrl = $state->md->normalizeLink('mailto:' . $url);
            if (!$state->md->validateLink($fullUrl)) { return false; }

            if (!$silent) {
                $token         = $state->push('link_open', 'a', 1);
                $token->attrs   = [ [ 'href', $fullUrl ] ];
                $token->markup  = 'autolink';
                $token->info    = 'auto';

                $token         = $state->push('text', '', 0);
                $token->content = $state->md->normalizeLinkText($url);

                $token         = $state->push('link_close', 'a', -1);
                $token->markup  = 'autolink';
                $token->info    = 'auto';
            }

            $state->pos += strlen($emailMatch[0]);
            return true;
        }

        return false;
    }
}