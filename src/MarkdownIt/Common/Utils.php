<?php
// Utilities
//

namespace Kaoken\MarkdownIt\Common;


use Exception;
use stdClass;

class Utils
{
    const UNESCAPE_MD = "\\\\([!\"#$%&'()*+,\-\.\/:;<=>?@[\\\\\]^_`{\|}~])";
    const UNESCAPE_MD_RE  = "/".self::UNESCAPE_MD."/";  // /g
    const ENTITY_RE       = '/&([a-z#][a-z0-9]{1,31});/i';  // /g
    const UNESCAPE_ALL_RE = "/".self::UNESCAPE_MD."|&([a-z#][a-z0-9]{1,31});/i";  // /g
    const DIGITAL_ENTITY_TEST_RE = "/^#((?:x[a-f0-9]{1,8}|[0-9]{1,8}))$/i";

    protected static ?Utils $instance=null;

    /**
     * @return Utils
     */
    public static function getInstance(): ?Utils
    {
        if( self::$instance === null ) self::$instance = new Utils();
        return self::$instance;
    }

    /**
     * If matches are found, the new $str will be returned, otherwise $str will be returned unchanged or NULL if an error occurred.
     * @param string $str
     * @return string|null
     */
    public function unescapeMd($str): ?string
    {
        if (strpos ($str,'\\') === false) return $str;
        return preg_replace(self::UNESCAPE_MD_RE, '$1', $str);
    }

    /**
     * @param string $match
     * @param string|null $name
     * @return string
     */
    public function replaceEntityPattern(string $match, ?string $name): string
    {
        if (isset($name)) {
            $e = html_entity_decode('&'.$name.';', ENT_HTML5|ENT_COMPAT);
            if( '&'.$name.';' != $e) return $e;
        }

        if ($name[0] === '#' && preg_match(self::DIGITAL_ENTITY_TEST_RE, $name)) {
            $code = strtolower($name[1]) === 'x' ?
                intval(substr($name,2), 16)
                :
                intval(substr($name,1), 10);
            if (self::isValidEntityCode($code)) {
                return self::fromCodePoint($code);
            }
        }

        return $match;
    }

    /**
     * @param string $str
     * @return string
     */
    public function unescapeAll(string $str): string
    {
        if (strpos($str, '\\') === false && strpos($str, '&') === false) { return $str; }

        return preg_replace_callback(self::UNESCAPE_ALL_RE, function ($match) {
                if ($match[1]) { return $match[1]; }
                return self::replaceEntityPattern($match[0], $match[2]);
            },$str);
    }


    /**
     * @param array $a
     * @param integer $size
     * @param mixed $val
     */
    public function resizeArray(array &$a, int $size, $val=null)
    {
        $l = count($a);
        if( $size > $l ){
            $size -= $l;
            do{ $a[] = $val; } while(--$size > 0);
        }else if( $size < $l ){
            array_splice($a, $size);
        }
    }

    //
    //
    /**
     * Merge objects
     * @param stdClass $obj
     * @param mixed ...$args
     * @return stdClass|array
     * @throws Exception
     */
    public function assign(stdClass $obj, ...$args)
    {
        if(is_object($obj)){
            foreach($args as &$source){
                if (!isset($source)) continue;

                if ( !is_object($source) ) {
                    throw new Exception($source . 'must be object');
                }
                foreach($source as $key=>&$val){
                    $obj->{$key} = $source->{$key};
                }
            }
        }else{
            throw new Exception("Utlis::assign() parameter 1 by not object");
        }

        return $obj;
    }

    /**
     * Remove element from array and put another array at those position.
     * Useful for some operations with tokens
     * @param array $src
     * @param integer $pos
     * @param array $newElements
     * @return array
     */
    public function arrayReplaceAt(array $src, int $pos, array $newElements): array
    {
        if(empty($newElements))$newElements=[];
        return array_merge(array_slice($src, 0, $pos), $newElements, array_slice($src, $pos + 1));
    }

    /**
     * @param int[]|string[] ...$args intrger|string array
     * @return string
     */
    public function fromCharCode(...$args): string
    {
        $output = '';
        foreach($args as $char){
            if(is_string($char)){
                $output .= $char;
            }else if(is_int($char)){
                $output .= chr($char);
            }
        }
        return $output;
    }

