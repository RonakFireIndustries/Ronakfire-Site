<?php
$sections = [
    ['collect', '1. Personal information we collect'],
    ['automatic', '2. Automatically collected information'],
    ['cookies', '3. Use of cookies'],
    ['use', '4. How we use your information'],
    ['communications', '5. User communications & submitted content'],
    ['security', '6. Data security & protection'],
    ['retention', '7. Data retention'],
    ['consent', '8. Consent'],
    ['sharing', '9. Sharing of information'],
    ['changes', '10. Changes to this policy'],
    ['grievance', '11. Grievance officer'],
    ['support', '12. Customer support'],
];
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="/">Home</a><?= icon('chevron-right') ?><span>Privacy policy</span>
        </nav>
        <div class="page-hero__grid">
            <div>
                <p class="eyebrow"><span></span> Legal</p>
                <h1>Privacy policy</h1>
                <p class="page-hero__lede">How Ronak Fire India Pvt. Ltd. collects, uses, stores, protects and discloses information obtained through ronakfire.com and its digital services.</p>
            </div>
            <div class="page-hero__aside">
                <strong>12</strong>
                <span>Sections</span>
                <small>Updated 15 November 2025</small>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container legal-layout">
        <aside class="legal-toc" aria-label="On this page">
            <h2>On this page</h2>
            <nav>
                <?php foreach ($sections as [$id, $label]): ?>
                    <a href="#<?= e($id) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <article class="legal-prose">
            <p class="legal-updated">Last updated: 15 November 2025</p>
            <p>This Privacy Policy explains how Ronak Fire India Pvt. Ltd. (“Company”, “Ronak Fire”, “we”, “us” or “our”) collects, uses, stores, protects and discloses information obtained from users (“User”, “you”) of our website www.ronakfire.com (“Website”). This policy applies to all digital platforms, services, products and interactions offered by Ronak Fire India Pvt. Ltd.</p>

            <section id="collect">
                <h2>1. Personal information we collect</h2>
                <p>We collect personal information from Users when they interact with our website or services. You may browse certain sections without revealing your identity. However, once you voluntarily provide personal details, you are no longer anonymous to us.</p>
                <p>Information we may collect includes, but is not limited to:</p>
                <ul>
                    <li>Name and contact details (email, phone number, address)</li>
                    <li>Company details and delivery information</li>
                    <li>Billing and payment information where applicable</li>
                    <li>Documents and details voluntarily submitted for inquiries or service requests</li>
                    <li>Communication history, preferences or feedback shared with us</li>
                </ul>
                <p>Some fields may be marked mandatory to complete a form, purchase or registration. You may choose not to provide particular information; however, certain features may not function without it.</p>
            </section>

            <section id="automatic">
                <h2>2. Automatically collected information</h2>
                <p>When you visit the Website, we may collect non-personal or technical information, such as:</p>
                <ul>
                    <li>IP address</li>
                    <li>Browser type and version</li>
                    <li>Device type and operating system</li>
                    <li>Pages visited, time spent and navigation patterns</li>
                    <li>Referring website URLs</li>
                    <li>Cookies and similar tracking identifiers</li>
                    <li>Aggregated user behaviour analytics</li>
                </ul>
                <p>This helps us improve performance, troubleshoot errors and enhance the user experience.</p>
            </section>

            <section id="cookies">
                <h2>3. Use of cookies</h2>
                <p>Our website uses cookies and similar tools to:</p>
                <ul>
                    <li>Analyse website traffic and navigation patterns</li>
                    <li>Improve security and session management</li>
                    <li>Enable quicker logins</li>
                    <li>Provide personalised content or recommendations</li>
                    <li>Measure advertising and marketing performance</li>
                </ul>
                <p>Cookies do not contain personally identifiable information themselves. You may decline cookies through your browser settings; however, certain Website features may not work optimally.</p>
                <p>Third-party tools (for example, Google Analytics) may also place cookies for analytics and marketing purposes. Their cookie usage is governed by their own privacy policies.</p>
            </section>

            <section id="use">
                <h2>4. How we use your information</h2>
                <p>We use collected information for purposes including, but not limited to:</p>
                <ul>
                    <li>Processing orders, service requests and transactions</li>
                    <li>Improving our product offerings and customer experience</li>
                    <li>Communicating updates, offers and service notifications</li>
                    <li>Conducting internal research on user behaviour and preferences</li>
                    <li>Enhancing security and fraud detection</li>
                    <li>Resolving disputes and providing customer support</li>
                    <li>Fulfilling legal or regulatory requirements</li>
                </ul>
                <p>We may use aggregated, non-identifiable information for market analysis, industry reports or performance improvement.</p>
            </section>

            <section id="communications">
                <h2>5. User communications &amp; submitted content</h2>
                <p>If you submit messages, emails, inquiries, feedback or documentation, we may store that information to:</p>
                <ul>
                    <li>Improve customer support</li>
                    <li>Resolve issues or disputes</li>
                    <li>Maintain service quality</li>
                    <li>Comply with regulatory requirements</li>
                </ul>
                <p>If another party sends us information or complaints regarding your use of our website, we may store that as part of your profile record.</p>
            </section>

            <section id="security">
                <h2>6. Data security &amp; protection</h2>
                <p>Ronak Fire India Pvt. Ltd. implements reasonable physical, electronic and administrative safeguards to protect your personal information from:</p>
                <ul>
                    <li>Unauthorised access</li>
                    <li>Loss or misuse</li>
                    <li>Alteration or destruction</li>
                </ul>
                <p>User information is stored securely, and access is restricted to authorised personnel only. However, Users acknowledge that data transmission over the internet carries inherent risks, and absolute security cannot be guaranteed. Users are responsible for safeguarding account passwords and login credentials.</p>
            </section>

            <section id="retention">
                <h2>7. Data retention</h2>
                <p>We retain personal data for as long as necessary to:</p>
                <ul>
                    <li>Provide requested services</li>
                    <li>Fulfil regulatory or legal obligations</li>
                    <li>Resolve disputes or prevent fraud</li>
                    <li>Maintain financial and audit records</li>
                </ul>
                <p>Non-personal or anonymised data may be retained indefinitely for research and analytics.</p>
            </section>

            <section id="consent">
                <h2>8. Consent</h2>
                <p>By accessing our website or providing your information, you consent to:</p>
                <ul>
                    <li>Collection, use, processing and storage of your data</li>
                    <li>Communication through SMS, email, calls and digital channels</li>
                    <li>Sharing information with approved partners for legitimate use, as described in this policy</li>
                </ul>
                <p>If you provide information about someone else, you confirm that you have the authority to do so.</p>
            </section>

            <section id="sharing">
                <h2>9. Sharing of information</h2>
                <p>Ronak Fire India Pvt. Ltd. never sells or trades personal information.</p>
                <p>We may share limited data with:</p>
                <ul>
                    <li>Service providers assisting in operations (hosting, payment gateways, email services)</li>
                    <li>Marketing or analytics partners</li>
                    <li>Logistics or delivery partners</li>
                    <li>Legal authorities when required by law</li>
                </ul>
                <p>All partners receive only the necessary information and are obligated to maintain confidentiality. We may share anonymised demographic data for reporting or collaboration purposes.</p>
            </section>

            <section id="changes">
                <h2>10. Changes to this policy</h2>
                <p>Ronak Fire India Pvt. Ltd. may update this Privacy Policy at any time. Users are encouraged to review this page periodically to stay informed about how their data is protected. Continued use of the Website will be considered acceptance of the updated policy.</p>
            </section>

            <section id="grievance">
                <h2>11. Grievance officer</h2>
                <p>In compliance with the IT Act, 2000 and applicable data protection rules, our Grievance Officer is:</p>
                <ul class="legal-contact">
                    <li><strong>Name:</strong> Ronak Fire India Pvt. Ltd.</li>
                    <li><strong>Designation:</strong> Grievance Officer</li>
                    <li><strong>Company:</strong> Ronak Fire India Pvt. Ltd.</li>
                    <li><strong>Address:</strong> Shop No. 118, 1st Floor, Vasudev Sky High, Kanakia Rd, Om Ram Sagar Phase 1, Beverly Park, Mira Road East, Mira Bhayandar, Maharashtra 401105</li>
                    <li><strong>Phone:</strong> <a href="tel:+919323947512">+91 93239 47512</a></li>
                    <li><strong>Email:</strong> <a href="mailto:info@ronakfire.com">info@ronakfire.com</a></li>
                    <li><strong>Working hours:</strong> Monday – Saturday, 9:00 am – 6:00 pm</li>
                </ul>
                <p>For queries or complaints regarding data usage, please contact the above officer.</p>
            </section>

            <section id="support">
                <h2>12. Customer support</h2>
                <p>For general support, service issues or product inquiries, contact us:</p>
                <ul class="legal-contact">
                    <li><strong>Phone:</strong> <a href="tel:+919323947512">+91 93239 47512</a> / <a href="tel:+918104039710">+91 81040 39710</a></li>
                    <li><strong>Email:</strong> <a href="mailto:info@ronakfire.com">info@ronakfire.com</a> / <a href="mailto:sales@ronakfire.com">sales@ronakfire.com</a></li>
                    <li><strong>Contact page:</strong> <a href="/contact">ronakfire.com/contact</a></li>
                </ul>
            </section>
        </article>
    </div>
</section>
