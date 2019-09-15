<?php
/** internal
 * class ParserInline
 *
 * Tokenizes paragraph content.
 **/
namespace Kaoken\MarkdownIt;

use Kaoken\MarkdownIt\RulesInline;
use Kaoken\MarkdownIt\RulesInline\StateInline;

/**
 * new ParserInline()
 **/
class ParserInline
{
    protected $_rules = [
        [ 'text',            \Kaoken\MarkdownIt\RulesInline\Text::class, 'text' ],
        [ 'newline',         \Kaoken\MarkdownIt\RulesInline\NewLine::class, 'newline'],
        [ 'escape',          \Kaoken\MarkdownIt\RulesInline\Escape::class, 'escape'],
        [ 'backticks',       \Kaoken\MarkdownIt\RulesInline\Backticks::class, 'backticks'],
        [ 'strikethrough',   \Kaoken\MarkdownIt\RulesInline\Strikethrough::class, 'tokenize' ],
        [ 'emphasis',        \Kaoken\MarkdownIt\RulesInline\Emphasis::class, 'tokenize' ],
        [ 'link',            \Kaoken\MarkdownIt\RulesInline\Link::class, 'link' ],
        [ 'image',           \Kaoken\MarkdownIt\RulesInline\Image::class, 'image' ],
        [ 'autolink',        \Kaoken\MarkdownIt\RulesInline\AutoLink::class, 'autoLink' ],
        [ 'html_inline',     \Kaoken\MarkdownIt\RulesInline\HtmlInline::class, 'htmlInline' ],
        [ 'entity',          \Kaoken\MarkdownIt\RulesInline\Entity::class, 'entity' ]
    ];

    protected $_rules2 = [
        [ 'balance_pairs',   \Kaoken\MarkdownIt\RulesInline\BalancePairs::class, 'linkPairs'  ],
        [ 'strikethrough',   \Kaoken\MarkdownIt\RulesInline\Strikethrough::class, 'postProcess' ],
        [ 'emphasis',        \Kaoken\MarkdownIt\RulesInline\Emphasis::class, 'postProcess' ],
        [ 'text_collapse',   \Kaoken\MarkdownIt\RulesInline\TextCollapse::class, 'textCollapse' ],
    ];
    /**
     * @var Ruler
     */
    public $ruler;
    /**
     * @var Ruler
     */
    public $ruler2;

    /**
     * ParserInline constructor.
     * @param string $stateClass
     */
    public function __construct($stateClass='')
    {
        if(class_exists($stateClass))
            $this->stateClass = $stateClass;

        /**
         * ParserInline#ruler -> Ruler
         *
         * [[Ruler]] instance. Keep configuration of inline rules.
         **/
        $this->ruler = new Ruler();
        foreach ( $this->_rules as &$rule) {
            $class = $rule[1];
            $this->ruler->push($rule[0],
                [ new $class(), $rule[2] ]
            );
        }
        unset($this->_rules);

        /**
         * ParserInline#ruler2 -> Ruler
         *
         * [[Ruler]] instance. Second ruler used for post-processing
         * (e.g. in emphasis-like rules).
         **/
        $this->ruler2 = new Ruler();
        foreach ( $this->_rules2 as &$rule) {
            $class = $rule[1];
            $this->ruler2->push($rule[0],
                [ new $class(), $rule[2]]
            );
        }
        unset($this->_rules2);
    }

    /**
     * returns `true` if any rule reported success
     * @param StateInline $state
     */
    public function skipToken(&$state)
    {
        $pos = $state->pos;
        $rules = $this->ruler->getRules('');
        $maxNesting = $state->md->options->maxNesting;
        $cache = &$state->cache;
        $ok = false;

        if ( isset($cache[$pos])) {
            $state->pos = $cache[$pos];
            return;
        }

        if ($state->level < $maxNesting) {
            foreach ($rules as &$rule) {
                // Increment state.level and decrement it later to limit recursion.
                // It's harmless to do here, because no tokens are created. But ideally,
                // we'd need a separate private state variable for this purpose.
                //
                $state->level++;

                if( is_array($rule) )
                    $ok = $rule[0]->{$rule[1]}($state, true);
                else
                    $ok = $rule($state, true);

                $state->level--;

                if ($ok) break;
            }
        } else {
            // Too much nesting, just skip until the end of the paragraph.
            //
            // NOTE: this will cause links to behave incorrectly in the following case,
            //       when an amount of `[` is exactly equal to `maxNesting + 1`:
            //
            //       [[[[[[[[[[[[[[[[[[[[[foo]()
            //
            // TODO: remove this workaround when CM standard will allow nested links
            //       (we can replace it by preventing links from being parsed in
            //       validation mode)
            //
            $state->pos = $state->posMax;
        }

        if (!$ok) { $state->pos++; }
        $cache[$pos] = $state->pos;
    }


    /**
     * Generate tokens for input range
     * @param StateInline $state
     */
    public function tokenize(&$state)
    {
        $rules = $this->ruler->getRules('');
        $len = count($rules);
        $end = $state->posMax;
        $maxNesting = $state->md->options->maxNesting;
        $ok = false;

        $i = 0;
        while ($state->pos < $end) {
            // Try all possible rules.
            // On success, rule should:
            //
            // - update `state.pos`
            // - update `state.tokens`
            // - return true
            if($state->pos == 3){
                $test = 0;
            }else if($state->pos == 17){
                $test = 0;
            }

            if ($state->level < $maxNesting) {
                $i = 0;
                foreach ($rules as &$rule) {
                    $i++;
                    if( is_array($rule) )
                        $ok = $rule[0]->{$rule[1]}($state, false);
                    else
                        $ok = $rule($state, false);
                    if ($ok) { break; }
                }
            }

            if ($ok) {
                if ($state->pos >= $end) { break; }
                continue;
            }

            $state->pending .= $state->src[$state->pos++];
        }

        if ($state->pending) {
            $state->pushPending();
        }
    }


    /**
     * Process input string and push inline tokens into `outTokens`
     *
     * @param string     $str
     * @param MarkdownIt $md
     * @param string     $env
     * @param Token[]    $outTokens
     */
    public function parse($str, $md, $env, &$outTokens)
    {
        if($str === "[__proto__]"){
            $n = "";
        }
        if(preg_match("/link \*foo \*\*bar\*\* `#`\*\]\(\//",$str)){
           $tttt = "";
        }
        $state = new StateInline($str, $md, $env, $outTokens);

        $this->tokenize($state);

        $rules = $this->ruler2->getRules('');

        foreach ( $rules as &$rule) {
            if( is_array($rule) )
                $rule[0]->{$rule[1]}($state);
            else
                $rule($state);
        }
    }
}