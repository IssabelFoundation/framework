## INSTALLATION

Edit /etc/httpd/conf.d/issabel-htaccess.conf and add at the end (if not already there):

```
<Directory "/var/www/html/pbxapi">
    AllowOverride All
</Directory>
```

Then reload the web server:

> systemctl reload httpd

A new MySQL view needs to be created:

>``` mysql -u root -p -e "CREATE VIEW `alldestinations` AS select `users`.`extension` AS `extension`,`users`.`name` AS `name`,'from-internal' AS `context`,'extension' AS `type` from `users` union select `queues_config`.`extension` AS `extension`,`queues_config`.`descr` AS `descr`,'ext-queues' AS `context`,'queue' AS `type` from `queues_config` union select `ringgroups`.`grpnum` AS `grpnum`,`ringgroups`.`description` AS `description`,'ext-group' AS `context`,'ringgroup' AS `type` from `ringgroups`"```

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


## USAGE

You must send GET/POST/PUT and DELETE requests to http://yourserver/pbxapi/resource in order to perform actions. As any
restful API, you can specify an ID to retrieve or act on one particular item, in the form http://yourserver/pbxapi/resource/id

Verbs that accept the item id to be specified are GET, DELETE and PUT

So you can get, delete or update one particular item.

POST does not allow an ID as it will create a new resource on the next available ID and return a Location header with the newly created id.


### Authentication

Before doing anything, you must get an access token in order to be granted access to resources, to do so,
send a POST request to _/pbxapi/authenticate_ with the admin and password as post variables.

*Example*

>curl -k -X POST --data 'user=admin&password=yourpassword' https://localhost/pbxapi/authenticate

*Response*

```
{"access_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMTIwMDY4OSwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.5cF825r08UHsaw9odM3up9l4oiEZF7ufGaa6xjZl9H4","expires_in":86400,"refresh_token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMzE4Nzg4OSwiZGF0YSI6W119.w9XptKx1EzXGqswE2x5L3PA240xYelf8gqx94PUlkpE","token_type":"Bearer"}
```

You will have to use the access_token in the Authorization: Bearer header on following requests.

Access token expires after some time, once that happens, you will receive an 'expired' status response. You can then use
the refresh token to get a new access token without needing to enter user/password credentials again. The resource location
to renew your access token is */pbxapi/authenticate/renewtoken?refresh_token={refresh_token}*


###Extensions

This resource lets you access extensions on your Issabel PBX system:

#### RETRIEVE ALL EXTENSIONS

Send a GET request to _/pbxapi/extensions_

*Example*

>curl -s -k -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMTIwMDY4OSwiZGF0YSI6eyjoiYWRtaW4ifX0.5cF825r08UHsaw9odM3up9l4oiEZF7ufGaa6xjZl9H4" https://localhost/pbxapi/extensions | python -m json.tool

*Response:*
```
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

#### RETRIEVE ONE

Send a GET request to /pbxapi/extensions/id

*Example*

>curl -k -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMTIwMDY4OSwiZGF0YSI6eyjoiYWRtaW4ifX0.5cF825r08UHsaw9odM3up9l4oiEZF7ufGaa6xjZl9H4" https://localhost/pbxapi/extensions/1002 | python -m json.tool

*Response*

```
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

>curl -v -k -X POST -d '{"name":"Some Name","voicemail":"novm","ringtimer":"30"' -H "Content-Type: application/json" -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMTQyODksImV4cCI6MTUyMTIwMDY4OSwiZGF0YSI6eyjoiYWRtaW4ifX0.5cF825r08UHsaw9odM3up9l4oiEZF7ufGaa6xjZl9H4" https://localhost/pbxapi/extensions

*Return*

HTTP Status:   HTTP/1.1 201 Created
HTTP Location header: https://localhost/pbxapi/extensions/{extension}
(where {extension} is the next available extension number automatically selected by the system )

*Response*

There is no response body

##### Apply changes

Any time you create, update or delete a new extension, the PBX will apply the changes/configuration automatically. If you want to disable this (because you are doing a batch of calls for example), then you must pass the reload variable with value 1 or true


#### CREATE A NEW EXTENSION (specifying extension number)

Send a PUT request to _/pbxapi/extensions/{extension}_ with the same variables as POST. If {extension} already exists, it will perform an update of data. Remember that variables should be sent in JSON format in the request body.


*Example*

>curl -v -k -X PUT -d '{"name":"Daniel","secret":"unaclave"}'  -H "Content-Type: application/json" -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMjY1NjgsImV4cCI6MTUyMTIxMjk2OCwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.CvKtlP45RTGfyjI5nt9juvpy-48v7pGaYNVyo10kWRo" https://localhost/pbxapi/extensions/1005

*Return*

HTTP Status: HTTP/1.1 200 OK
HTTP Location header: https://localhost/pbxapi/extensions/{extension}
Location is only set if the resource was created instead of updated

*Response*

There is no response body

#### UPDATE AN EXTENSION

Send a PUT request to _/pbxapi/extensions/{extension}_ passing the variables you want to update where {extension} already exists on the system, otherwise it will be insert a new one.

*Example*

>curl -v -k -X PUT -d '{"secret":"unaclave"}'  -H "Content-Type: application/json" -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMjY1NjgsImV4cCI6MTUyMTIxMjk2OCwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.CvKtlP45RTGfyjI5nt9juvpy-48v7pGaYNVyo10kWRo" https://localhost/pbxapi/extensions/1005

*Return*

HTTP Status: HTTP/1.1 200 OK

*Response*

There is no response body


#### DELETE AN EXTENSION

Send a DELETE request to */pbxapi/extensions/{extension},[{more},{extensions}]*

Notice that you can specify one extension or multiple extensions separated by comma.

*Example*

Delete extension 1005:

>curl -v -L -k -X DELETE -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMjY1NjgsImV4cCI6MTUyMTIxMjk2OCwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.CvKtlP45RTGfyjI5nt9juvpy-48v7pGaYNVyo10kWRo" https://localhost/pbxapi/extensions/1005


Delete extensions 1005 and 1006:

>curl -v -L -k -X DELETE -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE1MjExMjY1NjgsImV4cCI6MTUyMTIxMjk2OCwiZGF0YSI6eyJuYW1lIjoiYWRtaW4ifX0.CvKtlP45RTGfyjI5nt9juvpy-48v7pGaYNVyo10kWRo" https://localhost/pbxapi/extensions/1005,1006

*Return*

HTTP Status: HTTP/1.1 200 OK

*Response*

There is no response body


