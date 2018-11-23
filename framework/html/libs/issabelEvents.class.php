<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2018 Issabel Foundation                                |
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
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
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: issabelEvents.class.php, Fri 23 Nov 2018 03:50:29 PM EST, nicolas@issabel.com
*/

class Events {
    const EVENT_REPEAT      = 0x0001;
    const EVENT_SEQUENCE    = 0x0002;
    var $events;
    var $timers;

    function __construct() {
            $this->events = array();
            $this->timers = array();
    }

    function AddTimer($when, $action, $args = false, $flags = 0) {
            if (preg_match('#([0-9a-zA-Z]+)..([0-9a-zA-Z]+)#', $when, $a)) {
                    $time = time(NULL) + rand($this->time2seconds($a[1]), $this->time2seconds($a[2]));
            } else {
                    $time = time(NULL) + $this->time2seconds($when);
            }
            if ($flags & self::EVENT_SEQUENCE) {
                    while ($this->IsArrayCount($this->timers[$time])) {
                            $time ++;
                    }
            }
            $this->timers[$time][] = array("when" => $when, "action" => $action, "args" => $args, "flags" => $flags);
            ksort($this->timers);
    }

    function GetNextTimer() {
            if (!$this->IsArrayCount($this->timers)) {
                    return false;
            }
            reset($this->timers);
            $firstevent = each($this->timers);
            if ($firstevent === false) {
                    return false;
            }
            $time = $firstevent["key"];
            $nextEvent = $time - time(NULL);
            if ($nextEvent < 1) {
                    return 1;
            }

            return $nextEvent;
    }

    function CheckTimers() {
            $rv = false;
            $now = time(NULL);
            foreach ($this->timers as $time => $events) {
                    if ($time > $now) {
                            break;
                    }
                    foreach ($events as $key => $event) {
                            # debug("Event::CheckTimer: {$event["action"]}");
                            # ircPubMsg("Event::CheckTimer: {$event["action"]}", "#bots");
                            if (!$event["args"]) {
                                    call_user_func($event["action"]);
                            } else {
                                    $rv = call_user_func_array($event["action"], is_array($event["args"]) ? $event["args"] : array($event["args"]));
                            }
                            unset($this->timers[$time][$key]);
                            if ($event["flags"] & self::EVENT_REPEAT) {
                                    $this->AddTimer($event["when"], $event["action"], $event["args"], $event["flags"]);
                            }
                            if ($rv) {
                                    # break;
                            }
                    }
                    if (!$this->IsArrayCount($this->timers[$time])) {
                            unset($this->timers[$time]);
                    }

                    if (0 && $rv) {
                            break;
                    }
            }

            if ($rv) {
                    return $rv;
            }
    }

    function time2seconds($timeString) {
            $end = substr($timeString, strlen($timeString) - 1);
            $seconds = intval($timeString); //  = preg_replace("#[^0-9]#", "", $a);

            if (is_numeric($end)) {
                    return $seconds;
            }

            $unim = array("s","m","h","d", "w", "m", "y");
            $divs = array(1, 60, 60, 24, 7, 28, 12, false);
            $found = false;
            while (!$found) {
                    $u = array_shift($unim);
                    $d = array_shift($divs);
                    $seconds *= $d;
                    if ($end === $u) {
                            return $seconds;
                    }
            }

            return intval($timeString);
    }

    function IsArrayCount($possibleArray) {
            return (isset($possibleArray) && is_array($possibleArray)) ? count($possibleArray) : false;
    }
}
