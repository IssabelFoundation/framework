#!/bin/bash
exec 2>&1
/usr/local/sbin/motd.sh
user=$(whoami)
load=`cat /proc/loadavg | awk '{print $1" (1min) "$2" (5min) "$3" (15min)"}'`
memory_usage=`free -m | awk '/Mem:/ { printf("%3.0f%%", ($3/$2)*100)}'`
memory=`free -m | awk '/Mem:/ { print $2 }'`
mem_used=`free -m| grep ^Mem | awk '{print $3}'`
swap_usage=`free -m | awk '/Swap/ { printf("%3.1f%%", "exit !$2;$3/$2*100") }'`
users=` w -s | grep -v WHAT | grep -v "load average" | wc -l`
time=`uptime | grep -ohe 'up .*' | sed 's/,/\ hours/g' | awk '{ printf $2" "$3 }'`
processes_total=`ps aux | wc -l`
processes_user=`ps -U ${user} u | wc -l`

root_total=`df -h / | awk '/\// {print $(NF-4)}'`
root_usedgb=`df -h / | awk '/\// {print $(NF-3)}' | sed 's/[^0-9\.,]//'`
root_used=`df -h / | awk '/\// {print $(NF-1)}' | sed 's/[^0-9]//'`
root_used_print=$(printf "%3.0f%%" $root_used)
root_free=$(expr 100 - $root_used)
root_used_gauge_val=`awk "BEGIN { a=($root_used/2); printf(\"%0.f\",a)}"`
root_free_gauge_val=`awk "BEGIN { a=($root_free/2); printf(\"%0.f\",a)}"`
root_used_gauge=$(seq -s= $root_used_gauge_val|tr -d '[:digit:]')
root_free_gauge=$(seq -s- $root_free_gauge_val|tr -d '[:digit:]')
root_disk_gauge=$(echo "[$root_used_gauge>$root_free_gauge] $root_used_print")

mem_free=$(expr $memory - $mem_used)
mem_free_percent=`awk "BEGIN { a=($mem_free*100/$memory); printf(\"%0.f\",a)}"`
mem_used_percent=`awk "BEGIN { a=($mem_used*100/$memory); printf(\"%0.f\",a)}"`
mem_used_gauge_val=`awk "BEGIN { a=($mem_used_percent/2); printf(\"%0.f\",a)}"`
mem_free_gauge_val=`awk "BEGIN { a=($mem_free_percent/2); printf(\"%0.f\",a)}"`
mem_used_gauge=$(seq -s= $mem_used_gauge_val|tr -d '[:digit:]')
mem_free_gauge=$(seq -s- $mem_free_gauge_val|tr -d '[:digit:]')
mem_gauge=$(echo "[$mem_used_gauge>$mem_free_gauge] $memory_usage")

asterisk_version=`asterisk -rx "core show version"  2>/dev/null| awk '{print  $1" "$2}'`
asterisk_calls=`asterisk -rx "core show channels"  2>/dev/null | grep "active calls" | awk '{print $1}'`

printf "\033[1;35mSystem load: \033[1;32m %-43s \033[1;35mUptime:  \033[1;32m%s\n" "$load" "$time"
if [ -z "$asterisk_version" ]; then
echo -e "\033[1;35mAsterisk:     \033[33;5mOFFLINE\033[0m"
else
printf "\033[1;35mAsterisk:    \033[1;32m %-37s \033[1;35mActive Calls: \033[1;32m %s\n" "$asterisk_version" "$asterisk_calls"
fi
printf "\033[1;35mMemory:      \033[1;32m %s %s/%sM\n" "$mem_gauge" "$mem_used" "$memory" 
printf "\033[1;35mUsage on /:  \033[1;32m %s %s/%s\n" "$root_disk_gauge" "$root_usedgb" "$root_total" 
printf "\033[1;35mSwap usage:  \033[1;32m %s\n" "$swap_usage"
printf "\033[1;35mSSH logins:  \033[1;32m %d open sessions\n" "$users"
printf "\033[1;35mProcesses:   \033[1;32m %d total, %d yours\n" "$processes_total" "$processes_user"
printf "\e[m\n";

