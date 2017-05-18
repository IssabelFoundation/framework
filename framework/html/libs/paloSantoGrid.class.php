<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.3                                                |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: paloSantoGrid.class.php, bmacias@palosanto.com Exp $ */

class paloSantoGrid {

    private $title;
    private $icon;
    private $width;
    private $enableExport;
    private $limit;
    private $total;
    private $offset;
    private $start;
    private $end;
    private $tplFile;
    private $pagingShow;
    private $nameFile_Export;
    private $arrHeaders;
    private $arrData;
    private $url;
    private $arrActions;
    private $arrControlFilters;

    private static $_whitelist_fontclass_font_awesome = array(
        "glass", "music", "search", "envelope-o", "heart", "star", "star-o",
        "user", "film", "th-large", "th", "th-list", "check", "remove", "close",
        "times", "search-plus", "search-minus", "power-off", "signal", "gear",
        "cog", "trash-o", "home", "file-o", "clock-o", "road", "download",
        "arrow-circle-o-down", "arrow-circle-o-up", "inbox", "play-circle-o",
        "rotate-right", "repeat", "refresh", "list-alt", "lock", "flag",
        "headphones", "volume-off", "volume-down", "volume-up", "qrcode",
        "barcode", "tag", "tags", "book", "bookmark", "print", "camera", "font",
        "bold", "italic", "text-height", "text-width", "align-left",
        "align-center", "align-right", "align-justify", "list", "dedent",
        "outdent", "indent", "video-camera", "photo", "image", "picture-o",
        "pencil", "map-marker", "adjust", "tint", "edit", "pencil-square-o",
        "share-square-o", "check-square-o", "arrows", "step-backward",
        "fast-backward", "backward", "play", "pause", "stop", "forward",
        "fast-forward", "step-forward", "eject", "chevron-left",
        "chevron-right", "plus-circle", "minus-circle", "times-circle",
        "check-circle", "question-circle", "info-circle", "crosshairs",
        "times-circle-o", "check-circle-o", "ban", "arrow-left", "arrow-right",
        "arrow-up", "arrow-down", "mail-forward", "share", "expand", "compress",
        "plus", "minus", "asterisk", "exclamation-circle", "gift", "leaf",
        "fire", "eye", "eye-slash", "warning", "exclamation-triangle", "plane",
        "calendar", "random", "comment", "magnet", "chevron-up", "chevron-down",
        "retweet", "shopping-cart", "folder", "folder-open", "arrows-v",
        "arrows-h", "bar-chart-o", "bar-chart", "twitter-square",
        "facebook-square", "camera-retro", "key", "gears", "cogs", "comments",
        "thumbs-o-up", "thumbs-o-down", "star-half", "heart-o", "sign-out",
        "linkedin-square", "thumb-tack", "external-link", "sign-in", "trophy",
        "github-square", "upload", "lemon-o", "phone", "square-o", "bookmark-o",
        "phone-square", "twitter", "facebook-f", "facebook", "github", "unlock",
        "credit-card", "feed", "rss", "hdd-o", "bullhorn", "bell",
        "certificate", "hand-o-right", "hand-o-left", "hand-o-up",
        "hand-o-down", "arrow-circle-left", "arrow-circle-right",
        "arrow-circle-up", "arrow-circle-down", "globe", "wrench", "tasks",
        "filter", "briefcase", "arrows-alt", "group", "users", "chain", "link",
        "cloud", "flask", "cut", "scissors", "copy", "files-o", "paperclip",
        "save", "floppy-o", "square", "navicon", "reorder", "bars", "list-ul",
        "list-ol", "strikethrough", "underline", "table", "magic", "truck",
        "pinterest", "pinterest-square", "google-plus-square", "google-plus",
        "money", "caret-down", "caret-up", "caret-left", "caret-right",
        "columns", "unsorted", "sort", "sort-down", "sort-desc", "sort-up",
        "sort-asc", "envelope", "linkedin", "rotate-left", "undo", "legal",
        "gavel", "dashboard", "tachometer", "comment-o", "comments-o", "flash",
        "bolt", "sitemap", "umbrella", "paste", "clipboard", "lightbulb-o",
        "exchange", "cloud-download", "cloud-upload", "user-md", "stethoscope",
        "suitcase", "bell-o", "coffee", "cutlery", "file-text-o", "building-o",
        "hospital-o", "ambulance", "medkit", "fighter-jet", "beer", "h-square",
        "plus-square", "angle-double-left", "angle-double-right",
        "angle-double-up", "angle-double-down", "angle-left", "angle-right",
        "angle-up", "angle-down", "desktop", "laptop", "tablet", "mobile-phone",
        "mobile", "circle-o", "quote-left", "quote-right", "spinner", "circle",
        "mail-reply", "reply", "github-alt", "folder-o", "folder-open-o",
        "smile-o", "frown-o", "meh-o", "gamepad", "keyboard-o", "flag-o",
        "flag-checkered", "terminal", "code", "mail-reply-all", "reply-all",
        "star-half-empty", "star-half-full", "star-half-o", "location-arrow",
        "crop", "code-fork", "unlink", "chain-broken", "question", "info",
        "exclamation", "superscript", "subscript", "eraser", "puzzle-piece",
        "microphone", "microphone-slash", "shield", "calendar-o",
        "fire-extinguisher", "rocket", "maxcdn", "chevron-circle-left",
        "chevron-circle-right", "chevron-circle-up", "chevron-circle-down",
        "html5", "css3", "anchor", "unlock-alt", "bullseye", "ellipsis-h",
        "ellipsis-v", "rss-square", "play-circle", "ticket", "minus-square",
        "minus-square-o", "level-up", "level-down", "check-square",
        "pencil-square", "external-link-square", "share-square", "compass",
        "toggle-down", "caret-square-o-down", "toggle-up", "caret-square-o-up",
        "toggle-right", "caret-square-o-right", "euro", "eur", "gbp", "dollar",
        "usd", "rupee", "inr", "cny", "rmb", "yen", "jpy", "ruble", "rouble",
        "rub", "won", "krw", "bitcoin", "btc", "file", "file-text",
        "sort-alpha-asc", "sort-alpha-desc", "sort-amount-asc",
        "sort-amount-desc", "sort-numeric-asc", "sort-numeric-desc",
        "thumbs-up", "thumbs-down", "youtube-square", "youtube", "xing",
        "xing-square", "youtube-play", "dropbox", "stack-overflow", "instagram",
        "flickr", "adn", "bitbucket", "bitbucket-square", "tumblr",
        "tumblr-square", "long-arrow-down", "long-arrow-up", "long-arrow-left",
        "long-arrow-right", "apple", "windows", "android", "linux", "dribbble",
        "skype", "foursquare", "trello", "female", "male", "gittip", "gratipay",
        "sun-o", "moon-o", "archive", "bug", "vk", "weibo", "renren",
        "pagelines", "stack-exchange", "arrow-circle-o-right",
        "arrow-circle-o-left", "toggle-left", "caret-square-o-left",
        "dot-circle-o", "wheelchair", "vimeo-square", "turkish-lira", "try",
        "plus-square-o", "space-shuttle", "slack", "envelope-square",
        "wordpress", "openid", "institution", "bank", "university",
        "mortar-board", "graduation-cap", "yahoo", "google", "reddit",
        "reddit-square", "stumbleupon-circle", "stumbleupon", "delicious",
        "digg", "pied-piper", "pied-piper-alt", "drupal", "joomla", "language",
        "fax", "building", "child", "paw", "spoon", "cube", "cubes", "behance",
        "behance-square", "steam", "steam-square", "recycle", "automobile",
        "car", "cab", "taxi", "tree", "spotify", "deviantart", "soundcloud",
        "database", "file-pdf-o", "file-word-o", "file-excel-o",
        "file-powerpoint-o", "file-photo-o", "file-picture-o", "file-image-o",
        "file-zip-o", "file-archive-o", "file-sound-o", "file-audio-o",
        "file-movie-o", "file-video-o", "file-code-o", "vine", "codepen",
        "jsfiddle", "life-bouy", "life-buoy", "life-saver", "support",
        "life-ring", "circle-o-notch", "ra", "rebel", "ge", "empire",
        "git-square", "git", "y-combinator-square", "yc-square", "hacker-news",
        "tencent-weibo", "qq", "wechat", "weixin", "send", "paper-plane",
        "send-o", "paper-plane-o", "history", "circle-thin", "header",
        "paragraph", "sliders", "share-alt", "share-alt-square", "bomb",
        "soccer-ball-o", "futbol-o", "tty", "binoculars", "plug", "slideshare",
        "twitch", "yelp", "newspaper-o", "wifi", "calculator", "paypal",
        "google-wallet", "cc-visa", "cc-mastercard", "cc-discover", "cc-amex",
        "cc-paypal", "cc-stripe", "bell-slash", "bell-slash-o", "trash",
        "copyright", "at", "eyedropper", "paint-brush", "birthday-cake",
        "area-chart", "pie-chart", "line-chart", "lastfm", "lastfm-square",
        "toggle-off", "toggle-on", "bicycle", "bus", "ioxhost", "angellist",
        "cc", "shekel", "sheqel", "ils", "meanpath", "buysellads",
        "connectdevelop", "dashcube", "forumbee", "leanpub", "sellsy",
        "shirtsinbulk", "simplybuilt", "skyatlas", "cart-plus",
        "cart-arrow-down", "diamond", "ship", "user-secret", "motorcycle",
        "street-view", "heartbeat", "venus", "mars", "mercury", "intersex",
        "transgender", "transgender-alt", "venus-double", "mars-double",
        "venus-mars", "mars-stroke", "mars-stroke-v", "mars-stroke-h", "neuter",
        "genderless", "facebook-official", "pinterest-p", "whatsapp", "server",
        "user-plus", "user-times", "hotel", "bed", "viacoin", "train", "subway",
        "medium", "yc", "y-combinator", "optin-monster", "opencart",
        "expeditedssl", "battery-4", "battery-full", "battery-3",
        "battery-three-quarters", "battery-2", "battery-half", "battery-1",
        "battery-quarter", "battery-0", "battery-empty", "mouse-pointer",
        "i-cursor", "object-group", "object-ungroup", "sticky-note",
        "sticky-note-o", "cc-jcb", "cc-diners-club", "clone", "balance-scale",
        "hourglass-o", "hourglass-1", "hourglass-start", "hourglass-2",
        "hourglass-half", "hourglass-3", "hourglass-end", "hourglass",
        "hand-grab-o", "hand-rock-o", "hand-stop-o", "hand-paper-o",
        "hand-scissors-o", "hand-lizard-o", "hand-spock-o", "hand-pointer-o",
        "hand-peace-o", "trademark", "registered", "creative-commons", "gg",
        "gg-circle", "tripadvisor", "odnoklassniki", "odnoklassniki-square",
        "get-pocket", "wikipedia-w", "safari", "chrome", "firefox", "opera",
        "internet-explorer", "tv", "television", "contao", "500px", "amazon",
        "calendar-plus-o", "calendar-minus-o", "calendar-times-o",
        "calendar-check-o", "industry", "map-pin", "map-signs", "map-o", "map",
        "commenting", "commenting-o", "houzz", "vimeo", "black-tie",
        "fonticons",
    );

