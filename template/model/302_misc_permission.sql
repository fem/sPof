BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

CREATE OR REPLACE VIEW view_user_group_permission AS
    SELECT DISTINCT
        p.id,
        p.name,
        g2.id AS group_id,
        g2.name AS group_name,
        r.user_id,
        r.explicit,
        (g2.id = g1.id) AS direct_permission,
        g2.n_left
    FROM
        (SELECT
            rgup.group_id,
            rgup.user_id,
            rgup.permission_id,
            true AS explicit
        FROM rel_group_user_permission rgup
        UNION
        SELECT
            rgur.group_id,
            rgur.user_id,
            rrp.permission_id,
            false AS explicit
        FROM rel_group_user_role rgur
        JOIN rel_role_permission rrp ON rrp.role_id = rgur.role_id) r
    JOIN tbl_permission p ON p.id = r.permission_id
    JOIN tbl_group g1 ON g1.id = r.group_id
    JOIN tbl_group g2 ON g1.n_root = g2.n_root
        AND g2.n_left >= g1.n_left
        AND g2.n_right <= g1.n_right
    ORDER BY g2.n_left DESC;

COMMIT;