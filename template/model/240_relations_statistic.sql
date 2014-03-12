BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ########## tbl_statistic ##########
-- ###################################

CREATE TABLE tbl_statistic (
    id bigserial NOT NULL,
    accessdate timestamp without time zone DEFAULT now() NOT NULL,
    accesscount integer DEFAULT 1 NOT NULL,
    period interval,
    user_id bigint,
    ip inet,
	CONSTRAINT tbl_statistic_pkey PRIMARY KEY (id),
    CONSTRAINT tbl_statistic_accesscount_check CHECK ((accesscount > 0)),
	CONSTRAINT tbl_statistic_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE SET NULL
);

-- ###################################
-- ######## tbl_statistic_file #######
-- ###################################

CREATE TABLE tbl_statistic_file (
    file_id bigint NOT NULL,
	CONSTRAINT tbl_statistic_file_pkey PRIMARY KEY (id),
	CONSTRAINT tbl_statistic_file_file_fk FOREIGN KEY (file_id) REFERENCES tbl_file(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT tbl_statistic_file_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE SET NULL
)
INHERITS (tbl_statistic);

-- ###################################
-- ###### tbl_statistic_visitor ######
-- ###################################

CREATE TABLE tbl_statistic_visitor (
    date date DEFAULT CURRENT_DATE NOT NULL,
    count bigint DEFAULT 1 NOT NULL,
	CONSTRAINT tbl_statistic_visitor_pkey PRIMARY KEY (date)
);

-- ###################################
-- ### tbl_statistic_visitor_host ####
-- ###################################

CREATE TABLE tbl_statistic_visitor_host (
    ip character varying(255) NOT NULL,
    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
	CONSTRAINT tbl_statistic_visitor_host_pkey PRIMARY KEY (ip),
    CONSTRAINT tbl_statistic_visitor_host_creationmodify_check CHECK ((creation <= modify))
);

COMMIT;