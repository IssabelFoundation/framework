<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
   +----------------------------------------------------------------------+
   | Issabel pbxapi - compatibilidad y utilidades compartidas             |
   +----------------------------------------------------------------------+
   | Issabel 4.x corre sobre PHP 5.4, que esta EOL desde septiembre 2015. |
   | Este archivo concentra lo minimo para usar primitivas modernas sin   |
   | depender de la version del interprete, y un unico parser de           |
   | /etc/issabel.conf para que index.php y jwtauth.php no divergan.       |
   +----------------------------------------------------------------------+
*/

/**
 * Comparacion de strings en tiempo constante.
 *
 * Equivalente a hash_equals() de PHP 5.6+, con la misma semantica:
 * devuelve false si algun argumento no es string o si las longitudes
 * difieren; si no, recorre ambos strings completos sin cortocircuito.
 *
 * Se expone siempre con nombre propio para poder probarla tambien en
 * PHP 8, donde hash_equals() ya existe de forma nativa.
 *
 * @param  string $known_string valor esperado (secreto)
 * @param  string $user_string  valor recibido del cliente
 * @return bool
 */
function pbxapi_hash_equals($known_string, $user_string) {
    if (!is_string($known_string) || !is_string($user_string)) {
        return false;
    }

    $known_len = strlen($known_string);
    if ($known_len !== strlen($user_string)) {
        return false;
    }

    $result = 0;
    for ($i = 0; $i < $known_len; $i++) {
        $result |= ord($known_string[$i]) ^ ord($user_string[$i]);
    }

    return $result === 0;
}

if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string) {
        return pbxapi_hash_equals($known_string, $user_string);
    }
}

/**
 * Parser unico de /etc/issabel.conf.
 *
 * Reemplaza las dos copias divergentes que existian:
 *  - index.php usaba explode('=', $line, 2)  -> valor completo
 *  - jwtauth.php usaba preg_split('/=/')     -> truncaba en el primer "="
 *
 * De ese modo el password que comparaba /pbxapi/authenticate era el
 * prefijo anterior al primer "=" y no el valor real del archivo.
 *
 * Mantiene el comportamiento del parser original de index.php:
 * ignora lineas vacias, comentarios (# y ;) y lineas sin "=".
 *
 * @param  string $file ruta absoluta al archivo de configuracion
 * @return array
 * @throws RuntimeException si el archivo no se puede leer
 */
function pbxapi_parse_conf($file) {
    $result = array();
    $lines  = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        throw new RuntimeException("Unable to read $file");
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || $line[0] === ';') {
            continue;
        }

        $partes = explode('=', $line, 2);
        if (count($partes) !== 2) {
            continue;
        }

        $result[trim($partes[0])] = trim($partes[1]);
    }

    return $result;
}
