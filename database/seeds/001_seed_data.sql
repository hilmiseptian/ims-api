-- ============================================================
-- UAM Seed Data
-- ============================================================

BEGIN;

-- Levels
INSERT INTO levels (name, description, is_active) VALUES
    ('Super Admin', 'Full system access', TRUE),
    ('Manager',     'Manage users and view reports', TRUE),
    ('Staff',       'Basic operational access', TRUE),
    ('Viewer',      'Read-only access', TRUE)
ON CONFLICT (name) DO NOTHING;

-- Pages
INSERT INTO pages (name, route_path, description, sort_order, is_active) VALUES
    ('Dashboard',           '/dashboard',    'Main dashboard',                    1,  TRUE),
    ('User Management',     '/users',        'Manage system users',               2,  TRUE),
    ('Level Management',    '/levels',       'Manage roles and levels',           3,  TRUE),
    ('Page Management',     '/pages',        'Manage application pages',          4,  TRUE),
    ('Permission Matrix',   '/permissions',  'Manage access permissions',         5,  TRUE)
ON CONFLICT (route_path) DO NOTHING;

-- Default Super Admin user
-- Password: Admin123!  (bcrypt hash)
INSERT INTO users (level_id, full_name, username, email, password_hash, is_active)
SELECT
    l.id,
    'Administrator',
    'admin',
    'admin@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin123!
    TRUE
FROM levels l WHERE l.name = 'Super Admin'
ON CONFLICT (username) DO NOTHING;

-- Sample Manager user
-- Password: Manager123!
INSERT INTO users (level_id, full_name, username, email, password_hash, is_active)
SELECT
    l.id,
    'John Manager',
    'manager',
    'manager@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    TRUE
FROM levels l WHERE l.name = 'Manager'
ON CONFLICT (username) DO NOTHING;

-- Sample Staff user
-- Password: Staff123!
INSERT INTO users (level_id, full_name, username, email, password_hash, is_active)
SELECT
    l.id,
    'Jane Staff',
    'staff',
    'staff@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    TRUE
FROM levels l WHERE l.name = 'Staff'
ON CONFLICT (username) DO NOTHING;

-- Super Admin: all permissions
INSERT INTO level_permissions (level_id, permission_key)
SELECT l.id, perm
FROM levels l,
     (VALUES
         ('dashboard.view'),
         ('users.view'), ('users.create'), ('users.update'), ('users.delete'),
         ('levels.view'), ('levels.create'), ('levels.update'), ('levels.delete'),
         ('pages.view'), ('pages.create'), ('pages.update'), ('pages.delete'),
         ('permissions.view'), ('permissions.update')
     ) AS perms(perm)
WHERE l.name = 'Super Admin'
ON CONFLICT (level_id, permission_key) DO NOTHING;

-- Manager: view/create/update users, view levels, view pages, view dashboard
INSERT INTO level_permissions (level_id, permission_key)
SELECT l.id, perm
FROM levels l,
     (VALUES
         ('dashboard.view'),
         ('users.view'), ('users.create'), ('users.update'),
         ('levels.view'),
         ('pages.view')
     ) AS perms(perm)
WHERE l.name = 'Manager'
ON CONFLICT (level_id, permission_key) DO NOTHING;

-- Staff: dashboard and view users only
INSERT INTO level_permissions (level_id, permission_key)
SELECT l.id, perm
FROM levels l,
     (VALUES
         ('dashboard.view'),
         ('users.view')
     ) AS perms(perm)
WHERE l.name = 'Staff'
ON CONFLICT (level_id, permission_key) DO NOTHING;

-- Viewer: dashboard only
INSERT INTO level_permissions (level_id, permission_key)
SELECT l.id, perm
FROM levels l,
     (VALUES
         ('dashboard.view')
     ) AS perms(perm)
WHERE l.name = 'Viewer'
ON CONFLICT (level_id, permission_key) DO NOTHING;

COMMIT;