    /**
     * @param integer $c
     * @return bool
     */
    public function isValidEntityCode($c): bool
    {
        /*eslint no-bitwise:0*/
        // broken sequence
        if ($c >= 0xD800 && $c <= 0xDFFF) { return false; }
        // never used
        if ($c >= 0xFDD0 && $c <= 0xFDEF) { return false; }
        if (($c & 0xFFFF) === 0xFFFF || ($c & 0xFFFF) === 0xFFFE) { return false; }
        // control codes
        if ($c >= 0x00 && $c <= 0x08) { return false; }
        if ($c === 0x0B) { return false; }
        if ($c >= 0x0E && $c <= 0x1F) { return false; }
        if ($c >= 0x7F && $c <= 0x9F) { return false; }
        // out of range
        if ($c > 0x10FFFF) { return false; }
        return true;
    }

    /**
     * UTF16 -> UTF8
     * @param integer $c
     * @return string
     */
    public function fromCodePoint(int $c): string
    {
        if ($c < 0x7F) // U+0000-U+007F - 1 byte
            return chr($c);
        if ($c < 0x7FF) // U+0080-U+07FF - 2 bytes
            return chr(0xC0 | ($c >> 6)) . chr(0x80 | ($c & 0x3F));
        if ($c < 0xFFFF) // U+0800-U+FFFF - 3 bytes
            return chr(0xE0 | ($c >> 12)) . chr(0x80 | (($c >> 6) & 0x3F)) . chr(0x80 | ($c & 0x3F));
        // U+010000-U+10FFFF - 4 bytes
        return chr(0xF0 | ($c >> 18)) . chr(0x80 | ($c >> 12) & 0x3F) .chr(0x80 | (($c >> 6) & 0x3F)) . chr(0x80 | ($c & 0x3F));
    }

    /**
     * @param int $n
     * @return int
     */
    public function getByteCountUtf8(int $n): int
    {
        if (0 <= $n && $n <= 0x7F) {
            return 1;
        }
        else if (0xC2 <= $n && $n <= 0xDF) {
            return 2;
        }
        else if (0xE0 <= $n && $n <= 0xEF) {
            return 3;
        }
        else if (0xF0 <= $n && $n <= 0xF7) {
            return 4;
        }
        else if(0xF8 <= $n && $n <= 0xFB ){
            return 5;
        }
        else if(0xFC <= $n && $n <= 0xFD ){
            return 6;
        }
        return 0;
    }
    /**
     * Acquire the character one last the $pos position.
     * simple and easy utf8 check.
     * @param string $text Only string variable
     * @param integer $pos The current starting position.
     * @param integer $outOffset If the last character is found, its position is substituted.
     * If not found, -1 is substituted.
     * @return string If found, it returns the last. If you can not find, it returns an empty('').
     */
    public function lastCharUTF8(string &$text, int $pos, int &$outOffset): string
    {
        $chars = mb_str_split($text);
        $idx = 0;
        foreach($chars as $c){
            $nextIdx = $idx + strlen($c);
            if($nextIdx  >= $pos){
                $outOffset = $idx;
                return $c;
            }
            $idx = $nextIdx ;
        }
        return "";
    }

    /**
     * Get the character at the current position '$pos'.
     * simple and easy utf8 check.
     * @param string $text Only string variable
     * @param integer $pos The current starting position.
     * @param null|int $outLen The length of the string is substituted.
     * If not found, -1 is substituted.
     * @return If found, Returns a 1 character.
     */
    public function currentCharUTF8(string &$text, int $pos, ?int &$outLen)
    {
        $max = strlen($text);
        $startPos = $pos;
        $outLen = 1;

        $str = $text[$pos];
        $code = ord($text[$pos]);
        $t = $code & 0xC0;
        if( $code < 0x80 ){
            return $str;
        }else if( $t !== 0xC0 || ($pos+1) == $max){
            // error
            return $str;
        }

        do{
            $code = ord($text[++$pos]);
            $t = $code & 0xC0;
            if( $t === 0xC0 || $t < 0x80 || ($pos+1) == $max){
                $outLen = $pos - $startPos + 1;
                return $str;
            }
            $str .= $text[$pos];
        }while($pos<$max);
        $outLen = $max - $startPos;
        return -1;
    }


    const REGEXP_ESCAPE_RE = "/[.?*+^$[\]\\(){}|-]/"; // /g

