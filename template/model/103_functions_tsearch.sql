BEGIN;

SET default_tablespace = '';
SET default_with_oids = false;
SET ROLE postgres;


-- ###################################
-- ###### tsvector_update_group ######
-- ###################################

CREATE FUNCTION tsvector_update_group() RETURNS trigger
    LANGUAGE plpgsql
    AS $$                                                                                                                                                 
DECLARE                                                                                                                                                                                                                  
  x tsvector;                                                                                                                                                                                                            
BEGIN                                                                                                                                                                                                                    
    x := setweight(to_tsvector(coalesce(NEW.name,'')), 'A');                                                                                                                                                                 
    x := x || setweight(to_tsvector(coalesce(NEW.description,'')), 'B');                                                                                                                                                     
    x := x || setweight(to_tsvector(coalesce(NEW.shortname,'')), 'C');                                                                                                                                                       
                                                                                                                                                                                                                         
  NEW.fti := x;                                                                                                                                                                                                          
  RETURN NEW;                                                                                                                                                                                                            
END                                                                                                                                                                                                                      
$$;

-- ###################################
-- # tsvector_update_name_description 
-- ###################################

CREATE FUNCTION tsvector_update_name_description() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
  x tsvector;
begin
  x :=
     setweight(to_tsvector(coalesce(new.name,'')), 'A');
    x := x || setweight(to_tsvector(coalesce(new.description,'')), 'B');
  NEW.fti := x;
  return new;
end
$$;

-- ###################################
-- ###### tsvector_update_title ######
-- ###################################

CREATE FUNCTION tsvector_update_title() RETURNS trigger
    LANGUAGE plpgsql
    AS $$                                                                                                                                                 
DECLARE                                                                                                                                                                                                                  
  x tsvector;                                                                                                                                                                                                            
BEGIN                                                                                                                                                                                                                    
    x := setweight(to_tsvector(coalesce(NEW.title,'')), 'A');                                                                                                                                                                
                                                                                                                                                                                                                         
  NEW.fti := x;                                                                                                                                                                                                          
  RETURN NEW;                                                                                                                                                                                                            
END                                                                                                                                                                                                                      
$$;

-- ###########################################
-- ###### tsvector_update_title_content ######
-- ###########################################

CREATE FUNCTION tsvector_update_title_content() RETURNS trigger
    LANGUAGE plpgsql
    AS $$                                                                                                                                                 
DECLARE                                                                                                                                                                                                                  
  x tsvector;                                                                                                                                                                                                            
BEGIN                                                                                                                                                                                                                    
    x := setweight(to_tsvector(coalesce(NEW.title,'')), 'A');                                                                                                                                                                
    x := x || setweight(to_tsvector(coalesce(NEW.content,'')), 'B');                                                                                                                                                                
                                                                                                                                                                                                                         
  NEW.fti := x;                                                                                                                                                                                                          
  RETURN NEW;                                                                                                                                                                                                            
END                                                                                                                                                                                                                      
$$;

-- ###################################
--  tsvector_update_title_description 
-- ###################################

CREATE FUNCTION tsvector_update_title_description() RETURNS trigger
    LANGUAGE plpgsql
    AS $$                                                                                                                                     
DECLARE                                                                                                                                                                                                                  
  x tsvector;                                                                                                                                                                                                            
BEGIN                                                                                                                                                                                                                    
    x := setweight(to_tsvector(coalesce(NEW.title,'')), 'A');                                                                                                                                                                
    x := x || setweight(to_tsvector(coalesce(NEW.description,'')), 'B');                                                                                                                                                     
                                                                                                                                                                                                                         
  NEW.fti := x;                                                                                                                                                                                                          
  RETURN NEW;                                                                                                                                                                                                            
END                                                                                                                                                                                                                      
$$;

-- ###################################
-- ###### tsvector_update_user #######
-- ###################################

CREATE FUNCTION tsvector_update_user() RETURNS trigger
    LANGUAGE plpgsql
    AS $$                                                                                                                                                  
DECLARE                                                                                                                                                                                                                  
  x tsvector;                                                                                                                                                                                                            
BEGIN                                                                                                                                                                                                                    
    x := setweight(to_tsvector(coalesce(NEW.name,'')), 'A');                                                                                                                                                                 
    x := x || setweight(to_tsvector(coalesce(NEW.lastname,'')), 'B');                                                                                                                                                        
    x := x || setweight(to_tsvector(coalesce(NEW.firstname,'')), 'C');                                                                                                                                                       
                                                                                                                                                                                                                         
  NEW.fti := x;                                                                                                                                                                                                          
  RETURN NEW;                                                                                                                                                                                                            
END                                                                                                                                                                                                                      
$$;

COMMIT;