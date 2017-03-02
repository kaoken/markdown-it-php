<?php
// GFM table, non-standard

namespace Kaoken\MarkdownIt\RulesBlock;

use Kaoken\MarkdownIt\Common\Utils;

class Table
{
    /**
     * @param StateBlock $state
     * @param integer    $line
     * @return string
     */
    protected function getLine(&$state, $line)
    {
        $pos = $state->bMarks[$line] + $state->blkIndent;
        $max = $state->eMarks[$line];

        return substr($state->src, $pos, $max - $pos);
    }

    /**
     * @param string $str
     * @return array
     */
    protected function escapedSplit($str)
    {
        $result = [];
        $pos = 0;
        $max = strlen($str);
        $escapes = 0;
        $lastPos = 0;
        $backTicked = false;
        $lastBackTick = 0;

        $ch  = $str[$pos];

        while ($pos < $max) {
            if ($ch === '`') {
                if ($backTicked) {
                    // make \` close code sequence, but not open it;
                    // the reason is: `\` is correct code block
                    $backTicked = false;
                    $lastBackTick = $pos;
                } else if ($escapes % 2 === 0) {
                    $backTicked = true;
                    $lastBackTick = $pos;
                }
            } else if ($ch === '|' && ($escapes % 2 === 0) && !$backTicked) {
                $result[] = substr($str, $lastPos, $pos - $lastPos);
                $lastPos = $pos + 1;
            }

            if ($ch === '\\') {
                $escapes++;
            } else {
                $escapes = 0;
            }

            $pos++;

            // If there was an un-closed backtick, go back to just after
            // the last backtick, but as if it was a normal character
            if ($pos === $max && $backTicked) {
                $backTicked = false;
                $pos = $lastBackTick + 1;
            }

            if( $pos >= $max ) break;
            $ch = $str[$pos];
        }

        $result[] = substr($str, $lastPos);

        return $result;
    }


    /**
     * @param StateBlock $state
     * @param integer $startLine
     * @param integer $endLine
     * @param boolean $silent
     * @return bool
     */
    public function set(&$state, $startLine, $endLine, $silent=false)
    {
        // should have at least two lines
        if ($startLine + 2 > $endLine) { return false; }

        $nextLine = $startLine + 1;

        if ($state->sCount[$nextLine] < $state->blkIndent) { return false; }

        // if it's indented more than 3 spaces, it should be a code block
        if ($state->sCount[$nextLine] - $state->blkIndent >= 4) { return false; }

        // first character of the second $line should be '|', '-', ':',
        // and no other characters are allowed but spaces;
        // basically, this is the equivalent of /^[-:|][-:|\s]*$/ regexp

        $pos = $state->bMarks[$nextLine] + $state->tShift[$nextLine];
        if ($pos >= $state->eMarks[$nextLine]) { return false; }

        $ch = $state->src[$pos++];
        if ($ch !== '|' && $ch !== '-' && $ch !== ':') { return false; }

        while ($pos < $state->eMarks[$nextLine]) {
            $ch = $state->src[$pos];

            if ($ch !== '|' && $ch !== '-' && $ch !== ':' && !$state->md->utils->isSpace($ch)) { return false; }

            $pos++;
        }

        $lineText = self::getLine($state, $startLine + 1);

        $columns = explode('|', $lineText);
        $aligns = [];
        for ($i = 0; $i < count($columns); $i++) {
            $t = trim($columns[$i]);
            if (!$t) {
                // allow empty $columns before and after table, but not in between $columns;
                // e.g. allow ` |---| `, disallow ` ---||--- `
                if ($i === 0 || $i === count($columns) - 1) {
                    continue;
                } else {
                    return false;
                }
            }

            if (!preg_match("/^:?-+:?$/", $t)) { return false; }
            if ($t[strlen($t) - 1] === ':') {
                $aligns[] = $t[0] === ':' ? 'center' : 'right';
            } else if ($t[0] === ':') {
                $aligns[] = 'left';
            } else {
                $aligns[] = '';
            }
        }

        $lineText = trim(self::getLine($state, $startLine));
        if ( strpos($lineText, '|') === false) { return false; }
        if ( $state->sCount[$startLine] - $state->blkIndent >= 4) { return false; }
        $columns = self::escapedSplit(preg_replace("/^\||\|$/", '', $lineText));   // /g

        // header row will define an amount of $columns in the entire table,
        // and align row shouldn't be smaller than that (the rest of the rows can)
        $columnCount = count($columns);
        if ($columnCount > count($aligns) ) { return false; }

        if ($silent) { return true; }

        $token     = $state->push('table_open', 'table', 1);
        $token->map = $tableLines = [ $startLine, 0 ];

        $token     = $state->push('thead_open', 'thead', 1);
        $token->map = [ $startLine, $startLine + 1 ];

        $token     = $state->push('tr_open', 'tr', 1);
        $token->map = [ $startLine, $startLine + 1 ];

        for ($i = 0; $i < count($columns); $i++) {
            $token          = $state->push('th_open', 'th', 1);
            $token->map      = [ $startLine, $startLine + 1 ];
            if ( !empty($aligns[$i]) ) {
                $token->attrs  = [ [ 'style', 'text-align:' . $aligns[$i] ] ];
            }

            $token          = $state->push('inline', '', 0);
            $token->content  = trim($columns[$i]);
            $token->map      = [ $startLine, $startLine + 1 ];
            $token->children = [];

            $token          = $state->push('th_close', 'th', -1);
        }

        $token     = $state->push('tr_close', 'tr', -1);
        $token     = $state->push('thead_close', 'thead', -1);

        $token     = $state->push('tbody_open', 'tbody', 1);
        $token->map = $tbodyLines = [ $startLine + 2, 0 ];

        for ($nextLine = $startLine + 2; $nextLine < $endLine; $nextLine++) {
            if ($state->sCount[$nextLine] < $state->blkIndent) { break; }

            $lineText = trim(self::getLine($state, $nextLine));
            if ( strpos($lineText, '|') === false) { break; }
            if ( $state->sCount[$nextLine] - $state->blkIndent >= 4) { break; }
            $columns = self::escapedSplit(preg_replace("/^\||\|$/", '', $lineText));

            $token = $state->push('tr_open', 'tr', 1);
            for ($i = 0; $i < $columnCount; $i++) {
                $token          = $state->push('td_open', 'td', 1);
                if ( !empty($aligns[$i]) ) {
                    $token->attrs  = [ [ 'style', 'text-align:' . $aligns[$i] ] ];
                }

                $token          = $state->push('inline', '', 0);
                $token->content  = isset($columns[$i]) ? trim($columns[$i]) : '';
                $token->children = [];

                $token          = $state->push('td_close', 'td', -1);
            }
            $token = $state->push('tr_close', 'tr', -1);
        }
        $token = $state->push('tbody_close', 'tbody', -1);
        $token = $state->push('table_close', 'table', -1);

        $tableLines[1] = $tbodyLines[1] = $nextLine;
        $state->line = $nextLine;
        return true;
    }
}