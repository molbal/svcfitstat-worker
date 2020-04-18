FROM centos:7

# Getting Python for Pyfa
RUN yum install -y https://centos7.iuscommunity.org/ius-release.rpm
RUN yum update -y
RUN yum install -y python36u python36u-libs python36u-devel python36u-pip
RUN yum install -y git

# Application requirements
RUN pip3 install pathlib2
RUN git clone https://github.com/molbal/Pyfa pyfa
RUN yum install -y gcc-c++
RUN yum install -y gtk3 gtk3-devel
RUN yum install -y which make
RUN echo $LD_LIBRARY_PATH
RUN cd pyfa && pip3 install -r requirements.txt

# Copy the Pyfa database
COPY eve.db pyfa/

# Install the package to simulate a display for Pyfa
RUN yum install -y xorg-x11-server-Xvfb

# Install Apache for serving requests
RUN yum install -y httpd 

# Install PHP 7.4 for the wrapper
RUN rpm -Uvh http://rpms.remirepo.net/enterprise/remi-release-7.rpm
RUN yum install -y yum-utils
RUN yum update
RUN yum-config-manager --enable remi-php74
RUN yum install -y php php-opcache

# Copy the wrapper files
COPY index.php /var/www/html
COPY phpinfo.php /var/www/html

# Clean up
RUN yum clean all

# Expose port 80
EXPOSE 80

# Update Pyfa files
RUN cd pyfa && git pull

# Enable the Apache process to launch Pyfa properly
RUN cd pyfa && chmod 777 .

# Specify the Docker container main process
ENTRYPOINT ["/usr/sbin/httpd","-D", "FOREGROUND"]