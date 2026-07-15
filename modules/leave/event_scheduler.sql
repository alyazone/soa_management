-- Enable scheduler
SET GLOBAL event_scheduler = ON;

-- Increase annual_leave for each staff by 1 every 28th of each month
CREATE EVENT IF NOT EXISTS `increment_annual_leave_monthly`
ON SCHEDULE EVERY 1 MONTH
STARTS (CURRENT_DATE + INTERVAL (28 - DAY(CURRENT_DATE) + IF(DAY(CURRENT_DATE) > 28, 30, 0)) DAY)
DO
  UPDATE leave_availability 
  SET annual_leave = annual_leave + 1;

-- Refresh count to default, except outstation leave
DELIMITER //

CREATE EVENT IF NOT EXISTS `reset_leave_balances_yearly`
ON SCHEDULE EVERY 1 YEAR
STARTS '2027-01-01 00:00:00'
DO
BEGIN
    -- 1. Update Carry Forward: 
    -- We take the remaining Annual Leave, but cap it at 10.
    -- LEAST(annual_leave, 10) ensures if they have 15, they get 10. If they have 4, they get 4.
    UPDATE leave_availability 
    SET carryforward_leave = LEAST(annual_leave, 10);

    -- 2. Reset the main balances to their default values
    UPDATE leave_availability 
    SET annual_leave = DEFAULT,
        emergency_leave = DEFAULT,
        medical_leave = DEFAULT,
        outstation_leave = DEFAULT,
        birthday_leave = DEFAULT;
END //

DELIMITER ;


-- USEFUL COMMAND ON MYSQL EVENT SCHEDULER
-- View all active events:
SHOW EVENTS;

-- Modify an event:
Use ALTER EVENT event_name ON SCHEDULE ...

-- Remove an event:
DROP EVENT IF EXISTS event_name;