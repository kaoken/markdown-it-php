<?php
// Process links like https://example.org/
namespace Kaoken\MarkdownIt\RulesInline;

use Kaoken\MarkdownIt\RulesInline\StateInline;

class Linkify
{
    // RFC3986: scheme = ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )
    const SCHEME_RE = "/(?:^|[^a-z0-9.+-])([a-z][a-z0-9.+-]*)$/i";


    /**
     * @param StateInline $state
     * @param bool $silent
     * @return bool
     */
    public function linkify(StateInline &$state, bool $silent = false): bool
    {        
        if (! $state->md->options->linkify) return false;
        if ( $state->linkLevel > 0) return false;

        $pos =  $state->pos;
        $max =  $state->posMax;
        
        if ( $pos + 3 >  $max) return false;
        if ( $state->src[$pos] !== ':' /* 0x3A */) return false;
        if ( $state->src[$pos + 1] !== '/' /* 0x2F */) return false;
        if ( $state->src[$pos + 2] !== '/' /* 0x2F */) return false;

        if (! preg_match(self::SCHEME_RE, $state->pending,$match)) return false;
        
        $proto = $match[1];

        $link = $state->md->linkify->matchAtStart( substr($state->src, $pos -  strlen($proto)));
        if (! $link) return false;
        
        $url =  $link->url;
        
        // disallow '*' at the end of the  $link (conflicts with emphasis)
        $url =  preg_replace("/\*+$/", '', $url);
        
        $fullUrl =  $state->md->normalizeLink( $url);
        if (! $state->md->validateLink( $fullUrl)) return false;
        
        if (!$silent) {
         $state->pending =  substr($state->pending,0, strlen($state->pending) - strlen($proto));
        
         $token          =  $state->push('link_open', 'a', 1);
         $token->attrs   = [ [ 'href',  $fullUrl ] ];
         $token->markup  = 'linkify';
         $token->info    = 'auto';
        
         $token          =  $state->push('text', '', 0);
         $token->content =  $state->md->normalizeLinkText( $url);
        
         $token          =  $state->push('link_close', 'a', -1);
         $token->markup  = 'linkify';
         $token->info    = 'auto';
        }
        
        $state->pos +=  strlen($url) -  strlen($proto);
        return true;
    }
}