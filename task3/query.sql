SELECT
    u.id AS user_id,
    u.email,
    p.name AS property_name,
    CASE
        WHEN p.type = 'string' THEN up.value_string
        WHEN p.type = 'int' THEN CAST(up.value_int AS CHAR)
        WHEN p.type = 'datetime' THEN CAST(up.value_datetime AS CHAR)
        END AS property_value
FROM
    users u
        JOIN
    users_properties up ON u.id = up.user_id
        JOIN
    properties p ON up.property_id = p.id
GROUP BY
    u.id, u.email, p.name, property_value;

SELECT
    u.id AS user_id,
    u.email,
    p.*,
    MAX(CASE WHEN p.name = 'name' THEN up.value_string END) AS name,
    MAX(CASE WHEN p.name = 'likes' THEN up.value_string END) AS likes,
    MAX(CASE WHEN p.name = 'dob' THEN up.value_string END) AS dob
FROM
    users u
        JOIN
    users_properties up ON u.id = up.user_id
        JOIN
    properties p ON up.property_id = p.id
GROUP BY
    u.id;
