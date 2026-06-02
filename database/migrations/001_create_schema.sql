-- ============================================================
-- UAM Database Schema - PostgreSQL
-- ============================================================

BEGIN;

-- Levels (Roles)
CREATE TABLE IF NOT EXISTS levels (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    deleted_at  TIMESTAMP WITH TIME ZONE,
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_levels_is_active ON levels (is_active);
CREATE INDEX IF NOT EXISTS idx_levels_deleted_at ON levels (deleted_at);

-- Users
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    level_id      INTEGER NOT NULL REFERENCES levels(id),
    full_name     VARCHAR(200) NOT NULL,
    username      VARCHAR(50) NOT NULL UNIQUE,
    email         VARCHAR(200) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_level_id ON users (level_id);
CREATE INDEX IF NOT EXISTS idx_users_username ON users (username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
CREATE INDEX IF NOT EXISTS idx_users_is_active ON users (is_active);

-- Pages (Menu items)
CREATE TABLE IF NOT EXISTS pages (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    route_path  VARCHAR(200) NOT NULL UNIQUE,
    description TEXT,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_active   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_pages_route_path ON pages (route_path);
CREATE INDEX IF NOT EXISTS idx_pages_sort_order ON pages (sort_order);
CREATE INDEX IF NOT EXISTS idx_pages_is_active ON pages (is_active);

-- Level Permissions (action-based)
-- permission_key examples: users.view, users.create, dashboard.view
CREATE TABLE IF NOT EXISTS level_permissions (
    id             SERIAL PRIMARY KEY,
    level_id       INTEGER NOT NULL REFERENCES levels(id) ON DELETE CASCADE,
    page_id        INTEGER REFERENCES pages(id) ON DELETE SET NULL,
    permission_key VARCHAR(100) NOT NULL,
    created_at     TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE (level_id, permission_key)
);

CREATE INDEX IF NOT EXISTS idx_level_permissions_level_id ON level_permissions (level_id);
CREATE INDEX IF NOT EXISTS idx_level_permissions_key ON level_permissions (permission_key);

-- User Additional Permissions
CREATE TABLE IF NOT EXISTS user_permissions (
    id             SERIAL PRIMARY KEY,
    user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    page_id        INTEGER REFERENCES pages(id) ON DELETE SET NULL,
    permission_key VARCHAR(100) NOT NULL,
    created_at     TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, permission_key)
);

CREATE INDEX IF NOT EXISTS idx_user_permissions_user_id ON user_permissions (user_id);

-- User Permission Exclusions
CREATE TABLE IF NOT EXISTS user_permission_exclusions (
    id             SERIAL PRIMARY KEY,
    user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    page_id        INTEGER REFERENCES pages(id) ON DELETE SET NULL,
    permission_key VARCHAR(100) NOT NULL,
    created_at     TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    UNIQUE (user_id, permission_key)
);

CREATE INDEX IF NOT EXISTS idx_user_permission_exclusions_user_id ON user_permission_exclusions (user_id);

COMMIT;
