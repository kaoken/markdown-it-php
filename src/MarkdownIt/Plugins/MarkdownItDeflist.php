<?php
/**
 * Copyright (c) 2014-2015 Vitaly Puzrin, Alex Kocharin.
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 */
/**
 * Copyright (c) 2016 kaoken
 *
 * This software is released under the MIT License.
 * http://opensource.org/licenses/mit-license.php
 *
 *
 * use javascript version 2.0.3
 * @see https://github.com/markdown-it/markdown-it-deflist/tree/2.0.3
 */
// Process definition lists
//
namespace Kaoken\MarkdownIt\Plugins;

use Exception;
use Kaoken\MarkdownIt\Common\Utils;
use Kaoken\MarkdownIt\MarkdownIt;
use Kaoken\MarkdownIt\RulesBlock\StateBlock;
use Kaoken\MarkdownIt\RulesInline\StateInline;

class MarkdownItDeflist
{
    /**
     * @param MarkdownIt $md
     * @throws Exception
     */
    public function plugin($md)
    {
        $md->block->ruler->before('paragraph', 'deflist', [$this, 'deflist'], [ "alt" => [ 'paragraph', 'reference' ] ]);
    }



    /**
     * Search `[:~][\n ]`, returns next $pos after $marker on success or -1 on fail.
     * @param StateBlock $state
     * @param integer $line
     * @return int
     */
    protected function skipMarker(&$state, $line)
    {
        $start = $state->bMarks[$line] + $state->tShift[$line];
        $max = $state->eMarks[$line];

        if ($start >= $max) { return -1; }

        // Check bullet
        $marker = $state->src[$start++];
        if ($marker !== '~' && $marker !== ':') { return -1; }

        $pos = $state->skipSpaces($start);

        // require space after ":"
        if ($start === $pos) { return -1; }

        // no empty definitions, e.g. "  : "
        if ($pos >= $max) { return -1; }

        return $start;
    }

    /**
     * @param StateInline $state
     * @param integer $idx
     */
    protected function markTightParagraphs(&$state, $idx)
    {
        $level = $state->level + 2;

        for ($i = $idx + 2, $l = count($state->tokens) - 2; $i < $l; $i++) {
            if ($state->tokens[$i]->level === $level && $state->tokens[$i]->type === 'paragraph_open') {
                $state->tokens[$i + 2]->hidden = true;
                $state->tokens[$i]->hidden = true;
                $i += 2;
            }
        }
    }

