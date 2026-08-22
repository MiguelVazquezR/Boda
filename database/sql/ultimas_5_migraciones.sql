-- ============================================================
-- ÚLTIMAS 5 MIGRACIONES (para ejecutar en phpMyAdmin)
-- Orden correcto: 1 → 5
-- ============================================================

-- ------------------------------------------------------------
-- 1) 2026_08_21_000001_create_guest_groups_table
--    Crea la tabla guest_groups
-- ------------------------------------------------------------
CREATE TABLE guest_groups (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY guest_groups_name_unique (name)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2) 2026_08_21_000002_add_profile_fields_to_guests_table
--    Agrega columnas de perfil a la tabla guests
-- ------------------------------------------------------------
ALTER TABLE guests
    ADD COLUMN first_name VARCHAR(255) NULL AFTER id,
    ADD COLUMN last_name VARCHAR(255) NULL AFTER first_name,
    ADD COLUMN age TINYINT UNSIGNED NULL AFTER last_name,
    ADD COLUMN gender VARCHAR(255) NULL AFTER age,
    ADD COLUMN guest_group_id BIGINT UNSIGNED NULL AFTER gender,
    ADD COLUMN phone VARCHAR(10) NULL AFTER guest_group_id,
    ADD COLUMN origin VARCHAR(255) NULL AFTER phone,
    ADD COLUMN state VARCHAR(255) NULL AFTER origin,
    ADD COLUMN city VARCHAR(255) NULL AFTER state;

-- Llave foránea hacia guest_groups (ON DELETE SET NULL)
ALTER TABLE guests
    ADD CONSTRAINT guests_guest_group_id_foreign
    FOREIGN KEY (guest_group_id) REFERENCES guest_groups (id)
    ON DELETE SET NULL;

-- Backfill: separa full_name existente en first_name / last_name
UPDATE guests
SET
    first_name = SUBSTRING_INDEX(TRIM(full_name), ' ', 1),
    last_name = CASE
        WHEN INSTR(TRIM(full_name), ' ') = 0 THEN NULL
        ELSE TRIM(SUBSTRING(TRIM(full_name), INSTR(TRIM(full_name), ' ') + 1))
    END
WHERE first_name IS NULL;

-- ------------------------------------------------------------
-- 3) 2026_08_21_000003_drop_passes_from_guests_table
--    Elimina las columnas de "pases"
-- ------------------------------------------------------------
ALTER TABLE guests
    DROP COLUMN allowed_passes,
    DROP COLUMN confirmed_passes;

-- ------------------------------------------------------------
-- 4) 2026_08_21_000004_drop_confirmed_by_name_from_guests_table
--    Elimina la columna confirmed_by_name
-- ------------------------------------------------------------
ALTER TABLE guests
    DROP COLUMN confirmed_by_name;

-- ------------------------------------------------------------
-- 5) 2026_08_21_000005_create_schedule_items_table
--    Crea la tabla schedule_items
-- ------------------------------------------------------------
CREATE TABLE schedule_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    time VARCHAR(255) NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
