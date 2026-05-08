-- Masscan Web UI — PostgreSQL schema

DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS data;

CREATE TABLE data (
  id          bigserial    PRIMARY KEY,
  ip          bigint       NOT NULL DEFAULT 0,
  port_id     integer      NOT NULL DEFAULT 0,
  scanned_ts  timestamp    NOT NULL DEFAULT NOW(),
  protocol    varchar(3)   NOT NULL DEFAULT '',
  state       varchar(10)  NOT NULL DEFAULT '',
  reason      varchar(255) NOT NULL DEFAULT '',
  reason_ttl  bigint       NOT NULL DEFAULT 0,
  service     varchar(100) NOT NULL DEFAULT '',
  banner      text         NOT NULL DEFAULT '',
  title       text         NOT NULL DEFAULT '',
  searchtext  tsvector
);

CREATE INDEX data_scanned_ts_idx ON data (scanned_ts);
CREATE INDEX data_ip_idx         ON data (ip);
CREATE INDEX data_searchtext_idx ON data USING GIN (searchtext);

CREATE TABLE jobs (
  id           varchar(36)  PRIMARY KEY,
  status       varchar(10)  NOT NULL DEFAULT 'running'
                            CHECK (status IN ('running','done','failed')),
  target       varchar(500) NOT NULL,
  ports        varchar(500) NOT NULL,
  rate         integer      NOT NULL DEFAULT 1000,
  banners      boolean      NOT NULL DEFAULT false,
  started_at   timestamp    NOT NULL DEFAULT NOW(),
  finished_at  timestamp,
  record_count integer,
  error_msg    text
);

CREATE INDEX jobs_status_idx     ON jobs (status);
CREATE INDEX jobs_started_at_idx ON jobs (started_at);
