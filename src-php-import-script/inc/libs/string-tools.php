<?php

function urlize($url) {
    $search = array('/&apos;/', '/&quot;/', '/[^a-z0-9]/', '/--+/', '/^-+/', '/-+$/' );
    $replace = array( '', '', '-', '-', '', '');
    return preg_replace($search, $replace, _utf2ascii($url));
}

function _utf2ascii($string) {
        $iso88591  = "\xE0\xE1\xE2\xE3\xE4\xE5\xE6\xE7";
        $iso88591 .= "\xE8\xE9\xEA\xEB\xEC\xED\xEE\xEF";
        $iso88591 .= "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7";
        $iso88591 .= "\xF8\xF9\xFA\xFB\xFC\xFD\xFE\xFF";
        $ascii = "aaaaaaaceeeeiiiidnooooooouuuuyyy";
        return strtr(mb_strtolower(utf8_decode($string), 'ISO-8859-1'),$iso88591,$ascii);
        //return strtr(strtolower($string),$iso88591,$ascii);
}

// devuelve una cadena entre otras dos, siendo la segunda opcional
function get_str_btw($result, $bef, $aft="", $trim=true) {
    $line=1;
    $len = strlen($bef);
    $pos_bef = strpos($result, $bef);
    if($pos_bef===false)
      return "";
    $pos_bef+=$len;

    if(empty($aft)) { // try to search up to the end of line
      $pos_aft = strpos($result, "\n", $pos_bef);
      if($pos_aft===false)
        $pos_aft = strpos($result, "\r\n", $pos_bef);
    }
    else $pos_aft = strpos($result, $aft, $pos_bef);

    if($pos_aft!==false) {
    	if ($pos_aft-$pos_bef === 0)
      	$rez = substr($result, $pos_bef);
    	else
      	$rez = substr($result, $pos_bef, $pos_aft-$pos_bef);
	}
    else
      $rez = substr($result, $pos_bef);

    if ($trim)
        return trim($rez);
    else
        return ($rez);
}

?>
