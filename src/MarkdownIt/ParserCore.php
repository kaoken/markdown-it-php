<?php
/** internal
 * class Core
 *
 * Top-level rules executor. Glues block/inline parsers and does intermediate
 * transformations.
 **/

namespace Kaoken\MarkdownIt;
use Kaoken\MarkdownIt\Ruler;
use \Kaoken\MarkdownIt\RulesCore\StateCore;

class ParserCore
{
    protected $_rules = [
        'normalize' =>     \Kaoken\MarkdownIt\RulesCore\Normalize::class,
        'block' =>         \Kaoken\MarkdownIt\RulesCore\Block::class,
        'inline' =>        \Kaoken\MarkdownIt\RulesCore\Inline::class,
        'linkify' =>       \Kaoken\MarkdownIt\RulesCore\Linkify::class,
        'replacements' =>  \Kaoken\MarkdownIt\RulesCore\ReplaceMents::class,
        'smartquotes' =>   \Kaoken\MarkdownIt\RulesCore\SmartQuotes::class
    ];

    /**
     * [[Ruler]] instance. Keep configuration of core rules.
     * @var Kaoken\MarkdownIt\Ruler
     */
    public $ruler;

    /**
     * new Core()
     **/
    public function __construct()
    {
        $this->ruler = new Ruler();

        foreach ($this->_rules as $key => &$val) {
            $this->ruler->push($key, [new $val(), 'set']);
        }
    }

    /**
     * @param string $src
     * @param \Kaoken\MarkdownIt\MarkdownIt $md
     * @param object $env
     * @return \Kaoken\MarkdownIt\RulesCore\StateCore
     */
    public function createState($src, $md, $env=null)
    {
        return new StateCore($src, $md, $env);
    }

    /**
     * @param object $state
     */
    public function process($state)
    {
        $rules = $this->ruler->getRules('');

        foreach ( $rules as &$rule) {
            $rule($state);
        }
    }
}