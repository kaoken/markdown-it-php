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
 * use javascript version 1.4.0
 * @see https://github.com/markdown-it/markdown-it-emoji/tree/1.4.0
 */

namespace Kaoken\MarkdownIt\Plugins;

use Kaoken\MarkdownIt\MarkdownIt;
use Kaoken\MarkdownIt\Plugins\Emoji\NormalizeOpts;
use Kaoken\MarkdownIt\Plugins\Emoji\Data\Shortcuts;
use Kaoken\MarkdownIt\Plugins\Emoji\Render;
use Kaoken\MarkdownIt\Plugins\Emoji\Replace;

class MarkdownItEmoji
{
    protected $type;

    public function __construct($type='full')
    {
        $this->type = strtolower($type);
    }

    /**
     * @param MarkdownIt $md
     * @param null|object|array $options
     * @throws \Exception
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

        $md->core->ruler->push('emoji',
            [
                new Replace($md, $opts->defs, $opts->shortcuts, $opts->scanRE, $opts->replaceRE),
                'replace'
            ]
        );
    }
}