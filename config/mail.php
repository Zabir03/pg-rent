<?php
/**
 * Email (SMTP) configuration for Gmail.
 *
 * IMPORTANT — Gmail will NOT accept your normal password here.
 * You must create an "App Password":
 *   1. Turn on 2-Step Verification on your Google account.
 *   2. Go to https://myaccount.google.com/apppasswords
 *   3. Create an app password (name it "PG Rent"), copy the 16-character code.
 *   4. Paste that 16-char code into SMTP_PASS below (NOT your Gmail login password).
 */
declare(strict_types=1);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                 // 587 = TLS
define('SMTP_USER', 'zabirhassan7890@gmail.com'); // your full Gmail address
define('SMTP_PASS', 'fmdz hxnv pofw znks'); // the App Password, no spaces
define('SMTP_FROM', 'youremail@gmail.com'); // usually same as SMTP_USER
define('SMTP_FROM_NAME', 'PG Rent Manager');
