-- RadBoX application schema.
-- Import the official FreeRADIUS MySQL schema first, then import this file.
-- RadBoX does not modify FreeRADIUS core tables.

CREATE TABLE IF NOT EXISTS rb_tenants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  code VARCHAR(40) NOT NULL UNIQUE,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NULL,
  name VARCHAR(150) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('super_admin','tenant_owner','technician') NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_rb_users_tenant_id (tenant_id),
  CONSTRAINT fk_rb_users_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_routers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  nas_ip_address VARCHAR(64) NOT NULL,
  radius_secret VARCHAR(255) NOT NULL,
  voucher_prefix VARCHAR(24) NOT NULL,
  description TEXT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_rb_routers_tenant_id (tenant_id),
  INDEX idx_rb_routers_nas_ip_address (nas_ip_address),
  CONSTRAINT fk_rb_routers_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_user_routers (
  user_id BIGINT UNSIGNED NOT NULL,
  router_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, router_id),
  INDEX idx_rb_user_routers_router_id (router_id),
  CONSTRAINT fk_rb_user_routers_user FOREIGN KEY (user_id) REFERENCES rb_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_user_routers_router FOREIGN KEY (router_id) REFERENCES rb_routers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  group_name VARCHAR(150) NOT NULL,
  username_prefix VARCHAR(24) NULL,
  total_uptime_minutes INT UNSIGNED NOT NULL DEFAULT 60,
  validity_minutes INT UNSIGNED NOT NULL DEFAULT 1440,
  idle_timeout_minutes INT UNSIGNED NULL,
  rate_limit VARCHAR(80) NULL,
  shared_users INT UNSIGNED NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_rb_profiles_group_name_unique (group_name),
  INDEX idx_rb_profiles_tenant_id (tenant_id),
  CONSTRAINT fk_rb_profiles_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_voucher_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  router_id BIGINT UNSIGNED NULL,
  profile_id BIGINT UNSIGNED NOT NULL,
  batch_code VARCHAR(80) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY idx_rb_voucher_batches_batch_code_unique (batch_code),
  INDEX idx_rb_voucher_batches_tenant_id (tenant_id),
  INDEX idx_rb_voucher_batches_profile_id (profile_id),
  CONSTRAINT fk_rb_batches_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_batches_router FOREIGN KEY (router_id) REFERENCES rb_routers(id) ON DELETE SET NULL,
  CONSTRAINT fk_rb_batches_profile FOREIGN KEY (profile_id) REFERENCES rb_profiles(id) ON DELETE RESTRICT,
  CONSTRAINT fk_rb_batches_user FOREIGN KEY (created_by) REFERENCES rb_users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_vouchers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  router_id BIGINT UNSIGNED NULL,
  batch_id BIGINT UNSIGNED NOT NULL,
  profile_id BIGINT UNSIGNED NOT NULL,
  username VARCHAR(80) NOT NULL,
  password VARCHAR(80) NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('generated','active','expired','deleted') NOT NULL DEFAULT 'generated',
  total_uptime_limit_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  total_uptime_used_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  validity_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  first_login_at DATETIME NULL,
  expires_at DATETIME NULL,
  last_seen_at DATETIME NULL,
  deleted_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_rb_vouchers_username_unique (username),
  INDEX idx_rb_vouchers_tenant_id (tenant_id),
  INDEX idx_rb_vouchers_router_id (router_id),
  INDEX idx_rb_vouchers_profile_id (profile_id),
  INDEX idx_rb_vouchers_status (status),
  CONSTRAINT fk_rb_vouchers_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_vouchers_router FOREIGN KEY (router_id) REFERENCES rb_routers(id) ON DELETE SET NULL,
  CONSTRAINT fk_rb_vouchers_batch FOREIGN KEY (batch_id) REFERENCES rb_voucher_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_vouchers_profile FOREIGN KEY (profile_id) REFERENCES rb_profiles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  router_id BIGINT UNSIGNED NULL,
  batch_id BIGINT UNSIGNED NOT NULL,
  voucher_id BIGINT UNSIGNED NOT NULL,
  username VARCHAR(80) NOT NULL,
  profile_id BIGINT UNSIGNED NOT NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('generated','sold','used','expired','deleted') NOT NULL DEFAULT 'generated',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_rb_transactions_tenant_id (tenant_id),
  INDEX idx_rb_transactions_profile_id (profile_id),
  INDEX idx_rb_transactions_created_at (created_at),
  CONSTRAINT fk_rb_transactions_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_transactions_router FOREIGN KEY (router_id) REFERENCES rb_routers(id) ON DELETE SET NULL,
  CONSTRAINT fk_rb_transactions_batch FOREIGN KEY (batch_id) REFERENCES rb_voucher_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_transactions_voucher FOREIGN KEY (voucher_id) REFERENCES rb_vouchers(id) ON DELETE CASCADE,
  CONSTRAINT fk_rb_transactions_profile FOREIGN KEY (profile_id) REFERENCES rb_profiles(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NULL,
  setting_key VARCHAR(120) NOT NULL,
  setting_value TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY idx_rb_settings_scope_key (tenant_id, setting_key),
  CONSTRAINT fk_rb_settings_tenant FOREIGN KEY (tenant_id) REFERENCES rb_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(250) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_rb_audit_logs_tenant_id (tenant_id),
  INDEX idx_rb_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rb_backup_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  backup_type VARCHAR(40) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(40) NOT NULL,
  message TEXT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_rb_backup_logs_created_at (created_at),
  CONSTRAINT fk_rb_backup_logs_user FOREIGN KEY (user_id) REFERENCES rb_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
