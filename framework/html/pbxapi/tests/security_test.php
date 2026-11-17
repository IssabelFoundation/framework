<?php
/**
 * Pruebas de regresion de seguridad para pbxapi.
 *
 * Compatible con PHP 5.4 (Issabel 4.x) y con PHP 8.x.
 *
 * Uso:
 *   php tests/security_test.php
 *
 * Nota: este archivo NO contiene el password real del equipo. Usa un
 * valor de prueba de la misma longitud que el original para que las
 * aserciones de tamano sigan siendo representativas.
 */

// Nunca ejecutar por HTTP: el script es de consola.
if (PHP_SAPI !== 'cli') {
    header('HTTP/1.0 404 Not Found');
    exit;
}

require_once(dirname(__DIR__) . '/lib/compat.php');
require_once(dirname(__DIR__) . '/models/jwtauth.php');
require_once(dirname(__DIR__) . '/models/asmanager.php');
require_once(dirname(__DIR__) . '/controllers/manager.php');

class FakeF3 {
    public $vars = array();

    function set($key, $value) {
        $this->vars[$key] = $value;
    }

    function get($key) {
        return isset($this->vars[$key]) ? $this->vars[$key] : null;
    }
}

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function check($name, $actual, $expected) {
    if ($actual === $expected) {
        $GLOBALS['pass']++;
        echo "  ok    " . $name . "\n";
    } else {
        $GLOBALS['fail']++;
        echo "  FALLA " . $name . " (esperado " . var_export($expected, true)
           . ", obtenido " . var_export($actual, true) . ")\n";
    }
}

echo "PHP " . PHP_VERSION . "\n\n";

// Password de prueba: 19 caracteres, igual que el original del incidente.
$pwd = 'TestPassword1234567';

/* ------------------------------------------------------------------ */
echo "== polyfill de hash_equals ==\n";
/* ------------------------------------------------------------------ */

check('hash_equals existe', function_exists('hash_equals'), true);
check('pbxapi_hash_equals existe', function_exists('pbxapi_hash_equals'), true);
check('polyfill: iguales', pbxapi_hash_equals($pwd, $pwd), true);
check('polyfill: un caracter distinto', pbxapi_hash_equals('abcdefghijklmnopqrs', $pwd), false);
check('polyfill: distinta longitud', pbxapi_hash_equals('abc', 'ab'), false);
check('polyfill: dos vacios', pbxapi_hash_equals('', ''), true);
check('polyfill: no string (int)', pbxapi_hash_equals('abc', 123), false);
check('polyfill: no string (null)', pbxapi_hash_equals(null, ''), false);
check('polyfill: no string (array)', pbxapi_hash_equals('abc', array('a')), false);
check('hash_equals nativo/polyfill coincide', hash_equals($pwd, $pwd), true);

/* ------------------------------------------------------------------ */
echo "\n== authenticate: type juggling ==\n";
/* ------------------------------------------------------------------ */

check('password correcto aceptado', jwtauth::credentialsMatch('admin', $pwd, $pwd), true);

$okbody = '{"user":"admin","password":"' . $pwd . '"}';
$okin   = json_decode($okbody, true);
check("body legitimo mide 49 bytes", strlen($okbody), 49);
check('body legitimo aceptado', jwtauth::credentialsMatch($okin['user'], $okin['password'], $pwd), true);

// Los tres cuerpos de 36 bytes que explotan el == de PHP < 8.
$attack_bodies = array(
    '{"user":"admin","password":0e999999}',
    '{"user":"admin","password":0.000000}',
    '{"user":"admin","password":0e+99999}',
);

foreach ($attack_bodies as $body) {
    $in = json_decode($body, true);
    check('body del ataque mide 36 bytes: ' . $body, strlen($body), 36);
    check('0e999999 decodifica a float', is_float($in['password']), true);
    check('body del ataque rechazado: ' . $body, jwtauth::credentialsMatch($in['user'], $in['password'], $pwd), false);
}

