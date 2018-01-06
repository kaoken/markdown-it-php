<?php
/**
 * It is for testing each application.
 */
require_once  __DIR__.'/vendor/autoload.php';
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );

use Kaoken\MarkdownIt\MarkdownIt;

//use Kaoken\Test\MDUrl\CTest;
//use Kaoken\Test\LinkifyIt\CTest;
use Kaoken\Test\MarkdownIt\CTest;
//use Kaoken\Test\Punycode\CTest;
//use Kaoken\Test\MarkdownIt\Plugins\CTest;

new CTest();
?>
