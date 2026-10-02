<?php
/*
 * Credential-free number and destination namespace for the restricted MCP service.
 *
 * This is a projection, not the generic REST controller: it never reads a
 * secret column and it never accepts a mutation. It exists so the assistant can
 * answer "is this number free?" and "does this destination exist?" with the same
 * data the PBX itself uses to reject a change.
 *
 * /mcpnamespace/<number> is the authoritative availability check. A 404 from
 * /mcpextensions/<number> only means no extension exists there; the number can
 * still be owned by a queue, ring group, conference, parking lot, custom
 * extension or feature code.
 *
 * Note: alldestinations/allextensions instantiate every controller in this
 * directory, so this class must stay cheap to construct and must never define
 * getDestinations()/getExtensions() or instantiate the aggregates, which would
 * recurse.
 */
class mcpnamespace {
    protected $db;

    function __construct($f3) {
        $auth = new authorize();
        $auth->authorized($f3);
        $auth->requireScope($f3, 'namespace:read');
        $this->db = $f3->get('DB');
    }

    function get($f3) {
        $what = strtolower(trim((string)$f3->get('PARAMS.id')));
        // A bare number answers the question that matters before any plan:
        // is this number usable, or does another module already own it?
        // /mcpextensions/<n> returning 404 only proves no *extension* exists.
        // A comma separated list answers it for a whole range in one round trip,
        // which is what makes an agent stop guessing for ranges.
        if(preg_match('/^[0-9]{1,8}(,[0-9]{1,8}){0,99}$/', $what)) {
            $numbers = array_values(array_unique(explode(',', $what)));
            $conflicts = pbxnamespace::numberConflicts($this->db, $numbers);
            $results = array();
            $free = array();
            $unavailable = array();
            foreach($numbers as $number) {
                $sources = isset($conflicts[$number]) ? $conflicts[$number] : array();
                $available = count($sources) === 0;
                if($available) { $free[] = $number; } else { $unavailable[] = $number; }
                $results[] = array(
                    'number'=>$number,
                    'available'=>$available,
                    'sources'=>$sources,
                    'usage'=>$available ? 'free' : (in_array('extensions', $sources, true) ? 'extension' : 'reserved'),
                    'detail'=>$available
                        ? 'No PBX module uses this number.'
                        : 'Number unavailable: reserved by '.pbxnamespace::conflictDetail(array($number=>$sources))
                );
            }
            if(count($results) === 1) {
                // Single number keeps its original flat shape.
                $this->json(200, array_merge(array('status'=>'ok'), $results[0]));
                return;
            }
            // 'free' and 'unavailable' answer "which of these can I use?"
            // without the caller having to re-derive it.
            $this->json(200, array(
                'status'=>'ok',
                'results'=>$results,
                'free'=>$free,
                'unavailable'=>$unavailable,
                'free_count'=>count($free),
                'unavailable_count'=>count($unavailable)
            ));
            return;
        }
        if($what !== '' && $what !== 'numbers' && $what !== 'destinations') {
            $this->json(400, array('status'=>'error',
                'detail'=>'Allowed paths: /mcpnamespace, /mcpnamespace/numbers, /mcpnamespace/destinations, /mcpnamespace/<number> or /mcpnamespace/<number,number,...>'));
            return;
        }
        $data = array('status'=>'ok');
        if($what === '' || $what === 'numbers') {
            $numbers = pbxnamespace::usedNumbers($this->db);
            $data['numbers'] = $numbers;
            $data['number_count'] = count($numbers);
        }
        if($what === '' || $what === 'destinations') {
            $destinations = pbxnamespace::usedDestinations($this->db);
            $data['destinations'] = $destinations;
            $data['destination_count'] = count($destinations);
            // Inventory of what exists, not a promise that a plan may target it:
            // plan validation remains authoritative inside mcpplans.
            $data['destinations_advisory'] = true;
        }
        $this->json(200, $data);
    }

    function post($f3) { $this->json(405, array('status'=>'method_not_allowed')); }
    function put($f3) { $this->json(405, array('status'=>'method_not_allowed')); }
    function delete($f3) { $this->json(405, array('status'=>'method_not_allowed')); }

    protected function json($status, $data) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol.' '.$status, true, $status);
        echo json_encode($data);
        die();
    }
}
