<?php

// Obtengo los nombres de las funciones en la carpeta /var/www/html/libs/
exec("grep -iE \"[[:space:]]*function\" libs/*", $arrOutput);

foreach($arrOutput as $linea) {
	if(preg_match("/[[:space:]]*function[[:space:]]+(&[[:space:]]+)?([[:alnum:]_]+)([[:space:]])?\(/", $linea, $arrReg)) {
		$arrNombreFunciones[] = $arrReg[2];
	}
}

// Para cada funcion veo si esta siendo utilizada en algun archivo
foreach($arrNombreFunciones as $nombreFuncion) {
	echo "Analizando funcion $nombreFuncion...";
	if(!funcionEstaSiendoUsada($nombreFuncion)) {
		$arrNombreFuncionesNoUsadas[] = $nombreFuncion;
		echo " NO esta siendo usada\n";
	} else {
		echo " Esta siendo usada\n";
	}
}

print_r($arrNombreFuncionesNoUsadas);

function funcionEstaSiendoUsada($nombreFuncion)
{
	$cmd = "grep -ri \"" . $nombreFuncion . "[[:space:]]*(\" /var/www/html/*";
	exec($cmd, $arrOutput, $retVar);

	if($retVar==0) {
		foreach($arrOutput as $linea) {
			if(!preg_match("/function/", $linea) and preg_match("/$nombreFuncion/", $linea)) {
				//echo "La funcion $nombreFuncion se encontro en el archivo $linea\n";
				return true;
				break;
			}
		}
	} else {
		// Siempre que de error conservo la funcion porsiaca
		return true;
	}
	return false;
}
?>
