<?php
// Normalize input string
namespace Kaoken\MarkdownIt\RulesCore;


class Normalize
{
    /**
     * @param StateCore $state
     */
    public function set(&$state) {
        // Normalize newlines /g
        $str = preg_replace("/\r\n?|\n/u", "\n", $state->src);

        // Replace NULL characters /g
        $str = preg_replace("/\x{0000}/u", "�", $str);  // \uFFFD

        $state->src = $str;
    }
}