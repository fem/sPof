BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

CREATE EXTENSION IF NOT EXISTS pg_trgm SCHEMA pg_catalog;
CREATE EXTENSION IF NOT EXISTS lo SCHEMA pg_catalog;
CREATE EXTENSION IF NOT EXISTS ltree SCHEMA pg_catalog;
CREATE EXTENSION IF NOT EXISTS pgcrypto SCHEMA pg_catalog;

COMMIT;