BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

-- ######################
-- ## INTEGRITY CHECK ###
-- ######################

CREATE FUNCTION nestedtree_sort_check(table_name name, n_sort_field character varying, n_sort_order character varying) RETURNS boolean
   LANGUAGE plpgsql
   AS $_$
DECLARE
  sort_order_valid boolean;
  sort_field_valid boolean;
BEGIN
   -- test if sort field exists in table
   -- http://www.postgresql.org/docs/9.2/static/infoschema-columns.html
   IF n_sort_field IS NOT NULL THEN
      EXECUTE 'SELECT TRUE from information_schema.columns where table_name ='|| quote_literal(table_name)||' AND column_name = '|| quote_literal(n_sort_field) INTO sort_field_valid;
      IF sort_field_valid <> TRUE THEN
        sort_field_valid := FALSE;
      END IF;

      -- test if sort order is valid
      EXECUTE 'SELECT '|| quote_literal(n_sort_order)||' IN (NULL, '|| quote_literal('ASC')||' , '||quote_literal ('DESC')||')' INTO sort_order_valid;

      RETURN sort_field_valid AND sort_order_valid;
   ELSE
      RETURN TRUE;
   END IF;
END
$_$;

-- ################################
-- ## REBUILD IS PERFORMED HERE ###
-- ################################

CREATE FUNCTION nestedtree_rebuild(parent_id bigint, table_name name, sort_field character varying, sort_order character varying) RETURNS void
    LANGUAGE plpgsql
    AS $_$
DECLARE
  element record;
  parent record;
  query text := 'SELECT id, n_sort_order, n_sort_field FROM '||table_name||' WHERE parent = '||parent_id;
BEGIN

  -- check if we have a custom order and use it
  IF sort_field IS NOT NULL AND sort_order IS NOT NULL THEN
    query := query||' ORDER BY '||sort_field||' '||sort_order;
  END IF;

  FOR element IN EXECUTE query
  LOOP
    -- if we select parent inside the loop, it performs better, awkward
    EXECUTE 'SELECT n_right, n_root, n_level, n_sort_field, n_sort_order FROM '||table_name||' WHERE id = '||parent_id INTO parent;

    EXECUTE 'UPDATE '||table_name||' SET n_left = n_left + 2 WHERE n_root = '||parent.n_root||' and n_left > '||parent.n_right||' and n_right >= '||parent.n_right;
    EXECUTE 'UPDATE '||table_name||' SET n_right = n_right + 2 WHERE n_root = '||parent.n_root||' and n_right >= '||parent.n_right;
    EXECUTE 'UPDATE '||table_name||' SET n_left = '||parent.n_right||', n_right = '||parent.n_right||' + 1, n_root = '||parent.n_root||', n_level = '||parent.n_level||' + 1 WHERE id = '||element.id;

    -- node childs are sorted like the parent wants them to be sorted
    PERFORM * FROM nestedtree_rebuild(element.id,table_name, element.n_sort_field, element.n_sort_order);
  END LOOP;
END
$_$;

-- ############################
-- ## REBUILD FROM TREEROOT ###
-- ############################

CREATE FUNCTION nestedtree_rebuild_from_root(root_id bigint, table_name name) RETURNS void
    LANGUAGE plpgsql
    AS $_$
DECLARE
  root record;
BEGIN
  -- get root for sort order
  EXECUTE 'SELECT n_root, n_sort_order, n_sort_field, parent FROM '||table_name||' WHERE id = '||root_id INTO root;
    
  IF root.parent IS NOT NULL AND root_id <> root.n_root THEN
    RAISE EXCEPTION 'rebuild_from_root called with non-root node';
  END IF;

  -- clear tree information for our root_id
  EXECUTE 'UPDATE '||table_name||' SET n_left = null, n_right = null, n_root = null, n_level = null WHERE n_root = '||root_id;
  EXECUTE 'UPDATE '||table_name||' SET n_left = 1, n_right = 2, n_root = id, n_level = 0 WHERE id = '||root_id;

  -- rebuild this tree respecting sort orders
  PERFORM * FROM nestedtree_rebuild(root_id,table_name, root.n_sort_field, root.n_sort_order);
END
$_$;

-- ########################################
-- ## REBUILD MULTIPLE SUBTREES AT ONCE ###
-- ########################################

CREATE FUNCTION nestedtree_rebuild_all_subtrees(table_name name) RETURNS void
    LANGUAGE plpgsql
    AS $_$
DECLARE
  root record;
BEGIN
  -- #### find parent element, if parent != 0 #####################################################################
    FOR root IN EXECUTE 'SELECT id FROM '||table_name||' WHERE parent IS NULL'
    LOOP
      PERFORM * FROM nestedtree_rebuild_from_root(root.id, table_name);
    END LOOP;
END
$_$;

-- #############
-- ## UPDATE ###
-- #############