    private static $_whitelist_fontclass_entypo = array(
        "note", "logo-db", "music", "search", "flashlight", "mail", "heart",
        "heart-empty", "star", "star-empty", "user", "users", "user-add",
        "video", "picture", "camera", "layout", "menu", "check", "cancel",
        "cancel-circled", "cancel-squared", "plus", "plus-circled",
        "plus-squared", "minus", "minus-circled", "minus-squared", "help",
        "help-circled", "info", "info-circled", "back", "home", "link",
        "attach", "lock", "lock-open", "eye", "tag", "bookmark", "bookmarks",
        "flag", "thumbs-up", "thumbs-down", "download", "upload",
        "upload-cloud", "reply", "reply-all", "forward", "quote", "code",
        "export", "pencil", "feather", "print", "retweet", "keyboard",
        "comment", "chat", "bell", "attention", "alert", "vcard", "address",
        "location", "map", "direction", "compass", "cup", "trash", "doc",
        "docs", "doc-landscape", "doc-text", "doc-text-inv", "newspaper",
        "book-open", "book", "folder", "archive", "box", "rss", "phone", "cog",
        "tools", "share", "shareable", "basket", "bag", "calendar", "login",
        "logout", "mic", "mute", "sound", "volume", "clock", "hourglass",
        "lamp", "light-down", "light-up", "adjust", "block", "resize-full",
        "resize-small", "popup", "publish", "window", "arrow-combo",
        "down-circled", "left-circled", "right-circled", "up-circled",
        "down-open", "left-open", "right-open", "up-open", "down-open-mini",
        "left-open-mini", "right-open-mini", "up-open-mini", "down-open-big",
        "left-open-big", "right-open-big", "up-open-big", "down", "left",
        "right", "up", "down-dir", "left-dir", "right-dir", "up-dir",
        "down-bold", "left-bold", "right-bold", "up-bold", "down-thin",
        "left-thin", "right-thin", "note-beamed", "ccw", "cw", "arrows-ccw",
        "level-down", "level-up", "shuffle", "loop", "switch", "play", "stop",
        "pause", "record", "to-end", "to-start", "fast-forward",
        "fast-backward", "progress-0", "progress-1", "progress-2", "progress-3",
        "target", "palette", "list", "list-add", "signal", "trophy", "battery",
        "back-in-time", "monitor", "mobile", "network", "cd", "inbox",
        "install", "globe", "cloud", "cloud-thunder", "flash", "moon", "flight",
        "paper-plane", "leaf", "lifebuoy", "mouse", "briefcase", "suitcase",
        "dot", "dot-2", "dot-3", "brush", "magnet", "infinity", "erase",
        "chart-pie", "chart-line", "chart-bar", "chart-area", "tape",
        "graduation-cap", "language", "ticket", "water", "droplet", "air",
        "credit-card", "floppy", "clipboard", "megaphone", "database", "drive",
        "bucket", "thermometer", "key", "flow-cascade", "flow-branch",
        "flow-tree", "flow-line", "flow-parallel", "rocket", "gauge",
        "traffic-cone", "cc", "cc-by", "cc-nc", "cc-nc-eu", "cc-nc-jp", "cc-sa",
        "cc-nd", "cc-pd", "cc-zero", "cc-share", "cc-remix", "github",
        "github-circled", "flickr", "flickr-circled", "vimeo", "vimeo-circled",
        "twitter", "twitter-circled", "facebook", "facebook-circled",
        "facebook-squared", "gplus", "gplus-circled", "pinterest",
        "pinterest-circled", "tumblr", "tumblr-circled", "linkedin",
        "linkedin-circled", "dribbble", "dribbble-circled", "stumbleupon",
        "stumbleupon-circled", "lastfm", "lastfm-circled", "rdio",
        "rdio-circled", "spotify", "spotify-circled", "qq", "instagram",
        "dropbox", "evernote", "flattr", "skype", "skype-circled", "renren",
        "sina-weibo", "paypal", "picasa", "soundcloud", "mixi", "behance",
        "google-circles", "vkontakte", "smashing", "sweden", "db-shape",
        "up-thin",
    );

