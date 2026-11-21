ALTER TABLE rb_users MODIFY role ENUM('super_admin','tenant_owner','technician') NOT NULL;

CREATE TABLE IF NOT EXISTS rb_user_routers (
  user_id BIGINT UNSIGNED NOT NULL,
  router_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, router_id),
  INDEX idx_rb_user_routers_router_id (router_id),
  CONSTRAINT fk_rb_user_routers_user FOREIGN KEY (user_id) REFERENCES rb_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_user_routers_router FOREIGN KEY (router_id) REFERENCES rb_routers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
