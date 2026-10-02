<?php
/*
 * Read-only time group inventory for the MCP service.
 *
 * A time group is a named set of ranges. Issabel stores one row per range in
 * timegroups_details with a packed "hours|weekdays|days|months" string, which
 * pbtime::parse() turns back into fields a caller can reason about.
 */
class mcptimegroups {
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
            $this->json(400, array('status'=>'error','detail'=>'Invalid time group id'));
            return;
        }
        $sql = 'SELECT id, description FROM timegroups_groups';
        $params = array();
        if($id !== '') { $sql .= ' WHERE id=?'; $params[] = $id; }
        $sql .= ' ORDER BY CAST(id AS UNSIGNED) LIMIT 1000';
        $groups = $this->db->exec($sql, $params);
        if($id !== '' && count($groups) === 0) {
            $this->json(404, array('status'=>'not_found'));
            return;
        }

        // One query for every range instead of one per group.
        $detailsSql = 'SELECT timegroupid, time FROM timegroups_details';
        $detailsParams = array();
        if($id !== '') { $detailsSql .= ' WHERE timegroupid=?'; $detailsParams[] = $id; }
        $detailsSql .= ' ORDER BY timegroupid, time';
        $ranges = array();
        foreach($this->db->exec($detailsSql, $detailsParams) as $row) {
            $ranges[(string)$row['timegroupid']][] = (string)$row['time'];
        }

        $results = array();
        foreach($groups as $group) {
            $stored = isset($ranges[(string)$group['id']]) ? $ranges[(string)$group['id']] : array();
            $times = array();
            foreach($stored as $time) { $times[] = pbtime::parse($time); }
            $results[] = array(
                'id'=>(string)$group['id'],
                'name'=>(string)$group['description'],
                'times'=>$times,
                'range_count'=>count($times)
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
