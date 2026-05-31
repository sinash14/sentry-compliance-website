<?php
/**
 * ============================================================
 * Sentry Compliance — Contact Form Handler
 * ============================================================
 * File:    form-handler.php
 * Upload:  Place this file in the SAME folder as index.html
 *          on your DreamHost server (sentrycompliance.com.au/)
 *
 * SETUP — fill in the three values below, then upload.
 * ============================================================
 */

// ── 1. WHERE TO SEND THE FORM SUBMISSIONS ───────────────────
//       This is the inbox that receives the audit requests.
$to_email = 'info@sentrycompliance.com.au';

// ── 2. YOUR DREAMHOST EMAIL PASSWORD ────────────────────────
//       The password for info@sentrycompliance.com.au
//       Set this in DreamHost panel → Mail → Manage Email
$smtp_password = 'ADD_YOUR_EMAIL_PASSWORD_HERE';

// ── 3. REPLY-TO NAME ────────────────────────────────────────
//       Shown as the sender name in your inbox
$from_name = 'Sentry Compliance Website';

// ============================================================
// DO NOT EDIT BELOW THIS LINE
// ============================================================

// ── Security: only accept POST requests ─────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// ── Sanitise all inputs ─────────────────────────────────────
function clean($value) {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

$name          = clean($_POST['name']          ?? '');
$pharmacy      = clean($_POST['pharmacy']      ?? '');
$suburb        = clean($_POST['suburb']        ?? '');
$phone         = clean($_POST['phone']         ?? '');
$email         = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$units         = clean($_POST['units']         ?? '');
$current_setup = clean($_POST['current_setup'] ?? '');
$message       = clean($_POST['message']       ?? '');

// ── Validate required fields ─────────────────────────────────
$errors = [];
if (empty($name))     $errors[] = 'Name is required.';
if (empty($pharmacy)) $errors[] = 'Pharmacy name is required.';
if (empty($suburb))   $errors[] = 'Suburb is required.';
if (empty($phone))    $errors[] = 'Phone is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

// ── Block spam: basic honeypot check ─────────────────────────
// (Add a hidden field named "website" to your form if needed)
if (!empty($_POST['website'])) {
    // Bot filled the honeypot field — silently redirect
    header('Location: /thank-you.html');
    exit;
}

// ── If validation failed, go back ───────────────────────────
if (!empty($errors)) {
    $error_string = urlencode(implode(' ', $errors));
    header("Location: /?error=$error_string#contact");
    exit;
}

// ── Build the email ──────────────────────────────────────────
$subject = "New Compliance Audit Request — $pharmacy ($suburb)";

$body  = "NEW AUDIT REQUEST — SENTRY COMPLIANCE\n";
$body .= str_repeat('=', 50) . "\n\n";
$body .= "Name:              $name\n";
$body .= "Pharmacy:          $pharmacy\n";
$body .= "Suburb:            $suburb\n";
$body .= "Phone:             $phone\n";
$body .= "Email:             $email\n";
$body .= "Cold storage units: $units\n";
$body .= "Current monitoring: $current_setup\n\n";
$body .= "Message:\n$message\n\n";
$body .= str_repeat('-', 50) . "\n";
$body .= "Submitted: " . date('d M Y, g:ia T') . "\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";

// ── Email headers ────────────────────────────────────────────
$headers  = "From: $from_name <info@sentrycompliance.com.au>\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ── Send via DreamHost SMTP ──────────────────────────────────
// DreamHost shared hosting supports PHP mail() natively.
// The SMTP settings below are for reference / future upgrade to PHPMailer.
//
// SMTP Host:     smtp.dreamhost.com
// SMTP Port:     587 (STARTTLS)
// SMTP User:     info@sentrycompliance.com.au
// SMTP Password: (the $smtp_password variable above)
//
// To upgrade to PHPMailer (recommended for reliability):
// 1. Download PHPMailer from github.com/PHPMailer/PHPMailer
// 2. Upload the src/ folder to your server
// 3. Uncomment the PHPMailer block below and comment out mail()

$sent = mail($to_email, $subject, $body, $headers);

// ── PHPMailer SMTP block (optional — uncomment to use) ───────
/*
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.dreamhost.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@sentrycompliance.com.au';
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('info@sentrycompliance.com.au', $from_name);
    $mail->addAddress($to_email);
    $mail->addReplyTo($email, $name);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    $mail->send();
    $sent = true;
} catch (Exception $e) {
    $sent = false;
}
*/

// ── Redirect based on result ─────────────────────────────────
if ($sent) {
    header('Location: /thank-you.html');
} else {
    header('Location: /?error=Sorry%2C+there+was+a+problem+sending+your+request.+Please+email+us+directly.#contact');
}
exit;