    public function paloSantoGrid($smarty)
    {
        $this->title  = "";
        $this->icon   = "images/list.png";
        $this->width  = "99%";
        $this->smarty = $smarty;
        $this->enableExport = false;
        $this->offset = 0;
        $this->start  = 0;
        $this->end    = 0;
        $this->limit  = 0;
        $this->total  = 0;
        $this->pagingShow = 1;
        $this->tplFile    = "_common/_list.tpl";
        $this->nameFile_Export = "Report-".date("YMd.His");
        $this->arrHeaders = array();
        $this->arrData    = array();
        $this->url        = "";

        $this->arrActions = array();
        $this->arrFiltersControl = array();
    }

    /**
     * Procedimiento para agregar un control de desactivación de filtro a la
     * grilla. Un control de desactivación de filtro es un hipervínculo (con
     * GET) que tiene seteada la variable reservada "name_delete_filters", la
     * cual tiene el valor de nombres de variables de petición separadas por
     * coma. La función getParameter() IGNORA los valores de las variables
     * mencionadas en la lista de "name_delete_filters" y devuelve NULL en
     * caso de pedirlas, incluso si estuviesen seteadas.
     *
     * Adicionalmente, esta función verifica por su cuenta las variables a
     * quitar mencionadas en "name_delete_filters", y si esas variables son las
     * claves del arreglo asociativo $arrFilter, se asume que los valores de
     * $arrFilter son los valores a reemplazar para resetear el filtro a su
     * estado por omisión. En este caso, se asume que $arrData es la lista de
     * variables de la petición, y los valores SE AGREGAN Y/O SOBREESCRIBEN con
     * los valores indicados en $arrFilter.
     *
     * La presentación exacta del control de desactivación de filtro puede variar
     * con el tema de Elastix, pero los temas estándar muestran el control como
     * un rectángulo rosado con bordes redondeados, un mensaje indicado por el
     * parámetro $msg, y una equis correspondiente al hipervínculo en sí.
     *
     * La secuencia de operaciones esperada por los módulos de grilla es:
     * - creación de nuevo paloSantoGrid
     * - se recogen las variables de petición en $arrData, usando getParameter()
     * - se llama sucesivamente a addFilterControl para cada control a quitar
     * - se llama showFilter() con el formulario y las variables de $arrData
     *   posiblemente modificadas por las llamadas a addFilterControl
     * - se construye el arreglo de url con $arrData como variables a asignar
     *   con setURL()
     * - conversión de $arrData a parámetros de filtración del query SQL
     *
     * @param string    $msg                Mensaje descriptivo del control
     * @param array     $arrData            Variables de petición recogidas con getParameter()
     * @param array     $arrFilter          Arreglo asociativo de variables anulables con valores de anulación
     * @param bool      $always_activated   VERDADERO si el control debe ser visible incluso si las variables están ausentes
     */
    public function addFilterControl($msg, &$arrData, $arrFilter = array(), $always_activated=false)
    {
        if (!empty($msg)) $msg = htmlentities($msg, ENT_COMPAT, 'UTF-8');

        $defaultFiler = "yes";
        if((is_array($arrFilter) && count($arrFilter)>0)){
            $name_delete_filters = getParameter('name_delete_filters');
            $keys = array_keys($arrFilter);
            $first = $keys[0];

            $name_delete_filters = explode(",",$name_delete_filters);
            if(in_array($first, $name_delete_filters)){ //accion eliminar
                foreach($arrFilter as $name => $value){
                    $arrData[$name] = $value;
                }
                if($always_activated){ // a pesar de que fue eliminado el filtro, se desea que el control siga visible.
                    $this->arrFiltersControl[] = array(
                        "msg" => $msg,
                        "filters" => implode(",",$keys),
                        "defaultFilter" => "yes"
                    );
                }
            } else {
                $filter_apply = true;
                foreach($arrFilter as $name => $value){
                    $val = (isset($arrData[$name]) && !empty($arrData[$name]))?$arrData[$name]:null;
                    if($val===null){
                        $filter_apply = false;
                        break;
                    }
                    //esto se hace para poder saber si el fitro aplicado corresponde al valor por default del filtro
                    if($always_activated){
                        if($val!=$arrFilter[$name]){
                            $defaultFiler = "no";
                        }
                    }else
                        $defaultFiler = "no";
                }
                if($filter_apply){ //solo si todos estan seteados o tiene un value asociado (!=null)
                    $this->arrFiltersControl[] = array(
                        "msg" => $msg,
                        "filters" => implode(",",$keys),
                        "defaultFilter" => $defaultFiler);
                }
            }
        } else {
            echo "Invalid format for variable \$arrFilter.";
        }
    }

