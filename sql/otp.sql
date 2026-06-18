-- ============================================================
-- OTP email verification — run this in phpMyAdmin (SQL tab)
-- ============================================================

-- store of one-time codes
CREATE TABLE IF NOT EXISTS email_otps (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(160) NOT NULL,
  otp_hash   VARCHAR(255) NOT NULL,      -- the code is hashed, never stored plain
  purpose    ENUM('signup','login') NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts   TINYINT NOT NULL DEFAULT 0, -- wrong tries, locks after 5
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_email (email)
) ENGINE=InnoDB;

-- track whether an owner verified their email at least once
ALTER TABLE users
  ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0;

-- existing accounts (admin, demo owner) are already trusted — mark verified
UPDATE users SET email_verified = 1;
