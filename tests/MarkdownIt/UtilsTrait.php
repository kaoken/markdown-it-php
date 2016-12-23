<?php
/**
 *
 * @see https://github.com/markdown-it/markdown-it/blob/master/test/utils.js
 */

namespace Kaoken\Test\MarkdownIt;

use Kaoken\MarkdownIt\Common\Utils as U;

trait UtilsTrait
{
    private function utils()
    {
        $this->group('Utils',function($g){
            $this->utilsUtils($g);
        });
    }

    /**
     * @param \Kaoken\Test\GroupTest $g
     */
    private function utilsUtils($g)
    {
        $g->group('fromCodePoint', function ($gg){
            $gg->strictEqual(U::fromCodePoint(0x20), ' ');
            $gg->strictEqual(U::fromCodePoint(0x1F601), '😁');
        });
        //------------------------------------------------------
        $g->group('isValidEntityCode', function ($gg) {
    
            $gg->strictEqual(U::isValidEntityCode(0x20), true);
            $gg->strictEqual(U::isValidEntityCode(0xD800), false);
            $gg->strictEqual(U::isValidEntityCode(0xFDD0), false);
            $gg->strictEqual(U::isValidEntityCode(0x1FFFF), false);
            $gg->strictEqual(U::isValidEntityCode(0x1FFFE), false);
            $gg->strictEqual(U::isValidEntityCode(0x00), false);
            $gg->strictEqual(U::isValidEntityCode(0x0B), false);
            $gg->strictEqual(U::isValidEntityCode(0x0E), false);
            $gg->strictEqual(U::isValidEntityCode(0x7F), false);
        });
        //------------------------------------------------------
        /*$g->group('replaceEntities', function ($gg) {
            $gg->strictEqual(U::replaceEntities('&amp;'), '&');
            $gg->strictEqual(U::replaceEntities('&#32;'), ' ');
            $gg->strictEqual(U::replaceEntities('&#x20;'), ' ');
            $gg->strictEqual(U::replaceEntities('&amp;&amp;'), '&&');
    
            $gg->strictEqual(U::replaceEntities('&am;'), '&am;');
            $gg->strictEqual(U::replaceEntities('&#00;'), '&#00;');
        });*/
        //------------------------------------------------------
        $g->group('assign', function ($gg) {
            $gg->deepEqual(U::assign((object)["a"=>1], null, (object)["b"=>2]), (object)["a"=>1, "b"=>2]);
            $gg->throws(function () {
                U::assign((object)[], 123);
            });
        });
        //------------------------------------------------------
        $g->group('escapeRE', function ($gg) {
            $gg->strictEqual(U::escapeRE(" .?*+^$[]\\\\(){}|-"), " \\.\\?\\*\\+\\^\\$\\[\\]\\\\\\(\\)\\{\\}\\|\\-" );
        });
        //------------------------------------------------------
        $g->group('isWhiteSpace', function ($gg) {
            $gg->strictEqual(U::isWhiteSpace("\x20\x00"), true);
            $gg->strictEqual(U::isWhiteSpace(chr(0x09)), true);
            $gg->strictEqual(U::isWhiteSpace(chr(0x30)), false);
        });
        //------------------------------------------------------
        $g->group('isMdAsciiPunct', function ($gg) {
            $gg->strictEqual(U::isMdAsciiPunct(chr(0x30)), false);

            foreach(str_split("!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~") as $val ){
                $gg->strictEqual(U::isMdAsciiPunct($val), true);
            }
        });
        //------------------------------------------------------
        $g->group('unescapeMd', function ($gg) {
            $gg->strictEqual(U::unescapeMd("\\foo"), "\\foo");
            $gg->strictEqual(U::unescapeMd('foo'), 'foo');

            foreach(str_split("!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~") as $ch ){
                $gg->strictEqual(U::unescapeMd("\\" . $ch), $ch);
            }
        });
    }
}