    public function addNew($task="add", $alt="New Row", $asLink=false)
    {
        $type = ($asLink)?"link":"submit";
        $this->addAction($task,$alt,"images/plus2.png",$type);
    }

    public function customAction($task="task", $alt="Custom Action", $img="",  $asLink=false)
    {
        $type = ($asLink)?"link":"submit";
        $this->addAction($task,$alt,$img,$type);
    }

    public function deleteList($msg="" , $task="remove", $alt="Delete Selected",  $asLink=false)
    {
        $type    = ($asLink)?"link":"submit";
        $onclick = "return confirmSubmit('"._tr($msg)."')";
        $this->addAction($task,$alt,"images/delete5.png",$type,$onclick,"ec6459");
    }

    public function addLinkAction($href="action=add", $alt="New Row", $icon=null, $onclick=null)
    {
        $this->addAction($href,$alt,$icon,"link",$onclick);
    }

    public function addSubmitAction($task="add", $alt="New Row", $icon=null, $onclick=null)
    {
        $this->addAction($task,$alt,$icon,"submit",$onclick);
    }

    public function addButtonAction($name="add", $alt="New Row", $icon=null, $onclick="javascript:click()")
    {
        $this->addAction($name,$alt,$icon,"button",$onclick);
    }

    public function addInputTextAction($name_input="add", $label="New Row", $value_input="", $task="add", $onkeypress_text=null)
    {
        $newAction['type']  = "text";
        $newAction['name']  = $name_input;
        $newAction['alt']   = $label;
        $newAction['value'] = $value_input;
        $newAction['onkeypress'] = empty($onkeypress_text)?null:$onkeypress_text;
        $newAction['task']    = empty($task)?"add":$task;

        $this->arrActions[] = $newAction;
    }

