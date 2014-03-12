BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ### view_user_group_membership ####
-- ###################################

CREATE VIEW view_user_group_membership AS
    SELECT DISTINCT
      g2.id,
      g2.parent,
      g2.name,
      g2.shortname,
      g2.description,
      g2.creation,
      g2.modify,
      g2.disabled,
      g2.visible,
      g2.n_left,
      g2.n_right,
      g2.n_root,
      g2.n_level,
      g2.show_members,
      g2.show_features,
      g2.joinable,
      g2.subgroups_allowed,
      r.user_id AS member_user_id,
      (g2.id = g1.id) AS direct_membership,
      CASE WHEN (g2.id = g1.id) THEN r.sort ELSE NULL::integer END AS sort
    FROM rel_user_group r
    JOIN tbl_group g1 ON g1.id = r.group_id
    JOIN tbl_group g2 ON g2.n_root = g1.n_root
    WHERE
      r.requested IS FALSE
      AND r.visible IS TRUE
      AND r.disabled IS FALSE
      AND g2.n_left <= g1.n_left
      AND g2.n_right >= g1.n_right
    ORDER BY
      g2.n_root,
      g2.n_left;

-- ###################################
-- ######## count_group_user #########
-- ###################################

CREATE FUNCTION count_group_user(bigint) RETURNS bigint
    LANGUAGE sql STABLE
    AS $_$
  SELECT count(user_id) FROM rel_user_group WHERE group_id = $1
$_$;

-- ###################################
-- ### count_group_user_recursive ####
-- ###################################

CREATE FUNCTION count_group_user_recursive(bigint) RETURNS bigint
    LANGUAGE plpgsql STABLE
    AS $_$
DECLARE
  sum INT8;
  element RECORD;
BEGIN
  sum = count_group_user($1);
  FOR element IN SELECT id FROM tbl_group WHERE parent = $1 LOOP
    sum = sum + count_group_user_recursive(element.id);
  END LOOP;
  RETURN sum;
END
$_$;

COMMIT;