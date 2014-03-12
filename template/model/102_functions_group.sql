BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ###################################
-- ####### sort_rel_user_group #######
-- ###################################

CREATE FUNCTION sort_rel_user_group() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  rec record;
BEGIN
  IF (TG_OP = 'INSERT') THEN
    IF (NEW.sort=0) THEN
      RAISE DEBUG 'sort_rel_user_group(): Neue Zeile - Feld sort ist kleiner gleich 0, setze auf Maximum plus Eins.';
      SELECT CASE WHEN max(sort) IS NULL THEN 1 ELSE max(sort)+1 END INTO NEW.sort FROM rel_user_group WHERE user_id = NEW.user_id;
    ELSE
      RAISE DEBUG 'sort_rel_user_group(): Neue Zeile - sort=% ist groesser 0, nichts zu tun.',NEW.sort;
    END IF;
  ELSIF (TG_OP = 'UPDATE') THEN
    IF (NEW.sort=0) THEN
      RAISE EXCEPTION 'sort_rel_user_group(): Update - sort=0 darf nicht verwendet werden.';
    END IF;
    IF (OLD.sort<>NEW.sort) THEN
      SELECT * INTO rec FROM rel_user_group WHERE user_id = NEW.user_id AND sort = NEW.sort;
      IF FOUND THEN
        RAISE NOTICE 'sort_rel_user_group(): Update - neuer Wert sort=% existiert bei group_id = %, tausche und setze dort sort=%.',rec.sort,rec.group_id,OLD.sort;

        UPDATE rel_user_group SET sort = (SELECT max(sort)+1 FROM rel_user_group WHERE user_id = OLD.user_id)
        WHERE group_id = rec.group_id AND user_id = rec.user_id;

        UPDATE rel_user_group SET sort = NEW.sort
        WHERE group_id = OLD.group_id AND user_id = OLD.user_id;

        UPDATE rel_user_group SET sort = OLD.sort
        WHERE group_id = rec.group_id AND user_id = rec.user_id;
      ELSE
        RAISE DEBUG 'sort_rel_user_group(): Update - neuer Wert sort=% existiert noch nicht, nichts zu tun.',NEW.sort;
      END IF;
    ELSE
      RAISE DEBUG 'sort_rel_user_group(): Update - Feld sort=% ist gleich, nichts zu tun.',NEW.sort;
    END IF;
  END IF;
  RETURN NEW;
END
$$;


-- ####################################################
-- ####### membership_visibility_rel_user_group #######
-- ####################################################

-- This function ensures, that you're group membership is only visible once for
-- each group to the root group. You're indirect member of those groups anyway.
--
-- There are two conditions when this function has to 'fix' the membership
-- visibility. First is, that you're member in $group1 and you're joining a
-- subgroup of $group1. Then you're membership in $group1 is set to invisible.
-- The second condition is: That if you're a member in a $group2 an invisible
-- member in one of its predecessors $group3 and you're leaving $group2. Then
-- you have to set the $group3 visible again (unless you're member of one of the
-- subgroups of $group3, because you're still indirect member in that case)

CREATE FUNCTION membership_visibility_rel_user_group() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  rec record;
BEGIN

  IF ((TG_OP = 'INSERT' OR TG_OP = 'UPDATE') AND NEW.requested = FALSE AND NEW.visible IS TRUE) THEN

    -- on accept group request
    -- - update all parent groups and set visible = false 
    UPDATE rel_user_group r SET visible = FALSE
    FROM tbl_group g, tbl_group g2
    WHERE
      r.user_id = NEW.user_id
      AND requested IS FALSE
      AND group_id = g.id
      AND g.n_root = g2.n_root
      AND g.n_left <= g2.n_left
      AND g.n_right >= g2.n_right
      AND g2.id = NEW.group_id
      AND r.visible IS TRUE;
  ELSIF (TG_OP = 'DELETE' OR (TG_OP = 'UPDATE' AND NEW.requested = TRUE)) THEN

    -- on leave group
    -- update first parent which has no other subgroup with membership
    UPDATE rel_user_group r SET visible = TRUE
    FROM
      tbl_group g,
      tbl_group g2
    WHERE
      r.user_id = OLD.user_id
      AND requested IS FALSE
      AND group_id = g.id
      AND g.n_root = g2.n_root
      AND g.n_left < g2.n_left
      AND g.n_right > g2.n_right
      AND g2.id = OLD.group_id
      AND r.visible IS FALSE
      AND NOT EXISTS (
        SELECT TRUE
        FROM rel_user_group r2
        JOIN tbl_group g3 ON r2.group_id = g3.id
        WHERE
          g3.n_left > g.n_left
          AND r2.requested IS FALSE
          AND r2.visible IS TRUE
          AND r2.disabled IS FALSE
          AND g3.n_right < g.n_right
          AND r2.user_id = r.user_id);
  END IF;

  IF (TG_OP = 'INSERT' OR TG_OP = 'UPDATE') THEN
    RETURN NEW;
  ELSE
    RETURN OLD;
  END IF;
END
$$;

COMMIT;