    public function addComboAction($name_select="cmb", $label="New Row", $data=array(), $selected=null, $task="add", $onchange_select=null)
    {
        $newAction['type'] = "combo";
        $newAction['name'] = $name_select;
        $newAction['alt']  = $label;
        $newAction['arrOptions'] = empty($data)?array():$data;
        $newAction['selected']   = empty($selected)?null:$selected;
        $newAction['onchange']   = empty($onchange_select)?null:$onchange_select;
        $newAction['task']    = empty($task)?"add":$task;

        $this->arrActions[] = $newAction;
    }

    public function addHTMLAction($html)
    {
        $this->addAction($html,null,null,"html",null);
    }

    private function addAction($task, $alt, $icon, $type="submit", $event=null, $overwrite_color=null)
    {
        global $arrConf;

        // Mapa de iconos conocidos a clases de iconos de tenant
        $iconmap = array(
            "images/delete5.png" => "fa fa-eraser",
            "images/plus2.png" => "fa fa-plus",
        );
        $iconclass = NULL;
        if (isset($iconmap[$icon])) {
            $iconclass = $iconmap[$icon];
        } elseif (in_array($icon, self::$_whitelist_fontclass_font_awesome)) {
            $iconclass = "fa fa-{$icon}";
            $icon = NULL;
        } elseif (in_array($icon, self::$_whitelist_fontclass_entypo)) {
            $iconclass = "entypo-{$icon}";
            $icon = NULL;
        }

        /* Los módulos de CallCenterPRO dependen de que no se anule el valor de
         * action.icon que es una sola letra mostrada con el tipo de letra
         * elastix_icons.ttf . Por eso se verifica con strlen(). */
        if (!is_null($icon) && strlen($icon) > 1 && !file_exists($icon)) {
            $icon = NULL;
        }

        $newAction = array();

        switch($type){
            case 'link':
            case 'button':
            case 'submit':
                $newAction = array(
                    'type' => $type,
                    'task' => $task,
                    'alt'  => $alt,
                    'icon' => $icon,
                    'iconclass' => $iconclass,
                    'onclick' => empty($event)?null:$event,
                    'ocolor' => empty($overwrite_color)?null:$overwrite_color);
                break;
            case 'html':
                $newAction = array(
                    'type' => $type,
                    'html' => $task);
                break;
            default:
                $newAction = array(
                    'type' => "submit",
                    'task' => $task,
                    'alt'  => $alt,
                    'iconclass' => $iconclass,
                    'icon' => $icon);
                break;
        }

        $this->arrActions[] = $newAction;
    }

