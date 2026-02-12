FROM php:8.1-cli

# Install PostgreSQL client and PDO driver
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000"]
