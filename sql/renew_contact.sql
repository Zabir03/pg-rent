-- ============================================================
-- Renewal contact details (shown to owners on the Subscription tab)
-- Run in phpMyAdmin → SQL tab. Edit the values to your real contacts.
-- ============================================================

INSERT INTO settings (owner_id, setting_key, setting_val) VALUES
(NULL, 'renew_email', 'zabirhassan7890@gmail.com'),
(NULL, 'renew_whatsapp', '+916900541980')
ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val);

-- To change later, just re-run with new values, or:
-- UPDATE settings SET setting_val='you@email.com' WHERE setting_key='renew_email' AND owner_id IS NULL;
-- UPDATE settings SET setting_val='+9198XXXXXXXX' WHERE setting_key='renew_whatsapp' AND owner_id IS NULL;
