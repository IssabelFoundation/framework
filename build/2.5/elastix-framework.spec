%define modname framework
Summary: Issabel is a Web based software to administrate a PBX based in open source programs, forked from Elastix
Name: elastix-%{modname}
Vendor: Issabel Project
Version: 2.5.0
Release: 21
License: GPL
Group: Applications/System
Source0: %{modname}_%{version}-%{release}.tgz

BuildRoot: %{_tmppath}/%{name}-%{version}-root
BuildArch: noarch
Requires(pre): /sbin/chkconfig, /etc/sudoers, sudo
Requires(pre): php, php-gd, php-pear, php-xml, php-mysql, php-pdo, php-imap, php-soap
Requires(pre): httpd, mysql-server, ntp, mod_ssl
# /usr/sbin/close-on-exec.pl /usr/local/sbin/motd.sh
Requires(pre): perl
Requires(pre): elastix-firstboot >= 2.3.0-4
Requires(pre): /sbin/pidof
Obsoletes: elastix-additionals
Provides: elastix-additionals
Conflicts: elastix-system <= 2.5.0-5
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
Requires: elastix-framework = %{version}-%{release}

%description themes-extra
This package provides the Issbel GUI themes from earlier versions.


%prep
%setup -n framework

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
mv $RPM_BUILD_DIR/elastix-framework/framework/html/*                              $RPM_BUILD_ROOT/var/www/html/

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
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/httpd/conf.d/elastix.conf        $RPM_BUILD_ROOT/etc/httpd/conf.d/
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/httpd/conf.d/elastix-htaccess.conf  $RPM_BUILD_ROOT/etc/httpd/conf.d/
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/php.d/elastix.ini                $RPM_BUILD_ROOT/etc/php.d/

# ** crons config ** #
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/cron.d/elastix.cron              $RPM_BUILD_ROOT/etc/cron.d/
chmod 644 $RPM_BUILD_ROOT/etc/cron.d/*

# ** Repos config ** #
#mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/yum.repos.d/CentOS-Base.repo     $RPM_BUILD_ROOT/usr/share/elastix/
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/yum.repos.d/Issabel.repo         $RPM_BUILD_ROOT/etc/yum.repos.d/

# ** sudoers config ** #
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/sudoers                          $RPM_BUILD_ROOT/usr/share/elastix/

# ** /usr/local/ files ** #
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/local/elastix/sampler.php        $RPM_BUILD_ROOT/usr/local/elastix/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/local/sbin/motd.sh               $RPM_BUILD_ROOT/usr/local/sbin/
chmod 755 $RPM_BUILD_ROOT/usr/local/sbin/motd.sh

# ** /usr/share/ files ** #
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/share/pear/DB/sqlite3.php                    $RPM_BUILD_ROOT/usr/share/pear/DB/

# ** setup ** #
mv $RPM_BUILD_DIR/elastix-framework/framework/setup/usr/share/elastix/privileged/*   $RPM_BUILD_ROOT/usr/share/elastix/privileged/
rmdir framework/setup/usr/share/elastix/privileged/ framework/setup/usr/share/elastix framework/setup/usr/share framework/setup/usr
mv $RPM_BUILD_DIR/elastix-framework/framework/setup/                                 $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
mv $RPM_BUILD_DIR/elastix-framework/framework/menu.xml                               $RPM_BUILD_ROOT/usr/share/elastix/module_installer/%{name}-%{version}-%{release}/

# ** elastix-* file ** #
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/elastix-menumerge            $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/elastix-menuremove           $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/elastix-dbprocess            $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/compareVersion		   $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/search_ami_admin_pwd             $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/elastix-add-yum-exclude             $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/elastix-notification             $RPM_BUILD_ROOT/usr/bin/
chmod 755 $RPM_BUILD_ROOT/usr/bin/compareVersion
chmod 755 $RPM_BUILD_ROOT/usr/bin/search_ami_admin_pwd

# ** Moving elastix_helper
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/bin/elastix-helper               $RPM_BUILD_ROOT/usr/bin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/sbin/elastix-helper              $RPM_BUILD_ROOT/usr/sbin/

chmod 755 $RPM_BUILD_ROOT/usr/sbin/elastix-helper
chmod 755 $RPM_BUILD_ROOT/usr/bin/elastix-helper


# Archivos generic-cloexec y close-on-exec.pl
mv $RPM_BUILD_DIR/elastix-framework/additionals/usr/sbin/close-on-exec.pl            $RPM_BUILD_ROOT/usr/sbin/
mv $RPM_BUILD_DIR/elastix-framework/additionals/etc/init.d/generic-cloexec           $RPM_BUILD_ROOT/etc/init.d/

#Logrotate
mkdir -p    $RPM_BUILD_ROOT/etc/logrotate.d/
mv          $RPM_BUILD_DIR/elastix-framework/additionals/etc/logrotate.d/*           $RPM_BUILD_ROOT/etc/logrotate.d/
# Los archivos de logrotate TIENEN que ser 0644 (http://bugs.elastix.org/view.php?id=2608)
chmod 644 $RPM_BUILD_ROOT/etc/logrotate.d/*

# File Issabel Access Audit log
mkdir -p    $RPM_BUILD_ROOT/var/log/elastix
touch       $RPM_BUILD_ROOT/var/log/elastix/audit.log
touch	    $RPM_BUILD_ROOT/var/log/elastix/postfix_stats.log

%pre
#Para conocer la version de elastix antes de actualizar o instalar
mkdir -p /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/
touch /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_elastix-framework.info
if [ $1 -eq 2 ]; then
    rpm -q --queryformat='%{VERSION}-%{RELEASE}' %{name} > /usr/share/elastix/module_installer/%{name}-%{version}-%{release}/preversion_elastix-framework.info
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
    /usr/bin/elastix-add-yum-exclude /etc/yum.repos.d/CentOS-Base.repo 'redhat-logos' 'php53*'
fi

# Patch httpd.conf so that User and Group directives in elastix.conf take effect
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
    	# Para versiones menores a 2.2.0-15 se debe reiniciar el apache debido a cambios en elastix.conf
    	compareVersion "$preversion" "2.2.0-15"
    	if [ "$?" == "9" ]; then
        	echo "Restarting apache..."
        	/sbin/service httpd restart > /dev/null 2>&1
    	fi
    fi
fi

# Se revisa la clave de ami si esta en /etc/elastix.conf
search_ami_admin_pwd
if [ "$?" == "1" ]; then
	echo "Restarting amportal..."
        /usr/sbin/amportal restart > /dev/null 2>&1
fi

# Actualizacion About Version Release
# Verificar si en la base ya existe algo
if [ "`sqlite3 /var/www/db/settings.db "select count(key) from settings where key='issabel_version_release';"`" = "0" ]; then
    `sqlite3 /var/www/db/settings.db "insert into settings (key, value) values('issabel_version_release','%{version}-%{release}');"`
else
    #Actualizar
    `sqlite3 /var/www/db/settings.db "update settings set value='%{version}-%{release}' where key='issabel_version_release';"`
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
%config(noreplace) /etc/httpd/conf.d/elastix.conf
%config(noreplace) /etc/php.d/elastix.ini
%config(noreplace) /etc/yum.repos.d/Issabel.repo
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
/var/www/html/var/cache
/var/www/html/var/templates_c

%files themes-extra
%defattr(-, root, root)
/var/www/html/themes/*
%exclude /var/www/html/themes/tenant

%changelog
* Wed Nov 23 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-20
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Wed Nov 23 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: fix file packaging ordering that results in /var/www/html/var/templates_c
  and /var/www/html/var/cache being owned by root instead of asterisk.
  SVN Rev[7768]

* Thu Nov 17 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-19
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Sat Nov 12 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: add and enforce .htaccess file on /var/www/html/var. Change ownership
  of /var/www/html/var to root.root.
  SVN Rev[7756]

* Fri Nov 11 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: The ARI webpage from FreePBX contains a .htaccess file that must be
  obeyed to harden the system against arbitrary file upload exploits on
  /recordings, just as it is done for /admin. The Apache service must be
  restarted after this update.
  SVN Rev[7755]

* Sat Oct  8 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: framework - add invisible submit button on filter grid for all themes
  so visible buttons, such as the Delete button, are not selected as the default
  submit button when pressing ENTER.
  SVN Rev[7752]

* Wed Sep 21 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: change permissions for logrotate files in /etc/logrotate.d/ to 0644.
  Fixes Elastix bug #2608 for elastix-framework package.
  SVN Rev[7747]

* Mon Sep 19 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Transmit displayName of extension of current user. For use with
  Elastix WebPhone Panel.
  SVN Rev[7446]

* Thu Sep 08 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: further fix of notification message components under tenant theme to
  force right-alignment of dismissal button.
  SVN Rev[7745]

* Fri Sep 02 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-18
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Wed Aug 31 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Group Permission: fix capitalization that prevents i18n from being used.
  SVN Rev[7742]

* Tue Aug 30 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: paloSantoSampler class now uses SQL parameters.
  SVN Rev[7740]
- FIXED: Framework: explicitly specify period as decimal separator and no
  thousands separator in number_format() in order to get locale-independent
  sample value in sampler.php.
  SVN Rev[7739]

* Mon Aug 22 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-17
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Fri Aug 19 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: expand filter panel on tenant theme to mitigate filter control wrapping.
  SVN Rev[7332]
- FIXED: fix misalignment of notification message components on tenant theme.
  SVN Rev[7331]

* Wed Aug 17 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Userlist: improve template correctness on user modification popup.
  SVN Rev[7720]

* Tue Aug 16 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-16
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Mon Aug 15 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- ADDED: Framework: add documentation on the sidebar panel API.
  SVN Rev[7709]

* Sun Aug 14 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- ADDED: Framework: define a compatible menu.xml for elastix-framework. This
  menu.xml contains custom privileges for the userlist module.
  SVN Rev[7706]
- CHANGED: Framework: add new methods to paloACL required for custom privilege
  assignment in Group Permission.
  SVN Rev[7704]
- CHANGED: Group Permission: create new option for each module that defines
  custom privileges, in order to grant or revoke them per group. Step 3 of
  implementation of per-module privileges to fix Elastix bug #1100.
  SVN Rev[7703]

* Sat Aug 13 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: fix reversed check on link for framed menuitem.
  SVN Rev[7702]
- CHANGED: Framework: the menu.xml format has been extended to encode custom
  module privileges. New methods have been implemented in paloACL to record them
  into the ACL database.
  SVN Rev[7699]
- CHANGED: Framework: implement paloACL::hasModulePrivilege() method. Step 2 of
  implementation of per-module privileges to fix Elastix bug #1100.
  SVN Rev[7698]
- CHANGED: Framework: code cleanup in paloSantoInstaller.class.php.
  SVN Rev[7697]
- CHANGED: Framework: use SQL parameters in paloMenu::createMenu and paloMenu::
  updateItemMenu. Introduce stricter checks on parameter consistency and
  existence of parent menu.
  SVN Rev[7696]
- CHANGED: Framework: rename paloMenu::deleteFather to deleteMenu. Make
  elastix-menuremove return a failure exit state on error, and silence output on
  success. Add failure cause to error message.
  SVN Rev[7695]
- CHANGED: Framework: rewrite menu item removal without using recursion. Also
  remove non-leaf nodes that contained the referenced menu item if it was the
  last child. Remove redundant function call to paloACL.
  SVN Rev[7694]
- CHANGED: Framework: make paloACL aware of per-module privileges when removing
  groups, users and resources.
  SVN Rev[7692]
- ADDED: Framework: new acl tables for per-module privileges. Step 1 of
  implementation of per-module privileges to fix Elastix bug #1100.
  SVN Rev[7691]

* Tue Aug  9 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- ADDED: Framework: add new plugin-aware userlist module. At this point, this
  module is still being replaced by the userlist module in elastix-system.
  SVN Rev[7690]
- DELETED: Framework: remove userlist module from framework. This module has
  been superseded by the userlist module in elastix-system since a long time
  ago. A later commit will replace it with the rewritten plugin-aware module.
  SVN Rev[7689]

* Mon Aug 08 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-15
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Sat Aug  6 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: implement support for Elastix Panels on themes: giox,
  elastixwave, elastixneo.
  SVN Rev[7685]
- CHANGED: Framework: implement support for Elastix Panels on blackmin theme.
  SVN Rev[7684]

* Wed Aug  3 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: allow individual panels to remove themselves from rendering
  by returning a non-array from templateContent() method. Also doubles as a
  validation check.
  SVN Rev[7683]

* Tue Aug  2 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: implement panel icon support on Tenant theme.
  SVN Rev[7682]
- CHANGED: Framework: implement i18n for panel sidebar title.
  SVN Rev[7681]
- CHANGED: Framework: implement initial support for Elastix Panels. These panels
  will appear on a sidebar to the right of the GUI, and will always be available
  regardless of the logged-in user. For now available only on Tenant theme.
  SVN Rev[7680]

* Fri Jul 15 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-14
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Mon Jul 11 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: Parameter $extension of updateUser is now optional, and
  will preserve the previous extension if not specified. To clear the extension,
  an empty string is required, as used in userlist module.
  SVN Rev[7672]
- CHANGED: Framework: create new paloACL method setUserExtension to update the
  extension of a single user. The $extension parameter of createUser is now
  optional.
  SVN Rev[7671]
- CHANGED: Framework: create new paloACL methods getUserProfile and
  saveUserProfile intended for use with the userlist module.
  SVN Rev[7669]

* Mon Jul  4 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: only auto-popup on non-registration for admin users.
  Non-admin users do not have authorization to initiate registration and
  therefore should not see a popup with "no authorization" error message.
  SVN Rev[7642]

* Fri Jun  3 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: the elastix-monitoring_services addon up to 5.4.1-8 uses
  the obsolete /register.php route removed in SVN commit #7912. The required
  Conflicts: header is included now.
  SVN Rev[7626]

* Wed Jun  1 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: implement method to preserve initial column widths on
  standard tables after applying colResizable() on them.
  SVN Rev[7625]

* Thu May 26 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- ADDED: Framework: add Turkish translation for main menus and Language module.
  SVN Rev[7622]

* Wed May 25 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: add some documentation to addFilterControl method in class
  paloSantoGrid().
  SVN Rev[7618]
- FIXED: Language: code cleanup. Use master language list instead of local
  available module translations to populate dropdown list. Fixes Elastix
  bug #2532.
  SVN Rev[7617]

* Thu Apr 21 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: Help screen should now load suitably translated help file,
  not only the ones in English and Spanish.
  SVN Rev[7590]

* Fri Apr  8 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: force registration popup to appear on first logged-in
  display if system is not registered.
  SVN Rev[7565]

* Mon Mar 14 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: enable larger limits for post_max_size and
  max_upload_filesize, since previous values are too small for common CallCenter
  uploads.
  SVN Rev[7519]

* Fri Mar 11 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-13
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Thu Mar 10 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: work around PHP 5.1.x bug in PDO that internally truncates
  integer values to 32 bits even on x86_64. Further fix for Elastix bug #2477.
  SVN Rev[7516]

* Thu Mar 10 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-12
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Mon Mar  7 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: prevent string-to-int conversion on database write if
  resulting int would exceed INT_MAX on the current system. Fixes Elastix
  bug #2477.
  SVN Rev[7507]

* Sun Mar  6 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Frameork: remove space for expand tab when in mini-menu mode in
  rewritten elastixwave and giox themes.
  SVN Rev[7505]

* Sat Mar  5 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: completely rewrite giox theme starting from elastixwave
  rewrite. This rewrite is based on the elastixneo theme and fixes the spilling
  of menu items past the right edge of the browser window. A best-effort attempt
  is made to preserve key aspects of L&F, particularly the mini-menu feature.
  SVN Rev[7504]

* Tue Feb 25 2016 Luis Abarca <labarca@palosanto.com> 2.5.0-11
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[7489]

* Fri Feb 12 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: use correct method set_error to report problems on user
  extension report.
  SVN Rev[7476]

* Wed Feb 10 2016 Luis Abarca <labarca@palosanto.com>
- CHANGED: branches/2.5.0 - _menu.tpl: Corrected icons in the notification
  header of Tenant theme.
  SVN Rev[7472]

* Fri Feb 05 2016 Luis Abarca <labarca@palosanto.com>
- CHANGED: branches/2.5.0 - styles.css-_menu.tpl: Corrected positions in
  framework about sticky note and hide some icons of functionalities currently
  in development.
  SVN Rev[7462]

* Mon Feb  1 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: added new _issabelutils action to expose telephony user
  and password. Intended to be used with Elastix Webphone.
  SVN Rev[7456]

* Thu Jan  7 2016 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: block kernel updates. The CentOS 7 kernel ABI is changing
  too quickly to follow reliably and continuously breaks the DAHDI modules. At
  this point in time, the tested kernel version is 3.10.0-229.14.1.el7.x86_64 .
  SVN Rev[7414]
- CHANGED: Framework: remove regexp check for username. This check was required
  back when SQL parameters were not used and should not be necessary now.
  SVN Rev[7412]

* Fri Dec 11 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: add support in tenant theme for notification display with
  no icon for removal (yet).
  SVN Rev[7395]
- FIXED: Framework: reverse LIMIT filter on SQL because of SQLITE errors.
  SVN Rev[7394]

* Mon Nov 30 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: relax icon file validation in paloSantoGrid class. The
  CallCenterPRO custom templates depend on legacy non-validation of the icon
  path and require the single-letter fonticon to not be scrubbed-out.
  SVN Rev[7375]

* Mon Nov 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: move help icon from toolbar to breadcrumb bar and change
  icon.
  SVN Rev[7369]
- CHANGED: Framework: add /usr/bin/elastix-notification to installed files.
  SVN Rev[7368]
- DELETED: Framework: file paloSantoModuloXML.class.php was removed. The
  menu.xml loading is now reimplemented using SimpleXML and contained entirely
  in /usr/bin/elastix-menumerge. Additionally more error conditions are detected
  and reported.
  SVN Rev[7367]
- CHANGED: Framework: the method Installer::addModuleLanguage() is apparently
  dead code not used anywhere. Removed. This removes a dependency on the
  ModuloXML class from paloSantoInstaller.class.php.
  SVN Rev[7366]
- CHANGED: Framework: create new program elastix-notification to insert new
  notifications from the command line. Fix bugs discovered through exercise of
  this program.
  SVN Rev[7365]
- CHANGED: Framework: make id_resource column known to paloACL and
  paloNotification classes. Allow insertion of notifications without id_user.
  SVN Rev[7364]
- CHANGED: Framework: add id_resource column to acl_notification table.
  SVN Rev[7363]

* Sat Nov 21 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: completely rewrite elastixwave theme. This rewrite is
  based on the elastixneo theme and fixes the spilling of menu items past the
  right edge of the browser window. A best-effort attempt is made to preserve
  key aspects of L&F, particularly the mini-menu feature.
  SVN Rev[7362]

* Fri Nov 20 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- ADDED: Framework: create new class paloNotification.
  SVN Rev[7360]
- CHANGED: Framework: introduce new table acl_notification. Make it known to
  paloACL::deleteUser() method.
  SVN Rev[7359]

* Sat Nov 14 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: fix corner case in main menu handling in elastixneo theme
  where the selected item would cause the overflow menu to overflow itself after
  being re-added to the main menu.
  SVN Rev[7355]

* Wed Nov 10 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: introduce methods paloACL::getUserProfileProperty() and
  paloACL::saveUserProfileProperty(). Switch menu color management to use these
  methods as a proof of concept.
  SVN Rev[7352]

* Tue Nov 10 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: add explicit index.php in two themes where it would
  otherwise be replaced by config.php when displaying Embedded FreePBX.
  SVN Rev[7351]
- ADDED: Framework: include jquery-migrate to work around modules requiring
  jQuery 1.8 APIs, in particular Embedded FreePBX.
  SVN Rev[7350]

* Fri Nov  6 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: reintroduce .menulogo2 CSS style in elastixneo theme since
  it is used in two templates from patched FreePBX.
  SVN Rev[7335]

* Thu Nov  5 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: complete rewrite of PDF exporting code. This rewrite fixes
  the following bugs:
  - The page break check fails to work correctly since the migration to TCPDF.
    This resulted in invisible data past the first page of a multi-page PDF.
  - Missing internationalization of page counter and messages.
  - Incorrect positioning of page table past the first page.
  - The request to use landscape layout was being ignored.
  - The Verdana font selection has never worked, and the report was silently
    defaulting to Helvetica due to the child class incorrectly overriding the
    setFont() method. The font that is actually used is now acknowledged in
    code.
  SVN Rev[7330]

* Wed Nov  4 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: force cast to int of query parameter that is a numeric
  string that needs to be bound as PDO::PARAM_INT. Required for CentOS 7.
  SVN Rev[7327]
- CHANGED: Themes System: filter invalid theme dirs from combo. Remove dependency
  on paloSantoInstaller and instead flush Smarty cache directly.
  SVN Rev[7326]
- CHANGED: Framework: rework load_theme() function to be more robust and
  fallback to tenant theme in case chosen theme is no longer available, as well
  as performing validations on whether the theme directory is usable.
  SVN Rev[7325]

* Sat Oct 31 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: the paloSantoForm class has been modified to use methods
  for each known widget. This allows the class to be subclassed and methods
  for specific widgets overloaded or added.
  SVN Rev[7291]
- CHANGED: Group Permission: Updated Russian translation. Provided by user
  Russian.
  SVN Rev[7288]
- CHANGED: Group List: Updated Russian translation. Provided by user Russian.
  SVN Rev[7287]
- CHANGED: Framework: Updated Russian translation (3). Provided by user Russian.
  SVN Rev[7286]

* Fri Oct 30 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: tweak elastixneo and blackmin themes so that the download
  links extend to the full width of the yellow highlight.
  SVN Rev[7284]
- FIXED: Framework: fix invalid exclude syntax in specfile.
  SVN Rev[7278]

* Thu Oct 29 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: move edwidgets to libs/js/jquery/jquery-edwidgets.js so
  all themes may use it.
  SVN Rev[7273]
- FIXED: Framework: do not assume TIMELIB is defined in bootstrap-datetimepicker
  check.
  SVN Rev[7269]

* Wed Oct 28 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: further tweaks to the tenant theme by Edgar Landivar.
  SVN Rev[7268]
- ADDED: Framework: experimental colorpicker widget for tenant theme only.
  SVN Rev[7267]
- CHANGED: Framework: experimental support for bootstrap-datetimepicker widget.
  SVN Rev[7266]
- DELETED: Framework: remove unused easypiechart javascript library.
  SVN Rev[7265]
- CHANGED: Framework: elastix-framework-themes-extras requires elastix-framework
  SVN Rev[7264]

* Tue Oct 27 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: explicitly spell out previously hidden package
  requirements that provide system commands.
  SVN Rev[7258]

* Mon Oct 26 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: attempt 1 to split off all themes except tenant and
  blackmin into a separate package.
  SVN Rev[7256]
- DELETED: menusAdminElx: apparently not used anywhere.
  SVN Rev[7255]
- CHANGED: Framework: allow ELASTIX_ROOT to specify location of base Elastix
  for command-line tools.
  SVN Rev[7254]
- DELETED: elastix-menutranslate: apparently not used anywhere.
  SVN Rev[7253]
- CHANGED: elastix-menumerge, elastix-menuremove: use elastix_dsn instead of
  hardcoding sqlite DSN.
  SVN Rev[7252]
- CHANGED: Group Permission: use elastix_dsn.menu instead of hardcoding DSN.
  SVN Rev[7251]
- CHANGED: Framework: use elastix_dsn.acl instead of hardcoding sqlite DSN.
  SVN Rev[7250]
- CHANGED: Framework: use elastix_dsn.acl instead of hardcoding sqlite DSN. Code
  cleanup of bookmark add/remove code. Ensure AJAX response for bookmark request
  is of type application/json. Update error message on bookmark list full. Add
  Spanish translations for messages.
  SVN Rev[7249]

* Fri Oct 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: massive s/www.elastix.org/www.elastix.com/g
  SVN Rev[7231]

* Thu Oct 22 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: use Bootstrap grid system properly on tenant theme, fixes
  overlapping link bar on low resolution screens.
  SVN Rev[7229]
- CHANGED: Framework: add several tool links for tenant theme.
  SVN Rev[7228]
- FIXED: tweak button style in blackmin theme.
  SVN Rev[7227]
- FIXED: Framework: fix table grid rounded borders on tenant theme on Firefox.
  Migrate table grid style on tenant to use elastix-standard-table class.
  SVN Rev[7225]

* Wed Oct 21 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: tweak list table to prevent incorrect positioning.
  SVN Rev[7223]
- CHANGED: Framework: use font icons in blackmin theme.
  SVN Rev[7222]
- CHANGED: Framework: whitelist entypo font icons. If a control specifies a
  font icon, it is checked against the font-awesome whitelist, followed by the
  entypo whitelist.
  SVN Rev[7220]
- CHANGED: Framework: remove theme guard in paloSantoGrid, allowing the font
  icon to be used on any theme that knows about it.
  SVN Rev[7219]
- CHANGED: Framework: move font icons from tenant theme to libs/font-icons so
  all themes can use them. Update paloSantoNavigation to add links to fonts
  under font-icons.
  SVN Rev[7216]
- CHANGED: Framework: code cleanup in paloSantoNavigation.
  SVN Rev[7215]
- DELETED: Framework: (trivial) remove backup css file in tenant theme.
  SVN Rev[7214]
- CHANGED: Framework: (trivial) fix background-image paths on tenant css styles.
  SVN Rev[7212]

* Tue Oct 20 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: (trivial) tweak button class in blackmin theme to make it
  more similar to an input button.
  SVN Rev[7205]
- FIXED: Framework: use module name instead of id for shortcuts in tenant theme.
  SVN Rev[7203]
- CHANGED: Framework: allow modules to specify a icon class for the tenant
  theme. Currently this will show no icon on other themes.
  SVN Rev[7202]
- FIXED: Framework: update link control in paloSantoGrid for tenant theme so
  that it displays the font icon, while preserving backward compatibility with
  other themes and custom icons. Opportunistically map known framework icons
  to font icons in addAction method.
  SVN Rev[7200]
- CHANGED: Framework: rework of tenant theme by Edgar Landivar.
  SVN Rev[7199]
- DELETED: Framework: remove fonts.old folder in tenant theme.
  SVN Rev[7198]

* Sat Oct 17 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: (trivial) add dummy href attribute to link in elastixneo.
  SVN Rev[7193]

* Wed Oct 14 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- DELETED: Framework: remove register.php helper. Update Conflicts: for
  elastix-addons to match.
  SVN Rev[7192]
- CHANGED: Framework: add register_link as a standard CSS class for all themes.
  SVN Rev[7190]
- CHANGED: Framework: use plain $.get instead of request() for three dialogs.
  SVN Rev[7189]
- CHANGED: Framework: route all registration actions through explicit
  registration module instead of register.php.
  SVN Rev[7188]
- FIXED: Framework: move Content-Type assignments to cover registration error
  cases.
  SVN Rev[7187]
- CHANGED: Framework: use jQuery instead of onclick for registration link.
  SVN Rev[7186]
- CHANGED: Framework: move one javascript function to registration module. Fix
  the rest of the base.js calls to use registration module explicitly.
  SVN Rev[7185]
- FIXED: Framework: add Content-Type: application/json to all registration
  responses.
  SVN Rev[7184]
- CHANGED: Framework: the _issabelutils module is no longer "special". Instead
  a list is defined in the framework configuration for modules to be provided
  without ACL authorization. This enables functionality to be migrated away from
  register.php and removes the _issabelutils special case.
  SVN Rev[7182]

* Tue Oct 13 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Updated Russian translation (2). Provided by user Russian.
  SVN Rev[7179]

* Thu Oct  8 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Updated Russian translation. Provided by user Russian.
  SVN Rev[7176]
* Tue Oct  6 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: code cleanup in paloSantoACL class:
  - all cases of string concatenation for SQL replaced with SQL parameters
  - factored common read patterns into helper functions
  - reimplementation of some functions as special cases of others
  - added missing cascade deletion in user and resource deletion.
  SVN Rev[7173]

* Mon Oct  5 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: use backticks for column names that are reserved keywords
  in MySQL.
  SVN Rev[7172]
- CHANGED: Framework: isolate sqlite3-specific operations in menu list.
  SVN Rev[7171]
- CHANGED: Framework (trivial): add error message reporting for failure to filter
  menulist.
  SVN Rev[7170]
- CHANGED: Framework: tweaks to make framework (mostly) relocatable - REST
  SVN Rev[7169]
- CHANGED: Framework: tweaks to make framework (mostly) relocatable - Help.
  SVN Rev[7168]
- CHANGED: Framework: tweaks to make framework (mostly) relocatable - GUI.
  SVN Rev[7166]

* Fri Sep 25 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-10
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[7156]

* Wed Jul 29 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: javascript method showPopupElastix has been replaced by
  method showPopupCloudLogin. Now elastix-framework must be marked as conflicting
  with older versions of elastix-addons which used the removed method.
  SVN Rev[7117]

* Wed Jun 24 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: (trivial) tweak sizes of default controls for blackmin
  theme
  SVN Rev[7098]

* Mon May  4 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: fix invalid HTML syntax on datetime control.
  SVN Rev[7037]

* Mon Apr 27 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-9
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[7026]

* Thu Apr 23 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: fix bookmark toggling that was incorrectly placed at the
  window resize handler.
  SVN Rev[7021]
- CHANGED: Framework: complete reimplementation of elastixneo theme. This
  reimplementation does away with many unused images, replaces many uses of
  javascript for hover behavior with equivalent :hover CSS rules, allows free
  resizing instead of fixing the module width to 1280 pixels, reworks the menu
  overflow to cope with resizing, replaces many uses of the neo-display-none
  class with .hide() and .show jQuery calls, and copies a standard table class
  from the blackmin theme.
  SVN Rev[7020]

* Wed Apr 22 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-8
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[7019]

* Tue Apr 21 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- Framework: force search div to remain visible while suggestion menu is open.
  SVN Rev[7017]

* Mon Apr 20 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- Framework: simplify autocomplete binding for module search.
  SVN Rev[7015]
- Framework: add missing template listcsv.tpl to blackmin theme.
  SVN Rev[7014]
- Framework: remove unused templates _alert_container_{confirmation|information|
  error}.tpl in all themes.
  SVN Rev[7013]

* Fri Apr 17 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-7
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Fri Apr 17 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
  Framework: restore image in elastixneo theme. The callcenterPRO module makes
  use of it.
  SVN Rev[7008]

* Thu Apr 16 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: stop testing for elastixneo theme in Sticky Note
  implementation. Instead use the control that contains the theme name.
  SVN Rev[7006]

* Wed Apr 15 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: Framework: fix regression in bookmark handling.
  SVN Rev[7005]

* Mon Apr 13 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: further tweak to grid support in order to fix double
  borders.
  SVN Rev[7004]

* Sun Apr 12 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: add colResizable support to blackmin theme grid.
  SVN Rev[7002]
- CHANGED: Framework: shorten CSS class names in blackmin theme and attempt to
  introduce a future naming standard. Factor out table grid as a separate class
  that could be applied to standalone tables not part of a recordset grid.
  SVN Rev[7001]

* Sat Apr 11 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: tweak blackmin menu to remove invisible overhang on menu
  items that forces a scrollbar to appear when right-hand menus are displayed.
  SVN Rev[6999]
- CHANGED: Framework: move the Change Password dialog to elastixutils.
  SVN Rev[6997]
- CHANGED: Framework: move the About Us dialog from registration to elastixutils
  SVN Rev[6996]
- CHANGED: Framework: replace function mostrar() with anonymous function linked
  to CSS class.
  SVN Rev[6994]

* Fri Apr 10 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: improve dimension calculation on popup dialog.
  SVN Rev[6992]
- CHANGED: Framework: remove legacy About dialog on older themes so that newer
  About dialog can be seen.
  SVN Rev[6991]
- CHANGED: Framework: nothing uses the PopupElastix div anymore. Removed.
  SVN Rev[6990]
- CHANGED: Framework: reimplement old function changeMenu() using jQuery.
  SVN Rev[6989]
- CHANGED: Framework: stop using an Array object as a hashtable in registration
  SVN Rev[6988]
- CHANGED: Framework: stop using an Array object as a hashtable in base.js
  SVN Rev[6987]
- CHANGED: Framework: move many elastixneo-specific javascript into its own
  private javascript file.
  SVN Rev[6986]
- CHANGED: Framework: add missing images for Sticky Note status for blackmin
  SVN Rev[6985]
- CHANGED: Framework: move association of setAdminPassword to classname.
  SVN Rev[6984]
- CHANGED: Framework: factor out one more instance of blockUI
  SVN Rev[6983]
- CHANGED: Framework: boxRPM element does not appear anywhere, style removed.
- CHANGED: Framework: box_overlayRPM does not appear anywhere, style removed.
- CHANGED: Framework: close_image_box does not appear anywhere, style removed.
- CHANGED: Framework: fade_overlay is never used, style and element removed.
- REMOVED: Framework: remove various unused images from themes.
  SVN Rev[6982]
- CHANGED: Framework: remove unused hidden input with translated text for
  package version dialog.
  SVN Rev[6981]
- CHANGED: Framework: move responsibility for rpm package version dialog to
  elastixutils. Collect all css and javascript into its own method and remove
  duplicates in all themes.
  SVN Rev[6980]

* Thu Apr 09 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: by properly setting Content-Type, the JSONRPMtoString
  method becomes unnecessary and can be removed.
  SVN Rev[6978]
- CHANGED: Framework: add support for Sticky Note to blackmin theme.
  SVN Rev[6976]
- CHANGED: Framework: Move Sticky Note autopopup support to the index.tpl of
  every theme for future migration to separate template. Remove compatibility
  functions for Sticky Note. Remove need for definition of neo-display-none
  class.
  SVN Rev[6975]
- FIXED: Framework: Sticky Note support for autopopup is supposed to be enabled
  for all themes but is actually broken for all except elastixneo and tenant.
  Fixed.
  SVN Rev[6974]

* Thu Apr 09 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-6
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6972]

* Thu Apr 09 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: rewrite Sticky Note javascript implementation to make it
  smaller and fix unescaped strings leading to XSS. Factor out UI blocking as
  done in Elastix interface into a single function.
  SVN Rev[6970]

* Wed Apr 08 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: consolidate all copies of Sticky Note styles into a single
  copy under libs/js/sticky_note.
  SVN Rev[6968]
- CHANGED: Framework: read Sticky Note status for blackmin theme.
  SVN Rev[6967]
- CHANGED: Framework: tweak z-index again to fix FullCalendar event appearing
  on top of the blackmin menu.
  SVN Rev[6966]

* Mon Apr 06 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: tweak z-index again to fix JCResizer control appearing
  on top of the blackmin menu.
  SVN Rev[6961]

* Sun Apr 05 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: port report grid and dialog look and feel from elastixneo
  to blackmin.
  SVN Rev[6960]
- CHANGED: Framework: add Elastix copyright footer at bottom of screen. Taken
  from elastixneo theme.
  SVN Rev[6958]

* Sat Apr 04 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: tweak z-index again to fix FullCalendar button appearing
  on top of the blackmin menu.
  SVN Rev[6957]
- CHANGED: Group Permissions: complete rewrite. Now the set of modules is shown
  using a grid to simulate a tree with expanding and collapsing branches.
  SVN Rev[6956]

* Fri Apr 03 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: remove middle man and use paloACL directly. The
  palosantoGroupPermission class just calls one paloACL method and does not add
  any useful abstraction.
  SVN Rev[6955]
- CHANGED: Framework: fix addons icon sticking out from toolbar in blackmin
  theme under IE8.
  SVN Rev[6954]
- CHANGED: Framework: fix hollow menus in blackmin theme arising from
  unsupported transparency in IE8.
  SVN Rev[6953]
- CHANGED: Framework: tidying up of blackmin theme navigation. The menu now has
  transparency (when supported by the browser). The long texts at the right were
  replaced by icons and now show CSS drop-down menus in the same layout as the
  elastixneo theme. Several functionalities were ported from elastixneo to
  blackmin, including module search and password change.
  SVN Rev[6952]

* Thu Apr 02 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: choose smoothness jQueryUI theme for tenant and blackmin.
  SVN Rev[6949]
- DELETED: Framework: remove unused template file in registration module.
- CHANGED: Framework: remove tag styling from forms. This styling is a no-op for
  most themes and removing it improve appearance in blackmin theme.
  SVN Rev[6948]

* Tue Mar 31 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: revert SVN commit 6941, and instead manually disable the
  Bootstrap button plugin.
  SVN Rev[6944]
- FIXED: Framework: removed applet theme overrides for tenant theme.
  SVN Rev[6943]
- FIXED: Framework: remove min-width from main module content in order to fit
  in smaller displays. Set back box-sizing property for content inside main
  module.
  SVN Rev[6942]
- FIXED: Framework: move Bootstrap before jQueryUI on tenant theme. This is
  necessary because the button plugin conflicts between Bootstrap and jQueryUI
  and jQueryUI must take precedence.
  SVN Rev[6941]
- FIXED: Framework: fix inclusion of hidden form variable that stores current
  Elastix module for tenant theme.
  SVN Rev[6940]

* Mon Mar 30 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: use jquery-ui-timepicker plugin instead of jscalendar.
  This plugin meshes better with jQueryUI themes. The old jscalendar library is
  not yet removed at this point.
  SVN Rev[6938]
- FIXED: Framework: fix reference to removed older copy of jQuery in tenant
  login screen.
  SVN Rev[6936]
- CHANGED: Framework: remove reference to unused plugin Raphaël in tenant theme.
  SVN Rev[6935]
- CHANGED: Framework: remove references to unused plugin Morris in tenant theme.
  SVN Rev[6934]
- FIXED: Framework: remove reference to removed older copy of jQueryUI in tenant
  theme.
  SVN Rev[6933]
- FIXED: Framework: replace call to nonexistent javascript function with
  hardcoded default for Elastix 2. This fixes issue of broken drop-down filters
  in tenant theme.
  SVN Rev[6932]
- DELETED: Framework: remove older copies of jQuery and jQueryUI in tenant
  theme.
  SVN Rev[6931]
- CHANGED: Framework: update jQuery to 1.11.2
- CHANGED: Framework: update jQueryUI to 1.11.4
- CHANGED: Framework: update colResizable to 1.5
  SVN Rev[6930]

* Sat Mar 28 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: framework - jQuery-1.11.2 migration - themes: fix incorrect use of
  attribute instead of property.
  SVN Rev[6921]
- CHANGED: framework - jQuery-1.11.2 migration - StickyNote: fix incorrect use
  of attribute instead of property.
  SVN Rev[6920]

* Wed Mar 18 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: remove obsolete nmap dependency. The only user of nmap is the network
  scan in the Endpoint Configurator.
  SVN Rev[6908]

* Wed Mar  4 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: factor out querying of system timezone into a separate function.
  Required for time_config module in elastix-system.
  SVN Rev[6892]
- CHANGED: check whether /etc/localtime is a symlink and use it as an additional
  way to find out the current timezone.
  SVN Rev[6891]

* Mon Mar  2 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
- FIXED: fix two issues with Smarty on CentOS 7. 1) Smarty 3.0 renamed
  get_template_vars to getTemplateVars and SmartyBC must be instantiated to get
  the old name 2) Smarty 3.0 now complains on unassigned template placeholders
  unless $smarty->error_reporting is set to emulate the old behavior.
  SVN Rev[6886]
- CHANGED: framework - elastix-framework does not directly require php-simplepie.
  However, it does require php-tcpdf.
  SVN Rev[6885]

* Mon Mar 2 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-5
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6884]

* Fri Feb 27 2015 Armando Chuto <armando@palosanto.com>
- DELETED: delete fpdf folder
  SVN Rev[6883]

* Fri Feb 27 2015 Armando Chuto <armando@palosanto.com>
- CHANGED: framework/palosantoPDF: change palosantoPDF.class.pdf for tcpdf
  library
  SVN Rev[6882]

* Wed Feb 25 2015 Armando Chuto <armando@palosanto.com>
- DELETED: delete jpgraph folder
  SVN Rev[6878]

* Wed Feb 25 2015 Armando Chuto <armando@palosanto.com>
- DELETED: delete phpmailer folder
  SVN Rev[6877]

* Wed Feb 25 2015 Armando Chuto <armando@palosanto.com>
- DELETED: delete smarty folder
  SVN Rev[6876]

* Wed Feb 25 2015 Armando Chuto <armando@palosanto.com>
- DELETED: delete magpierss folder
  SVN Rev[6874]

* Wed Feb 25 2015 Armando Chuto <armando@palosanto.com>
- CHANGED: Update icon
  SVN Rev[6873]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- UPDATE: /framework/setup/build/ added library to Elastix Framework
  SVN Rev[6865]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- ADDED: /framework/setup/build/ added library to Elastix Framework
  SVN Rev[6864]

* Mon Feb 23 2015 Armando Chuto <armando@palosanto.com>
- CHANGE: framework libs/paloSantoGraphImage.lib.php: change the route to
  usr/share/php of gpgraph library
  SVN Rev[6858]

* Thu Feb 19 2015 Luis Abarca <labarca@palosanto.com>
- ADDED: framework - themes/tennant: A partial migration of tennant theme of
  Elastix MT has been made.
  SVN Rev[6857]

* Fri Feb 13 2015 Luis Abarca <labarca@palosanto.com>
- CHANGED: framework - elastix-framework.spec: Put the correct date in the
  changelog of spec in order to create an rpm.
  SVN Rev[6847]

* Fri Feb 13 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-4
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6846]

* Fri Feb 13 2015 Alex Villacís Lasso <a_villacis@palosanto.com>
  Framework: force 770 mode for session directory.
  SVN Rev[6845]

* Thu Feb 12 2015 Luis Abarca <labarca@palosanto.com> 2.5.0-3
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6844]

* Thu Feb 12 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: partial cleanup of elastix-dbprocess. Use pidof instead
  of /sbin/service to check for mysqld in order to work around chroot issue in
  CentOS 7 install.
  SVN Rev[6843]

* Tue Jan 20 2015 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: switch from overwriting the CentOS repo to patching it
  as required. This prevents insertion or removal of repos that might not be
  appropriate for current CentOS distro. Required for CentOS 7.
  SVN Rev[6823]
- CHANGED: Framework: Attempt to enable mariadb in addition to mysqld for
  CentOS 7.
  SVN Rev[6821]
- FIXED: Framework: Disable assignment to session.save_path in php.conf
  so that setting in elastix.ini can take effect.
  SVN Rev[6820]

* Tue Dec 02 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-2
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.

* Tue Dec 02 2014 Luis Abarca <labarca@palosanto.com>
- CHANGED: branches - additionals/CentOS-Base.repo: Update an exception in
  package of the family 'php53' in order to correct an unexpected dependency by
  addon 'isurveyx'.
  SVN Rev[6783]

* Tue Nov 11 2014 Luis Abarca <labarca@palosanto.com> 2.5.0-1
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Version and Release in specfile.

* Wed Sep 24 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-19
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6747]

* Wed Sep 24 2014 Bruno Macias <bmacias@palosanto.com>
- FIXED: framework file misc.lib.php, function isStrongPassword was removed.
  This function conflits with function in addon callcenterPRO.
  SVN Rev[6746]

* Fri Sep 19 2014 Bruno Macias Velasco <bmacias@palosanto.com> 2.4.0-18
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6745]

* Fri Sep 19 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: refine previous commit by checking whether arrParams is
  an actual Array.
  SVN Rev[6742]
- FIXED: Framework: filter out properties inserted through Array mixins when
  building an AJAX request. Fixes breakage of Ember.js after SVN commit #6735.
  SVN Rev[6741]

* Thu Sep 18 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-17
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6738]

* Thu Sep 18 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: the request() helper function uses an incorrect URL encoding
  method that fails to escape special characters in string parameters. Fixed by
  relying instead on the well-tested jQuery handling of hash parameters in
  AJAX requests.
  SVN Rev[6735]

* Tue Sep 16 2014 Bruno Macias <bmacias@palosanto.com>
- UPDATED: framework registration module, translations was updated.
  SVN Rev[6729]

* Mon Sep 15 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-16
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6723]

* Fri Sep 12 2014 Bruno Macias <bmacias@palosanto.com>
- UPDATED: framework elastix, themes was updated because register
  popup now is menor height.
  SVN Rev[6722]

* Fri Sep 12 2014 Bruno Macias <bmacias@palosanto.com>
- UPDATED: framework elastix, registration module was updated.
  SVN Rev[6721]

* Tue Aug 19 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-15
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6692]

* Tue Aug 19 2014 Bruno Macias <bmacias@palosanto.com>
- CHANGED: framework - all themes, process registration has been changed, now
  elastix registration server requiere have a account in elastix cloud.
  SVN Rev[6691]

* Tue Aug 19 2014 Bruno Macias <bmacias@palosanto.com>
- CHANGED: framework - libs/misc.lib.php, process registration has been
  changed, now elastix registration server requiere have a account in elastix cloud.
  SVN Rev[6690]

* Tue Aug 19 2014 Bruno Macias <bmacias@palosanto.com>
- CHANGED: framework - javascript base.js, process registration has been
  changed, now elastix registration server requiere have a account in elastix cloud.
  SVN Rev[6688]

* Tue Aug 19 2014 Bruno Macias <bmacias@palosanto.com>
- CHANGED: framework - module register, process registration has been changed,
  now elastix registration server requiere have a account in elastix cloud.
  SVN Rev[6687]

* Wed Jun 04 2014 Luis Abarca <labarca@palosanto.com>
- CHANGED: modules - Classes, Libraries and Indexes: Because in the new php 5.3
  packages were depreciated many functions, the equivalent functions are
  updated in the files that use to have the menctioned functions.
  SVN Rev[6638]

* Mon May 26 2014 Bruno Macias <bmacias@palosanto.com>
- DELETED: extras - vtigerCRM, vtigerCRM software was removed on core elastix
  apps. Now is a addon.
  SVN Rev[6634]

* Wed Apr 09 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-14
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6579]

* Wed Apr 09 2014 Sergio Broncano <sbroncano@palosanto.com>
- FIXED: framework elastix 2, empty validation for document root now is denied.
  SVN Rev[6578]

* Wed Apr 09 2014 Sergio Broncano <sbroncano@palosanto.com>
- FIXED: framework, document root validation  changed by empty field.x
  SVN Rev[6577]

* Tue Apr 08 2014 Sergio Broncano <sbroncano@palosanto.com>
- FIXED: framework elastix, document root line now is not comment
  SVN Rev[6572]

* Mon Apr 07 2014 Sergio Broncano <sbroncano@palosanto.com>
- FIXED: framework elastix, global variable document root assigned as default
  /var/www/html when key in $_SERVER dont exists.
  SVN Rev[6571]

* Thu Apr 03 2014 Sergio Broncano <sbroncano@palosanto.com>
- UPDATED: framework elastix, now document root is automatic value from
  $_SERVER variable. This only web enviroment.
  SVN Rev[6567]

* Wed Mar 19 2014 Luis Abarca <labarca@palosanto.com>
- REMOVED: framework - elastix-framework.spec: The prereq: php-sqlite3 its no
  longer necesary because now the package php-pdo provides the dependencies
  that formerly provides php-sqlite3 package.
  SVN Rev[6550]

* Sat Mar 15 2014 Bruno Macias <bmacias@palosanto.com>
- FIXED: paloSantoForm.class.php, SELECT input when option value was cero
  number always compare is true for selected state option.
  SVN Rev[6540]

* Wed Mar 05 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: rest.php: accept ordinary cookie-based Elastix session in addition to
  the Basic HTTP authentication.
  SVN Rev[6500]

* Tue Feb 18 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: framework - add proper Content-Type header to JSON response when
  failing a rawmode request due to invalid session.
  SVN Rev[6482]

* Mon Feb 17 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: framework - disable xdebug before creating a SoapClient in order to
  work around xdebug generating fatal errors for SOAP exceptions on creation.
  SVN Rev[6478]

* Wed Feb 12 2014 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: framework - tweak blackmin theme to make module menu interaction
  easier.
  SVN Rev[6428]

* Mon Jan 27 2014 Luis Abarca <labarca@palosanto.com>
- CHANGED: framework,my_extension - index.html,paloSantoValidar.class.php: A
  correction in the name of variable numeric_rang has been made it.
  SVN Rev[6419]

* Mon Jan 27 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-13
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6416]

* Thu Jan 16 2014 Bruno Macias <bmacias@palosanto.com>
- ADDED: framework - paloSantoValidar.class.php, new type validation,
  numeric_rang.
  SVN Rev[6384]

* Thu Jan 16 2014 Bruno Macias <bmacias@palosanto.com>
- UPDATED: framework - libs/paloSantoForm.class.php - libs/js/base.js, Input
  type radio was improved for better interaction.
  SVN Rev[6383]

* Tue Jan 14 2014 Bruno Macias <bmacias@palosanto.com>
- UPDATED: framework - help/frameRight.php, support new multi language, en and
  es now supported
  SVN Rev[6381]

* Tue Jan 14 2014 Luis Abarca <labarca@palosanto.com> 2.4.0-12
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[6379]

* Mon Jan 13 2014 Jose Briones <jbriones@palosanto.com>
- UPDATED: Update to the changelog about the lang files
  SVN Rev[6378]

* Mon Jan 13 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Monitoring module: The lang files were updated due to a change in the
  name of the Monitoring module to Calls Recordings.
  SVN Rev[6376]

* Fri Jan 10 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Webmail, Flash Operator Panel, Openfire, vTigerCRM, Calling Cards:
  For each module listed here the english help file was renamed with the
  prefix "en_" and a spanish help file with the prefix "es_" was ADDED.
  Some unnecessary help related files were deleted.
  SVN Rev[6372]

* Fri Jan 10 2014 Jose Briones <jbriones@elastix.com>
- CHANGED: Groups, Group Permissions, Language, Themes: For each module listed
  here the english help file was renamed to en.hlp and a spanish help file called
  es.hlp was ADDED.
  SVN Rev[6366]

* Thu Dec 26 2013 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: replace deprecated jquery .live with .click. in
  elastixneo theme. Fixed.
  SVN Rev[6328]

* Thu Dec 26 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: patch colResizable to stop using deprecated $.browser .
  SVN Rev[6326]
- CHANGED: Framework: update jquery.blockUI.js to latest version 2.66
  SVN Rev[6325]
- CHANGED: Framework: replace deprecated jquery .live with .click. in elastixneo
  theme.
  SVN Rev[6324]

* Thu Dec 19 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: regenerate session ID on successful login. Fixes Elastix
  bug #1805.
  SVN Rev[6311]

* Mon Dec 16 2013 Rocio Mera <rmera@palosanto.com>
- CHANGED: BRANCHES/2.4.0 - FRAMEWORK/HTML: Was made change in file table.css
  that belogn to theme elastixneo. Was changed in selector
  div.neo-table-filter-controls property height: 28px to min-height: 28px. This
  was made to fixed display error that happened when many filters are selected
  SVN Rev[6294]

* Wed Nov 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: fetch Elastix package list in alphabetical order.
  SVN Rev[6084]

* Mon Oct 14 2013 Alex Villacís Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: trivial fix to two styles to use correct image reference.
  SVN Rev[6010]

* Sat Oct 05 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: prefer system-installed Smarty instead of bundled Smarty
  if available. This is a preparation for unbundling Smarty.
  SVN Rev[5989]
- DELETED: Framework: remove KendoUI javascript library. Nowhere in the code is
  this library used.
  SVN Rev[5985]
- DELETED: Framework: remove AeroWindow javascript library. Nowhere in the code
  is this library used anymore, and contains styles that conflict with jQueryUI.
  SVN Rev[5984]

* Thu Sep 05 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: for SQL parameters to queries, conversion of a numeric
  string into an integer should not be done for numeric strings that start with
  a zero. Fixes Elastix bug #1694.
  SVN Rev[5840]

* Wed Aug 21 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-11
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5782]

* Tue Aug 13 2013 Jose Briones <jbriones@palosanto.com>
- REMOVED: Module Downloads, Old help files were deleted
  SVN Rev[5728]

* Fri Aug 09 2013 Jose Briones <jbriones@palosanto.com>
- UPDATE: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5711]

* Fri Aug  9 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: switch PHP session directory from /tmp to
  /var/lib/php/session-asterisk in order to prevent sessions from being removed
  by systemd. Fixes Elastix bug #1661.
  SVN Rev[5647]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5606]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5598]

* Thu Aug 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file fr.lang.
  SVN Rev[5597]

* Wed Aug 07 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file en.lang.
  SVN Rev[5581]

* Wed Aug  7 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: add help link and help template to blackmin theme.
  SVN Rev[5578]
- CHANGED: Framework: add border-spacing: 0 to styles for old themes so that
  jQueryUI dialogs and widgets are displayed correctly.
  SVN Rev[5573]

* Wed Aug 07 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: ADDED spanish translation of some words in main menus.
  SVN Rev[5576]

 Tue Aug 06 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file es.lang.
  SVN Rev[5568]

* Tue Aug 06 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Correction of some mistakes in the translation file es.lang.
  SVN Rev[5567]

* Wed Jul 31 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module themes_system. Correction of some mistakes in the translation
  files.
  SVN Rev[5473]

* Tue Jul 30 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Add a Conflicts directive to prevent installation along with
  elastix-developer <= 2.3.0-4 . Part of fix for Elastix bug #1643.
  SVN Rev[5453]

* Fri Jul 26 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Framework lang. Correction of some mistakes in the translation
  files.
  SVN Rev[5431]

* Fri Jul 26 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module language. Correction of some mistakes in the translation
  files.
  SVN Rev[5427]

* Fri Jul 26 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: Module grouplist. Correction of some mistakes in the translation
  files.
  SVN Rev[5421]

* Thu Jul 18 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-10
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5349]

* Tue Jul 16 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: fix /etc/init.d/generic-cloexec script to be aware of
  systemctl and run it instead of blindly running a /etc/init.d/ script that
  might not exist in a systemctl system. Fixes Elastix bug #1632.
  SVN Rev[5317]

* Mon Jul 15 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: reorganize the API provided by paloSantoGraphImage in
  order to separate the graph stroke based on a callback result, from the class
  loading and method invoking required to generate said callback result. This
  enables modules to build graph results inside their own methods without having
  to implement the specific method callbacks, and most importantly, without
  having to place the function inside a class that resides in any specific path.
  This is required for the dashboard applet reorganization.
  SVN Rev[5310]

* Tue Jun 25 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: move several CSS files out of the ui-lightness jQueryUI
  theme into a custom directory widgetcss. These CSS files are not part of the
  ui-lightness theme, but styles used by elastixneo widgets. This allows
  switching of jQueryUI themes without losing widget functionality.
  SVN Rev[5129]
- CHANGED: Framework: choose a jQueryUI theme based on the current theme. The
  association of themes is currently hardcoded for now.
  SVN Rev[5126]
- CHANGED: Framework: add !DOCTYPE declaration to all themes that missed it in
  order to normalize behavior of jQueryUI widgets.
  SVN Rev[5125]
- FIXED: Framework: fix blackmin style so padding is not incorrectly applied to
  buttons in Calendar menu.
  SVN Rev[5124]

* Fri Jun 21 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-9
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5117]

* Mon Jun 17 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: rewrite menu selection for blackmin theme using CSS
  drop-down menus to improve navigation.
  SVN Rev[5105]

* Mon Jun 17 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-8
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5101]

* Fri Jun 14 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Group Permission: use strpos instead of regexp to search for
  substring. Pointed out by Fortify report.
  SVN Rev[5098]

* Thu Jun 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: minimum year for copyright is 2013, so force it if date()
  reports anything lower.
  SVN Rev[5094]

* Wed Jun 12 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: force focus on input_user textbox for blackmin theme as done
  with the other themes.
  SVN Rev[5086]

* Tue Jun 11 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-7
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile.
  SVN Rev[5080]

* Mon Jun 10 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: remove insecure implementation of requestURL()
  SVN Rev[5079]

* Sat Jun 08 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Group Permission: use _tr instead of arrLang, use load_language_module().
  SVN Rev[5071]
- CHANGED: Registration: use _tr instead of arrLang, use load_language_module().
  SVN Rev[5070]
- CHANGED: Themes System: use _tr instead of arrLang, use load_language_module().
  SVN Rev[5069]
- CHANGED: Group List: use _tr instead of arrLang, use load_language_module().
  SVN Rev[5068]
- CHANGED: Language:  use _tr instead of arrLang, use load_language_module().
  SVN Rev[5067]
- CHANGED: Framework: use _tr instead of arrLang in paloSantoValidar and
  paloSantoGraphImage.lib.php.
  SVN Rev[5066]
- CHANGED: Framework: backport changes to paloSantoForm from trunk to 2.4
  branch. Remove references to arrLang and use _tr instead.
  SVN Rev[5065]

* Thu Jun 06 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: lay foundation to remove some boilerplate from all Elastix
  modules:
  - All modules need to load their i18n strings. Currently every module either
    calls load_language_module() or uses require() directly to load the PHP
    strings. The framework will now load the i18n strings for the module.
    Duplicate loading of strings is harmless, so old modules can remain as-is.
  - All modules need to load their default.conf.php file. Just like the language
    files, the framework now loads the configuration for the module.
  - All modules need to get the custom template directory for forms and such.
    A framework function, getTemplatesDirModule(), has been created for this.
  - Finally, many modules use the convention that class XYZ is defined in the
    file XYZ.class.php. The framework can now support this convention to
    implement autoloading, so that modules do not need to require() every single
    class file anymore.
  SVN Rev[5060]

* Fri May 31 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: re-introduce saving last viewed module in a session
  variable. It turns out that embedded freepbx really needs this hack. Now with
  a comment explaining why the hack is necessary.
  SVN Rev[5049]

* Thu May 30 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-6
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile
  SVN Rev[5047]

* Tue May 28 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: require paloSantoPDF.class.php inside the only method that
  actually requires its class paloPDF, rather than every time paloSantoGrid is
  required.
  SVN Rev[5034]
- CHANGED: Framework: introduce new setting 'uelastix'. This flag will be set
  for uElastix images and absent/unset on ordinary systems. When set, the
  framework will enable a number of optimizations to improve performance in the
  ARM environment. Currently setting this flag disables tracking of menu history
  and enables caching of authorized modules in the session variable
  'elastix_user_permission'.
  SVN Rev[5033]

* Mon May 27 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-5
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Bump Release in specfile
  SVN Rev[5019]

* Mon May 27 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: do not use HTTP_HOST to build redirects and other URLs in
  REST services, as it is attacker-controlled. Pointed out by Fortify report.
  SVN Rev[5010]

* Tue May 21 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: do not echo back the invalid e-mail address to prevent XSS.
  Pointed out by Fortify report.
  SVN Rev[5007]
- FIXED: Framework: escape id_nodo, name_nodo in main help system. Pointed out
  by Fortify report.
  SVN Rev[5005]
- CHANGED: Framework: replace unserialize with implode/explode in help system.
  SVN Rev[4995]

* Mon May 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: remove XSS bug on module name in help system.
  SVN Rev[4927]
- DELETED: Framework: remove several unused files and directories of examples
  and documentation for various libraries shipped with Elastix Framework.
  SVN Rev[4926]

* Mon May 13 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-4
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4917]

* Fri May 10 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Themes: check that selected theme is a valid name that exists in the
  themes directory.
  SVN Rev[4911]

* Thu May 09 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: change registration text to point out that registration
  is now required for installation of all addons through the web interface.
  SVN Rev[4909]

* Tue May 06 2013 José Briones <jbriones@palosanto.com>
- UPDATED: slashdot theme help.css help style was updated.
  SVN Rev[4896]
- UPDATED: giox theme help.css help style was updated.
  SVN Rev[4895]
- UPDATED: elastixwine theme help.css help style was updated.
  SVN Rev[4894]
- UPDATED: elastixwave theme help.css help style was updated.
  SVN Rev[4893]
- UPDATED: elastixblue theme help.css help style was updated.
  SVN Rev[4892]
- UPDATED: default theme help.css help style was updated.
  SVN Rev[4891]
- UPDATED: blackmin theme help.css help style was updated.
  SVN Rev[4890]
- UPDATED: al help.css help style was updated.
  SVN Rev[4889]
- UPDATED: elastixneo help.css help style was updated.
  SVN Rev[4888]
- UPDATED: elastixneo help.css help style was updated.
  SVN Rev[4887]

* Thu May 02 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: use strpos instead of dynamic regexp in module search
  SVN Rev[4883]

* Thu May 02 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-3
- FIXED: framework - Build/elastix-framework.spec: Changed release in specfile.
  SVN Rev[4875]

* Mon Apr 29 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: reimplement several widget helper methods to receive the
  database connection used for authentication instead of opening a duplicate.
  SVN Rev[4873]
- CHANGED: Framework: reimplement putMenuAsBookmark to receive additional
  parameters of database connections, instead of opening duplicates.
  SVN Rev[4872]
- CHANGED: Framework: reorganization of menu management and theme encapsulation:
  - The implementation of paloSantoNavigation has been rewritten and
    considerably simplified. The previous implementation maintained the menu
    items as a simple list with parents weakly linked through the IdParent
    property, and every query of the children of such items required a walk of
    the entire node list. This walk, as well as the walk required to choose the
    module to display given the menu item, were open-coded through the
    implementation and involved several node copies. The new implementation
    builds references between parents and children in the constructor, and then
    relies mainly on these references to select the module to display. This
    allows the menu walk to be implemented once, to be shorter, and the overall
    code to be considerably simplified.
  - The menu walking code does not assume a maximum menu depth. This removes
    several kludges (mainly in showContent) that stemmed from the previous
    implementation assuming a two-level menu and then hurriedly adapted to
    support three-level menus.
  - The menu node assignment has been unified. Since the nodes have children
    lists and the HasChild property is actively maintained, themes no longer
    require a separate menu list for second-level menu decorations. This affects
    the elastixneo and elastixwave themes.
  - Second-level popup menu tables have been pushed into the themes where they
    belong. This affects the following themes: al elastixwine giox slashdot.
  - Theme-specific menu manipulation (elastixneo) has been abstracted out of
    paloSantoNavigation and into a new per-theme library inside themesetup.php.
  - Several widget-rendering operations that require database access have also
    been abstracted out of paloSantoNavigation and index.php. Since the only
    theme that makes use of these widgets is elastixneo, the calls have been
    moved into its themesetup.php file.
  - The modified index.php no longer assigns the selected menu item to a session
    variable. This may break some addons that depend on this.
  SVN Rev[4871]

* Sun Apr 28 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: move implementation of loadShortcut out of
  paloSantoNavigation and into misc.lib.php, thus making paloSantoNavigation
  almost identical between 2.4.0 and trunk.
  SNV Rev[4870]
- CHANGED: Framework: push out bookmark/history shortcut layout into a separate
  template, moving this layout concern out of paloSantoNavigation.
  SVN Rev[4869]

* Fri Apr 26 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: move remainder of requests to elastixutils module. Handle
  elastixutils before entering paloSantoNavigation to prevent assignment to
  session variable.
  SVN Rev[4868]
- CHANGED: Framework: the following requests now send the current module ID and
  attempt to route to the elastixutils module: addBookmark, deleteBookmark,
  save_sticky_note, get_sticky_note, saveNeoToggleTab.
  SVN Rev[4867]
- FIXED: Framework: many legacy themes displayed help link incorrectly for
  third level modules. Fixed.
- ADDED: Framework: add hidden input tag elastix_framework_module_id that
  contains the ID of the current module displayed.
  SVN Rev[4866]
- FIXED: Framework: main theme needs to be explicitly queried, which broke help
  navigation. Fixed. Also load default timezone on help scripts.
  SVN Rev[4865]
- FIXED: Framework: giox theme displayed help link incorrectly for third-level
  modules. Fixed.
  SVN Rev[4864]
- CHANGED: Framework: move changeColorMenu functionality to elastixutils.
  SVN Rev[4863]
- CHANGED: Framework: move search_module functionality to elastixutils.
  SVN Rev[4862]
- CHANGED: Framework: unify paloSantoNavigation implementations as much as
  possible between 2.4.0 and trunk for easier analysis.
  SVN Rev[4861]

* Thu Apr 25 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: move changePasswordElastix functionality to elastixutils.
  SVN Rev[4859]
- ADDED: Framework: introduce hidden module _issabelutils. This module will
  contain various utilities for widgets in the Elastix Web GUI. This allows
  a cleanup of index.php, by removing functionality that does not belong in
  the router and authorization code. As a proof of concept, the package version
  query was moved to _issabelutils. In the process, the query was reimplemented
  to issue a single rpm command instead of multiple ones, and achieving a 50%
  speedup. This also makes /usr/bin/versionPaquetes.sh obsolete so it is now
  removed.
  SVN Rev[4858]

* Wed Apr 24 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Applet Admin: use supplied module_name instead of getting variable
  from session. The package elastix-framework needs a Conflicts with previous
  versions of elastix-system.
  SVN Rev[4857]
- CHANGED: Framework: remove useless developerMode variable
  SVN Rev[4856]
- CHANGED: Framework: make some variables of paloSantoNavigation private.
  SVN Rev[4855]
- CHANGED: Framework: make some methods of paloSantoNavigation private.
  SVN Rev[4854]

* Fri Apr 19 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: (trivial) Make input widgets for blackmin rounded like
  they are for elastixneo.
- CHANGED: Framework: Display no-data placeholder on list template for blackmin.
  SVN Rev[4851]

* Mon Apr 15 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-2
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4836]

* Mon Apr 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: calling_cards help section was updated.
  SVN Rev[4799]

* Mon Apr 08 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: calling_cards help section was updated.
  SVN Rev[4798]

* Thu Apr 04 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: im help section was updated.
  SVN Rev[4795]

* Thu Apr 04 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: openfire help section was updated.
  SVN Rev[4794]

* Mon Apr 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: instantmessaging module help section was updated.
  SVN Rev[4778]

* Mon Apr 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: sphones module help section was updated.
  SVN Rev[4777]

* Mon Apr 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: faxutils module help section was updated.
  SVN Rev[4776]

* Mon Apr 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: sphones module help section was updated.
  SVN Rev[4775]

* Mon Apr 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: sphones module help section was updated.
  SVN Rev[4774]

* Mon Apr 01 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: a2b help section was updated.
  SVN Rev[4772]

* Thu Feb 28 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: vtiger help section was updated.
  SVN Rev[4771]

* Thu Feb 28 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: openfire, help section was updated.
  SVN Rev[4747]

* Thu Feb 28 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: openfire module, help section was updated.
  SVN Rev[4746]

* Thu Feb 28 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: fop module, help section was updated.
  SVN Rev[4745]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: webmail module, help section was updated.
  SVN Rev[4710]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: themes_system module, help section was updated.
  SVN Rev[4703]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: language module, help section was updated.
  SVN Rev[4701]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: grouplist module, help section was updated.
  SVN Rev[4695]

* Tue Feb 19 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: group_permission module, help section was updated.
  SVN Rev[4694]

* Mon Feb 18 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: help css file, help style file was updated.
  SVN Rev[4685]

* Mon Feb 18 2013 Jose Briones <jbriones@palosanto.com>
- UPDATED: help css file, help style file was updated.
  SVN Rev[4684]

* Wed Feb 13 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: framework: allow registration process to accept arbitrary strings for
  Contact Name, Company, City. Fixes Elastix bug #1476.
  SVN Rev[4665]

* Tue Jan 29 2013 Bruno Macias <bmacias@palosanto.com>
- UPDATED: framework - themes, elastix theme was updated
  SVN Rev[4650]

* Tue Jan 29 2013 Luis Abarca <labarca@palosanto.com> 2.4.0-1
- FIXED: framework - Build/elastix-framework.spec: Changed Version and Release in
  specfile according to the current branch.
  SVN Rev[4633]

* Mon Jan 28 2013 Luis Abarca <labarca@palosanto.com> 2.3.0-17
- FIXED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4623]

* Mon Jan 28 2013 Bruno Macias <bmacias@palosanto.com>
- FIXED: framework - elastixneo theme, slogan elastix, was updated because
  there was a mistake.
  SVN Rev[4622]

* Thu Jan 24 2013 German Macas <gmacas@palosanto.com>
- FIXED: modules: group_permission: Fixed columns width in grid in all themes
  SVN Rev[4618]

* Thu Jan 24 2013 German Macas <gmacas@palosanto.com>
- FIXED: modules: group_permission: Fixed columns width in grid
  SVN Rev[4617]

* Fri Jan 11 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: framework: elastixneo theme : fix syntax for javascript object
  rejected by IE6.
  SVN Rev[4578]

* Fri Jan 11 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: readout of FreePBX database password incorrectly returned an
  array instead of a scalar. Fixed.
  SVN Rev[4575]

* Fri Jan 04 2013 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: framework: improve readability on blackmin theme
  SVN Rev[4546]

* Mon Dec 24 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: framework: update internal jQueryUI to 1.8.24, fixes Draggable
  incompatibilities with updated jQuery.
  SVN Rev[4530]

* Wed Dec 19 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: framework: (trivial) remove extra newline in jslib/css lists.
  SVN Rev[4523]
- CHANGED: framework: update internal jQuery to 1.8.3.
  SVN Rev[4522]

* Thu Dec 04 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-16
- FIXED: framework - Build/elastix-framework.spec: Put in correct order the
  procedure of delete a group of dirs in the spec.
  SVN Rev[4498]

* Fri Nov 30 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- Framework: round up three duplicates of smarty creation into a single method,
  paves the way to moving compiled template directory off the wwwroot.
  SVN Rev[4488]

* Fri Nov 30 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: no module is using var/backups anymore. Remove this
  directory.
  SVN Rev[4487]

* Fri Nov 30 2012 Bruno Macias <bmacias@palosanto.com>
- FIXED: framework elastix, file base.js in the function ShowModalPopUP, was
  improved usability.
  SVN Rev[4483]

* Tue Nov 13 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- Framework: limit scope of javascript keypress handler to just the input boxes
  on the elastixneo theme grid views. Original fix by Bruno Macias. Fixes
  Elastix bug #1365.
  SVN Rev[4431]

* Tue Nov 13 2012 Bruno Macias <bmacias@palosanto.com>
- FIXED: elastix framework - file base.js, fixed bug when applied enter button
  in same  modules, the focus field is not correct, new validation was wrote
  SVN Rev[4429]

* Tue Nov 13 2012 Bruno Macias <bmacias@palosanto.com>
- CHANGED: elastix framework, file menu.tpl of elastixneo theme, logout link
  was updated
  SVN Rev[4426]

* Mon Nov 12 2012 Bruno Macias <bmacias@palosanto.com>
- FIXED: elastix framework - file base.js, fixed bug when applied enter button
  in module pin set freepbx, it deleted a register. Extra validation
  on function keyPressed was wrote.
  SVN Rev[4425]

* Thu Oct 18 2012 Luis Abarca <labarca@palosanto.com>
- FIXED: framework - Build/elastix-framework.spec: Put in correct order the
  procedure of delete a group of dirs in the spec.
  SVN Rev[4367]

* Thu Oct 18 2012 Luis Abarca <labarca@palosanto.com>
- FIXED: framework - Build/elastix-framework.spec: The procedure of delete a
  group of dirs in the spec its now working.
  SVN Rev[4364]

* Wed Oct 17 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- Framework: fix elastix-dbprocess to remove the temporary file 1_sqlFile.sql
  whenever it is successfully committed to a database or copied to firstboot.
  Part of the fix for Elastix bug #1398.
  SVN Rev[4355]

* Wed Oct 17 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-15
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile
  SVN Rev[4349]

* Wed Oct 17 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- Framework,Modules: remove temporary file preversion_MODULE.info under
  /usr/share/elastix/module_installer/MODULE_VERSION/ which otherwise prevents
  proper cleanup of /usr/share/elastix/module_installer/MODULE_VERSION/ on
  RPM update. Part of the fix for Elastix bug #1398.
- Framework,Modules: switch as many files and directories as possible under
  /var/www/html to root.root instead of asterisk.asterisk. Partial fix for
  Elastix bug #1399.
- Framework,Modules: clean up specfiles by removing directories under
  /usr/share/elastix/module_installer/MODULE_VERSION/setup/ that wind up empty
  because all of their files get moved to other places.
  SVN Rev[4347]

* Tue Oct 16 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: remove the entry in /etc/sudoers for the command
  /usr/bin/yum. Since commit 4342 the only user of sudo yum has been converted
  to use a privileged script.
  SVN Rev[4343]

* Wed Oct 10 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: remove hardware_detector from /etc/sudoers. The hardware
  detection module now invokes it as a privileged script through elastix-helper.
  SVN Rev[4339]
- CHANGED: Framework: at long last, remove the entries in /etc/sudoers for the
  commands: /bin/touch, /bin/chmod, /bin/chown, /sbin/init. With the migration
  to privileged scripts completed, these commands are no longer needed (and
  there was much rejoicing).
  SVN Rev[4336]

* Tue Oct  9 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: replace implementation of paloConfig::privado_chown with
  a version that does not invoke sudo chown. The last user of the method
  paloConfig::escribir_configuracion is search_ami_admin_pwd which runs in root
  context at RPM install time.
  SVN Rev[4335]

* Mon Sep 17 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: Since commit 4216, palosantoEmailAdmin no longer requires
  sudo access to postmap and saslpasswd2, so remove it.
  SVN Rev[4217]

* Wed Sep 12 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: Since commits 4199-4200, palosantoEmailAdmin no longer
  requires sudo access to postfix, so remove it.
- CHANGED: Framework: Conflicts: elastix-email_admin <= 2.3.0-8
  SVN Rev[4201]

* Tue Sep 11 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Framework: remove commented-out dead code in paloSantoConfig
- CHANGED: Framework: remove two methods in paloSantoConfig that are defined but
  never used in Elastix. This removes two potential uses of sudo chown.
  SVN Rev[4195]

* Mon Sep 03 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-14
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile
  SVN Rev[4176]

* Fri Aug 31 2012 German Macas <gmacas@palosanto.com>
- Fixed javascript error in index.php
  SVN Rev[4165]

* Fri Aug 24 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-13
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4126]

* Mon Aug 06 2012 German Macas <gmacas@palosanto.com>
- Fixed bug when logout from A2billing, before didn't work in all a2b's menu.
  SVN Rev[4093]

* Fri Aug 03 2012 German Macas <gmacas@palosanto.com>
- To remain embebed a2billing in elastix when logout.
  SVN Rev[4089]

* Fri Aug 03 2012 German Macas <gmacas@palosanto.com>
- Fixed bug 0001318, bug 0001338: fixed in Asterisk File Editor return last
  query in Back link, fixed Popups, position and design, add in Dashboard
  Applet Admin option to check all.
  SVN Rev[4088]

* Wed Jun 27 2012 Luis Abarca <labarca@palosanto.com> 2.3.0-12
- CHANGED: framework - Build/elastix-framework.spec: update specfile with latest
  SVN history. Changed release in specfile.
  SVN Rev[4024]

* Tue Jun 12 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: use SERVER_ADDR instead of ifconfig for querying IP of
  request in iframe module display. SVN Rev[3994]
- FIXED: Framework: use ip addr show instead of ifconfig to get assigned IP
  address. Required for compatibility with Fedora 17. SVN Rev[3991]

* Mon Jun 11 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: replace TERM=dumb with TERM=xterm in elastix-helper
  environment, prevents error messages from appearing on stderr. SVN Rev[3988]
- FIXED: Framework: teach version display to deal with some missing packages
  SVN Rev[3986]

* Fri Jun 8 2012 Alberto Santos <asantos87@palosanto.com>
- ADDED: framework databases, added a new database called elastix.db.
  SVN Rev[3982]

* Thu Jun 7 2012 Alberto Santos <asantos87@palosanto.com>
- NEW: framework class that applies the method of Long Poll.
  SVN Rev[3970]

* Wed Jun 06 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- FIXED: Framework: probe CPU load the proper way, by reading /proc/stat twice
  and subtracting values. Fixes Elastix bug #1043.
- FIXED: Framework: use Processor entry in /proc/cpuinfo if present. Allows
  presenting a decent "CPU" entry in dashboard on ARM systems.
  SVN Rev[3963]

* Thu May 31 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Only overwrite /etc/yum.repos.d/CentOS-Base.repo if this file already
  exists. Prevents creation of nonfunctional repository in Fedora 17.
  SVN Rev[3951]

* Mon May 28 2012 Alex Villacis Lasso <a_villacis@palosanto.com> 2.3.0-11
- FIXED: Framework/PalosantoGrid: remove XSS vulnerability in filter
  value display on elastixneo theme SVN Rev[3941]

* Mon May 7 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-10
- CHANGED: theme - blackmin: fixed popup's in blackmin theme
  SVN Rev[3929]
- UPDATED: Framework - Build: update specfile with
  SVN Rev[3922]

* Wed May 2 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-9
- CHANGED: framework - help: Revert commit 3913.
  SVN Rev[3919]
- FIXED: Framework - Popups: Fixed bug in popup framework in all themes.
  SVN Rev[3917]
- CHANGED: modules - registration: Change popup form of version
  SVN Rev[3913]

* Fri Apr 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-8
- CHANGED: Framework - Build/elastix-framework.spec: Changed release in specfile

* Fri Apr 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-7
- CHANGED: Framework - Build/elastix-framework.spec: update specfile with
  latest SVN history. Changed release in specfile

* Tue Apr 24 2012 Alex Villacis Lasso <a_villacis@palosanto.com>
- CHANGED: Add proper conflicts for kernel-module-*-xen as well as ordinary
  kernel-module-* as neither are supported anymore.
- CHANGED: Framework: attempt to pick any educated guess for the default
  timezone before hitting the filesystem.
- FIXED: Framework: PHP 5.3+ requires the timezone to be explicitly set. Load
  timezone from /etc/sysconfig/clock if it exists.
- FIXED: Framework: Workaround for PHP bug #44639 in PHP 5.3.x and later.
  Instead of executing the PDO database statement directly, the parameters are
  bound with a PDO datatype derived from the underlying PHP data type.
- FIXED: Framework: do not use reserved superglobal names as parameters for a
  function.
- FIXED: framework - base.js: when press enter event in textarea html not work
  it.
- FIXED: New validation type when it is empty.

* Fri Mar 30 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-6
- CHANGED: Framework - Themes/blackmin/index.tpl: Added id='message_error'
  in div that show the message on top of the window
  SVN Rev[3810]

* Fri Mar 30 2012 Bruno Macias <bmacias@palosanto.com> 2.3.0-5
- CHANGED: In spec file, changed prereq elastix-firstboot >= 2.3.0-4
- NEW: framework - sticky-note, new implemetation auto popup.
  SVN Rev[3804].
- FIXED: framework settings DB: se quita SQL redundante de alter table,
  esto causaba un error leve en la instalación del framework.
  SVN Rev[3796].
- CHANGED: Framework - lang: Added traduction in es.lang and en.lang for
  applied filters.
  SVN Rev[3792]

* Tue Mar 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-4
- CHANGED: In spec file, changed prereq elastix-firstboot >= 2.3.0-2
- CHANGED: Framework - Modules/registration: Changed the way that appeared
  the registration window
  SVN Rev[3790]
- CHANGED: Framework - libs/js/base.js: Changed the way that appeared
  the popup
  SVN Rev[3789]
- CHANGED: framework - themes: changed height of register popup
  SVN Rev[3787]

* Tue Mar 27 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-3
- ADDED: framework - images: nuevas imagenes para el manejo y presentación de
  modal
  SVN Rev[3786]
- CHANGED: Themes - All: Changed in files index.tpl and styles.css to better
  the appearance of popup
  SVN Rev[3785]
- FIXED: framework - download grid, se corrige posición del div para descarga
  se desaparecia al pasar justo en el borde inferior.
  SVN Rev[3782]
- CHANGED: themes - elastixneo/styles.css: Improved some positions in the grids
  as well as colors and margins.
  SVN Rev[3764]
- FIXED: themes - elastixneo/_common/_list.tpl: Now pressing an image on the
  grid, that image perform the specified action.
  SVN Rev[3763]
- CHANGED: elastix-menutranslate, changed the methodology for the reception of
  the file with the translations, now it must be a php file with the menus
  translations
  SVN Rev[3762]
- NEW: additionals script elastix-menutranslate, this script handle the
  insertion or update for menus translations
  SVN Rev[3755]
- CHANGED: framework index.php, added the support for menus translations
  SVN Rev[3754]
- CHANGED: Framework - themes/elastixneo changed to better the function
  addFilterControl, it doesnt't appear the 'X' option in whose filters that are
  always active
  SVN Rev[3753]
- CHANGED: libs - paloSantoGrid.class.php changed to better the function
  addFilterControl, it doesnt't appear the 'X' option in whose filters that are
  always active
  SVN Rev[3752]
- CHANGED: framework - themes/elastixneo/styles.css: The color of some missing
  text areas now are the same.
  SVN Rev[3749]
- NEW: added new files to handle the Elastix rest web services
  SVN Rev[3743]
- ADDED: Additionals: add specfile for lcdelastix.
  SVN Rev[3742]

* Fri Mar 09 2012 Alberto Santos <asantos@palosanto.com> 2.3.0-2
- CHANGED: In spec file, changed prereq elastix-firstboot >= 2.3.0-1
- UPDATED: framework - es lang: Se define español de la frase
  "Filter applied"
  SVN Rev[3731]
- CHANGED: Framework: raise memory limit for PHP to 1 gigabyte,
  to enable processing of large number of extensions.
  SVN Rev[3725]

* Wed Mar 07 2012 Rocio Mera <rmera@palosanto.com> 2.3.0-1
- UPDATED: framework - GRID: Mejoras para el manejo de grid, respecto a los
  controles de filtros.
  SVN Rev[3723]
- UPDATED: framework - themes: Se define como text-decoration:none a la lista
  de exportación
  SVN Rev[3716]
- UPDATED: framework - GRID: Mejoras para el manejo de grid, respecto a los
  controles de filtros.
  SVN Rev[3710]
- UPDATED: framework - GRID: Mejoras para el manejo de grid, respecto a los
  controles de filtros.
  SVN Rev[3708]
- UPDATED: framework - GRID: Mejoras para el manejo de grid, respecto a los
  controles de filtros.
  SVN Rev[3707]
- CHANGED: group_permission index.php add control to applied filters
  SVN Rev[3697]
- FIXED: framework - misc.lib.php: se quita print_r dentro de la función
  getParameter, no se lo quito por error. introduce by Bruno Macias.
  SVN Rev[3690]
- NEW: framework - GRID: Nuevo soporte para control de filtros en los reportes.
  Ahora se puede visualizar que filtro está aplicado y tiene una X para
  removerlo facilmente.
  SVN Rev[3689]
- NEW: framework - GRID: Nuevo soporte para control de filtros en los reportes.
  Ahora se puede visualizar que filtro está aplicado y tiene una X para
  removerlo facilmente.
  SVN Rev[3688]
- UPDATED: framework - jquery: Updated version jquery 1.5.1 to 1.7.1
  SVN Rev[3681]
- UPDATED: framework - paloSantoGrid.class.php: Se da soporte para poder
  agregar acciones a la grilla según las acciones que sean necesarias, para
  esto se modificó todos los temas de elastix.
  SVN Rev[3676]-SVN Rev[3675]-SVN Rev[3674]
- CHANGED: little change in file *.tpl to better the appearance the options
  inside the filter
  SVN Rev[3640]
- CHANGED: framework - themes/elastixneo: Some indication messages now can be
  seen complete.
  SVN Rev[3632]

* Wed Feb 1 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-30
- CHANGED: framework - themes/elastixneo: Some colors in the style
  are changed for visibility reasons. SVN Rev[3614].
- FIXED: Framework - Themes/elastixneo: Download Button in the grid doesn't function correctly.
  -- Es --
  M    elastixneo/_common/_list.tpl
  M    elastixneo/styles.css
  M    elastixneo/table.css. SVN Rev[3612].

* Mon Jan 30 2012 Alberto Santos <asantos@palosanto.com> 2.2.0-29
- NEW: framework - modules/grouplist: Se mejora la implementación
  para obtener datos paginados
  SVN Rev[3607]
- NEW: framework - paloSantoACL.class.php: Nuevas funciones para
  obtener datos paginados de los módulos de userlist y grouplist
  SVN Rev[3606]
- CHANGED: Now exist the option 'More Option'
  SVN Rev[3605]
- CHANGED: little change in the view of new grid
  SVN Rev[3600]
- Fixed: framework - lang Delete a enter at the end of en.lang and
  es.lang file
  SVN Rev[3599]
- UPDATED: Framework -js: Se actualizo el archivo colResizable.js
  para mejorar el aspecto de la grilla en el tema elastixneo.
  SVN Rev[3598]
- CHANGED: framework - lang Add traductions in english and spanish
  to words 'Hide Filter', 'Show Filter', 'More Options'
  SVN Rev[3592]
- CHANGED: framework - lang Se aumento traduccion en ingles y español
  de la palabra Warning
  SVN Rev[3589]

* Sat Jan 28 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-28
- ADDED: framework - images: Se agregar nueva imagen
  icon_arrowup2.png para el filtro de las grilla. SVN Rev[3584].
- CHANGED: framework - elastixneo: Mejoras en el diseño del
  mensaje de error, y de algunos cambios menores en la vista
  de elastixneo. SVN Rev[3583].
- UPDATED: framework - paloSantoGrid.class.php: Mejoras en
  el proceso de la paginación por paginas. SVN REV[3582].
- CHANGED: framework - trunk/html/themes/*/_common/_list.tpl:
  Se modifico el archivo _list.tpl para compatibilidad con
  la nueva grilla. SVN Rev[3582].