    /**
     * @param string $str
     * @return string|null
     * If matches are found, the new $str will be returned, otherwise $str will be returned unchanged or NULL if an error occurred.
     */
    public function escapeRE(string $str): ?string
    {
        return preg_replace(self::REGEXP_ESCAPE_RE, '\\\\$0', $str);
    }


    /**
     * @param string $ch
     * @return bool
     */
    public function isSpace(string $ch): bool
    {
        return $ch == "\t" || $ch == ' ';
    }

    /**
     * Zs (unicode class) || [\t\f\v\r\n]
     * @param string $code
     * @return bool
     */
    public function isWhiteSpace(string $code): bool
    {
        return preg_match("/\p{Zs}|[\t\f\v\r\n]/u", $code) === 1;
    }


    /*eslint-disable max-len*/

    // /[!-#%-\*,-/:;\?@\[-\]_\{\}\xA1\xA7\xAB\xB6\xB7\xBB\xBF\u037E\u0387\u055A-\u055F\u0589\u058A\u05BE\u05C0\u05C3\u05C6\u05F3\u05F4\u0609\u060A\u060C\u060D\u061B\u061E\u061F\u066A-\u066D\u06D4\u0700-\u070D\u07F7-\u07F9\u0830-\u083E\u085E\u0964\u0965\u0970\u0AF0\u0DF4\u0E4F\u0E5A\u0E5B\u0F04-\u0F12\u0F14\u0F3A-\u0F3D\u0F85\u0FD0-\u0FD4\u0FD9\u0FDA\u104A-\u104F\u10FB\u1360-\u1368\u1400\u166D\u166E\u169B\u169C\u16EB-\u16ED\u1735\u1736\u17D4-\u17D6\u17D8-\u17DA\u1800-\u180A\u1944\u1945\u1A1E\u1A1F\u1AA0-\u1AA6\u1AA8-\u1AAD\u1B5A-\u1B60\u1BFC-\u1BFF\u1C3B-\u1C3F\u1C7E\u1C7F\u1CC0-\u1CC7\u1CD3\u2010-\u2027\u2030-\u2043\u2045-\u2051\u2053-\u205E\u207D\u207E\u208D\u208E\u2308-\u230B\u2329\u232A\u2768-\u2775\u27C5\u27C6\u27E6-\u27EF\u2983-\u2998\u29D8-\u29DB\u29FC\u29FD\u2CF9-\u2CFC\u2CFE\u2CFF\u2D70\u2E00-\u2E2E\u2E30-\u2E44\u3001-\u3003\u3008-\u3011\u3014-\u301F\u3030\u303D\u30A0\u30FB\uA4FE\uA4FF\uA60D-\uA60F\uA673\uA67E\uA6F2-\uA6F7\uA874-\uA877\uA8CE\uA8CF\uA8F8-\uA8FA\uA8FC\uA92E\uA92F\uA95F\uA9C1-\uA9CD\uA9DE\uA9DF\uAA5C-\uAA5F\uAADE\uAADF\uAAF0\uAAF1\uABEB\uFD3E\uFD3F\uFE10-\uFE19\uFE30-\uFE52\uFE54-\uFE61\uFE63\uFE68\uFE6A\uFE6B\uFF01-\uFF03\uFF05-\uFF0A\uFF0C-\uFF0F\uFF1A\uFF1B\uFF1F\uFF20\uFF3B-\uFF3D\uFF3F\uFF5B\uFF5D\uFF5F-\uFF65]|\uD800[\uDD00-\uDD02\uDF9F\uDFD0]|\uD801\uDD6F|\uD802[\uDC57\uDD1F\uDD3F\uDE50-\uDE58\uDE7F\uDEF0-\uDEF6\uDF39-\uDF3F\uDF99-\uDF9C]|\uD804[\uDC47-\uDC4D\uDCBB\uDCBC\uDCBE-\uDCC1\uDD40-\uDD43\uDD74\uDD75\uDDC5-\uDDC9\uDDCD\uDDDB\uDDDD-\uDDDF\uDE38-\uDE3D\uDEA9]|\uD805[\uDC4B-\uDC4F\uDC5B\uDC5D\uDCC6\uDDC1-\uDDD7\uDE41-\uDE43\uDE60-\uDE6C\uDF3C-\uDF3E]|\uD807[\uDC41-\uDC45\uDC70\uDC71]|\uD809[\uDC70-\uDC74]|\uD81A[\uDE6E\uDE6F\uDEF5\uDF37-\uDF3B\uDF44]|\uD82F\uDC9F|\uD836[\uDE87-\uDE8B]|\uD83A[\uDD5E\uDD5F]/
    const UNICODE_PUNCT = "[!-#%-\*,-\/:;\?@\[-\]_\{\}]|\p{P}|\p{Pc}\p{Pd}|\p{Pe}|\p{Pf}|\p{Pi}|\p{Po}|\p{Ps}";
    const UNICODE_PUNCT_RE = "/".self::UNICODE_PUNCT."/u";
    // Currently without astral characters support.

