%define modname framework
Summary: Issabel is a Web based software to administrate a PBX based in open source programs, forked from Elastix
Name: issabel-%{modname}
Vendor: Issabel Foundation
Version: 4.0.0
Release: 1
License: GPL
Group: Applications/System
Source0: %{modname}_%{version}-%{release}.tgz
Patch0:  baserepo-40.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires(pre): /sbin/chkconfig, /etc/sudoers, sudo
Requires(pre): php, php-gd, php-pear, php-xml, php-mysql, php-pdo, php-imap, php-soap
Requires(pre): httpd, mysql-server, ntp, mod_ssl
Requires(pre): perl
Requires(pre): issabel-firstboot >= 2.3.0-4
Requires(pre): /sbin/pidof
Obsoletes: elastix-additionals
Conflicts: elastix-system <= 4.0.0-8
Conflicts: elastix-callcenter <= 2.0.0-16
Conflicts: elastix-pbx <= 2.2.0-16
Conflicts: elastix-fax <= 2.2.0-5
Conflicts: elastix-email_admin <= 2.3.0-8
Conflicts: elastix-developer <= 2.3.0-4
Conflicts: elastix-addons <= 2.5.0-3
Conflicts: elastix-monitoring_services <= 5.4.1-8
Conflicts: kernel-module-dahdi
Conflicts: kernel-module-rhino
Conflicts: kernel-module-wanpipe
Conflicts: kernel-module-dahdi-xen
Conflicts: kernel-module-rhino-xen
Conflicts: kernel-module-wanpipe-xen
Obsoletes: elastix <= 2.2.0-17
Requires: php-Smarty
Requires: php-jpgraph
Requires: php-tcpdf
Requires: php-PHPMailer
Obsoletes: elastix-framework
Provides: elastix-framework

# commands: uname df rm cat
Requires: coreutils

# commands: uptime
Requires: procps

# commands: rpm
Requires: rpm

# commands: /usr/bin/mysql /usr/bin/mysqldump
Requires: mysql

# commands: /usr/bin/sqlite3
Requires: sqlite

# FIXME: /usr/local/elastix/sampler.php requieres /usr/sbin/asterisk but
# elastix-framework should stand by itself without an asterisk dependency.

%description
Issabel is a Web based software to administrate a PBX based in open source programs

%package themes-extra
Summary: Issabel GUI themes from 2.4 and earlier
Group: Applications/System
BuildArch: noarch
Requires: issabel-framework = %{version}-%{release}

%description themes-extra
This package provides the Issabel GUI themes from earlier versions.

%prep
%setup -n %{name}_%{version}-%{release}
%patch0 -p1

%install
## ** Step 1: Creation path for the installation ** ##
rm -rf   $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT

# ** /var path ** #
mkdir -p $RPM_BUILD_ROOT/var/www/db
mkdir -p $RPM_BUILD_ROOT/var/www/html
mkdir -p $RPM_BUILD_ROOT/var/www/backup
mkdir -p $RPM_BUILD_ROOT/var/lib/php/session-asterisk

# ** /usr path ** #
mkdir -p $RPM_BUILD_ROOT/usr/local/bin
mkdir -p $RPM_BUILD_ROOT/usr/local/elastix
mkdir -p $RPM_BUILD_ROOT/usr/local/sbin
mkdir -p $RPM_BUILD_ROOT/usr/sbin
mkdir -p $RPM_BUILD_ROOT/usr/bin
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix
mkdir -p $RPM_BUILD_ROOT/usr/share/pear/DB
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/privileged
mkdir -p $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

# ** /etc path ** #
mkdir -p $RPM_BUILD_ROOT/etc/cron.d
mkdir -p $RPM_BUILD_ROOT/etc/httpd/conf.d
mkdir -p $RPM_BUILD_ROOT/etc/php.d
mkdir -p $RPM_BUILD_ROOT/etc/yum.repos.d
mkdir -p $RPM_BUILD_ROOT/etc/init.d


