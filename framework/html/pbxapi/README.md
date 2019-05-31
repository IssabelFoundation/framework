# Table of contents

1. [Installation](#installation)
2. [Usage](#usage)
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


