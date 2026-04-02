-- Trigger: log_application_created
-- Automatically creates activity log entry when a new internship application is created
-- Fires: AFTER INSERT on applications table

DROP TRIGGER IF EXISTS log_application_created;

CREATE TRIGGER log_application_created
AFTER INSERT ON applications
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (
        user_id,
        action_type,
        description,
        model_type,
        model_id,
        properties,
        status,
        created_at,
        updated_at
    )
    SELECT
        NEW.users_id,
        'apply_internship_trigger',
        CONCAT('Lietotājs ', COALESCE(u.name, 'Nezināms'), ' pieteicās praksei "', COALESCE(i.name, 'Nezināma'), '" grupā "', COALESCE(g.name, 'Nezināma'), '"'),
        'App\\Models\\Application',
        NEW.id,
        JSON_OBJECT(
            'user_id', NEW.users_id,
            'group_id', NEW.group_id,
            'internship_id', NEW.internships_id,
            'motivation_letter', NEW.motivation_letter,
            'approved_at', NEW.approved_at
        ),
        'success',
        NOW(),
        NOW()
    FROM users u
    LEFT JOIN internships i ON i.id = NEW.internships_id
    LEFT JOIN `groups` g ON g.id = NEW.group_id
    WHERE u.id = NEW.users_id;
END;
