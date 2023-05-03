<?php

/**
* MVC_MYSQL
*
* MySQL database access using mysqli PHP functions
*
* @package		softlineMVC
* @author		Juanga Covas
*/

define("MVC_MYSQL_MESSAGE",	"<hr><span style=\"font-family: Calibri, Tahoma, Arial;\">"
	  ."<b class=bgdbg style=\"border-top:2px solid gray; border-bottom:2px solid gray;\">&nbsp;MVC_MYSQL&nbsp;</b>&nbsp; &copy; ".date("Y")." &nbsp;"
	  ."<b style=\"background-color:#004d95; color:white; border-top:2px solid #004d95; border-bottom:3px solid #e0007a;\">"
	  ."&nbsp;SoftLine&nbsp;</b> &nbsp;</span>&nbsp;"
);
define("NoDieOnError", false);	// use NoDieOnError	at "send function" in order	to be able to check	for	$instance->errors after	$instance->send()
define("NoDieOnErrors",	false);	// alias

/*
if (! function_exists('debug'))	{
function debug($str) {

}
}
*/

class MVC_MYSQL
{
	// for each	QUERY ($instance->send()) you can use these	vars until the next	$instance->send()

	public $config = null;
	public $n_cols;					// how many	columns
	public $n_rows,	$rows, $row;	// how many	rows and row data ($instance->rows[0]['colname'] even if there is just one row)
	public $n_affected;				// how many	affected rows resulted
	public $n_queries;				// how many	queries	so far
	public $errors;					// this	var	initializes	to false when $instance->send()	is called
	public $debug, $debug_results, $debug_format_queries;

	// for each	INSTANCE
	public $host, $db, $user, $pass;
	public $link, $flashmode;
	public $queries = array();				// query list (just	SQL	queries)
	public $total_q_time = 0;				// time took for all queries
	public $debug_queries = array();		// query list (including connection	notes &	error(s))
	public $clon;

	private $mvc = NULL;

	function __construct($config=null)
	{
		if (empty($config))	{
			throw new Exception("ERROR MVC_MYSQL: Database definitions required.");
		}
		$this->config =	$config;

		$this->mvc = mvc::instance();

		$this->db=$config['name']; $this->host=$config['host'];	$this->user=$config['user']; $this->pass=$config['pass'];
		$this->n_queries=0;
		$this->flashmode=$config['flashmode'];

		if(!function_exists('mysqli_query')) {
			throw new Exception("PHP mysqli	functions not found?");
		}

		if(empty($this->config['charset']))	{
			$this->config['charset'] = 'utf8';
		}

		$this->debug = $config['debug']; //	you	can	use	this boolean to	determine whether to print queries on your output or do	nothing
		$this->debug_results = $config['debug_results'];
		$this->debug_format_queries	= $config['debug_format_queries'];

		if ($this->debug)
		{
			$this->clon	= false;

			(strlen($config['pass'])) ?	$yes=""	: $yes=", Password:	NO";
			$this->debug_queries[] = MVC_MYSQL_MESSAGE . " (Host: {$this->host}, DB: {$this->db}, user:	{$this->user}{$yes})";
			$this->debug_queries[] = "<style>"
			.".sqldbg	{ font-family:Arial; font-size:11px; }"
			."b.bgdbg {	color:white; background-color:#111111; border:solid 1px; }"
			."span.query { font-size:12px; color:blue; }"
			."</style>";
		}
	}

	function __destruct() {
		if ($this->link) {
			mysqli_close($this->link);
			$this->link	= null;
		}
	}

