# Table of contents

1. [MCP and AI Assistant](#mcp-and-ai-assistant)
2. [Installation](#installation)
3. [Usage](#usage)
	1. [Authentication](#authentication)
	2. [Extensions](#extensions)
		1. [Retrieve All](#extensions_retrieve_all)
		2. [Retrieve One](#extensions_retrieve_one)
		3. [Insert with automatic extension assignment](#extensions_insert)
		4. [Insert specifying extension number](#extensions_insertspecify)
		5. [Update](#extensions_update)
		6. [Delete](#extensions_delete)
	3. [Ring Groups](#ringgroups)
		1. [Retrieve All](#ringgroups_retrieve_all)
		2. [Retrieve One](#ringgroups_retrieve_one)
		3. [Insert with automatic extension assignment](#ringgroups_insert)
		4. [Insert specifying extension number](#ringgroups_insertspecify)
		5. [Update](#ringgroups_update)
		6. [Delete](#ringgroups_delete)

***

<a name='mcp-and-ai-assistant'></a>
## MCP and AI Assistant

This tree includes a security-constrained MCP server and an Issabel web assistant. The Go daemon in `mcp/` supports OpenAI, Anthropic, and Gemini with a per-user BYOK configuration encrypted locally using AES-256-GCM. Provider URLs are fixed; custom endpoints are intentionally unsupported.

OpenAI tool calls use the Responses API. The Issabel module follows the native `_moduleContent()` contract and keeps its scoped CSS and JavaScript under `themes/default`, so it renders inside the existing Issabel shell without changing global page styles.

The exposed MCP tools are limited to extension reads and immutable change plans:

- `list_extensions` and `get_extension`
- `create_extension_plan`, `update_extension_plan`, and `delete_extension_plan`
- `create_queue_plan` for queues with explicit static and dynamic agents
- `list_queues` and `delete_queue_plan` for secret-free inventory and approval-bound deletion
- `list_ringgroups`, `create_ring_group_plan`, `update_ring_group_plan` and `delete_ring_group_plan` for ring groups
- `list_timegroups`, `list_timeconditions`, `create_time_group_plan`, `delete_time_group_plan`, `create_time_condition_plan` and `delete_time_condition_plan` for time groups and time conditions
- `list_ivrs`, `create_ivr_plan`, `update_ivr_plan` and `delete_ivr_plan` for IVRs
- `list_numbers`, `list_destinations`, `check_number` and `check_numbers` for the credential-free number and destination namespace
- `get_plan_status` and `cancel_plan`

There is no MCP tool for approval, execution, shell, SQL, AMI, Originate, trunks, or routes. An Issabel administrator must review and approve each plan in the same-origin web UI. Plans expire after 30 minutes, contain no credentials, and are integrity checked before execution. Generated SIP passwords and voicemail PINs exist only during execution and are returned once as a CSV.

Rejected plan requests are recorded for 30 days in the local `request_audit` table with a correlation ID, actor, HTTP status, sanitized validation reason, and a safe projection of extension selector fields. The daemon journal also records the provider/model, tool name, and safe selector fields produced by the LLM before normalization. Raw request payloads, prompts, and secrets are not stored in these diagnostic records.

If that CSV is lost, `update_extension_plan` can create a new approval-bound credential rotation plan. The replacement password/PIN values are likewise generated only during execution and returned in a new one-time CSV.

The supported creation profiles are `sip`, `pjsip`, and `pjsip_webrtc`. `voicemail.enabled` is mandatory and must be a JSON boolean. A count of 10 starting at 100 produces 100–109; contradictory count/range inputs are rejected as ambiguous. Batches are limited to 100 extensions.

Extension-plan tools expose a required discriminated selector. Use `{"selector":{"mode":"range","start_extension":100,"count":10}}` for a sequence or `{"selector":{"mode":"list","extensions":["100","105"]}}` for an explicit list. The daemon treats `mode` as authoritative, removes fields from the other mode before calling PBX API, and continues to normalize the previous flat selector format for external MCP clients.

The WebRTC profile uses Issabel's combined `/etc/asterisk/keys/asterisk.pem` file as both the DTLS certificate and private key by default. Deployments with separate files can override these paths through `PBXAPI_DTLS_CERT` and `PBXAPI_DTLS_KEY`.

Queue plans require an extension, name, strategy, explicit static and dynamic agent arrays, maximum wait, and an allowlisted failover. Each agent has an explicit penalty. Supported failovers are hangup, an existing extension, an existing queue, or an existing ring group; arbitrary dialplan destinations are never accepted. The failover type is authoritative, so any provider-populated destination field is discarded for hangup. The plan shows the effective maximum wait, per-agent timeout, retry interval, and wrap-up time before approval.

`list_queues` uses a dedicated read-only controller and returns no queue passwords or arbitrary dialplan data. `delete_queue_plan` stages deletion of one queue at a time. The deletion plan stores a non-reversible fingerprint of the queue configuration and refuses execution if that queue changes before approval, preventing a reviewed plan from deleting a replacement or materially modified queue.

Time groups and time conditions share one scope pair, `time:read` and `time:plan`, because a condition is meaningless without its group. A time group is a set of ranges stored as packed `hours|weekdays|monthdays|months` rows in `timegroups_details`; `models/pbtime.php` owns that format, validating and defaulting each field on the way in and parsing it back on the way out, so the read projection and the plans cannot drift apart. A range only needs the fields the caller cares about: an omitted end mirrors the resolved start and an omitted hour window defaults to the whole day. A time condition pairs one existing time group with a destination for a match and another for a miss, both using the same allowlist as queues and ring groups; the time group, its members and both destinations are re-checked at execution time. Deleting a time group is refused while a time condition still points at it, naming the conditions, because the alternative is a condition silently pointing at a group that no longer exists. Creating a time condition also creates its `*27<id>` toggle feature code and its `TC/<id>` entry, exactly as the GUI does, so `list_numbers` will show that feature code afterwards.

An IVR is a parent row plus one row per menu option. `list_ivrs` reports the greeting id, the timeout, both fallback destinations and every option decoded. `create_ivr_plan` requires an explicit name, both fallback destinations and an `entries` array, which may be empty; each option is a single caller digit from 0 to 9 with its own destination, duplicate digits are rejected, and the menu is capped at ten options. `update_ivr_plan` sends only the changed fields, and because the API replaces the whole menu when `entries` is present, sending it rewrites the menu rather than merging it. `delete_ivr_plan` stores a fingerprint of the parent row **and** its menu, so a plan approved for one menu cannot delete a different one.

Destinations in a plan are no longer limited to extensions, queues and ring groups. `mcpplans::destinationFamilies()` is now the single registry of the families a plan may point at — extension, queue, ring group and IVR, plus the `hangup` pseudo-destination — and each entry owns the table, the column and the dialplan template. Nested IVR menus work because of it. The value is always checked to exist in that family, and an arbitrary dialplan string is still never accepted; adding another family is one entry in that map plus its enum value in the tool schema.

Ring groups follow the same model. `list_ringgroups` returns the strategy, ring time, members and a decoded failover, and it never echoes raw dialplan text: destinations are projected through the shared `pbxnamespace::parseDestination()`, which also backs `list_queues`. The decoder recognises the families the PBX builds itself (extensions, queues, ring groups, conferences, paging, voicemail blasts, feature codes, time conditions, IVRs, announcements and misc destinations) and reports anything else as `configured`. That is a description of what exists, not a promise: plan validation still accepts only the four allowlisted failover types. Creation is a `POST /ringgroups` with an explicit member list of existing extensions and a strategy from the eight value ring group enum; the API stores members as `-` separated values, and the projection tolerates both `-` and `,` because GUI created records have used both. `update_ring_group_plan` changes an existing ring group and accepts only `name`, `members`, `strategy`, `ring_time_seconds` and `failover` inside a `changes` object; any other field is rejected, so an unknown key can never be forwarded to the API. `delete_ring_group_plan` stores the same configuration fingerprint as queues, so a plan approved for one ring group refuses to delete a different or modified one. Both the create and update request bodies are built by pure functions with an explicit field mapping, so the API contract is unit tested without HTTP.

`list_numbers` and `list_destinations` read the shared namespace model in `models/pbxnamespace.php`, which `mcpplans` also uses to reject a plan before approval. `list_numbers` reports every number Issabel reserves when creating an extension or queue — extensions, queues, ring groups, conferences, parking lots, custom extensions, and feature codes (under `customcode` when set, otherwise `defaultcode`) — so a plan is never created for a number that would fail mid-execution with HTTP 409. `list_destinations` reports the dialplan destinations that exist, with the family and label, as an advisory inventory; plan validation stays authoritative. Numbers that Issabel does not reserve for extensions, such as paging or voicemail blast groups, are deliberately not listed as blockers. Both tools select identifiers and admin-visible labels only, never credential columns such as `meetme.userpin`, `vmblast.password`, or `users.secret`, and they accept no mutation.

`check_number` answers the narrower question that precedes any plan: is this specific number usable? It is needed because `get_extension` returning 404 only proves that no *extension* has that number — the number can still be owned by a queue, ring group, conference, parking lot, custom extension or feature code, as happened with a feature code on 411. `check_number` returns `available`, the owning `sources`, a `usage` classification (`free`, `extension`, or `reserved`), and a human-readable reason. The tool descriptions and the assistant system prompt both require verifying every number with `check_number` before proposing it, and the 404 from `mcpextensions` now says explicitly that it does not mean the number is free.

`check_numbers` does the same for up to 100 numbers in one call and returns `free` and `unavailable` arrays. It exists because asking *"which numbers are free between 410 and 415"* previously made the model check nothing: verifying six numbers one by one looked too expensive, so it answered from the extension list alone and reported a reserved number as free. The system prompt requires every availability statement — including answers to such questions — to come from a `check_number`/`check_numbers` result, and to say the availability is unverified when that call was never made. Read-tool calls are logged as `tool_call_read` in the daemon journal, so whether the model actually verified a number is now auditable.

Build, service installation, key generation, and restricted SSH/stdio instructions are in [`packaging/README.md`](packaging/README.md). The service listens only on `127.0.0.1` by default. Conversation history is isolated by Issabel user and automatically expires after 30 days.

Example stdio client command after installation:

```sh
ssh -T -i /path/to/restricted-key mcp-user@pbx.example
```

The corresponding SSH key must use the forced command described in the packaging guide.

***

<a name='installation'></a>
## INSTALLATION 

>**Issabel Framework 4.0.0-6, released on October 2018 has the API already installed, you do not need to follow this instructions if you have this version.**

Edit /etc/httpd/conf.d/issabel-htaccess.conf and add at the end (if not already there):

```
<Directory "/var/www/html/pbxapi">
    AllowOverride All
</Directory>
```

Then reload the web server:

> systemctl reload httpd

A new MySQL view needs to be created:

>``` mysql -u root -p -e "CREATE OR REPLACE VIEW `alldestinations` AS select `users`.`extension` AS `extension`,`users`.`name` AS `name`,'from-did-direct' AS `context`,'extension' AS `type` from `users` union select `queues_config`.`extension` AS `extension`,`queues_config`.`descr` AS `descr`,'ext-queues' AS `context`,'queue' AS `type` from `queues_config` union select `ringgroups`.`grpnum` AS `grpnum`,`ringgroups`.`description` AS `description`,'ext-group' AS `context`,'ringgroup' AS `type` from `ringgroups`"```

Some tables need to be updated:

> ``` mysql -u root -p -e "ALTER TABLE devices ADD primary key (id);" ```

> ``` mysql -u root -p -e "ALTER TABLE users ADD PRIMARY KEY (extension);" ```

Create the file:

/usr/share/issabel/privileged/applychanges

with the following content:

```
#!/usr/bin/php
<?php

if(is_executable("/usr/sbin/amportal")) {
    system('/usr/sbin/amportal a r');

}
exit(0);

?>
```

***

<a name='usage'></a>
## USAGE 

You must send GET/POST/PUT and DELETE requests to http://yourserver/pbxapi/resource in order to perform actions. As any
restful API, you can specify an ID to retrieve or act on one particular item, in the form http://yourserver/pbxapi/resource/id

Verbs that accept the item id to be specified are GET, DELETE and PUT

So you can get, delete or update one particular item.

POST does not allow an ID as it will create a new resource on the next available ID and return a Location header with the newly created id.

<a name='authentication'></a>
### Authentication

Before doing anything, you must get an access token in order to be granted access to resources, to do so,
send a POST request to _/pbxapi/authenticate_ with the admin and password as post variables.

*Example*

>curl -k -X POST --data 'user=admin&password=yourpassword' https://localhost/pbxapi/authenticate

*Response*

```json
{ 
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMTIwMDY4OSwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.5cF825r08UHsaw9odM3up9l4oiEZF7ufGaa6xjZl9H4",
  "expires_in": 86400,
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMzE4Nzg4OSwiZGF0YSI6W119.w9XptKx1EzXGqswE2x5L3PA240xYelf8gqx94PUlkpE",
  "token_type": "Bearer"
}
```

You will have to use the access_token in the Authorization: Bearer header on following requests. To make things simpler if you want to use this document as example/testing, we will export the access token 
into an environment variable TOKEN. So every example call with curl in this document from now on will use that short variable name instead of the long token string:

```
export TOKEN=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMTIwMDY4OSwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.5cF825r08UHsaw9odM3up9l4oiEZF7ufGaa6xjZl9H4
```

Access token expires after some time, once that happens, you will receive an 'expired' status response. You can then use
the refresh token to get a new access token without needing to enter user/password credentials again. The resource location
to renew your access token is */pbxapi/authenticate/renewtoken?refresh_token={refresh_token}&access_token={expired_access_token}*

***

<a name='extensions'></a>
### Extensions

This resource lets you access extensions on your Issabel PBX system

<a name='extensions_retrieve_all'></a>
#### RETRIEVE ALL EXTENSIONS

Send a GET request to _/pbxapi/extensions_

*Example*

>curl -s -k -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions | python -m json.tool

*Response:*

```json
{
    "results": [
    {
            "dial": "SIP/1000",
            "extension": "1000",
            "name": "Antonio",
            "secret": "6bs0sPut0079",
            "tech": "sip"

    },
    {
            "dial": "SIP/1001",
            "extension": "1001",
            "name": "Jose",
            "secret": "Issab3l2018",
            "tech": "sip"

    },
    {
            "dial": "SIP/1002",
            "extension": "1002",
            "name": "Maria Granuja",
            "secret": "eec8ef4d15658f6b167910404d3cbdb0",
            "tech": "sip"

    },
    {
            "dial": "SIP/1003",
            "extension": "1003",
            "name": "Pedro Picapiedras",
            "secret": "05c7b12a812d3c1ee6df50794c512451",
            "tech": "sip"

    }
        ]

}
```

<a name='extensions_retrieve_one'></a>
#### RETRIEVE ONE

Send a GET request to _/pbxapi/extensions/id_ where id is the extension number

*Example*

>curl -k -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions/1002 | python -m json.tool

*Response*

```json
{
    "results": [
    {
            "dial": "SIP/1002",
            "extension": "1002",
            "name": "Maria Granuja",
            "secret": "eec8ef4d15658f6b167910404d3cbdb0",
            "tech": "sip"

    }

        ]

}
```
<a name='extensions_insert'></a>
#### CREATE A NEW EXTENSION (without specifying extension number)

Send a POST request to _/pbxapi/extensions_

Any time you create a new extension, the PBX will apply the changes/configuration automatically. If you want to disable this (because you are doing a batch of calls for example), then you must pass the reload variable with value 0 or false

Some variables you can use:

* name: Name of user/extension
* ringtimer: seconds to ring extension
* voicemail: [ novm | default ]
* recording_in_internal: [ always | dontcare | never ]
* recording_in_external: [ always | dontcare | never  ]
* recording_out_internal: [ always | dontcare | never  ]
* recording_out_external: [ always | dontcare | never  ]

Variables should be sent in JSON format, with the header application/json

*Example*

>curl -v -k -X POST -d '{"name":"Some Name","voicemail":"novm","ringtimer":"30"}' -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions

*Return*

HTTP Status:   HTTP/1.1 201 Created  
HTTP Location header: https://localhost/pbxapi/extensions/{extension}  
(where {extension} is the next available extension number automatically selected by the system )  

*Response*

There is no response body

##### Apply changes

Any time you create, update or delete a new extension, the PBX will apply the changes/configuration automatically. If you want to disable this (because you are doing a batch of calls for example), then you must pass the reload variable with value 1 or true


<a name='extensions_insertspecify'></a>
#### CREATE A NEW EXTENSION (specifying extension number)

Send a PUT request to _/pbxapi/extensions/{extension}_ with the same variables as POST. If {extension} already exists, it will perform an update of data. Remember that variables should be sent in JSON format in the request body.


*Example*

>curl -v -k -X PUT -d '{"name":"Daniel","secret":"unaclave"}'  -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions/1005

*Return*

HTTP Status: HTTP/1.1 200 OK  
HTTP Location header: https://localhost/pbxapi/extensions/{extension}  
Location is only set if the resource was created instead of updated  

*Response*

There is no response body

<a name='extensions_update'></a>
#### UPDATE AN EXTENSION

Send a PUT request to _/pbxapi/extensions/{extension}_ passing the variables you want to update where {extension} already exists on the system, otherwise it will be insert a new one.

*Example*

>curl -v -k -X PUT -d '{"secret":"unaclave"}'  -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions/1005

*Return*

HTTP Status: HTTP/1.1 200 OK

*Response*

There is no response body


<a name='extensions_delete'></a>
#### DELETE AN EXTENSION

Send a DELETE request to */pbxapi/extensions/{extension},[{more},{extensions}]*

Notice that you can specify one extension or multiple extensions separated by comma.

*Example*

Delete extension 1005:

>curl -v -L -k -X DELETE -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions/1005


Delete extensions 1005 and 1006:

>curl -v -L -k -X DELETE -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions/1005,1006

*Return*

HTTP Status: HTTP/1.1 200 OK

*Response*

There is no response body

***

<a name='ringgroups'></a>
### Ring Groups

This resource lets you access ring groups on your Issabel PBX system:


<a name='ringgroups_retrieve_all'></a>
#### RETRIEVE ALL RING GROUPS

Send a GET request to /pbxapi/ringgroups

*Example*

>curl -s -k -H "Authorization: Bearer $TOKEN://localhost/pbxapi/ringgroups | python -m json.tool

*Response:*
```json
{
    "results": [
        {
            "change_callerid": "default",
            "destination": "from-internal,600,1",
            "extension": "600",
            "extension_list": [
                "200",
                "201"
            ],
            "fixed_callerid": "",
            "id": "600",
            "name": "Sales",
            "strategy": "ringall"
        }
    ]
}
```

<a name='ringgroups_retrieve_one'></a>
#### RETRIEVE ONE PARTICULAR RING GROUP DISPLAYING ALL AVAILABLE FIELDS

Some entities have lots of configuration fields available. For making things simpler, default views will list the most important/key fields from a particular entity. If you want to display all available fields, you can append fields=* at the end of the URI to get all fields, or you can also list a a specific list of fields delimited by commas. Here is an example to get ring groups with all available fields:

>curl -s -k -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/ringgroups/600?fields=* | python -m json.tool

*Response:*
```json
{
    "results": [
        {
            "alert_info": "",
            "announce_id": 0,
            "change_callerid": "default",
            "cid_name_prefix": "",
            "confirm_calls": "off",
            "destination": "from-internal,600,1",
            "destination_if_no_answer": "app-blackhole,hangup,1",
            "enable_call_pickup": "off",
            "extension": "600",
            "extension_list": [
                "200",
                "201"
            ],
            "fixed_callerid": "",
            "id": "600",
            "ignore_call_forward_settings": "off",
            "music_on_hold_ringing": "Ring",
            "name": "Sales",
            "recording": "dontcare",
            "remote_announce_id": 0,
            "ring_time": 20,
            "skip_busy_agent": "off",
            "strategy": "ringall",
            "too_late_announce_id": 0
        }
    ]
}
```

<a name='ringgroups_insert'></a>
#### CREATE A NEW RING GROUP (without specifying extension number)

Send a POST request to _/pbxapi/ringgroups_

*Required Fields*: It is mandatory to send an 'extension_list' in the JSON payload when creating a new ring group.

Any time you create a new ring group, the PBX will apply the changes/configuration automatically. If you want to disable this (because you are doing a batch of calls for example), then you must pass the reload variable with value 0 or false

Some variables you can use:

* name: Ring group name
* strategy: ring strategy to use: [ ringall | ringall-prim | hunt | hunt-prim | memoryhunt | memoryhunt-prim | firstavailable | firstnotonphone ]
* ring_time: Number of seconds to ring group (maximum 300 seconds)
* extension_list: array containing list of extension numbers

Variables should be sent in JSON format, with the header application/json

*Example*

>curl -v -k -X POST -d '{"name":"Sales","strategy":"ringall","ring_time":"120","extension_list":["200","201"]}' -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/extensions

*Return*

HTTP Status:   HTTP/1.1 201 Created  
HTTP Location header: https://localhost/pbxapi/ringgroups/{extension}  
(where {extension} is the next available extension number automatically selected by the system )  

*Response*

There is no response body

<a name='ringgroups_insertspecify'></a>
#### CREATE A NEW RING GROUP (specifying extension number)

Send a PUT request to _/pbxapi/ringgroups/{extension}_ with the same variables as POST. If {extension} already exists, it will perform an update of data. Remember that variables should be sent in JSON format in the request body.


*Example*

>curl -v -k -X PUT -d '{"name":"Sales","strategy":"ringall","extension_list":["200","201"]}'  -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/ringgrouops/600

*Return*

HTTP Status: HTTP/1.1 200 OK  
HTTP Location header: https://localhost/pbxapi/ringgroups/{extension}  
Location is only set if the resource was created instead of updated  

*Response*

There is no response body


<a name='ringgroups_update'></a>
#### UPDATE A RING GROUP

Send a PUT request to _/pbxapi/ringgroups/{extension}_ passing the variables you want to update where {extension} already exists on the system, otherwise it will be insert a new one. The following example will change the name for ring group 600 to "NewName":

*Example*

>curl -v -k -X PUT -d '{"name":"NewName"}'  -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/ringgroups/600

*Return*

HTTP Status: HTTP/1.1 200 OK

*Response*

There is no response body


<a name='extensions_delete'></a>
#### DELETE A RING GROUP

Send a DELETE request to */pbxapi/ringgroups/{extension},[{more},{extensions}]*

Notice that you can specify one or multiple ring group extensions  separated by comma.

*Example*

Delete ring group 600:

>curl -v -L -k -X DELETE -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/ringgroups/600


Delete ring groups 600 and 610:

>curl -v -L -k -X DELETE -H "Authorization: Bearer $TOKEN" https://localhost/pbxapi/ringgroups/600,610

*Return*

HTTP Status: HTTP/1.1 200 OK

*Response*

There is no response body
