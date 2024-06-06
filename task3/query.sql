SET @sql = (
    SELECT CONCAT(
               'SELECT u.id AS user_id, u.email, ',
               GROUP_CONCAT(
                   DISTINCT CONCAT(
                   'MAX(CASE WHEN p.name = ''', `name`, ''' THEN ',
                   'CASE WHEN p.type = ''string'' THEN up.value_string ',
                   'WHEN p.type = ''int'' THEN up.value_int ',
                   'WHEN p.type = ''datetime'' THEN DATE_FORMAT(up.value_datetime, ''%Y-%m-%d'') END END) AS `', `name`, '`'
                            ) ORDER BY `id` SEPARATOR ', '
               ),
               ' FROM users u JOIN users_properties up ON u.id = up.user_id JOIN properties p ON up.property_id = p.id GROUP BY u.id, u.email'
           )
    FROM properties
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
