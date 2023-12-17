<?php
// Commonmark default options
namespace Kaoken\MarkdownIt\Presets;


class CommonMark
{
    /**
     * @return object
     */
    public static function get(): object
    {
        $o = [
            "options" => [
                // Enable HTML tags in source
                "html" =>         true,

                // Use '/' to close single tags (<br />)
                "xhtmlOut" =>     true,

                // Convert '\n' in paragraphs into <br>
                "breaks" =>       false,

                // CSS language prefix for fenced blocks
                "langPrefix" =>   'language-',

                // autoconvert URL-like texts to links
                "linkify" =>      false,

                // Enable some language-neutral replacements + quotes beautification
                "typographer" =>  false,

                // Double + single quotes replacement pairs, when typographer enabled,
                // and smartquotes on. Could be either a String or an Array.
                //
                // For example, you can use '«»„“' for Russian, '„“‚‘' for German,
                // and ['«\xA0', '\xA0»', '‹\xA0', '\xA0›'] for French (including nbsp).
                "quotes" => "“”‘’", /* “”‘’ */

                // Highlighter function. Should return escaped HTML,
                // or '' if the source string is not changed and should be escaped externaly.
                // If result starts with <pre... internal wrapper is skipped.
                //
                // function (/*str, lang*/) { return ''; }
                //
                "highlight" => null,

                // Internal protection, recursion limit
                "maxNesting" =>   20
            ],

            "components" => [

                "core" => [
                    "rules" => [
                        'normalize',
                        'block',
                        'inline',
                        'text_join'
                    ]
                ],

                "block" => [
                    "rules" => [
                        'blockquote',
                        'code',
                        'fence',
                        'heading',
                        'hr',
                        'html_block',
                        'lheading',
                        'list',
                        'reference',
                        'paragraph'
                    ]
                ],

                "inline" => [
                    "rules" => [
                        'autolink',
                        'backticks',
                        'emphasis',
                        'entity',
                        'escape',
                        'html_inline',
                        'image',
                        'link',
                        'newline',
                        'text'
                    ],
                    "rules2" => [
                        'balance_pairs',
                        'emphasis',
                        'fragments_join'
                    ]
                ]
            ]
        ];
        return json_decode(json_encode($o));
    }
}