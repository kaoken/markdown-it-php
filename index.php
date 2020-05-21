<?php
/**
 * It is for testing each application.
 */
require_once  __DIR__.'/vendor/autoload.php';
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );

use Kaoken\MarkdownIt\MarkdownIt;

$md = new MarkdownIt([
    "html"=> true,
    "langPrefix"=> '',
    "typographer"=> true,
    "linkify"=> true
]);

switch(isset($_GET['i'])?$_GET['i']:'0'){
    case '1':
        new Kaoken\Test\MDUrl\CTest();break;
    case '2':
        new Kaoken\Test\LinkifyIt\CTest();break;
    case '3':
        new Kaoken\Test\Punycode\CTest();break;
    case '4':
        new Kaoken\Test\MarkdownIt\Plugins\CTest();break;
    default:
        new Kaoken\Test\MarkdownIt\CTest();break;
}
//echo($md->render("The dog---\"'man's' best friend\""));
