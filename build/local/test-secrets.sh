#!/usr/bin/env bash
# Run ONLY in a disposable EL8 container with the build output at /output.
set -euo pipefail
package=$(find /output/RPMS -name 'issabel-mcp-*.rpm' -print -quit)
[[ -n "$package" ]]
useradd -r asterisk
# Issabel itself is absent here; --nodeps is confined to this packaging test.
rpm -Uvh --nodeps "$package"
usermod -a -G issabel-ai asterisk
runuser -u asterisk -- test -r /etc/issabel-mcp/web.secret
runuser -u asterisk -- test -r /etc/issabel-mcp/public.pem
runuser -u asterisk -- sh -c '! test -r /etc/issabel-mcp/private.pem && ! test -r /etc/issabel-mcp/master.key && ! test -w /etc/issabel-mcp'
for key in private.pem master.key web.secret; do
    runuser -u issabel-mcp -- test -r "/etc/issabel-mcp/$key"
done
sha256sum /etc/issabel-mcp/{private.pem,public.pem,master.key,web.secret} > /tmp/secrets-before
rm /etc/issabel-mcp/public.pem
rpm -Uvh --replacepkgs --nodeps "$package"
sha256sum --status -c /tmp/secrets-before
runuser -u asterisk -- test -r /etc/issabel-mcp/public.pem
runuser -u asterisk -- test -r /etc/issabel-mcp/web.secret
# Exercise actual daemon initialization using the generated secrets.
runuser -u issabel-mcp -- /usr/sbin/issabel-mcp -mode serve >/tmp/mcp-start.log 2>&1 &
service_pid=$!
trap 'kill "$service_pid" 2>/dev/null || :' EXIT
for attempt in {1..30}; do
    if grep -q 'listening on' /tmp/mcp-start.log; then break; fi
    kill -0 "$service_pid"
    sleep 0.2
done
grep -q 'listening on 127.0.0.1:8787' /tmp/mcp-start.log
stat -c '%a %U:%G %n' /etc/issabel-mcp /etc/issabel-mcp/*
echo 'PASS: permissions, preserved secrets, recovered public key, daemon startup'
