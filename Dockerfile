FROM rockylinux:9

LABEL org.opencontainers.image.authors="Thomas Urban <ThomasUrban@urban-software.de>"

# If this is a standalone Cacti, uncomment the following line
# EXPOSE 80 443

# Expose the MySQL Port as well when this is a MASTER Cacti
EXPOSE 80 443 3306

## --- ENV ---
ENV \
    DB_NAME=cacti \
    DB_USER=cactiuser \
    DB_PASS=cactipassword \
    DB_HOST=localhost \
    DB_PORT=3306 \
    RDB_NAME=cacti \
    RDB_USER=cactiuser \
    RDB_PASS=cactipassword \
    RDB_HOST=localhost \
    RDB_PORT=3306 \
    CACTI_URL_PATH=cacti \
    BACKUP_RETENTION=7 \
    BACKUP_TIME=0 \
    REMOTE_POLLER=0 \
    CACTI_MASTER_IP=127.0.0.1 \
    INITIALIZE_DB=0 \
    TZ=UTC \
    PHP_MEMORY_LIMIT=800M \
    PHP_MAX_EXECUTION_TIME=60 \
    PHP_SNMP=0

CMD ["/start.sh"]

## --- Start ---
COPY start.sh /start.sh

## --- SUPPORTING FILES ---
COPY cacti /cacti_install

## --- SERVICE CONFIGS ---
COPY configs /template_configs
COPY configs/crontab /etc/crontab

## --- SETTINGS/EXTRAS ---
COPY plugins /cacti_install/plugins
COPY templates /templates
COPY settings /settings

## --- SCRIPTS ---
COPY upgrade.sh /upgrade.sh
COPY restore.sh /restore.sh
COPY backup.sh /backup.sh

## --- UPDATE OS, INSTALL EPEL, PHP EXTENTIONS, CACTI/SPINE Requirements, Other/Requests ---
RUN \
    chmod +x /upgrade.sh && \
    chmod +x /restore.sh && \
    chmod +x /backup.sh && \
    mkdir /backups && \
    mkdir /cacti && \
    mkdir /spine && \
    dnf update -y && \
    dnf install -y epel-release && \
    dnf install -y dnf-utils http://rpms.remirepo.net/enterprise/remi-release-9.rpm && \
    dnf install -y dnf-plugins-core && \
    dnf config-manager --set-enabled crb && \
    dnf -y module reset php  && \
    dnf -y module enable php:remi-8.0  && \
    dnf install -y \
    php php-xml php-session php-sockets php-ldap php-gd \
    php-json php-mysqlnd php-gmp php-mbstring php-posix \
    php-snmp php-intl php-common php-cli php-devel php-pear \
    php-pdo && \
    dnf install -y \
    rrdtool net-snmp net-snmp-utils cronie mariadb autoconf \
    bison openssl openldap mod_ssl net-snmp-libs automake \
    gcc gzip libtool make net-snmp-devel dos2unix m4 which \
    openssl-devel mariadb-devel sendmail wget help2man perl-libwww-perl && \
    dnf clean all && \
    rm -rf /var/cache/yum/* && \
    chmod 0644 /etc/crontab && \
    echo "ServerName localhost" > /etc/httpd/conf.d/fqdn.conf && \
    /usr/libexec/httpd-ssl-gencerts