CREATE FUNCTION nestedtree_update() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  parent RECORD;
BEGIN
  -- prevent trigger from triggering itself (during nested_tree_update etc.)
  -- depth 0: no invokation as trigger
  -- depth 1: first invokation as trigger
  -- depth 2+: nested triggering -> prevent this
  IF pg_trigger_depth() > 1 THEN
    RETURN NULL;
  END IF;

  PERFORM 'LOCK TABLES '||TG_TABLE_NAME||' write';

  -- #################################################################################################################
  -- #### INSERT Statement ###########################################################################################
  -- #################################################################################################################
  IF (TG_OP = 'INSERT') THEN

    -- #### if parent is null, following query cannot be used; set temporaly to 0 ####################################
    IF NEW.parent IS NULL THEN
      NEW.parent = 0;
    END IF;

    -- #### find parent element, if parent != 0 #####################################################################
    FOR parent IN EXECUTE 'SELECT n_root FROM '||TG_TABLE_NAME||' WHERE id = '||NEW.parent LOOP END LOOP;

    -- #### if parent element could be found, shift n_left, n_right of children for new child########################
    IF FOUND THEN
      -- rebuild tree
      PERFORM * FROM nestedtree_rebuild_from_root(parent.n_root, TG_TABLE_NAME);

    -- #### if there is no parent element, insert current node as new root node #####################################
    ELSE
      -- set initial values (setting NEW.columns useless, since we are an AFTER trigger) 
      EXECUTE 'UPDATE '|| TG_TABLE_NAME ||' SET n_left = 1, n_right = 2, n_level = 0, n_root = ' || NEW.id || ' WHERE id = ' || NEW.id;
    END IF;

  -- #################################################################################################################
  -- #### UPDATE Statement ###########################################################################################
  -- #################################################################################################################
  ELSIF (TG_OP = 'UPDATE') THEN
    -- 2 cases: old.parent == new.parent || old.parent <> new.parent
    IF OLD.parent <> NEW.parent OR (OLD.parent IS NULL AND NEW.parent IS NOT NULL) OR (OLD.parent IS NOT NULL AND NEW.parent IS NULL) THEN

      -- check if we got not a new root
      IF NEW.parent IS NOT NULL THEN
        -- get parent
        EXECUTE 'SELECT n_root, n_left FROM '||TG_TABLE_NAME||' WHERE id = '||NEW.parent INTO parent;

        -- check if new parent is 0/NULL -> new root node
        IF NEW.n_root <> parent.n_root THEN
          RAISE NOTICE 'you set parent to a node in a different tree - trying to move node and its children to the foreign tree';

          -- update NEW.n_root since this setting doesn't modifies itself 
          EXECUTE 'UPDATE '|| TG_TABLE_NAME ||' SET n_root = ' || parent.n_root || ' WHERE id = ' || NEW.id; 
          PERFORM nestedtree_rebuild_from_root(parent.n_root, TG_TABLE_NAME);
        ELSE
          RAISE NOTICE 'you set parent to a node in the same tree - trying to move node and its children within the same tree';

          -- sanity check for correct node movement, raise exception if failing
          IF parent.n_left BETWEEN NEW.n_left AND NEW.n_right THEN
            RAISE EXCEPTION 'Cannot move: source entry contains destination';
          END IF;
        END IF;
      ELSE
        PERFORM * FROM nestedtree_rebuild_from_root(NEW.id, TG_TABLE_NAME);
      END IF;

      -- rebuild old tree, if we don't move his root
      IF OLD.n_root <> OLD.id THEN
        PERFORM * FROM nestedtree_rebuild_from_root(OLD.n_root, TG_TABLE_NAME);
      END IF;
    ELSE -- OLD.parent = NEW.parent
      -- check if sort order or field has changed
      IF OLD.n_sort_field <> NEW.n_sort_field
        OR OLD.n_sort_order <> NEW.n_sort_order
        OR NEW.n_sort_field IS NOT NULL AND OLD.n_sort_field IS NULL
        OR NEW.n_sort_order IS NOT NULL AND OLD.n_sort_order IS NULL
      THEN
        RAISE NOTICE 'rebuild because of sort properties';
        PERFORM * FROM nestedtree_rebuild_from_root(OLD.n_root, TG_TABLE_NAME);
      END IF;

    END IF;

  -- #################################################################################################################
  -- #### DELETE Statement ###########################################################################################
  -- #################################################################################################################
  ELSIF (TG_OP = 'DELETE') THEN
    -- we can only delete leaves (ON DELETE RESTRICT)
    EXECUTE 'UPDATE '||TG_TABLE_NAME||' set n_left = n_left - 2 where n_root = '||OLD.n_root||' AND n_left > '||OLD.n_left;
    EXECUTE 'UPDATE '||TG_TABLE_NAME||' set n_right = n_right - 2 where n_root = '||OLD.n_root||' AND n_right > ' ||OLD.n_right;
  END IF;

  PERFORM 'UNLOCK TABLES';
  RETURN NEW;
END
$$;

COMMIT;
