# Gramosi Backend

This is the backend API for the Gramosi application, built with PHP and MySQL.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Apache/Nginx web server

## Installation

1. Clone the repository
2. Navigate to the backend directory
3. Install dependencies:
   ```bash
   composer install
   ```
4. Create a MySQL database named `salary_zenith`
5. Import the database schema from `database/schema.sql`
6. Configure your web server to point to the `public` directory
7. Update the database configuration in `config/database.php`
8. Set up your JWT secret key in `config/config.php`

## API Endpoints

### Authentication
- POST `/api/auth` - Login/Register
  - Action: 'login' or 'register'
  - Required fields for login: username, password
  - Required fields for register: username, password, email

## Security

- All passwords are hashed using bcrypt
- JWT tokens are used for authentication
- CORS is properly configured
- Input validation and sanitization is implemented
- Prepared statements are used for all database queries

## Development

To start development:

1. Set up your local environment
2. Install dependencies
3. Configure your database
4. Start your web server
5. The API will be available at `http://localhost:8000/api/`

## Testing

To run tests:
```bash
php vendor/bin/phpunit
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request 