    function pagingShow($show)
    {
        $this->pagingShow = (int)$show;
    }

    function setTplFile($tplFile)
    {
        $this->tplFile  = $tplFile;
    }

    function getTitle()
    {
        return $this->title;
    }

    function setTitle($title)
    {
        $this->title = $title;
    }

    function getIcon()
    {
        return $this->icon;
    }

    function setIcon($icon)
    {
        $this->icon = $icon;
    }

    function getWidth()
    {
        return $this->width;
    }

    function setWidth($width)
    {
        $this->width = $width;
    }

    function setURL($arrURL)
    {
        if (is_array($arrURL))
            $this->url = construirURL($arrURL, array('nav', 'start', 'logout','name_delete_filters'));
        else
            $this->url = $arrURL;
    }

    function getColumns()
    {
        return $this->arrHeaders;
    }

    function setColumns($arrColumns)
    {
        $arrHeaders = array();

        if(is_array($arrColumns) && count($arrColumns)>0){
            foreach($arrColumns as $k => $column){
                $arrHeaders[] = array(
                    "name"      => $column,
                    "property1" => "");
            }
        }
        $this->arrHeaders = $arrHeaders;
    }

    function getData()
    {
        return $this->arrData;
    }

    function setData($arrData)
    {
        if(is_array($arrData) && count($arrData)>0)
            $this->arrData = $arrData;
    }

    function fetchGrid($arrGrid=array(), $arrData=array(), $arrLang=array())
    {
        if(isset($arrGrid["title"]))
            $this->title = $arrGrid["title"];
        if(isset($arrGrid["icon"]))
            $this->icon  = $arrGrid["icon"];
        if(isset($arrGrid["width"]))
            $this->width = $arrGrid["width"];

        if(isset($arrGrid["start"]))
            $this->start = $arrGrid["start"];
        if(isset($arrGrid["end"]))
            $this->end   = $arrGrid["end"];
        if(isset($arrGrid["total"]))
            $this->total = $arrGrid["total"];

        if(isset($arrGrid['url'])) {
            if (is_array($arrGrid['url']))
                $this->url = construirURL($arrGrid['url'], array('nav', 'start', 'logout','name_delete_filters'));
            else
                $this->url = $arrGrid["url"];
        }

        if(isset($arrGrid["columns"]) && count($arrGrid["columns"]) > 0)
            $this->arrHeaders = $arrGrid["columns"];
        if(isset($arrData) && count($arrData) > 0)
            $this->arrData = $arrData;


        $export = $this->exportType();

        switch($export){
            case "csv":
                $content = $this->fetchGridCSV($arrGrid, $arrData);
                break;
            case "pdf":
                $content = $this->fetchGridPDF();
                break;
            case "xls":
                $content = $this->fetchGridXLS();
                break;
            default: //html
                $content = $this->fetchGridHTML();
                break;
        }
        return $content;
    }