- CHANGED: framework - trunk/html/themes/*/_common/_list.tpl:
  Se modifico el archivo _list.tpl para compatibilidad con la
  nueva grilla. SVN Rev[3581].
- CHANGED: Framework - Themes: Changes in all themes for change
  the column title color in the grid of Summary module.
  SVN Rev[3578].
- CHANGED: mframework- cimages SSe cambia imagenes de iconos
  en los modulos del framework. SVN Rev[3573].
- CHANGED: framework - themes/elastixneo: Cambio menor en
  id del formulario del tpl _list.tpl. Rv. [3565]
- NEW: framework - themes/elastixneo: Se mejora el diseño
  de tablas - grillas para reportes. SVN Revision [3561]
- NEW: framework - images: Se agregan nuevas imagenes para
  el nuevo look de las tablas - grillas de reportes.
  SVN Rev [3560]
- NEW: framework - kendo,colResizable: Se añade nuevas
  librerias de javascript kendo y jquery kendo,colResizable.
  SVN Rev [3559]
- UPDATED: framework - paloSantoGrid.class.php: pendiente.
  SVN Rev[3557]
- CHANGED: framework - trunk/html/themes/*/_common/_menu.tpl:
  Se modifico el archivo _menu.tpl en todos los temas excepto
  elastixneo para que tenga soporte con la nueva grilla.
  SVN Rev[3555]
