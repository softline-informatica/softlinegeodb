### import script

This script is useless without data

You're more interested in this [7z file with MySQL dumps](https://github.com/softline-informatica/softlinegeodb/blob/main/softlinegeodb-spain-minimal-db.sql.7z) at root directory of the project.

To properly configure the **.htaccess** file for Apache:
```
if index.php is loaded through: localhost/softlinegeodb/src-php-import-script/, 
          RewriteBase should be: /softlinegeodb/src-php-import-script/
if index.php is located at localhost/softlinegeodb, this should be 
          RewriteBase should be: /softlinegeodb/
if index.php is located at localhost (root dir), this should be 
          RewriteBase should be: /
```
