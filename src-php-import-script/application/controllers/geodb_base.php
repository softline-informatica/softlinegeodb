<?php

include 'inc/libs/string-tools.php';

/**
* 
* 	softlineGeoDB
* 
*      Escrito por Juanga Covas 2016 - 2023
* 
*/
class Geodb_Base_Controller extends MVC_Controller
{
    private $db = NULL;
    private $data = NULL;
    private $cp = NULL;
    private $provincias_ccaa = NULL;

	function __construct() {
		parent::__construct();

		if (ob_get_level() == 0) ob_start();
	}

	private function _set_db() {
		
		$this->load->model('geodb_model', 'mgeodb');
		$this->db =& $this->mgeodb->db;
	}

	public function index() {

		echo '<h1>SoftLine GeoDB</h1>';
		echo '<a href="geodb_base/import">Importar</a>';
	}

    public function import() {

    	ini_set('memory_limit', '912M');
    	set_time_limit(0); // run forever
    	
    	$this->_set_db();
    	
    	// CCAA y Provincias no deberían importarse de nuevo nunca, tienen campos introducidos a mano como Capitales/Provincia y el id_ccaa/Provincia
    	//$this->import_ccaa();
    	//$this->import_provincias();

    	// Prepara splits del archivo de censo
    	$this->_split_censo();
    	$this->_split_ngbe();
    	
    	//$this->_test();
    	
    	// Estos sí pueden importarse de nuevo para recbir 'actualizaciones' de datos
    	$this->import_municipios();
		$this->import_geodata();
		$this->import_ngbe();
		
    	$this->import_codigospostales();
		$this->import_callejero();
		
		//$this->check_github_ds_codigos_postales_ine();
		
    	echo "<hr>Hecho.<br/>";

		$this->db->debug_info($debug_info);
		echo "<hr>".$debug_info;
    }
    
    public function _test() {
		
		echo "Uno...<br/>";
		$this->_flush();
		sleep(1);
		echo "Dos...<br/>";
		$this->_flush();
		sleep(1);
		echo "Done!<br/>";
		
    }

    // OJO! Hace TRUNCATE de la tabla
    public function import_geodata() {

    	$year = date('Y');
    
		echo "<hr><strong>Importar Geo Data CNIG</strong> Tabla: sp_geodb_ine_municipios_geo";
		$filename = 'datos/CNIG/'.$year.'/MUNICIPIOS.txt';
		$lines = file($filename);
		if (! $n_lines = count($lines)) {
			die('Unable to load: '.$filename);
		}
		
		echo "Archivo: {$filename} ({$n_lines} líneas)";
		echo '<hr>';

		$this->db->send("TRUNCATE sp_geodb_ine_municipios_geo");

		if (! $this->provincias_ccaa) $this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		//if (! $this->provincias_isla) $this->provincias_isla = $this->mgeodb->get_provincias_islas_ids();

		$values = array();
		$total=0; 
		if ($lines[0] && strpos($lines[0], 'COD_INE') === 0) {
			// quitar primera línea solo si parece haber primera línea con los nombres de columnas
			$_first_line = $lines[0];
			array_shift($lines);

			$_test = explode(';', $_first_line);
			if (count($_test) != 18) die('*** ERROR: Se esperaba que la primera línea tuviera 18 columnas');
			if (trim($_test[17]) !== 'ORIGENALTITUD') die('*** ERROR: Se esperaba que la última columna de la primera línea fuera ORIGENALTITUD');

		} else {
			die('*** ERROR: Se esperaba que la primera línea empezara por COD_INE');
		}
		
		foreach($lines as $line) {
			$line = trim($line);
			if (empty($line))
				continue;

			//$_test = explode(';', $line);			
				
			list(
				$COD_INE,
				$ID_REL,
				$COD_GEO,
				$COD_PROV,
				$PROVINCIA,
				$NOMBRE_ACTUAL,
				$POBLACION_MUNI,
				$SUPERFICIE,
				$PERIMETRO,
				$COD_INE_CAPITAL,
				$CAPITAL,
				$POBLACION_CAPITAL,
				$HOJA_MTN25_ETRS89,
				$LONGITUD_ETRS89,
				$LATITUD_ETRS89,
				$ORIGENCOOR,
				$ALTITUD,
				$ORIGENALTITUD
			) = explode(";", $line);

			//echo $line.'<hr>';
			
			// id_municipio
            $ine_id_provincia = substr($COD_INE, 0, 2);
            $id_provincia = $ine_id_provincia;
            settype($id_provincia, 'int');
            settype($id_provincia, 'string');
			$id_ccaa = $this->provincias_ccaa[$id_provincia];
            $ine_id_municipio = substr($COD_INE, 2, 3);
            $id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;
            $entidad_colectiva = substr($COD_INE, 5, 2);
            $entidad_singular = substr($COD_INE, 7, 2);
            $nucleo = substr($COD_INE, 9, 2);

			$id_ccaa = $this->provincias_ccaa[$id_provincia];
			
            $id_isla = $this->_get_id_municipios2isla($id_municipio);
            
            /*
            // id_municipio_capital
            // Resultan coincidir todos con el id_municipio...
            $ine_id_provincia_capital = substr($COD_INE_CAPITAL, 0, 2);
            $id_provincia_capital = $ine_id_provincia_capital;
            settype($id_provincia_capital, 'int');
            settype($id_provincia_capital, 'string');
			$id_ccaa_capital = $this->provincias_ccaa[$id_provincia_capital];
            $ine_id_municipio_capital = substr($COD_INE_CAPITAL, 2, 3);
            $id_municipio_capital = $id_ccaa_capital.$ine_id_provincia_capital.$ine_id_municipio_capital;
            */

            $LATITUD_ETRS89 = str_replace(',', '.', $LATITUD_ETRS89);
            $LONGITUD_ETRS89 = str_replace(',', '.', $LONGITUD_ETRS89);

            $SUPERFICIE = str_replace(',', '.', $SUPERFICIE);
            $PERIMETRO = str_replace(',', '.', $PERIMETRO);

            if ($HOJA_MTN25_ETRS89 == 'No disponible') {
				$HOJA_MTN25_ETRS89 = '';
            }
            
			$POBLACION_MUNI = $this->db->str2sql($POBLACION_MUNI);
			$SUPERFICIE = $this->db->str2sql($SUPERFICIE);
			$PERIMETRO = $this->db->str2sql($PERIMETRO);
			$HOJA_MTN25_ETRS89 = $this->db->str2sql($HOJA_MTN25_ETRS89);
			$ALTITUD = $this->db->str2sql($ALTITUD);
			$COD_GEO = $this->db->str2sql($COD_GEO);

			$values[]="({$id_municipio}, {$LATITUD_ETRS89}, {$LONGITUD_ETRS89}, {$id_provincia}, {$id_isla}, {$id_ccaa}, {$POBLACION_MUNI}, '{$SUPERFICIE}', {$PERIMETRO}, '{$HOJA_MTN25_ETRS89}', '{$ALTITUD}', '{$COD_GEO}')";

			$total++;
			
			//break;
		}

		echo "{$total} entradas leídas.<br/>";

		$n_inserted = $this->db->send("INSERT sp_geodb_ine_municipios_geo (id_municipio_geo, latitud, longitud, id_provincia, id_isla, id_ccaa, poblacion, superficie, perimetro, hoja_mtn25, altitud, codigo_cnig) VALUES ".join(',', $values));

		echo "Insertadas {$n_inserted} filas en sp_geodb_ine_municipios_geo.<br/><br/>";
		
		$this->db->debug_queries = array();
		$this->db->queries = array();
		
		$this->_flush();

    }

