# Base image
FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Copy application code
COPY ../app/src ./src
COPY ../app/templates ./templates
COPY ../app/static ./static
COPY ../db ./db

# Install dependencies
RUN pip install --no-cache-dir flask bcrypt pyjwt

# Expose the Flask port
EXPOSE 5000

# Set environment variables
ENV FLASK_APP=src/app.py
ENV FLASK_RUN_HOST=0.0.0.0
ENV FLASK_ENV=development

# Run the Flask app
CMD ["flask", "run"]
