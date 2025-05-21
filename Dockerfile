# PHP 기반 이미지 사용
FROM php:8.2-apache

# Composer 설치
RUN apt-get update && apt-get install -y unzip curl
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 워킹 디렉토리 설정
WORKDIR /var/www/html

# 프로젝트 파일 복사
COPY . /var/www/html

# 필요한 PHP 확장 설치 (PDO, MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# AWS SDK 및 기타 의존성 설치
RUN composer install

EXPOSE 80

# 아파치 실행
CMD ["apache2-foreground"]
