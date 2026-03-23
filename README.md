# Laravel Task API

A RESTful API built with Laravel for managing tasks with authentication.

## Features
- User authentication via Sanctum tokens
- CRUD operations for tasks
- Repository pattern with service layer
- Feature and unit tests

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/register | Register user |
| POST | /api/login | Login user |
| GET | /api/tasks | List tasks |
| POST | /api/tasks | Create task |
| GET | /api/tasks/{id} | Show task |
| PUT | /api/tasks/{id} | Update task |
| DELETE | /api/tasks/{id} | Delete task |

## Testing
```bash
php artisan test
```
