<?php
/*
 * Staged extension changes for MCP and the Issabel AI assistant.
 * Mutating tools may only create plans. Execution requires an administrator
 * token with extensions:approve (legacy administrator tokens map to '*').
 */

class mcpplans {
    protected $db;
    protected $pbx;
    protected $auth;
    protected $actor;
    protected $requestId;
    protected $requestAuditContext = array();

    function __construct($f3) {
        $this->auth = new authorize();
        $payload = $this->auth->authorized($f3);
        $this->pbx = $f3->get('DB');
        $this->actor = $this->payloadActor($payload);
        $this->requestId = bin2hex($this->randomBytes(16));

        $path = getenv('PBXAPI_MCP_DB');
        if($path === false || $path === '') {
            $path = '/var/www/db/pbxapi-mcp.sqlite';
        }
        $this->db = new PDO('sqlite:'.$path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA busy_timeout=5000');
        @chmod($path, 0600);
        $this->initializeSchema();
        $this->expirePlans();
    }

    function get($f3) {
        $id = (string)$f3->get('PARAMS.id');
        $auditMode = (string)$f3->get('GET.audit');
        $auditRequested = $id !== '' && $auditMode === '1';
        $rejectionsRequested = $id === '' && $auditMode === 'rejected';
        if($auditRequested || $rejectionsRequested) {
            $this->auth->requireScope($f3, 'extensions:audit');
        } else {
            $this->auth->requireAnyScope($f3, array('plans:read','extensions:read','queues:read','ringgroups:read'));
        }
        if($rejectionsRequested) {
            $stmt = $this->db->prepare('SELECT request_id,actor,method,path,status_code,detail,created_at FROM request_audit ORDER BY id DESC LIMIT 100');
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($events as &$event) {
                $event['status_code'] = intval($event['status_code']);
                $event['created_at'] = gmdate('c', intval($event['created_at']));
                $event['detail'] = json_decode($event['detail'], true);
            }
            $this->json(200, array('events'=>$events));
        }
        if($id === '') {
            $scopes = $f3->get('JWT_SCOPES');
            if(is_array($scopes) && in_array('*', $scopes, true)) {
                $stmt = $this->db->prepare('SELECT id,operation,status,actor,summary,created_at,expires_at,approved_at,executed_at,error FROM plans ORDER BY created_at DESC LIMIT 100');
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare('SELECT id,operation,status,actor,summary,created_at,expires_at,approved_at,executed_at,error FROM plans WHERE actor=? ORDER BY created_at DESC LIMIT 100');
                $stmt->execute(array($this->actor));
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($rows as &$row) {
                $row['summary'] = json_decode($row['summary'], true);
            }
            $this->json(200, array('results'=>$rows));
        }

        $plan = $this->loadPlan($id);
        if(!$this->canViewPlan($f3, $plan)) {
            $this->json(403, array('status'=>'forbidden'));
        }
        if($auditRequested) {
            $stmt = $this->db->prepare('SELECT event,actor,created_at,detail FROM audit WHERE plan_id=? ORDER BY id');
            $stmt->execute(array($id));
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach($events as &$event) {
                $event['created_at'] = gmdate('c', intval($event['created_at']));
                $event['detail'] = json_decode($event['detail'], true);
            }
            $this->json(200, array('plan_id'=>$id,'events'=>$events));
        }
        $this->json(200, $this->publicPlan($plan));
    }

    function post($f3) {
        if((string)$f3->get('PARAMS.id') !== '') {
            $this->json(405, array('status'=>'error','detail'=>'POST accepts no plan id'));
        }

        $input = $this->jsonInput($f3);
        $this->requestAuditContext = $this->safeRequestAuditContext($input);
        $operation = isset($input['operation']) ? strtolower($input['operation']) : 'create';
        if(!in_array($operation, array('create','update','delete','create_queue','delete_queue','create_ringgroup','update_ringgroup','delete_ringgroup','create_time_group','delete_time_group','create_time_condition','delete_time_condition','create_ivr','update_ivr','delete_ivr'), true)) {
            $this->json(422, array('status'=>'error','detail'=>'Allowed operations: create, update, delete, create_queue, delete_queue, create_ringgroup, update_ringgroup, delete_ringgroup, create_time_group, delete_time_group, create_time_condition, delete_time_condition, create_ivr, update_ivr, delete_ivr'));
        }
        $this->auth->requireScope($f3, $this->planScope($operation));

        $normalized = $this->normalizePlan($operation, $input);
        $id = $this->uuidV4();
        $now = time();
        $expires = $now + 1800;
        $payloadJson = $this->canonicalJson($normalized);
        $hash = hash('sha256', $payloadJson);
        $summary = $this->makeSummary($operation, $normalized);

        $stmt = $this->db->prepare('INSERT INTO plans (id,operation,status,actor,payload,summary,payload_hash,created_at,expires_at) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array($id,$operation,'pending_approval',$this->actor,$payloadJson,json_encode($summary),$hash,$now,$expires));
        $this->audit($id, 'created', array('operation'=>$operation,'summary'=>$summary));

        $this->json(201, array(
            'plan_id'=>$id,
            'status'=>'pending_approval',
            'summary'=>$summary,
            'payload_hash'=>$hash,
            'expires_at'=>gmdate('c', $expires)
        ));
    }

    function put($f3) {
        $this->auth->requireScope($f3, 'extensions:approve');
        $id = (string)$f3->get('PARAMS.id');
        if($id === '') {
            $this->json(405, array('status'=>'error','detail'=>'Plan id is required'));
        }
        $plan = $this->loadPlan($id);
        $action = strtolower((string)$f3->get('GET.action'));
        if($action === 'approve') {
            if($plan['status'] !== 'pending_approval' || intval($plan['expires_at']) <= time()) {
                $this->json(409, array('status'=>'error','detail'=>'Plan is not pending approval'));
            }
            $stmt = $this->db->prepare('UPDATE plans SET status=?,approved_by=?,approved_at=? WHERE id=? AND status=?');
            $stmt->execute(array('approved',$this->actor,time(),$id,'pending_approval'));
            $this->audit($id, 'approved', array('approved_by'=>$this->actor));
            $this->json(200, array('plan_id'=>$id,'status'=>'approved'));
        }
        if($action === 'execute') {
            if($plan['status'] !== 'approved') {
                $this->json(409, array('status'=>'error','detail'=>'Plan must be approved before execution'));
            }
            $executionInput = $this->jsonInput($f3, true);
            $this->executePlan($f3, $plan, $executionInput);
        }
        $this->json(400, array('status'=>'error','detail'=>'Allowed actions: approve, execute'));
    }

    function delete($f3) {
        $this->auth->requireAnyScope($f3, array('plans:cancel','extensions:plan','queues:plan','ringgroups:plan'));
        $id = (string)$f3->get('PARAMS.id');
        $plan = $this->loadPlan($id);
        if(!$this->canViewPlan($f3, $plan)) {
            $this->json(403, array('status'=>'forbidden'));
        }
        if(!in_array($plan['status'], array('pending_approval','approved'), true)) {
            $this->json(409, array('status'=>'error','detail'=>'Only pending or approved plans can be cancelled'));
        }
        $stmt = $this->db->prepare('UPDATE plans SET status=? WHERE id=?');
        $stmt->execute(array('cancelled',$id));
        $this->audit($id, 'cancelled', array('actor'=>$this->actor));
        $this->json(200, array('plan_id'=>$id,'status'=>'cancelled'));
    }

    protected function normalizePlan($operation, $input) {
        if($operation === 'create_queue') { return $this->normalizeQueuePlan($input); }
        if($operation === 'delete_queue') { return $this->normalizeDeleteQueuePlan($input); }
        if($operation === 'create_ringgroup') { return $this->normalizeRingGroupPlan($input); }
        if($operation === 'update_ringgroup') { return $this->normalizeUpdateRingGroupPlan($input); }
        if($operation === 'delete_ringgroup') { return $this->normalizeDeleteRingGroupPlan($input); }
        if($operation === 'create_time_group') { return $this->normalizeTimeGroupPlan($input); }
        if($operation === 'delete_time_group') { return $this->normalizeDeleteTimeGroupPlan($input); }
        if($operation === 'create_time_condition') { return $this->normalizeTimeConditionPlan($input); }
        if($operation === 'delete_time_condition') { return $this->normalizeDeleteTimeConditionPlan($input); }
        if($operation === 'create_ivr') { return $this->normalizeIvrPlan($input); }
        if($operation === 'update_ivr') { return $this->normalizeUpdateIvrPlan($input); }
        if($operation === 'delete_ivr') { return $this->normalizeDeleteIvrPlan($input); }
        $selectorKeys = array('operation','extensions','start_extension','count','end_extension');
        if($operation === 'delete') { $this->assertAllowedKeys($input, $selectorKeys); }
        if($operation === 'update') { $this->assertAllowedKeys($input, array_merge($selectorKeys,array('changes'))); }
        if($operation === 'create') { $this->assertAllowedKeys($input, array_merge($selectorKeys,array('profile','name_pattern','voicemail','credentials','codecs','context'))); }
        $extensions = $this->resolveExtensions($input);
        $this->assertExtensionsAvailable($operation, $extensions);

        if($operation === 'delete') {
            return array('extensions'=>$extensions);
        }

        if($operation === 'update') {
            if(!isset($input['changes']) || !is_array($input['changes']) || count($input['changes']) === 0) {
                $this->json(422, array('status'=>'error','detail'=>'Update plans require a non-empty changes object'));
            }
            $sanitized = $this->sanitizeChanges($input['changes']);
            return array('extensions'=>$extensions,'changes'=>$sanitized['fields'],'credential_rotation'=>$sanitized['rotation']);
        }

        if(!isset($input['profile']) || !in_array($input['profile'], array('sip','pjsip','pjsip_webrtc'), true)) {
            $this->json(422, array('status'=>'error','detail'=>'profile must be sip, pjsip, or pjsip_webrtc'));
        }
        $this->assertProfileReady($input['profile']);
        if(!isset($input['voicemail']) || !is_array($input['voicemail']) || !array_key_exists('enabled', $input['voicemail']) || !is_bool($input['voicemail']['enabled'])) {
            $this->json(422, array('status'=>'error','detail'=>'voicemail.enabled must be explicitly true or false'));
        }
        $this->assertAllowedKeys($input['voicemail'], array('enabled','pin_mode'));
        if(isset($input['credentials']) && !is_array($input['credentials'])) {
            $this->json(422, array('status'=>'error','detail'=>'credentials must be an object'));
        }
        if(isset($input['credentials'])) { $this->assertAllowedKeys($input['credentials'], array('password_mode')); }

        $passwordMode = 'generate';
        if(isset($input['credentials']['password_mode'])) {
            $passwordMode = $input['credentials']['password_mode'];
        }
        if(!in_array($passwordMode, array('generate','provided_at_execution'), true)) {
            $this->json(422, array('status'=>'error','detail'=>'Allowed password modes: generate, provided_at_execution'));
        }
        $pinMode = $input['voicemail']['enabled'] ? 'generate' : 'disabled';
        if(isset($input['voicemail']['pin_mode'])) {
            $pinMode = $input['voicemail']['pin_mode'];
        }
        if($input['voicemail']['enabled'] && !in_array($pinMode, array('generate','provided_at_execution'), true)) {
            $this->json(422, array('status'=>'error','detail'=>'Enabled voicemail requires pin_mode generate or provided_at_execution'));
        }

        $defaultCodecs = $input['profile'] === 'pjsip_webrtc' ? array('opus') : array('ulaw','alaw');
        $codecs = isset($input['codecs']) ? $input['codecs'] : $defaultCodecs;
        if(!is_array($codecs) || count($codecs) === 0) {
            $this->json(422, array('status'=>'error','detail'=>'codecs must be a non-empty array'));
        }
        $allowedCodecs = array('ulaw','alaw','opus','g722','gsm');
        foreach($codecs as $codec) {
            if(!in_array($codec, $allowedCodecs, true)) {
                $this->json(422, array('status'=>'error','detail'=>'Unsupported codec: '.$codec));
            }
        }
        $context = isset($input['context']) ? $input['context'] : 'from-internal';
        if($context !== 'from-internal') {
            $this->json(422, array('status'=>'error','detail'=>'Only the from-internal context is allowed in this release'));
        }

        if(isset($input['name_pattern']) && !is_string($input['name_pattern'])) { $this->json(422, array('status'=>'error','detail'=>'name_pattern must be a string')); }
        $namePattern = isset($input['name_pattern']) ? trim($input['name_pattern']) : 'Extension {extension}';
        if($namePattern === '' || strlen($namePattern) > 120 || strpos($namePattern, '{extension}') === false) {
            $this->json(422, array('status'=>'error','detail'=>'name_pattern must contain {extension} and be at most 120 characters'));
        }

        return array(
            'extensions'=>$extensions,
            'profile'=>$input['profile'],
            'name_pattern'=>$namePattern,
            'voicemail'=>array('enabled'=>$input['voicemail']['enabled'],'pin_mode'=>$pinMode),
            'credentials'=>array('password_mode'=>$passwordMode),
            'codecs'=>array_values(array_unique($codecs)),
            'context'=>$context,
            'effective_options'=>$this->profileOptions($input['profile'], $codecs)
        );
    }

    protected function normalizeQueuePlan($input) {
        $this->assertAllowedKeys($input, array('operation','extension','name','strategy','agents','max_wait_seconds','agent_timeout_seconds','retry_seconds','wrapup_seconds','failover'));
        if(!isset($input['extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['extension'])) {
            $this->json(422, array('status'=>'error','detail'=>'Queue extension must contain 1 to 8 digits'));
        }
        $extension = (string)$input['extension'];
        if(!isset($input['name']) || !is_string($input['name']) || trim($input['name']) === '' || strlen(trim($input['name'])) > 80 || preg_match('/[\x00-\x1F\x7F]/', $input['name'])) {
            $this->json(422, array('status'=>'error','detail'=>'Queue name must contain 1 to 80 printable characters'));
        }
        $strategies = array('ringall','leastrecent','fewestcalls','random','rrmemory','rrordered','linear','wrandom');
        if(!isset($input['strategy']) || !in_array($input['strategy'], $strategies, true)) {
            $this->json(422, array('status'=>'error','detail'=>'Unsupported queue strategy'));
        }
        if(!isset($input['agents']) || !is_array($input['agents'])) {
            $this->json(422, array('status'=>'error','detail'=>'agents must explicitly contain static and dynamic arrays'));
        }
        $this->assertAllowedKeys($input['agents'], array('static','dynamic'));
        if(!isset($input['agents']['static']) || !is_array($input['agents']['static']) || !isset($input['agents']['dynamic']) || !is_array($input['agents']['dynamic'])) {
            $this->json(422, array('status'=>'error','detail'=>'agents.static and agents.dynamic must both be arrays'));
        }
        $static = $this->normalizeQueueAgents($input['agents']['static'], 'static');
        $dynamic = $this->normalizeQueueAgents($input['agents']['dynamic'], 'dynamic');
        $allAgents = array_merge(array_keys($static), array_keys($dynamic));
        if(count($allAgents) !== count(array_unique($allAgents))) {
            $this->json(422, array('status'=>'error','detail'=>'An extension cannot be both a static and dynamic queue agent'));
        }
        $this->assertQueueAgentsExist($allAgents);

        if(!array_key_exists('max_wait_seconds', $input)) { $this->json(422, array('status'=>'error','detail'=>'max_wait_seconds is required')); }
        $maxWait = $this->integerInput($input['max_wait_seconds'], 'max_wait_seconds', 0, 86400);
        $agentTimeout = isset($input['agent_timeout_seconds']) ? $this->integerInput($input['agent_timeout_seconds'], 'agent_timeout_seconds', 1, 3600) : 15;
        $retry = isset($input['retry_seconds']) ? $this->integerInput($input['retry_seconds'], 'retry_seconds', 1, 300) : 5;
        $wrapup = isset($input['wrapup_seconds']) ? $this->integerInput($input['wrapup_seconds'], 'wrapup_seconds', 0, 3600) : 0;
        $failover = $this->normalizeFailover(isset($input['failover']) ? $input['failover'] : null);
        $this->assertQueueExtensionAvailable($extension);

        return array(
            'extension'=>$extension,
            'name'=>trim($input['name']),
            'strategy'=>$input['strategy'],
            'agents'=>array('static'=>$static,'dynamic'=>$dynamic),
            'max_wait_seconds'=>$maxWait,
            'agent_timeout_seconds'=>$agentTimeout,
            'retry_seconds'=>$retry,
            'wrapup_seconds'=>$wrapup,
            'failover'=>$failover
        );
    }

    protected function normalizeDeleteQueuePlan($input) {
        $this->assertAllowedKeys($input, array('operation','extension'));
        if(!isset($input['extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['extension'])) {
            $this->json(422, array('status'=>'error','detail'=>'Queue extension must contain 1 to 8 digits'));
        }
        $extension = (string)$input['extension'];
        $queue = $this->queueRecord($extension);
        if($queue === null) { $this->json(404, array('status'=>'not_found','detail'=>'Queue does not exist')); }
        return array('extension'=>$extension,'name'=>(string)$queue['descr'],'expected_hash'=>$this->queueFingerprint($extension));
    }

    protected function queueRecord($extension) {
        $rows = $this->pbx->exec('SELECT extension,descr FROM queues_config WHERE extension=?', array($extension));
        return count($rows) > 0 ? $rows[0] : null;
    }

    protected function queueFingerprint($extension) {
        $config = $this->pbx->exec('SELECT * FROM queues_config WHERE extension=?', array($extension));
        if(count($config) === 0) { return null; }
        $details = $this->pbx->exec('SELECT keyword,data FROM queues_details WHERE id=? ORDER BY keyword,data', array($extension));
        return hash('sha256', $this->canonicalJson(array('config'=>$config[0],'details'=>$details)));
    }

    protected function normalizeQueueAgents($agents, $kind) {
        if(count($agents) > 100) { $this->json(422, array('status'=>'error','detail'=>'A queue supports at most 100 '.$kind.' agents per plan')); }
        $normalized = array();
        foreach($agents as $agent) {
            if(!is_array($agent)) { $this->json(422, array('status'=>'error','detail'=>'Each '.$kind.' agent must be an object')); }
            $this->assertAllowedKeys($agent, array('extension','penalty'));
            if(!isset($agent['extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$agent['extension'])) {
                $this->json(422, array('status'=>'error','detail'=>'Invalid '.$kind.' agent extension'));
            }
            if(!array_key_exists('penalty', $agent)) { $this->json(422, array('status'=>'error','detail'=>'Each '.$kind.' agent requires an explicit penalty')); }
            $extension = (string)$agent['extension'];
            if(isset($normalized[$extension])) { $this->json(422, array('status'=>'error','detail'=>'Duplicate '.$kind.' agent: '.$extension)); }
            $normalized[$extension] = $this->integerInput($agent['penalty'], 'agent penalty', 0, 100);
        }
        return $normalized;
    }

    protected function assertQueueAgentsExist($extensions) {
        if(count($extensions) === 0) { return; }
        $unique = array_values(array_unique($extensions));
        $marks = implode(',', array_fill(0, count($unique), '?'));
        $rows = $this->pbx->exec('SELECT extension FROM users WHERE extension IN ('.$marks.')', $unique);
        $found = array();
        foreach($rows as $row) { $found[] = (string)$row['extension']; }
        $missing = array_values(array_diff($unique, $found));
        if(count($missing) > 0) { $this->json(422, array('status'=>'missing_agents','extensions'=>$missing,'detail'=>'Queue agents must reference existing extensions')); }
    }

    // The destination types a plan may target, each resolved against the shared
    // registry pbxnamespace uses for reads, so a table, column or template is
    // never declared twice. Adding a type is one entry here plus its enum value
    // in the tool schema; reusing a family the registry already knows costs
    // nothing else.
    protected function destinationFamilies() {
        $sources = pbxnamespace::destinationSources();
        $types = array('extension'=>'extensions','queue'=>'queues','ring_group'=>'ringgroups','ivr'=>'ivr');
        $families = array();
        foreach($types as $type=>$family) {
            if(!isset($sources[$family])) { continue; }
            $families[$type] = array('table'=>$sources[$family]['table'], 'field'=>$sources[$family]['value'],
                'template'=>$sources[$family]['template']);
        }
        return $families;
    }

    protected function destinationTypes() {
        return array_merge(array('hangup'), array_keys($this->destinationFamilies()));
    }

    // Destinations are always an allowlisted type plus a value that must exist.
    // An arbitrary dialplan string is never accepted from a caller.
    protected function normalizeFailover($failover) {
        if(!is_array($failover)) { $this->json(422, array('status'=>'error','detail'=>'failover must be an object')); }
        $this->assertAllowedKeys($failover, array('type','destination_extension'));
        $type = isset($failover['type']) ? $failover['type'] : '';
        if(!in_array($type, $this->destinationTypes(), true)) {
            $this->json(422, array('status'=>'error','detail'=>'Allowed failover types: '.implode(', ', $this->destinationTypes())));
        }
        if($type === 'hangup') {
            // failover.type is authoritative. Older provider schemas could
            // populate the optional destination with an empty or stray value.
            return array('type'=>'hangup','destination'=>'app-blackhole,hangup,1');
        }
        if(!isset($failover['destination_extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$failover['destination_extension'])) {
            $this->json(422, array('status'=>'error','detail'=>'Failover destination_extension is required'));
        }
        $destination = (string)$failover['destination_extension'];
        $this->assertFailoverTargetExists($type, $destination);
        $family = $this->destinationFamilies();
        return array('type'=>$type,'destination_extension'=>$destination,
            'destination'=>str_replace('%s', $destination, $family[$type]['template']));
    }

    // Re-checked at execution time: a target can disappear between approval and
    // execution, and the plan must fail with a clear reason rather than write a
    // destination pointing at a number that no longer exists.
    protected function assertFailoverTargetExists($type, $destination) {
        $family = $this->destinationFamilies();
        if(!isset($family[$type])) {
            $this->json(422, array('status'=>'invalid_failover','detail'=>'Unsupported destination type: '.$type));
        }
        $field = $family[$type]['field'];
        $rows = $this->pbx->exec('SELECT '.$field.' FROM '.$family[$type]['table'].' WHERE '.$field.'=?', array($destination));
        if(count($rows) === 0) { $this->json(422, array('status'=>'invalid_failover','detail'=>'Failover destination does not exist')); }
    }

    protected function assertNumberFreeFor($extension, $label) {
        $extension = (string)$extension;
        $rows = $this->pbx->exec('SELECT extension FROM users WHERE extension=?', array($extension));
        if(count($rows) > 0) { $this->json(409, array('status'=>'collision','extensions'=>array($extension),'detail'=>$label.' is already in use')); return; }
        $conflicts = pbxnamespace::numberConflicts($this->pbx, array($extension), array('extensions'));
        if(count($conflicts) > 0) {
            $this->json(409, array('status'=>'collision','extensions'=>array($extension),'sources'=>$conflicts,
                'detail'=>'Number reserved by another PBX module: '.pbxnamespace::conflictDetail($conflicts)));
        }
    }

    protected function assertQueueExtensionAvailable($extension) { $this->assertNumberFreeFor($extension, 'Queue extension'); }

    protected function assertRingGroupExtensionAvailable($extension) { $this->assertNumberFreeFor($extension, 'Ring group extension'); }

    // Each numbered module owns its planning scope; keep the mapping in one place.
    protected function planScope($operation) {
        if($operation === 'create_queue' || $operation === 'delete_queue') { return 'queues:plan'; }
        if($operation === 'create_ringgroup' || $operation === 'update_ringgroup' || $operation === 'delete_ringgroup') { return 'ringgroups:plan'; }
        if($operation === 'create_time_group' || $operation === 'delete_time_group' || $operation === 'create_time_condition' || $operation === 'delete_time_condition') { return 'time:plan'; }
        if($operation === 'create_ivr' || $operation === 'update_ivr' || $operation === 'delete_ivr') { return 'ivr:plan'; }
        return 'extensions:plan';
    }

    protected function normalizeRingGroupName($name) {
        if(!is_string($name) || trim($name) === '' || strlen(trim($name)) > 80 || preg_match('/[\x00-\x1F\x7F]/', $name)) {
            $this->json(422, array('status'=>'error','detail'=>'Ring group name must contain 1 to 80 printable characters'));
        }
        return trim($name);
    }

    protected function normalizeRingGroupStrategy($strategy) {
        // Mirrors the enum in controllers/ringgroups.php.
        $strategies = array('ringall','ringall-prim','hunt','hunt-prim','memoryhunt','memoryhunt-prim','firstavailable','firstnotonphone');
        if(!is_string($strategy) || !in_array($strategy, $strategies, true)) {
            $this->json(422, array('status'=>'error','detail'=>'Unsupported ring group strategy'));
        }
        return $strategy;
    }

    protected function normalizeRingGroupMembers($members) {
        if(!is_array($members) || count($members) === 0) {
            $this->json(422, array('status'=>'error','detail'=>'members must be a non-empty array of extensions'));
        }
        if(count($members) > 100) {
            $this->json(422, array('status'=>'error','detail'=>'A ring group accepts at most 100 members'));
        }
        $normalized = array();
        foreach($members as $member) {
            if(!(is_int($member) || is_string($member)) || !preg_match('/^[0-9]{1,8}$/', (string)$member)) {
                $this->json(422, array('status'=>'error','detail'=>'Each ring group member must contain 1 to 8 digits'));
            }
            $normalized[] = (string)$member;
        }
        if(count($normalized) !== count(array_unique($normalized))) {
            $this->json(422, array('status'=>'error','detail'=>'A ring group member cannot be listed twice'));
        }
        $this->assertRingGroupMembersExist($normalized);
        return $normalized;
    }

    protected function normalizeRingGroupPlan($input) {
        $this->assertAllowedKeys($input, array('operation','extension','name','members','strategy','ring_time_seconds','failover'));
        if(!isset($input['extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['extension'])) {
            $this->json(422, array('status'=>'error','detail'=>'Ring group extension must contain 1 to 8 digits'));
        }
        $extension = (string)$input['extension'];
        if(!isset($input['name'])) { $this->json(422, array('status'=>'error','detail'=>'Ring group name is required')); }
        $name = $this->normalizeRingGroupName($input['name']);
        if(!isset($input['strategy'])) { $this->json(422, array('status'=>'error','detail'=>'Ring group strategy is required')); }
        $strategy = $this->normalizeRingGroupStrategy($input['strategy']);
        if(!isset($input['members'])) { $this->json(422, array('status'=>'error','detail'=>'Ring group members are required')); }
        $members = $this->normalizeRingGroupMembers($input['members']);
        $ringTime = isset($input['ring_time_seconds']) ? $this->integerInput($input['ring_time_seconds'], 'ring_time_seconds', 1, 300) : 20;
        $failover = $this->normalizeFailover(isset($input['failover']) ? $input['failover'] : null);
        $this->assertRingGroupExtensionAvailable($extension);
        return array(
            'extension'=>$extension,
            'name'=>$name,
            'members'=>$members,
            'strategy'=>$strategy,
            'ring_time_seconds'=>$ringTime,
            'failover'=>$failover
        );
    }

    protected function normalizeUpdateRingGroupPlan($input) {
        $this->assertAllowedKeys($input, array('operation','extension','changes'));
        if(!isset($input['extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['extension'])) {
            $this->json(422, array('status'=>'error','detail'=>'Ring group extension must contain 1 to 8 digits'));
        }
        $extension = (string)$input['extension'];
        $ringGroup = $this->ringGroupRecord($extension);
        if($ringGroup === null) { $this->json(404, array('status'=>'not_found','detail'=>'Ring group does not exist')); }
        if(!isset($input['changes']) || !is_array($input['changes']) || count($input['changes']) === 0) {
            $this->json(422, array('status'=>'error','detail'=>'changes must be a non-empty object'));
        }
        $allowed = array('name','members','strategy','ring_time_seconds','failover');
        foreach($input['changes'] as $key=>$value) {
            if(!in_array($key, $allowed, true)) {
                $this->json(422, array('status'=>'error','detail'=>'Update field is not allowed: '.$key));
            }
        }
        $changes = array();
        if(array_key_exists('name', $input['changes'])) { $changes['name'] = $this->normalizeRingGroupName($input['changes']['name']); }
        if(array_key_exists('members', $input['changes'])) { $changes['members'] = $this->normalizeRingGroupMembers($input['changes']['members']); }
        if(array_key_exists('strategy', $input['changes'])) { $changes['strategy'] = $this->normalizeRingGroupStrategy($input['changes']['strategy']); }
        if(array_key_exists('ring_time_seconds', $input['changes'])) { $changes['ring_time_seconds'] = $this->integerInput($input['changes']['ring_time_seconds'], 'ring_time_seconds', 1, 300); }
        if(array_key_exists('failover', $input['changes'])) { $changes['failover'] = $this->normalizeFailover($input['changes']['failover']); }
        if(count($changes) === 0) { $this->json(422, array('status'=>'error','detail'=>'No effective update fields')); }
        return array(
            'extension'=>$extension,
            'name'=>(string)$ringGroup['description'],
            'changes'=>$changes,
            'changed_fields'=>array_keys($changes)
        );
    }

    protected function normalizeDeleteRingGroupPlan($input) {
        $this->assertAllowedKeys($input, array('operation','extension'));
        if(!isset($input['extension']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['extension'])) {
            $this->json(422, array('status'=>'error','detail'=>'Ring group extension must contain 1 to 8 digits'));
        }
        $extension = (string)$input['extension'];
        $ringGroup = $this->ringGroupRecord($extension);
        if($ringGroup === null) { $this->json(404, array('status'=>'not_found','detail'=>'Ring group does not exist')); }
        return array('extension'=>$extension,'name'=>(string)$ringGroup['description'],'expected_hash'=>$this->ringGroupFingerprint($extension));
    }

    // Ring groups are created with POST (the controller refuses an id in the
    // URL) and the API implodes extension_list with '-'. Kept as pure functions
    // so the exact API contract is testable without HTTP.
    protected function ringGroupCreateBody($payload) {
        return array(
            'extension'=>$payload['extension'],
            'name'=>$payload['name'],
            'extension_list'=>$payload['members'],
            'strategy'=>$payload['strategy'],
            'ring_time'=>(string)$payload['ring_time_seconds'],
            'destination_if_no_answer'=>$payload['failover']['destination'],
            'reload'=>true
        );
    }

    // Explicit field mapping: API keys are never built from the input keys.
    protected function ringGroupUpdateBody($changes) {
        $body = array('reload'=>true);
        foreach($changes as $field=>$value) {
            if($field === 'name') { $body['name'] = $value; }
            if($field === 'members') { $body['extension_list'] = $value; }
            if($field === 'strategy') { $body['strategy'] = $value; }
            if($field === 'ring_time_seconds') { $body['ring_time'] = (string)$value; }
            if($field === 'failover') { $body['destination_if_no_answer'] = $value['destination']; }
        }
        return $body;
    }

    protected function ringGroupRecord($extension) {
        $rows = $this->pbx->exec('SELECT * FROM ringgroups WHERE grpnum=?', array($extension));
        return count($rows) === 0 ? null : $rows[0];
    }

    // Same guarantee as queue deletion: a plan approved for one ring group
    // refuses to delete a different one.
    protected function ringGroupFingerprint($extension) {
        return $this->rowFingerprint('ringgroups','grpnum',$extension);
    }

    protected function assertRingGroupMembersExist($extensions) {
        if(count($extensions) === 0) { return; }
        $unique = array_values(array_unique($extensions));
        $marks = implode(',', array_fill(0, count($unique), '?'));
        $rows = $this->pbx->exec('SELECT extension FROM users WHERE extension IN ('.$marks.')', $unique);
        $found = array();
        foreach($rows as $row) { $found[] = (string)$row['extension']; }
        $missing = array_values(array_diff($unique, $found));
        if(count($missing) > 0) { $this->json(422, array('status'=>'missing_members','extensions'=>$missing,'detail'=>'Ring group members must reference existing extensions')); }
    }

    // Hardcoded table and column names only: this helper never receives input.
    protected function rowFingerprint($table, $field, $value) {
        $rows = $this->pbx->exec('SELECT * FROM '.$table.' WHERE '.$field.'=?', array($value));
        if(count($rows) === 0) { return null; }
        return hash('sha256', $this->canonicalJson($rows[0]));
    }

    // Re-checks a destination kept in a plan; a hung up call has no target.
    protected function assertDestinationStillExists($destination) {
        if(!is_array($destination) || !isset($destination['type']) || $destination['type'] === 'hangup') { return; }
        if(!isset($destination['destination_extension'])) { return; }
        $this->assertFailoverTargetExists($destination['type'], $destination['destination_extension']);
    }

    protected function normalizeDisplayName($value, $label) {
        if(!is_string($value) || trim($value) === '' || strlen(trim($value)) > 80 || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            $this->json(422, array('status'=>'error','detail'=>$label.' must contain 1 to 80 printable characters'));
        }
        return trim($value);
    }

    protected function timeGroupRecord($id) {
        $rows = $this->pbx->exec('SELECT * FROM timegroups_groups WHERE id=?', array($id));
        return count($rows) === 0 ? null : $rows[0];
    }

    protected function timeConditionRecord($id) {
        $rows = $this->pbx->exec('SELECT * FROM timeconditions WHERE timeconditions_id=?', array($id));
        return count($rows) === 0 ? null : $rows[0];
    }

    protected function timeGroupFingerprint($id) {
        $group = $this->timeGroupRecord($id);
        if($group === null) { return null; }
        $details = $this->pbx->exec('SELECT time FROM timegroups_details WHERE timegroupid=? ORDER BY time', array($id));
        return hash('sha256', $this->canonicalJson(array('group'=>$group,'details'=>$details)));
    }

    // A time group referenced by a condition cannot be deleted: the condition
    // would be left pointing at a group that no longer exists.
    protected function assertTimeGroupUnused($id) {
        $rows = $this->pbx->exec('SELECT timeconditions_id FROM timeconditions WHERE time=?', array($id));
        if(count($rows) > 0) {
            $ids = array();
            foreach($rows as $row) { $ids[] = (string)$row['timeconditions_id']; }
            $this->json(409, array('status'=>'in_use','timeconditions'=>$ids,
                'detail'=>'Time group is used by time conditions: '.implode(', ', $ids)));
        }
    }

    protected function normalizeTimeRanges($ranges) {
        if(!is_array($ranges) || count($ranges) === 0) {
            $this->json(422, array('status'=>'error','detail'=>'times must be a non-empty array of ranges'));
        }
        if(count($ranges) > 50) {
            $this->json(422, array('status'=>'error','detail'=>'A time group accepts at most 50 ranges'));
        }
        $normalized = array();
        foreach($ranges as $range) {
            try {
                $normalized[] = pbtime::normalizeRange($range);
            } catch(InvalidArgumentException $invalid) {
                $this->json(422, array('status'=>'error','detail'=>$invalid->getMessage()));
            }
        }
        return $normalized;
    }

    protected function normalizeTimeGroupId($value) {
        if(!isset($value) || !preg_match('/^[0-9]{1,8}$/', (string)$value)) {
            $this->json(422, array('status'=>'error','detail'=>'time_group_id must contain 1 to 8 digits'));
        }
        $id = (string)$value;
        if($this->timeGroupRecord($id) === null) {
            $this->json(422, array('status'=>'unknown_time_group','detail'=>'Time group does not exist'));
        }
        return $id;
    }

    protected function normalizeTimeGroupPlan($input) {
        $this->assertAllowedKeys($input, array('operation','name','times'));
        if(!isset($input['name'])) { $this->json(422, array('status'=>'error','detail'=>'Time group name is required')); }
        $name = $this->normalizeDisplayName($input['name'], 'Time group name');
        if(!isset($input['times'])) { $this->json(422, array('status'=>'error','detail'=>'times is required')); }
        $times = $this->normalizeTimeRanges($input['times']);
        return array('name'=>$name, 'times'=>$times, 'range_count'=>count($times));
    }

    protected function normalizeDeleteTimeGroupPlan($input) {
        $this->assertAllowedKeys($input, array('operation','id'));
        if(!isset($input['id']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['id'])) {
            $this->json(422, array('status'=>'error','detail'=>'Time group id must contain 1 to 8 digits'));
        }
        $id = (string)$input['id'];
        $group = $this->timeGroupRecord($id);
        if($group === null) { $this->json(404, array('status'=>'not_found','detail'=>'Time group does not exist')); }
        $this->assertTimeGroupUnused($id);
        return array('id'=>$id, 'name'=>(string)$group['description'], 'expected_hash'=>$this->timeGroupFingerprint($id));
    }

    protected function normalizeTimeConditionPlan($input) {
        $this->assertAllowedKeys($input, array('operation','name','time_group_id','matches','does_not_match'));
        if(!isset($input['name'])) { $this->json(422, array('status'=>'error','detail'=>'Time condition name is required')); }
        $name = $this->normalizeDisplayName($input['name'], 'Time condition name');
        if(!isset($input['time_group_id'])) { $this->json(422, array('status'=>'error','detail'=>'time_group_id is required')); }
        $timeGroupId = $this->normalizeTimeGroupId($input['time_group_id']);
        if(!isset($input['matches'])) { $this->json(422, array('status'=>'error','detail'=>'matches is required')); }
        $matches = $this->normalizeFailover($input['matches']);
        if(!isset($input['does_not_match'])) { $this->json(422, array('status'=>'error','detail'=>'does_not_match is required')); }
        $doesNotMatch = $this->normalizeFailover($input['does_not_match']);
        $group = $this->timeGroupRecord($timeGroupId);
        return array('name'=>$name, 'time_group_id'=>$timeGroupId,
            'time_group_name'=>(string)$group['description'],
            'matches'=>$matches, 'does_not_match'=>$doesNotMatch);
    }

    protected function normalizeDeleteTimeConditionPlan($input) {
        $this->assertAllowedKeys($input, array('operation','id'));
        if(!isset($input['id']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['id'])) {
            $this->json(422, array('status'=>'error','detail'=>'Time condition id must contain 1 to 8 digits'));
        }
        $id = (string)$input['id'];
        $condition = $this->timeConditionRecord($id);
        if($condition === null) { $this->json(404, array('status'=>'not_found','detail'=>'Time condition does not exist')); }
        return array('id'=>$id, 'name'=>(string)$condition['displayname'],
            'expected_hash'=>$this->rowFingerprint('timeconditions','timeconditions_id',$id));
    }

    protected function ivrRecord($id) {
        $rows = $this->pbx->exec('SELECT * FROM ivr_details WHERE id=?', array($id));
        return count($rows) === 0 ? null : $rows[0];
    }

    // Parent row plus its menu, so a plan approved for one IVR cannot delete or
    // rewrite a different menu.
    protected function ivrFingerprint($id) {
        $ivr = $this->ivrRecord($id);
        if($ivr === null) { return null; }
        $entries = $this->pbx->exec('SELECT selection,dest,ivr_ret FROM ivr_entries WHERE ivr_id=? ORDER BY selection', array($id));
        return hash('sha256', $this->canonicalJson(array('ivr'=>$ivr,'entries'=>$entries)));
    }

    protected function ivrEntries($entries) {
        if(!is_array($entries)) { $this->json(422, array('status'=>'error','detail'=>'entries must be an array')); }
        if(count($entries) > 10) { $this->json(422, array('status'=>'error','detail'=>'An IVR menu accepts at most 10 options')); }
        $normalized = array();
        $seen = array();
        foreach($entries as $entry) {
            if(!is_array($entry)) { $this->json(422, array('status'=>'error','detail'=>'Each menu option must be an object')); }
            $this->assertAllowedKeys($entry, array('digits','destination','return_to_ivr'));
            $digits = isset($entry['digits']) ? (string)$entry['digits'] : '';
            if(!preg_match('/^[0-9]$/', $digits)) {
                $this->json(422, array('status'=>'error','detail'=>'Each menu option needs a single digit from 0 to 9'));
            }
            if(isset($seen[$digits])) {
                $this->json(422, array('status'=>'duplicate_digit','detail'=>'Menu digit pressed twice: '.$digits));
            }
            $seen[$digits] = true;
            if(!isset($entry['destination'])) { $this->json(422, array('status'=>'error','detail'=>'Each menu option needs a destination')); }
            $destination = $this->normalizeFailover($entry['destination']);
            $return = true;
            if(array_key_exists('return_to_ivr', $entry)) {
                if(!is_bool($entry['return_to_ivr'])) {
                    $this->json(422, array('status'=>'error','detail'=>'return_to_ivr must be a boolean'));
                }
                $return = $entry['return_to_ivr'];
            }
            $normalized[] = array('digits'=>$digits,'destination'=>$destination,'return_to_ivr'=>$return);
        }
        usort($normalized, function($left, $right) { return strcmp($left['digits'], $right['digits']); });
        return $normalized;
    }

    // $extraAllowed carries the operation key for the create path, where the
    // caller sends it alongside the fields; the update path uses changes alone.
    protected function ivrFields($input, $extraAllowed = array()) {
        $this->assertAllowedKeys($input, array_merge(array('name','description','announcement','timeout_seconds','timeout_destination','invalid_destination','entries'), $extraAllowed));
        $fields = array();
        if(isset($input['name'])) { $fields['name'] = $this->normalizeDisplayName($input['name'], 'IVR name'); }
        if(isset($input['description'])) {
            if(!is_string($input['description']) || strlen($input['description']) > 150 || preg_match('/[\x00-\x1F\x7F]/', $input['description'])) {
                $this->json(422, array('status'=>'error','detail'=>'IVR description must be at most 150 printable characters'));
            }
            $fields['description'] = trim($input['description']);
        }
        if(isset($input['announcement'])) {
            // A recording id from the recordings module; that module is not
            // exposed, so the value is checked as an id, not cross referenced.
            $fields['announcement'] = $this->integerInput($input['announcement'], 'announcement', 0, 999999);
        }
        if(isset($input['timeout_seconds'])) {
            $fields['timeout_seconds'] = $this->integerInput($input['timeout_seconds'], 'timeout_seconds', 1, 300);
        }
        if(isset($input['timeout_destination'])) { $fields['timeout_destination'] = $this->normalizeFailover($input['timeout_destination']); }
        if(isset($input['invalid_destination'])) { $fields['invalid_destination'] = $this->normalizeFailover($input['invalid_destination']); }
        if(isset($input['entries'])) { $fields['entries'] = $this->ivrEntries($input['entries']); }
        return $fields;
    }

    protected function normalizeIvrPlan($input) {
        $fields = $this->ivrFields($input, array('operation'));
        if(!isset($fields['name'])) { $this->json(422, array('status'=>'error','detail'=>'IVR name is required')); }
        if(!isset($fields['timeout_destination'])) { $this->json(422, array('status'=>'error','detail'=>'timeout_destination is required')); }
        if(!isset($fields['invalid_destination'])) { $this->json(422, array('status'=>'error','detail'=>'invalid_destination is required')); }
        if(!isset($fields['entries'])) { $this->json(422, array('status'=>'error','detail'=>'entries is required; use an empty array for a menu with no options')); }
        $fields['timeout_seconds'] = isset($fields['timeout_seconds']) ? $fields['timeout_seconds'] : 10;
        return $fields;
    }

    protected function normalizeUpdateIvrPlan($input) {
        $this->assertAllowedKeys($input, array('operation','id','changes'));
        $id = $this->ivrId($input);
        if(!isset($input['changes']) || !is_array($input['changes']) || count($input['changes']) === 0) {
            $this->json(422, array('status'=>'error','detail'=>'changes must be a non-empty object'));
        }
        $fields = $this->ivrFields($input['changes']);
        if(count($fields) === 0) { $this->json(422, array('status'=>'error','detail'=>'No effective update fields')); }
        $ivr = $this->ivrRecord($id);
        return array('id'=>$id, 'name'=>(string)$ivr['name'], 'changes'=>$fields, 'changed_fields'=>array_keys($fields));
    }

    protected function normalizeDeleteIvrPlan($input) {
        $this->assertAllowedKeys($input, array('operation','id'));
        $id = $this->ivrId($input);
        $ivr = $this->ivrRecord($id);
        return array('id'=>$id, 'name'=>(string)$ivr['name'], 'expected_hash'=>$this->ivrFingerprint($id));
    }

    protected function ivrId($input) {
        if(!isset($input['id']) || !preg_match('/^[0-9]{1,8}$/', (string)$input['id'])) {
            $this->json(422, array('status'=>'error','detail'=>'IVR id must contain 1 to 8 digits'));
        }
        $id = (string)$input['id'];
        if($this->ivrRecord($id) === null) {
            $this->json(404, array('status'=>'not_found','detail'=>'IVR does not exist'));
        }
        return $id;
    }

    // The API stores one row per option and expects return_to_ivr as yes/no.
    protected function ivrEntryRows($entries) {
        $rows = array();
        foreach($entries as $entry) {
            $rows[] = array(
                'digits'=>$entry['digits'],
                'destination'=>$entry['destination']['destination'],
                'return_to_ivr'=>$entry['return_to_ivr'] ? 'yes' : 'no'
            );
        }
        return $rows;
    }

    protected function ivrBodyFields($fields) {
        $body = array();
        if(isset($fields['name'])) { $body['name'] = $fields['name']; }
        if(isset($fields['description'])) { $body['description'] = $fields['description']; }
        if(isset($fields['announcement'])) { $body['announcement'] = $fields['announcement']; }
        if(isset($fields['timeout_seconds'])) { $body['timeout'] = (string)$fields['timeout_seconds']; }
        if(isset($fields['timeout_destination'])) { $body['timeout_destination'] = $fields['timeout_destination']['destination']; }
        if(isset($fields['invalid_destination'])) { $body['invalid_destination'] = $fields['invalid_destination']['destination']; }
        if(isset($fields['entries'])) { $body['entries'] = $this->ivrEntryRows($fields['entries']); }
        return $body;
    }

    protected function ivrCreateBody($payload) {
        $body = $this->ivrBodyFields($payload);
        $body['reload'] = true;
        return $body;
    }

    protected function ivrUpdateBody($changes) {
        $body = $this->ivrBodyFields($changes);
        $body['reload'] = true;
        return $body;
    }

    // The API formats each range itself, so the structured ranges are sent as
    // they are; stored_times in the summary shows what will be written.
    protected function timeGroupCreateBody($payload) {
        return array('name'=>$payload['name'], 'times'=>$payload['times'], 'reload'=>true);
    }

    protected function timeConditionCreateBody($payload) {
        return array(
            'name'=>$payload['name'],
            'time_group_id'=>$payload['time_group_id'],
            'destination_if_time_matches'=>$payload['matches']['destination'],
            'destination_if_time_does_not_match'=>$payload['does_not_match']['destination'],
            'reload'=>true
        );
    }

    protected function resolveExtensions($input) {
        $hasList = isset($input['extensions']);
        $hasRange = isset($input['start_extension']) || isset($input['count']) || isset($input['end_extension']);
        // Older MCP clients sometimes emitted an empty optional list next to a
        // valid range. An empty list carries no selection and must not make the
        // otherwise valid range ambiguous.
        if($hasList && $hasRange && is_array($input['extensions']) && count($input['extensions']) === 0) {
            $hasList = false;
        }
        if($hasList && $hasRange) {
            if($this->selectorsAreEquivalent($input)) {
                $hasRange = false;
            } else {
                $this->json(422, array('status'=>'ambiguous','detail'=>'Use either extensions or start_extension/count, not both unless they select exactly the same extensions'));
            }
        }
        $extensions = array();
        if($hasList) {
            if(!is_array($input['extensions'])) {
                $this->json(422, array('status'=>'error','detail'=>'extensions must be an array'));
            }
            foreach($input['extensions'] as $extension) {
                if(!(is_int($extension) || is_string($extension)) || !preg_match('/^[0-9]{1,8}$/', (string)$extension)) {
                    $this->json(422, array('status'=>'error','detail'=>'Each extension must contain 1 to 8 digits'));
                }
                $extensions[] = (string)$extension;
            }
        } else {
            if(!isset($input['start_extension']) || !isset($input['count'])) {
                $this->json(422, array('status'=>'error','detail'=>'start_extension and count are required'));
            }
            $start = $this->integerInput($input['start_extension'], 'start_extension', 0, 99999999);
            $count = $this->integerInput($input['count'], 'count', 1, 100);
            if(isset($input['end_extension'])) {
                $end = $this->integerInput($input['end_extension'], 'end_extension', 0, 99999999);
                $expected = $end - $start + 1;
                if($expected !== $count) {
                    $this->json(422, array('status'=>'ambiguous','detail'=>'count does not match the inclusive extension range'));
                }
            }
            for($i=0; $i<$count; $i++) {
                if($start+$i > 99999999) { $this->json(422, array('status'=>'error','detail'=>'Extension range exceeds 8 digits')); }
                $extensions[] = (string)($start+$i);
            }
        }
        $extensions = array_values(array_unique($extensions));
        if(count($extensions) < 1 || count($extensions) > 100) {
            $this->json(422, array('status'=>'error','detail'=>'A plan must contain between 1 and 100 unique extensions'));
        }
        foreach($extensions as $extension) {
            if(!preg_match('/^[0-9]{1,8}$/', $extension)) {
                $this->json(422, array('status'=>'error','detail'=>'Invalid extension: '.$extension));
            }
        }
        return $extensions;
    }

    protected function selectorsAreEquivalent($input) {
        if(!isset($input['extensions']) || !is_array($input['extensions']) ||
           !isset($input['start_extension']) || !isset($input['count'])) {
            return false;
        }
        if(!(is_int($input['start_extension']) || (is_string($input['start_extension']) && preg_match('/^[0-9]+$/', $input['start_extension'])))) {
            return false;
        }
        if(!(is_int($input['count']) || (is_string($input['count']) && preg_match('/^[0-9]+$/', $input['count'])))) {
            return false;
        }
        $start = intval($input['start_extension']);
        $count = intval($input['count']);
        if($count < 1 || $count > 100) { return false; }
        if(isset($input['end_extension'])) {
            if(!(is_int($input['end_extension']) || (is_string($input['end_extension']) && preg_match('/^[0-9]+$/', $input['end_extension'])))) {
                return false;
            }
            if(intval($input['end_extension']) !== $start+$count-1) { return false; }
        }
        $explicit = array();
        foreach($input['extensions'] as $extension) {
            if(!(is_int($extension) || is_string($extension)) || !preg_match('/^[0-9]{1,8}$/', (string)$extension)) {
                return false;
            }
            $explicit[] = (string)$extension;
        }
        $explicit = array_values(array_unique($explicit));
        $range = array();
        for($i=0; $i<$count; $i++) { $range[] = (string)($start+$i); }
        sort($explicit, SORT_STRING);
        sort($range, SORT_STRING);
        return $explicit === $range;
    }

    protected function integerInput($value, $field, $minimum, $maximum) {
        if(!(is_int($value) || (is_string($value) && preg_match('/^[0-9]+$/', $value)))) {
            $this->json(422, array('status'=>'error','detail'=>$field.' must be an integer'));
        }
        $number = intval($value);
        if($number < $minimum || $number > $maximum) {
            $this->json(422, array('status'=>'error','detail'=>$field.' is outside the allowed range'));
        }
        return $number;
    }

    protected function assertAllowedKeys($input, $allowed) {
        foreach($input as $key=>$value) {
            if(!in_array($key, $allowed, true)) {
                $this->json(422, array('status'=>'error','detail'=>'Unexpected field: '.$key));
            }
        }
    }

    protected function assertExtensionsAvailable($operation, $extensions) {
        $marks = implode(',', array_fill(0, count($extensions), '?'));
        $rows = $this->pbx->exec('SELECT extension FROM users WHERE extension IN ('.$marks.')', $extensions);
        $existing = array();
        foreach($rows as $row) { $existing[] = (string)$row['extension']; }
        if($operation === 'create') {
            if(count($existing) > 0) {
                $this->json(409, array('status'=>'collision','extensions'=>$existing,
                    'detail'=>'Extension already exists: '.implode(', ', $existing)));
                return;
            }
            // A number can also be taken by a queue, ring group, conference,
            // parking lot, custom extension or feature code. Without this check
            // the plan is approved and only fails mid-execution with HTTP 409.
            // 'extensions' is excluded because users was already checked above.
            $conflicts = pbxnamespace::numberConflicts($this->pbx, $extensions, array('extensions'));
            if(count($conflicts) > 0) {
                $this->json(409, array('status'=>'collision',
                    'extensions'=>array_map('strval', array_keys($conflicts)),
                    'sources'=>$conflicts,
                    'detail'=>'Number reserved by another PBX module: '.pbxnamespace::conflictDetail($conflicts)));
            }
            return;
        }
        $missing = array_values(array_diff($extensions, $existing));
        if(count($missing) > 0) {
            $this->json(404, array('status'=>'missing','extensions'=>$missing));
        }
    }

    protected function profileOptions($profile, $codecs) {
        $options = array(
            'disallow_codecs'=>'all',
            'allow_codecs'=>implode('&', $codecs)
        );
        if($profile === 'sip') {
            $options['transport'] = 'udp';
        } elseif($profile === 'pjsip') {
            $options['transport'] = 'transport-udp';
        } else {
            $cert = getenv('PBXAPI_DTLS_CERT'); if($cert === false || $cert === '') { $cert = '/etc/asterisk/keys/asterisk.pem'; }
            $key = getenv('PBXAPI_DTLS_KEY'); if($key === false || $key === '') { $key = '/etc/asterisk/keys/asterisk.pem'; }
            $options = array_merge($options, array(
                'transport'=>'transport-wss',
                'avpf'=>'yes',
                'force_avp'=>'yes',
                'ice_support'=>'yes',
                'dtls_enable'=>'yes',
                'dtls_verify'=>'fingerprint',
                'dtls_setup'=>'actpass',
                'dtls_certificate_file'=>$cert,
                'dtls_private_key'=>$key,
                'rtcp_mux'=>'yes',
                'encryption'=>'yes'
            ));
        }
        return $options;
    }

    protected function assertProfileReady($profile) {
        if(getenv('PBXAPI_SKIP_PROFILE_CHECKS') === '1') { return; }
        $module = $profile === 'sip' ? 'chan_sip.so' : 'chan_pjsip.so';
        $modules = glob('/usr/lib*/asterisk/modules/'.$module);
        if($modules === false || count($modules) === 0) {
            $this->json(422, array('status'=>'profile_unavailable','detail'=>'Required Asterisk module is unavailable: '.$module));
        }
        if($profile !== 'pjsip_webrtc') { return; }

        foreach(array('res_pjsip_transport_websocket.so','res_http_websocket.so','res_srtp.so') as $webRTCModule) {
            $matches = glob('/usr/lib*/asterisk/modules/'.$webRTCModule);
            if($matches === false || count($matches) === 0) {
                $this->json(422, array('status'=>'profile_unavailable','detail'=>'Required WebRTC module is unavailable: '.$webRTCModule));
            }
        }

        $transport = getenv('PBXAPI_WSS_TRANSPORT'); if($transport === false || $transport === '') { $transport = 'transport-wss'; }
        $transportFound = false;
        $files = glob('/etc/asterisk/pjsip*.conf');
        if(is_array($files)) {
            foreach($files as $file) {
                if(!is_readable($file)) { continue; }
                $content = file_get_contents($file);
                if($content !== false && (strpos($content, '['.$transport.']') !== false || preg_match('/^\s*protocol\s*=\s*wss\s*$/mi', $content))) { $transportFound = true; break; }
            }
        }
        if(!$transportFound) { $this->json(422, array('status'=>'profile_unavailable','detail'=>'PJSIP WSS transport is not configured')); }
        $options = $this->profileOptions($profile, array('ulaw'));
        // Apache only persists these paths; Asterisk is the process that must
        // read the private material. Requiring Apache read access would create
        // a false failure and encourage unsafe key permissions.
        if(!is_file($options['dtls_certificate_file']) || !is_file($options['dtls_private_key'])) {
            $this->json(422, array('status'=>'profile_unavailable','detail'=>'DTLS certificate or private key is unavailable'));
        }
    }

    protected function sanitizeChanges($changes) {
        $allowed = array('name','voicemail','codecs','context','credentials');
        foreach($changes as $key=>$value) {
            if(!in_array($key, $allowed, true)) {
                $this->json(422, array('status'=>'error','detail'=>'Update field is not allowed: '.$key));
            }
        }
        $normalized = array();
        $rotation = array();
        if(isset($changes['name'])) {
            $name = trim((string)$changes['name']);
            if($name === '' || strlen($name) > 120) { $this->json(422, array('status'=>'error','detail'=>'name must be 1 to 120 characters')); }
            $normalized['name'] = $name;
        }
        if(isset($changes['voicemail'])) {
            if(!is_array($changes['voicemail']) || !array_key_exists('enabled', $changes['voicemail']) || !is_bool($changes['voicemail']['enabled'])) {
                $this->json(422, array('status'=>'error','detail'=>'voicemail.enabled must be an explicit boolean'));
            }
            $this->assertAllowedKeys($changes['voicemail'], array('enabled','pin_mode'));
            $normalized['voicemail'] = array('enabled'=>$changes['voicemail']['enabled'] ? 'yes' : 'no');
            if(isset($changes['voicemail']['pin_mode'])) {
                if(!$changes['voicemail']['enabled'] || !in_array($changes['voicemail']['pin_mode'],array('generate','provided_at_execution'),true)) {
                    $this->json(422, array('status'=>'error','detail'=>'PIN rotation requires enabled voicemail and a valid pin_mode'));
                }
                $rotation['voicemail_pin_mode'] = $changes['voicemail']['pin_mode'];
            }
        }
        if(isset($changes['codecs'])) {
            if(!is_array($changes['codecs']) || count($changes['codecs']) === 0) { $this->json(422, array('status'=>'error','detail'=>'codecs must be a non-empty array')); }
            $allowedCodecs = array('ulaw','alaw','opus','g722','gsm');
            foreach($changes['codecs'] as $codec) { if(!is_string($codec) || !in_array($codec,$allowedCodecs,true)) { $this->json(422, array('status'=>'error','detail'=>'Unsupported codec')); } }
            $normalized['device_options'] = array('disallow_codecs'=>'all','allow_codecs'=>implode('&',array_values(array_unique($changes['codecs']))));
        }
        if(isset($changes['context'])) {
            if($changes['context'] !== 'from-internal') { $this->json(422, array('status'=>'error','detail'=>'Only from-internal is allowed')); }
            $normalized['context'] = 'from-internal';
        }
        if(isset($changes['credentials'])) {
            if(!is_array($changes['credentials'])) { $this->json(422, array('status'=>'error','detail'=>'credentials must be an object')); }
            $this->assertAllowedKeys($changes['credentials'], array('password_mode'));
            if(!isset($changes['credentials']['password_mode']) || !in_array($changes['credentials']['password_mode'],array('generate','provided_at_execution'),true)) {
                $this->json(422, array('status'=>'error','detail'=>'Password rotation requires password_mode generate or provided_at_execution'));
            }
            $rotation['password_mode'] = $changes['credentials']['password_mode'];
        }
        if(count($normalized) === 0 && count($rotation) === 0) { $this->json(422, array('status'=>'error','detail'=>'No effective update fields')); }
        return array('fields'=>$normalized,'rotation'=>$rotation);
    }

    protected function executePlan($f3, $plan, $executionInput) {
        $payload = json_decode($plan['payload'], true);
        if(!is_array($payload) || hash('sha256', $this->canonicalJson($payload)) !== $plan['payload_hash']) {
            $this->failPlan($plan['id'], 'Plan integrity check failed');
        }
        if($plan['operation'] === 'create_queue') {
            $this->assertQueueExtensionAvailable($payload['extension']);
            $this->assertQueueAgentsExist(array_merge(array_keys($payload['agents']['static']), array_keys($payload['agents']['dynamic'])));
        } elseif($plan['operation'] === 'delete_queue') {
            $currentHash = $this->queueFingerprint($payload['extension']);
            if($currentHash === null) { $this->json(404, array('status'=>'not_found','detail'=>'Queue no longer exists')); }
            if(!isset($payload['expected_hash']) || !$this->secureEquals($payload['expected_hash'], $currentHash)) {
                $this->json(409, array('status'=>'queue_changed','detail'=>'Queue changed after the deletion plan was created; create a new plan'));
            }
        } elseif($plan['operation'] === 'create_ringgroup') {
            $this->assertRingGroupExtensionAvailable($payload['extension']);
            $this->assertRingGroupMembersExist($payload['members']);
        } elseif($plan['operation'] === 'delete_ringgroup') {
            $currentHash = $this->ringGroupFingerprint($payload['extension']);
            if($currentHash === null) { $this->json(404, array('status'=>'not_found','detail'=>'Ring group no longer exists')); }
            if(!isset($payload['expected_hash']) || !$this->secureEquals($payload['expected_hash'], $currentHash)) {
                $this->json(409, array('status'=>'ringgroup_changed','detail'=>'Ring group changed after the deletion plan was created; create a new plan'));
            }
        } elseif($plan['operation'] === 'update_ringgroup') {
            if($this->ringGroupRecord($payload['extension']) === null) {
                $this->json(404, array('status'=>'not_found','detail'=>'Ring group no longer exists'));
            }
            if(isset($payload['changes']['members'])) { $this->assertRingGroupMembersExist($payload['changes']['members']); }
            if(isset($payload['changes']['failover'])) { $this->assertDestinationStillExists($payload['changes']['failover']); }
        } elseif($plan['operation'] === 'delete_time_group') {
            $currentHash = $this->timeGroupFingerprint($payload['id']);
            if($currentHash === null) { $this->json(404, array('status'=>'not_found','detail'=>'Time group no longer exists')); }
            $this->assertTimeGroupUnused($payload['id']);
            if(!isset($payload['expected_hash']) || !$this->secureEquals($payload['expected_hash'], $currentHash)) {
                $this->json(409, array('status'=>'timegroup_changed','detail'=>'Time group changed after the deletion plan was created; create a new plan'));
            }
        } elseif($plan['operation'] === 'create_time_condition') {
            $this->normalizeTimeGroupId($payload['time_group_id']);
            $this->assertDestinationStillExists($payload['matches']);
            $this->assertDestinationStillExists($payload['does_not_match']);
        } elseif($plan['operation'] === 'delete_time_condition') {
            $currentHash = $this->rowFingerprint('timeconditions','timeconditions_id',$payload['id']);
            if($currentHash === null) { $this->json(404, array('status'=>'not_found','detail'=>'Time condition no longer exists')); }
            if(!isset($payload['expected_hash']) || !$this->secureEquals($payload['expected_hash'], $currentHash)) {
                $this->json(409, array('status'=>'timecondition_changed','detail'=>'Time condition changed after the deletion plan was created; create a new plan'));
            }
        } elseif($plan['operation'] === 'create_ivr') {
            // Every target can disappear between approval and execution.
            $this->assertDestinationStillExists($payload['timeout_destination']);
            $this->assertDestinationStillExists($payload['invalid_destination']);
            foreach($payload['entries'] as $entry) { $this->assertDestinationStillExists($entry['destination']); }
        } elseif($plan['operation'] === 'update_ivr') {
            if($this->ivrRecord($payload['id']) === null) { $this->json(404, array('status'=>'not_found','detail'=>'IVR no longer exists')); }
            foreach(array('timeout_destination','invalid_destination') as $key) {
                if(isset($payload['changes'][$key])) { $this->assertDestinationStillExists($payload['changes'][$key]); }
            }
            if(isset($payload['changes']['entries'])) {
                foreach($payload['changes']['entries'] as $entry) { $this->assertDestinationStillExists($entry['destination']); }
            }
        } elseif($plan['operation'] === 'delete_ivr') {
            $currentHash = $this->ivrFingerprint($payload['id']);
            if($currentHash === null) { $this->json(404, array('status'=>'not_found','detail'=>'IVR no longer exists')); }
            if(!isset($payload['expected_hash']) || !$this->secureEquals($payload['expected_hash'], $currentHash)) {
                $this->json(409, array('status'=>'ivr_changed','detail'=>'IVR changed after the deletion plan was created; create a new plan'));
            }
        } else {
            $this->assertExtensionsAvailable($plan['operation'], $payload['extensions']);
            if($plan['operation'] === 'create') { $this->assertProfileReady($payload['profile']); }
        }

        $stmt = $this->db->prepare('UPDATE plans SET status=? WHERE id=? AND status=?');
        $stmt->execute(array('executing',$plan['id'],'approved'));
        if($stmt->rowCount() !== 1) {
            $this->json(409, array('status'=>'error','detail'=>'Plan execution already started'));
        }

        try {
            if($plan['operation'] === 'create_queue') {
                $body = array(
                    'name'=>$payload['name'],
                    'strategy'=>$payload['strategy'],
                    'maxwait'=>(string)$payload['max_wait_seconds'],
                    'timeout'=>(string)$payload['agent_timeout_seconds'],
                    'retry'=>(string)$payload['retry_seconds'],
                    'wrapuptime'=>(string)$payload['wrapup_seconds'],
                    'dest'=>$payload['failover']['destination'],
                    'static_members'=>$this->queueMemberList($payload['agents']['static']),
                    'dynamic_members'=>$this->queueMemberList($payload['agents']['dynamic']),
                    'restrict_dynamic_agents'=>'0',
                    'cron_schedule'=>'never',
                    'reload'=>true
                );
                $this->internalRequest($f3, 'PUT', '/queues/'.$payload['extension'].'?create_only=1', $body);
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('queue_extension'=>$payload['extension']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'delete_queue') {
                $this->internalRequest($f3, 'DELETE', '/queues/'.$payload['extension'], array('reload'=>true));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('deleted_queue'=>$payload['extension']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'create_ringgroup') {
                $this->internalRequest($f3, 'POST', '/ringgroups', $this->ringGroupCreateBody($payload));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('ringgroup_extension'=>$payload['extension']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'delete_ringgroup') {
                // The controller json_decodes the body and rejects an empty one.
                $this->internalRequest($f3, 'DELETE', '/ringgroups/'.$payload['extension'], array('reload'=>true));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('deleted_ringgroup'=>$payload['extension']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'update_ringgroup') {
                $this->internalRequest($f3, 'PUT', '/ringgroups/'.$payload['extension'], $this->ringGroupUpdateBody($payload['changes']));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('ringgroup_extension'=>$payload['extension'],'changed_fields'=>$payload['changed_fields']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'create_time_group') {
                $this->internalRequest($f3, 'POST', '/timegroups', $this->timeGroupCreateBody($payload));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('time_group'=>$payload['name']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'delete_time_group') {
                $this->internalRequest($f3, 'DELETE', '/timegroups/'.$payload['id'], array('reload'=>true));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('deleted_time_group'=>$payload['id']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'create_time_condition') {
                $this->internalRequest($f3, 'POST', '/timeconditions', $this->timeConditionCreateBody($payload));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('time_condition'=>$payload['name']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'delete_time_condition') {
                $this->internalRequest($f3, 'DELETE', '/timeconditions/'.$payload['id'], array('reload'=>true));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('deleted_time_condition'=>$payload['id']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'create_ivr') {
                $this->internalRequest($f3, 'POST', '/ivr', $this->ivrCreateBody($payload));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('ivr'=>$payload['name']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'update_ivr') {
                // The API replaces the whole menu when entries is present.
                $this->internalRequest($f3, 'PUT', '/ivr/'.$payload['id'], $this->ivrUpdateBody($payload['changes']));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('ivr_id'=>$payload['id'],'changed_fields'=>$payload['changed_fields']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'delete_ivr') {
                $this->internalRequest($f3, 'DELETE', '/ivr/'.$payload['id'], array('reload'=>true));
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('deleted_ivr'=>$payload['id']));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'create') {
                $credentials = $this->executeCreate($f3, $payload, $executionInput, $plan['id']);
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('count'=>count($payload['extensions'])));
                $this->csv($credentials, 'issabel-extension-credentials-'.$plan['id'].'.csv');
            }
            if($plan['operation'] === 'delete') {
                $this->internalRequest($f3, 'DELETE', '/extensions/'.implode(',', $payload['extensions']), null);
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('count'=>count($payload['extensions'])));
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
            if($plan['operation'] === 'update') {
                $count = count($payload['extensions']);
                $credentials = array();
                $usedPasswords = array();
                foreach($payload['extensions'] as $index=>$extension) {
                    $body = $payload['changes'];
                    $password = '';
                    $pin = '';
                    if(isset($payload['credential_rotation']['password_mode'])) {
                        $password = $this->executionSecretForMode($payload['credential_rotation']['password_mode'], $executionInput, $extension);
                        $passwordHash = hash('sha256', $password);
                        if(isset($usedPasswords[$passwordHash])) { throw new RuntimeException('Device passwords must be unique within the batch'); }
                        $usedPasswords[$passwordHash] = true;
                        $body['secret'] = $password;
                    }
                    if(isset($payload['credential_rotation']['voicemail_pin_mode'])) {
                        $pin = $this->executionPinForMode($payload['credential_rotation']['voicemail_pin_mode'], $executionInput, $extension);
                        if(!isset($body['voicemail'])) { $body['voicemail'] = array('enabled'=>'yes'); }
                        $body['voicemail']['pin'] = $pin;
                    }
                    $body['reload'] = ($index === $count-1);
                    $this->internalRequest($f3, 'PUT', '/extensions/'.$extension, $body);
                    if($password !== '' || $pin !== '') { $credentials[] = array('extension'=>$extension,'username'=>$extension,'password'=>$password,'voicemail_pin'=>$pin); }
                }
                $this->markExecuted($plan['id']);
                $this->audit($plan['id'], 'executed', array('count'=>$count));
                if(count($credentials) > 0) { $this->csv($credentials, 'issabel-extension-credentials-'.$plan['id'].'.csv'); }
                $this->json(200, array('plan_id'=>$plan['id'],'status'=>'executed'));
            }
        } catch(Exception $e) {
            $this->failPlan($plan['id'], $e->getMessage());
        }
    }

    protected function queueMemberList($members) {
        $result = array();
        foreach($members as $extension=>$penalty) { $result[] = (string)$extension.','.intval($penalty); }
        return $result;
    }

    protected function executeCreate($f3, $payload, $executionInput, $planId = '') {
        $created = array();
        $credentials = array();
        $usedPasswords = array();
        $count = count($payload['extensions']);
        try {
            foreach($payload['extensions'] as $index=>$extension) {
                $password = $this->executionSecret($payload, $executionInput, 'passwords', $extension, 32);
                $passwordHash = hash('sha256', $password);
                if(isset($usedPasswords[$passwordHash])) { throw new RuntimeException('Device passwords must be unique within the batch'); }
                $usedPasswords[$passwordHash] = true;
                $pin = '';
                if($payload['voicemail']['enabled']) {
                    $pin = $this->executionPin($payload, $executionInput, $extension);
                }
                $body = array(
                    'name'=>str_replace('{extension}', $extension, $payload['name_pattern']),
                    'tech'=>$payload['profile'] === 'sip' ? 'sip' : 'pjsip',
                    'context'=>$payload['context'],
                    'secret'=>$password,
                    'callerid_override'=>array('emergency'=>''),
                    'voicemail'=>array('enabled'=>$payload['voicemail']['enabled'] ? 'yes' : 'no'),
                    'device_options'=>$payload['effective_options'],
                    'reload'=>($index === $count-1)
                );
                if($pin !== '') { $body['voicemail']['pin'] = $pin; }
                $this->internalRequest($f3, 'PUT', '/extensions/'.$extension.'?create_only=1', $body);
                $created[] = $extension;
                $credentials[] = array('extension'=>$extension,'username'=>$extension,'password'=>$password,'voicemail_pin'=>$pin);
            }
        } catch(Exception $e) {
            // Compensate the extensions that were already created. A failure here
            // used to be swallowed, leaving the PBX with orphan extensions and no
            // trace of it, so both outcomes are audited and reported.
            if(count($created) > 0) {
                try {
                    $this->internalRequest($f3, 'DELETE', '/extensions/'.implode(',', $created), null);
                    $this->audit($planId, 'rolled_back', array('extensions'=>$created));
                } catch(Exception $rollback) {
                    $rollbackDetail = $this->safeDiagnosticText($rollback->getMessage());
                    $this->audit($planId, 'rollback_failed', array('extensions'=>$created, 'error'=>$rollbackDetail));
                    throw new RuntimeException($e->getMessage()
                        .' [rollback of '.implode(',', $created).' failed, these extensions may still exist: '.$rollbackDetail.']');
                }
            }
            throw $e;
        }
        return $credentials;
    }

    protected function executionSecret($payload, $input, $bucket, $extension, $length) {
        if($payload['credentials']['password_mode'] === 'provided_at_execution') {
            if(!isset($input[$bucket][$extension]) || strlen($input[$bucket][$extension]) < 16) {
                throw new RuntimeException('A password of at least 16 characters is required for extension '.$extension);
            }
            return (string)$input[$bucket][$extension];
        }
        return $this->randomString($length, 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789');
    }

    protected function executionPin($payload, $input, $extension) {
        if($payload['voicemail']['pin_mode'] === 'provided_at_execution') {
            if(!isset($input['voicemail_pins'][$extension]) || !preg_match('/^[0-9]{4,12}$/', $input['voicemail_pins'][$extension])) {
                throw new RuntimeException('A 4 to 12 digit voicemail PIN is required for extension '.$extension);
            }
            return (string)$input['voicemail_pins'][$extension];
        }
        return $this->randomString(6, '0123456789');
    }

    protected function executionSecretForMode($mode, $input, $extension) {
        if($mode === 'provided_at_execution') {
            if(!isset($input['passwords'][$extension]) || strlen($input['passwords'][$extension]) < 16) {
                throw new RuntimeException('A password of at least 16 characters is required for extension '.$extension);
            }
            return (string)$input['passwords'][$extension];
        }
        return $this->randomString(32, 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789');
    }

    protected function executionPinForMode($mode, $input, $extension) {
        if($mode === 'provided_at_execution') {
            if(!isset($input['voicemail_pins'][$extension]) || !preg_match('/^[0-9]{4,12}$/', $input['voicemail_pins'][$extension])) {
                throw new RuntimeException('A 4 to 12 digit voicemail PIN is required for extension '.$extension);
            }
            return (string)$input['voicemail_pins'][$extension];
        }
        return $this->randomString(6, '0123456789');
    }

    protected function internalRequest($f3, $method, $path, $body) {
        if(!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is required to execute plans');
        }
        $base = getenv('PBXAPI_INTERNAL_URL');
        if($base === false || $base === '') { $base = 'https://127.0.0.1/pbxapi'; }
        $base = rtrim($base, '/');
        if(preg_match('#^http://(127\.0\.0\.1|localhost)(:[0-9]+)?(/|$)#i', $base)) {
            $base = 'https://'.substr($base, 7);
        }
        $headers = $f3->get('HEADERS');
        $curl = curl_init($base.$path);
        $authorization = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
        if($authorization === '') { throw new RuntimeException('Authorization header unavailable for plan execution'); }
        $requestHeaders = array('Authorization: '.$authorization, 'Accept: application/json');
        if($body !== null) {
            $requestHeaders[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        $target = parse_url($base);
        $host = isset($target['host']) ? strtolower($target['host']) : '';
        if(isset($target['scheme']) && strtolower($target['scheme']) === 'https' && in_array($host, array('127.0.0.1','localhost','::1'), true)) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if($response === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('PBX API request failed (HTTP '.$status.') during '.$method.' '.$path.$this->failureDiagnostic($status, $response).($error !== '' ? ': '.$error : ''));
        }
        return $response;
    }

    // Response text from the PBX API is never trusted: keep it bounded, strip
    // control characters and redact anything token shaped, which could be a
    // generated credential, before it can reach the audit trail.
    protected function safeDiagnosticText($value, $limit = 200) {
        if(!is_scalar($value)) { return ''; }
        $text = preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$value);
        $text = preg_replace('/[A-Za-z0-9+\/_=-]{20,}/', '<redacted>', (string)$text);
        $text = trim(preg_replace('/\s+/', ' ', (string)$text));
        return substr($text, 0, $limit);
    }

    // Allow only bounded, credential-free explanations, never arbitrary response
    // text (successful execution responses may include generated credentials).
    // Every rejected request must leave a reason behind: a bare "HTTP 404" or
    // "HTTP 409" is not actionable.
    protected function failureDiagnostic($status, $response) {
        if(intval($status) < 400 || !is_string($response)) { return ''; }
        $data = json_decode($response, true);
        if(!is_array($data)) {
            // Keep a silent failure diagnosable even when the body is not the
            // expected JSON envelope, for example an HTML error page.
            $shape = ($response === '') ? 'empty' : 'unparseable';
            error_log('[pbxapi.mcpplans] HTTP '.intval($status).' body '.$shape.' ('.strlen($response).' bytes) during plan execution');
            return ' response_body='.$shape;
        }
        $details = array();
        if(isset($data['detail'])) {
            $text = $this->safeDiagnosticText($data['detail'], 140);
            if($text !== '') { $details[] = $text; }
        }
        $diagnostic = array();
        if(isset($data['errors']) && is_array($data['errors'])) {
            foreach($data['errors'] as $error) {
                if(!is_array($error)) { continue; }
                if(isset($error['detail'])) {
                    $text = $this->safeDiagnosticText($error['detail'], 140);
                    if($text !== '' && !in_array($text, $details, true)) { $details[] = $text; }
                }
                if(count($diagnostic) === 0 && isset($error['code']) && $error['code'] === 'extension_exists' &&
                   isset($error['diagnostic']) && is_array($error['diagnostic'])) {
                    $source = $error['diagnostic'];
                    $safe = array();
                    foreach(array('requested_extension', 'mapped_extension') as $key) {
                        if(isset($source[$key]) && is_scalar($source[$key]) && preg_match('/^[0-9]{0,20}$/D', (string)$source[$key])) {
                            $safe[$key] = (string)$source[$key];
                        }
                    }
                    if(isset($source['direct_matches']) && is_array($source['direct_matches'])) {
                        $safe['direct_matches'] = array();
                        foreach(array_slice($source['direct_matches'], 0, 3) as $extension) {
                            if(is_scalar($extension) && preg_match('/^[0-9]{1,20}$/D', (string)$extension)) {
                                $safe['direct_matches'][] = (string)$extension;
                            }
                        }
                    }
                    if(isset($source['direct_query_failed']) && $source['direct_query_failed'] === true) { $safe['direct_query_failed'] = true; }
                    if(count($safe) > 0) { $diagnostic = $safe; }
                }
            }
        }
        $out = '';
        if(count($diagnostic) > 0) { $out .= ' conflict_diagnostic='.json_encode($diagnostic); }
        if(count($details) > 0) { $out .= ' reason='.json_encode(array_slice($details, 0, 3)); }
        if($out === '') { $out = ' response_body=unexplained'; }
        return $out;
    }

    protected function initializeSchema() {
        $this->db->exec('CREATE TABLE IF NOT EXISTS plans (id TEXT PRIMARY KEY, operation TEXT NOT NULL, status TEXT NOT NULL, actor TEXT NOT NULL, payload TEXT NOT NULL, summary TEXT NOT NULL, payload_hash TEXT NOT NULL, created_at INTEGER NOT NULL, expires_at INTEGER NOT NULL, approved_by TEXT, approved_at INTEGER, executed_at INTEGER, error TEXT)');
        $this->db->exec('CREATE TABLE IF NOT EXISTS audit (id INTEGER PRIMARY KEY AUTOINCREMENT, plan_id TEXT NOT NULL, event TEXT NOT NULL, actor TEXT NOT NULL, created_at INTEGER NOT NULL, detail TEXT NOT NULL)');
        $this->db->exec('CREATE TABLE IF NOT EXISTS request_audit (id INTEGER PRIMARY KEY AUTOINCREMENT, request_id TEXT NOT NULL, actor TEXT NOT NULL, method TEXT NOT NULL, path TEXT NOT NULL, status_code INTEGER NOT NULL, detail TEXT NOT NULL, created_at INTEGER NOT NULL)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS plans_actor_created ON plans(actor,created_at)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS request_audit_created ON request_audit(created_at)');
    }

    protected function expirePlans() {
        $stmt = $this->db->prepare("UPDATE plans SET status='expired' WHERE status IN ('pending_approval','approved') AND expires_at<=?");
        $stmt->execute(array(time()));
        $stmt = $this->db->prepare('DELETE FROM request_audit WHERE created_at<?');
        $stmt->execute(array(time()-(30*24*60*60)));
    }

    protected function loadPlan($id) {
        if(!preg_match('/^[a-f0-9-]{36}$/', (string)$id)) {
            $this->json(404, array('status'=>'not_found'));
        }
        $stmt = $this->db->prepare('SELECT * FROM plans WHERE id=?');
        $stmt->execute(array($id));
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$plan) { $this->json(404, array('status'=>'not_found')); }
        return $plan;
    }

    protected function canViewPlan($f3, $plan) {
        $scopes = $f3->get('JWT_SCOPES');
        return $plan['actor'] === $this->actor || (is_array($scopes) && in_array('*', $scopes, true));
    }

    protected function publicPlan($plan) {
        return array(
            'plan_id'=>$plan['id'],
            'operation'=>$plan['operation'],
            'status'=>$plan['status'],
            'actor'=>$plan['actor'],
            'summary'=>json_decode($plan['summary'], true),
            'payload_hash'=>$plan['payload_hash'],
            'created_at'=>gmdate('c', intval($plan['created_at'])),
            'expires_at'=>gmdate('c', intval($plan['expires_at'])),
            'approved_at'=>$plan['approved_at'] ? gmdate('c', intval($plan['approved_at'])) : null,
            'executed_at'=>$plan['executed_at'] ? gmdate('c', intval($plan['executed_at'])) : null,
            'error'=>$plan['error']
        );
    }

    protected function makeSummary($operation, $payload) {
        if($operation === 'create_queue') {
            return array(
                'operation'=>'create_queue',
                'extension'=>$payload['extension'],
                'name'=>$payload['name'],
                'strategy'=>$payload['strategy'],
                'static_agents'=>$payload['agents']['static'],
                'dynamic_agents'=>$payload['agents']['dynamic'],
                'max_wait_seconds'=>$payload['max_wait_seconds'],
                'agent_timeout_seconds'=>$payload['agent_timeout_seconds'],
                'retry_seconds'=>$payload['retry_seconds'],
                'wrapup_seconds'=>$payload['wrapup_seconds'],
                'failover'=>$payload['failover']['type'],
                'failover_extension'=>isset($payload['failover']['destination_extension']) ? $payload['failover']['destination_extension'] : null
            );
        }
        if($operation === 'delete_queue') {
            return array('operation'=>'delete_queue','extension'=>$payload['extension'],'name'=>$payload['name']);
        }
        if($operation === 'create_ringgroup') {
            return array(
                'operation'=>'create_ringgroup',
                'extension'=>$payload['extension'],
                'name'=>$payload['name'],
                'members'=>$payload['members'],
                'member_count'=>count($payload['members']),
                'strategy'=>$payload['strategy'],
                'ring_time_seconds'=>$payload['ring_time_seconds'],
                'failover'=>$payload['failover']['type'],
                'failover_extension'=>isset($payload['failover']['destination_extension']) ? $payload['failover']['destination_extension'] : null
            );
        }
        if($operation === 'delete_ringgroup') {
            return array('operation'=>'delete_ringgroup','extension'=>$payload['extension'],'name'=>$payload['name']);
        }
        if($operation === 'update_ringgroup') {
            return array(
                'operation'=>'update_ringgroup',
                'extension'=>$payload['extension'],
                'name'=>$payload['name'],
                'changed_fields'=>$payload['changed_fields'],
                'changes'=>$payload['changes']
            );
        }
        if($operation === 'create_time_group') {
            $stored = array();
            foreach($payload['times'] as $range) { $stored[] = pbtime::format($range); }
            return array('operation'=>'create_time_group','name'=>$payload['name'],'times'=>$payload['times'],
                'stored_times'=>$stored,'range_count'=>$payload['range_count']);
        }
        if($operation === 'delete_time_group') {
            return array('operation'=>'delete_time_group','id'=>$payload['id'],'name'=>$payload['name']);
        }
        if($operation === 'create_time_condition') {
            return array('operation'=>'create_time_condition','name'=>$payload['name'],
                'time_group_id'=>$payload['time_group_id'],'time_group_name'=>$payload['time_group_name'],
                'matches'=>$payload['matches'],'does_not_match'=>$payload['does_not_match']);
        }
        if($operation === 'delete_time_condition') {
            return array('operation'=>'delete_time_condition','id'=>$payload['id'],'name'=>$payload['name']);
        }
        if($operation === 'create_ivr') {
            return array('operation'=>'create_ivr','name'=>$payload['name'],
                'description'=>isset($payload['description']) ? $payload['description'] : '',
                'timeout_seconds'=>$payload['timeout_seconds'],
                'timeout_destination'=>$payload['timeout_destination'],
                'invalid_destination'=>$payload['invalid_destination'],
                'entries'=>$payload['entries'],'entry_count'=>count($payload['entries']));
        }
        if($operation === 'update_ivr') {
            return array('operation'=>'update_ivr','id'=>$payload['id'],'name'=>$payload['name'],
                'changed_fields'=>$payload['changed_fields'],'changes'=>$payload['changes']);
        }
        if($operation === 'delete_ivr') {
            return array('operation'=>'delete_ivr','id'=>$payload['id'],'name'=>$payload['name']);
        }
        $summary = array('operation'=>$operation,'extensions'=>$payload['extensions'],'count'=>count($payload['extensions']));
        if($operation === 'create') {
            $summary['profile'] = $payload['profile'];
            $summary['voicemail'] = $payload['voicemail']['enabled'];
            $summary['voicemail_pin_mode'] = $payload['voicemail']['pin_mode'];
            $summary['password_mode'] = $payload['credentials']['password_mode'];
            $summary['name_pattern'] = $payload['name_pattern'];
            $summary['codecs'] = $payload['codecs'];
            $summary['context'] = $payload['context'];
            $summary['effective_options'] = $payload['effective_options'];
        } elseif($operation === 'update') {
            $summary['changes'] = $payload['changes'];
            if(isset($payload['credential_rotation']['password_mode'])) { $summary['password_mode'] = $payload['credential_rotation']['password_mode']; }
            if(isset($payload['credential_rotation']['voicemail_pin_mode'])) { $summary['voicemail_pin_mode'] = $payload['credential_rotation']['voicemail_pin_mode']; }
        }
        return $summary;
    }

    protected function markExecuted($id) {
        $stmt = $this->db->prepare('UPDATE plans SET status=?,executed_at=?,error=NULL WHERE id=?');
        $stmt->execute(array('executed',time(),$id));
    }

    protected function failPlan($id, $message) {
        $safe = substr(preg_replace('/[\r\n]+/', ' ', (string)$message), 0, 500);
        $stmt = $this->db->prepare('UPDATE plans SET status=?,error=? WHERE id=?');
        $stmt->execute(array('failed',$safe,$id));
        $this->audit($id, 'failed', array('error'=>$safe));
        $this->json(500, array('plan_id'=>$id,'status'=>'failed','detail'=>$safe));
    }

    protected function audit($planId, $event, $detail) {
        $stmt = $this->db->prepare('INSERT INTO audit (plan_id,event,actor,created_at,detail) VALUES (?,?,?,?,?)');
        $stmt->execute(array($planId,$event,$this->actor,time(),json_encode($detail)));
    }

    protected function auditRejectedRequest($status, $data) {
        $safe = array();
        foreach(array('status','detail','required_scope','extensions') as $key) {
            if(isset($data[$key])) { $safe[$key] = $data[$key]; }
        }
        if(isset($safe['detail'])) {
            $safe['detail'] = substr(preg_replace('/[\r\n]+/', ' ', (string)$safe['detail']), 0, 500);
        }
        if(isset($safe['extensions']) && is_array($safe['extensions'])) {
            $safe['extensions'] = array_slice(array_map('strval', $safe['extensions']), 0, 100);
        }
        if(count($this->requestAuditContext) > 0) {
            $safe['request'] = $this->requestAuditContext;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'UNKNOWN';
        $requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/mcpplans';
        $path = parse_url($requestUri, PHP_URL_PATH);
        if(!is_string($path) || $path === '') { $path = '/mcpplans'; }
        $encoded = json_encode($safe);
        try {
            $stmt = $this->db->prepare('INSERT INTO request_audit (request_id,actor,method,path,status_code,detail,created_at) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute(array($this->requestId,$this->actor,$method,substr($path,0,255),intval($status),$encoded,time()));
        } catch(Exception $e) {
            error_log('[pbxapi.mcpplans] request audit write failed: '.$e->getMessage());
        }
        error_log('[pbxapi.mcpplans] rejected request_id='.$this->requestId.' actor='.substr(preg_replace('/[^A-Za-z0-9_.@-]/','_', $this->actor),0,80).' method='.$method.' status='.intval($status).' detail='.$encoded);
    }

    protected function safeRequestAuditContext($input) {
        $safe = array();
        $hasList = array_key_exists('extensions', $input);
        $hasRange = array_key_exists('start_extension', $input) || array_key_exists('count', $input) || array_key_exists('end_extension', $input);
        $safe['selector_mode'] = $hasList ? ($hasRange ? 'both' : 'list') : ($hasRange ? 'range' : 'missing');
        if(array_key_exists('operation', $input)) { $safe['operation'] = $this->safeAuditEnum($input['operation'], array('create','update','delete')); }
        if(array_key_exists('profile', $input)) { $safe['profile'] = $this->safeAuditEnum($input['profile'], array('sip','pjsip','pjsip_webrtc')); }
        foreach(array('start_extension','end_extension') as $key) {
            if(array_key_exists($key, $input)) { $safe[$key] = $this->safeAuditInteger($input[$key], 8); }
        }
        if(array_key_exists('count', $input)) { $safe['count'] = $this->safeAuditInteger($input['count'], 3); }
        if($hasList) {
            if(!is_array($input['extensions'])) {
                $safe['extensions'] = 'invalid_type';
            } else {
                $safe['extensions'] = array();
                $limit = min(count($input['extensions']), 100);
                for($i=0; $i<$limit; $i++) {
                    $safe['extensions'][] = $this->safeAuditInteger($input['extensions'][$i], 8);
                }
                if(count($input['extensions']) > 100) { $safe['extensions_truncated'] = true; }
            }
        }
        return $safe;
    }

    protected function safeAuditInteger($value, $maxDigits) {
        if(is_string($value)) {
            return preg_match('/^[0-9]{1,'.intval($maxDigits).'}$/', $value) ? $value : 'invalid_value';
        }
        if(is_int($value) || is_float($value)) { return $value; }
        return 'invalid_type';
    }

    protected function safeAuditEnum($value, $allowed) {
        if(!is_string($value)) { return 'invalid_type'; }
        return in_array($value, $allowed, true) ? $value : 'invalid_value';
    }

    protected function payloadActor($payload) {
        if(isset($payload->sub) && trim($payload->sub) !== '') { return (string)$payload->sub; }
        if(isset($payload->data->name)) { return (string)$payload->data->name; }
        return 'unknown';
    }

    protected function jsonInput($f3, $allowEmpty=false) {
        $body = $f3->get('BODY');
        if($allowEmpty && trim((string)$body) === '') { return array(); }
        $input = json_decode($body, true);
        if(!is_array($input)) { $this->json(400, array('status'=>'error','detail'=>'A JSON object is required')); }
        return $input;
    }

    protected function canonicalJson($value) {
        $this->recursiveSort($value);
        return json_encode($value);
    }

    protected function recursiveSort(&$value) {
        if(!is_array($value)) { return; }
        foreach($value as &$child) { $this->recursiveSort($child); }
        if($this->isAssoc($value)) { ksort($value); }
    }

    protected function isAssoc($value) {
        if(count($value) === 0) { return false; }
        return array_keys($value) !== range(0, count($value)-1);
    }

    protected function uuidV4() {
        $bytes = $this->randomBytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
    }

    protected function randomBytes($length) {
        if(function_exists('random_bytes')) { return random_bytes($length); }
        $bytes = openssl_random_pseudo_bytes($length, $strong);
        if($bytes === false || !$strong) { throw new RuntimeException('Secure random source unavailable'); }
        return $bytes;
    }

    protected function secureEquals($known, $provided) {
        if(function_exists('hash_equals')) { return hash_equals((string)$known, (string)$provided); }
        $known = (string)$known; $provided = (string)$provided;
        if(strlen($known) !== strlen($provided)) { return false; }
        $result = 0;
        for($i=0; $i<strlen($known); $i++) { $result |= ord($known[$i]) ^ ord($provided[$i]); }
        return $result === 0;
    }

    protected function randomString($length, $alphabet) {
        $result = '';
        $size = strlen($alphabet);
        $limit = floor(256/$size)*$size;
        while(strlen($result)<$length) {
            $bytes = $this->randomBytes($length);
            for($i=0; $i<strlen($bytes) && strlen($result)<$length; $i++) {
                $value = ord($bytes[$i]);
                if($value<$limit) { $result .= $alphabet[$value%$size]; }
            }
        }
        return $result;
    }

    protected function csv($rows, $filename) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: no-store, private');
        $out = fopen('php://output', 'w');
        fputcsv($out, array('extension','username','password','voicemail_pin'));
        foreach($rows as $row) { fputcsv($out, $row); }
        fclose($out);
        die();
    }

    protected function json($status, $data) {
        if(intval($status) >= 400 && !isset($data['plan_id'])) {
            $this->auditRejectedRequest($status, $data);
            $data['request_id'] = $this->requestId;
            header('X-Issabel-Request-ID: '.$this->requestId);
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol.' '.$status, true, $status);
        echo json_encode($data);
        die();
    }
}