    // check our CP database with another project that does the same :)
    // WooHoo! Check passed!!! 2023-02-05 Happy Birthday Juanga...
    public function check_github_ds_codigos_postales_ine() {
		
		$file = 'devel/github-inigoflores-ds-codigos-postales-ine-es/data/codigos_postales_municipiosid.csv';
		
		$lines = file($file);
		
		$_stripped = array_shift($lines);
		
		echo 'Stripped: '.$_stripped.'<br/>';

		if (! $this->provincias_ccaa) $this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();	
		
		$my_cps = [];
		$rows = $this->db->query("SELECT cp, id_municipio_cp FROM sp_geodb_ine_municipios_cp ORDER BY cp ASC");
		foreach($rows as $row) {
			if (!isset($my_cps[$row['cp']])) {
				$my_cps[$row['cp']] = [];
			}
			if (in_array($row['id_municipio_cp'], $my_cps[$row['cp']])) die('Oops... duplicated on my db?');
			$my_cps[$row['cp']][] = $row['id_municipio_cp'];
		}
		
		$nl = 0;
		$_flores_cps = [];
		foreach($lines as $line) {
			$n_line++;
			$line = trim($line);
			if (empty($line)) continue;
			
			$csv_line = str_getcsv($line, ',');
			
			$cp = $csv_line[0];
			$ine_idm = $csv_line[1];
			
			if (! ctype_digit($cp)) throw new Exception("Error, no cp number");
			if (! ctype_digit($ine_idm)) throw new Exception("Error, no ine_idm number");
			
			$_cp = ltrim($cp, '0');
			$_idp = ltrim(substr($ine_idm, 0, 2), '0');
			$_idm = $ine_idm; //ltrim($ine_idm, '0');
			$_ccaa = $this->provincias_ccaa[$_idp];
			
			$_idm = $_ccaa.$_idm;
			
			if (! isset($_flores_cps[$_cp])) {
				$_flores_cps[$_cp] = [];
			}
			$_flores_cps[$_cp][] = $_idm;
						
			if (!in_array($_idm, $my_cps[$_cp])) throw new Exception("idm not found on my base");
			
			if (isset($rows[$nl]['id_municipio_cp']) && $rows[$nl]['id_municipio_cp'] != $_idm) {
				//throw new Exception("Order check failed");
				throw new Exception($nl.' line does not match ['.$rows[$nl]['id_municipio_cp'].'] ['.$_idm.']');
			}  
			
			$nl++;
		}
		
		echo '<hr>';
		echo $nl.' lines. Done.<br/>';
		
    }
        
