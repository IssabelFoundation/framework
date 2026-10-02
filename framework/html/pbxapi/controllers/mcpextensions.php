<?php
/* Credential-free extension projection for the restricted MCP service token. */
class mcpextensions {
    protected $db;
    protected $auth;

    function __construct($f3) {
        $this->auth = new authorize();
        $this->auth->authorized($f3);
        $this->auth->requireScope($f3, 'extensions:read');
        $this->db = $f3->get('DB');
    }

    function get($f3) {
        $id = (string)$f3->get('PARAMS.id');
        if($id !== '' && !preg_match('/^[0-9]{1,8}$/', $id)) {
            $this->json(400, array('status'=>'error','detail'=>'Invalid extension'));
        }
        $query = 'SELECT u.extension,u.name,d.tech,d.dial,COALESCE(s.data,\'from-internal\') AS context '.
                 'FROM users u LEFT JOIN devices d ON d.id=u.extension '.
                 'LEFT JOIN sip s ON s.id=u.extension AND s.keyword=\'context\'';
        if($id === '') {
            $rows = $this->db->exec($query.' ORDER BY CAST(u.extension AS UNSIGNED) LIMIT 1000');
        } else {
            $rows = $this->db->exec($query.' WHERE u.extension=? LIMIT 1', array($id));
            if(count($rows) === 0) {
                // A missing extension is not a free number: a queue, ring group,
                // conference, parking lot, custom extension or feature code may
                // own it. Availability is answered by /mcpnamespace/<number>.
                $this->json(404, array('status'=>'not_found',
                    'detail'=>'No extension has this number. This does not mean the number is free; another PBX module may reserve it.'));
            }
        }
        $this->json(200, array('results'=>$rows));
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
