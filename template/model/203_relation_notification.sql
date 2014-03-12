BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;


-- ###########################################
-- ############# tbl_notification ############
-- ###########################################

create table tbl_notification (
  id bigserial not null,
  name ltree not null,
  description varchar(255) not null,
  target_group varchar(16) not null, -- user, group, permission
  creation timestamp without time zone DEFAULT now() NOT NULL,
  modify timestamp without time zone DEFAULT now() NOT NULL,
  disabled boolean DEFAULT true NOT NULL,
  visible boolean DEFAULT false NOT NULL,

  CONSTRAINT tbl_notification_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_notification_unique_name UNIQUE (name),
  CONSTRAINT tbl_notification_creationmodify_check CHECK ((creation <= modify))
);

create table tbl_notification_protocol (
  id bigserial not null,
  name varchar(16) not null,
  description varchar(255) not null,
  creation timestamp without time zone DEFAULT now() NOT NULL,
  modify timestamp without time zone DEFAULT now() NOT NULL,
  disabled boolean DEFAULT true NOT NULL,
  visible boolean DEFAULT false NOT NULL,

  CONSTRAINT tbl_notification_protocol_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_notification_protocol_unique_name UNIQUE (name),
  CONSTRAINT tbl_notification_protocol_creationmodify_check CHECK ((creation <= modify))
);


-- define default way
create table rel_notification_protocol (
  notification_id bigint not null,
  protocol_id bigint not null,
  allowed boolean default true not null, -- whether or not to allow users choosing that option

  CONSTRAINT rel_notification_protocol_pkey PRIMARY KEY (notification_id, protocol_id),
  CONSTRAINT rel_notification_protocol_notification_fk FOREIGN KEY (notification_id) REFERENCES tbl_notification(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT rel_notification_protocol_protocol_fk FOREIGN KEY (protocol_id) REFERENCES tbl_notification_protocol(id) ON UPDATE CASCADE ON DELETE CASCADE

);


create table rel_user_notification_protocol (
  user_id bigint not null,
  notification_id bigint not null,
  protocol_id bigint not null,
  used boolean not null, -- overwrite default case of rel_notification_protocol
  creation timestamp without time zone DEFAULT now() NOT NULL,
  modify timestamp without time zone DEFAULT now() NOT NULL,
  disabled boolean DEFAULT true NOT NULL,
  visible boolean DEFAULT false NOT NULL,

  CONSTRAINT rel_user_pk PRIMARY KEY (user_id, notification_id, protocol_id),
  CONSTRAINT rel_user_notification_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT rel_user_notification_notification_fk FOREIGN KEY (notification_id) REFERENCES tbl_notification(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT rel_user_notification_protocol_fk FOREIGN KEY (protocol_id) REFERENCES tbl_notification_protocol(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT rel_user_notification_creationmodify_check CHECK ((creation <= modify))
);


-- ###################################################
-- ############# tbl_notification_message ############
-- ###################################################

create table tbl_notification_message (
  id bigserial not null,
  protocol_id bigint not null,
  user_id bigint NOT NULL,
  from_user_id bigint NOT NULL,
  notification_id bigint NOT NULL,
  content text not null,
  creation timestamp without time zone DEFAULT now() NOT NULL,
  modify timestamp without time zone DEFAULT now() NOT NULL,
  disabled boolean DEFAULT true NOT NULL,
  visible boolean DEFAULT false NOT NULL,

  CONSTRAINT tbl_notification_message_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_notification_message_creationmodify_check CHECK ((creation <= modify)),
  CONSTRAINT tbl_notification_message_content_check CHECK ((length((content)::text) > 0)),
  CONSTRAINT tbl_notification_message_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_notification_message_from_user_fk FOREIGN KEY (from_user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_notification_message_notification_fk FOREIGN KEY (notification_id) REFERENCES tbl_notification(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_notification_message_protocol_fk FOREIGN KEY (protocol_id) REFERENCES tbl_notification_protocol(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- ###################################################
-- ############# tbl_notification_browser ############
-- ###################################################

create table tbl_notification_browser (
  id bigserial not null,
  user_id bigint NOT NULL,
  content text not null,
  title character varying(255) not null,
  creation timestamp without time zone DEFAULT now() NOT NULL,

  CONSTRAINT tbl_notification_browser_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_notification_browser_content_check CHECK ((length((content)::text) > 0)),
  CONSTRAINT tbl_notification_browser_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);

COMMIT;