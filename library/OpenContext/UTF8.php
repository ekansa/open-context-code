<?php

/**
 *
 * These functions are from the Squirrel Mail project.
 *
 */

class OpenContext_UTF8 {

    public static function unicodetoutf8($var) {

    if ($var < 128) {

        $ret = chr ($var);

    } else if ($var < 2048) {

        // Two byte utf-8

        $binVal = str_pad (decbin ($var), 11, "0", STR_PAD_LEFT);

        $binPart1 = substr ($binVal, 0, 5);

        $binPart2 = substr ($binVal, 5); 
        $char1 = chr (192 + bindec ($binPart1));

        $char2 = chr (128 + bindec ($binPart2));

        $ret = $char1 . $char2;

    } else if ($var < 65536) {

        // Three byte utf-8

        $binVal = str_pad (decbin ($var), 16, "0", STR_PAD_LEFT);

        $binPart1 = substr ($binVal, 0, 4);

        $binPart2 = substr ($binVal, 4, 6);

        $binPart3 = substr ($binVal, 10);

 

        $char1 = chr (224 + bindec ($binPart1));

        $char2 = chr (128 + bindec ($binPart2));

        $char3 = chr (128 + bindec ($binPart3));

        $ret = $char1 . $char2 . $char3;

    } else if ($var < 2097152) {

        // Four byte utf-8

        $binVal = str_pad (decbin ($var), 21, "0", STR_PAD_LEFT);

        $binPart1 = substr ($binVal, 0, 3);

        $binPart2 = substr ($binVal, 3, 6);

        $binPart3 = substr ($binVal, 9, 6);

        $binPart4 = substr ($binVal, 15);

 

        $char1 = chr (240 + bindec ($binPart1));

        $char2 = chr (128 + bindec ($binPart2));

        $char3 = chr (128 + bindec ($binPart3));

        $char4 = chr (128 + bindec ($binPart4));

        $ret = $char1 . $char2 . $char3 . $char4;

    } else if ($var < 67108864) {

        // Five byte utf-8

        $binVal = str_pad (decbin ($var), 26, "0", STR_PAD_LEFT);

        $binPart1 = substr ($binVal, 0, 2);

        $binPart2 = substr ($binVal, 2, 6);

        $binPart3 = substr ($binVal, 8, 6);

        $binPart4 = substr ($binVal, 14,6);

        $binPart5 = substr ($binVal, 20);

 

        $char1 = chr (248 + bindec ($binPart1));

        $char2 = chr (128 + bindec ($binPart2));

        $char3 = chr (128 + bindec ($binPart3));

        $char4 = chr (128 + bindec ($binPart4));

        $char5 = chr (128 + bindec ($binPart5));

        $ret = $char1 . $char2 . $char3 . $char4 . $char5;

    } else if ($var < 2147483648) {

        // Six byte utf-8

        $binVal = str_pad (decbin ($var), 31, "0", STR_PAD_LEFT);

        $binPart1 = substr ($binVal, 0, 1);

        $binPart2 = substr ($binVal, 1, 6);

        $binPart3 = substr ($binVal, 7, 6);

        $binPart4 = substr ($binVal, 13,6);

        $binPart5 = substr ($binVal, 19,6);

        $binPart6 = substr ($binVal, 25);

 

        $char1 = chr (252 + bindec ($binPart1));

        $char2 = chr (128 + bindec ($binPart2));

        $char3 = chr (128 + bindec ($binPart3));

        $char4 = chr (128 + bindec ($binPart4));

        $char5 = chr (128 + bindec ($binPart5));

        $char6 = chr (128 + bindec ($binPart6));

        $ret = $char1 . $char2 . $char3 . $char4 . $char5 . $char6;

    } else {

        // there is no such symbol in utf-8

        $ret='?';

    }

    return $ret;

    }


    // useful for urlencoding Pınarbaşı
    public static function charset_decode_utf_8($string) {
          /* Only do the slow convert if there are 8-bit characters */
        /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
        //if (! ereg("[\200-\237]", $string) and ! ereg("[\241-\377]", $string))
        
        $hexA = dechex(200);
        $hexB = dechex(237);
        $hexC = dechex(241);
        $hexD = dechex(377);
        /*
        $hexA = (200);
        $hexB = (237);
        $hexC = (241);
        $hexD = (377);
     */

        if (! preg_match("/[\200-\237]/", $string) and ! preg_match("/[\241-\377]/", $string))
            return $string;
    
        // decode three byte unicode characters
        $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",       
        "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",   
        $string);
    
        // decode two byte unicode characters
        $string = preg_replace("/([\300-\337])([\200-\277])/e",
        "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
        $string);
    
        return $string;
    }

    // useful for urlencoding Pınarbaşı -Fix
    public static function charset_encode_utf_8 ($string) {

   // don't run encoding function, if there are no encoded characters

   if (! preg_match("'&#[0-9]+;'",$string) ) return $string;

    $string=preg_replace("/&#([0-9]+);/e","OpenContext_UTF8::unicodetoutf8('\\1')",$string);

    return $string;

}

}