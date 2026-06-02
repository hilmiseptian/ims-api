# UAM Backend — Slim PHP 4

User Access Management REST API built with **Slim PHP 4**, **PostgreSQL**, and **JWT** authentication.

## Stack
- PHP 8.2+
- Slim PHP 4
- PHP-DI 7 (Dependency Injection)
- Firebase JWT 6
- PostgreSQL

## Quick Start

### 1. Install dependencies
```bash
composer install
```

### 2. Configure environment
```bash
cp .env.example .env
# Edit .env with your DB credentials and JWT secret
```

### 3. Create database
```bash
psql -U postgres -c "CREATE DATABASE uam_db;"
```

### 4. Run migrations
```bash
composer run migrate
# or: php database/migrate.php
```

### 5. Seed data
```bash
composer run seed
# or: php database/seed.php
```

### 6. Start server
```bash
composer run start
# Runs on http://localhost:8080
```

---

## Default Credentials

| Username | Password     | Level       |
|----------|-------------|-------------|
| admin    | Admin123!   | Super Admin |
| manager  | Password123! | Manager     |
| staff    | Password123! | Staff       |

---

## API Endpoints

### Authentication
| Method | Endpoint         | Permission | Description          |
|--------|------------------|------------|----------------------|
| POST   | /api/auth/login  | Public     | Login, returns JWT   |
| GET    | /api/auth/me     | Auth       | Current user + perms |
| POST   | /api/auth/logout | Auth       | Logout (client-side) |

### Users
| Method | Endpoint          | Permission    |
|--------|-------------------|---------------|
| GET    | /api/users        | users.view    |
| GET    | /api/users/{id}   | users.view    |
| POST   | /api/users        | users.create  |
| PUT    | /api/users/{id}   | users.update  |
| DELETE | /api/users/{id}   | users.delete  |

### Levels
| Method | Endpoint          | Permission    |
|--------|-------------------|---------------|
| GET    | /api/levels       | levels.view   |
| GET    | /api/levels/{id}  | levels.view   |
| POST   | /api/levels       | levels.create |
| PUT    | /api/levels/{id}  | levels.update |
| DELETE | /api/levels/{id}  | levels.delete |

### Pages
| Method | Endpoint          | Permission    |
|--------|-------------------|---------------|
| GET    | /api/pages        | pages.view    |
| GET    | /api/pages/{id}   | pages.view    |
| POST   | /api/pages        | pages.create  |
| PUT    | /api/pages/{id}   | pages.update  |
| DELETE | /api/pages/{id}   | pages.delete  |

### Permissions
| Method | Endpoint                              | Permission         |
|--------|---------------------------------------|--------------------|
| GET    | /api/permissions/my                   | Auth only          |
| GET    | /api/permissions/matrix               | permissions.view   |
| GET    | /api/permissions/levels/{id}          | permissions.view   |
| PUT    | /api/permissions/levels/{id}          | permissions.update |
| GET    | /api/permissions/users/{id}           | permissions.view   |
| PUT    | /api/permissions/users/{id}/additions | permissions.update |
| PUT    | /api/permissions/users/{id}/exclusions| permissions.update |

---

## Permission Model

Action-based permissions using dot-notation:

```
dashboard.view
users.view | users.create | users.update | users.delete
levels.view | levels.create | levels.update | levels.delete
pages.view | pages.create | pages.update | pages.delete
permissions.view | permissions.update
```

**Effective Permission Formula:**
```
Effective = (Level Permissions) + (User Additions) - (User Exclusions)
```

---

## API Response Format

**Success:**
```json
{ "success": true, "message": "...", "data": {} }
```

**Validation Error:**
```json
{ "success": false, "message": "Validation failed", "errors": {} }
```

**Unauthorized:**
```json
{ "success": false, "message": "Unauthorized" }
```

**Forbidden:**
```json
{ "success": false, "message": "Forbidden" }
```

---

## Project Structure

```
app/
├── Modules/
│   ├── Auth/         (login, me)
│   ├── User/         (CRUD)
│   ├── Level/        (CRUD + soft delete)
│   ├── Page/         (CRUD)
│   └── Permission/   (matrix, level perms, user perms)
├── Shared/
│   ├── Database/     (PDO singleton)
│   ├── Security/     (JwtService)
│   ├── Middleware/   (AuthMiddleware, PermissionMiddleware)
│   ├── Response/     (JsonResponse helper)
│   └── Exceptions/   (custom exceptions)
config/               (DI container)
routes/               (api.php)
database/
├── migrations/       (schema SQL)
└── seeds/            (seed SQL)
public/               (entry point)
```
