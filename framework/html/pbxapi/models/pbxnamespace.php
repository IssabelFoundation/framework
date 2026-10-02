<?php
/*
 * Shared, credential-free view of the PBX number and destination namespaces.
 *
 * Two consumers depend on this being the single source of truth:
 *   - mcpplans rejects a plan up front through numberConflicts() instead of
 *     letting it fail halfway through execution with an unexplained HTTP 409.
 *   - mcpnamespace exposes usedNumbers()/usedDestinations() to the assistant so
 *     it stops proposing numbers and destinations that do not exist.
 *
 * Every query here selects identifiers and admin-visible labels only. Several
 * of the tables involved carry credentials in other columns (meetme.userpin and
 * adminpin, vmblast.password, users.secret), so columns are listed explicitly
 * and never with SELECT *.
 */
class pbxnamespace {

    // Every number source Issabel treats as reserved when creating an extension
    // or a queue. Mirrors rest::dieExtensionDuplicate(); keep in sync with the
    // module controllers.
    public static function numberSources() {
        return array(
            'extensions'       => array('table'=>'users',             'expr'=>'extension'),
            'queues'           => array('table'=>'queues_config',     'expr'=>'extension'),
            'ringgroups'       => array('table'=>'ringgroups',        'expr'=>'grpnum'),
            'conferences'      => array('table'=>'meetme',            'expr'=>'exten'),
            'parkinglots'      => array('table'=>'parkplus',          'expr'=>'parkext'),
            'customextensions' => array('table'=>'custom_extensions', 'expr'=>'custom_exten'),
            // A feature code is reserved under its customcode when one is set,
            // otherwise under its defaultcode. Disabled codes count too, exactly
            // as featurecodes::getExtensions() reports them.
            'featurecodes'     => array('table'=>'featurecodes',
                'expr'=>"CASE WHEN customcode IS NULL OR customcode='' THEN defaultcode ELSE customcode END")
        );
    }

    // Destination families, mirroring each controller's getDestinations().
    // 'template' is the dialplan destination the PBX publishes, with %s replaced
    // by the raw value. Families whose controller builds a custom string (ivr,
    // announcements) are reproduced literally here.
    public static function destinationSources() {
        return array(
            'extensions'         => array('table'=>'users',               'value'=>'extension',        'label'=>'name',        'template'=>'from-did-direct,%s,1'),
            'queues'             => array('table'=>'queues_config',       'value'=>'extension',        'label'=>'descr',       'template'=>'ext-queues,%s,1'),
            'ringgroups'         => array('table'=>'ringgroups',          'value'=>'grpnum',           'label'=>'description', 'template'=>'ext-group,%s,1'),
            'conferences'        => array('table'=>'meetme',              'value'=>'exten',            'label'=>'description', 'template'=>'ext-meetme,%s,1'),
            'paging'             => array('table'=>'paging_config',       'value'=>'page_group',       'label'=>'description', 'template'=>'app-pagegroups,%s,1'),
            'vmblast'            => array('table'=>'vmblast',             'value'=>'grpnum',           'label'=>'description', 'template'=>'vmblast-grp,%s,1'),
            'featurecodes'       => array('table'=>'featurecodes',
                'value'=>"CASE WHEN customcode IS NULL OR customcode='' THEN defaultcode ELSE customcode END",
                'label'=>'featurename',   'template'=>'ext-featurecodes,%s,1'),
            'timeconditions'     => array('table'=>'timeconditions',      'value'=>'timeconditions_id','label'=>'displayname', 'template'=>'timeconditions,%s,1'),
            'ivr'                => array('table'=>'ivr_details',         'value'=>'id',               'label'=>'name',        'template'=>'ivr-%s,s,1'),
            'announcements'      => array('table'=>'announcement',        'value'=>'announcement_id',  'label'=>'description', 'template'=>'app-announcement-%s,s,1'),
            'customdestinations' => array('table'=>'custom_destinations', 'value'=>'custom_dest',      'label'=>'description', 'template'=>'%s'),
            'customextensions'   => array('table'=>'custom_extensions',   'value'=>'custom_exten',     'label'=>'description', 'template'=>'%s'),
            'miscdestinations'   => array('table'=>'miscdests',           'value'=>'id',               'label'=>'description', 'template'=>'ext-miscdests,%s,1')
        );
    }

    // Returns array(number => array(source labels)) for numbers owned by any
    // source other than those listed in $exclude. A source whose table or column
    // is missing is treated as absent, never as a conflict: the authoritative
    // check still runs inside the PBX API when the plan executes.
    public static function numberConflicts($db, array $numbers, array $exclude = array()) {
        $numbers = array_values(array_unique(array_map('strval', $numbers)));
        if(count($numbers) === 0) { return array(); }
        $marks = implode(',', array_fill(0, count($numbers), '?'));
        $conflicts = array();
        foreach(self::numberSources() as $label=>$source) {
            if(in_array($label, $exclude, true)) { continue; }
            $expr = $source['expr'];
            $rows = self::query($db, 'SELECT DISTINCT '.$expr.' AS reserved FROM '.$source['table']
                .' WHERE '.$expr.' IN ('.$marks.')', $numbers, 'number source '.$label);
            if($rows === null) { continue; }
            foreach($rows as $row) {
                $value = (string)$row['reserved'];
                if(!in_array($value, $numbers, true)) { continue; }
                if(!isset($conflicts[$value])) { $conflicts[$value] = array(); }
                if(!in_array($label, $conflicts[$value], true)) { $conflicts[$value][] = $label; }
            }
        }
        return $conflicts;
    }