    // OJO! Hace TRUNCATE de la tabla
    public function import_ngbe() {

    	$year = date('Y');
    
		echo "<hr><strong>Importar entidades Geo Data CNIG</strong> Tabla: sp_geodb_ine_municipios_entidades_geo<br/>";

		if (! $this->provincias_ccaa) $this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		//if (! $this->provincias_isla) $this->provincias_isla = $this->mgeodb->get_provincias_islas_ids();

		$this->db->send("TRUNCATE sp_geodb_ine_municipios_entidades_geo");
		$this->db->send("TRUNCATE sp_geodb_ine_municipios_entidades_geo_data");
		$this->db->send("TRUNCATE sp_geodb_ine_municipios_entidades_tipos");
		
		foreach($this->provincias_ccaa as $id_provincia => $id_ccaa) {
		
			$filename = 'datos/CNIG/split/NGBE-P'.$id_provincia.'.txt';			
			
			$lines = file($filename);
			if (! $n_lines = count($lines)) {
				die('Unable to load: '.$filename);
			}
			
			echo "Archivo: {$filename} ({$n_lines} líneas)<br/>";
			
			$this->_flush();

			//if ($id_provincia != 30) continue;

			$values_geo = array();
			$values_geo_data = array();
			$total=0; 
			
			$tipos_arr = array();
			$n_line = 0;
			
			foreach($lines as $line) {
				$n_line++;
				$line = trim($line);
				if (empty($line))
					continue;
				$this->_encode($line);
				
				$id_municipio = 0;
				
				$csv_line = str_getcsv($line, ';', '"'); // 2023 fuckup, ahora son puntos y comas...

				if (! $assoc_arr = $this->_get_ngbe_array($csv_line, $n_line)) continue;
				
				extract($assoc_arr);

				//echo $line.'<hr>';
				
				// puede venir uno o varios municipios (CODIGOS INE)

				if (strpos($COD_INE, ',') !== FALSE) {
					$COD_INE = str_replace(' ', '', trim($COD_INE));
	            	//echo "COD_INE[{$COD_INE}] más de un municipio";
					$_COD_INE_LIST = explode(',', $COD_INE);
					$COD_INE_LIST = array($_COD_INE_LIST[0]); // solo el primero
				} else {
					$COD_INE_LIST = array($COD_INE);
				}
				
				//if ($n_line != 9078) continue;
				
				$_tmplist = array();
				foreach($COD_INE_LIST as $COD_INE) {
				
					if (empty($COD_INE)) continue;
					
					// id_municipio
		            $ine_id_provincia = substr($COD_INE, 0, 2);
		            $id_provincia = $ine_id_provincia;
		            settype($id_provincia, 'int');
		            settype($id_provincia, 'string');
					$id_ccaa = $this->provincias_ccaa[$id_provincia];
		            $ine_id_municipio = substr($COD_INE, 2, 3);
		            $id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;
		            $entidad_colectiva = substr($COD_INE, 5, 2);
		            $entidad_singular = substr($COD_INE, 7, 2);
		            $nucleo = substr($COD_INE, 9, 2);

		            if (! is_numeric($id_municipio)) {
						die('ERROR: NOT numeric ine_id_municipio: '.$id_municipio.' Linea #'.$n_line);
		            }
		            if (empty($id_municipio)) {
	            		echo "  ERROR: linea[{$n_line}] id[{$id}] COD_INE[{$COD_INE}] no se pudo resolver municipio<br/>";
	            		continue;
		            }

		            if (isset($_tmplist[$id_municipio])) continue;
		            
		            $_tmplist[$id_municipio] = true;
		            
					if (! $id_ccaa = $this->provincias_ccaa[$id_provincia]) {
	            		echo "  ERROR: linea[{$n_line}] id[{$id}] COD_INE[{$COD_INE}] no se pudo resolver id_ccaa para id_provincia[{$id_provincia}]<br/>";
	            		continue;
					}
					
            		$id_isla = $this->_get_id_municipios2isla($id_municipio);
		            		            
		            $id = (int)$id;
		            $codigo_ngbe = str_replace('.', '', $codigo_ngbe);
		            $tipo = $codigo_ngbe;
		            
		            $LATITUD_ETRS89 = str_replace(',', '.', trim($lat_etrs89_regcan95));
		            $LONGITUD_ETRS89 = str_replace(',', '.', trim($long_etrs89_regcan95));

		            /* if ($id == '2621140') {
	            		$aaa = 1;
		            } */
		            
		            if (empty($LATITUD_ETRS89) || empty($LONGITUD_ETRS89)) {
	            		echo "  ERROR: linea[{$n_line}] id[{$id}] COD_INE[{$COD_INE}] lat/long en blanco<br/>";
	            		continue;
		            }
		            
		            if ($hojamtn_25 == 'No disponible') {
						$hojamtn_25 = '';
		            }
		            
					$HOJA_MTN25_ETRS89 = $this->db->str2sql($hojamtn_25);

					$idioma = $this->_get_e_name($idioma_idg);
					
					$nombre = $this->_get_e_name($identificador_geografico);
					$nombre_largo = $this->_get_e_name($nombre_extendido);
					
					$nombre_alt = $this->_get_e_name($nombre_alternativo_2);
					$nombre_alt2 = $this->_get_e_name($nombre_alternativo_3);

					//$nombre_var = $this->_get_e_name($fuente_variante_1);
					//$nombre_var2 = $this->_get_e_name($fuente_variante_2);
					//$nombre_var3 = $this->_get_e_name($fuente_variante_3);
					
					$tipos[$tipo] = trim($codigo_ngbe_text);
					
					if (empty($nombre_largo) && empty($nombre_alt) && empty($nombre_alt2)) $nombre_tipo = '0';
					elseif (!empty($nombre_largo) && empty($nombre_alt) && empty($nombre_alt2)) $nombre_tipo = 1;
					elseif (!empty($nombre_largo) && !empty($nombre_alt) && empty($nombre_alt2)) $nombre_tipo = 2;
					elseif (!empty($nombre_largo) && !empty($nombre_alt) && !empty($nombre_alt2)) $nombre_tipo = 3;
					else $nombre_tipo = 4;
					
					$values_geo[]="({$id}, {$id_municipio}, {$tipo}, {$id_provincia}, {$id_isla}, {$id_ccaa})";
					$values_geo_data[]="({$id}, {$tipo}, '{$idioma}', '{$nombre}', {$nombre_tipo}, '{$nombre_largo}', '{$nombre_alt}', '{$nombre_alt2}', {$LATITUD_ETRS89}, {$LONGITUD_ETRS89}, '{$HOJA_MTN25_ETRS89}')";
				}

				$total++;
				
			}
			
			echo "{$total} entradas leídas, ".count($values_geo)." rows preparadas.<br/>";

			$this->_flush();

			$n_inserted = $this->db->send("
				REPLACE sp_geodb_ine_municipios_entidades_geo (id_entidad_geo, id_municipio, tipo, id_provincia, id_isla, id_ccaa) VALUES 
			".join(',', $values_geo));

			echo "Insertadas {$n_inserted} filas en sp_geodb_ine_municipios_geo. ";
			
			$n_inserted = $this->db->send("
				REPLACE sp_geodb_ine_municipios_entidades_geo_data (id_entidad_geo, tipo, idioma, nombre, nombre_tipo, nombre_largo, nombre_alt, nombre_alt2, latitud, longitud, hoja_mtn25) VALUES 
			".join(',', $values_geo_data));

			echo "Insertadas {$n_inserted} filas en sp_geodb_ine_municipios_geo_data.<br/><br/>";

			foreach($tipos as $tipo => $nombre) {
			
				if (empty($nombre)) continue;
				
				$parts = explode('.', $nombre);
				$n_parts = count($parts);
				
				$nombre1 = !empty($parts[0]) ? $parts[0] : '';
				$nombre2 = !empty($parts[1]) ? $parts[1] : '';
				$nombre3 = !empty($parts[2]) ? $parts[2] : '';
				
				$this->db->send("REPLACE sp_geodb_ine_municipios_entidades_tipos (id_tipo, tipo_nombre, nombre1, nombre2, nombre3) VALUES ({$tipo}, {$n_parts}, '{$nombre1}', '{$nombre2}', '{$nombre3}')");
			}

			$this->_flush();
			
			$this->db->debug_queries = array();
			$this->db->queries = array();
		}
				
		$this->_flush();
        echo "<hr>Done!";
    }
    
    private function _get_ngbe_array($csv_line, $n_line) {
    	
		//$tmp_arr = explode(',', $line); // ";"

		if (count($csv_line) != 40) { // 40 mierdas para 2019 y 2020 go go go
	        echo "  ERROR: linea[{$n_line}] nº de columnas inesperado (".count($csv_line).") (quiero 40) (,) {$csv_line[40]}<br/>";
	        return [];
		}

		// 40 columnas		
		$_var_list = [
			'id',
			'nombre_extendido',			// 2020
			'identificador_geografico',  // 2020
			'nombre_alternativo_2',		// 2020
			'nombre_alternativo_3',		// 2020
			'nombre_variante_1',
			'nombre_variante_2',
			'nombre_variante_3',
			'fuente_extendido',
			'fuente_idg',
			'fuente_alternativo_2',
			'fuente_alternativo_3',
			'fuente_variante_1',
			'fuente_variante_2',
			'fuente_variante_3',
			'idioma_idg',
			'estatus_idg',
			'long_etrs89_regcan95',
			'lat_etrs89_regcan95',
			'huso_etrs89_regcan95',
			'x_utm_etrs89_regcan95',
			'y_utm_etrs89_regcan95',
			'hojamtn_25',
			'COD_INE',	// 2023 ahora es "codigo_ine"
			'codigo_ngbe',
			'codigo_ngbe_text',
			'idioma_alternativo_2', // 2023 - era: '_idioma_alter2',			
			'idioma_alternativo_3', // 2023 - era: '_idioma_alter3',			
			'idioma_variante_1', // 2023 - era: '_idioma_variante1',			
			'idioma_variante_2', // 2023 - era: '_idioma_variante2',			
			'idioma_variante_3', // 2023 - era: '_idioma_variante3',			
			'idioma_extendido', // 2023 - era: '_idioma_extendido',
			'estatus_extendido', // 2023 - era: '_estatus_ext',
			'estatus_alternativo_2', // 2023 - era: '_estatus_alt2',				
			'estatus_alternativo_3', // 2023 - era: '_estatus_alt3',				
			'estatus_variante_1', // 2023 - era: _estatus_var1',				
			'estatus_variante_2', // 2023 - era: '_estatus_var2',				
			'estatus_variante_3', // 2023 - era: '_estatus_var3',
			'provincias_nombre', // 2023 - era: '_provincias_nombre',						
			'provincias_id', // 2023 - era: '_provincias_id',		// + 2020 fuck-up
		];
		
		$assoc = [];
		foreach($csv_line as $idx => $value) {
			$assoc[$_var_list[$idx]] = $value;
		}
		
		return $assoc;
    }
    
    private function _get_e_name($name) {
    	$name = trim($name);
    	if (empty($name)) return $name;
    	return $this->db->str2sql($name);
    }
    
    // OJO! Hace TRUNCATE de la tabla
    public function import_callejero() {

    	$year = date('Y');
    
		if (! $this->provincias_ccaa) $this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		if (! $this->provincias_isla) $this->provincias_isla = $this->mgeodb->get_provincias_islas_ids();

		echo "<hr><strong>Callejero.</strong> Tablas: sp_geodb_ine_callejero, sp_geodb_ine_callejero_cp, sp_geodb_ine_callejero_cun, sp_geodb_ine_callejero_cvia_cun<hr>";

		// Procesa datos para sp_geodb_ine_callejero_cvia_cun 
		echo "<br/><strong>Importar callejero a tabla: sp_geodb_ine_callejero_cvia_cun</strong><br/>";
		$row = $this->db->query_row("SELECT COUNT(*) AS cuantos FROM sp_geodb_ine_callejero_cvia_cun");
		if (! $row['cuantos']) {
			
			echo "<strong>Importar CUNs</strong><br/>";
			
			$this->db->send("TRUNCATE sp_geodb_ine_callejero_cvia_cun");

			echo "Insertando en tabla: sp_geodb_ine_callejero_cvia_cun ...<br/>";

			foreach($this->provincias_ccaa as $id_provincia => $id_ccaa) {
				
				$n_inserted = 0;
				$this->db->debug_queries = array();
				$this->db->queries = array();		
				
				$this->_process_tramos('CUN', $id_provincia);

				$this->_flush();
				$values = array();
				$n=0;		
				foreach($this->data as $id_municipio => $cvia_arr) {
					$id_municipio = substr($id_municipio, 1);
					foreach($cvia_arr as $cvia => $CUN_arr) {
						$cvia = substr($cvia, 1);				
						foreach($CUN_arr as $CUN => $minmax_arr) {
							$CUN = substr($CUN, 1);

							$pares = ''; $impares = ''; $max=0;
							if (! empty($minmax_arr)) {						
								$pares = $minmax_arr['p']['m'] . '-' . $minmax_arr['p']['x'];
								$impares = $minmax_arr['i']['m'] . '-' . $minmax_arr['i']['x'];
								if ($pares == '-') $pares = ''; 
								if ($impares == '-') $impares = '';
								
								// obtiene el máximo número de pares o impares
								$max = max($minmax_arr['p']['x'], $minmax_arr['i']['x']);
							}
							if (! $max) {
								$max = '0';
							} 						
							 
							$values[]="({$id_municipio}, {$cvia}, {$CUN}, '{$pares}', '{$impares}', '{$max}')";
							$n++;
							if ($n >= 25000) {
								$n=0;
								$n_inserted += $this->db->send("INSERT sp_geodb_ine_callejero_cvia_cun (id_municipio, cvia, CUN, pares, impares, max) VALUES ".join(', ', $values), true, false); // no timer
								unset($values);
								$values = array();		
								$this->db->debug_queries = array();
								$this->db->queries = array();
							}				
						}
					} 						
				}

				if (! empty($values)) {
					$n_inserted += $this->db->send("INSERT sp_geodb_ine_callejero_cvia_cun (id_municipio, cvia, CUN, pares, impares, max) VALUES ".join(', ', $values), true, false); // no timer
					unset($values);
					$values = array();
				}
				
				echo "Insertadas {$n_inserted} filas en sp_geodb_ine_callejero_cvia_cun.<br/>";
			}
			

			$this->db->debug_queries = array();
			$this->db->queries = array();		
			unset($this->data);
			$this->data = array();
			$values = array();
		}
		else {
			echo "Saltando proceso de tabla: <strong>sp_geodb_ine_callejero_cvia_cun</strong> al no estar vacía.<br/><br/>";
		}
				
		// Procesa datos para sp_geodb_ine_callejero
		
		echo "<br/><strong>Importar callejero a tabla: sp_geodb_ine_callejero</strong><br/>";

		$row = $this->db->query_row("SELECT COUNT(*) AS cuantos FROM sp_geodb_ine_callejero");
		if (! $row['cuantos']) {
			
			$this->db->send("TRUNCATE sp_geodb_ine_callejero");
			$this->db->send("TRUNCATE sp_geodb_ine_callejero_cp");

			foreach($this->provincias_ccaa as $filter_id_provincia => $id_ccaa) {
			 
				$this->db->debug_queries = array();
				$this->db->queries = array();

				$this->_process_tramos('CPCVIA', $filter_id_provincia);
				
				$filename = "datos/INE-callejero-censo/{$year}/INE-VIAS-NAL.txt";
				if ( !$handle = fopen($filename, "r")) {
					die('ERROR: Unable to open: '.$filename);
				}

				$this->data = array();

				echo "Procesando INE-VIAS-NAL.txt para provincia [{$filter_id_provincia}] ...<br/>";
				$this->_flush();

				$values = array();
				$values_cp = array();
				$total=0; $n=0; $nn=0; $n_affected=0; $n_affected_cp=0; $no_cp=0;
				while (($line = fgets($handle)) !== false) {
						
					$id_provincia = substr($line, 0, 2);
					if ($filter_id_provincia &&  $filter_id_provincia != (int)$id_provincia) continue;
					//if ($id_provincia != '07' || $id_municipio != '040') continue;

					$line = trim($line);
					if (empty($line))
						continue;

					$id_municipio = substr($line, 2, 3);
					$id_provincia = trim($id_provincia);
					$id_municipio = trim($id_municipio);

					$ine_id_provincia = $id_provincia;
					$ine_id_municipio = $id_municipio;
					settype($id_provincia, 'int');
					settype($id_municipio, 'int');
					// set back to string
					settype($id_provincia, 'string');
					settype($id_municipio, 'string');

					$id_ccaa = $this->provincias_ccaa[$id_provincia];

					$ine_id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;

            		$id_isla = $this->_get_id_municipios2isla($ine_id_municipio);

					$cvia = trim(substr($line, 5, 5));
					$ine_cvia = $cvia;

					//if ($ine_cvia != '00110') continue;

					$cvia_full = $ine_id_municipio.$ine_cvia;
					$s_cvia_full = "f{$cvia_full}";
					settype($cvia, 'int');
					settype($cvia, 'string');
					
					//CALLE0SAN MARTIN                                        SAN MARTIN               
					$tvia = ucfirst(trim(strtolower(substr($line, 27, 5))));
					if ($tvia == '.') {
						$tvia = '';
					}

					$this->_encode($line);

					$nombre = trim(substr($line, 33, 50));
					$nombre_corto = trim(substr($line, 83, 25));

					$tipo_nombre = ($nombre === $nombre_corto) ? '0' : '1';
					
					$nombre = $this->db->str2sql($nombre);
					$nombre_corto = $this->db->str2sql($nombre_corto);
					$tvia = $this->db->str2sql($tvia);
					
					$urlized = urlize($nombre); 
					$urlized_corto = urlize($nombre_corto); 
					
					/*
					// Hemos comprobado que NO se produce colisión en este archivo			
					if (isset($this->data[$cvia_full])) {
						die("Colisión: {$cvia_full} con datos: ".join(', ', $this->data[$cvia_full]));
					}
					*/
					if (! isset($this->cp[$s_cvia_full])) {
						//echo "No se encontraron CPs en Tramos Censo para el municipio [{$ine_id_municipio}] para [{$tvia}] [{$nombre}] [{$nombre_corto}] cvia: [{$ine_cvia}] cvia_full[{$cvia_full}]<br/>";
						$cp = '0';
						$pares = '';
						$impares = '';
						$no_cp++;
												
						$values_cp[]="({$ine_id_municipio}, {$cp}, '{$cvia}', '{$pares}', '{$impares}')";
						$values[]="({$ine_id_municipio}, '{$cvia}', {$id_provincia}, {$id_isla}, {$id_ccaa}, '{$tvia}', '{$nombre}', '{$tipo_nombre}', '{$nombre_corto}', '{$urlized}', '{$urlized_corto}')";
					}
					else {
						$s_cp_arr = $this->cp[$s_cvia_full];

						foreach($s_cp_arr as $s_cp => $crap) {
							//$s_cp = key($this->cp[$s_cvia_full]); // una calle, un solo CP
							$cp = substr($s_cp, 1);
							
							$pares = ''; $impares = '';
							if (! empty($this->cp[$s_cvia_full][$s_cp])) {						
								$pares = $this->cp[$s_cvia_full][$s_cp]['p']['m'] . '-' . $this->cp[$s_cvia_full][$s_cp]['p']['x']; 
								$impares = $this->cp[$s_cvia_full][$s_cp]['i']['m'] . '-' . $this->cp[$s_cvia_full][$s_cp]['i']['x'];
								if ($pares == '-') $pares = ''; 
								if ($impares == '-') $impares = ''; 
							}
												
							$values_cp[]="({$ine_id_municipio}, {$cp}, '{$cvia}', '{$pares}', '{$impares}')";
							if (! isset($this->data[$s_cvia_full])) {
								$values[]="({$ine_id_municipio}, '{$cvia}', {$id_provincia}, {$id_isla}, {$id_ccaa}, '{$tvia}', '{$nombre}', '{$tipo_nombre}', '{$nombre_corto}', '{$urlized}', '{$urlized_corto}')";
								$this->data[$s_cvia_full] = 1;
							}
						}
					}
								
					if ($nn >= 15000) {
						$n_affected += $this->db->send("INSERT sp_geodb_ine_callejero (id_municipio_callejero, cvia, id_provincia, id_isla, id_ccaa, tvia, nombre, tipo_nombre, nombre_corto, urlized, urlized_corto) VALUES ".join(', ', $values), true, false);
						$n_affected_cp += $this->db->send("INSERT sp_geodb_ine_callejero_cp (id_municipio_callejero, cp, cvia, pares, impares) VALUES ".join(', ', $values_cp), true, false);
						$this->db->debug_queries = array();
						$this->db->queries = array();
						$values = array();
						$values_cp = array();
						//echo "Insertadas {$n_affected} filas ... <br/>";
						//$this->_flush();				
						$nn=0;
					}
					
					$total++; $n++; $nn++;
					if ($n >= 100000) {
						//echo "Procesadas {$total} líneas, insertadas {$n_affected} filas ... <br/>";
						$n=0;
						//$this->_flush();
					}
				}
				fclose($handle);

				if (! empty($values)) {
					$n_affected += $this->db->send("INSERT sp_geodb_ine_callejero (id_municipio_callejero, cvia, id_provincia, id_isla, id_ccaa, tvia, nombre, tipo_nombre, nombre_corto, urlized, urlized_corto) VALUES ".join(', ', $values), true, false);
					$this->db->debug_queries = array();
					$this->db->queries = array();
					$values = array();
					echo "Insertadas {$n_affected} filas en sp_geodb_ine_callejero<br/>";
				}
				if (! empty($values_cp)) {
					$n_affected_cp += $this->db->send("INSERT sp_geodb_ine_callejero_cp (id_municipio_callejero, cp, cvia, pares, impares) VALUES ".join(', ', $values_cp), true, false);
					$this->db->debug_queries = array();
					$this->db->queries = array();
					$values_cp = array();			
					echo "Insertadas {$n_affected_cp} filas en sp_geodb_ine_callejero_cp<br/>";
				}
				
				echo "<br/>Procesadas {$total} líneas, insertadas {$n_affected} filas en sp_geodb_ine_callejero y {$n_affected_cp} en sp_geodb_ine_callejero_cp<br/>";
				if ($no_cp) {
					echo "Errores: {$no_cp} calles sin CP.<br/><br/>";
				}
			}

			$this->db->debug_queries = array();
			$this->db->queries = array();
			$this->data = NULL;
			$this->cp = NULL;
		}
		else {
			echo "Saltando proceso de tabla: <strong>sp_geodb_ine_callejero</strong> al no estar vacía.<br/><br/>";			
		}
		
		$this->_flush();
    }

    private function _process_tramos($action, $filter_id_provincia = null) {

    	$year = date('Y');
    
		if ($filter_id_provincia)    	
			$filename = "datos/INE-callejero-censo/split/INE-CENSO-TRAMOS-NAL-P{$filter_id_provincia}.txt";
		else
			$filename = "datos/INE-callejero-censo/{$year}/INE-CENSO-TRAMOS-NAL.txt";
		
		if ( !$handle = fopen($filename, "r")) {
			die('ERROR: Unable to open: '.$filename.' - Need to _split_censo() first?');
		}
		echo "*** Procesando {$filename} ...<br/>";
		$this->_flush();

		switch($action) {
			case 'CUN':
				$this->data = array();
				break;
			case 'CPCVIA':
				$this->cp = array();
				break;
			default:
				die("Unexpected action[{$action}] at _process_tramos()");
				break;
		}
		
		$total=0; $n=0;
		while (($line = fgets($handle)) !== false) {

			$id_provincia = substr($line, 0, 2);
			if ($filter_id_provincia && (int)$id_provincia != $filter_id_provincia) continue;			
			//if ($id_provincia != '07' || $id_municipio != '040') continue;

			$line = trim($line);
			if (empty($line))
				continue;

			$id_municipio = substr($line, 2, 3);
			
			$cp = substr($line, 42, 5);
			$s_cp = "c{$cp}";

			$id_provincia = trim($id_provincia);
			$id_municipio = trim($id_municipio);

			$ine_id_provincia = $id_provincia;
			$ine_id_municipio = $id_municipio;

			settype($id_provincia, 'int');
			settype($id_municipio, 'int');
			settype($cp, 'int');
			// set back to string
			settype($id_provincia, 'string');
			settype($id_municipio, 'string');
			settype($cp, 'string');

			$id_ccaa = $this->provincias_ccaa[$id_provincia];

			$ine_id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;
			$s_ine_id_municipio = "m{$ine_id_municipio}";			
			
			$cvia = trim(substr($line, 20, 5));
			$ine_cvia = $cvia;
			
			//if ($ine_cvia != '00110') continue;
			
			$cvia_full = $ine_id_municipio.$ine_cvia;
			$s_cvia_full = "f{$cvia_full}";
			settype($cvia, 'int');
			settype($cvia, 'string');
			$s_cvia = "v{$cvia}";
			
			$tipo_minmax = substr($line, 262, 1);  
			$num_min = (int)trim(substr($line, 263, 4));
			$num_max = (int)trim(substr($line, 268, 4));
			settype($num_min, 'string');
			settype($num_max, 'string');
			
			$CUN = (int)trim(substr($line, 78, 7));
			settype($CUN, 'string');			
			$s_CUN = "c{$CUN}";

			switch($action)
			{
				case 'CPCVIA': 
				{
					if (isset($this->cp[$s_cvia_full][$s_cp])) {

						if ($tipo_minmax)
							$this->_callejero_minmax($this->cp[$s_cvia_full][$s_cp], $tipo_minmax, $num_min, $num_max);						
					}
					else {
						$this->cp[$s_cvia_full][$s_cp] = array();
						if ($tipo_minmax)
							$this->_callejero_minmax($this->cp[$s_cvia_full][$s_cp], $tipo_minmax, $num_min, $num_max);						
					}
					//$this->cp[$s_cvia_full][$s_cp]['tramos']++;					
				}
				break;
				
				case 'CUN':
				{
					if (isset($this->data[$s_ine_id_municipio][$s_cvia][$s_CUN])) {
						
						if ($tipo_minmax)
							$this->_callejero_minmax($this->data[$s_ine_id_municipio][$s_cvia][$s_CUN], $tipo_minmax, $num_min, $num_max);																		
					}
					else {
						$this->data[$s_ine_id_municipio][$s_cvia][$s_CUN] = array();
						if ($tipo_minmax)
							$this->_callejero_minmax($this->data[$s_ine_id_municipio][$s_cvia][$s_CUN], $tipo_minmax, $num_min, $num_max);																		
					}					
				}
				break;
			}			
												
			$total++; $n++;
			if ($n >= 250000) {
				echo "Procesadas {$total} líneas... <br/>";
				$n=0;
				$this->_flush();
			}
		}
		fclose($handle);
		echo "Procesadas {$total} líneas.<br/>";
		
		switch($action)
		{
			case 'CPCVIA':
			{
				$n_vias = count($this->cp);
				$n_cps = 0;
				foreach($this->cp as $cvia_full => $cp_arr) {
					$n_cps += count($cp_arr);
				}
				print "Contados {$n_vias} vías, {$n_cps} códigos postales.<br/>";
			}	
			break;
					
			case 'CUN':
			{
				$n_municipios = 0; $n_vias = 0; $n_cuns = 0;
				foreach($this->data as $id_municipio => $cvia_arr) {
					$n_municipios++;
					foreach($cvia_arr as $cvia => $CUN_arr) {
						$n_vias++;
						foreach($CUN_arr as $CUN => $arr) {
							$n_cuns++;
						}
					}
				}
				print "Contados {$n_municipios} municipios, {$n_vias} vías, {$n_cuns} CUNs.<br/>";				
			}
			break;
		}
		$this->_flush();		
    }

	private function _callejero_minmax(&$cur, $tipo, $num_min, $num_max) {
		switch($tipo) {
			case 1: $tipo='i'; $check = 9999; break;
			case 2: $tipo='p'; $check = 9998; break;
			default: die("Unexpected tipo: [{$tipo}] at _callejero_minmax() min[{$num_min}] max[{$num_max}]"); break;
		}
		if (! isset($cur[$tipo]['m'])) {
			$cur[$tipo]['m'] = $num_min;
		}
		if (! isset($cur[$tipo]['x'])) {
			if ($num_max < $check) {
				$cur[$tipo]['x'] = $num_max;
			} else {
				$cur[$tipo]['x'] = 0;				
			}
		}
		
		// soporta los 330 - 9998, habiendo 284 - 328 por ejemplo
		if ($num_min > $cur[$tipo]['x']) // min mayor que max
			$cur[$tipo]['x'] = $num_min;

		if ($num_min < $cur[$tipo]['m']) // min menor que min
			$cur[$tipo]['m'] = $num_min;
			
		if ($num_max > $cur[$tipo]['x'] && $num_max < $check) // max mayor que max, pero no el tope
			$cur[$tipo]['x'] = $num_max;

				
	}
    
    // OJO! Hace TRUNCATE de la tabla
    public function import_codigospostales() {

    	$year = date('Y');
		if (! $this->provincias_ccaa) $this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		//if (! $this->provincias_isla) $this->provincias_isla = $this->mgeodb->get_provincias_islas_ids();

		echo "<hr><strong>Códigos Postales.</strong> Tabla: sp_geodb_ine_municipios_cp ";
		$filename = 'datos/INE-callejero-censo/'.$year.'/INE-CENSO-TRAMOS-NAL.txt';
		if ( !$handle = fopen($filename, "r")) {
			die('ERROR: Unable to open: '.$filename);
		}
		$this->data = array();
		$this->cp = array();

		echo "Procesando {$filename} ...<br/>";
		$this->_flush();

		$total=0; $n=0;
		while (($line = fgets($handle)) !== false) {
			$line = trim($line);
			if (empty($line))
				continue;

			$id_provincia = substr($line, 0, 2);
			
			//if ($id_provincia !== '07') continue;
			
			$id_municipio = substr($line, 2, 3);
			$cp = substr($line, 42, 5);

			$id_provincia = trim($id_provincia);
			$id_municipio = trim($id_municipio);

			$ine_id_provincia = $id_provincia;
			$ine_id_municipio = $id_municipio;

			settype($id_provincia, 'int');
			settype($id_municipio, 'int');
			settype($cp, 'int');
			// set back to string
			settype($id_provincia, 'string');
			settype($id_municipio, 'string');
			settype($cp, 'string');

			$id_ccaa = $this->provincias_ccaa[$id_provincia];

			$ine_id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;

            $id_isla = $this->_get_id_municipios2isla($ine_id_municipio);

			$cvia = trim(substr($line, 20, 5));
			$ine_cvia = $cvia;		
                                    
			if (! isset($this->cp[$cp][$ine_id_municipio][$ine_cvia])) {
				$this->cp[$cp][$ine_id_municipio][$ine_cvia] = array();
				$this->cp[$cp][$ine_id_municipio][$ine_cvia]['idp'] = $id_provincia;
				$this->cp[$cp][$ine_id_municipio][$ine_cvia]['idi'] = $id_isla;
				$this->cp[$cp][$ine_id_municipio][$ine_cvia]['idc'] = $id_ccaa;
			}			
			
			$total++; $n++;
			if ($n >= 250000) {
				echo "Procesadas {$total} líneas... <br/>";
				$n=0;
				$this->_flush();
			}			
		}
		fclose($handle);

		echo "Procesadas {$total} líneas ...<br/>";
		echo "Insertando en DB ".count($this->cp)." códigos postales y sus municipios ...<br/>";
		$this->_flush();

		$this->db->send("TRUNCATE sp_geodb_ine_municipios_cp");

		$values = array();
		$n = 0;
		$filas = 0;
		$n_inserted = 0;
		foreach($this->cp as $cp => $municipios) {
			$n_municipios = count($municipios);
			foreach($municipios as $ine_id_municipio => $cvias) {
				$n_calles = count($cvias);
				foreach($cvias as $cvia => $info) {
					$id_provincia = $info['idp'];					
					$id_isla = $info['idi'];					
					$id_ccaa = $info['idc'];
					break;
				}
				$values[]="({$cp}, {$ine_id_municipio}, {$id_provincia}, {$id_isla}, {$id_ccaa}, {$n_calles}, {$n_municipios})";
				$filas++;
			}
			$n++;
			if ($n == 500) {
				$n_inserted += $this->db->send("
					INSERT sp_geodb_ine_municipios_cp (cp, id_municipio_cp, id_provincia, id_isla, id_ccaa, num_calles, num_municipios) 
					VALUES ".join(',', $values));
				//echo "{$n_inserted} filas insertadas.<br/>";
				$this->_flush();
				$values = array();
				$n=0;
			}
		}
		if (!empty($values)) {
			$n_inserted += $this->db->send("INSERT sp_geodb_ine_municipios_cp (cp, id_municipio_cp, id_provincia, id_isla, id_ccaa, num_calles, num_municipios) VALUES ".join(',', $values));
		}

		$this->db->debug_queries = array();
		$this->db->queries = array();	
		$this->data = NULL;
		$this->cp = NULL;
		
		echo "Insertadas {$n_inserted} filas de CP / id_municipio en sp_geodb_ine_municipios_cp<br/><br/>";
		$this->_flush();
    }

	// OJO! Hace TRUNCATE
	public function	import_municipios() {

		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		echo "<hr><strong>Municipios</strong><hr> Tabla: sp_geodb_ine_municipios<hr>";

		$this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		$this->provincias_urlized = $this->mgeodb->get_provincias_ids_urlized();

		$this->data = array();

		// rellena $this->data[$ine_id_municipio] con los municipios de las islas
		$this->_get_municipios_islas();
		// termina de rellenar $this->data[$ine_id_municipio] con todos los municipios del resto de España
		$this->_get_municipios_todos();

		if (! count($this->data)) {
			die('ERROR: Unable to get municipios?');
		}

		echo "Cargados ".count($this->data)." municipios.<br/>";

		if (! ksort($this->data)) {
			die('ERROR en ksorting datos de municipios');
		}

		$this->db->send("TRUNCATE sp_geodb_ine_municipios");

		$values = array();
		$u = array();
		foreach($this->data as $ine_id_municipio => $value_arr) {

			// checkear urlized únicos
			list(
				$ine_id_municipio,
				$id_ccaa,
				$id_provincia,
				$id_isla,
				$dc,
				$nombre_municipio,
				$nombre_corto,
				$nombre_alt,
				$urlized,
				$nombre_corto_urlized,
				$nombre_alt_urlized
			) = $value_arr;

            $urlized = trim(str_replace("'", '', $urlized));
            $nombre_corto_urlized = trim(str_replace("'", '', $nombre_corto_urlized));
            $nombre_alt_urlized = trim(str_replace("'", '', $nombre_alt_urlized));

            foreach(array('urlized', 'nombre_corto_urlized', 'nombre_alt_urlized') as $tipo) {
				if (isset($u[$tipo][$$tipo])) {
					$nombre_provincia = $this->provincias_urlized[$id_provincia];
					$nuevo = $$tipo.'-'.$nombre_provincia;
					if (isset($u[$tipo][$nuevo])) {
						die("Colisión: {$tipo} tried[{$$tipo}] tried[{$nuevo}]");
					}
					else {
						$u[$tipo][$nuevo]=1;
						$$tipo = $nuevo;
					}
				}
				else $u[$tipo][$$tipo]=1;
            }

            if ($nombre_municipio === $nombre_corto && $nombre_municipio === $nombre_alt) {
				$tipo_nombre = '0';
            }
            elseif (strstr($nombre_municipio,'/') && strstr($nombre_municipio,',')) {
            	$tipo_nombre = 3;
			}
            elseif (strstr($nombre_municipio,'/')) {
				$tipo_nombre = 1;
            }
            elseif (strstr($nombre_municipio,',')) {
				$tipo_nombre = 2;
			}
			else $tipo_nombre = 4; // no debería haber de este tipo


			$nombre_municipio = $this->db->str2sql($nombre_municipio);
			$nombre_corto = $this->db->str2sql($nombre_corto);
			$nombre_alt = $this->db->str2sql($nombre_alt);

			$value_str = "({$ine_id_municipio}, {$id_ccaa}, {$id_provincia}, {$id_isla}, {$dc}, '{$nombre_municipio}', {$tipo_nombre}, '{$nombre_corto}', '{$nombre_alt}', '{$urlized}', '{$nombre_corto_urlized}', '{$nombre_alt_urlized}')";

			$values[]=$value_str;
		}
		$n_affected = $this->db->send("INSERT sp_geodb_ine_municipios (id_municipio, id_ccaa, id_provincia, id_isla, dc, nombre, tipo_nombre, nombre_corto, nombre_alt, urlized, urlized_corto, urlized_alt) VALUES ".join(', ', $values), NoDieOnErrors);

		$this->db->debug_queries = array();
		$this->db->queries = array();				
		$this->data = NULL;
		
		echo "Insertadas {$n_affected} filas en sp_geodb_ine_municipios.<br/><br/>";
	}
	
	public function import_ccaa() {

		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		echo "<hr><strong>CCAA</strong><hr>";
		$lines = file('datos/INE-txt-codigos/INE-ccaa.txt');
		$values = array();
		foreach($lines as $line) {
			$this->_encode($line);
			$line = trim($line);
			if (empty($line))
				continue;

			list($id, $ccaa_alt) = explode(' ', $line, 2);
			$id = trim($id);
			$ccaa_alt = trim($ccaa_alt);
			$ccaa = $ccaa_alt;
			settype($id, 'int');
			if (strchr($ccaa_alt, ',')) {
				list($segundo, $primero) = explode(', ', $ccaa_alt, 2);
				$ccaa = $primero.' '.$segundo;
			}
			$test = urlize($ccaa);
			switch($test) {
				case 'principado-de-asturias': $ccaa_corto = 'Asturias'; break;
				case 'illes-balears': $ccaa_corto = 'Islas Baleares'; break;
				case 'comunitat-valenciana': $ccaa_corto = 'Valencia'; break;
				case 'comunidad-de-madrid': $ccaa_corto = 'Madrid'; break;
				case 'region-de-murcia': $ccaa_corto = 'Murcia'; break;
				case 'comunidad-foral-de-navarra': $ccaa_corto = 'Navarra'; break;
				default: $ccaa_corto = $ccaa; break;
			}
			$urlized_alt = urlize($ccaa_corto);
			$urlized = urlize($ccaa);

			echo "[{$id}] [{$ccaa}] [{$ccaa_corto}] [{$ccaa_alt}] [{$urlized}] [{$urlized_alt}]<br/>";

			$id = $this->db->str2sql($id);
			$ccaa = $this->db->str2sql($ccaa);
			$ccaa_corto = $this->db->str2sql($ccaa_corto);
			$ccaa_alt = $this->db->str2sql($ccaa_alt);
			$urlized = $this->db->str2sql($urlized);
			$urlized_alt = $this->db->str2sql($urlized_alt);

			$values[] = "({$id}, '{$ccaa}', '{$ccaa_corto}', '{$ccaa_alt}', '{$urlized}', '{$urlized_alt}')";

		}

		$this->db->send("INSERT sp_geodb_ine_ccaa (id_ccaa, nombre, nombre_corto, nombre_alt, urlized, urlized_alt) VALUES ".join(',', $values), NoDieOnErrors);
	}

	public function	import_provincias() {

		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		echo "<hr><strong>Provincias</strong><hr>";
		$lines = file('datos/INE-txt-codigos/INE-provincias.txt');
		$values = array();
		foreach($lines as $line) {
			$this->_encode($line);
			$line = trim($line);
			if (empty($line))
				continue;

			list($id, $provincia) = explode(' ', $line, 2);
			$id = trim($id);
			$provincia = trim($provincia);
			$provincia_alt = $provincia;
			settype($id, 'int');
			$provincia_corto = $provincia_alt;
			if (strchr($provincia, '/')) {
				list($primero, $segundo) = explode('/', $provincia, 2);
				$provincia_corto = $primero;
				switch($provincia_corto) {
					case 'Araba': $provincia_corto = 'Álava'; break;
				}
			}
			if (strchr($provincia, ',')) {
				list($segundo, $primero) = explode(', ', $provincia, 2);
				$provincia_alt = $primero.' '.$segundo;
				switch($provincia_alt) {
					case 'Illes Balears': $provincia_corto = 'Islas Baleares'; break;
					case 'A Coruña': $provincia_corto = 'A Coruña'; break;
					case 'Las Palmas': $provincia_corto = 'Las Palmas'; break;
					case 'La Rioja': $provincia_corto = 'La Rioja';
				}
			}
			switch($provincia_alt) {
				case 'Las Palmas': $provincia_alt = 'Las Palmas de Gran Canaria'; break;
				case 'Bizkaia': $provincia_corto = 'Vizcaya'; break;
				case 'Gipuzkoa': $provincia_corto = 'Guipúzcoa'; break;
			}
			$test = urlize($provincia_corto);
			switch($test) {
				default: break;
			}
			$urlized_alt = urlize($provincia_corto);
			$urlized = urlize($provincia_alt);

			echo "[{$id}] [{$provincia}] [{$provincia_alt}] [{$provincia_corto}] [{$urlized}] [{$urlized_alt}]<br/>";

			$id = $this->db->str2sql($id);
			$provincia = $this->db->str2sql($provincia);
			$provincia_corto = $this->db->str2sql($provincia_corto);
			$provincia_alt = $this->db->str2sql($provincia_alt);
			$urlized = $this->db->str2sql($urlized);
			$urlized_alt = $this->db->str2sql($urlized_alt);

			$values[] = "({$id}, '{$provincia}', '{$provincia_alt}', '{$provincia_corto}', '{$urlized}', '{$urlized_alt}')";

		}

		$this->db->send("INSERT sp_geodb_ine_provincias (id_provincia, nombre, nombre_corto, nombre_alt, urlized, urlized_alt) VALUES ".join(',', $values), NoDieOnErrors);
	}

	private function _get_municipios_todos() {

		$year = date('Y');
		$year2 = date('y');
		
		// TODOS LOS MUNICIPIOS
		$master_file = 'datos/INE-txt-codigos/'.$year.'/INE-'.$year2.'codmun-diccionario.txt'; // ahora viene "completo" en diccionarioYY.xlsx
		
		$lines = file($master_file);
		
		array_shift($lines); array_shift($lines);

        if (empty($lines)) {
			die('Unable to load file at _get_municipios_todos(): '.$master_file);
        }

		foreach($lines as $line) {
			$this->_encode($line);
			$line = trim($line);
			if (empty($line))
				continue;

			list($file_id_ccaa, $id_provincia, $id_municipio, $dc, $nombre_municipio) = explode("\t", $line);
			$file_id_ccaa = (int)$file_id_ccaa;

			$id_provincia = trim($id_provincia);
			$id_municipio = trim($id_municipio);
			$dc = trim($dc);
			$nombre_municipio = trim($nombre_municipio);

			$ine_id_provincia = $id_provincia;
			$ine_id_municipio = $id_municipio;
			settype($id_provincia, 'int');
			settype($id_municipio, 'int');
			settype($dc, 'int');

			// set string again
			settype($id_provincia, 'string');
			settype($id_municipio, 'string');

			list(
				$nombre_municipio,
				$urlized,
				$nombre_corto,
				$nombre_corto_urlized,
				$nombre_alt,
				$nombre_alt_urlized
			) = $this->_resolve_nombre_municipio($nombre_municipio);

			$id_ccaa = $this->provincias_ccaa[$id_provincia];
			if ($file_id_ccaa != $id_ccaa) {
				die("CCAA mismatch: {$file_id_ccaa} != {$id_ccaa} at line: {$line}");
			}

			$id_isla = '0';

			$ine_id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;

			if (isset($this->data[$ine_id_municipio])) {
				$check_arr = $this->data[$ine_id_municipio];
				if ($check_arr[3] == '0') { // no es una isla previamente almacenada
					die("ERROR(2): {$nombre_municipio} colisiona id:{$ine_id_municipio} con datos: ".$this->data[$ine_id_municipio]);
				}
				// los municipios de las islas ya los hemos almacenado previamente, para tener el id_isla correcto
				continue;
			}

			//$this->data[$ine_id_municipio] = "({$ine_id_municipio}, {$id_ccaa}, {$id_provincia}, {$id_isla}, {$dc}, '{$nombre_municipio}', '{$nombre_municipio_alt}', '{$urlized}')";
			//$this->data[$ine_id_municipio] = "({$ine_id_municipio}, {$id_ccaa}, {$id_provincia}, {$id_isla}, {$dc}, '{$nombre_municipio}', '{$nombre_corto}', '{$nombre_alt}', '{$urlized}', '{$nombre_corto_urlized}', '{$nombre_alt_urlized}')";
			$this->data[$ine_id_municipio] = array($ine_id_municipio, $id_ccaa, $id_provincia, $id_isla, $dc, $nombre_municipio, $nombre_corto, $nombre_alt, $urlized, $nombre_corto_urlized, $nombre_alt_urlized);
		}
	}

	private function _get_municipios_islas() {

		$year = date('Y');
		$year2 = date('y');
		
		$lines = array();
		$chunk = file('datos/INE-txt-codigos/'.$year.'/INE-'.$year2.'codislas07.txt'); // BALEARES
		if (! count($chunk)) {
			die('ERROR: Unable to load islas file: 07');
		}
		array_shift($chunk); array_shift($chunk); array_shift($chunk);
		$lines = array_merge($lines, $chunk);
		$chunk = file('datos/INE-txt-codigos/'.$year.'/INE-'.$year2.'codislas35.txt'); // LAS PALMAS
		if (! count($chunk)) {
			die('ERROR: Unable to load islas file: 35');
		}
		array_shift($chunk); array_shift($chunk); array_shift($chunk);
		$lines = array_merge($lines, $chunk);
		$chunk = file('datos/INE-txt-codigos/'.$year.'/INE-'.$year2.'codislas38.txt'); // SANTA CRUZ DE TENERIFE
		if (! count($chunk)) {
			die('ERROR: Unable to load islas file: 38');
		}
		array_shift($chunk); array_shift($chunk); array_shift($chunk);
		$lines = array_merge($lines, $chunk);

		if (! count($lines)) {
			die('ERROR: Unable to load files?');
		}

		$this->data = array();

		$values = array();
		foreach($lines as $line) {
			$this->_encode($line);
			$line = trim($line);
			if (empty($line))
				continue;

			list($id_provincia, $id_isla, $nombre_isla, $id_municipio, $dc, $nombre_municipio) = explode("\t", $line);

			$id_provincia = trim($id_provincia);
			$id_isla = trim($id_isla);
			$id_municipio = trim($id_municipio);
			$dc = trim($dc);
			$nombre_municipio = trim($nombre_municipio);

			$ine_id_provincia = $id_provincia;
			$ine_id_municipio = $id_municipio;

			settype($id_provincia, 'int');
			settype($id_isla, 'int');
			settype($id_municipio, 'int');
			settype($dc, 'int');

			// set string again
			settype($id_provincia, 'string');
			settype($id_municipio, 'string');

			list(
				$nombre_municipio,
				$urlized,
				$nombre_corto,
				$nombre_corto_urlized,
				$nombre_alt,
				$nombre_alt_urlized
			) = $this->_resolve_nombre_municipio($nombre_municipio);

			$id_ccaa = $this->provincias_ccaa[$id_provincia];

			$ine_id_municipio = $id_ccaa.$ine_id_provincia.$ine_id_municipio;

			if (isset($this->data[$ine_id_municipio])) {
				die("ERROR(1): {$nombre_municipio} colisiona id:{$ine_id_municipio} con datos: ".$this->data[$ine_id_municipio]);
			}

            $nombre_municipio = $this->db->str2sql($nombre_municipio);
            $nombre_corto = $this->db->str2sql($nombre_corto);
            $nombre_alt = $this->db->str2sql($nombre_alt);
            //$urlized = $this->db->str2sql($urlized);

			$this->data[$ine_id_municipio] = array($ine_id_municipio, $id_ccaa, $id_provincia, $id_isla, $dc, $nombre_municipio, $nombre_corto, $nombre_alt, $urlized, $nombre_corto_urlized, $nombre_alt_urlized);
		}

	}

	private function _resolve_nombre_municipio($nm) {

		// gestiona nombres con comas y alternativos con / (barra)

		$nm_urlized = urlize($nm);

		if (! strchr($nm, '/')) {

			// no tiene / (barra)

			if (strchr($nm, ',')) {

				// tiene coma
				list($segundo, $primero) = explode(', ', $nm, 2);
				$primero = trim($primero);
				$segundo = trim($segundo);
				if (empty($primero) || empty($segundo))
					die('Unable to make alternate name(1): ['.$nm.']');

				if (strchr($primero,"'"))
					$nm_corto = ucfirst($primero).$segundo;
				else
					$nm_corto = ucfirst($primero).' '.$segundo;
				$nm_alt = $nm_corto;

				$nm_corto_urlized = urlize($nm_corto);
				$nm_alt_urlized = urlize($nm_alt);

				return array($nm, $nm_urlized, $nm_corto, $nm_corto_urlized, $nm_alt, $nm_alt_urlized);
			}

			// no tiene coma ni / (barra)
			$nm_corto = $nm;
			$nm_corto_urlized = urlize($nm_corto);
			$nm_alt = $nm;
			$nm_alt_urlized = urlize($nm_alt);

			return array($nm, $nm_urlized, $nm_corto, $nm_corto_urlized, $nm_alt, $nm_alt_urlized);
		}

		// tiene / (barra)
		list($primero, $segundo) = explode('/', $nm, 2);
		$primero = trim($primero);
		$segundo = trim($segundo);
		if (empty($primero) || empty($segundo))
			die('Unable to make alternate name(2): ['.$nm.']');

		$nombres = array($primero, $segundo);
		foreach($nombres as $idx => $nombre) {
			if (strchr($nombre, ',')) {

				// tiene coma
				list($segundo, $primero) = explode(', ', $nombre, 2);
				$primero = trim($primero);
				$segundo = trim($segundo);
				if (empty($primero) || empty($segundo))
					die('Unable to make alternate name(3): ['.$nm.']');

				if ($idx == 0) {
					if (strchr($primero,"'"))
						$nm_corto = ucfirst($primero).$segundo;
					else
						$nm_corto = ucfirst($primero).' '.$segundo;
					$nm_corto_urlized = urlize($nm_corto);
				} else {
					if (strchr($primero,"'"))
						$nm_alt = ucfirst($primero).$segundo;
					else
						$nm_alt = ucfirst($primero).' '.$segundo;
					$nm_alt_urlized = urlize($nm_alt);
				}
			}
			else {
				if ($idx == 0) {
					$nm_corto = $nombre;
					$nm_corto_urlized = urlize($nm_corto);
				} else {
					$nm_alt = $nombre;
					$nm_alt_urlized = urlize($nm_alt);
				}
			}
		}

		return array($nm, $nm_urlized, $nm_corto, $nm_corto_urlized, $nm_alt, $nm_alt_urlized);
	}

	private function _split_censo() {
		
		$year = date('Y');
		$masterfilename = "datos/INE-callejero-censo/{$year}/INE-CENSO-TRAMOS-NAL.txt";

		$this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		//$this->provincias_isla = $this->mgeodb->get_provincias_islas_ids();

		echo "<hr><strong>Preparando split del Censo</strong> Archivo: {$masterfilename}<br/>";

		@mkdir('datos/INE-callejero-censo/split');
		
		foreach($this->provincias_ccaa as $filter_id_provincia => $id_ccaa) {

			$filename = "datos/INE-callejero-censo/split/INE-CENSO-TRAMOS-NAL-P{$filter_id_provincia}.txt";
			if (file_exists($filename)) {
				continue;
			}
			echo "Grabando {$filename} ... ";
			$this->_flush();
			
			if ( !$handle = fopen($masterfilename, "r")) {
				die('ERROR: Unable to open: '.$masterfilename);
			}
			$newlines='';
			while (($line = fgets($handle)) !== false) {
					
				if ($filter_id_provincia &&  $filter_id_provincia != (int)substr($line, 0, 2)) continue;

				$newlines .= $line;
			}
			fclose($handle);
			
			$bytes = file_put_contents($filename, $newlines);
			echo "{$bytes} bytes.<br/>"; 
		}

		echo "Hecho.<br/>";
		$this->db->debug_queries = array();
		$this->db->queries = array();
		$this->data = NULL;
		$this->cp = NULL;						
	}

	private function _split_ngbe() {
		
		$year = date('Y');
		$masterfilename = "datos/CNIG/{$year}/ngbe.csv";

		$this->provincias_ccaa = $this->mgeodb->get_provincias_ccaa_ids();
		//$this->provincias_isla = $this->mgeodb->get_provincias_islas_ids();

		echo "<hr><strong>Preparando split del NGBE</strong> Archivo: {$masterfilename}<br/>";

		@mkdir('datos/CNIG/split');
		
		$errors = array();

		foreach($this->provincias_ccaa as $filter_id_provincia => $id_ccaa) {

			//if ($filter_id_provincia != 24) continue;
			
			$filename = "datos/CNIG/split/NGBE-P{$filter_id_provincia}.txt";
			if (file_exists($filename)) {
				continue;
			}
			echo "Grabando {$filename} ... ";
			$this->_flush();
			
			if ( !$handle = fopen($masterfilename, "r")) {
				die('ERROR: Unable to open: '.$masterfilename);
			}
			$newlines='';
			$n_line=0;
			$first_line = false;
			while (($line = fgets($handle)) !== false) {
				$n_line++;
				$line = trim($line);
				if (empty($line)) { 
					if (!isset($errors[$n_line])) echo "ERROR: linea[{$n_line}] empty<br/>";
					$errors[$n_line] = true;
					continue; 
				}
								
				$pos = strrpos($line, ';'); //, // ';'
				if ($pos === FALSE) {
					if (!isset($errors[$n_line])) echo "ERROR: linea[{$n_line}] comma (,) not found<br/>";
					$errors[$n_line] = true;
					continue; 
				}
			    $pos = $pos+1;

			    $id_provincia = substr($line, $pos);
			    if (empty($id_provincia)) {
			    	if (!isset($errors[$n_line])) echo "ERROR: linea[{$n_line}] empty id_provincia<br/>";
					$errors[$n_line] = true;
			    	continue; 
			    }
			    if (! $first_line) {
			    	if ($id_provincia === 'provincias_id') {
			    		$first_line = true;
						continue;
					}
					die('provincias_id no encontrada como última columna de la primera línea (cabecera)');
			    }

				$csv_line = str_getcsv($line, ';', '"'); // ,
				
				if (count($csv_line) != 40) {
					die('ERROR, se esperaban 40 columnas en línea: '.$n_line);
				}

				$_provs = explode(';', $csv_line[39]);
				if (empty($_provs)) die('ERROR provincias inválidas ('.$csv_line[39].') en línea: '.$n_line);
				
			    $id_provincia = $_provs[0]; // la primera
			    
				if ($filter_id_provincia != $id_provincia) continue;
				
				//$assoc = $this->_get_ngbe_array($line, $n_line);
				
			    // limpiar saltos de línea
			    //$line = str_replace(array('"', "\r","\n","\t"), '', $line);
			    $line = str_replace(array("\r","\n","\t"), '', $line);
			    $line = str_replace('.00000000', '', $line);

				$newlines .= $line."\n";
			}
			fclose($handle);
			
			$bytes = file_put_contents($filename, $newlines);
			echo "{$bytes} bytes.<br/>"; 
		}

		echo "Hecho.<br/>";
		$this->db->debug_queries = array();
		$this->db->queries = array();
		$this->data = NULL;
		$this->cp = NULL;						
	}
	
	// obtiene la isla a la que pertenece un municipio
	private function _get_id_municipios2isla($ine_id_municipio) {
	
		static $muni2isla = null;
		if (! $muni2isla) {
			if (! $rows = $this->db->query("SELECT id_municipio, id_isla FROM sp_geodb_ine_municipios WHERE id_isla <> 0")) {
				die('ERROR: Unable to find non-zero id_isla at sp_geodb_ine_municipios table');
			}
			$muni2isla = array();
			foreach($rows as $row) {
				$muni2isla[$row['id_municipio']] = $row['id_isla'];
			}
		}
		
		return isset($muni2isla[$ine_id_municipio]) ? $muni2isla[$ine_id_municipio] : '0';
	}
	
	private function _flush() {
		ob_flush();
		flush();
	}

	private function _encode(&$str) {
		$enc = mb_detect_encoding($str, 'UTF-8' , true);
		if (! $enc) {
			$str = utf8_encode($str);
			return;
		}
	}

	function __destruct() {
		ob_end_flush();
	}

}

