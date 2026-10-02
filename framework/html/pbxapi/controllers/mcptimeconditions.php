<?php
/*
 * Read-only time condition inventory for the MCP service.
 *
 * A time condition pairs a time group with two destinations. Neither is echoed
 * as raw dialplan text: both go through pbxnamespace::parseDestination(), which
 * decodes the families the PBX itself builds.
 *
 * The per-condition override state lives in the Asterisk database under TC/<id>
 * and is deliberately not read here: it would require an AMI connection on a
 * read-only projection.
 */
class mcptimeconditions {
    protected $db;

    function __construct($f3) {
        $auth = new authorize();
        $auth->authorized($f3);
        $auth->requireScope($f3, 'time:read');
        $this->db = $f3->get('DB');
    }

    function get($f3) {
        $id = (string)$f3->get('PARAMS.id');
        if($id !== '' && !preg_match('/^[0-9]{1,8}$/', $id)) {
            $this->json(400, array('status'=>'error','detail'=>'Invalid time condition id'));
            return;
        }
        $sql = 'SELECT t.timeconditions_id,t.displayname,t.time,t.truegoto,t.falsegoto,g.description AS timegroup_name '
             . 'FROM timeconditions t LEFT JOIN timegroups_groups g ON g.id=t.time';
        $params = array();
        if($id !== '') { $sql .= ' WHERE t.timeconditions_id=?'; $params[] = $id; }
        $sql .= ' ORDER BY CAST(t.timeconditions_id AS UNSIGNED) LIMIT 1000';
        $rows = $this->db->exec($sql, $params);
        if($id !== '' && count($rows) === 0) {
            $this->json(404, array('status'=>'not_found'));
            return;
        }
        $results = array();
        foreach($rows as $row) {
            $results[] = array(
                'id'=>(string)$row['timeconditions_id'],
                'name'=>(string)$row['displayname'],
                'time_group_id'=>(string)$row['time'],
                'time_group_name'=>(string)$row['timegroup_name'],
                'destination_if_time_matches'=>pbxnamespace::parseDestination($row['truegoto']),
                'destination_if_time_does_not_match'=>pbxnamespace::parseDestination($row['falsegoto'])
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
