<?php

class MVC_Library_Handle_Time {

	public $CI = NULL; // Controller Instance, auto-set when load->library. You can access models, view or other loaded libraries

	private $os = false;
	private $need_utf8_encode = true;
	private $now = NULL;
	
	function __construct() {
				
		$locale = 'es_ES';
		$locale_639 = 'ESP';
		
		//setlocale(LC_ALL,"es_ES");
		//setlocale(LC_ALL,"es_ES").': ';
		// domingo 24 de abril de 2016
		//$locale_date_string = strftime('%A %d de %B de %Y', time());
		
		if (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
			$this->os = 'linux';
			if (FALSE === setlocale(LC_ALL, $locale)) {
				//echo "Failed {$locale}, trying: {$locale}.utf8 ";
				setlocale(LC_ALL, "{$locale}.utf8");
				$this->need_utf8_encode = FALSE;
			}
		}
		elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->os = 'windows';
			// ISO-639 here: http://www.w3.org/WAI/ER/IG/ert/iso639.htm
			if (FALSE === setlocale(LC_ALL,"ESP")) {
				//echo "Failed {$locale_639}, trying: {$locale}";
				setlocale(LC_ALL, $locale);
			}
		}
		elseif (strtoupper(strstr(PHP_OS, 'DARWIN'))) {
			$this->os = 'osx';
			$this->need_utf8_encode = FALSE; // weird...
			if (FALSE === setlocale(LC_ALL, $locale)) {
				//echo "Failed {$locale}, trying: {$locale}.utf8 ";
				setlocale(LC_ALL, "{$locale}.utf8");
			}
		}

