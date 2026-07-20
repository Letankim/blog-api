# Sử dụng image PHP 8.2 có sẵn Apache
FROM php:8.2-apache

# Cài đặt các thư viện hệ thống cần thiết và extension của PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Bật module rewrite của Apache (rất quan trọng cho Slim Framework / định tuyến)
RUN a2enmod rewrite

# Cập nhật thư mục gốc (DocumentRoot) của Apache trỏ vào thư mục public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cài đặt thư mục làm việc
WORKDIR /var/www/html

# Copy Composer từ image chính thức của Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy toàn bộ mã nguồn vào container
COPY . .

# Phân quyền cho các thư mục lưu trữ/logs để ứng dụng có thể ghi log
RUN mkdir -p storage/logs logs
RUN chmod -R 777 storage logs public/uploads

# Chạy lệnh composer install để cài các package
RUN composer install --no-dev --optimize-autoloader

# Khai báo port 80 cho Render biết
EXPOSE 80