- UPDATED: framework - trunk/html/lang/: Se agrego algunas
  traducciones a ingles y español para la nueva grilla en el
  tema elastixneo. SVN Rev [3553].
- NEW: framework trunk/html/themes/elastixneo/_common/_menu.tpl:
  Se añadio una imagen a topbar del tema elastix-neo para hacer
  un acceso rapido a el modulo addons, se agrego la imagen
  toolbar_addons a
  elastix/framework/trunk/html/themes/elastixneo/images/ .
  SVN Rev[3552]
- CHANGED: Modules - System: Support for the new grid layout.
  SVN Rev[3544]
- FIXED: Elastix Framework: generator of WSDL schema must
  specify a namespace attribute for each body. Required
  for SOAPpy compatibility. SVN Rev[3539].

* Thu Jan 19 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-27
- CHANGED: In spec file, give asterisk permissions to folder
  /var/log/elastix
- DELETED: additionals - CentOS-Base.repo: kernel attribute was
  removed, now it is not need to update the kernel because the
  kernel updates are handles through the kmod. SVN Rev[3538].
- FIXED: Elastix Framework: an extra comma at the end of a block
  declaration in jquery-upl-blockUI.js triggers syntax error
  warnings in IE6 and IE8 in compatibility mode. Remove it
  (introduced by SVN commit #3515). SVN Rev[3537].
- CHANGED: additionals elastix-dbprocess, changed the methodology
  for comparing RPMs. Now here is used the same methodology as
  used in module addons_availables. SVN Rev [3531].
- FIXED: additionals - lcdelastix/lcdapplets/ch.php: Se muestra
  mensaje de error en el shell cuando se accede a PBX Activity>Concurr
  Channels con el LCD del appliance. Bug 0001098. SVN Rev [3528].

* Tue Jan 17 2012 Rocio Mera <rmera@palosanto.com> 2.2.0-26
- CHANGED: Framework - Themes: Changes applied in _menu.tpl. This
  changes add variables of languages as "hidden input" and support
  view a state of a note with tab_notes_on.png. SVN Rev[3516].
- ADDED:   Framework - Themes/elastixneo/images: Added a new imagen
  tab_notes_on.png for commit SVN Rev[3514]. SVN Rev[3516].
- CHANGED: Framework - index.php: Added action to put image
  tag_notes_on.png for a sticky note if the current module has a one.
  This image is in toolbar of a module. SVN Rev[3515].
- CHANGED: Framework - js (base.js, sticky_note.js,
  jquery/jquery-upl-blockUI.js): Changes in javascripts to apply a
  state of a note with a image. This identify if the module or menu
  has a note added. In lib blockUI some attributes of css was changed
  to show a better pop-up. SVN Rev[3514].

* Fri Dec 30 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-25
- CHANGED: In spec file, create the user asterisk if not exists

* Thu Dec 29 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-24
- CHANGED: In spec file, the prereq php-pear-DB was removed, also
  everything related with asterisk was removed
- DELETED: additionals, deleted empty folders additionals/trunk/bin
  and additionals/trunk/etc/cron.daily
  SVN REV[3497]
- CHANGED: changed everything to do with asterisk from framework
  to elastix-pbx
  SVN Rev[3496]
- CHANGED: Framework - Themes/elastixneo/_common/_menu.tpl: Changes
  applied in _menu.tpl to keep in module tool bar the icon "expand
  left bar" when there is not a third level menu.
  SVN Rev[3493]

* Mon Dec 26 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-23
- FIXED BUG: Framework - base.js: function getElastixKey,
  now is no longer necessary to parse the JSON due to the use
  of library JSON.php, also the key for the value of the server
  key is now "server_key"
	   * Introduced by: Alberto Santos
	   * Since: Due to the redesign of module addons
  SVN Rev[3490]
- CHANGED: In Spec file move all files privileged to
  /usr/share/elastix/privileged, for the new file privileged
- CHANGED: Framework - (themes, libs, index.php): Changed the
  name of action to show a note. Before "ticky note" now
  "sticky note". SVN Rev[3488]
- CHANGED: Framework - base.js: changed window.open to location.href on
  function getElastixKey. SVN Rev[3482]
- CHANGED: Framework - index.php: Changes in index.php and
  base.js to show a alert message when the session has been
  expired, it only occur in ajax request. SVN Rev[3473]
- CHANGED: Framework - Themes: Support to add a ticky note for
  all themes. SVN Rev[3471]
- CHANGED: Framework - Languages: Modified en.lang and es.lang
  to add words to "ticky note". SVN Rev[3469]
- CHANGED: Framework - Themes: In elastixneo add funtionality
  of leave a message as "ticky note". SVN Rev[3468]

* Wed Dec 14 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-22
- FIXED: Framework: fix invalid javascript syntax for object
  literal in colorpicker declaration. Fixes Elastix bug #1115.
  SVN Rev[3452]
- FIXED: Framework - base.js: Changes in base.js to fix the bug
  when try to remove a bookmar after to do a login. SVN Rev[3439]
- CHANGED: Framework - themes: Changes in elastixneo to delete
  any bookmark from the div of bookmarks press on "X" image.
  SVN Rev[3433]


* Thu Dec 08 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-21
- CHANGED: Framework - Themes: In elastixneo support to drag of
  login on login.tpl. SVN Rev[3430]
- FIXED: Framework - Themes: In elastixneo added property min-width
  on 1 and 2 level menu. SVN Rev[3429]
- CHANGED: Framework: attempt to further increate speed of menu
  filtering. SVN Rev[3428]
- FIXED: Framework: obey menu order in improved implementation of
  ACL menu filtering in first-level menus too. SVN Rev[3427]
- CHANGED: Framework: get rid of caching of authorized menus.
  No longer necessary with much faster ACL filtering. SVN Rev[3426]
- FIXED: Framework: obey menu order in improved implementation
  of ACL menu filtering. SVN Rev[3425]
- CHANGED: Framework: greatly increase the speed at which
  authorized modules are resolved. Now authorized menu filtering
  is at least 64x faster. SVN Rev[3424]
- FIXED: Framework: fix in previous commit. SVN Rev[3420][3421]
- CHANGED: Framework: abstract away ACL filtering of menus into
  a new method in paloMenu class. SVN Rev[3419]
- CHANGED: Modules - Extra: Changes in a2billing to fix the bug
  with user "root" and password without encode. SVN Rev[3418]
- CHANGED: Framework: method cargar_menu is a menu operation that
  belongs in paloMenu class. SVN Rev[3414]
- FIXED: Framework - Themes: Fixed Bug in ElastixNeo when menues
  are 8 menues the style is corrupted by a <div> where is never
  closed. SVN Rev[3412]
- CHANGED: Framework: use _tr() instead of $arrLang consistently.
  SVN Rev[3411]
- FIXED: Additional - elastix-firstboot: Changes scripts
  elastix-firstboot and change-passwords to change the user root to
  admin in a2billing database. SVN Rev[3410]
- CHANGED: Framework/Registration: use privileged script 'elastixkey'
  to reimplement writing registration key. SVN Rev[3409]
- ADDED: Framework/Registration: introduce new privileged script
  'elastixkey' to write registration key to /etc/elastix.key
  SVN Rev[3408]

* Fri Dec 02 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-20
- FIXED: script search_ami_admin_pwd, added a line break at the
  end of each line in file /etc/elastix.conf if it does not have one
  SVN Rev[3407]
- FIXED: library paloSantoACL.class.php, the function getUserExtension
  was parameterized and the validation for username that it has to be
  alphanumeric is no longer necessary
  SVN Rev[3406]

* Thu Dec 01 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-19
- FIXED: script search_ami_admin_pwd, the keys are written with
  spaces between the equal. Now the spaces between the equal are removed
  SVN Rev[3405]

* Fri Nov 25 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-18
- CHANGED: In spec file, changed name to elastix-framework
- ADDED: In spec file, added conflicts elastix-pbx <= 2.2.0-16
  and elastix-fax <= 2.2.0-5
- NEW: new script search_ami_admin_pwd, this script search the
  ami password for user admin in /etc/asterisk/manager.conf and
  put it in /etc/elastix.conf, also verifies the ari password
  in /etc/amportal.conf
  SVN Rev[3400]
- CHANGED: Framework: remove asterisk permission for nmap
  command in /etc/sudoers. This must be applied after SVN
  commit 3382 in elastix-pbx.
  SVN Rev[3383]
- CHANGED: Framework: remove uucp permission for chmod command
  in /etc/sudoers. This must be applied after SVN commit 3376
  in elastix-fax.
  SVN Rev[3378]
- CHANGED: Framework: remove uucp permission for chmod command
  in /etc/sudoers. This must be applied after SVN commit 3376
  in elastix-fax.
  SVN Rev[3377]
- ADDED: Framework - Themes: Added 2 images to solved the bug
  http://bugs.elastix.org/view.php?id=1088. This change is
  required for the commit 3371
  SVN Rev[3372]
- FIXED: Framework - themes: Changes in elastixNeo Theme to fix
  the bug http://bugs.elastix.org/view.php?id=1088.
  SVN Rev[3371]
- FIXED: Framework - PalosantoNavigator, index.php: Added validation
  in function "getIdParentMenu" in palosantoNavigator because appear
  a wanning when the current theme is not elastixNeo.
  SVN Rev[3366]
- CHANGED: Additional - motd.sh: Changed the labels in motd.sh.
  CHANGED: Framework - Themes: Changed theme elastixneo to
  support buttons sliders when there are many menues of 2 level
  SVN Rev[3359]

* Wed Nov 23 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-17
- FIXED: Framework - PalosantoNavigator: Changes applied in
  palosantoNavigator to include the menu current as part of the
  history  in the view of web interface. SVN Rev[3349][3351]
- FIXED: Additional - elastix-menumerge: Changes applied to update
  the description of a menu in process updating. SVN Rev[3349]
- CHANGED: Additionals - motd.sh: Changed the files motd.sh to
  include a message. SVN Rev[3349]
- CHANGED: module registration, changed the message displayed when
  the data can not be saved in the database, to the following
  "The register information could not be saved in the local database."
  SVN Rev[3347]
- FIXED: module registration, if the database register.db or table
  register do not exist, are automatically created. SVN Rev[3346]

* Wed Nov 23 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-16
- CHANGED: Framework - base.js: Changes in style of blockui action
  for add bookmark and remove the blockui in action saveToggleTab
  SVN Rev[3344]
- FIXED:  Framework - PalosantoNavigator: Fixed bug when menu of 3
  level cannot be saved as bookmark. SVN Rev[3342]

* Tue Nov 22 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-15
- FIXED:   Changes in index.php and palosantoNavigator to set in
  var $_SESSION['menu'] when is is empty. SVN Rev[3339]
- CHANGED: jquery-upl-colorpicker.js plugin, added an option to
  pass as a parameter the id of the element
  SVN Rev[3335]
- CHANGED: theme elastixneo, the colorpicker is now also closed
  when user clicks on its icon
  SVN Rev[3334]
- CHANGED: elastix themes, added a new style for input disabled
  SVN Rev[3330]
- ADDED: update sql script, this script changes the order of modules
  userlist to 41, grouplist to 42 and group_permission to 43
  SVN Rev[3328]
- FIXED: Framework: use SQL query parameters in get_key_settings
  and set_key_settings
  SVN Rev[3317]
- CHANGED: Framework - Base.js : Changed javascript in bookmark to
  put a new label of images (title) and add a new image expandOut.png
  SVN Rev[3316]
- CHANGED: Framework - Themes: Changed labels of bookmarks in elastixneo.
  SVN Rev[3315]
- CHANGED: Framework - Languages: Added new labels of languages in
  english and spanish
  SVN Rev[3314]
- CHANGED: module themes_system, changed the value of the button to
  "Save" and changed its location
  SVN Rev[3313]
- CHANGED: module language, changed the width to label "language"
  SVN Rev[3312]
- CHANGED: module language, added the tag <form> to language.tpl
  SVN Rev[3311]
- CHANGED: module language, changed the name of the button to
  "Save" and change its location
  SVN Rev[3310]
- CHANGED: Framework - base.js: Changes in base js to remove alerts
  of message when operation of add or remove a bokmark is done.
  Only the alert appear when there are an error.
  SVN Rev[3309]
- CHANGED: Framework - libs: Changes in palosantoNavigator and
  misc.lib and others files to support the action to add a bookmark
  and save a database acl the history
  SVN Rev[3308]
- CHANGED: Framework - Themes: Changes in elastixneo to support
  bookmarks and history.
  SVN Rev[3306]
- ADDED: Framework - Language: Added new words for traslating in
  english and spanish. This labels are used in elastixneo theme
  SVN Rev[3305]
- NEW: script elastix_warning_authentication, new script that
  shows a template with information about elastix authentication
  or permissions
  SVN Rev[3304]
- NEW: Framework - js: Add library jquery-upl-blockUI.js to windows
  loading for elastixneo in bookmarks
  SVN Rev[3301]
- CHANGED: elastix-htaccess.conf, allowed the use of files
  .htaccess in /var/www/html/panel
  SVN Rev[3300]
- Framework: (blackmin) work around strange CSS in Backup/Restore
  module that makes Elastix menu items too narrow.
  SVN Rev[3298]
- Framework: (blackmin) introduce gray line that is visible
  on empty reports
  SVN Rev[3297]
- Framework: (blackmin) standarize widget appearance as done
  for other themes.
  SVN Rev[3296]
- CHANGED: framework lang, added new translations
  SVN Rev[3295]
- Framework: first version of new theme blackmin. This is a
  minimalistic theme with shades of gray, that dedicates as much
  space as possible to the module itself. Click on the logo at
  the upper-left corner for the Elastix menu.
  SVN Rev[3294]
- FIXED: Framework - themes: Elastix neo appear the
  lengueta-minimized above of div module content
  SVN Rev[3284]
- Fixed: Framework - Themes: Fixed bug where popup with position
  absolute do not appear correctly.
  SVN Rev[3269]

* Tue Nov 01 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-14
- CHANGED: styles.css in theme elastixneo, changed the background
  image for class "menulogo2"
  SVN Rev[3251]
- FIXED: Framework - base.js: Added patch to fix the bug when the
  list of modules in a action to search appear in other position.
  SVN Rev[3246]
- CHANGED: Framework - images : changed image expand.png.
  SVN Rev[3244]
- CHANGED: Framework - Themes: After the change de color this do
  not appear selected in reference of colorPicker library, this
  is solved change the color of colorPicker with the actual color
  value. SVN Rev[3243]

* Sat Oct 29 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-13
- CHANGED: theme elastixneo, added a border left to the neo-second-showbox-menu
  SVN Rev[3233]
- CHANGED: theme elastixneo, added a validation for versions of
  internet explorer 8 or less
  SVN Rev[3232]
- FIXED: Framework - libs: Fixed bug when a user administrator has
  not a profile assigned in the acl.db
  SVN Rev[3231]
- ADDED: Framework - Setup : Added new sql script to update dashboard.db
  and setting.db
  SVN Rev[3230]

* Sat Oct 29 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-12
- CHANGED: Framework - Themes: changes in themes/elastixneo/content.css
  SVN Rev[3220]
- UPDATED: update themes
  SVN Rev[3219]
- FIXED: Framework: remove percentages from pie graph
  SVN Rev[3217]
- CHANGED: Framework - Themes : Changed styles for support the new dashboard.
  SVN Rev[3216]
- FIXED: Framework - index.php: Added smarty variable to label
  "Search Module" in _menmu.tpl
  SVN Rev[3215]
- FIXED: Framework: fix plot of 0%/100% of a pie slice.
  SVN Rev[3214]
- CHANGED:  Framework - Base.js: Change the style for popup of change password
  SVN Rev[3213]
- FIXED: Framework: complete separation to plot3d2
  SVN Rev[3210]
- FIXED: Framework - paloSantoGraphImage.lib.php: Restore type graph
  plod3d and add plod3d2 by "displayGraph_draw_pie3d" function
  SVN Rev[3209]
- FIXED: Framework: fix regexp on disk usage so that it still matches
  on a full partition (100%)
  SVN Rev[3208]
- CHANGED: Framework - images: Changed images images/flecha_asc.png and
  images/flecha_desc.png with background blank by the same images with
  transparence
  SVN Rev[3205]
- CHANGED: elastix themes, added a class called "frameModule"
  SVN Rev[3183]
- CHANGED: theme al, added a class called "frameModule"
  SVN Rev[3182]
- CHANGED: library paloSantoNavigation.class.php, added a class to
  iframe for frame modules
  SVN Rev[3181]
- CHANGED: Framework - themes: Changes in styles of ElastixNeo theme
  SVN Rev[3178]
- CHANGED: Framework: ElastixNeo - use min-height instead of height
  for select, unbreaks multiline select controls.
  SVN Rev[3173]
- NEW: Framework - Themes: Added new styles for ElastixNeo Theme
  SVN Rev[3172]
- CHANGED: Framework - libs: changes in palosantoNavigator to support
  ElastixNeo theme.
  SVN Rev[3171]
- CHANGED: FRAMEWORK - themes : changes in ElastixNeo
  SVN Rev[3170]
- CHANGED: theme slashdot, the module title is now handled by the framework
  SVN Rev[3169]
- CHANGED: theme giox, the module title is now handled by the framework
  SVN Rev[3168]
- CHANGED: theme elastixwine, the module title is now handled by the framework
  SVN Rev[3167]
- CHANGED: theme elastixwave, the module title is now handled by the framework
  SVN Rev[3166]
- CHANGED: theme elastixblue, the module title is now handled by the framework
  SVN Rev[3165]
- CHANGED: theme default, the module title is now handled by the framework
  SVN Rev[3164]
- CHANGED: theme al, the module title is now handled by the framework
  SVN Rev[3163]
- FIXED: Framework: remove stray print_r
  SVN Rev[3137]
- CHANGED: library paloSantoNavigation.class.php, added a title for
  frame modules
  SVN Rev[3136]
- CHANGED: module themes_system, the module title is now handled by the
  framework
  SVN Rev[3130]
- CHANGED: module language, the module title is now handled by the framework
  SVN Rev[3128]
- ADDED: added new image email.png to the framework
  SVN Rev[3122]
- CHANGED: module grouplist, the module title is now handled by the framework
  SVN Rev[3120]
- CHANGED: library paloSantoNavigation.class.php, now it is no longer
  necessary to fetch the menu.tpl because this is done now in index.php
  SVN Rev[3113]
- FIXED: index.php of framework, now smarty variables used in list.tpl
  can be used in menu.tpl
  SVN Rev[3112]
- NEW: FRAMEWORK - themes: New theme elastix Neo.
  CHANGED: FRAMEWORK - misc.lib: Support to function in ElastixNeo
  CHANGED: FRAMEWORK - base.js:  SUpport new javascripts to ElastixNeo
  SVN Rev[3111]
- CHANGED: Framework - index.php:  Support to the new theme ElastixNeo
  in index.php
  SVN Rev[3110]
- CHANGED: module themes_system, better way to fix the refresh theme bug.
  SVN Rev[3109]
- FIXED: module themes_system, the smarty cache is also refreshed when
  entering to the module
  SVN Rev[3107]
- CHANGED: index of framework, the index was changed in order to a user
  can not access to a menu which its parent is not authorized
  SVN Rev[3105]
- CHANGED: Framework - lang: Add new key of languages to support the new theme
  SVN Rev[3104]
- FIXED: Framework: fix new pie and gauge not being centered.
  SVN Rev[3098]
- CHANGED: Framework: encapsulate 3d pie into internal function
  SVN Rev[3096]
- ADDED: Framework: new graph type 'gauge'
  SVN Rev[3093]
- CHANGED: Framework: new method of displaying pie graph with alpha image
  SVN Rev[3091]

* Mon Oct 17 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-11
- FIXED: Framework - Registration: Validation from server about register
  information to send elastix web service. SVN Rev[3085]
- FIXED: Framework - registration: Added error message if the database
  register.db doesn't exist and the JSON Array is changed to send from
  server to the clients. SVN Rev[3084]
- FIXED: Registration: replace exec of echo with file_put_contents
  for write of registration SID. SVN Rev[3083]

* Fri Oct 14 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-10
- ADDED: In spec file, added conflicts elastix-callcenter <= 2.0.0-16

* Mon Oct 10 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-9
- CHANGED: In spec file, for installations the apache is restarted

* Fri Oct 07 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-8
- CHANGED: In spec file, changed prereq elastix-firstboot >= 2.2.0-5
- ADDED: added a configuration file that allows files .htaccess
  in /var/www/html/admin and in /var/www/html/mail
  SVN Rev[3058]
- CHANGED: elastix.conf, reverted the changes of commit 3053
  SVN Rev[3056]
- NEW: new script bash that compares two versions with elastix format
  SVN Rev[3054]
- FIXED: elastix.conf, added new directories in order to files
  .htaccess take effect in these directories
  SVN Rev[3053]
- CHANGED: base.js, for modules that have the filter_value text box,
  call a function that submits the form in case the key "enter" was pressed
  SVN Rev[3032]

* Tue Oct 04 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-7
- ADDED: framework lang, added new translations
  SVN Rev[3026]
- FIXED: elastix-dbprocess, added a validation in case the
  file db.info does not exist or it is empty
  SVN Rev[3025]

* Wed Sep 28 2011 Eduardo Cueva <ecueva@palosanto.com> 2.2.0-6
- FIXED: Framework: Bad format of email template when a voicemail
  is sent. This bug is fixed with function verifyTemplate_vm_email().
  This commit solved the commit 3014 where ip is not replaced in
  /etc/asterisk/vm_email.inc. SVN Rev[3017][3014]
- FIXED: Framework: Fixed bug where appear images over the popup
  register (over main menu), this bug appear in theme elastixwine
  SVN Rev[3015]
- ADDED: added new images to framework. SVN Rev[3011]

* Tue Sep 27 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-5
- CHANGED: In spec file, changed prereq elastix-firstboot >= 2.2.0-4
- ADDED: misc.lib.php, added new function that gets the
  AMI password in file /etc/elastix.conf
  SVN Rev[2994]
- CHANGED: framework, changed the location of function
  checkFrameworkDatabases. Now it is called in file default.conf.php
  just before calling the function load_theme to prevent any error
  in themes due to the non-existence of database settings.db
  SVN Rev[2992]

* Thu Sep 22 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-4
- FIXED: Framework - libs/js/jquery/jquery-upl-windowAero.js:
  The button close of a window generated by lib "jquery-upl-windowAero.js"
  never remove the content of the windows and create a new other
  with the same id.
  SVN Rev[2969]

* Wed Sep 07 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-3
- CHANGED: In Spec file, creation of log /var/log/elastix/postfix_stats.log
  and added a config(noreplace) to elastixEmailStats.logrotate
- CHANGED: module grouplist, in view mode the asterisks and word
  required file were removed
  SVN Rev[2945]
- NEW: elastixEmailStats.logrotate, logrotate for log
  /var/log/elastix/postfix_stats.log
  SVN Rev[2937]
- ADDED: images of themes, added the image closelabel.gif in all
  the themes because it is used in module hardware_detector
  SVN Rev[2933]

* Mon Aug 29 2011 Alberto Santos <asantos@palosanto.com> 2.2.0-2
- CHANGED: In spec file, changed prereq elastix-firstboot >= 2.2.0-1
- ADDED: misc.lib.php and index.php, added a function that checks
  if the framework databases exist, in case they dont it tries to
  remane their equivalent file that ends in .rpmsave
  SVN Rev[2900]
- CHANGED: In Spec file, added the use of elastix-dbprocess for
  databases in framework
- CHANGED: databases of framework, created the hierarchy of folders
  for sql scripts exactly the same as the created for the modules
  SVN Rev[2898]
- FIXED: elastix themes, incremented the z-index of layerCM
  SVN Rev[2880]

* Mon Aug 01 2011 Bruno Macias  <bmacias@palosanto.com> 2.2.0-1
- DELETED: SQLite database acl.db in additionals section, Database was
  deleted because its use is obsolete. elastix-dbprocess script and
  elastix-menumerge script now are responsible for permits according
  to the XML resources menu.xml it has defined.
- CHANGED: elastix-dbprocess, for a database mysql, if the action
  is install or update added "USE $dbName".


* Fri Jul 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-30
- CHANGED: In spe file changed Conflics with elastix-system < 2.0.4-18

* Tue Jul 19 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-29
- CHANGED: Framework - registration: change in code to allow
  view the form register only for administrator group. SVN Rev[2822]

* Mon Jul 11 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-28
- CHANGED: Framework - base.js: Add lines to improve the process to
  update when the serverID is missed. SVN Rev[2818]
- FIXED: Framework - base.js: show button activated register. This
  button was not showed because there are a error do not handled.
  SVN Rev[2817][2816][2815][2814]
- FIXED: theme elastixblue, added to the menu links the word
  index.php in order to all the requests go through the index
  framework. SVN Rev[2803]

* Thu Jun 30 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-27
- CHANGED: library paloSantoGraphImage.lib.php, when the graph has
  nothing to show, the title of the image is set on the center of it
  SVN Rev[2770]

* Wed Jun 29 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-26
- FIXED: library misc.lib.php, function writeLOG wrote the hour
  in 12-hour format. Now it writes in 24-hour format
  SVN Rev[2765]

* Fri Jun 24 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-25
- UPDATED: Framework SOAPhandler.class.php lib, definition of
  name WSDL document was improved, before was defined as
  genericWDSDL. SVN Rev[2749]
- FIXED: theme elastixblue, the height of the div "acerca_de"
  was increased in order to show the bottom of about us message
  in chrome. SVN Rev[2748]
- ADDED: images of framework, added the image called pci.png.
  SVN Rev[2747]
- FIXED: framework elastix theme elastixwave, the height of the
  div acerca_de was increased in order to fix the problem of not
  showing the border bottom of about us in chrome. SVN Rev[2742]
- CHANGED: Frameword - libs : Remove inclution file
  email_functions.lib.php in misc.lib.php. SVN Rev[2737]
- DELETED: Frameword - libs : Delete file email_functions.lib.php,
  because all function in email_functions.lib.php are in
  PalosantoEmail.class.php. SVN Rev[2737]

* Mon Jun 13 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-24
- CHANGED: In spec file add Conflicts: elastix-system < 2.0.4-14
- FIXED: Framework - Registration: Fixed the action when the
  server ID in not valid, now the system recommend update the data
  in Elastix Web Services to generate a new Server ID. SVN Rev[2734]
- CHANGED: Framework - registration: Some changes was applied to
  improve the loading of data from Elastix Web Services in each
  elastix server. Using Ajax to solve the problem. SVN Rev[2731][2733]
- CHANGED: elastix-dbprocess, better informative message in case of
  wrong version format. SVN Rev[2732]
- CHANGED: elastixAudit.logrotate, changed name from
  "elastixAccess.logrotate" to "elastixAudit.logrotate". SVN Rev[2722]
- CHANGED: index.php of framework and paloSantoNavigation, write in
  log file audit.log when a user enters a module. SVN Rev[2721]
- CHANGED: elastix-dbprocess, new validation for version format
  like x.x.x-x.x.x (in particular for fop2). SVN Rev[2719]
- CHANGED:  Framework - Registration: Add changes to show serverKey
  in registration's window and change the height of that window.
  SVN Rev[2717]
- CHANGED: Framework: Due to use of elastix-helper by System/Date
  Time, date no longer requires sudo privileges. SVN Rev[2709]
- FIXED: Framework: ensure that invalid permissions make script exit
  with nonzero (failure) status. SVN Rev[2708]
- FIXED: Framework: sudo wrapper script requires quotes to protect
  parameters with spaces. SVN Rev[2706]
- CHANGED: Framework: Due to use of elastix-helper by
  System/Network Configuration, route and hostname no longer require
  sudo privileges. SVN Rev[2695]
- CHANGED: Framework: add elastix-helper to /etc/sudoers. SVN Rev[2691]
- CHANGED: Additionals: The ereg function was replaced by the
  preg_match function due to that the ereg function was deprecated
  since PHP 5.3.0. SVN Rev[2687]
- CHANGED: Framework - paloSantoDB.class.php: The genExec function
  has been modified due to that returned false, in spite of that
  the query was executed successfully. SVN Rev[2684]
- ADDED: Framework: introduce elastix-helper.
  This program (elastix-helper) is intended to be a single point of
  entry for operations started from the web interface that require
  elevated privileges. The program must be installed as
  /usr/sbin/elastix-helper and invoked via the wrapper
  /usr/bin/elastix-helper which closes extra file descriptors with
  /usr/sbin/close-on-exec.pl and adds the sudo invocation.
  As extra file descriptors past STDIN/STDOUT/STDERR are closed via
  the intended invocation, helper programs should not rely on any
  file descriptors being open other than the standard ones.
  Packages should install helper programs in
  /usr/share/elastix/privileged. All communication should be
  performed via command-line parameters. SVN Rev[2683]
- CHANGED: Framework: mark several methods in paloConfig as
  private. SVN Rev[2682]
- CHANGED: Framework: comment out methods get_archivos_directorio
  in paloConfig. Seems nobody is using it. Part of ongoing effort
  to remove sudo chown. SVN Rev[2681]
- CHANGED: Framework: comment out methods establece_permisos in
  paloConfig. Seems nobody is using it. Part of ongoing effort
  to remove sudo chown. SVN Rev[2680]
- CHANGED: Framework: comment out methods crear_archivo and
  crear_archivo_sin_establecer_permisos in paloConfig.
  Seems nobody is using them. Part of ongoing effort to remove
  sudo chown. SVN Rev[2679]
- CHANGED: Framework: comment out method crear_directorio in
  paloConfig. Seems nobody is using it. Part of ongoing effort to
  remove sudo chown. SVN Rev[2678]
- CHANGED: Framework: mark method privado_chown in paloConfig as
  private. Part of ongoing effort to remove sudo chown. SVN Rev[2677]
- CHANGED: Framework: revert a bit of SVN commit 2674. Many
  ereg()-style regular expressions are scattered through the code
  in form definitions, and all of these must be checked for
  preg_match() compatibility before switching to preg_match()
  in form validation. SVN Rev[2675]
- CHANGED: The ereg function of these files was replaced by the
  preg_match function due to that the ereg function was deprecated
  since PHP 5.3.0. SVN Rev[2674]

* Tue May 31 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-23
- CHANGED: Module Time Config, se cambio de lugar al módulo time
  config, paso de framework a modules/core/system. SVN Rev[2667]

* Mon May 30 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-22
- NEW:  Add Database register.db to register the installation of a
  elastix. SVN Rev[2658]
- NEW:  Framework : New Action "Register" in framework, This action
  allows to the users register their elastix. SVN Rev[2656]
- CHANGED: The split function of these files was replaced by the
  explode function due to that the split function was deprecated
  since PHP 5.3.0. SVN Rev[2651]
- FIXED: elastix-dbprocess, if the password of mysql has spaces an
  error occurs. Now the mysql password can have spaces. SVN Rev[2648]
- FIXED: Framework Elastix, misc.lib.php. Name function javascript
  cannot  have "-" character. SVN Rev[2644]

* Tue May 10 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-21
- FIXED: elastix-dbprocess, wrong variable name $dbname, the correct
  name is $dbName also wrong name for renaming sqlite3 databases
  it must ends in .db
  SVN Rev[2634]
- FIXED: elastix-dbprocess, in case of databases sqlite3 changed
  the owner and group to asterisk
  SVN Rev[2633]

* Thu May 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-20
- CHANGED: framework, changed to the new logo of elastix.
  SVN Rev[2596]
- CHANGED: misc.lib.php : Separate emails function in a new file
  called email_functions.lib.php in branch and trunk. SVN Rev[2595]
- FIXED:   dialog "about us", when it is showed on module antispam
  the bar to select the level of spam filtering (1 to 10) overlaps
  the box "About us". It has been fixed in all the themes.
  SVN Rev[2592]
- CHANGED: elastix-dbprocess, a "{" was misplaced. SVN Rev[2582]
- CHANGED: SOAPhandler.class.php, wrong class name in the header
  documentation. SVN Rev[2581]

* Tue Apr 26 2011 Alberto Santos <asantos@palosanto.com> 2.0.4-19
- NEW: new libraries WSDLcreator.class.php and SOAPhandler.class.php
  SVN Rev[2555]
- CHANGED: changed the height for the popup about us, because
  was not showing the bottom border for browser google chrome
  SVN Rev[2522]
- CHANGED: elastix-dbprocess, new structure for elastix-dbprocess
  SVN Rev[2502]
- CHANGED: In Spec file, wrong path for remove module userlist from
  framework

* Mon Apr 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-18
- CHANGED:  Framework - images: Resize the image
  x-lite-4-lrg.jpg because this was too big compared with the
  others. SVN Rev[2501]

* Fri Apr 01 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-17
- FIXED: additionals - elastix-dbprocess :  Add validation to
  know if mysql is running or not in a process to install when
  the event use the update scripts

* Thu Mar 31 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-16
- FIXED: elastix-dbprocess, Validation was improved if file
  /etc/elastix.conf don't exists. SVN Rev[2479]

* Wed Mar 30 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4.15
- FIXED: module group_permission, actions view, create, update
  and delete do not exist in the table acl_action. Those actions
  were commented. SVN Rev[2473]

* Tue Mar 29 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-14
- CHANGED: about us message was changed for a better message.
  SVN Rev[2461]
- FIXED: fixed the problem of logout=yes in the url (bug #710).
  SVN Rev[2457]
- CHANGED: paloSantoACL, changed the functions getNumResources
  and getListResources, now the parameter that they receive
  could be a string or an array. SVN Rev[2452]
- CHANGED: module group_permission, changed the methodology for
  searching a resource. SVN Rev[2451]
- CHANGED: module grouplist, changed the en.lang, the word
  "extension user" was changed to "Extension User". SVN Rev[2449]
- CHANGED: in en.lang of Framework, translation changed "administrator"
  to "Administrator" and "extension" to "Extension". SVN Rev[2447]
- UPDATED:  Update libs of JQuery from jquery 1.4.2 to 1.5.1 and
  jquery-ui 1.8.2 to 1.8.10. SVN Rev[2443]
- CHANGED: Change permissions of "/etc/sasldb2" after to execute
  "saslpasswd2 -c cyrus -u example.com" to create user cyrus admin
  SVN Rev[2442]

* Sat Mar 19 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-13
- CHANGED: changed the old logo to the new one. SVN Rev[2421]
- FIXED: wrong favicon, now the favicon is the correct logo of
  elastix. SVN Rev[2419]
- ADDED: image x-lite-4-lrg used in static softphones.
  SVN Rev[2404]
- FIXED:  change line: $clave = obtenerClaveCyrusAdmin()  by
  $clave = obtenerClaveCyrusAdmin("/var/www/html/"), is
  necessary for antispam. SVN Rev[2397]

* Wed Mar 09 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-12
- FIXED: elastix-dbprocess, undefined variable engine, the
  correct variable name is data['engine']. SVN Rev[2394]

* Fri Mar 04 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-11
- FIXED: elastix-dbprocess, when the action is "install" the
  process of creating database is not completed because the script
  elastix-dbprocess was supposed to receive 2 parameters and not 4,
  also the script needs to give asterisk group permissions to
  the database. SVN Rev[2393]

* Tue Mar 01 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-10
- CHANGED: theme elastixwave, added a focus to the username field
  SVN Rev[2387]
- FIXED: additionals - elastix-firstboot: In elastix-firstboot
  add new password in elastix.conf for cyrus admin user, this
  fixes the bug where any user could connect remotely to the
  console using cyrus admin user and password known. SVN Rev[2383]
- FIXED: framework - misc.lib.class  Add new function to get password
  of cyrus admin, this fix the bug where anybody could connect to
  cyrus admin by net. SVN Rev[2381]
- FIXED:  Framework - paloSantoForm.class.php: PalosantoForm does
  not validate forms with html element type of FILE. SVN Rev[2370]
- FIXED: Additionals elastix-menumerge, Fixed bugs where temporal
  files of smarty cache return an error when in a upgrading there
  are changes in designer of any module or framework where those
  changes cannot be seen in the web interface. SVN Rev[2359]
- NEW: Elastix framework, paloSantoDB.class.php. Added support
  to connections at postgreSQL. Improvement function
  getLastInsertId to be more generic and accept an object of
  connection. SVN Rev[2351]
- CHANGED: module time_config, replaced message to accept the
  change of time configuration. SVN Rev[2349]
- FIXED: framework, fixed the problem of not showing a border
  line in the window displayed in "about us" using Chrome 8.0,
  the problem was fixed for all the themes. SVN Rev[2348]
- CHANGED: module time_config, changed the message to "Changing
  the date and time in the system can cause unexpected or
  inconsistent values in the process whose calculations depend
  on it". SVN Rev[2345]
- CHANGED: framework, changed the width of the left side of the
  help window for all the themes. SVN Rev[2337]

* Mon Feb 07 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-9
- CHANGED: Send output of dialog to file descriptor 3 with
  --output-fd option. This prevents error messages from dialog
  from messing the password output. Should fix Elastix bug #702.
  SVN Rev[2331]
- CHANGED: elastix-dbprocess, validate the case that the engine
  using is mysql but mysql is shutdown. SVN Rev[2325]
- FIXED: Elastix framework - paloSantoInstaller.class.php,
  Scape mysql password in creation of databases, it works with
  function escapeshellcmd de php.

* Thu Feb 03 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-8
- CHANGED:  in Spec files remove lines about html folder in
  additionals because this folder not exist in the last source
  of files.
- CHANGED:  elastix-dbprocess, validate the type of engine
  using (mysql or sqlite3) and created the function to delete.
  SVN Rev[2305]
- CHANGED:  libs Framework - palosantoModuloXML,
  palosantoInstaller,palosantoACL: Support new xml from menu.xml
  to add group permissions in a process to install. SVN Rev[2301]
- CHANGED:  Additionals - elastix-menumerge:  change file to
  support new xml to install modules in that xml will have a
  tag "permissions". SVN Rev[2300]
- DELETED: Elastix framework, Remove Group "Extension" in acl.db,
  Because it will be create in process to install rpms.
  SVN Rev[2295]
- ADDED:    module endpoint_configuration, added model GXV3175
  SVN Rev[2288]
- FIXED:    framework-palosantoACL: change function
  isUserAdministratorGroup where it return false if one user do
  not belong to administrator group. SVN Rev[2278]
- UPDATED:  Elastix Framework, elastix-menuremove. For deleting
  a menu if that operation is not completed the querys are done
  a rollback. SVN Rev[2277]
- DELETED:  Delete folder additionals/html because this folder
  is empty and all files was moved modules. SVN Rev[2269]
- CHANGED:  Additionals - trunk/html/:  move xmlservices, static
  and openfireWrapper.php to modules/trunk/core/extras and
  modules/trunk/core/im folders. SVN Rev[2267]
- FIXED:    Problem if any account was deleted due to if there is
  an error while to delete an email account and its user on system
  cannot be removed, the account is deleted but the user system not,
  it occur when a new account is create with the same user that was
  deleted because this user in system exist.. [#489] SVN Rev[2248]
- ADDED:    module time_config, added the javascript that contains
  the construction of the jquery calendar. SVN Rev[2242]
- CHANGED:  module time_config, added a JQuery calendar in order
  to set the date. SVN Rev[2241]
- FIXED:    framework paloSantoGraphImage, made global the
  variable $_MSJ_NOTHING with this change its fixed the problem
  of showing an error message when the outgoing or ingoing calls
  are 0 in the module summary_by_extension. SVN Rev[2234]
- CHANGED:  Add new option in INPUT_EXTRA_PARAM to date, this
  new option is "FIRSTDAY" and can be 1 to 7 where 1 is monday
  and 7 is sunday. It is used to show the first day in calendar.
  SVN Rev[2229]
- FIXED: Framework index.php, bad definition word, unknown.
  SVN Rev[2227]

* Wed Jan 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-7
- FIXED: Framework email.conf.php, Put localhost to connect with
  user cyrus. Bug http://bugs.elastix.org/view.php?id=382.
  SVN Rev[2223]

* Wed Jan 05 2011 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-6
- CHANGED: Framework index.php, Messages of audit was improved
  so show a type of message when the access is by web.
  SVN Rev[2211]

* Wed Dec 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-5
- FIXED: Framework elastix-dbprocess, Fix event to install or
  update a database where dbprocess ask keywork of mysqlroot if
  mysql in on or exportar SQL to elastix-firstboot if it is off.
  SVN Rev[2177]

* Wed Dec 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-4
- FIXED: Framework Elastix, elastix-dbprocess. Fixed problem
  with error in the process to update of SQLs. SVN Rev[2174]

* Tue Dec 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-3
- CHANGED: framework, validates that the user can maximum be a
  string of 20 characters and the use of urlencode for the
  variable $_POST['input_user']. SVN Rev[2166]
- CHANGED: elastix-firstboot: Bump version for release.
  SVN Rev[2158]
- CHANGED: Elastix logrotate, move because it must be in
  framework SVN Rev[2155].
- CHANGED: Additionals libs, move libs from additional folder
  to each specify module. SVN Rev[2152]

* Thu Dec 23 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-2
- CHANGED: Additionals libs, move libs from additional folder
  to each specify module. SVN Rev[2149]
- FIXED: paloSantoACL, name field does not support names with
  apostrophe. bug 648 fixed now name field supports the
  apostrophe. SVN Rev[2147]
- NEW:  Access log file and created logrotate.

* Thu Dec 23 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.4-1
- ADDED:   Module Security - Rulers Filtering, add lines in file
  sudoers for permit to execute commands iptables. SVN Rev[2140]
- UPDATED: Framework paloSantoConfig.class.php, Add functions
  'recuperar_archivo' and 'respaldar_archivo', used in
  Security - Rulers Filtering modules. SVN Rev[2139]
- NEW:     Framework elastix, support to log of access to web
  interface. SVN Rev[2137]
- FIXED:   framework: remove unexplained and bogus check between
  first element of current row and first element of last row.
  Fixes Elastix bug #651. SVN Rev[2131]

* Mon Dec 20 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-60
- CHANGED: Modify elastix.spec move all process "post" and "install":
  - email(cyrus-imapd, postfix, spamfilter) -> elastix-email_admin.spec
  - Hardware_detector and dahdi genconf -> elastix-system.spec
  - vsftp, tftpboot and ftp -> elastix-pbx.spec
  - hylafax, iaxmoden -> elastix-fax.spec
- NEW:     Framework elastix _list.tpl, add message in header of report,
  be able to show progress message. SVN Rev[2122]
- UPDATED: Framework _list.tpl, set color as separator "#AAAAAA" in Tr
  (themes). SVN Rev[2121]
- CHANGED: menus of 2 level have by default a height:23px in style.css
  of elastixwave (theme) SVN Rev[2112]
- DELETED: Remove all files configuration about email in additionals.
  SVN Rev[2111]
- NEW:     Files about configuration email was moved from additionals
  to setup forlder of email_admin module, these change is for better
  organization in elastix.spec. SVN Rev[2111]
- DELETED: Deleted file hardware_dectector in additionals for better
  organization of elastix.spec. SVN Rev[2110]
- ADDED:   New file hardware_detector in setup folder of system, it was
  move from additionals. SVN Rev[2110]
- DELETE: Remove files of vsftpd, xinetd.d folders and vsftpd.user_list
  file from additionals/trunk/etc, for better organization in elastix.spec
  SVN Rev[2109]
- NEW:    New files of vsftpd, xinetd.d folders and vsftpd.user_list file
  in setup/etc in modules/trunk/pbx/, now the spec of elastix.pbx use and
  required these services. SVN Rev[2109]
- DELETED: Tftpboot in additionals was delete from trunk. SVN Rev[2106]
- NEW:     Tftpboot in setup of pbx was added from trunk, it is for get
  a better organization. SVN Rev[2106]
- NEW:     New libs phpmailer. These was moved from hylafax as part
  of framework libs. SVN Rev[2105]
- CHANGED:  Change includes in files function.php (hylafax/bin/include)
  where the include has a lib phpmailer old, now this lib was in
  /var/www/html/libs. SVN Rev[2104]
- FIXED:   Framework: remove useless redundant download headers.
  Fixes issue of XLS export not downloadable under IE8. SVN Rev[2097]
- FIXED:   Framework paloSantoForm.class.php, Parameter ONCHANGE for
  type select field bad format definition. SVN Rev[2096]
- CHANGED: Module faxnew, Fixed Hard to see Bug  (H2C Bug), on
  paloSantoFax.class.php _deleteLinesFromInittab  MUST be called using
  $devId instead $idFax. Code Improvement, class paloSantoFax.class.php,
  a new function called  restartFax() was created.
  www.bugs.elastix.org [#607]. SVN Rev[2089]
- CHANGED: additional paloSantoFax.class.php, move it library to
  modules - fax, it is for better organization in elastix.spec.
  SVN Rev[2081]
- CHANGED: additional hylafax, Move folder hylafax to modules - fax,
  it is for better organization for spec files. SVN Rev[2073]
- FIXED:   Monitoring: the context variable MEETME_RECORDINGFILE stores
  the name of the conference recording, if one exists, and should be
  assigned to cdr.userfield. SVN Rev[2063]

* Mon Dec 06 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-59
- CHANGED: Remove Prereq: freePBX, RoundCubeMail, iaxmodem, hylafax,
  asterisk, wanpipe-util, openfire in this SPEC file
- CHANGED: Remove Prereq: elastix from spec file, since this module
  does not actually use any files from the Elastix framework, and
  also to remove a circular dependency with elastix package.
  SVN Rev[2052]
- NEW: Additionals paloSantoCDR.class.php, New functions getParam
  y getNumCDR, this will help changes of grids to obtain the amount
  of registers. SVN Rev[2045]
- FIXED: Framework paloSantoGrid.class.php, fixed problem about
  download report as SPREAD SHEET nd CSV when the name of file had
  spaces, this fixed with concat the name of file in the header html.
  SVN Rev[2041]
- ADDED: framework: enhance getTrunkGroupsDAHDI() to attempt to
  parse dahdi configuration files if Asterisk AMI is not available
  or does not support "dahdi show channels group N".
  Required for Elastix 1.6.x. SVN Rev[2038]
- FIXED: Escape ampersand in admin password since the ampersand
  is a special character for sed. Should fix Elastix bug #598.
  SVN Rev[2013]
- CHANGED: massive search and replace of HTML encodings with the
  actual characters. SVN Rev[2003]
- REMOVED: framework: remove images/pie_dist.php. Its only user
  (Destination Distribution) switched to generating the graphic
  internally in commit 1980. SVN Rev[1981]
- REMOVED: remove images/plot.php as nobody is using it and is
  an information exposure vuln. Modules sysinfo/dashboard already
  use different methods for displaying CPU usage. SVN Rev[1978]
- REMOVED: remove images/pie.php as nobody is using it. Modules
  sysinfo/dashboard already use different methods for displaying
  disk usage. SVN Rev[1977]
- REMOVED: remove libs/palosantoGraph.class.php and
  libs/paloSantoGraphImage.php . This mechanism of generating
  graphics is badly designed and a security bug. All users of
  these files have already been migrated to
  libs/paloSantoGraphImage.lib.php. SVN Rev[1976]
- REMOVED: remove images/bar.php as it is broken and nobody is
  using it. SVN Rev[1975]
- DELETED: Módulo sysinfo, el módulo sysinfo es obsoleto para
  elastix 2.0. Este fue quitado del framework elastix 2.0.
  SVN Rev[1972]
- CHANGED: framework: obey "menu" from $_POST as well as from
  $_GET for module selection[1996]
- ADDED: introduce palosantoGraphImage.lib.php, a somewhat
  compatible replacement for the palosantoGraph/palosantoGraphImage
  method of generating graphics. SVN Rev[1964][1969]

* Fri Nov 19 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-58
- FIXED: Additionals: Fix regression from commit 1950 that
  reenabled kernel updates unintentionally via yum. The proper
  syntax for exclude is to list several packages in one line,
  not to insert multiple exclude lines. Fixes Elastix bug #595.
  SVN Rev[1991]

* Mon Nov 15 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-57
- FIXED: Date/Time: tweak command to set date to redirct any errors
  to stdout. Also display lines of output from command with implode,
  as $output is an array. With this, error messages are now shown
  properly. Part of fix for Elastix bug #584. SVN Rev[1960]
- FIXED: Date/Time: use methods load_language_module and _tr from
  Elastix framework. Should make module more resistant to missing
  i18n strings. Part of fix for Elastix bug #584. SVN Rev[1958]

* Mon Nov 15 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-56
- FIXED:   paloSantoForm: conditionally define internal functions,
  so that method fetchForm() may be called multiple times. SVN Rev[1952]
- UPDATED: CentOS-Base.repo was updated. This changes get to update rpm of
  redhat-logos. The solution was the line exclude=redhat-logos in repo file.
  SVN Rev[1950]

* Fri Nov 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-55
- FIXED: paloSantoForm: fix copy-paste-propagated typo: $arrVals-->$arrVars
         paloSantoForm: fix typo in sprintf template $s-->%s. SVN Rev[1949]
- CHANGED: improve functions load_language() and load_language_module()
  so that they can cope with missing language other than 'en',
  and with incomplete main/module translations. It is in misc.lib
  SVN Rev[1942]
- FIXED: make module aware of url-as-array in paloSantoGrid.
     Split up URL construction into an array.
     Assign the URL array as a member of the $arrGrid structure.
     Remove <form> tags from the filter HTML template. They are not
      required, since the template already includes a proper <form>
      tag enclosing the grid.
     Run htmlspecialchars through additional template variables assigned
      in the module.
     Part of fix for Elastix bug #572. Requires commits 1901 and 1902
      in order to work properly.
  SVN Rev[1918]
- FIXED: grouplist: return to main group listing if specified group ID
  for viewing/editing is invalid. Part of fix for Elastix bug #572.
  SVN Rev[1917]
- FIXED: clean up the code for paloForm::fetchForm method. In the
  process, remove a number of opportunities for XSS by escaping
  form values with htmlentities(). Part of fix for Elastix bug #572.
  SVN Rev[1911]
- FIXED: framework: Add support in paloSantoGrid::fetchGridHTML()
  for an $arrGrid['url'] of type Array with variable name as key and
  variable value as array value. This allows the method to properly
  escape URL variables and build an URL string with construirURL().
  For backwards compatibility, 'url' is still allowed to be of type
  String. Part of fix for Elastix bug #572. SVN Rev[1902]
- FIXED:   Messages of warning and errors appear in each module
  when had and error but the button dismiss do not work.
  SVN Rev[1900]
- FIXED: paloSantoACL: add definitions for string constants that
  were used without being defined. SVN Rev[1899]

* Mon Nov 08 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-54
- CHANGED: In Spec File change this: [0-9a-zA-Z._-/]* by
  /usr/java/j2sdk1.4.2_07. It is a replace in
  /tftpboot/GS_CFG_GEN/bin/encode.sh

* Fri Nov 05 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-53
- FIXED:   elastix-dbprocess is more generic, the message changed
  to updating database. SVN Rev[1890]

* Fri Oct 29 2010 Edaurdo Cueva <ecueva@palosanto.com> 2.0.0-52
- FIXED:remove the line version=1.3.0-4, where this line was only
  a proof. SVN Rev[1886]

* Fri Oct 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-51
- FIXED: Syntax error in elastix-dbProccess. SVN Rev[1883]

* Fri Oct 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-50
- FIXED:   Fixed bug where variable path was passed in function
  obtenerClaveConocidaMySQL (line 728 function generarDSNSistema).
  SVN Rev[1882]

* Fri Oct 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-49
- CHANGED: In elastix-dbProcess before the executeFiles_SQL_update
  function received as one of arguments a string, now this string
  has been replaced by a file. SVN Rev[1881]

* Fri Oct 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-48
- CHANGED: The change that took place in the setup_dbprocces file,
  change the function executeFiles_SQL, and now there are 2 functions:
     1) executeFiles_SQL_install
     2) executeFiles_SQL_update
  SVN Rev[1873]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-47
- DELETE:  Remove migrationFilesMonitor.php, Now it is in elastix-pbx
  and change the spec file for that. SVN Rev[1865]
- CHANGED: FIXED:    Output in maillog.log about SQUAT failed to open
  index file. It was fixed in cyrus.conf with:
  squatter cmd="squatter -r *" period=15 where create index for
  mailbox for more details see in "man squatter". SVN Rev[1863]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-46
- NEW:     New script to update packages.
  It is in /usr/share/elastix/migrationFilesMonitor.php
  in additionals to 1.6 and 2.0. SVN Rev[1862]
- FIXED:   Restrict range of special characters accepted as valid in passwords.
  Should fix Elastix bug #462. SVN Rev[1861]
- FIXED:   Updated the Bulgarian language elastix both version 1.6 as 2.0.
  SVN Rev[1856]
- UPDATED: Update content of softphone. SVN Rev[1851]

* Wed Oct 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-45
- NEW:     New file /in usr/share/elastix/migrationFilesMonitor.php. This
  file is for migrating to monitoring audio files to the database
  asteriskcdrdb. SVN Rev[1862]
- CHANGED: Spec file was add new file "migrationFilesMonitor.php" in
  additionals

* Mon Oct 18 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-44
- FIXED:   Some functions were added to the file-DBPROCESS elastix, these functions
  update the databases of packages in elastix. SVN Rev[1847].
- FIXED:   postfix configuration support in migration from 1.6 to 2.0.
  See in http://bugs.elastix.org/view.php?id=490  SVN Rev[1837-1838-1839-1840]
- FIXED:   Removed audio.php and popup.php in libs to fixed security bug.
  [#552]   SVN Rev[1829]
- FIXED:   Fixed security bug with audio.php and popup.php where an user can be
  download files system without authentication by url. [#522] SVN Rev[1829]
- FIXED:   copyright were changed in all themes. SVN Rev[1827]
- CHANGE:  Updated french language. SVN Rev[1825].
- ADDED:   Added new function to paloSantoGrid.class.php for knowing if there is a
  request with action export to PDF, CSV o SPREADSHEET. SVN Rev[1824]

* Tue Oct 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-43
- FIXED:   use generic-cloexec for network restart, as "service network restart"
  may start daemons of its own in DHCP mode. Rev SVN[1809]
- FIXED:   Change the files.lang, Corresponding to language lang Persia, they sent
  me some files and exchange with those in the SVN. SVN Rev[1791]
- FIXED:   In function obtenerClaveConocidaMySQL in misc.lib has a parameter $ruta_base
- CHANGED: Added option text mode and html mode in action version of packages
  in all themes and base.js bugs.elastix.org[#57] SVN Rev[1784]
- CHANGED: Added option text mode and html mode in action version of packages
  in all themes. bugs.elastix.org[#57] SVN Rev[1783]
- DELETED: function wlog in class paloSantoPDF.class.php. SVB Rev[1782]
- CHANGED: Added new labels text mode and html mode to use in action version.
  bugs.elastix.org [#57]. SVN Rev[1781]
- FIXED:   Renamed operator to operator in the System menu in groups.
  elastixbugs(#525) Rev SVN[1778]
- CHANGED: New information in script versionPaquetes.sh about version of postfix,
  openfire, kernel. Bugs.elastix.org[#57]. SVN Rev[1771]

* Wed Sep 29 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-42
- FIXED:   Fixed some errors in the process to update menus with their order in elastix web.
  SVN Rev[1767]

* Tue Sep 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-41
- CHANGED:     New function where update all menus including the order of menus to solve
  the problem elastix 1.6 to 2.0. SVN Rev[1765], SVN Rev[1766]

* Tue Sep 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-40
- CHANGED:  New image loading.gif to show version of packages. Rev[1761]

* Mon Sep 27 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-39
- FIXED:    paloSantoTrunks: Do not reference $this outside of object context. Fixes Elastix bug #488. SVN Rev[1760]
- FIXED:    clean up stale record from table acl_membership in acl.db. Part of fix for Elastix bug #515. SVN Rev[1758]
- FIXED:    Bug fixed. Comand rpmq use CPU in 100%. bugs.elastix.org[#498] SVN Rev[1752]
- NEW       New libs was added, paloSantoJSON.class.php, JSON.php. This lib can be used to send and get message in JSON Format. SVN Rev[1751]
- CHANGED   In base.js exist a new function to response to the server, this response is in JSON format. SVN Rev[1751]
- FIXED:    Fix the auto resized columns. On this occasion the default is A3 paper. SVN Rev[1746]

* Wed Sep 15 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-38
- ADDED:   Added new script versionPaquetes.sh in Spec.

* Tue Sep 14 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-37
- CHANGED: Change some definions in templates _list.tpl to support export reports in PDF Files, spread sheets and CSV. Rev[1745]
- CHANGED: New labels for version name of installed packages. Rev[1744]
- NEW:     New Script obtain the version of packages in elastix 2. Rev[1743]
- ADDED:   New function in misc lib where can obtain the version of installed packeges in elastix. Rev[1742]
- NEW:     Add images used for generate reports. Rev[1739]
- NEW:     PDF support in Framework Elastix for reports in PaloSantoGrid with new library as paloSantoPDF.class.php. Rev[1738]
- FIXED:   fix typo in Elastix password screen. Rev[1727]

* Fri Aug 20 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-36
- FIXED: Ensure everything in /etc/init.d/ is executable. Rev[1720]
- FIXED: Also set password on files in /etc/asterisk/ that had copies of the FreePBX database password. Rev[1715]

* Wed Aug 18 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-35
- ADDED: Script path in spec elastix. /etc/init.d/generic-cloexec and /usr/sbin/close-on-exec.pl

* Wed Aug 18 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-34
- ADDED: introduce procedure by which open file descriptors from web server are closed before starting a daemon. This prevents hylafax, iaxmodem, and other daemons from holding HTTP[S] ports open, thus preventing httpd from restarting successfully. See http://bugs.php.net/bug.php?id=38915 for explanation. Rev[1696]
- FIXED: Work around PHP bug (forget to close httpd file descriptors on PHP fork()) for the case of openfire restart. Requires SVN commit #1696. Rev[1705]
- FIXED: Work around PHP bug (forget to close httpd file descriptors on PHP fork()) for the case of hylafax/iaxmodem restart. Requires SVN commit #1696.Rev[1697]
- FIXED: fix typo in network restart. Rev[1706]

* Thu Aug 12 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-33
- ADDED:     set FreePBX database password along with the other passwords, and update /etc/amportal.conf accordingly. Rev[1686]
- CHANGED:   PaloSantoNavigator was improved in new Function for JQuery libs due to this lib were included when output is a modules but not when was not it. Rev[1692]
- CHANGED:    Some modifications about the style of main menu in theme elastixwave. Rev[1690]

* Sat Aug 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-32
- FIXED: use "core show channels" instead of "show channels" to sample active channels for channel usage. Required by Asterisk 1.6.2.x. Should fix Elastix bug #429.
- UPDATED: Update content about Zoiper in extra seccion of elastix.
- FIXED: handle install in active system as dependency install by writing default legacy password to /etc/elastix.conf.

* Wed Jul 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-31
- NEW:     It implements the logic for the update. This logic is a begin because need to add more algorithms to determine the current version to version you are upgrading. Rev[1645]
- CHANGED: Add explanation text for prompts and screen numbers. Rev[1639]
-          chown 600 asterisk.asterisk for /etc/elastix.conf. Rev[1639]
-          The look of theme elastixwave was improved. Rev[1641]
- REMOVED: Password setting for sugarcrm no longer necessary. Rev[1622]

* Fri Jul 23 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-30
- FIXED: Removed dependence elastix-sugarcrm.

* Fri Jul 23 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-29
- NEW:  Compatibility for updates where /etc/elastix.conf is not available for get root passwd default.
- FIXED: The error variable in class paloSantoACL.class.php was fixed.
- CHANGED: String connection database as root in lib paloSantoInstaller.class.php.

* Fri Jul 23 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-28
- NEW: Script elastix-dbprocess to administratation database install, update and delete. But the process update and delete didn't implementacion yet.
- NEW: Functions in misc.lib.php obtenerClaveConocidaMySQL and generarDSNSistema for centralized de password database with the file /etc/elastix.conf
- FIXED: Bug lib paloSantoMenu.class.php, the function deleteFather improved.

* Wed Jul 14 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-27
- FIXED: Validation XHTML in main elastix theme support(elastixwave). Improve XHTML compliance.

* Mon Jun 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-26
- FIXED: paloSantoGraphImage.php - Add validation for session and module permissions, and check that class name is a valid PHP identifier.

* Mon Jun 28 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-25
- UPDATE:  Upgrade jquery libs and like part of framework.
- FIXED:   bug [261] bugs.elastix.org  GrandStream provisioning Error was solved change some lines in spec file to replaces the correct paths.

* Thu Jun 17 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-24
- Fixed bug in configs/default.conf.php where close tab php "?>" was not there

* Mon Jun 07 2010 Eduardo Cueva <ecueva@palosanto.com> 2.0.0-23
- Add new function in palosantoform for design of tables
- Support method BRI over OpenVox B200P

* Wed May 05 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-22
- Upload code lcdelastix to SVN elastix code.
- Fixed mayor bug, CVE-2010-1492, Directory traversal vulnerability.

* Thu Apr 15 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-21
- Fixed minor bug in framework elastix.
- Look elastix was improved.

* Mon Apr 05 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-20
- Fixed bug, include script elastix-menuremove.

* Fri Mar 26 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-19
- Fixed bug number port cyrus-imapd 2000 to 4190 file /etc/cyrus.conf. This is http://bugs.elastix.org/view.php?id=256 and
  Bug#559923: avelsieve: Default configuration should specify Sieve port 4190.

* Fri Mar 19 2010 Eduardo Cueva D <ecueva@palosanto.com> 2.0.0-18
- Lib paloSantoGraphImage was fixed with the defaul color of pie charts pictures.
- New var language Error, that var had never been defined.
- Solution with index.php when a user into elastix and load the first module without the javascript libraries into the HEAD.
- Change in hardware detector in lib, now this module check if chan_dahdi_additional.conf exits, if not is true it is create.

* Wed Mar 17 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-17
- Support for library and style from modules, denifition HEADER_MODULES in index.php and index.tpl.

* Tue Mar 16 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-16
- Update framewok support native jquery step beta.

* Mon Mar 01 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-15
- Update look elastix version rc.

* Wed Feb 10 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-14
- Fixed bug, JAVA_PATH in endpoint configurator greandstream phone. The solution is a sed after unpackage for replace JAVA_PATH.

* Tue Jan 19 2010 Bruno Macias V <bmacias@palosanto.com> 2.0.0-13
- Fixed bug, in freepbx 2.6 trunks now have a own table in database asterisk. (PaloSantoTrunk.class.php fixed)
- framework elastix, improved action rawmode for output only code, now use function getParameter.
- Function getParamater now is part of framework elastix, the getParameter function was removed in each module, now this function is in misc.lib.php
- Fixed bug in navigation menus en web interface, losed the url.
- New improve in look elastix.


* Wed Dec 30 2009 Bruno Macias V <bmacias@palosanto.com> 2.0.0-12
- Fixed bug in group permission, name module sysinfo appeard yet, the module sysinfo was deleted.

* Tue Dec 29 2009 Bruno Macias V <bmacias@palosanto.com> 2.0.0-11
- New look module dasboard, this modulo replace to sysinfo module.
- Fixed minor bug in paloSantoGraphImage.php, images size

* Fri Dec 04 2009 Bruno Macias V <bmacias@palosanto.com> 2.0.0-10
- Fixed the correct url to mirrorlist for RPMs repo .
- Fixed bugs in global definitions variable $arrConf and $arrLang.
- New look elastixwave, this will be default theme.

* Fri Oct 23 2009 Bruno Macias V <bmacias@palosanto.com> 2.0.0-9
- Fixed bug, file elastix.repo url to version 2 in repos and mirrorlist.

* Fri Oct 23 2009 Bruno Macias V <bmacias@palosanto.com> 2.0.0-8
- Fixed bug, file elastix.cron  BAD FILE MODE 755 to 644
- Improved, script hardware_detector for write file chan_dahdi.conf
- Fixed bugs, module remote smtp - bad config.
- Fixed bug module hardware detector, validation ports range improved.

* Mon Oct 19 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-7
- New theme elastix, elastixblue. This theme is alpha

* Sat Oct 17 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-6
- Fixed definitions words and messages in same modules.
- Update framework elastix for support RPM install modules. This feature is to elastix 2.0.
- Validation login when a user administrator, now user will see the main menu sysinfo.
- Fixed minor bugs, definition languages and definition format variables php in module backup restore.
- Fixed bug, user of email not created by webinterface, error imap.
- Fixed bug, user spamfilter for execute the script antispam was created.
- Fixed bugs for support commands of root for user asterisk in PATH variable. This is for script hardware_detection

* Fri Sep 18 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-5
- Script for desintall menus elastix.

* Tue Sep 07 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-4
- Alpha 3 test genrated.

* Mon Sep 07 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-3
- Fixed Bug in email configuration, delete @example.com and validation in email box when not exits.
- Try new strategy for language file inclusion that tries to ensure that a string will have an English translation as a fallback if no localized string is available.
- Add more debugging information on error path. paloSantoDB.class.php

* Thu Aug 27 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-2
- Script menusAdmunElx comment, this script is obsolete for elastix 2.0.0
- Fixed bug images not found in module summary by extension.

* Wed Aug 26 2009 Bruno Macias V. <bmacias@palosanto.com> 2.0.0-1
- Version 2.0.0-1
- Script for menus and acls process elastix-menumerge.

* Thu Aug 13 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 2.0-2test
- Require newer version of wanpipe-util for hardware_detector.
- Do not mess up with vsftpd configuration anymore (inherited from
  elastix-additionals, elastix-1.6-7).
- Try to patch vsftpd configuration to restore proper behavior which
  was broken from previous versions.

* Mon Jul 27 2009 Bruno Macias <bmacias@palosanto.com> 2.0-1test
- Prueba de genracion de modulos rpms.

* Tue Jun 23 2009 Mauro Avecilla <mavecilla@palosanto.com> 1.6-5.1
- Personalizacion para Mtech.

* Tue Jun 02 2009 Bruno Macias V <bmacias@palosanto.com> 1.6-5.1
- Fixed bug with hylafax files (configs and bin files), conflict with rpm hylafax resolved.
- In paloSantoFax.class.php now defined FaxRcvdCmd keyword to use of hylafax.
- New files faxrcvd.php and faxrcvd-elastix.php to define script after process fax recived.
- Keyword FaxRcvdCmd on file config.ttyIAX* added, for avoid replace file faxrcvd.

* Mon Jun 01 2009 Bruno Macias V <bmacias@palosanto.com> 1.6-4
- Fixed some traductions not defined in files languages elastix.
- Path static in elastix now are dynamic, elastix can be relocalitation.
- Fixed bug security, files backups of elastix now are in /var/www/backup/
- Id module Email changed to email_admin, freebpx used the same id for voicemails.
- Login in web interface now permit user with piriod.
- Changed message login in console elastix.

* Tue May 26 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.6-3
- Elastix package now provides elastix-additionals as well, to ease update to new package.
- Partially revert change to unpack /tftpboot at %%install, since some files are ELF and
  generate unwanted dependencies. These files are so be served to remote clients, not
  used locally.
- Properly mark several configuration files as %%config(noreplace)

* Mon May 18 2009 Bruno Macias <bmacias@palosanto.com> 1.6-2
- Files in /tftpboot, are now installed in instalation time.
- Obsoletes elastix-additionals

* Sat May 16 2009 Bruno Macias <bmacias@palosanto.com> 1.6-1
- New structure of content tar elastix. Now have three folders: additionals, framework and modules.
- Split webinterface in modules and framework foders.
- Configuration additionals be in additionals folders.
- Specs elastix was added news implementation for delete rpm elastix-additionals.


* Tue May  5 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.5.2-2.3
- Loosen up dependency on wanpipe-util. Now only its presence is required,
  not a specific version.

* Sat Apr 25 2009 Bruno Macias <bmacias@palosanto.com> 1.5.2-2.2
- Fixed bug in validation call center parameter (module callcenter_config), the bug was in paloSantoValidator.class.php

* Fri Apr 24 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.5.2-2.1
- Do not provide a patched wancfg_zaptel.pl, since wanpipe-util-3.3.16
  is now patched to provide the changes.

* Tue Mar 31 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.5.2-2
- Do not overwrite existing httpd.conf. Instead, move most configuration
  changes to elastix.conf in /etc/httpd/conf.d and comment out User
  and Group directives so that the ones on elastix.conf take effect.
  Also, reverse commenting-out at %%preun so httpd.conf is returned to
  pre-Elastix state.
- Remove unnecessary manipulations of elastix.ini at %%post, instead place
  it at its final place in /etc/php.d/ at %%install .
- Add /etc/dahdi/genconf_parameters as standard managed file instead of
  generating it at %%post .
- Add /var/spool/hylafax/etc/FaxDictionary as standard managed file instead
  of copying it over at %%post .
- Do not change ownership of /var/www/html/* to asterisk, made unnecessary
  by %%defattr in spec.
- Attempt to restore wancfg_zaptel.pl on %%preun .

* Thu Mar 26 2009 Bruno Macias <bmacias@palosanto.com> 1.5.2-1
- Better reorganization repos elastix.
- Enabled dectection sangona cards in hardware detector web interface.

* Wed Mar 18 2009 Bruno Macias <bmacias@palosanto.com> 1.5-9
- Fixed bug when choose spanish language.
- Changed currency argentinian.

* Sat Mar 14 2009 Bruno Macias <bmacias@palosanto.com> 1.5-8
- Fixed bug in adress Book reported by Saleh Madi
- New locate languages modules in themselves.
- Fixed bug integration wiht freePBX in file pbxadmin/index.php in function module_getinfo.

* Mon Mar 09 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.5-7
- Relax dependency on wanpipe-util

* Thu Feb 26 2009 Bruno Macias <bmacias@palosanto.com> 1.5-6
  - Delete module echo canceller in web interface Elastix.
  - Standarization, languages in each module.

* Wed Feb 25 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.5-5
  - Add session.save_path override to elastix.ini. Required because
    installation changes httpd process owner to asterisk, which does
    not have write permission on /var/lib/php/session

* Mon Feb 16 2009 Alex Villacis Lasso <a_villacis@palosanto.com> 1.5-4
  - Add php-pdo to dependency list
  - Do not overwrite /etc/php.ini - just create new elastix.ini with required variable changes
  - Updated wancfg_zaptel.pl patch for wanpipe-util 3.3.15

* Fri Feb 06 2009 Bruno Macias <bmacias@palosanto.com> 1.5-3
  - Release beta3 1.5-3
  - updated kernel to 2.6.18-92.1.22.el5.
  - RPMS Sangoma created.

* Tue Feb 03 2009 Bruno Macias <bmacias@palosanto.com> 1.5-2
  - Release beta2 1.5-2

* Fri Jan 30 2009 Bruno Macias <bmacias@palosanto.com> 1.5-1
  - Release beta 1.5-1
  - Changed module billing_report zaptel by dahdi [#148]
  - Soport DAHDI
  - Asterisk 1.4.23.1

* Thu Jan 29 2009 Bruno Macias <bmacias@palosanto.com> 1.4-5
  - Fixed bug, names of folder faxes remane this in /var/www/html/faxes/
  - Changed module hardware_detector zaptel by dahdi [#148]
  - Changed module billing_report zaptel by dahdi [#148]
  - Changed module billing_setup zaptel by dahdi [#148]
  - Changed module graphic_report zaptel by dahdi [#148]
  - Changed module dest_distribution zaptel by dahdi [#148]
  - Changed module backup-restore zaptel by dahdi [#148]
  - paloSantoCDR.class.php and paloSantoTrunk.class.php implementation with dahdi [#148]
  - cron /usr/local/elastix/sampler.php implementation with dahdi [#148]

* Wed Jan 14 2009 Bruno Macias <bmacias@palosanto.com> 1.4-4
  - Version rc.
  - Fax Visor show faxes sent. [#138]

* Fri Nov 28 2008 Bruno Macias <bmacias@palosanto.com> 1.4-3
  - Version beta3.
  - Fixed bug extensions_batch, voicemails not work [#137].
  - Fixed bug not show images in freePBX embedded [#135].
  - Fixed bug CDRReport duplicated rows [#136].

* Fri Nov 28 2008 Bruno Macias <bmacias@palosanto.com> 1.4-2
  - Version beta2.
  - Extension Batch fixed bug with changed of meta data asterisk and hints in configs files sip [#129].
  - Endpoint Configuration better functionality and interaction in process network scan [#130].
  - Calendar fixed bug,not dialing an external number [#116].

* Tue Nov 10 2008 Bruno Macias <bmacias@palosanto.com> 1.4-1
  - Version beta.
  - Fixed bug with freePBX, definition GLOBAL for _guielement_tabindex, _guielement_formfields. This bug not shows the extension menu.
  - Creation new Text to Wav module, user could create your own records [#18].
  - Update help files embedded, so as the creation in missed modules [#16].
  - Hardware mISDN now detection in module hardware_detector [#53].
  - Update file wakeup.php, version 2.0 [#59].
  - New place for help files, now this files integrated with the own module. Creation folder images, help,
    Files Languages split for each module, the folder lang is now in them [#95].
  - Update languages Bulgaro, French and Persa [#94].
  - Update the content in files help embedded [#104].
  - Fixed bug in paloSantoDB, conecction to othet ip host. [#118]
  - New module graphic reports, reports by extensions, trunks and queues [#33] [#34].
  - Fixed bug in module dashboard, the resize now is constant [#86].
  - Update file config for Atcom, this is used in module endpoints_configuration [#97].
  - Changed words "Losed" by "Lost" and "segs" by "secs" in module dashboard [#98].
  - Fixed bug in paloSantoDashboars.class.php (Dashboard module), changed {localhost:143} by {localhost:143/notls} [#120].
  - Fixed bug in Extension Batch module, order the name the header and any validation [#102].
  - Fixed bug in calendar module, now update create column call_to [#100].
  - New module (extra) AvantFAX, this module not include by default [#7].
  - New interfaz, user configure your voicemails (PBX->VoiceMail) [#31].
  - Asterisk Log now have the option of search pattern words [#72].

* Tue Sep 24 2008 Bruno Macias <bmacias@palosanto.com> 1.3-2
  - Fixed bug in module address book (paging not work fine).

* Fri Sep 12 2008 Bruno Macias <bmacias@palosanto.com> 1.3-1
  - Add Prereq spamassassin for elastix rpm, this is used in module antispam.
  - In module hardware_detection now support sangoma cards, new scrip hardware_detector in /usr/sbin
  - Custom scrip wancfg_zaptel.pl, elastix defined files configs con *.wanpipe

* Fri Sep 05 2008 Bruno Macias <bmacias@palosanto.com> 1.2.1-4
  - Delete comment about faxvisor.
  - New module antispam, this spec add implementation for pre configuration, file spamfilter.sh in path /usr/local/bin
  - Fixed bug, whe update elastix the file /etc/postfix/network_table losed your content

* Mon Sep 01 2008 Bruno Macias <bmacias@palosanto.com> 1.2-4
  - Increase release for rc2.

* Thu Aug 28 2008 Bruno Macias <bmacias@palosanto.com> 1.2-3
  - Integration modules address book and Calendar, now you can generate calls to another phones.
  - Fixed bug in Roundcube, asosiate with the send attachment. Review spec roundcube.
  - Module Extension Batch add field Outbound CID and fixed bug with the field Direct DID when show null.

* Fri Aug 22 2008 Bruno Macias <bmacias@palosanto.com> 1.2-2
  - Fixed error with xajax and firefox 3.
  - Integration Elastix and Roundcube better, user and password can be changed in settings of Roundcube.

* Mon Aug 11 2008 Bruno Macias <bmacias@palosanto.com> 1.1-8
  - Change rpm freePBX, version 2.4.0.0, bug fixed.

* Tue Jul 08 2008 Bruno Macias <bmacias@palosanto.com> 1.2-1
  - new module asterisk log.
  - In hylafax script (faxrcvd) and funtion, changed conexion database to fax.db. Now is with pdo.
  - Fixed bug in paloSantoTrunk.class.php, the format for customer trunk had a "AMP:" this prefix was replace for empty, this suggestion was report by Jaume Olivé.
  - Module backup/restore add file configs of FOP.

* Wed Jun 26 2008 Adonis Figueroa <afigueroa@palosanto.com> 1.1-7
  - Module Address Book now permitt upload and download csv files.
  - All themes in elastix were changed for adaptation of modules, example call_center (agent console)
  - Help was updated to show the info of first son if it's a folder.

* Tue Jun 24 2008 Adonis Figueroa <afigueroa@palosanto.com>
  - Module Address Book was updated to report the emails in the internal directory (freepbx).
  - Fixed bug in the function _getNextAvailableDevId. This problem affected to the id of faxes
    when a fax was deleted and a new was created.
  - Module Address Book was updated to hide the column delete to internal directory.

* Mon Jun 23 2008 Adonis Figueroa <afigueroa@palosanto.com>
  - Module Address Book was updated to report the list of freepbx how internal directory and only to
    external directory you can add a register.

* Fri Jun 20 2008 Adonis Figueroa <afigueroa@palosanto.com>
  - Module monitoring was updated to change the date obtained fom file OUT.*
  - Module extensions batch was updated to support the context data when you upload a batch.
  - There was a change to manage the help with the menu from session. The file menu.php was deleted.
    Moreover the users now can see only the help for their modules, not of anothers groups.

* Wed Jun 18 2008 Bruno Macias <bmacias@palosanto.com> 1.1-6
  - Version Stable 1.1

* Tue Jun 10 2008 Bruno Macias <bmacias@palosanto.com> 1.1-5
  - Add new language japanese.
  - Update language brazilian-portuguese, romanian
  - Update module pbxadmin menus.
  - new module recordings.
  - calendar better, now recordings record.
  - update validation version elastix in menuAdministrationElastix.
  - validation in menuAdministrationElastix for new tables in acl database exists (acl_profile_properties and acl_user_profile).
  - Module User Information, better validation in account webmail not defined.

* Fri Jun 06 2008 Bruno Macias <bmacias@palosanto.com>  1.1-4
  - Version 1.1 beta initial.
  - Add Prereq php-imap in this spec necessary for module user information (handler emails).
  - Add funcionality call in module address book.
  - In module calendar add context (see spec freePBX) for active gsm reproduce, module calendar agree this funcionality call extension user for avise calendar event.
  - User Information add reports of calendar event.
  - Module load module change format xml, support 3 level menus, also change implementation paloSantoModuleXML and paloSantoInstaller.

* Fri May 30 2008 Bruno Macias <bmacias@palosanto.com>  1.1-3
  - Add Prereq mod_ssl in this spec necessary for httpd port 443 where listen elastix.
  - New funcionality, webmail integrated in elastix login.

* Tue May 27 2008 Bruno Macias <bmacias@palosanto.com> 1.1-2
  - Standarization the conexion to databases, for all modules in elastix.
  - Initial changed for acopled new frameWork Elastix.
  - Support in palosantoModuleXML for 3 level in menus.
  - PaloSantoNavigator better implementation in forms menus.
  - Version stable of module address book.
  - New FrameWork Elastix.
  - Updated language French.
  - Call Center language French updated.

* Tue May 20 2008 Bruno Macias <bmacias@palosanto.com> 1.1-1
  - Version 1.1 alpha initial.
  - Add Prereq php-mysql in this spec.
  - Add Prereq RoundCubeMail in this spec.
  - Expresion regular in module hardware_detection better.
  - New module calendar.
  - New module user information.
  - New module address book.

* Mon Apr 28 2008 Bruno Macias <bmacias@palosanto.com>  1.0-17
  - More implementation in modules billing by developer Hetii. Add function for parse zapata.configs
  - Add fields in module New Virtual fax, area and country code. This fields are required.
  - Fixed Bug in module sysinfo, now accept more formats in partitions name.(Graphical disc image)
  - Add validation in menuAdministrationElastix, exists columns country_code and area_code in database fax.db.
  - Add required rpm php-xml for elastix.

* Tue Apr 22 2008 Adonis Figueroa <afigueroa@palosanto.com>
  - Module Monitoring was changed to order by date.
  - Module Backup/Restore better interaction and better funcionality in make backup and restore.
  - Module Email - domain, fixed error for delete, modify and insert domain.

* Mon Apr 21 2008 Bruno Macias <bmacias@palosanto.com>
  - Fixed bug in billing_rates, in sqlite3 database rate.db add column trunk.
  - Fixed bug in Virtual Fax List, in palosantoFax.class.php fix validacion is_array to isset.
  - Updated language Persian.
  - In file menuAdministrationElastix add validation, alter table rate add column trunk TEXT;

* Wed Apr 19 2008 Bruno Macias <bmacias@palosanto.com> 1.0-16
  - Fixed Bug module monitoring, add new formats files.
  - New themes for web interface: al, slashdot and giox.
  - Modules billing_report and billing_rates better implementation do hetii (developer sourceforge). Before the rates are assosiated only prefix, Now the rates are assosiated with prefix and trunks.

* Wed Apr 09 2008 Bruno Macias <bmacias@palosanto.com> 1.0-15
  - This spec comment lines of create folder faxvisor, this folder is in modules elastix.
  - New language Catalan.
  - Update module Hardware Detection, now zapata.conf is more complete.

* Fri Apr 04 2008 Adonis Figueroa <afigueroa@palosanto.com>
  - Module Extension Batch changed to support more parameters of VoiceMail.
  - Module GroupPermissions: Do not permit change the permissions of modules administratives to administrator group.

* Wed Apr 01 2008 Adonis Figueroa <afigueroa@palosanto.com> 1.0-14
  - Module Voicemail was changed to make the reports faster, and the admin can view all extensions.
  - Module CDRReport was changed, the users can view only their reports and the admin can view all reports.
  - Module monitoring was changed to make the reports faster.
  - Language Bulgarian updated.

* Wed Mar 26 2008 Bruno Macias <bmacias@palosanto.com> 1.0-13
  - Add language swedish.
  - Add words language for module Reports.
  - Help embedded updated.

* Tue Mar 25 2008 Bruno Macias <bmacias@palosanto.com> 1.0-12
  - Module cdrreport (Reports) add botton delete register.
  - Add Prereq elastix-sugarcrm

* Wed Mar 19 2008 Bruno Macias <bmacias@palosanto.com> 1.0-11
  - New dependency php-pear-DB and new rpm php-pear-DB..
  - Maintenaince of folder otherFiles/pear
  - Fixed warnning in the modules sources (maintenaince).
  - Files vtigerWrapper.php, schema.vtiger, sugarcrmWrapper.php and schema.sugarcrm move in rpms elastix-vtigercrm and elastix-sugarcrm.

* Wed Mar 19 2008 Bruno Macias <bmacias@palosanto.com> 1.0-10
  - New module extensions_batch
  - Fixed warnning and notices in source of modules. Better handler declared variables.
  - File Editor bug of seguridad file, fixed.

* Tue Mar 18 2008 Bruno Macias <bmacias@palosanto.com> 1.0-9
  - Maintenaince: /tmp/ replace for /usr/share/elastix/tmp/ suguest for zafiri
  - Also comment of funcionality old  deleted.
  - The elastix-1.0-9.tar.gz update menus ok.
  - Error and output standar handler in section Administration Menus and permission.

* Mon Mar 03 2008 Bruno Macias <bmacias@palosanto.com> 1.0-8
  - Add language Persian.
  - Finish implementation theme elastixwine

* Fri Feb 22 2008 Bruno Macias <bmacias@palosanto.com> 1.0-7
  - Add this spec Prereq nmap for module endpoints_configuration.
* Thu Feb 21 2008 Bruno Macias <bmacias@palosanto.com> 1.0-6
  - Fixed bug in telnet for atcom 320.
  - Module faxlist now show the ttyIAX number.
* Wed Feb 20 2008 Bruno Macias <bmacias@palosanto.com> 1.0-5
  - Module Conferences finish, add action kick all and invite caller, View number person in the conferences.
  - Module Endpoint Configuration, atcom provionality finish (model AT 320 and AT 530).
    Lynksys 841 ok provisional.
    Add filter for mask in subnet.
* Mon Feb 11 2008 Bruno Macias <bmacias@palosanto.com> 1.0-4
  - Add wrapper for finish instalation to module conference.
  - Module themes_system add funcionality of refresh smarty templates_c
  - Module endpoints_configuration add validation when the devices aren't created.
  - Add schema meetme in /var/www/html.
* Sat Feb 09 2008 Bruno Macias <bmacias@palosanto.com> 1.0-3
  - New Theme for elastix (elastixwine).
  - Add in spec freepbx resources (patch) for correct funcionality of modules call_center and conferences.
  - New module conferences
  - palosantoForm add field checkbox.
* Thu Feb 07 2008 Bruno Macias <bmacias@palosanto.com> 1.0-2
  - Version Alpha the elastix 1.0
  - Better funcionality module Load Module, this make for the module call center.
  - Add new words in the langs.
  - Better the frameWork paloSantoInstaler and paloSantoQueue
  - Note: In spec the freePBX 2.3.1.29 add patch for that function module call center.
  - Add validation in module Endpoint configuration. And better structure the file CFG of the endpoints.
* Wed Jan 30 2008 Bruno Macias <bmacias@palosanto.com> 1.0-1
  - Version alpha the elastix 1.0
  - New module DHCP Server
  - New module Endpoint Configuration
  - Add language hungarian.
  - Update language french.
  - New organization menu Network (Network Parameters and DHCP Server)
  - New functionality File Editor, add file and better search.
  - Include zapata-channels.conf and zapata_additional.conf in zapata.conf
* Tue Dec 26 2007 Bruno Macias <bmacias@palosanto.com> 0.9.2-4
  - Add funcionality delete voicemails in module voicemails.
  - Add funcionality delete faxes pdf, in module fax visor.
  - Backup Restore fixed bug include palosantoFax.
  - Order desc the pdfs fax in module fax visor.
* Tue Dec 18 2007 Bruno Macias <bmacias@palosanto.com> 0.9.2-3
  - New funcionality in module file editor, now this files order by name and can be search by name file.
* Mon Dec 17 2007 Bruno Macias <bmacias@palosanto.com> 0.9.2-2
  - Fixed format valid for emails and domain in module Email.
* Fri Dec 14 2007 Bruno Macias <bmacias@palosanto.com> 0.9.2-1
  - Add new language croata.
  - Fixed palosantoValidator, format valid regular expresion that valid emails, domain.
  - Add functionality hardware detection, now will be replaced zapata.conf file for personal file zapata elastix.
  - Fixed bug listen recordings and voicemails.
  - New order menus in the table. Tables affecct menu (menu.db) and acl_resorces (acl.db)
* Mon Dec 4 2007 Edgar Landivar <elandivar@palosanto.com> 0.9.1-4
  - Removing elastix-vtigercrm dependency
* Mon Dec 3 2007 Edgar Landivar <elandivar@palosanto.com> 0.9.1-3
  - The elastix-vtigercrm package was referenced in a bad way
* Mon Dec 3 2007 Edgar Landivar <elandivar@palosanto.com> 0.9.1-2
  - Change to handle better the fact that freepbx disable some modules that need upgrade
* Fri Nov 23 2007 Bruno Macias <bmacias@palosanto.com> 0.9.1-1
  - New module Fax-TemplateEmail for configuration data remitente and mail format. New lenguage for this module.
  - New module User Management-Group List for create new group user. New lenguage for this module
  - Add funcionality for module PBX-monitoring, now be can delete recordings.
  - Changes button name Activate by Accept. In module repositories the updates.
  - Update help Elatix embedded web interface.
  - Change link menu openfire {IP_SERVER} by {NAME_SERVER}. Add funtion for get name server in palosantoNavigator.
  - Replace old menu ports_details for hardware_detection in menu.db.
* Wed Nov 21 2007 Bruno Macias <bmacias@palosanto.com> 0.9.0-18
  - Delete menus Backup, Restore and Backup List this menus obsolete. (Add functionality of this in seccion Administration)
* Tue Nov 20 2007 Bruno Macias <bmacias@palosanto.com> 0.9.0-17
  - Fixed bug in recording, add functionality for confguration record incoming or outgoing to always.
    Users administrator can see all recordings, the others user only yours.
    Change the groupd get database.
    This change will shows a gray bar when at least one freePBX module is disabled.
* Mon Nov 19 2007 Bruno Macias <bmacias@palosanto.com> 0.9.0-16
  - Update language bulgaro an fixed bug with the "Apply Changes" bar in the PBX menu
* Tue Nov 13 2007 Bruno Macias <bmacias@palosanto.com> 0.9.0-15
  - Update menus elastix and permission, funcionality better (this spec seccion Administration Menus and permission) and update of fax.db. Prereq elastix-vtigercrm
* Tue Nov 13 2007 Bruno Macias <bmacias@palosanto.com> 0.9.0-14
  - Update menus elastix and permission, funcionality better (this spec seccion Administration Menus and permission)
* Thu Nov 8 2007 Adonis Figueroa <afigueroa@palosanto.com> 0.9.0-13
  - Framed Spark in downloads section
* Wed Nov 7 2007 Adonis Figueroa <afigueroa@palosanto.com> 0.9.0-12
  - About Update, Version and Release
* Thu Nov 6 2007 Edgar Landivar <elandivar@palosanto.com> 0.9.0-10
  - Updated Prereq to freepbx 2.3.1-7
* Thu Nov 1 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-9
  - Added wrapper for openfire, start service and  /sbin/chkconfig --level 2345 openfire on. elastix-0.9.0-9.tar.gz .
* Wed Oct 31 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-8
  - Added wrapper for vtiger create database elastix-0.9.0-8.
* Tue Oct 30 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-7
  - Added new menus in the help link package elastix-0.9.0-7.
* Mon Oct 29 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-6
  - Changes in freePBX version 2.3 and inteface web freePBX is dual operation correction error.
* Fri Oct 26 2007 Bruno Macias   <bmacias@palosanto.com> 0.9.0-5
  - Changes in freePBX version 2.3 and inteface web freePBX is dual operation, standar format the version rpms.
* Thu Oct 25 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-4
  - Add Link version Elastix, changes in the module Backup in this version elastix-0.9-4.tar.gz
* Mon Oct 22 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-3
  - Add new modules and better funcionality in this version elastix-0.9-3.tar.gz
* Mon Oct 22 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-2
  - Add new modules, changes order in menus in this version elastix-0.9-2.tar.gz
* Wed Oct 19 2007 Bruno Macias   <bmacias@palosanto.com> 0.9-1
  - Add new modules in this version elastix-0.9-1.tar.gz
* Tue Oct  9 2007 Edgar Landivar <elandivar@palosanto.com> 0.9.0-1
  - Hylafax changes removed. These changes should be made in the hylafax RPM.
