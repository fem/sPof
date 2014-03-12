BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

CREATE SCHEMA "binary";

SET search_path TO "binary";

CREATE DOMAIN lo AS oid;

CREATE FUNCTION tbl_file_update_handler() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE
  rec record;
BEGIN
  IF (TG_OP = 'UPDATE') THEN
    -- prevent updates to metadata while leaving content_oid unchanged
    IF ((OLD.hash <> NEW.hash OR OLD.size <> NEW.size) AND OLD.content_oid = NEW.content_oid) THEN
      RAISE EXCEPTION 'tbl_file_update_handler(): metadata update without changing content_oid is forbidden!';
    END IF;
  END IF;
  
  SELECT * INTO rec FROM "binary".tbl_file_oid WHERE content_oid = NEW.content_oid;
  IF FOUND THEN
    -- prevent updates on existing oid to avoid conflicts with other schemas
    IF (rec.hash <> NEW.hash OR rec.size <> NEW.size) THEN
      RAISE EXCEPTION 'tbl_file_update_handler(): metadata (hash or size) differs from tbl_file_oid';
    END IF;
  ELSIF (NEW.content_oid IS NOT NULL) THEN
    INSERT INTO "binary".tbl_file_oid (name, size, hash, old_path, content_oid) VALUES (NEW.name, NEW.size, NEW.hash, NEW.old_path, NEW.content_oid);
  END IF;
  RETURN NEW;
END
$$;

CREATE TABLE tbl_file_oid (
    id bigserial NOT NULL,
    name character varying(256) NOT NULL,
    size bigint DEFAULT 0 NOT NULL,
    hash character varying(32) NOT NULL,
    path_old character varying,
    content_oid lo NOT NULL,
    CONSTRAINT tbl_file_oid_pkey PRIMARY KEY (id)
);

REVOKE ALL ON SCHEMA "binary" FROM PUBLIC;

COMMIT;
