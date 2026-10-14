<?php
/**
 * Ronak Fire India Pvt. Ltd. - website enquiry handler.
 *
 * Receives the contact / product-enquiry forms, validates the input and
 * emails it to the address configured below. Responds with JSON for the
 * AJAX submit and with a redirect for a normal (no-JavaScript) submit.
 */
declare(strict_types=1);

// Where enquiries are delivered.
$RECIPIENT = 'info@ronakfire.com';

// "From" address must belong to your own domain to pass SPF/DMARC.
$FROM_ADDRESS = 'no-reply@ronakfire.com';
$FROM_NAME    = 'Ronak Fire Website';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

/**
 * Send the final response and stop.
 */
function respond(bool $success, string $message): void
{
    global $isAjax;

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => $success, 'message' => $message]);
    } else {
        $target = $success ? 'contact-us.html?status=success' : 'contact-us.html?status=error';
        header('Location: ' . $target, true, 303);
    }
    exit;
}

/**
 * Strip control characters so nothing can be injected into mail headers.
 */
function clean($value): string
{
    return trim((string) preg_replace('/[\r\n\t]+/', ' ', (string) $value));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Honeypot: real users never see or fill this field.
if (!empty($_POST['website'])) {
    respond(true, 'Thank you! Your enquiry has been received.');
}

$name    = clean($_POST['name'] ?? '');
$surname = clean($_POST['surname'] ?? '');
$number  = clean($_POST['number'] ?? '');
$email   = clean($_POST['email'] ?? '');
$product = clean($_POST['product'] ?? '');
$page    = clean($_POST['page'] ?? '');
$message = trim((string) ($_POST['message'] ?? ''));

$fullName = trim($name . ' ' . $surname);

if ($fullName === '' || $number === '' || $message === '') {
    respond(false, 'Please fill in all required fields.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if (
    mb_strlen($fullName) > 120 ||
    mb_strlen($number) > 40 ||
    mb_strlen($email) > 120 ||
    mb_strlen($product) > 120 ||
    mb_strlen($message) > 5000
) {
    respond(false, 'One of the fields is too long. Please shorten it and try again.');
}

$subject = 'Website Enquiry' . ($product !== '' ? ' - ' . $product : '');

$body  = "New enquiry from the Ronak Fire website\r\n\r\n"
       . "Name:    " . $fullName . "\r\n"
       . "Phone:   " . $number . "\r\n"
       . "Email:   " . ($email !== '' ? $email : '-') . "\r\n"
       . ($product !== '' ? "Product: " . $product . "\r\n" : '')
       . ($page !== '' ? "Page:    " . $page . "\r\n" : '')
       . "\r\nMessage:\r\n" . $message . "\r\n\r\n"
       . 'Sent: ' . date('Y-m-d H:i:s') . "\r\n"
       . 'IP:   ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\r\n";

$headers   = [];
$headers[] = 'From: ' . $FROM_NAME . ' <' . $FROM_ADDRESS . '>';
if ($email !== '') {
    $headers[] = 'Reply-To: ' . $email;
}
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = @mail($RECIPIENT, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    respond(true, 'Thank you! Your enquiry has been sent. We will contact you shortly.');
}

respond(false, 'Sorry, the message could not be sent. Please email us at info@ronakfire.com.');