    /**
     * @param string $c
     * @return int
     */
    public function isPunctChar(string $c): int
    {
        return preg_match(self::UNICODE_PUNCT_RE, $c);
    }

    /**
     * Markdown ASCII punctuation characters.
     *
     * !, ", #, $, %, &, ', (, ), *, +, ,, -, ., /, :, ;, <, =, >, ?, @, [, \, ], ^, _, `, {, |, }, or ~
     * @see http://spec.commonmark.org/0.15/#ascii-punctuation-character
     *
     * Don't confuse with unicode punctuation !!! It lacks some chars in ascii range.
     *
     * @param string $c
     * @return bool
     */
    public function isMdAsciiPunct(string $c): bool
    {
        $code = mb_ord($c);
        if(
            ( $code >= 0x21 /* ! */  && $code <= 0x2F /* / */) ||
            ( $code >= 0x3A /* : */  && $code <= 0x40 /* @ */) ||
            ( $code >= 0x5B /* [ */  && $code <= 0x60 /* ` */) ||
            ( $code >= 0x7B /* { */  && $code <= 0x7E /* ~ */)
        ) return true;
        return false;
    }

    // Hepler to unify [reference labels].
    //
    public function normalizeReference($str): string
    {
        // Trim and collapse whitespace
        //
        $str = mb_strtoupper( preg_replace("/\s+/", ' ', trim($str)) ); // /g

        // In node v10 'ẞ'.toLowerCase() === 'Ṿ', which is presumed to be a bug
        // fixed in v12 (couldn't find any details).
        //
        // So treat this one as a special case
        // (remove this when node v10 is no longer supported).
        //
        if (mb_strtoupper('ẞ') === 'Ṿ') {
            $str = mb_strtoupper( preg_replace("/ẞ/", 'ß', trim($str)) ); // /g
        }

        // .toLowerCase().toUpperCase() should get rid of all differences
        // between letter variants.
        //
        // Simple .toLowerCase() doesn't normalize 125 code points correctly,
        // and .toUpperCase doesn't normalize 6 of them (list of exceptions:
        // İ, ϴ, ẞ, Ω, K, Å - those are already uppercased, but have differently
        // uppercased versions).
        //
        // Here's an example showing how it happens. Lets take greek letter omega:
        // uppercase U+0398 (Θ), U+03f4 (ϴ) and lowercase U+03b8 (θ), U+03d1 (ϑ)
        //
        // Unicode entries:
        // 0398;GREEK CAPITAL LETTER THETA;Lu;0;L;;;;;N;;;;03B8;
        // 03B8;GREEK SMALL LETTER THETA;Ll;0;L;;;;;N;;;0398;;0398
        // 03D1;GREEK THETA SYMBOL;Ll;0;L;<compat> 03B8;;;;N;GREEK SMALL LETTER SCRIPT THETA;;0398;;0398
        // 03F4;GREEK CAPITAL THETA SYMBOL;Lu;0;L;<compat> 0398;;;;N;;;;03B8;
        //
        // Case-insensitive comparison should treat all of them as equivalent.
        //
        // But .toLowerCase() doesn't change ϑ (it's already lowercase),
        // and .toUpperCase() doesn't change ϴ (already uppercase).
        //
        // Applying first lower then upper case normalizes any character:
        // '\u0398\u03f4\u03b8\u03d1'.toLowerCase().toUpperCase() === '\u0398\u0398\u0398\u0398'
        //
        // Note: this is equivalent to unicode case folding; unicode normalization
        // is a different step that is not required here.
        //
        // Final result should be uppercased, because it's later stored in an object
        // (this avoid a conflict with Object.prototype members,
        // most notably, `__proto__`)
        //
        return mb_strtoupper(mb_strtolower($str));
    }
}