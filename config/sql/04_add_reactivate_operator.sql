DROP PROCEDURE IF EXISTS `sp_reactivate_operator`;

DELIMITER //
CREATE PROCEDURE `sp_reactivate_operator`(
    IN pOperatorID INT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM `operator`
        WHERE OperatorID = pOperatorID
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Operator does not exist';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM `operator`
        WHERE OperatorID = pOperatorID
          AND Active = 1
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Operator is already active';
    END IF;

    UPDATE `operator`
    SET Active = 1
    WHERE OperatorID = pOperatorID;
END //
DELIMITER ;