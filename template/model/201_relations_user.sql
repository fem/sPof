BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ############ tbl_user #############
-- ###################################

CREATE TABLE tbl_user (
    id bigserial NOT NULL,
    name character varying(35) NOT NULL,
    passphrase character varying(256),
    email character varying(128) NOT NULL,
    title character varying(32),
    firstname character varying(64),
    middlename character varying(32),
    lastname character varying(64),
    maidenname character varying(32),
    female boolean,
    birthday date,
    birthplace character varying(64),
    accesscount integer,
    lastaccess timestamp without time zone,
    logincount integer,
    lastlogin timestamp without time zone,
    lastupdate timestamp without time zone DEFAULT now() NOT NULL,
    token character varying(255),
    tokenexpiry timestamp without time zone,
    lastvalidated timestamp without time zone,
    expiry timestamp without time zone,
    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
    visible boolean DEFAULT false NOT NULL,
    old_id character varying(32),
    old_stg text,
    old_seg smallint DEFAULT 0 NOT NULL,
    fti tsvector,

    CONSTRAINT tbl_user_pkey PRIMARY KEY (id),
    CONSTRAINT tbl_user_old_id_uq UNIQUE (old_id),
    CONSTRAINT tbl_user_token_uq UNIQUE (token),
    CONSTRAINT tbl_user_creationmodify_check CHECK ((creation <= modify)),
    CONSTRAINT tbl_user_email_check CHECK (((email)::text ~* '^([a-z0-9_\\-\\.]+)@([a-z0-9_\\-\\.]+)\\.([a-z]{2,5})$'::text))
);

-- indexes
CREATE INDEX tbl_user_fti_idx ON tbl_user USING gist (fti);
CREATE INDEX tbl_user_lastupdate_idx ON tbl_user USING btree (lastupdate);
CREATE INDEX tbl_user_modify_idx ON tbl_user USING btree (modify);
CREATE UNIQUE INDEX tbl_user_name_idx ON tbl_user USING btree (lower((name)::text));
CREATE INDEX tbl_user_stg_idx ON tbl_user USING btree (old_stg);
CREATE INDEX ON tbl_user (disabled);
CREATE INDEX ON tbl_user (visible);

-- triggers
CREATE TRIGGER tsvectorupdate BEFORE INSERT OR UPDATE ON tbl_user FOR EACH ROW EXECUTE PROCEDURE tsvector_update_user();


COMMIT;