Soporte para paneles en la barra lateral de Elastix
-----------------------------------------------------------

Desde las versiones de elastix-framework-2.5.0-15 y elastix-framework-4.0.0-13
está soportada la visualización de una barra lateral en la interfaz web de
Elastix (para los temas tenant, blackmin, elastixneo, elastixwave, giox). En
esta barra lateral se pueden colocar uno o más paneles con contenido y controles
para acciones personalizadas. Estos paneles siempre están disponibles (si el
código del panel decide mostrarlo) sin importar el usuario logoneado o el módulo
que se esté usando. El siguiente es el modelo implementado del API para estos
paneles personalizados.

Organización de archivos
------------------------

Para agregar un nuevo panel, se debe crear un directorio dentro de
/var/www/html/panels/ que contiene los archivos que conforman el panel.
Dentro del directorio se debe de incluir un archivo index.php, el cual contiene
los puntos de entrada para implementar la funcionalidad deseada. Además se deben
incluir los siguientes directorios debajo del panel, en caso de ser necesario:

js/
Funciones javascript que deben incluirse para implementar el panel. Estos
archivos deben ser incluidos de forma manual por un tag <script> en el HTML
inicial del panel. En jQuery el contenido del panel está dentro del tag <div>
seleccionado con: 'div#elastix-panel-{NOMBRE_DEL_MODULO} > div.panel-body' .

lang/
Traducciones de idioma para los textos a usar en el panel. El formato de archivo
de traducción es idéntico al de los archivos usados por los módulos ordinarios.
Estos archivos serán cargados automáticamente por el framework de Elastix, y
por lo tanto no es necesario llamar a load_language_module() dentro del código.

tpl/
Archivos de plantillas a usar para el panel.

Otros directorios u archivos son ignorados por el código aunque pueden ser
incluidos explícitamente.

Funciones y clases a definir
----------------------------

Para evitar colisiones entre paneles, el index.php del panel debe de definir
una clase cuyo nombre se deriva del nombre de directorio del panel, con la
primera letra puesta en mayúscula. Por ejemplo, si el directorio se llama
"ponchador", se espera una clase "Panel_Ponchador".

Dentro de la clase se pueden definir las funciones siguientes:


static function templateContent($smarty, $module_name)

La función templateContent debe de existir, y se le pasará el objeto Smarty del
framework, y el nombre del directorio en $module_name. La ruta del directorio de
plantillas del panel puede formarse con la expresión:

dirname($_SERVER['SCRIPT_FILENAME'])."/panels/$module_name/tpl"

La función templateContent debe devolver un arreglo que contiene el título, el
icono opcional, y el contenido del panel para que el panel aparezca en la
interfaz, o NULL si el código decide no mostrar el panel. El contenido devuelto
debe estar estructurado así:

array('title' => "...", 'content' => "...", 'iconclass' => "...", 'icon' => "...")

Los valores de title y content son obligatorios. Se puede especificar cualquiera
de icon o iconclass, pero si se especifican ambos, iconclass toma precedencia.
El valor de icon es una ruta a un archivo de icono, relativa al URL base del
framework de Elastix. El valor de iconclass es una definición de clase CSS que
será aplicada a un tag <i> para hacer uso de un font de iconos, ya sea uno
disponible (Font Awesome o Entypo), o uno personalizado (a ser incluido
manualmente por el panel). Por ejemplo, para hacer aparecer una patita de gato,
se puede especificar iconclass="fa fa-paw".


static function handleJSON_ACCION($smarty, $module_name)

Para cada operación definida por el panel que requiera acceso al servidor, se
puede definir una función handleJSON_ACCION, donde ACCION se usa para discriminar
la operación entre las múltiples posibles. Para construir una peticion AJAX que
invoque la acción, se deben especificar de la siguiente manera (GET/POST):

$.post('index.php', {
    menu: '_elastixpanel',
    elastixpanel: {NOMBRE_DEL_MODULO},
    action: {ACCION},
    param1: {...},
    param2: {...},
    param3: {...}
}, function(data) {});

El valor de menu es siempre '_elastixpanel'. El parámetro elastixpanel es
obligatorio y se usa para elegir el panel en cuestión. El parámetro action es
obligatorio y se usa para invocar a la función handleJSON_ACCION dentro de la
clase del módulo. Los parámetros adicionales param1..paramN dependen del panel.