check('password float rechazado', jwtauth::credentialsMatch('admin', 0.0, $pwd), false);
check('password int rechazado', jwtauth::credentialsMatch('admin', 0, $pwd), false);
check('password null rechazado', jwtauth::credentialsMatch('admin', null, $pwd), false);
check('password bool rechazado', jwtauth::credentialsMatch('admin', true, $pwd), false);
check('password array rechazado', jwtauth::credentialsMatch('admin', array($pwd), $pwd), false);
check('password vacio rechazado', jwtauth::credentialsMatch('admin', '', $pwd), false);
check('usuario distinto rechazado', jwtauth::credentialsMatch('Admin', $pwd, $pwd), false);
check('usuario integer rechazado', jwtauth::credentialsMatch(0, $pwd, $pwd), false);
check('espacio final rechazado', jwtauth::credentialsMatch('admin', $pwd . ' ', $pwd), false);
check('magic hash string/string rechazado', jwtauth::credentialsMatch('admin', '0e999999', '0e123456'), false);
check('amiadminpwd vacio rechazado', jwtauth::credentialsMatch('admin', '', ''), false);
check('amiadminpwd null rechazado', jwtauth::credentialsMatch('admin', $pwd, null), false);

if (PHP_VERSION_ID < 80000) {
    check('(PHP<8) el == viejo aceptaba float 0', (0.0 == $pwd), true);
} else {
    echo "  info  PHP >= 8: el bug original no se reproduce en este interprete;\n";
    echo "        el fix se valida con credentialsMatch().\n";
}

/* ------------------------------------------------------------------ */
echo "\n== originate: bloqueo de System ==\n";
/* ------------------------------------------------------------------ */

check('System bloqueado', manager::isBlockedApplication('System'), true);
check('SYSTEM bloqueado', manager::isBlockedApplication('SYSTEM'), true);
check(' system  bloqueado', manager::isBlockedApplication(" system "), true);
check('TrySystem bloqueado', manager::isBlockedApplication('TrySystem'), true);
check('Exec bloqueado', manager::isBlockedApplication('Exec'), true);
check('TryExec bloqueado', manager::isBlockedApplication('TryExec'), true);
check('ExecIf bloqueado', manager::isBlockedApplication('ExecIf'), true);
check('ExecIfTime bloqueado', manager::isBlockedApplication('ExecIfTime'), true);
check('MixMonitor bloqueado', manager::isBlockedApplication('MixMonitor'), true);
check('Originate bloqueado', manager::isBlockedApplication('Originate'), true);
check('array rechazado', manager::isBlockedApplication(array('System')), true);

check('Playback permitido', manager::isBlockedApplication('Playback'), false);
check('Queue permitido', manager::isBlockedApplication('Queue'), false);
check('aplicacion vacia permitida', manager::isBlockedApplication(''), false);
check('aplicacion null permitida', manager::isBlockedApplication(null), false);
check('solo espacios permitida', manager::isBlockedApplication('   '), false);

/* ------------------------------------------------------------------ */
echo "\n== originate: inyeccion de cabeceras AMI ==\n";
/* ------------------------------------------------------------------ */

check('CR detectado', manager::hasControlChars("a\rb"), true);
check('LF detectado', manager::hasControlChars("a\nb"), true);
check('CRLF detectado', manager::hasControlChars("Action: Command\r\nCommand: id"), true);
check('NUL detectado', manager::hasControlChars("a\0b"), true);
check('texto normal permitido', manager::hasControlChars('Local/s@default'), false);
check('data numerica permitida', manager::hasControlChars('12345'), false);
check('no string no explota', manager::hasControlChars(array('a')), false);

/* ------------------------------------------------------------------ */
echo "\n== parse_conf: un solo parser ==\n";
/* ------------------------------------------------------------------ */

$tmp = tempnam(sys_get_temp_dir(), 'issabelconf');
file_put_contents($tmp, "# comentario\nmysqlrootpwd=rootpw\namiadminpwd=Sup3r=Secret2026\n\nsin_igual\n");

$auth = new jwtauth(new FakeF3());
$cfg  = $auth->parse_conf($tmp);

check('conserva el valor completo con "="', $cfg['amiadminpwd'], 'Sup3r=Secret2026');
check('ignora comentarios', isset($cfg['# comentario']), false);
check('ignora lineas sin "="', isset($cfg['sin_igual']), false);
check('lee claves normales', isset($cfg['mysqlrootpwd']) ? $cfg['mysqlrootpwd'] : null, 'rootpw');

unlink($tmp);

/* ------------------------------------------------------------------ */
echo "\n" . $GLOBALS['pass'] . " pruebas ok, " . $GLOBALS['fail'] . " fallos\n";
exit($GLOBALS['fail'] === 0 ? 0 : 1);
