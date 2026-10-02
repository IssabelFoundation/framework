# RPM locales para pruebas de integración MCP (EL8 / x86_64)

Desde `framework`, con Docker iniciado y `ai_assistant` como repositorio hermano:

```sh
bash build/local/build.sh
```

Construye una imagen Rocky 8 x86_64 (emulada si el host es ARM), ejecuta tests
Go y lint PHP, y empaqueta el estado actual de ambos árboles, incluidos los
archivos nuevos. No requiere push, no ejecuta Actions y no publica RPM.
El contenedor monta los repositorios como solo lectura. Se descargan únicamente
la imagen/dependencias de construcción. La base Rocky 8 usa los paquetes EL8
actualmente disponibles, no queda congelada en 8.8.

Los resultados están en `build/local/output/`: `RPMS/`, `SRPMS/`, `SOURCES/`,
`SPECS/` y `php-lint.log`. El helper MCP genera binario y tarball, no SRPM.
Luego prueba en otro contenedor la instalación/reinstalación de MCP, los
permisos de secretos y el arranque del daemon; guarda `secrets-test.log`.
La prueba agrega manualmente a `asterisk` al grupo: no ejecuta los scriptlets
web de Issabel ni simula sus bases de datos o reinicios con systemd.
Se generan cuatro RPM binarios: framework, themes-extra, MCP y asistente web.
`themes-extra` solo es necesario si ya está instalado o se usan esos temas.

Los specs originales conservan su Release. Solo las copias temporales reciben
`.localYYYYMMDDHHMMSS`, por ejemplo `0.1.0-1.el8.local20261002150000`.
Una release local basada en 1 NO supera una release 12 instalada.
Tampoco se reemplaza automáticamente por la futura release oficial 1;
para esa transición puede ser necesario un downgrade explícito.
Variables opcionales: `ASSISTANT_ROOT`, `RPM_OUTPUT`, `LOCAL_RPM_TAG`
(este último alfanumérico). Usar un directorio de salida nuevo por lote evita
mezclar diferentes versiones al copiar los RPM al servidor.

## Instalación en un sistema de prueba

Verificar arquitectura y paquetes instalados:

```sh
uname -m
rpm -q issabel-framework issabel-framework-themes-extra issabel-mcp issabel-ai-assistant
```

Copiar únicamente los RPM binarios del lote elegido a un directorio vacío del
servidor. Instalar framework, MCP y asistente en una única transacción; agregar
el nuevo themes-extra si está instalado porque requiere la misma release de
framework. Desde ese directorio, revisar la transacción antes de aceptarla:

```sh
dnf install ./*.rpm
systemctl enable --now issabel-mcp
systemctl restart issabel-mcp
```

Si hay releases de prueba anteriores con numeración mayor, `dnf install` no
las rebaja: usar `dnf downgrade` con los archivos exactos de esos paquetes,
revisando la transacción. No usar `--nodeps` en el sistema destino.
La instalación del asistente reinicia PHP-FPM y httpd si están activos.
Los scriptlets completos de framework también modifican configuración y bases
de Issabel: probar sobre una VM/snapshot; Docker aquí valida empaquetado, no
sustituye la prueba funcional sobre Issabel.

## Contrato entre paquetes y claves

- `issabel-framework`: instala `/var/www/html/pbxapi`, verifica los JWT RS256
  de MCP con `/etc/issabel-mcp/public.pem`, y conserva su autenticación HS256
  independiente mediante `pbxapijwtsecret` en `/etc/issabel.conf`.
- `issabel-mcp`: su `%pre` crea el grupo `issabel-ai` y el usuario de sistema
  `issabel-mcp`. Su `%post`, ejecutado como root **en el servidor destino**,
  genera las claves faltantes. No se generan ni se incluyen secretos en el
  build/RPM. El servicio lee las claves; no las genera durante el arranque.
- `issabel-ai-assistant`: instala el módulo PHP, agrega `asterisk` (y `apache`
  si existe) al grupo `issabel-ai`, registra menú/ACL y reinicia los procesos
  web activos para que incorporen el grupo. Requiere MCP de igual versión y
  framework antes de su `%post`. La dependencia no certifica que un framework
  antiguo incluya los endpoints MCP: instalar el framework de este lote.

| Ruta bajo /etc/issabel-mcp | Propietario | Modo | Uso |
|---|---|---|---|
| directorio | issabel-mcp:issabel-ai | 0750 | MCP escribe; grupo atraviesa/lee |
| private.pem | issabel-mcp:issabel-ai | 0600 | Solo MCP/root; firma JWT RS256 |
| public.pem | root:issabel-ai | 0644 | PBX API verifica JWT; acceso limitado por directorio |
| master.key | issabel-mcp:issabel-ai | 0600 | Solo MCP/root; cifra credenciales BYOK |
| web.secret | issabel-mcp:issabel-ai | 0640 | PHP y MCP; autentica peticiones locales con HMAC |

`asterisk` puede leer public.pem y web.secret, pero no private.pem ni master.key.
Los miembros de `issabel-ai` no tienen permiso de escritura sobre el directorio.
El usuario MCP sí controla ese directorio y se considera parte de la confianza
de esta integración. Los secretos no vacíos se preservan al reinstalar o
actualizar. La clave pública se deriva de la privada existente y se recupera
si falta. No borrar master.key: perderla impide descifrar credenciales guardadas.
Las claves generadas y `/var/lib/issabel-mcp` no se borran al desinstalar.

El build de framework requiere `issabel-firstboot >= 5.0.0-2`; su `%post`
invoca `issabel-admin-passwords --ensure-jwt-secret`. Este secreto HS256 es
independiente de los cuatro archivos MCP y también debe existir para que
arranque PBX API. El spec actual silencia el fallo de ese comando.

## Verificación sin mostrar secretos

```sh
id asterisk
stat -c '%a %U:%G %n' /etc/issabel-mcp /etc/issabel-mcp/*
runuser -u asterisk -- test -r /etc/issabel-mcp/public.pem
runuser -u asterisk -- test -r /etc/issabel-mcp/web.secret
runuser -u asterisk -- sh -c '! test -r /etc/issabel-mcp/private.pem && ! test -r /etc/issabel-mcp/master.key'
runuser -u issabel-mcp -- test -r /etc/issabel-mcp/private.pem
systemctl status issabel-mcp --no-pager
journalctl -u issabel-mcp -n 50 --no-pager
```

Además probar desde una nueva sesión web: abrir el asistente, configurar un
proveedor, leer extensiones y crear/revisar un plan. La aprobación/ejecución
usa autorización de administrador; los tokens MCP no reciben esos permisos.
