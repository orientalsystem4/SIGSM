FROM php:8.0-apache

# Habilitar el módulo rewrite de Apache para el enrutamiento MVC
RUN a2enmod rewrite

# Instalar las extensiones de base de datos necesarias para MariaDB
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Redirigir el root de apache al subdirectorio SIGSM
RUN echo '<?php header("Location: /SIGSM/"); exit; ?>' > /var/www/html/index.php

# Configurar el directorio de trabajo para que coincida con la ruta local
# Permite los PDF de hasta 15 MB aceptados por el formulario.
RUN printf 'upload_max_filesize=15M\npost_max_size=20M\n' > /usr/local/etc/php/conf.d/subidas.ini

WORKDIR /var/www/html/SIGSM
