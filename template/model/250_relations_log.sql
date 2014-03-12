BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ########## tbl_log_event ##########
-- ###################################

CREATE TABLE tbl_log_event (
    id bigserial NOT NULL,
    event ltree NOT NULL,
    user_id bigint,
    reference_parameters character varying(1024) NOT NULL,
    description character varying(4096) NOT NULL,
    creation timestamp without time zone DEFAULT now() NOT NULL,
	CONSTRAINT tbl_log_event_pkey PRIMARY KEY (id)
);

COMMENT ON COLUMN tbl_log_event.user_id IS 'Has no foreign key set, because we want to keep entries, even if users are deleted';

-- ###################################
-- ######### tbl_log_session #########
-- ###################################

CREATE TABLE tbl_log_session (
    id bigserial NOT NULL,
    session_id character varying(255) NOT NULL,
    view character varying(255) NOT NULL,
    reference_parameters character varying(1024) NOT NULL,
    other_parameters character varying(4096) NOT NULL,
    creation timestamp without time zone DEFAULT now() NOT NULL,
	CONSTRAINT tbl_log_session_pkey PRIMARY KEY (id)
);

COMMIT;