    function fetchGridCSV($arrGrid=array(), $arrData=array())
    {
        if(isset($arrGrid["columns"]) && count($arrGrid["columns"]) > 0)
            $this->arrHeaders = $arrGrid["columns"];
        if(isset($arrData) && count($arrData) > 0)
            $this->arrData = $arrData;

        header("Cache-Control: private");
        header("Pragma: cache");    // Se requiere para HTTPS bajo IE6
        header('Content-Disposition: attachment; filename="'."{$this->nameFile_Export}.csv".'"');
        header("Content-Type: text/csv; charset=UTF-8");

        $numColumns = count($this->getColumns());
        $this->smarty->assign("numColumns", $numColumns);
        $this->smarty->assign("header",     $this->getColumns());
        $this->smarty->assign("arrData",    $this->getData());

        return $this->smarty->fetch("_common/listcsv.tpl");
    }

    function fetchGridPDF()
    {
        global $arrConf;
        require_once "{$arrConf['basePath']}/libs/paloSantoPDF.class.php";
        $pdf = new paloSantoPDF('L', PDF_UNIT, 'A3');
        $pdf->printTable("{$this->nameFile_Export}.pdf", $this->getTitle(), $this->getColumns(), $this->getData());

        return "";
    }

    function fetchGridXLS()
    {
        header ("Cache-Control: private");
        header ("Pragma: cache");    // Se requiere para HTTPS bajo IE6
        header ('Content-Disposition: attachment; filename="'."{$this->nameFile_Export}.xls".'"');
        header ("Content-Type: application/vnd.ms-excel; charset=UTF-8");

        $tmp = $this->xlsBOF();
        # header
        $headers = $this->getColumns();
        foreach($headers as $i => $header)
            $tmp .= $this->xlsWriteCell(0,$i,$header["name"]);

        #data
        $data = $this->getData();
        foreach($data as $k => $row) {
            foreach($row as $i => $cell){
                $tmp .= $this->xlsWriteCell($k+1,$i,$cell);
            }
        }
        $tmp .= $this->xlsEOF();
        echo $tmp;
    }

    function fetchGridHTML($arrLang=array())
    {
        $this->smarty->assign("pagingShow",$this->pagingShow);

        $this->smarty->assign("arrActions",$this->arrActions);
        $this->smarty->assign("arrFiltersControl",$this->arrFiltersControl);

        $this->smarty->assign("title", $this->getTitle());
        $this->smarty->assign("icon",  $this->getIcon());
        $this->smarty->assign("width", $this->getWidth());

        $this->smarty->assign("start", $this->start);
        $this->smarty->assign("end",   $this->end);
        $this->smarty->assign("total", $this->total);

        $numPage = ($this->limit==0)?0:ceil($this->total / $this->limit);
        $this->smarty->assign("numPage",$numPage);

        $currentPage = ($this->limit==0 || $this->start==0)?0:(floor($this->start / $this->limit) + 1);
        $this->smarty->assign("currentPage",$currentPage);

        if(!empty($this->url))
            $this->smarty->assign("url",   $this->url);

        $numColumns = count($this->getColumns());
        $numData    = count($this->getData());
        $this->smarty->assign("numColumns", $numColumns);
        $this->smarty->assign("header",     $this->getColumns());
        $this->smarty->assign("arrData",    $this->getData());
        $this->smarty->assign("numData",    $numData);

        $this->smarty->assign("enableExport", $this->enableExport);

        //dar el valor a las etiquetas segun el idioma
        $etiquetas = array('Export','Start','Previous','Next','End','Page','of','records');
        foreach ($etiquetas as $etiqueta)
            $this->smarty->assign("lbl$etiqueta", _tr($etiqueta));

        $this->smarty->assign("NO_DATA_FOUND"     , _tr("No records match the filter criteria"));
        $this->smarty->assign("FILTER_GRID_SHOW"  , _tr("Show Filter"));
        $this->smarty->assign("FILTER_GRID_HIDE"  , _tr("Hide Filter"));
        $this->smarty->assign("MORE_OPTIONS"      , _tr("More Options"));
        $this->smarty->assign("DOWNLOAD_GRID"     , _tr("Download"));

        return $this->smarty->fetch($this->tplFile);
    }

