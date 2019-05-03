<?php

namespace Kaoken\MarkdownIt\Plugins\Emoji;


use Kaoken\MarkdownIt\Token;

class Render
{
    /**
     * @param Token[]  $tokens
     * @param interger $idx
     * @param null $options
     * @param null $env
     * @return string
     */
    function emoji_html(&$tokens, $idx, $options=null, $env=null)
    {
        return $tokens[$idx]->content;
    }

    /**
     * Alternative renderer that uses twemoji images.
     *
     * This can ensure having emojis look exactly the same independent of which
     * device the site is loaded on.
     *
     * @param Token[] $tokens
     * @param integer $idx
     * @param null $options
     * @param null $env
     *
     * @return string
     */
    function twemoji(&$tokens, $idx, $options=null, $env=null)
    {
        $emoji = $tokens[$idx]->content;

        // If this isn't exactly 4 characters long, there's no way it's a valid
        // emoji, so we just return the 'emoji' as is. This serves as a
        // fallback.
        if (strlen($emoji) != 4) {
            return $emoji;
        }

        // Convert the character into it's unicode 'order number' (decimal).
        list(, $ord) = unpack('N', mb_convert_encoding($emoji,
            'UCS-4BE', 'UTF-8'));
        // This decimal value in hex representation gives us the UTF code point.
        $hexCP = dechex($ord);

        // Build the URL that points to the correct emoji image on twemoji.
        $url = 'https://twemoji.maxcdn.com/72x72/' . $hexCP . '.png';

        // Return said image as an image tag, with the actual emoji character as
        // fallack.
        return '<img class="emoji" src="' .$url .
            '" alt="' . $emoji . '"></img>';
    }
}