## ** Step 2: Installation of files and folders ** ##
# ** Installating framework elastix webinterface ** #
#rm -rf $RPM_BUILD_DIR/elastix-framework/framework/html/modules/userlist/  # Este modulo no es el modificado para soporte de correo, eso se encuentra en modules-core
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/framework/html/*                              $RPM_BUILD_ROOT/var/www/html/

# ** Installating modules elastix webinterface ** #
#mv $RPM_BUILD_DIR/elastix/modules-core/*                                $RPM_BUILD_ROOT/var/www/html/modules/

# ** Installating additionals elastix webinterface ** #
#mv $RPM_BUILD_DIR/elastix/additionals/db/*                              $RPM_BUILD_ROOT/var/www/db/
#mv $RPM_BUILD_DIR/elastix/additionals/html/libs/*                       $RPM_BUILD_ROOT/var/www/html/libs/
#rm -rf $RPM_BUILD_DIR/elastix/additionals/html/libs/
#mv $RPM_BUILD_DIR/elastix/additionals/html/*                            $RPM_BUILD_ROOT/var/www/html/

chmod 777 $RPM_BUILD_ROOT/var/www/db/
chmod 755 $RPM_BUILD_ROOT/usr/share/elastix/privileged

# ** Httpd and Php config ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/httpd/conf.d/issabel.conf        $RPM_BUILD_ROOT/etc/httpd/conf.d/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/httpd/conf.d/elastix-htaccess.conf  $RPM_BUILD_ROOT/etc/httpd/conf.d/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/php.d/elastix.ini                $RPM_BUILD_ROOT/etc/php.d/

# ** crons config ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/cron.d/elastix.cron              $RPM_BUILD_ROOT/etc/cron.d/
chmod 644 $RPM_BUILD_ROOT/etc/cron.d/*

# ** Repos config ** #
#mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/yum.repos.d/CentOS-Base.repo     $RPM_BUILD_ROOT/usr/share/elastix/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/yum.repos.d/Issabel.repo         $RPM_BUILD_ROOT/etc/yum.repos.d/

# ** sudoers config ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/sudoers                          $RPM_BUILD_ROOT/usr/share/elastix/

# ** /usr/local/ files ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/local/elastix/sampler.php        $RPM_BUILD_ROOT/usr/local/elastix/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/local/sbin/motd.sh               $RPM_BUILD_ROOT/usr/local/sbin/
chmod 755 $RPM_BUILD_ROOT/usr/local/sbin/motd.sh

# ** /usr/share/ files ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/share/pear/DB/sqlite3.php                    $RPM_BUILD_ROOT/usr/share/pear/DB/

# ** setup ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/framework/setup/usr/share/elastix/privileged/*   $RPM_BUILD_ROOT/usr/share/elastix/privileged/
rmdir framework/setup/usr/share/elastix/privileged/ framework/setup/usr/share/elastix
rmdir framework/setup/usr/share framework/setup/usr
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/framework/setup/                                 $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/framework/menu.xml                               $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

# ** elastix-* file ** #
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/elastix-menumerge            $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/elastix-menuremove           $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/elastix-dbprocess            $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/compareVersion		   $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/search_ami_admin_pwd             $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/elastix-add-yum-exclude             $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/elastix-notification             $RPM_BUILD_ROOT/usr/bin/
chmod 755 $RPM_BUILD_ROOT/usr/bin/compareVersion
chmod 755 $RPM_BUILD_ROOT/usr/bin/search_ami_admin_pwd

# ** Moving elastix_helper
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/bin/elastix-helper               $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/sbin/elastix-helper              $RPM_BUILD_ROOT/usr/sbin/

chmod 755 $RPM_BUILD_ROOT/usr/sbin/elastix-helper
chmod 755 $RPM_BUILD_ROOT/usr/bin/elastix-helper


# Archivos generic-cloexec y close-on-exec.pl
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/usr/sbin/close-on-exec.pl            $RPM_BUILD_ROOT/usr/sbin/
mv $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/init.d/generic-cloexec           $RPM_BUILD_ROOT/etc/init.d/

#Logrotate
mkdir -p    $RPM_BUILD_ROOT/etc/logrotate.d/
mv          $RPM_BUILD_DIR/%{name}_%{version}-%{release}/additionals/etc/logrotate.d/*           $RPM_BUILD_ROOT/etc/logrotate.d/
# Los archivos de logrotate TIENEN que ser 0644 (http://bugs.elastix.org/view.php?id=2608)
chmod 644 $RPM_BUILD_ROOT/etc/logrotate.d/*

# File Elastix Access Audit log
mkdir -p    $RPM_BUILD_ROOT/var/log/elastix
touch       $RPM_BUILD_ROOT/var/log/elastix/audit.log
touch	    $RPM_BUILD_ROOT/var/log/elastix/postfix_stats.log

%pre
#Para conocer la version de elastix antes de actualizar o instalar
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_framework.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_framework.info
fi

# if not exist add the asterisk group
grep -c "^asterisk:" %{_sysconfdir}/group &> /dev/null
if [ $? = 1 ]; then
    echo "   0:adding group asterisk..."
    /usr/sbin/groupadd -r -f asterisk
else
    echo "   0:group asterisk already present"
fi

# Modifico usuario asterisk para que tenga "/bin/bash" como shell
/usr/sbin/usermod -c "Asterisk VoIP PBX" -g asterisk -s /bin/bash -d /var/lib/asterisk asterisk

# TODO: TAREA DE POST-INSTALACIÓN
#useradd -d /var/ftp -M -s /sbin/nologin ftpuser
#(echo asterisk2007; sleep 2; echo asterisk2007) | passwd ftpuser

%post

# TODO: tarea de post-instalación.
# Habilito inicio automático de servicios necesarios
chkconfig --level 345 ntpd on
chkconfig --level 345 mysqld on
chkconfig --level 345 mariadb on
chkconfig --level 345 httpd on
chkconfig --del cups  &> /dev/null
chkconfig --del gpm   &> /dev/null


# ** Change content of sudoers ** #
cat   /usr/share/elastix/sudoers > /etc/sudoers
rm -f /usr/share/elastix/sudoers
rm -f /etc/yum.repos.d/elastix.repo

# ** Change content of CentOS-Base.repo ** #
if [ -e /etc/yum.repos.d/CentOS-Base.repo ] ; then
    /usr/bin/elastix-add-yum-exclude /etc/yum.repos.d/CentOS-Base.repo 'redhat-logos' 'php53*' 'kernel*'
fi

# Patch httpd.conf so that User and Group directives in issabel.conf take effect
sed --in-place "s,User\sapache,#User apache,g" /etc/httpd/conf/httpd.conf
sed --in-place "s,Group\sapache,#Group apache,g" /etc/httpd/conf/httpd.conf

# Patch php.conf to remove the assignment to session.save_path in CentOS 7
sed --in-place "s,php_value session.save_path,#php_value session.save_path,g" /etc/httpd/conf.d/php.conf

# ** Uso de elastix-dbprocess ** #
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
preversion=`cat $pathModule/preversion_elastix-framework.info`
rm -f $pathModule/preversion_elastix-framework.info

if [ $1 -eq 1 ]; then #install
    # The installer database
    elastixversion=`rpm -q --queryformat='%{VERSION}-%{RELEASE}' elastix`
    verifyVersion=`echo $elastixversion | grep -oE "^[0-9]+(\.[0-9]+){1,2}-[0-9]+$"`
    if [ "$verifyVersion" == "" ]; then
	elastix-dbprocess "install" "$pathModule/setup/db"
    else
	elastix-dbprocess "update"  "$pathModule/setup/db" "$verifyVersion"
    fi
    /sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
    	echo "Restarting apache..."
    	/sbin/service httpd restart > /dev/null 2>&1
    fi
elif [ $1 -eq 2 ]; then #update
    elastix-dbprocess "update"  "$pathModule/setup/db" "$preversion"
    /sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
    	# Para versiones menores a 2.2.0-15 se debe reiniciar el apache debido a cambios en issabel.conf
    	compareVersion "$preversion" "2.2.0-15"
    	if [ "$?" == "9" ]; then
        	echo "Restarting apache..."
        	/sbin/service httpd restart > /dev/null 2>&1
    	fi
    fi
fi

# Se revisa la clave de ami si esta en /etc/issabel.conf
search_ami_admin_pwd
if [ "$?" == "1" ]; then
	echo "Restarting amportal..."
        /usr/sbin/amportal restart > /dev/null 2>&1
fi

# Actualizacion About Version Release
# Verificar si en la base ya existe algo
if [ "`sqlite3 /var/www/db/settings.db "select count(key) from settings where key='elastix_version_release';"`" = "0" ]; then
    `sqlite3 /var/www/db/settings.db "insert into settings (key, value) values('elastix_version_release','%{version}-%{release}');"`
else
    #Actualizar
    `sqlite3 /var/www/db/settings.db "update settings set value='%{version}-%{release}' where key='elastix_version_release';"`
fi

# Para q se actualice smarty (tpl updates)
rm -rf /var/www/html/var/templates_c/*

# Patch elastix.ini to work around %config(noreplace) in previous versions
sed --in-place "s,/tmp,/var/lib/php/session-asterisk,g" /etc/php.d/elastix.ini
if [ $1 -eq 1 ]; then #install
    /sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
        echo "Restarting apache..."
        /sbin/service httpd restart > /dev/null 2>&1
    fi
elif [ $1 -eq 2 ]; then #update
    /sbin/service httpd status > /dev/null 2>&1
    if [ "$?" == "0" ]; then
        # Para versiones menores a 2.4.0-11 se debe reiniciar el apache debido a cambios en elastix.ini
        # respecto a los archivos de sessiones, por ello tambien hay que reubicarlos
        compareVersion "$preversion" "2.4.0-11"
        if [ "$?" == "9" ]; then
             # Patch elastix.ini, relocate session files to the new path.
            echo "Session files in the old directory. Starting relocation process..."
            for file_sess in `ls /tmp/sess_*`
            do
              file_name=`basename $file_sess`
              if [ -f /var/lib/php/session-asterisk/$file_name ]; then
                rm -rf /var/lib/php/session-asterisk/$file_name
              fi

              echo "Copying file /tmp/$file_name to /var/lib/php/session-asterisk/$file_name."
              cp -p /tmp/$file_name /var/lib/php/session-asterisk/
            done

            echo "Restarting apache..."
            /sbin/service httpd restart > /dev/null 2>&1
        fi
    fi
fi

# Merge current menu.xml for userlist custom privileges
elastix-menumerge $pathModule/menu.xml

# Los archivos de logrotate TIENEN que ser 0644 (http://bugs.elastix.org/view.php?id=2608)
chmod 644 /etc/logrotate.d/elastixAudit.logrotate
chmod 644 /etc/logrotate.d/elastixEmailStats.logrotate

%preun
# Reverse the patching of php.conf
sed --in-place "s,#php_value session.save_path,php_value session.save_path,g" /etc/httpd/conf.d/php.conf

# Reverse the patching of httpd.conf
sed --in-place "s,#User\sapache,User apache,g" /etc/httpd/conf/httpd.conf
sed --in-place "s,#Group\sapache,Group apache,g" /etc/httpd/conf/httpd.conf
pathModule="/usr/share/elastix/module_installer/%{name}-%{version}-%{release}"
if [ $1 -eq 0 ] ; then # Validation for desinstall this rpm
  echo "Dump and delete %{name} databases"
  elastix-dbprocess "delete" "$pathModule/setup/db"
fi

%clean
rm -rf $RPM_BUILD_ROOT

# basic contains some reasonable sane basic tiles
%files
%defattr(-, asterisk, asterisk)
/var/www/db
/var/www/backup
/var/log/elastix
/var/log/elastix/*
# %config(noreplace) /var/www/db/
%defattr(-, root, root)
/var/www/html/configs
/var/www/html/favicon.ico
/var/www/html/help
/var/www/html/images
/var/www/html/lang
/var/www/html/libs
/var/www/html/modules
/var/www/html/themes/tenant
/var/www/html/*.php
/var/www/html/robots.txt
/var/www/html/panels/README.en
/var/www/html/panels/README.es
/var/www/html/var
/var/www/html/var/.htaccess
/usr/share/elastix/*
/usr/share/pear/DB/sqlite3.php
/usr/local/elastix/sampler.php
/usr/local/sbin/motd.sh
/usr/sbin/close-on-exec.pl
/usr/bin/elastix-menumerge
/usr/bin/elastix-menuremove
/usr/bin/elastix-dbprocess
/usr/bin/elastix-helper
/usr/bin/elastix-add-yum-exclude
/usr/bin/elastix-notification
/usr/bin/compareVersion
/usr/bin/search_ami_admin_pwd
/usr/sbin/elastix-helper
%config(noreplace) /etc/cron.d/elastix.cron
%config(noreplace) /etc/httpd/conf.d/issabel.conf
%config(noreplace) /etc/php.d/elastix.ini
/etc/yum.repos.d/Issabel.repo
#%config(noreplace) /etc/yum.repos.d/Issabel.repo
#%config(noreplace) /etc/logrotate.d/elastixAccess.logrotate
%config(noreplace) /etc/logrotate.d/elastixAudit.logrotate
%config(noreplace) /etc/logrotate.d/elastixEmailStats.logrotate
%config /etc/httpd/conf.d/elastix-htaccess.conf
/etc/init.d/generic-cloexec
%defattr(755, root, root)
/usr/share/elastix/privileged/*
%defattr(770, root, asterisk, 770)
/var/lib/php/session-asterisk
%defattr(-, asterisk, asterisk)
/var/www/html/cache/.dummy
/var/www/html/templates_c/.dummy

%files themes-extra
%defattr(-, root, root)
/var/www/html/themes/*
%exclude /var/www/html/themes/tenant

%changelog
