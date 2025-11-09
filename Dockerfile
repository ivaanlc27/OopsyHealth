FROM php:7.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite

COPY ./www /var/www/html/

WORKDIR /var/www/html

RUN mkdir -p /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod 755 /var/www/html/uploads

COPY apache-logfile.conf /etc/apache2/conf-available/apache-logfile.conf

RUN rm -f /var/log/apache2/access.log /var/log/apache2/error.log || true && \
    mkdir -p /var/log/apache2 && \
    touch /var/log/apache2/access.log /var/log/apache2/error.log && \
    chown -R www-data:www-data /var/log/apache2 && \
    chmod 644 /var/log/apache2/*.log && \
    a2enconf apache-logfile.conf

COPY zz-logs-combined.conf /etc/apache2/conf-available/zz-logs-combined.conf
RUN a2enconf zz-logs-combined

RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    chromium \
    chromium-driver \
    cron \
    && pip install --upgrade selenium \
    && rm -rf /var/lib/apt/lists/*

# Create a cron job for the doctor bot
RUN echo "* * * * * root python3 /var/www/html/doctor/doctor_bot.py > /var/log/doctor_bot.log 2>&1" \
      > /etc/cron.d/doctor-bot

RUN chmod 0644 /etc/cron.d/doctor-bot

CMD service cron start && apache2-foreground
