#!/bin/sh

# Check for existence of /etc/init.d/wanrouter
if [ ! -e /etc/init.d/wanrouter ] ; then
        if [ -e /usr/sbin/wanrouter ] ; then
                ln -s /usr/sbin/wanrouter /etc/init.d/wanrouter
		service asterisk stop > /dev/null 2>&1
		service dahdi stop > /dev/null 2>&1
		service wanrouter stop > /dev/null 2>&1
		service wanrouter start > /dev/null 2>&1
		service dahdi start > /dev/null 2>&1
		service asterisk start > /dev/null 2>&1
        fi
fi

MSJ_NO_IP_DHCP="If you could not get a DHCP IP address please type setup and select \"Network configuration\" to set up a static IP."
INTFCNET=`ls -A /sys/class/net/`

echo -e "\e[1m" 
echo -e "  O \e[96m@ \e[91m@    \e[0mIssabel is a product meant to be configured through a web browser."
echo -e "  \e[31;1m@ \e[35m@ \e[39mO    \e[0mAny changes made from within the command line may corrupt the system"
echo -e "  \e[33;1m@ \e[39mO O    \e[0mconfiguration and produce unexpected behavior; in addition, changes"
echo -e "\e[1m    O      \e[0mmade to system files through here may be lost when doing an update."
#echo -e " \e[1mIssabel \e[0m "
echo ""
echo "To access your Issabel System, using a separate workstation (PC/MAC/Linux)"
echo "Open the Internet Browser using the following URL:"
echo -e "\e[4m"

cont=0
for x in $INTFCNET
do
	case $x in
		lo*)
		;;

		sit*)
		;;
				
		# Since CentOS 7 the way of naming network interfaces change to "Consistent Network Device Naming"
		# wich implements a change in the usual name 'ethN' to others network names of the form:
		# en* for ethernet interfaces
		# wl* for wireless lan interfaces
		# ww* for wireless wan interfaces
		# sl* for lineal serial interfaces
		eth*|en*|ww*|wl*|sl*)
			IPADDR[$cont]=$(ip a s $x | awk -F"[/ ]+" '/inet / {print $3}')
		;;
	esac
	let "cont++"
done
if [ "$IPADDR[@]" = "" ]; then
   echo "https://<YOUR-IP(s)-HERE>"
   echo "$MSJ_NO_IP_DHCP"
else
   arr=$(echo ${IPADDR[@]} | tr " " "\n")
   for IPs in $arr
   do
	  echo "https://$IPs"
   done
fi

echo -e "\e[0m"

echo -e "\e[1mYour opportunity to give back: \e[4mhttp://www.patreon.com/issabel\e[0m"
echo -e " "

