<?php
/*
 * Read-only IVR inventory for the MCP service.
 *
 * An IVR is a parent row in ivr_details plus one row per menu option in
 * ivr_entries. Destinations are never echoed as raw dialplan text: both the
 * timeout and invalid targets and every menu option go through
 * pbxnamespace::parseDestination(), which decodes the families the PBX builds.
 *
 * The greeting is a recording id owned by the recordings module; that module is
 * not exposed, so the raw id is reported as-is rather than resolved to a name.
 */
class mcpivrs {
    protected $db;

    function __construct($f3) {
        $auth = new authorize();
        $auth->authorized($f3);
        $auth->requireScope($f3, 'ivr:read');
        $this->db = $f3->get('DB');
    }

    function get($f3) {
        $id = (string)$f3->get('PARAMS.id');
        if($id !== '' && !preg_match('/^[0-9]{1,8}$/', $id)) {
            $this->json(400, array('status'=>'error','detail'=>'Invalid IVR id'));
            return;
        }
        // Real column names from ivr_details; the API's own field_map names
        // (timeout_destination, invalid_destination, timeout_time) are kept
        // because they are the columns here.
        $sql = 'SELECT id,name,description,announcement,timeout_time,'
             . 'timeout_destination,invalid_destination FROM ivr_details';
        $params = array();
        if($id !== '') { $sql .= ' WHERE id=?'; $params[] = $id; }
        $sql .= ' ORDER BY CAST(id AS UNSIGNED) LIMIT 1000';
        $rows = $this->db->exec($sql, $params);
        if($id !== '' && count($rows) === 0) {
            $this->json(404, array('status'=>'not_found'));
            return;
        }

        // One query for every menu option instead of one per IVR. The column
        // list is fixed; SELECT * is never used.
        $entriesSql = 'SELECT ivr_id,selection,dest,ivr_ret FROM ivr_entries';
        $entriesParams = array();
        if($id !== '') { $entriesSql .= ' WHERE ivr_id=?'; $entriesParams[] = $id; }
        $entriesSql .= ' ORDER BY selection';
        $menus = array();
        foreach($this->db->exec($entriesSql, $entriesParams) as $entry) {
            $menus[(string)$entry['ivr_id']][] = array(
                'digits'=>(string)$entry['selection'],
                'destination'=>pbxnamespace::parseDestination($entry['dest']),
                'return_to_ivr'=>(string)$entry['ivr_ret'] === '1'
            );
        }

        $results = array();
        foreach($rows as $row) {
            $options = isset($menus[(string)$row['id']]) ? $menus[(string)$row['id']] : array();
            $results[] = array(
                'id'=>(string)$row['id'],
                'name'=>(string)$row['name'],
                'description'=>(string)$row['description'],
                'announcement_id'=>$row['announcement'] === null ? null : (string)$row['announcement'],
                'timeout_seconds'=>intval($row['timeout_time']),
                'timeout_destination'=>pbxnamespace::parseDestination($row['timeout_destination']),
                'invalid_destination'=>pbxnamespace::parseDestination($row['invalid_destination']),
                'entries'=>$options,
                'entry_count'=>count($options)
            );
        }
        $this->json(200, array('results'=>$results));
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
