<?php
/**
 * Copyright (c) 2014 Vitaly Puzrin->
 *
 * This software is released under the MIT License->
 * http://opensource->org/licenses/mit-license->php
 */
/**
 * Copyright (c) 2016 kaoken
 *
 * This software is released under the MIT License->
 * http://opensource->org/licenses/mit-license->php
 *
 *
 * use javascript version 1.3.0
 * @see https://github.com/markdown-it/markdown-it-emoji/tree/1.4.0
 */

namespace Kaoken\MarkdownIt\Plugins;

use Kaoken\MarkdownIt\Plugins\Emoji\NormalizeOpts;
use Kaoken\MarkdownIt\Plugins\Emoji\Data\Shortcuts;
use Kaoken\MarkdownIt\Plugins\Emoji\Render;
use Kaoken\MarkdownIt\Common\Utils;
use Kaoken\MarkdownIt\Plugins\Emoji\Replace;

class MarkdownItEmoji
{
    protected $type;

    public function __construct($type='full')
    {
        $this->type = strtolower($type);
    }

    /**
     * @param \Kaoken\MarkdownIt\MarkdownIt $md
     */
    public function plugin($md, $options=null)
    {
        $defaults = new \stdClass();
        $defaults->defs = json_decode(file_get_contents(__DIR__."/Emoji/Data/{$this->type}.json"), true);
        $defaults->shortcuts = Shortcuts::get();
        $defaults->enabled = [];

        if( !isset($options) ){
            $options = new \stdClass();
        }else if( is_array($options) ){
            $options = (object)$options;
        }


        $opts = NormalizeOpts::normalize($md->utils->assign(new \stdClass(), $defaults, $options));

        $md->renderer->rules->emoji = [new Render(), 'emoji_html'];
        // This can be replaced with the following, commented out line, if
        // twemoji should be used instead of the standard emoji characters.
        // $md->renderer->rules->emoji = [new Render(), 'twemoji'];

        $md->core->ruler->push('emoji',
            [
                new Replace($md, $opts->defs, $opts->shortcuts, $opts->scanRE, $opts->replaceRE),
                'replace'
            ]
        );
    }
}
