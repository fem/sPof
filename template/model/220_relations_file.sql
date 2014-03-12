BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ############# tbl_file ############
-- ###################################

CREATE TABLE tbl_file (
    id bigserial NOT NULL,
    group_id bigint,
    user_id bigint,
    name character varying(256) NOT NULL,
    description text,
    size bigint DEFAULT 0 NOT NULL,
    mimetype character varying(32) NOT NULL,
    hash character varying(32) NOT NULL,
    content_oid lo,
    sid character varying(32),
    accesscount bigint,
    lastaccess timestamp without time zone,
    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
    visible boolean DEFAULT false NOT NULL,
    old_id character varying(32),
    old_path character varying,
	CONSTRAINT tbl_file_pkey PRIMARY KEY (id),
    CONSTRAINT tbl_file_creationmodify_check CHECK ((creation <= modify)),
	CONSTRAINT tbl_file_group_fk FOREIGN KEY (group_id) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT tbl_file_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- indexes
CREATE INDEX tbl_file_group_id_idx ON tbl_file USING btree (group_id);
CREATE INDEX tbl_file_user_id_idx ON tbl_file USING btree (user_id);
CREATE INDEX tbl_file_sid_idx ON tbl_file USING btree (sid);

-- triggers
--CREATE TRIGGER lo_manage BEFORE DELETE OR UPDATE ON tbl_file FOR EACH ROW EXECUTE PROCEDURE lo_manage('content_oid');
CREATE TRIGGER recalc_sid BEFORE INSERT OR UPDATE ON tbl_file FOR EACH ROW EXECUTE PROCEDURE recalc_sid();

COMMIT;