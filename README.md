# Laravel Task Management API

A RESTful task management API built with Laravel. Users register, log in, and manage their own projects and tasks. Written for a mid-level technical assessment.

## Tech Stack

- PHP 8.3, Laravel 13
- MySQL
- Laravel Sanctum (API auth)
- L5 Swagger (API docs)

## Features

- Sanctum auth: register, login, logout
- Projects: CRUD with status (active, completed, archived), soft deletes
- Tasks: CRUD with status (todo, in_progress, done), priority (low, medium, high), due date, soft deletes
- Filter tasks by status, priority, search by title
- Dashboard: project and task stats for the logged-in user
- Policy-based ownership (you only see your own stuff)
- Repository + service layer
- Queue job: notifies a user when a task becomes overdue
- Feature tests

## Setup

Requirements: PHP 8.3+, Composer, MySQL, Node (for Vite).

```bash
git clone https://github.com/Drrrose/Laravel-Mid-Level-Technical-Assessment
cd Laravel-Mid-Level-Technical-Assessment
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database details (DB_DATABASE, DB_USERNAME, DB_PASSWORD).

```bash
php artisan migrate --seed
php artisan serve
```

Or use the built-in combo script which starts the server, queue worker, Vite, and logs at once:

```bash
composer run dev
```

Default demo user after seeding:

```
Email:    demo@example.com
Password: password
```

## API

Base URL: `http://localhost:8000`

All endpoints except register/login require a Bearer token:

```
Authorization: Bearer <token>
```

### Auth

| Method | Endpoint | Description |
|---|---|---|
| POST | `/register` | Register (name, email, password) |
| POST | `/login` | Login, returns token |
| POST | `/logout` | Revoke current token |

### Projects

| Method | Endpoint | Description |
|---|---|---|
| GET | `/projects` | List (paginated, `?status=` filter) |
| POST | `/projects` | Create |
| GET | `/projects/{project}` | View |
| PUT/PATCH | `/projects/{project}` | Update |
| DELETE | `/projects/{project}` | Soft delete |

### Tasks

| Method | Endpoint | Description |
|---|---|---|
| GET | `/projects/{project}/tasks` | List (`?status=`, `?priority=`, `?search=` filters) |
| POST | `/projects/{project}/tasks` | Create |
| GET | `/projects/{project}/tasks/{task}` | View |
| PUT/PATCH | `/projects/{project}/tasks/{task}` | Update |
| DELETE | `/projects/{project}/tasks/{task}` | Soft delete |

### Dashboard

| Method | Endpoint | Description |
|---|---|---|
| GET | `/dashboard` | Total/active projects, total/completed/pending/overdue tasks |

Responses use a consistent envelope:

```json
{ "status": "success", "message": "...", "data": {} }
```

Errors:

```json
{ "status": "error", "message": "...", "errors": {} }
```

## Swagger / OpenAPI

Interactive docs at `/api/documentation`.

Regenerate the spec after changing controllers:

```bash
php artisan l5-swagger:generate
```

## Overdue Task Notifications

A scheduled command picks up overdue tasks (due date passed, not done) every hour and dispatches a queued job that notifies the owner.

```bash
php artisan schedule:work   # runs the scheduler locally
php artisan queue:work      # processes queued jobs
```

The task is marked as notified so you only get one notification per task. Notifications go to mail (currently the `log` driver) and the database (`notifications` table).

## Postman

Import `postman/laravel-task-management.postman_collection.json`. Login/register requests store the token automatically and all other requests use it.

## Tests

```bash
php artisan test
```

## Project Structure

```
app/
├── Enums/          ProjectStatus, TaskStatus, TaskPriority
├── Exceptions/     ApiExceptionRenderer (consistent JSON errors)
├── Http/
│   ├── Controllers/  Auth, Project, Task, Dashboard
│   ├── Requests/     FormRequest validation
│   └── Resources/    API resource classes
├── Jobs/           SendOverdueTaskNotification
├── Notifications/  TaskOverdueNotification
├── Observers/      ProjectObserver (cascade soft deletes)
├── Policies/       Ownership policies
├── Repositories/   Data access layer
├── Services/       Business logic layer
└── Traits/         ApiResponse
```

