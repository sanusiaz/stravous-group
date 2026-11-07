<?php
/**
 * Stravous Group contact form handler (live server only).
 * Receives the enquiry form from index.html and emails it to the address below.
 * The hosting account must allow PHP mail().
 */

$TO_EMAIL   = 'info@stravousgroup.com';
$FROM_EMAIL = 'no-reply@stravousgroup.com';   // must be an address on this domain
$FROM_NAME  = 'Stravous Group Website';

date_default_timezone_set('Africa/Lagos');
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

function respond($ok, $error = null, $status = 200) {
    http_response_code($status);
    echo json_encode($error ? ['ok' => $ok, 'error' => $error] : ['ok' => $ok]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed', 405);
}

// Spam trap: real visitors never fill this hidden field.
if (!empty($_POST['company_site'])) {
    respond(true);
}

function field($key, $max) {
    $v = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}
function one_line($v) {
    return str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v);
}

$name    = one_line(field('name', 120));
$email   = one_line(field('email', 160));
$phone   = one_line(field('phone', 40));
$topic   = one_line(field('topic', 60));
$message = field('message', 5000);

$topics = ['Construction', 'Real estate', 'Land development', 'Project management', 'Something else'];
if (!in_array($topic, $topics, true)) {
    $topic = 'Something else';
}

if ($name === '' || $message === '') {
    respond(false, 'Name and message are required', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Enter a valid email address', 422);
}

$subject = 'Website enquiry: ' . $topic . ' from ' . $name;

$body  = "New enquiry from stravousgroup.com\n\n";
$body .= "Name:    $name\n";
$body .= "Email:   $email\n";
$body .= "Phone:   " . ($phone !== '' ? $phone : '-') . "\n";
$body .= "Topic:   $topic\n\n";
$body .= "Message:\n$message\n\n";
$body .= "Sent: " . date('j M Y, H:i') . "\n";

$headers  = 'From: ' . $FROM_NAME . ' <' . $FROM_EMAIL . ">\r\n";
$headers .= 'Reply-To: ' . $name . ' <' . $email . ">\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($TO_EMAIL, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-f' . $FROM_EMAIL);

$sent ? respond(true) : respond(false, 'Mail could not be sent', 500);