		// initialize current time
		$this->now = time();
	}
	
	public function get_nice_today() {
		$str = ucfirst(strftime('%A, %d de %B de %Y', $this->now));
		return ($this->need_utf8_encode) ? utf8_encode($str) : $str;
	}
	
	public function friendly_datetime($input, $simple_format=false, $hourmin = 'H:i', $timestamp = null) {
		
		if (!$timestamp) {
			$timestamp = $this->now;
		}
		if ($input == '0000-00-00') return '';
		if (!is_numeric($input)) $input = strtotime($input);

		//$h = " h";
		$h = '';

		if (! $simple_format) { // print "today, tomorrow, yesterday", or week day, based on current day/week
			
			if (date("Ymd", $input) == date("Ymd", $timestamp)) { return (!$hourmin) ? "Hoy ".date("j", $input) : "Hoy ".date('j, '.$hourmin, $input).$h; }
			if (date("Ymd", $input) == date("Ymd", $timestamp+60*60*24)) { return (!$hourmin) ? "Mañana ".date("j", $input) : "Mañana ".date('j, '.$hourmin, $input).$h; }
			if (date("Ymd", $input) == date("Ymd", $timestamp-60*60*24)) { return (!$hourmin) ? "Ayer ".date("j", $input) : "Ayer ".date('j, '.$hourmin, $input).$h; }
			//if ($input > $timestamp-60*60*24*6) { return (!$hourmin) ? date("l", $input) : date("l", $input).", ".date($hourmin, $input).$h; } // Día de la semana
			if ($input > $timestamp-60*60*24*6 && $input < $timestamp) {
				// Día de la semana
				if (!$hourmin) {
					$result = ucfirst(strftime("%A", $input))." ".date('j', $input);
				} else {
					$result = ucfirst(strftime("%A", $input))." ".date('j, '.$hourmin, $input).$h;
				} 
				return ($this->need_utf8_encode) ? utf8_encode($result) : $result;
			}
		}

		// Print monthday and month (exclude year if it's current year)
		if ($this->os != 'windows') {
			//if (date("Y", $input) == date("Y", $timestamp)) { return (!$hourmin) ? date("j \d\e M.", $input) : date("j \d\e M.", $input)." ".date($hourmin, $input)." h"; } // 5 DIC  
			if (date("Y", $input) == date("Y", $timestamp)) { return (!$hourmin) ? strtoupper(strftime("%e %b", $input)) : strtoupper(strftime("%e %b", $input)." ".date($hourmin, $input)).$h; } // 5 DIC
		} else {
			if (date("Y", $input) == date("Y", $timestamp)) { return (!$hourmin) ? strtoupper(strftime("%#d %b", $input)) : strtoupper(strftime("%#d %b", $input)." ".date($hourmin, $input)).$h; } // 5 DIC
		}
		
		// Otherwhise print monthday, month and year
		if ($this->os != 'windows') {
			//return (!$hourmin) ? date("Y M. j", $input) : date("Y M. j", $input).", ".date($hourmin, $input)." h"; // 2016-12-25
			return (!$hourmin) ? strtoupper(strftime("%e %b %Y", $input)) : strtoupper(strftime("%e %b %Y", $input).", ".date($hourmin, $input)).$h; // 5 DIC 2016
		}
		else {  
			return (!$hourmin) ? strtoupper(strftime("%#d %b %Y", $input)) : strtoupper(strftime("%#d %b %Y", $input).", ".date($hourmin, $input)).$h; // 5 DIC 2016
		}		
	}

	/**
	* "Faltan x días..." pasar solo fecha futura como primer parámetro
	* "Hace x horas..." pasar fecha pasada
	* 
	* @param mixed $a
	* @param mixed $b
	* @param mixed $timestamp
	* @return mixed
	*/
	function friendly_btw_time($a=0, $b=0, $timestamp = null) {
		if (!$timestamp) {
			$timestamp = time();
		}
		if (!is_numeric($a)) $a = strtotime($a); if (!is_numeric($b)) $b = strtotime($b);
		if ($a == 0) $a = $timestamp; if ($b == 0) $b = $timestamp;
		if ($a == $b) return "Ahora mismo";
		if ($a > $b) { $aa = $b; $b = $a; $a = $aa; $q = "falta"; $qq = 'n'; } else { $q = "hace"; $qq = '';}
		$value = $b-$a;
		if ($value == 1) return "{$q} 1 segundo";
		if ($value < 60) return "{$q}{$qq} $value segundos";
		$value /= 60;
		if (round($value) == 1) return "{$q} 1 minuto";
		if ($value < 60) return "{$q}{$qq} " . round($value) . " minutos";
		$value /= 60;
		if (round($value) == 1) return "{$q} 1 hora";
		if ($value < 24) return "{$q}{$qq} " . round($value) . " horas";
		$value /= 24;
		if (round($value) == 1) if ($q == "hace") return "Ayer"; else return "Mañana";
		if ($value < 31) return "{$q}{$qq} " . round($value) . " días";
		$value /= 31;
		if (round($value) == 1) return "{$q} 1 mes";
		if ($value < 12) return "{$q}{$qq} " . round($value) . " meses";
		$value /= 12;
		if (round($value) == 1) return "{$q} 1 año";
		return "{$q}{$qq} " . round($value) . " años";
	}

	public function friendly_countdown($ts, $now_dt) {

		$future_date = new DateTime("@$ts");
		$i = $future_date->diff($now_dt);
		$s = array();
        if ($i->y) { $s[]= "{$i->y} ".(($i->y == 1) ? 'año': 'años'); }
        if ($i->m) { $s[]= "{$i->m} ".(($i->m == 1) ? 'mes': 'meses'); }
        if ($i->d) { $s[]= "{$i->d} ".(($i->d == 1) ? 'día': 'días'); }
        if ($i->h) { $s[]= "{$i->h} ".(($i->h == 1) ? 'hora': 'horas'); }
        if ($i->i) { $s[]= "{$i->i} ".(($i->i == 1) ? 'min.': 'mins.'); }
        if ($i->s) { $s[]= "{$i->s} ".(($i->s == 1) ? 'seg.': 'segs.'); }
        return join(', ', $s);
		//return $interval->format("%y año(s), %m mes(es), %a día(s), %h hora(s), %i minuto(s), %s segundo(s)");
	}
	
	/*
	public function _strftime($str, $timestamp) {
		$result = strftime($str, $timestamp);
		$enc = mb_detect_encoding($result);
		if ($enc == 'ISO-8859-1') {
			return iconv('ISO-8859-1', 'UTF-8', $result);
		}
		return $result; 		
	}
	*/
	
	public function test() {

		// Tell PHP that we're using UTF-8 strings until the end of the script
		mb_internal_encoding('UTF-8');
		// Tell PHP that we'll be outputting UTF-8 to the browser
		mb_http_output('UTF-8');
		// Set the UTF-8 charset at http response headers
		header('Content-Type: text/html; charset=UTF-8');
		
		echo '<pre>';
		echo 'PHP_OS: ['.PHP_OS.'] os['.$this->os.'] need_utf8_encode['.$this->need_utf8_encode.']<hr>';
		
		echo "<strong>Testing friendly_datetime()</strong><br/>";
		$dates = array(
			date("Y-m-d") => time(), // hoy
			date("Y-m-d", strtotime("-1 day")) => strtotime("-1 day"), // ayer
			date("Y-m-d", strtotime("-2 day")) => strtotime("-2 day"), // día de la semana
			date("Y-m-d", strtotime("-3 day")) => strtotime("-3 day"), // día de la semana
			date("Y-m-d", strtotime("-4 day")) => strtotime("-4 day"), // día de la semana
			date("Y-m-d", strtotime("-5 day")) => strtotime("-5 day"), // día de la semana
			date("Y-m-d", strtotime("-6 day")) => strtotime("-6 day"), // día de la semana
			date("Y-m-d", strtotime("-7 day")) => strtotime("-7 day"), // día del mes
			date("Y-m-d", strtotime("-8 day")) => strtotime("-8 day"), // día del mes
			date("Y-m-d", strtotime("-1 month")) => strtotime("-1 month"), // día del mes año actual
			date("Y-m-d", strtotime("-2 month")) => strtotime("-2 month"), // día del mes año actual
			date("Y-m-d", strtotime("2016-01-01")) => strtotime("2016-01-01"), // día del mes año actual
			date("Y-m-d", strtotime("-1 year")) => strtotime("-1 year"), // día mes año completo
			date("Y-m-d", strtotime("-2 year")) => strtotime("-2 year"), // día mes año completo
		);
		
		$now = time();
		foreach($dates as $date => $ts) {
			$cdate = $this->friendly_datetime($ts, null, $now);
			$cdatetime = $this->friendly_datetime($ts, 'H:i', $now); 
			echo "[{$date}] =&gt; [{$cdate}] - [{$cdatetime}]<br/>";
		}
		echo '<hr>';
		
		echo "<strong>Testing friendly_btw_time()</strong><br/>";
		$now = time();
		$dates = array(
			array('a' => $now),
			array('a' => strtotime('+1 day')),				// Mañana
			array('a' => strtotime('+3 second')),			// Faltan ...
			array('a' => strtotime('+5 minute')),			// Faltan ...
			array('a' => strtotime('+1 hour')),				// Faltan ...
			array('a' => strtotime('+1 hour +32 minute')),	// Faltan ...
			array('a' => strtotime('+3 day +1 hour')),		// Faltan ...
			array('a' => strtotime('+1 month +1 day')),		// Faltan ...
			array('a' => strtotime('+6 month +6 day')),		// Faltan ... 
			array('a' => strtotime('+1 year +6 day')),		// Faltan ...
			array('a' => strtotime('+3 year +6 day')),		// Faltan ... 

			array('a' => strtotime('-1 day')),				// Ayer
			array('a' => strtotime('-3 day -1 hour')),		// Hace 3 días
			array('a' => strtotime('-1 week')),				// Hace ...
			array('a' => strtotime('-1 month')),			// Hace ...
			array('a' => strtotime('-1 year -2 month')),	// Hace ...
			array('a' => strtotime('-2 year -3 month')),	// Hace ...

		);
		foreach($dates as $data) {
			$this->_test_friendly_btw_time($data);
		}
		echo '<hr>';		
	}
	
	private function _test_friendly_btw_time($data) {
		//echo "[".date('Y-m-d H:i:s', $data['a'])."] [".date('Y-m-d H:i:s', $data['b'])."] ";
		echo "[".date('Y-m-d H:i:s', $data['a'])."]";
			
		echo ' => ['.$this->friendly_btw_time($data['a'], $data['b']).']';
						
		echo "<br/>";
	} 
	
}