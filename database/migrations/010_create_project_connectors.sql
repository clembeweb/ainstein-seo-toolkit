-- Project Connectors - Centralized CMS connections for Global Projects
-- Used by: seo-audit (WordPress audit), content-creator (CMS publishing), internal-links (future)

CREATE TABLE IF NOT EXISTS project_connectors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('wordpress','shopify','prestashop','magento') NOT NULL,
    name VARCHAR(255) NOT NULL,
    config JSON NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_test_at DATETIME NULL,
    last_test_status ENUM('success','error') NULL,
    last_test_message VARCHAR(500) NULL,
    seo_plugin VARCHAR(50) NULL,
    wp_version VARCHAR(20) NULL,
    plugin_version VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
