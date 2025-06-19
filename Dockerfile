# Multi-stage build for PapuaJourneyExpo
FROM php:8.0-apache AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    python3 \
    python3-pip \
    python3-venv \
    mariadb-client \
    wget \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Don't install newer SQLite system-wide to avoid conflicts with PHP
# We'll use pysqlite3-binary which includes its own SQLite

# Configure PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set up Apache configuration for PHP app
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

# Create Python virtual environment for chatbot
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Install Python dependencies
RUN pip install --upgrade pip && \
    pip install pysqlite3-binary && \
    pip install -r /var/www/html/users/chatbot/rag_py/requirements.txt

# Expose ports
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Start with entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]