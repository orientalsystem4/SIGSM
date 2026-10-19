FROM php:8.0-apache

# Habilitar el módulo rewrite de Apache para el enrutamiento MVC
RUN a2enmod rewrite

# Instalar las extensiones de base de datos necesarias para MariaDB
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Redirigir el root de apache al subdirectorio SIGSM
RUN echo '<?php header("Location: /SIGSM/"); exit; ?>' > /var/www/html/index.php

# Configurar el directorio de trabajo para que coincida con la ruta local
WORKDIR /var/www/html/SIGSM
