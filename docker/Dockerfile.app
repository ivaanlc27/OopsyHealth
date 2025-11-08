# Base image
FROM python:3.11-slim

WORKDIR /app

# Copy application code
COPY app/src ./src
COPY app/templates ./templates
COPY app/static ./static
COPY db ./db

# Install dependencies
RUN pip install --no-cache-dir flask bcrypt pyjwt

# Expose Flask port
EXPOSE 5000

# Env variables
ENV FLASK_APP=src/app.py
ENV FLASK_RUN_HOST=0.0.0.0
ENV FLASK_ENV=development

# Run DB init and then Flask app
CMD ["sh", "-c", "python /app/src/init_db.py && flask run"]
