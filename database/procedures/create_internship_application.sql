-- Procedure: create_internship_application
-- Creates a new internship application with validations
-- Parameters:
--   p_user_id: The ID of the user applying
--   p_internship_id: The ID of the internship
--   p_motivation_letter: Optional motivation letter
-- Returns: JSON object with success status, error_code, message, and application data

DROP PROCEDURE IF EXISTS create_internship_application;

CREATE PROCEDURE create_internship_application(
    IN p_user_id INT,
    IN p_internship_id INT,
    IN p_motivation_letter TEXT,
    OUT p_result JSON
)
BEGIN
    -- Declare variables for error handling
    DECLARE v_user_group_id INT DEFAULT NULL;
    DECLARE v_internship_exists INT DEFAULT 0;
    DECLARE v_is_active INT DEFAULT 0;
    DECLARE v_user_allowed INT DEFAULT 0;
    DECLARE v_application_exists INT DEFAULT 0;
    DECLARE v_new_application_id INT;
    
    -- Declare exit handler for exceptions
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_result = JSON_OBJECT(
            'success', FALSE,
            'error_code', 'DATABASE_ERROR',
            'message', 'Database error occurred'
        );
    END;
    
    START TRANSACTION;
    
    -- a) Check if user exists in database
    SELECT groups_id INTO v_user_group_id
    FROM users
    WHERE id = p_user_id;
    
    IF v_user_group_id IS NULL THEN
        ROLLBACK;
        SET p_result = JSON_OBJECT(
            'success', FALSE,
            'error_code', 'USER_NOT_FOUND',
            'message', CONCAT('Lietotājs ar ID ', p_user_id, ' nav atrasts datubāzē.')
        );
    ELSE
        -- b) Check if internship exists and is valid (has active time period)
        SELECT COUNT(*) INTO v_internship_exists
        FROM internships
        WHERE id = p_internship_id;
        
        IF v_internship_exists = 0 THEN
            ROLLBACK;
            SET p_result = JSON_OBJECT(
                'success', FALSE,
                'error_code', 'INTERNSHIP_NOT_FOUND',
                'message', CONCAT('Prakse ar ID ', p_internship_id, ' nav atrasta.')
            );
        ELSE
            -- Check if internship has active period for any group
            SELECT COUNT(*) INTO v_is_active
            FROM group_internships
            WHERE internship_id = p_internship_id
              AND start_at <= CURDATE()
              AND end_at >= CURDATE();
            
            IF v_is_active = 0 THEN
                ROLLBACK;
                SET p_result = JSON_OBJECT(
                    'success', FALSE,
                    'error_code', 'INTERNSHIP_NOT_VALID',
                    'message', CONCAT('Prakse ar ID ', p_internship_id, ' nav derīga vai nav aktīvs laika periods.')
                );
            ELSE
                -- c) Check if user is allowed to apply (their group is linked to this internship)
                SELECT COUNT(*) INTO v_user_allowed
                FROM group_internships gi
                INNER JOIN users u ON u.groups_id = gi.group_id
                WHERE u.id = p_user_id
                  AND gi.internship_id = p_internship_id
                  AND gi.start_at <= CURDATE()
                  AND gi.end_at >= CURDATE();
                
                IF v_user_allowed = 0 THEN
                    ROLLBACK;
                    SET p_result = JSON_OBJECT(
                        'success', FALSE,
                        'error_code', 'USER_NOT_ALLOWED',
                        'message', CONCAT('Lietotājam ar ID ', p_user_id, ' nav atļauts pieteikties praksei ar ID ', p_internship_id, '.')
                    );
                ELSE
                    -- Check if application already exists
                    SELECT COUNT(*) INTO v_application_exists
                    FROM applications
                    WHERE users_id = p_user_id
                      AND internships_id = p_internship_id;
                    
                    IF v_application_exists > 0 THEN
                        ROLLBACK;
                        SET p_result = JSON_OBJECT(
                            'success', FALSE,
                            'error_code', 'APPLICATION_ALREADY_EXISTS',
                            'message', CONCAT('Lietotājs ar ID ', p_user_id, ' jau ir pieteicies praksei ar ID ', p_internship_id, '.')
                        );
                    ELSE
                        -- All validations passed - create the application
                        INSERT INTO applications (users_id, group_id, internships_id, motivation_letter, created_at, updated_at)
                        VALUES (p_user_id, v_user_group_id, p_internship_id, p_motivation_letter, NOW(), NOW());
                        
                        SET v_new_application_id = LAST_INSERT_ID();
                        
                        COMMIT;
                        SET p_result = JSON_OBJECT(
                            'success', TRUE,
                            'error_code', NULL,
                            'message', 'Application created successfully',
                            'data', JSON_OBJECT(
                                'id', v_new_application_id,
                                'users_id', p_user_id,
                                'group_id', v_user_group_id,
                                'internships_id', p_internship_id,
                                'motivation_letter', p_motivation_letter
                            )
                        );
                    END IF;
                END IF;
            END IF;
        END IF;
    END IF;
END;
