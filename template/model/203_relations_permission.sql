BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ######### tbl_permission ##########
-- ###################################

CREATE TABLE tbl_permission (
    id bigserial NOT NULL,
    name ltree NOT NULL,
    description character varying(255),
    default_privacy_group privacy_group,
	creation timestamp without time zone NOT NULL DEFAULT now(),
	modify timestamp without time zone NOT NULL DEFAULT now(),
	disabled boolean NOT NULL DEFAULT true,
  	visible boolean NOT NULL DEFAULT false,
  	CONSTRAINT tbl_permission_pkey PRIMARY KEY (id),
	CONSTRAINT tbl_permission_name_uq UNIQUE (name)
);

-- ###################################
-- #### rel_group_user_permission ####
-- ###################################

CREATE TABLE rel_group_user_permission (
    group_id bigint NOT NULL,
    user_id bigint NOT NULL,
    permission_id bigint NOT NULL,
	CONSTRAINT rel_group_user_permission_pkey PRIMARY KEY (group_id, user_id, permission_id),
	CONSTRAINT rel_group_user_permission_group_fk FOREIGN KEY (group_id) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT rel_group_user_permission_permission_fk FOREIGN KEY (permission_id) REFERENCES tbl_permission(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT rel_group_user_permission_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ###################################
-- ############ tbl_role #############
-- ###################################

CREATE TABLE tbl_role (
    id bigserial NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    classNames varchar(128),
    assignable_in_subtree bigint,
    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
    visible boolean DEFAULT false NOT NULL,

    CONSTRAINT tbl_role_pkey PRIMARY KEY (id),
    CONSTRAINT tbl_role_name_uq UNIQUE (name),
    CONSTRAINT tbl_role_creationmodify_check CHECK ((creation <= modify)),
    CONSTRAINT tbl_role_assignable_fk FOREIGN KEY (assignable_in_subtree) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- ###################################
-- ####### rel_group_user_role #######
-- ###################################

CREATE TABLE rel_group_user_role (
    user_id bigint NOT NULL,
    role_id bigint NOT NULL,
    group_id bigint NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
	CONSTRAINT rel_group_user_role_pkey PRIMARY KEY (user_id, role_id, group_id),
	CONSTRAINT rel_group_user_role_group_fk FOREIGN KEY (group_id) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT rel_group_user_role_role_fk FOREIGN KEY (role_id) REFERENCES tbl_role(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT rel_group_user_role_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ###################################
-- ####### rel_role_permission #######
-- ###################################

CREATE TABLE rel_role_permission (
    role_id bigint NOT NULL,
    permission_id bigint NOT NULL,
	CONSTRAINT rel_role_permission_pk PRIMARY KEY (role_id, permission_id),
	CONSTRAINT rel_role_permission_permission_fk FOREIGN KEY (permission_id) REFERENCES tbl_permission(id) ON UPDATE CASCADE ON DELETE RESTRICT,
	CONSTRAINT rel_role_permission_role_fk FOREIGN KEY (role_id) REFERENCES tbl_role(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ###################################
-- ########### tbl_privacy ###########
-- ###################################

CREATE TABLE tbl_privacy (
    permission_id bigint NOT NULL,
    user_id bigint NOT NULL,
    privacy_group privacy_group NOT NULL,
    CONSTRAINT tbl_privacy_pkey PRIMARY KEY (permission_id, user_id, privacy_group),
	CONSTRAINT tbl_privacy_permission_fk FOREIGN KEY (permission_id) REFERENCES tbl_permission(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT tbl_privacy_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;