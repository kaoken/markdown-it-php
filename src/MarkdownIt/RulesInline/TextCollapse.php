<?php
// Merge adjacent text nodes into one, and re-calculate all token levels
//

namespace Kaoken\MarkdownIt\RulesInline;

use Kaoken\MarkdownIt\Common\Utils;

class TextCollapse
{
    /**
     * @param StateInline $state
     * @param boolean     $silent
     * @return bool
     */
    public function textCollapse(&$state, $silent=false)
    {
        $level = 0;
        $tokens = &$state->tokens;
        $max = count($state->tokens);

        for ($curr = $last = 0; $curr < $max; $curr++) {
            // re-calculate levels
            $level += $tokens[$curr]->nesting;
            $tokens[$curr]->level = $level;

            if ($tokens[$curr]->type === 'text' &&
                $curr + 1 < $max &&
                $tokens[$curr + 1]->type === 'text'
            ) {

                // collapse two adjacent text nodes
                $tokens[$curr + 1]->content = $tokens[$curr]->content . $tokens[$curr + 1]->content;
            } else {
                if ($curr !== $last) {
                    $tokens[$last] = $tokens[$curr];
                }

                $last++;
            }
        }

        if ($curr !== $last) {
            $state->md->utils->resizeArray($state->tokens, $last);
        }
    }
}