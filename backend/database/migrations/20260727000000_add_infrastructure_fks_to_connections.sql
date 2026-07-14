-- Add Infrastructure Foreign Keys to Connections table
-- Apply through the project's migration runner; do not alter production manually.

ALTER TABLE connections
    ADD COLUMN pop_site_id BIGINT UNSIGNED NULL AFTER sector,
    ADD COLUMN tower_id BIGINT UNSIGNED NULL AFTER pop_site_id,
    ADD COLUMN sector_id BIGINT UNSIGNED NULL AFTER tower_id,
    ADD COLUMN device_id BIGINT UNSIGNED NULL AFTER sector_id,
    ADD KEY idx_connections_pop_site (pop_site_id),
    ADD KEY idx_connections_tower (tower_id),
    ADD KEY idx_connections_sector (sector_id),
    ADD KEY idx_connections_device (device_id),
    ADD CONSTRAINT fk_connections_pop_site FOREIGN KEY (pop_site_id) REFERENCES pop_sites (id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_connections_tower FOREIGN KEY (tower_id) REFERENCES towers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_connections_sector FOREIGN KEY (sector_id) REFERENCES sectors (id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_connections_device FOREIGN KEY (device_id) REFERENCES network_devices (id) ON DELETE SET NULL ON UPDATE CASCADE;