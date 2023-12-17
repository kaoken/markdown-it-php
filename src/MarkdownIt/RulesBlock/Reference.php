<?php
namespace Kaoken\MarkdownIt\RulesBlock;
use Kaoken\MarkdownIt\Common\Utils;


class Reference
{
    /**
     * @param StateBlock $state
     * @param integer $startLine
     * @param integer $endLine
     * @param boolean $silent
     * @return bool
     */
    public function set(StateBlock &$state, int $startLine, int $endLine, bool $silent=false): bool
    {
        $pos = $state->bMarks[$startLine] + $state->tShift[$startLine];
        $max = $state->eMarks[$startLine];
        $nextLine = $startLine + 1;

        // if it's indented more than 3 spaces, it should be a code block
        if ($state->sCount[$startLine] - $state->blkIndent >= 4) { return false; }

        if ($state->src[$pos] !== '[') { return false; }

        // Simple check to quickly interrupt scan on [link](url) at the $start of line.
        // Can be useful on practice: https://github.com/markdown-it/markdown-it/issues/54
        while (++$pos < $max) {
            if ($state->src[$pos] === ']' &&
                $state->src[$pos - 1] !== '\\') {
                if ($pos + 1 === $max) { return false; }
                if ($state->src[$pos + 1] !== ':') { return false; }
                break;
            }
        }

        $endLine = $state->lineMax;

        // jump line-by-line until empty one or EOF
        $terminatorRules = $state->md->block->ruler->getRules('reference');

        $oldParentType = $state->parentType;
        $state->parentType = 'reference';

        for (; $nextLine < $endLine && !$state->isEmpty($nextLine); $nextLine++) {
            // this would be a code block normally, but after paragraph
            // it's considered a lazy continuation regardless of what's there
            if ($state->sCount[$nextLine] - $state->blkIndent > 3) { continue; }

            // quirk for blockquotes, this line should already be checked by that rule
            if ($state->sCount[$nextLine] < 0) { continue; }

            // Some tags can $terminate paragraph without empty line.
            $terminate = false;
            foreach( $terminatorRules as &$rule ){
                if( is_array($rule) ){
                    if ($rule[0]->{$rule[1]}($state, $nextLine, $endLine, true)) {
                        $terminate = true;
                        break;
                    }
                }else if( is_callable($rule) ){
                    if ($rule($state, $nextLine, $endLine, true)) {
                        $terminate = true;
                        break;
                    }
                }
            }

            if ($terminate) { break; }
        }

        $str = trim($state->getLines($startLine, $nextLine, $state->blkIndent, false));
        $max = strlen($str);
        $lines = 0;
        $labelEnd = -1;
        for ($pos = 1; $pos < $max; $pos++) {
            $ch = $str[$pos];
            if ($ch === '[') {
                return false;
            } else if ($ch === ']') {
                $labelEnd = $pos;
                break;
            } else if ($ch === "\n") {
                $lines++;
            } else if ($ch === '\\') {
                $pos++;
                if ($pos < $max && $str[$pos] === "\n") {
                    $lines++;
                }
            }
        }

        if ($labelEnd < 0 || $labelEnd+1 >= $max ) return false;
        if ( $str[$labelEnd + 1] !== ':') return false;

        // [$label]:   destination   'title'
        //         ^^^ skip optional whitespace here
        for ($pos = $labelEnd + 2; $pos < $max; $pos++) {
            $ch = $str[$pos];
            if ($ch === "\n") {
                $lines++;
            } else if ($state->md->utils->isSpace($ch)) {
                /*eslint no-empty:0*/
            } else {
                break;
            }
        }

        // [$label]:   destination   'title'
        //            ^^^^^^^^^^^ parse this
        $res = $state->md->helpers->parseLinkDestination($str, $pos, $max);
        if (!$res->ok) { return false; }

        $href = $state->md->normalizeLink($res->str);
        if (!$state->md->validateLink($href)) { return false; }

        $pos = $res->pos;
        $lines += $res->lines;

        // save cursor $state, we could require to rollback later
        $destEndPos = $pos;
        $destEndLineNo = $lines;

        // [$label]:   destination   'title'
        //                       ^^^ skipping those spaces
        $start = $pos;
        for (; $pos < $max; $pos++) {
            $ch = $str[$pos];
            if ($ch === "\n") {
                $lines++;
            } else if ($state->md->utils->isSpace($ch)) {
                /*eslint no-empty:0*/
            } else {
                break;
            }
        }

        // [$label]:   destination   'title'
        //                          ^^^^^^^ parse this
        $res = $state->md->helpers->parseLinkTitle($str, $pos, $max);
        if ($pos < $max && $start !== $pos && $res->ok) {
            $title = $res->str;
            $pos = $res->pos;
            $lines += $res->lines;
        } else {
            $title = '';
            $pos = $destEndPos;
            $lines = $destEndLineNo;
        }

        // skip trailing spaces until the rest of the line
        while ($pos < $max) {
            $ch = $str[$pos];
            if (!$state->md->utils->isSpace($ch)) { break; }
            $pos++;
        }

        if ($pos < $max && $str[$pos] !== "\n") {
            if ($title) {
                // garbage at the end of the line after $title,
                // but it could still be a valid reference if we roll back
                $title = '';
                $pos = $destEndPos;
                $lines = $destEndLineNo;
                while ($pos < $max) {
                    $ch = $str[$pos];
                    if (!$state->md->utils->isSpace($ch)) { break; }
                    $pos++;
                }
            }
        }

        if ($pos < $max && $str[$pos] !== "\n") {
            // garbage at the end of the line
            return false;
        }

        $label = $state->md->utils->normalizeReference(substr($str, 1, $labelEnd-1));
        if (!$label) {
            // CommonMark 0.20 disallows empty labels
            return false;
        }

        // Reference can not $terminate anything. This check is for safety only.
        /*istanbul ignore if*/
        if ($silent) { return true; }

        if ( !isset($state->env->references) ) {
            $state->env->references = [];
        }
        if ( !isset($state->env->references[$label]) ) {
            $state->env->references[$label] = [];
            $obj = &$state->env->references[$label];
            $obj['title'] = $title;
            $obj['href'] = $href;
        }

        $state->parentType = $oldParentType;

        $state->line = $startLine + $lines + 1;
        return true;
    }
}