	/**
	* Sends	a query	to MySQL and return	Number of rows in set or N Affected	rows (if not a SELECT)
	*
	* @param mixed $query
	* @param bool $die_on_error	(default: true)
	* @return int number of	rows in	set	or affected	rows if	not	a select
	*/
	public function	send($query, $die_on_error=true, $timer=true)
	{
		$query = trim($query);
		if (empty($query)) {
			$this->_error("EMPTY QUERY???",	true); // die on error
		}
		$this->last_query =	$query;

		// connect if needed
		$this->_connect();
		// initialize
		$this->error=false;
		// count this query
		$this->n_queries++;

		$this->rows	= NULL;
		$this->row = NULL;
		$this->n_rows =	0;
		$this->n_affected =	0;
		$this->n_cols =	0;

		if ($this->debug && $timer) {
			$this->mvc->timer('mvc_mysql_q_'.$this->n_queries.'_s');
		}

		// query database
		if (! $res = mysqli_query($this->link, $query))	{
			$this->_error($query, $die_on_error);
		}

		if ($this->debug && $timer) {
			$this->mvc->timer('mvc_mysql_q_'.$this->n_queries.'_e');
		}

		$this->n_affected=@mysqli_affected_rows($this->link);

		if (is_object($res)) {

			$this->n_cols=@mysqli_num_fields($res);
			$this->n_rows=@mysqli_num_rows($res);

			if ($this->error) {
				return 0;
			}

			if ($this->n_rows > 0) {
				// format results (compose a nice array	at $instance->rows)
				$nr	= 0;
				$this->rows = array();
				while ($row	= mysqli_fetch_assoc($res))	{
					foreach($row as	$colname =>	$value)	{
						$this->rows[$nr][$colname] = $value;
					}
					$nr++;
				}
				$this->row =& $this->rows[0];
			}
		}

		$is_select = (preg_match("/^SELECT/i", $query)) ? true : false;

		if ($this->debug)
		{
			// log the query and add debug info
			$this->queries[]=$query;
			if (!$this->debug_format_queries)
			$this->debug_queries[]="<b>mysql> </b><span	class=query>".$query."</span>";
			else
			$this->debug_queries[]='<b>mysql> </b><span	class=query>'.str_replace("\t","&nbsp;&nbsp;", str_replace("\n", "<br>", trim($query)))."</span>";

			$st_n_rows = ($this->n_rows) ? $this->n_rows : '0';
			$st_n_affected = ($this->n_affected) ? $this->n_affected : '0';

			//debug("MVC_MYSQL::send()...".str_replace("\t", "", trim($query)));
			if ($timer) {
				$q_time = $this->mvc->timer('mvc_mysql_q_'.$this->n_queries.'_s','mvc_mysql_q_'.$this->n_queries.'_e');
				$this->total_q_time += $q_time;
			}
			if ($is_select) {
				$one_row = ($this->n_rows == 1) ? true : false;
				$s_str = ($one_row) ? '' : 's';
				if (! $this->n_rows) {
					$res_str_a = '<b style="background-color:darkblue; color:white;">&nbsp;'; $res_str_b = '&nbsp;</b>';
				}
				$this->debug_queries[]="&nbsp;&nbsp;&nbsp;{$res_str_a}{$st_n_rows} fila{$s_str}{$res_str_b} (".sprintf('%0.5f', $q_time).' seg.)';
			} else {
				$one_row = ($this->n_affected == 1) ? true : false;
				$s_str = ($one_row) ? '' : 's';
				if (! $this->n_affected) {
					$res_str_a = '<b style="background-color:darkred; color:white;">&nbsp;'; $res_str_b = '&nbsp;</b>';
				} else {
					$res_str_a = '<b style="background-color:darkgreen; color:white;">&nbsp;'; $res_str_b = '&nbsp;</b>';
				}
				$this->debug_queries[] = "&nbsp;&nbsp;&nbsp;{$res_str_a}{$st_n_affected} fila{$s_str} afectada{$s_str}{$res_str_b} (".sprintf('%0.5f', $q_time).' seg.)';
			}
			//debug(" ".$this->n_rows."	row(s) in set, ".$this->n_affected." affected row(s).");

			if ($this->debug_results) {
				if ($this->n_rows) { //	print formatted	result
					$htmlr="<table cellspacing=0 cellpadding=2 border=1><tr><td>&nbsp;</td>";
					foreach($this->rows[0] as $colname => $value) {
						$htmlr.="<td class=sqldbg><b>{$colname}</b></td>";
					}
					$htmlr.="</tr>";
					foreach($this->rows	as $nr => $row)	{
						$htmlr.="<tr><td class=sqldbg>#{$nr}</td>";
						foreach($row as	$colname =>	$value)	{
							$htmlr.="<td class=sqldbg>{$value}</td>";
						}
						$htmlr.="</tr>";
					}
					$htmlr.="</table>";
					$this->debug_queries[]=$htmlr;
				}
			}
		}

		if ($is_select) {
			return $this->n_rows;
		}
		return $this->n_affected;
	}

	// get a copy of the rows
	public function get_rows() {
		if ($this->rows) {
			$copy = array();
			foreach($this->rows as $row) {
				$copy[] = $row;
			}
			return $copy;
		}
		return NULL;
	}

	public function	get_columns() {
		if (! $this->rows) {
			return NULL;
		}
		$cols=array();
		foreach($this->rows[0] as $colname => $value) {
			$cols[]=$colname;
		}
		return $cols;
	}

	public function	last_insert_id() {
		return mysqli_insert_id($this->link);
	}

	/**
	* Send the query and return	instance->rows bidimensional array [0..N]['column']
	*
	* @param mixed $query
	* @param bool $die_on_error	(default: true)
	*/
	public function	query($query, $die_on_error=true) {
		if ($this->send($query,	$die_on_error)) {
			if ($this->rows) {
				$copy = array();
				foreach($this->rows as $row) {
					$copy[] = $row;
				}
				return $copy;
			}
		}
		return NULL;
	}
	/**
	* Send the query and return	the	first row only as associative array
	*
	* @param mixed $query
	* @param bool $die_on_error	(default: true)
	*/
	public function	query_row($query, $die_on_error=true) {
		if ($this->send($query,	$die_on_error)) {
			if ($this->row)	{
				$copy = $this->rows[0];
				return $copy;
			}
		}
		return NULL;
	}

