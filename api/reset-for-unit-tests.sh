rm database/database.sqlite
touch database/database.sqlite
php vendor/doctrine/orm/bin/doctrine orm:schema-tool:update --force --dump-sql
