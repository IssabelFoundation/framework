<?php
/*
 * Read-only, secret-free ring group inventory for the MCP service.
 *
 * Ring groups carry no credentials, but the destination field is free-form
 * dialplan text, so it is projected through pbxnamespace::parseDestination()
 * instead of being echoed back verbatim.
 */
class mcpringgroups {
    protected $db;

    function __construct($f3) {
        $auth = new authorize();
        $auth->authorized($f3);
        $auth->requireScope($f3, 'ringgroups:read');
        $this->db = $f3->get('DB');
    }

    function get($f3) {
        $id = (string)$f3->get('PARAMS.id');
        if($id !== '' && !preg_match('/^[0-9]{1,8}$/', $id)) {
            $this->json(400, array('status'=>'error','detail'=>'Invalid ring group extension'));
            return;
        }
        $sql = 'SELECT grpnum,description,grplist,strategy,grptime,postdest,ringing FROM ringgroups';
        $params = array();
        if($id !== '') { $sql .= ' WHERE grpnum=?'; $params[] = $id; }
        $sql .= ' ORDER BY CAST(grpnum AS UNSIGNED) LIMIT 1000';
        $rows = $this->db->exec($sql, $params);
        if($id !== '' && count($rows) === 0) {
            $this->json(404, array('status'=>'not_found'));
            return;
        }

        $results = array();
        foreach($rows as $row) {
            $members = pbxnamespace::memberList($row['grplist']);
            $results[] = array(
                'extension'=>(string)$row['grpnum'],
                'name'=>(string)$row['description'],
                'strategy'=>(string)$row['strategy'],
                'ring_time_seconds'=>intval($row['grptime']),
                'members'=>$members,
                'member_count'=>count($members),
                'music_on_hold_ringing'=>(string)$row['ringing'],
                'failover'=>pbxnamespace::parseDestination($row['postdest'])
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