    function showFilter($htmlFilter,$as_options=false)
    {
        if($as_options)
            $this->smarty->assign("AS_OPTION", 1);
        else
            $this->smarty->assign("AS_OPTION", 0);

        $this->smarty->assign("contentFilter", $htmlFilter);
    }

    function calculatePagination()
    {
        $accion = getParameter("nav");

        if($accion == "bypage"){
            $numPage = ($this->getLimit()==0)?0:ceil($this->getTotal() / $this->getLimit());

            $page  = getParameter("page");
            if(preg_match("/[0-9]+/",$page)==0)// no es un número
                $page = 1;

            if( $page > $numPage) // se está solicitando una pagina mayor a las que existen
                $page = $numPage;

            $start = ( ( ($page - 1) * $this->getLimit() ) + 1 ) - $this->getLimit();

            $accion = "next";
            if($start + $this->getLimit() <= 1){
                $accion = null;
                $start = null;
            }
        }
        else
            $start  = getParameter("start");

        $this->setOffsetValue($this->getOffSet($this->getLimit(),$this->getTotal(),$accion,$start));
        $this->setEnd(($this->getOffsetValue() + $this->getLimit()) <= $this->getTotal() ? $this->getOffsetValue() + $this->getLimit() : $this->getTotal());
        $this->setStart(($this->getTotal()==0) ? 0 : $this->getOffsetValue() + 1);
    }

    function calculateOffset()
    {
        $this->calculatePagination();
        return $this->getOffsetValue();
    }

    function getOffSet($limit,$total,$accion,$start)
    {
        // Si se quiere avanzar a la sgte. pagina
        if(isset($accion) && $accion=="next") {
            $offset = $start + $limit - 1;
        }
        // Si se quiere retroceder
        else if(isset($accion) && $accion=="previous") {
            $offset = $start - $limit - 1;
        }
        else if(isset($accion) && $accion=="end") {
            if(($total%$limit)==0)
                $offset = $total - $limit;
            else
                $offset = $total - $total%$limit;
        }
        else if(isset($accion) && $accion=="start") {
            $offset = 0;
        }
        else $offset = 0;
        return $offset;
    }

    function enableExport()
    {
        $this->enableExport = true;
    }

    function setLimit($limit)
    {
        $this->limit = $limit;
    }

    function setTotal($total)
    {
        $this->total = $total;
    }

    function setOffsetValue($offset)
    {
        $this->offset = $offset;
    }

    function setStart($start)
    {
        $this->start = $start;
    }

    function setEnd($end)
    {
        $this->end = $end;
    }

    function getLimit()
    {
        return $this->limit;
    }

    function getTotal()
    {
        return $this->total;
    }

    function getOffsetValue()
    {
        return $this->offset;
    }

    function getEnd()
    {
        return $this->end;
    }

    function exportType()
    {
        if(getParameter("exportcsv") == "yes")
            return "csv";
        else if(getParameter("exportpdf") == "yes")
            return "pdf";
        else if(getParameter("exportspreadsheet") == "yes")
            return "xls";
        else
            return "html";
    }

    function isExportAction()
    {
        if(getParameter("exportcsv") == "yes")
            return true;
        else if(getParameter("exportpdf") == "yes")
            return true;
        else if(getParameter("exportspreadsheet") == "yes")
            return true;
        else
            return false;
    }

    function setNameFile_Export($nameFile)
    {
        $this->nameFile_Export = "$nameFile-".date("YMd.His");
    }

    function xlsBOF()
    {
        $data = pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
        return $data;
    }

    function xlsEOF()
    {
        $data = pack("ss", 0x0A, 0x00);
        return $data;
    }

    function xlsWriteNumber($Row, $Col, $Value)
    {
        $data  = pack("sssss", 0x203, 14, $Row, $Col, 0x0);
        $data .= pack("d", $Value);
        return $data;
    }

    function xlsWriteLabel($Row, $Col, $Value )
    {
        $Value2UTF8=utf8_decode($Value);
        $L = strlen($Value2UTF8);
        $data  = pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x0, $L);
        $data .= $Value2UTF8;
        return $data;
    }

    function xlsWriteCell($Row, $Col, $Value )
    {
        if(is_numeric($Value))
            return $this->xlsWriteNumber($Row, $Col, $Value);
        else
            return $this->xlsWriteLabel($Row, $Col, $Value);
    }
}
?>