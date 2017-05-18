#!/bin/bash

# "-------------------------------------------"
# "Configuring sudo"
# "-------------------------------------------"

if ! grep "asterisk ALL = NOPASSWD: /sbin/shutdown" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /sbin/shutdown" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /sbin/shutdown added to /etc/sudoers"
fi

if ! grep "asterisk ALL = NOPASSWD: /sbin/service" /etc/sudoers >/dev/null 2>&1; then
	echo "asterisk ALL = NOPASSWD: /sbin/service" >> /etc/sudoers
	echo "asterisk ALL = NOPASSWD: /sbin/service added to /etc/sudoers"
fi
