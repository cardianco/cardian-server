## Notes:
1. `ST_CONTAINS`: <sub>[[1][sqlpoly]]</sub><sub>[[2][sqlpolyfunc]]</sub>
    ```sql
    SELECT name FROM places WHERE ST_CONTAINS(@paris, coordinates)
    ```
2. 1-65535 are legit `TCP` ports and it is true that 1-1023 are for *well known port* services.
3. So if you write a `TCP` service that listens on port `20001`.
   You might be good today... and tomorrow.
   But one day your service may startup and attempt to bind to `20001` and it will fail
   because it was taken as an *ephemeral port*.<sub>[[3][portrnge]]</sub>
4. Two ways to  MySQL partitioning vs Events.<sub>[[4][sqlevent]]</sub><br>
    <small>Partitioning: *In effect, different portions of a table are stored as separate tables in different locations*.</small><sub>[[5][sqlpartition]]</sub>
5. MySQL text can't have default values duo to store complexity.<sub>[[6][msqltxtdoc]]</sub><sub>[[7][msqltxtso]]</sub>
   > Each BLOB or TEXT value is represented internally by a separately allocated object. This is in contrast to all other data types, for which storage is allocated once per column when the table is opened.
6. Recommendation for hashing passwords is Argon2.<sub>[[8][phc]]</sub><sub>[[9][chashse]]</sub>
7. Code for a single query to insert or update a value if one already exists.<sub>[[10][updateifduplicate]]</sub>
   ```sql
    INSERT INTO t1 (a,b,c) VALUES (1,2,3)
        ON DUPLICATE KEY UPDATE c=c+1;
   ```
8. `JSON_MERGE_PRESERVE(json_doc, ...)` Merges two or more JSON documents and returns the merged result. <sub>[[11][mergejsonpatch]]</sub>

<!-- References -->

[sqlpoly]: https://marcgg.com/blog/2017/03/13/mysql-viewport-gis "MySQL geo poly"
[sqlpolyfunc]: https://dev.mysql.com/doc/refman/5.7/en/gis-polygon-property-functions.html "MySQL polygon property functions: `ST_CONTAINS`"
[sqlevent]: https://www.mysqltutorial.org/mysql-triggers/working-mysql-scheduled-event "Working with MySQL Scheduled Event"
[sqlpartition]: https://dev.mysql.com/doc/refman/8.0/en/partitioning-overview.html "MySQl partitioning overview"
[portrnge]: https://serverfault.com/a/873488 "Change the system ephemeral port range policy on server"
[msqltxtdoc]: https://dev.mysql.com/doc/refman/8.0/en/blob.html "11.3.4 The BLOB and TEXT Types"
[msqltxtso]: https://stackoverflow.com/q/3466872 "Why can't a text column have a default value in MySQL"
[chashse]: https://crypto.stackexchange.com/q/30785 "Password hashing security of argon2 vs bcrypt/PBKDF2"
[phc]: https://www.password-hashing.net "Password Hashing Competition"
[updateifduplicate]: https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html "Insert value or Update if exist"
[mergejsonpatch]: https://dev.mysql.com/doc/refman/5.7/en/json-modification-functions.html#function_json-merge-patch "MySQL, merging two Json together"