    public static function conflictDetail(array $conflicts) {
        $parts = array();
        foreach($conflicts as $number=>$labels) { $parts[] = (string)$number.' ('.implode(', ', $labels).')'; }
        return implode('; ', $parts);
    }

    public static function usedNumbers($db) {
        $used = array();
        foreach(self::numberSources() as $label=>$source) {
            $expr = $source['expr'];
            $rows = self::query($db, 'SELECT DISTINCT '.$expr.' AS number FROM '.$source['table']
                .' WHERE '.$expr." IS NOT NULL AND ".$expr."<>'' ORDER BY 1 LIMIT 1000", array(), 'number source '.$label);
            if($rows === null) { continue; }
            foreach($rows as $row) {
                $value = (string)$row['number'];
                if($value === '') { continue; }
                if(!isset($used[$value])) { $used[$value] = array(); }
                if(!in_array($label, $used[$value], true)) { $used[$value][] = $label; }
            }
        }
        ksort($used, SORT_NATURAL);
        $result = array();
        foreach($used as $number=>$sources) {
            $result[] = array('number'=>(string)$number, 'sources'=>$sources);
        }
        return $result;
    }

    public static function usedDestinations($db) {
        $result = array();
        foreach(self::destinationSources() as $family=>$source) {
            $value = $source['value'];
            $rows = self::query($db, 'SELECT DISTINCT '.$value.' AS value, '.$source['label'].' AS label FROM '
                .$source['table'].' ORDER BY 1 LIMIT 1000', array(), 'destination family '.$family);
            if($rows === null) {
                // Retry without the label when that column is missing on this PBX.
                $rows = self::query($db, 'SELECT DISTINCT '.$value.' AS value FROM '.$source['table']
                    .' ORDER BY 1 LIMIT 1000', array(), 'destination family '.$family);
                if($rows === null) { continue; }
            }
            foreach($rows as $row) {
                $raw = (string)$row['value'];
                if($raw === '') { continue; }
                $result[] = array(
                    'family'=>$family,
                    'value'=>$raw,
                    'label'=>isset($row['label']) ? self::cleanLabel($row['label']) : '',
                    'destination'=>str_replace('%s', $raw, $source['template'])
                );
            }
        }
        return $result;
    }

    // Shared, safe projection of a dialplan destination. Arbitrary dialplan text
    // is never echoed back: only the destination families this API can itself
    // produce are decoded, anything else is reported as 'configured'.
    public static function parseDestination($destination) {
        $destination = (string)$destination;
        if($destination === 'app-blackhole,hangup,1') { return array('type'=>'hangup'); }
        // "context,value,1" families. value is digits for numbered modules and
        // may carry a leading * for feature codes such as the *27<id> toggle a
        // time condition creates. destination_extension is the target id.
        if(preg_match('/^([a-z0-9\-]+),([0-9*]{1,20}),1$/', $destination, $matches)) {
            $families = array(
                'from-did-direct'=>'extension', 'ext-queues'=>'queue', 'ext-group'=>'ring_group',
                'ext-meetme'=>'conference', 'app-pagegroups'=>'paging', 'vmblast-grp'=>'vmblast',
                'ext-miscdests'=>'misc_destination', 'timeconditions'=>'time_condition',
                'ext-featurecodes'=>'feature_code'
            );
            if(isset($families[$matches[1]])) {
                return array('type'=>$families[$matches[1]], 'destination_extension'=>$matches[2]);
            }
        }
        // Families the PBX builds with a literal marker instead of a context.
        if(preg_match('/^ivr-([0-9]{1,8}),s,1$/', $destination, $matches)) {
            return array('type'=>'ivr', 'destination_extension'=>$matches[1]);
        }
        if(preg_match('/^app-announcement-([0-9]{1,8}),s,1$/', $destination, $matches)) {
            return array('type'=>'announcement', 'destination_extension'=>$matches[1]);
        }
        return array('type'=>'configured');
    }

    // Ring group members are stored by the API as '-' separated values; tolerate
    // ',' as well because GUI created records have used both.
    public static function memberList($stored) {
        $members = array();
        foreach(preg_split('/[-,]+/', (string)$stored) as $member) {
            $member = trim($member);
            if($member !== '' && preg_match('/^[0-9]{1,8}$/', $member)) { $members[] = $member; }
        }
        return array_values(array_unique($members));
    }

    protected static function cleanLabel($value) {
        if(!is_scalar($value)) { return ''; }
        $text = preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$value);
        $text = trim(preg_replace('/\s+/', ' ', (string)$text));
        return substr($text, 0, 120);
    }

    // Returns the rows on success, an empty array for an empty result set, and
    // null when the source is unavailable so callers can tell them apart.
    protected static function query($db, $sql, $params, $what) {
        try {
            $rows = $db->exec($sql, $params);
            return is_array($rows) ? $rows : array();
        } catch(Exception $e) {
            error_log('[pbxapi.pbxnamespace] '.$what.' unavailable on this PBX');
            return null;
        }
    }
}
