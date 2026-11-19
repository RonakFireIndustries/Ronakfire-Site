<?php

declare(strict_types=1);

use App\Catalog;

/* -------------------------------------------------------------------------
   Marketing pages
   ------------------------------------------------------------------------- */

function ctrl_home(): void
{
    $categories = Catalog::categories();
    $counts = Catalog::countsByCategory();

    render('home', [
        'pageTitle' => 'Fire Safety Equipment Manufacturer & B2B Store | Ronak Fire India',
        'metaDescription' => 'Fire extinguishers, hose reel drums, hydrants, sprinklers and made-to-measure fire safety equipment from Ronak Fire India. Configure products and order at trade pricing.',
        'canonical' => abs_url('/'),
        'categories' => $categories,
        'counts' => $counts,
        'featured' => Catalog::featured(4),
        'totalProducts' => array_sum($counts),
        'activeNav' => 'home',
    ]);
}

function ctrl_about(): void
{
    $counts = Catalog::countsByCategory();

    render('marketing/about', [
        'pageTitle' => 'About Ronak Fire India | Fire Safety Equipment Manufacturer',
        'metaDescription' => 'Ronak Fire India Pvt. Ltd. manufactures firefighting equipment in Palghar, Maharashtra under an ISO 9001:2015 quality system. Explore our factory, capabilities and quality process.',
        'canonical' => abs_url('/about'),
        'totalProducts' => array_sum($counts),
        'activeNav' => 'about',
    ]);
}

function ctrl_contact(): void
{
    render('marketing/contact', [
        'pageTitle' => 'Contact Ronak Fire India | Sales, Factory & Corporate Office',
        'metaDescription' => 'Contact Ronak Fire India for fire safety equipment pricing, project quotations and technical support. Corporate office in Mira Road East and factory in Palghar, Maharashtra.',
        'canonical' => abs_url('/contact'),
        'activeNav' => 'contact',
    ]);
}

function ctrl_privacy(): void
{
    render('marketing/privacy', [
        'pageTitle' => 'Privacy Policy | Ronak Fire India',
        'metaDescription' => 'How Ronak Fire India Pvt. Ltd. collects, uses, stores and protects information provided through ronakfire.com and its digital services.',
        'canonical' => abs_url('/privacy'),
        'activeNav' => '',
    ]);
}

function ctrl_refund_policy(): void
{
    render('marketing/refund-policy', [
        'pageTitle' => 'Refund & Cancellation Policy | Ronak Fire India',
        'metaDescription' => 'Order confirmation, cancellation, replacement and refund terms for products purchased from Ronak Fire India Pvt. Ltd.',
        'canonical' => abs_url('/refund-policy'),
        'activeNav' => '',
    ]);
}

function ctrl_contact_post(): void
{
    require_csrf();

    $honeypot = trim((string) post('website', ''));
    $name     = trim((string) post('name', ''));
    $company  = trim((string) post('company', ''));
    $email    = trim((string) post('email', ''));
    $phone    = trim((string) post('phone', ''));
    $subject  = trim((string) post('subject', 'General enquiry'));
    $message  = trim((string) post('message', ''));

    $errors = [];
    if ($honeypot !== '') {
        $errors[] = 'Submission rejected.';
    }
    if ($name === '') {
        $errors[] = 'Enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
        $errors[] = 'Enter a phone number with at least 10 digits.';
    }
    if ($message === '') {
        $errors[] = 'Enter your message.';
    }

    if ($errors) {
        flash('error', implode(' ', $errors));
        redirect('/contact');
    }

    $now = date('Y-m-d H:i:s');
    $customer = current_customer();
    db()->insert('enquiries', [
        'customer_id'  => $customer['id'] ?? null,
        'name'         => $name,
        'company'      => $company,
        'email'        => $email,
        'phone'        => $phone,
        'gstin'        => '',
        'product_id'   => null,
        'product_name' => $subject,
        'qty'          => 0,
        'configuration'=> '',
        'message'      => $message,
        'status'       => 'new',
        'created_at'   => $now,
        'updated_at'   => $now,
    ]);

    if (config('store.notify_orders', false)) {
        try {
            $body = "New contact form submission\n\n"
                . 'Name: ' . $name . "\n"
                . 'Company: ' . $company . "\n"
                . 'Email: ' . $email . "\n"
                . 'Phone: ' . $phone . "\n"
                . 'Subject: ' . $subject . "\n\n"
                . $message;
            @mail(
                (string) config('store.order_email', config('store.support_email')),
                'Contact form: ' . $subject . ' — ' . $name,
                $body,
                'From: ' . config('store.support_email') . "\r\nContent-Type: text/plain; charset=UTF-8"
            );
        } catch (Throwable $e) {
            error_log('Contact notification failed: ' . $e->getMessage());
        }
    }

    flash('success', "Thank you, {$name}. We have received your message and will be in touch shortly.");
    redirect('/contact?sent=1');
}
