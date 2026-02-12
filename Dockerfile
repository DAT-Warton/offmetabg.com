FROM php:8.1-cli

# Install PostgreSQL client and PDO driver
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY . .

# Make startup script executable
RUN chmod +x start.sh

EXPOSE 10000

CMD ["./start.sh"]
