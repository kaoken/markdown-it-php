<?php
/**
 * It is for testing each application.
 */
require_once  __DIR__.'/vendor/autoload.php';
ini_set( 'display_errors', 1 );
ini_set( 'error_reporting', E_ALL );

//use Kaoken\MarkdownIt\MarkdownIt;
//use Kaoken\MarkdownIt\Plugins\MarkdownItContainer;
//$md = new MarkdownIt([
//    "html"=> true,
//    "langPrefix"=> '',
//    "typographer"=> true,
//    "linkify"=> true
//]);
////$md->plugin(new MarkdownItContainer(), 'name');
//$str = '\\'.json_decode('"\uD835\uDC9C"');
//echo($md->render($str));

$_GET['i'] = 4;
switch(isset($_GET['i'])?$_GET['i']:'0'){
    case '1':
        new Kaoken\Test\MDUrl\CTest();break;
    case '2':
        new Kaoken\Test\LinkifyIt\CTest();break;
    case '3':
        new Kaoken\Test\Punycode\CTest();break;
    case '4':
        new Kaoken\Test\MarkdownIt\Plugins\CTest();break;
    case '99':
        phpinfo();
        break;
    default:
        new Kaoken\Test\MarkdownIt\CTest();break;
}

