-- Migration: add parent_id to categories
-- Run in MySQL: mysql -u root -p diep_anh_shop < migrations/20251019_add_parent_id_to_categories.sql

ALTER TABLE categories
  ADD (parent_id NUMBER);

-- Optional foreign key (uncomment if desired and database supports it):
-- ALTER TABLE categories
--   ADD CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE;
