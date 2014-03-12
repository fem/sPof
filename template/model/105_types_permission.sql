BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

CREATE TYPE privacy_group AS ENUM ('anybody','logged in', 'sharing groups', 'owner');

COMMIT;
