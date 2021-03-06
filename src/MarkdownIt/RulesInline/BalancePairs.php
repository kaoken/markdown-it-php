<?php
// For each opening emphasis-like marker find a matching closing one
//
namespace Kaoken\MarkdownIt\RulesInline;


use Kaoken\MarkdownIt\Common\ArrayObj;

class BalancePairs
{
    /**
     * @param StateInline $state
     * @param ArrayObj $delimiters
     */
    private function processDelimiters(StateInline &$state, ArrayObj &$delimiters)
    {
        $openersBottom = [];
        $max = $delimiters->length();

        for ($closerIdx = 0; $closerIdx < $max; $closerIdx++) {
            $closer = $delimiters[$closerIdx];

            // Length is only used for emphasis-specific "rule of 3",
            // if it's not defined (in strikethrough or 3rd party plugins),
            // we can default it to 0 to disable those checks.
            //
            $closer->length = $closer->length ?? 0;

            if (!$closer->close) continue;

            // Previously calculated lower bounds (previous fails)
            // for each marker and each delimiter length modulo 3.
            if (!array_key_exists($closer->marker, $openersBottom)) {
                $openersBottom[$closer->marker] = [ -1, -1, -1 ];
            }

            $minOpenerIdx = $openersBottom[$closer->marker][$closer->length % 3];

            $openerIdx = $closerIdx - $closer->jump - 1;
            // avoid crash if `closer.jump` is pointing outside of the array, see #742
            if ($openerIdx < -1) $openerIdx = -1;
            $newMinOpenerIdx = $openerIdx;

            for (; $openerIdx > $minOpenerIdx; $openerIdx -= $opener->jump + 1) {
                $opener = $delimiters[$openerIdx];

                if ($opener->marker !== $closer->marker) continue;

                if ($opener->open && $opener->end < 0){

                    $isOddMatch = false;

                    // from spec:
                    //
                    // If one of the $delimiters can both open and close emphasis, then the
                    // sum of the lengths of the delimiter runs containing the opening and
                    // closing $delimiters must not be a multiple of 3 unless both lengths
                    // are multiples of 3.
                    //
                    if ($opener->close || $closer->open) {
                        if (($opener->length + $closer->length) % 3 === 0) {
                            if ($opener->length % 3 !== 0 || $closer->length % 3 !== 0) {
                                $isOddMatch = true;
                            }
                        }
                    }

                    if (!$isOddMatch) {
                        // If previous delimiter cannot be an $opener, we can safely skip
                        // the entire sequence in future checks. This is required to make
                        // sure algorithm has linear complexity (see *_*_*_*_*_... case).
                        //
                        $lastJump = $openerIdx > 0 && !$delimiters[$openerIdx - 1]->open ?
                            $delimiters[$openerIdx - 1]->jump + 1 :
                            0;

                        $closer->jump  = $closerIdx - $openerIdx + $lastJump;
                        $closer->open  = false;
                        $opener->end   = $closerIdx;
                        $opener->jump  = $lastJump;
                        $opener->close = false;
                        $newMinOpenerIdx = -1;
                        break;
                    }
                }
            }

            if ($newMinOpenerIdx !== -1) {
                // If match for this delimiter run failed, we want to set lower bound for
                // future lookups. This is required to make sure algorithm has linear
                // complexity.
                //
                // See details here:
                // https://github.com/commonmark/cmark/issues/178#issuecomment-270417442
                //
                $openersBottom[$closer->marker][($closer->length ?? 0) % 3] = $newMinOpenerIdx;
            }
        }
    }

    /**
     * @param StateInline $state
     */
    public function linkPairs(StateInline &$state) {
        $tokens_meta = &$state->tokens_meta;
        $max = $state->tokens_meta->length();

        $this->processDelimiters($state, $state->delimiters);

        for ($curr = 0; $curr < $max; $curr++) {
            if (!is_null($tokens_meta[$curr]) && !is_null($tokens_meta[$curr]->delimiters)) {
                $this->processDelimiters($state, $tokens_meta[$curr]->delimiters);
            }
        }
    }
}