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
        'metaDescription' => 'Contact Ronak Fire India for fire safety equipment pricing, project quotations and technical support. Corporate office in Bhayandar East and factory in Palghar, Maharashtra.',
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
