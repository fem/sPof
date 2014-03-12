BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;

CREATE OR REPLACE FUNCTION bytea_to_sid(id bytea)
RETURNS TEXT AS $$
DECLARE
	output_text TEXT;
	i INTEGER;
	INDEX TEXT[];
	bits TEXT;
	bit_chunk TEXT;
BEGIN
	INDEX := string_to_array('0,d,A,3,E,z,W,m,D,S,Q,l,K,s,P,b,N,c,f,j,5,I,t,C,i,y,o,G,2,r,x,h,V,J,k,-,T,w,H,L,9,e,u,X,p,U,a,O,v,4,R,B,q,M,n,g,1,F,6,Y,=,8,7,Z', ',');

	-- convert to string of bits
	bits = '';
	FOR i IN 1..octet_length(id) LOOP
		bits := bits || get_byte(id, i - 1)::bit(8);
	END LOOP;

	-- pad to length divisible by 6
	bits := rpad(bits, length(bits) + 6 - (length(bits) % 6), '0');

	-- encode bit chunks of 6 bits each using lookup table
	output_text := '';
	FOR i IN 1..((length(bits) / 6)) LOOP
		bit_chunk := substring(bits FROM (1 + (i - 1) * 6) FOR 6);
 
		output_text := output_text || INDEX[bit_chunk::bit(6)::integer + 1];
	END LOOP;

	RETURN output_text;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION id_oid_to_sid(id BIGINT, oid lo)
RETURNS TEXT AS $$
BEGIN
	-- sid only valid with non-null oid
	IF oid IS NULL THEN
		RETURN NULL;
	END IF;
	
	-- use pipe as delimiter to concat both numbers to a string
	-- calculate md5 hash (returning bytea)
	-- substring 8 bytes (half of hash)
	-- encode hash to string of alphanumeric characters
	RETURN bytea_to_sid(substring(digest(id || '|' || oid,'md5') FROM 1 FOR 8));
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION recalc_sid() RETURNS trigger AS $$
BEGIN
	IF NEW.content_oid IS NULL THEN
		NEW.sid := NULL;
	ELSIF NEW.sid IS NULL OR OLD.sid <> NEW.sid OR OLD.id <> NEW.id OR OLD.content_oid <> NEW.content_oid THEN
		NEW.sid := id_oid_to_sid(NEW.id, NEW.content_oid);
	END IF;
	RETURN NEW;
END;
$$ LANGUAGE plpgsql;

COMMIT;
