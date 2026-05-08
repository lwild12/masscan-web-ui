-- Masscan Web UI — MySQL initialisation schema
-- Auto-run by the MySQL Docker image on first container start.

CREATE TABLE IF NOT EXISTS `data` (
  `id`         bigint unsigned      NOT NULL AUTO_INCREMENT,
  `ip`         int unsigned         NOT NULL DEFAULT '0',
  `port_id`    mediumint unsigned   NOT NULL DEFAULT '0',
  `scanned_ts` timestamp            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `protocol`   enum('tcp','udp')    NOT NULL,
  `state`      varchar(10)          NOT NULL DEFAULT '',
  `reason`     varchar(255)         NOT NULL DEFAULT '',
  `reason_ttl` int unsigned         NOT NULL DEFAULT '0',
  `service`    varchar(100)         NOT NULL DEFAULT '',
  `banner`     text                 NOT NULL,
  `title`      text                 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scanned_ts` (`scanned_ts`),
  KEY `ip` (`ip`),
  FULLTEXT KEY `banner` (`banner`, `title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jobs` (
  `id`           varchar(36)                          NOT NULL,
  `status`       enum('running','done','failed')      NOT NULL DEFAULT 'running',
  `target`       varchar(500)                         NOT NULL,
  `ports`        varchar(500)                         NOT NULL,
  `rate`         int unsigned                         NOT NULL DEFAULT 1000,
  `banners`      tinyint(1)                           NOT NULL DEFAULT 0,
  `started_at`   timestamp                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at`  timestamp                            NULL,
  `record_count` int unsigned                         DEFAULT NULL,
  `error_msg`    text                                 DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
