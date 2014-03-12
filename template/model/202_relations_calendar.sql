BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- #########################
-- ####### tbl_event #######
-- #########################

CREATE TABLE tbl_event (
    id bigserial NOT NULL,

    original_uid character varying(512),

    user_id bigint NOT NULL,
    parent_id bigint,
    group_id bigint,

    title character varying(256) NOT NULL,
    description character varying(8192),
    locality character varying(128),
    beginning timestamp without time zone NOT NULL,
    length interval NOT NULL,
    public boolean default true NOT NULL,

    repeat varchar(256), -- http://www.kanzaki.com/docs/ical/recur.html
    tags json,

    creation timestamp without time zone DEFAULT now() NOT NULL,
    modify timestamp without time zone DEFAULT now() NOT NULL,
    disabled boolean DEFAULT true NOT NULL,
    visible boolean DEFAULT false NOT NULL,
    fti tsvector,

    old_announce_id bigint,
    old_carpool_id bigint,

    CONSTRAINT tbl_event_pkey PRIMARY KEY (id),
    CONSTRAINT tbl_event_unique_original_uid UNIQUE (original_uid),
    CONSTRAINT tbl_event_creationmodify_check CHECK ((creation <= modify)),
    CONSTRAINT tbl_event_parentorrepeat_check CHECK ((repeat is null or parent_id is null)),
    CONSTRAINT tbl_event_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT tbl_event_event_fk FOREIGN KEY (parent_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT tbl_event_group_fk FOREIGN KEY (group_id) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE CASCADE
);

-- indexes
CREATE INDEX new_calendar_event_fti_idx ON tbl_event USING gist (fti);

-- triggers
CREATE TRIGGER tsvectorupdate BEFORE INSERT OR UPDATE ON tbl_event FOR EACH ROW EXECUTE PROCEDURE tsvector_update_title_description();


-- ##############################
-- ####### tbl_event_date #######
-- ##############################


-- rdate
CREATE TABLE tbl_event_date (
    event_id bigint NOT NULL,
    beginning timestamp without time zone NOT NULL,

    CONSTRAINT tbl_event_date_pkey PRIMARY KEY (event_id, beginning),
    CONSTRAINT tbl_event_event_fk FOREIGN KEY (event_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE
);


-- ####################################
-- ####### tbl_event_exception ########
-- ####################################

-- exception dates to recurring events
-- exdate
CREATE TABLE tbl_event_exception (
  event_id bigint not null,
  scheduled_time timestamp without time zone,

  CONSTRAINT tbl_event_exception_pk PRIMARY KEY(event_id, scheduled_time),
  CONSTRAINT tbl_event_exception_event_fk FOREIGN KEY (event_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE
);


-- ###################################
-- ######### tbl_file_event ##########
-- ###################################

CREATE TABLE tbl_file_event (
  event_id bigint not null,

  CONSTRAINT tbl_file_event_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_file_event_mimetype_check CHECK (((mimetype)::text = 'image/jpeg'::text) OR ((mimetype)::text = 'image/png'::text)),
  CONSTRAINT tbl_file_event_group_fk FOREIGN KEY (group_id) REFERENCES tbl_group(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_file_event_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_file_event_event_fk FOREIGN KEY (event_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE
)
INHERITS (tbl_file);


-- ################################
-- ### tbl_statistic_file_event ###
-- ################################

CREATE TABLE tbl_statistic_file_event (
  CONSTRAINT tbl_statistic_file_event_pkey PRIMARY KEY (id),
  CONSTRAINT tbl_statistic_file_event_file_fk FOREIGN KEY (file_id) REFERENCES tbl_file_event(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_statistic_file_event_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE SET NULL
)
INHERITS (tbl_statistic_file);

-- rules
CREATE RULE tbl_statistic_file_insert_event AS ON INSERT TO tbl_statistic_file WHERE (new.file_id IN (SELECT tbl_file_event.id FROM tbl_file_event)) DO INSTEAD
  INSERT INTO tbl_statistic_file_event (id, accessdate, accesscount, period, user_id, ip, file_id)
  VALUES (new.id, new.accessdate, new.accesscount, new.period, new.user_id, new.ip, new.file_id)
;


-- ################################
-- ####### tbl_rating_event #######
-- ################################

CREATE TABLE tbl_rating_event (
  user_id bigint NOT NULL,
  event_id bigint NOT NULL,
  rate smallint NOT NULL,
  CONSTRAINT tbl_rating_event_pkey PRIMARY KEY (user_id, event_id),
  CONSTRAINT tbl_rating_event_rate_check CHECK (((rate >= (-5)) AND (rate <= 5))),
  CONSTRAINT tbl_rating_event_event_fk FOREIGN KEY (event_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT tbl_rating_event_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);


-- ########################################
-- ############ rel_user_event ############
-- ########################################

CREATE TABLE rel_user_event (
  event_id bigint NOT NULL,
  user_id bigint NOT NULL,
  label varchar(16),
  CONSTRAINT rel_user_event_pkey PRIMARY KEY (event_id, user_id),
  CONSTRAINT rel_user_event_event_fk FOREIGN KEY (event_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT rel_user_event_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);


-- ###############################################
-- ############ rel_user_event_ignore ############
-- ###############################################

CREATE TABLE rel_user_event_ignore (
  event_id bigint NOT NULL,
  user_id bigint NOT NULL,
  CONSTRAINT rel_user_event_ignore_pkey PRIMARY KEY (event_id, user_id),
  CONSTRAINT rel_user_event_ignore_event_fk FOREIGN KEY (event_id) REFERENCES tbl_event(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT rel_user_event_ignore_user_fk FOREIGN KEY (user_id) REFERENCES tbl_user(id) ON UPDATE CASCADE ON DELETE CASCADE
);


-- ################################
-- ###### parse_occurrences #######
-- ################################

CREATE OR REPLACE FUNCTION parse_occurrences(rule text, start timestamp with time zone) RETURNS timestamp[]
AS $$
from dateutil.rrule import *
from dateutil.parser import *
from dateutil.tz import *
from pytz import timezone
import datetime

dt_unaware = datetime.datetime(*(parse(start).timetuple()[:6]))

localtz = timezone('UTC')
dt_aware = localtz.localize(dt_unaware)

dates = list(rrulestr(rule, dtstart=dt_aware))
return [str(a) for a in dates]
$$ LANGUAGE plpythonu;


-- #####################################
-- ###### cache_repeating_events #######
-- #####################################

CREATE OR REPLACE FUNCTION cache_repeating_events() RETURNS trigger AS
$$
BEGIN

  IF (TG_OP = 'INSERT' AND NEW.repeat IS NOT NULL) THEN
    EXECUTE 'INSERT INTO tbl_event_date (event_id, beginning) SELECT '||NEW.id||' AS event_id, unnest(parse_occurrences('||quote_literal(NEW.repeat)||', '''||NEW.beginning||'''::timestamp))';
    RETURN NEW;
  ELSIF (TG_OP = 'UPDATE' AND (NEW.repeat != OLD.repeat OR NEW.beginning != OLD.beginning)) THEN
    IF (OLD.repeat IS NOT NULL) THEN
      EXECUTE 'DELETE FROM tbl_event_date WHERE event_id = '||OLD.id;
    END IF;
    IF (NEW.repeat IS NOT NULL) THEN
      EXECUTE 'INSERT INTO tbl_event_date (event_id, beginning) SELECT '||NEW.id||' AS event_id, unnest(parse_occurrences('||quote_literal(NEW.repeat)||', '''||NEW.beginning||'''::timestamp))';
    END IF;
    RETURN NEW;
  ELSIF (TG_OP = 'DELETE') THEN
    EXECUTE 'DELETE FROM tbl_event_date WHERE event_id = '||OLD.id;
    RETURN OLD;
  END IF;

  RETURN NULL;

END
$$ LANGUAGE plpgsql;

CREATE TRIGGER cache_dates AFTER INSERT OR UPDATE OR DELETE ON tbl_event FOR EACH ROW EXECUTE PROCEDURE cache_repeating_events();


-- #############################
-- ###### view_event_all #######
-- #############################

CREATE VIEW view_event_all AS
  SELECT
    id,
    beginning,
    length,
    title,
    description,
    locality,
    user_id,
    group_id,
    public,
    creation,
    modify,
    disabled,
    visible
  FROM tbl_event
  WHERE repeat IS NULL
  UNION
  SELECT
    e.id,
--    unnest(parse_occurrences(CONCAT(repeat,';UNTIL=', to_char(:end::timestamp, 'YYYYMMDDT000000'::text)), beginning::timestamp)) AS beginning,
    d.beginning,
    e.length,
    e.title,
    e.description,
    e.locality,
    e.user_id,
    e.group_id,
    e.public,
    e.creation,
    e.modify,
    e.disabled,
    e.visible
  FROM tbl_event e
  JOIN tbl_event_date d ON d.event_id=e.id
  WHERE repeat IS NOT NULL
;


-- ##############################
-- ###### view_event_only #######
-- ##############################

CREATE VIEW view_event_only AS
  SELECT
    id,
    beginning,
    length,
    title,
    description,
    locality,
    user_id,
    group_id,
    public,
    creation,
    modify,
    disabled,
    visible
  FROM ONLY tbl_event
  WHERE repeat IS NULL
  UNION
  SELECT
    e.id,
--    unnest(parse_occurrences(CONCAT(repeat,';UNTIL=', to_char(:end::timestamp, 'YYYYMMDDT000000'::text)), beginning::timestamp)) AS beginning,
    d.beginning,
    e.length,
    e.title,
    e.description,
    e.locality,
    e.user_id,
    e.group_id,
    e.public,
    e.creation,
    e.modify,
    e.disabled,
    e.visible
  FROM ONLY tbl_event e
  JOIN tbl_event_date d ON d.event_id=e.id
  WHERE repeat IS NOT NULL
;

COMMIT;


