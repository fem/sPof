BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ########### tbl_group #############
-- ###################################

CREATE TABLE tbl_group (
    id bigserial NOT NULL,
    parent bigint,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    shortname character varying(32),
    description character varying(1200),
    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
    visible boolean DEFAULT false NOT NULL,
    n_left bigint,
    n_right bigint,
    n_root bigint,
    n_level integer,
    n_sort_field character varying(32) DEFAULT 'name',
    n_sort_order character varying(4) DEFAULT 'ASC',
    old_id character varying(32),
    old_sg smallint,
    fti tsvector,
    managed_extern bigint,

    appearance_color varchar(32) check(appearance_color ~* '^[a-z0-9]+$'),
    show_members boolean default false,
    show_features boolean default false,
    joinable boolean default false,
    subgroups_allowed boolean default false,

    CONSTRAINT tbl_group_pkey PRIMARY KEY (id),
    CONSTRAINT tbl_group_parent_name_uq UNIQUE (parent, name),
    CONSTRAINT tbl_group_creationmodify_check CHECK ((creation <= modify)),
    CONSTRAINT tbl_group_nested_tree_check CHECK ((nestedtree_sort_check(name('tbl_group'), n_sort_field, n_sort_order))),
    CONSTRAINT tbl_group_parent_fk FOREIGN KEY (parent) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT tbl_group_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT tbl_group_managed_extern_fk FOREIGN KEY (managed_extern) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- indexes
CREATE INDEX tbl_group_fti_idx ON tbl_group USING gist (fti);
CREATE INDEX tbl_group_parent_idx ON tbl_group USING btree (parent);
CREATE INDEX tbl_group_n_left_idx ON tbl_group USING btree (n_left);
CREATE INDEX tbl_group_n_right_idx ON tbl_group USING btree (n_right);
CREATE INDEX tbl_group_n_root_idx ON tbl_group USING btree (n_root);

-- triggers
CREATE TRIGGER nestedtree_update AFTER INSERT OR DELETE OR UPDATE ON tbl_group FOR EACH ROW EXECUTE PROCEDURE nestedtree_update();
CREATE TRIGGER tsvectorupdate BEFORE INSERT OR UPDATE ON tbl_group FOR EACH ROW EXECUTE PROCEDURE tsvector_update_group();

-- ###################################
-- ######## rel_user_group ###########
-- ###################################

CREATE TABLE rel_user_group (
    group_id bigint NOT NULL,
    user_id bigint NOT NULL,
    requested boolean DEFAULT false NOT NULL,
    sort integer DEFAULT 0 NOT NULL,
    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
    visible boolean DEFAULT false NOT NULL,
    comment character varying(8192),
	CONSTRAINT rel_user_group_pkey PRIMARY KEY (group_id, user_id),
	CONSTRAINT rel_user_group_sort_uq UNIQUE (user_id, sort),
    CONSTRAINT rel_user_group_creationmodify_check CHECK ((creation <= modify)),
    CONSTRAINT rel_user_group_sort_check CHECK ((sort >= 0)),
	CONSTRAINT rel_user_group_group_fk FOREIGN KEY (group_id) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE CASCADE,
	CONSTRAINT rel_user_group_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- triggers
CREATE TRIGGER rel_user_group_trigger BEFORE INSERT OR UPDATE ON rel_user_group FOR EACH ROW EXECUTE PROCEDURE sort_rel_user_group();
CREATE TRIGGER visibilityupdate BEFORE INSERT OR DELETE OR UPDATE ON rel_user_group FOR EACH ROW EXECUTE PROCEDURE membership_visibility_rel_user_group();

COMMIT;