	public function	str2sql($cad, $allowtags=true, $convert_to_html=false)
	{
		if (! $allowtags) $cad = strip_tags($cad);
		if ($convert_to_html) $cad = htmlspecialchars($cad);
		//$cad = addslashes($cad);
		if (! $this->link)
		return addslashes($cad);
		else
		return mysqli_real_escape_string($this->link, $cad);
	}

	// Connect to MySQL
	private	function _connect( $test=false ) {

		if ($this->link) return	true;

		$this->connect_error = null;

		//debug("MVC_MYSQL::_connect({$this->host},	db:	{$this->db}, user: {$this->user})");

		if ($this->debug)
			$this->mvc->timer('mvc_mysql_connect_start');


		if (! $this->link =	mysqli_connect($this->host,$this->user,$this->pass,	$this->db) ) {
			$this->connect_error = 'ERROR al intentar conectar a la DB. #'.mysqli_connect_errno().': '.mysqli_connect_error();
			if ($this->flashmode) {
				echo "&vestado=0&verrores=database_nolink";
				exit;
			}
			if ($test) return false;
			//debug("######	".$this->connect_error);
			$this->debug_queries[] = "###### ".$this->connect_error;

			$this->_error(); //	will die
		}

		if (! mysqli_set_charset($this->link, $this->config['charset'])) {
			throw new Exception("ERROR al establecer el charset de la DB ({$this->config['charset']}) - ".mysqli_error($this->link));
		}

		if ($this->debug)
			$this->mvc->timer('mvc_mysql_connect_end');

		return true;
	}

	// prueba la conexión (intenta establecer el link y	seleccionar	la base	de datos por defecto indicada en el	constructor)
	public function	connect_test()
	{
		return $this->_connect(	true );
	}

	// On Error:
	private	function _error($query=NULL, $die_on_errors=true) {
		$this->debug_queries[] = "<b style=\"color:white; background-color:#222222; border:solid 1px;\">Este query ha fallado:</b> <br>"
		.str_replace("\n", "<br>", trim($query))."<br><b>MySQL error #".mysqli_errno($this->link).":</b> ".mysqli_error($this->link);
		//debug("MVC_MYSQL::send() > $query");
		$diemsg="(dying) : "; if (!	$die_on_errors)	$diemsg="(NoDieOnError)	: ";
		//debug("######	MySQL ERROR	#".mysqli_errno($this->link)." {$diemsg}".mysqli_error($this->link));
		$this->error=true;
		if ($die_on_errors)	{ // if	die	on errors, print queries and exit
			//debug("EXIT FROM PHP");
			@header('Content-Type: text/html; charset=UTF-8');
			die("<b	style=\"color:white; background-color:#DD0000; border:solid 1px; border-color:red;\">"
			."Error inesperado al acceder a la base de datos</b><br><p>"
			.implode("<br>", $this->debug_queries));
		}
	}

	// Print queries for debug
	public function	debug_info(&$where)	{
		if ($this->debug) {
			if (! empty($this->debug_queries)) {

				$where = '';
				$where .= array_shift($this->debug_queries); // extract the title line and put it first

				$c_time = $this->mvc->timer('mvc_mysql_connect_start', 'mvc_mysql_connect_end');
				$this->total_q_time += $c_time;

				$sql_c_time_str = 'Tiempo para conectar a MySQL: '.sprintf('%0.5f', $c_time).' seg.';

				$one_query = ($this->n_queries == 1) ? true : false;
				$q_str = ($one_query) ? 'query' : 'queries';
				if ($this->n_queries) {
					$s_str = ($one_query) ? '' : 's';
					$where.="<br/><br/><font style=\"font-family:Arial; font-size:11px;\">".$this->n_queries." {$q_str} MySQL ejecutado{$s_str} en ".sprintf('%0.5f', $this->total_q_time).' seg. - '.$sql_c_time_str.'<br/>';
				} else {
					$where.="<p><hr><font style=\"font-family:Arial; font-size:11px;\">No se han enviado queries a MySQL. ";
					$c_str = ($c_time) ? '<b style="color:red;">'.$sql_c_time_str.'</b>' : 'Tampoco se ha conectado al servidor MySQL.';
					$where.=$c_str.'</p>';
				}
				// list all queries
				foreach	($this->debug_queries as $query) {
					$where.= $query."<br>";
				}
			}
		}
	}
}

if(!function_exists("stripslashes_array")){
	function stripslashes_array(&$array)
	{
		if (! is_array($array))	{ stripslashes($array);	return;	}

		while (list($key) = each($array)) {
			if (is_array($array[$key])) stripslashes_array($array[$key]);
			else	$array[$key] = stripslashes($array[$key]);
		}
	}
}

