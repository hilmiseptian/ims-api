-- database/migrations/002_enhance_pages.sql
BEGIN;

-- Add missing columns to pages
ALTER TABLE pages
    ADD COLUMN IF NOT EXISTS icon           VARCHAR(50)  NOT NULL DEFAULT 'article',
    ADD COLUMN IF NOT EXISTS permission_key VARCHAR(100),
    ADD COLUMN IF NOT EXISTS is_system      BOOLEAN      NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS parent_id      INTEGER      REFERENCES pages(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_pages_parent_id      ON pages(parent_id);
CREATE INDEX IF NOT EXISTS idx_pages_permission_key ON pages(permission_key);
CREATE INDEX IF NOT EXISTS idx_pages_is_system      ON pages(is_system);

-- Backfill system pages
UPDATE pages SET icon='dashboard',            permission_key='dashboard.view',   is_system=TRUE WHERE route_path='/dashboard';
UPDATE pages SET icon='people',               permission_key='users.view',        is_system=TRUE WHERE route_path='/users';
UPDATE pages SET icon='layers',               permission_key='levels.view',       is_system=TRUE WHERE route_path='/levels';
UPDATE pages SET icon='article',              permission_key='pages.view',        is_system=TRUE WHERE route_path='/pages';
UPDATE pages SET icon='admin_panel_settings', permission_key='permissions.view',  is_system=TRUE WHERE route_path='/permissions';

COMMIT;