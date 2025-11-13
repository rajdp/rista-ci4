-- EdQuill V2: Outbox Triggers (MySQL 5.7 compatible)
-- These triggers enqueue events to t_event_outbox when self-registration status changes
-- Uses CONCAT/QUOTE() for JSON construction (MySQL 5.7 compatible)

DELIMITER $$

-- On self-registration status change
DROP TRIGGER IF EXISTS trg_ssr_status_outbox$$
CREATE TRIGGER trg_ssr_status_outbox AFTER UPDATE ON student_self_registrations
FOR EACH ROW BEGIN
  IF (OLD.status <> NEW.status) THEN
    INSERT INTO t_event_outbox (school_id, event_type, payload_json)
    VALUES (
      NEW.school_id, 'selfreg.status.updated',
      CONCAT('{',
        '"selfreg_id":', NEW.id, ',',
        '"old":', QUOTE(OLD.status), ',',
        '"new":', QUOTE(NEW.status), ',',
        '"ts":', QUOTE(NOW()), '}')
    );
  END IF;
END$$

-- On conversion to student/parents
DROP TRIGGER IF EXISTS trg_ssr_converted_outbox$$
CREATE TRIGGER trg_ssr_converted_outbox AFTER UPDATE ON student_self_registrations
FOR EACH ROW BEGIN
  IF (OLD.converted_at IS NULL AND NEW.converted_at IS NOT NULL) THEN
    INSERT INTO t_event_outbox (school_id, event_type, payload_json)
    VALUES (
      NEW.school_id, 'selfreg.converted',
      CONCAT('{',
        '"selfreg_id":', NEW.id, ',',
        '"student_user_id":', IFNULL(NEW.converted_student_user_id, 'null'), ',',
        '"primary_guardian_id":', IFNULL(NEW.converted_primary_guardian_id, 'null'), ',',
        '"ts":', QUOTE(NEW.converted_at), '}')
    );
  END IF;
END$$

DELIMITER ;

