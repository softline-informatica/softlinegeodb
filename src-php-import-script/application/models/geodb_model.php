<?php

class Geodb_Model Extends MVC_Model {


    public function get_provincias_ccaa_ids($fields = null) {

    	if (! $fields) {
			$fields = "P.id_provincia, P.id_ccaa";
    	}

		$rows = $this->db->query("
			SELECT {$fields}
			FROM sp_geodb_ine_provincias P 
			ORDER BY id_ccaa ASC
		");
		$prov = array();
		foreach($rows as $row) {
			$prov[$row['id_provincia']] = $row['id_ccaa'];
		}
		return $prov;
    }
	
	public function get_provincias_islas_ids() {
		if (! $rows = $this->db->query("SELECT * FROM sp_geodb_ine_islas")) {
			return NULL;
		}
		foreach($rows as $row) {
			$prov[$row['id_provincia']] = $row['id_isla'];
		}
		return $prov;		
	}

    public function get_provincias() {
		return $this->db->query("SELECT * FROM sp_geodb_ine_provincias");
    }
    public function get_provincias_ids_urlized() {
    	$rows = $this->get_provincias();
    	$prov = array();
    	foreach($rows as $row) {
			$prov[$row['id_provincia']] = $row['urlized_alt'];
    	}
    	return $prov;
	}

    /*
		SELECT P.id_provincia, P.nombre_alt, M.nombre_alt AS nombre_capital
		FROM `sp_geodb_ine_provincias` P
		LEFT JOIN sp_geodb_ine_municipios M ON M.id_municipio = P.id_municipio_capital
		ORDER BY `P`.`id_provincia` ASC
		LIMIT 0 , 30
    */

}

