<?php
/* Read-only, secret-free queue inventory for the MCP service. */
class mcpqueues {
    protected $db;

    function __construct($f3) {
        $auth = new authorize();
        $auth->authorized($f3);
        $auth->requireScope($f3, 'queues:read');
        $this->db = $f3->get('DB');
    }

    function get($f3) {
        $id = (string)$f3->get('PARAMS.id');
        if($id !== '' && !preg_match('/^[0-9]{1,8}$/', $id)) {
            $this->json(400, array('status'=>'error','detail'=>'Invalid queue extension'));
        }
        $sql = "SELECT q.extension,q.descr AS name,q.maxwait,q.dest,MAX(CASE WHEN d.keyword='strategy' THEN d.data END) AS strategy,MAX(CASE WHEN d.keyword='timeout' THEN d.data END) AS agent_timeout,MAX(CASE WHEN d.keyword='retry' THEN d.data END) AS retry,MAX(CASE WHEN d.keyword='wrapuptime' THEN d.data END) AS wrapup FROM queues_config q LEFT JOIN queues_details d ON d.id=q.extension";
        $params = array();
        if($id !== '') { $sql .= ' WHERE q.extension=?'; $params[] = $id; }
        $sql .= ' GROUP BY q.extension,q.descr,q.maxwait,q.dest ORDER BY CAST(q.extension AS UNSIGNED)';
        $rows = $this->db->exec($sql, $params);
        if($id !== '' && count($rows) === 0) { $this->json(404, array('status'=>'not_found')); }

        $static = $this->staticAgents($id);
        $dynamic = $this->dynamicAgents($f3, $id);
        $result = array();
        foreach($rows as $row) {
            $extension = (string)$row['extension'];
            $result[] = array(
                'extension'=>$extension,
                'name'=>(string)$row['name'],
                'strategy'=>(string)$row['strategy'],
                'max_wait_seconds'=>intval($row['maxwait']),
                'agent_timeout_seconds'=>intval($row['agent_timeout']),
                'retry_seconds'=>intval($row['retry']),
                'wrapup_seconds'=>intval($row['wrapup']),
                'static_agents'=>isset($static[$extension]) ? $static[$extension] : array(),
                'dynamic_agents'=>isset($dynamic[$extension]) ? $dynamic[$extension] : array(),
                'failover'=>$this->safeFailover((string)$row['dest'])
            );
        }
        $this->json(200, array('results'=>$result));
    }

    protected function staticAgents($id) {
        $sql = "SELECT id,data FROM queues_details WHERE keyword='member'";
        $params = array();
        if($id !== '') { $sql .= ' AND id=?'; $params[] = $id; }
        $rows = $this->db->exec($sql, $params);
        $result = array();
        foreach($rows as $row) {
            if(preg_match('/^(?:(?:Local|SIP|PJSIP|IAX2|DAHDI|Agent)\/)?([0-9]{1,8})(?:@[^,]+)?,([0-9]{1,3})$/i', (string)$row['data'], $matches)) {
                $result[(string)$row['id']][] = array('extension'=>$matches[1],'penalty'=>intval($matches[2]));
            }
        }
        return $result;
    }

    protected function dynamicAgents($f3, $id) {
        $result = array();
        $ami = $f3->get('AMI');
        if(!is_object($ami) || !is_callable(array($ami, 'DatabaseShow'))) { return $result; }
        $rows = $ami->DatabaseShow('QPENALTY');
        if(!is_array($rows)) { return $result; }
        foreach($rows as $key=>$penalty) {
            $parts = explode('/', trim((string)$key, '/'));
            $offset = array_search('QPENALTY', $parts, true);
            if($offset === false || !isset($parts[$offset+1],$parts[$offset+2],$parts[$offset+3]) || $parts[$offset+2] !== 'agents') { continue; }
            $queue = (string)$parts[$offset+1];
            $agent = (string)$parts[$offset+3];
            if(($id === '' || $queue === $id) && preg_match('/^[0-9]{1,8}$/', $agent)) {
                $result[$queue][] = array('extension'=>$agent,'penalty'=>intval($penalty));
            }
        }
        return $result;
    }

    protected function safeFailover($destination) {
        // One implementation, shared with the ring group projection.
        return pbxnamespace::parseDestination($destination);
    }

    protected function json($status, $data) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol.' '.$status, true, $status);
        echo json_encode($data);
        die();
    }
}