    /**
     * @param StateBlock $state
     * @param integer $startLine
     * @param integer $endLine
     * @param bool $silent
     * @return bool
     */
    public function deflist($state, $startLine, $endLine=-1, $silent=false)
    {
        if ($silent) {
            // quirk: validation mode validates a dd block only, not a whole deflist
            if ($state->ddIndent < 0) { return false; }
            return $this->skipMarker($state, $startLine) >= 0;
        }

        $nextLine = $startLine + 1;
        if ($nextLine >= $endLine) { return false; }

        if ($state->isEmpty($nextLine)) {
            $nextLine++;
            if ($nextLine >= $endLine) { return false; }
        }

        if ($state->sCount[$nextLine] < $state->blkIndent) { return false; }
        $contentStart = $this->skipMarker($state, $nextLine);
        if ($contentStart < 0) { return false; }

        // Start list
        $listTokIdx = count($state->tokens);
        $tight = true;

        $token     = $state->push('dl_open', 'dl', 1);
        $token->map = $listLines = [ $startLine, 0 ];

        //
        // Iterate list items
        //

        $dtLine = $startLine;
        $ddLine = $nextLine;

        // One definition list can contain multiple DTs,
        // and one DT can be followed by multiple DDs.
        //
        // Thus, there is two loops here, and label is
        // needed to break out of the second one
        //
        /*eslint no-labels:0,block-scoped-var:0*/
        $rootLoop = true;
        OUTER:
        while ($rootLoop) {
            $prevEmptyEnd = false;

            $token          = $state->push('dt_open', 'dt', 1);
            $token->map      = [ $dtLine, $dtLine ];

            $token          = $state->push('inline', '', 0);
            $token->map      = [ $dtLine, $dtLine ];
            $token->content  = trim($state->getLines($dtLine, $dtLine + 1, $state->blkIndent, false));
            $token->children = [];

            $token          = $state->push('dt_close', 'dt', -1);

            while (true) {
                $token     = $state->push('dd_open', 'dd', 1);
                $token->map = $itemLines = [ $nextLine, 0 ];

                $pos = $contentStart;
                $max = $state->eMarks[$ddLine];
                $offset = $state->sCount[$ddLine] + $contentStart - ($state->bMarks[$ddLine] + $state->tShift[$ddLine]);

                while ($pos < $max) {
                    $ch = $state->src[$pos];

                    if ($state->md->utils->isSpace($ch)) {
                        if ($ch === "\t") {
                            $offset += 4 - $offset % 4;
                        } else {
                            $offset++;
                        }
                    } else {
                        break;
                    }

                    $pos++;
                }

                $contentStart = $pos;

                $oldTight = $state->tight;
                $oldDDIndent = $state->ddIndent;
                $oldIndent = $state->blkIndent;
                $oldTShift = $state->tShift[$ddLine];
                $oldSCount = $state->sCount[$ddLine];
                $oldParentType = $state->parentType;
                $state->blkIndent = $state->ddIndent = $state->sCount[$ddLine] + 2;
                $state->tShift[$ddLine] = $contentStart - $state->bMarks[$ddLine];
                $state->sCount[$ddLine] = $offset;
                $state->tight = true;
                $state->parentType = 'deflist';

                $state->md->block->tokenize($state, $ddLine, $endLine, true);

                // If any of list item is $tight, mark list as $tight
                if (!$state->tight || $prevEmptyEnd) {
                    $tight = false;
                }
                // Item become loose if finish with empty $line,
                // but we should filter last element, because it means list finish
                $prevEmptyEnd = ($state->line - $ddLine) > 1 && $state->isEmpty($state->line - 1);

                $state->tShift[$ddLine] = $oldTShift;
                $state->sCount[$ddLine] = $oldSCount;
                $state->tight = $oldTight;
                $state->parentType = $oldParentType;
                $state->blkIndent = $oldIndent;
                $state->ddIndent = $oldDDIndent;

                $token = $state->push('dd_close', 'dd', -1);

                $itemLines[1] = $nextLine = $state->line;


                if ($nextLine >= $endLine) {
                    $rootLoop = false;
                    goto OUTER;
                }

                if ($state->sCount[$nextLine] < $state->blkIndent) {
                    $rootLoop = false;
                    goto OUTER;
                }
                $contentStart = $this->skipMarker($state, $nextLine);
                if ($contentStart < 0) { break; }

                $ddLine = $nextLine;

                // go to the next loop iteration:
                // insert DD tag and repeat checking
            }

            if ($nextLine >= $endLine) { break; }
            $dtLine = $nextLine;

            if ($state->isEmpty($dtLine)) { break; }
            if ($state->sCount[$dtLine] < $state->blkIndent) { break; }

            $ddLine = $dtLine + 1;
            if ($ddLine >= $endLine) { break; }
            if ($state->isEmpty($ddLine)) { $ddLine++; }
            if ($ddLine >= $endLine) { break; }

            if ($state->sCount[$ddLine] < $state->blkIndent) { break; }
            $contentStart = $this->skipMarker($state, $ddLine);
            if ($contentStart < 0) { break; }

            // go to the next loop iteration:
            // insert DT and DD tags and repeat checking
        }

        // Finilize list
        $token = $state->push('dl_close', 'dl', -1);

        $listLines[1] = $nextLine;

        $state->line = $nextLine;

        // mark paragraphs $tight if needed
        if ($tight) {
            $this->markTightParagraphs($state, $listTokIdx);
        }

        return true;
    }
}