<?php
$this->load->view('includes/marketing_head', marketing_layout_view_vars(get_defined_vars()));
$l = legal_meta();
$co = legal_h($l['company']);
$brand = legal_h($l['brand']);
$product = legal_h($l['product']);
?>
<main id="main" class="mc-legal-page">
    <div class="mc-container mc-legal-content">
        <article id="policy">
        <h1><?php echo $co; ?> Privacy Policy</h1>
        <p class="legal-meta">Effective <?php echo legal_h($l['effective_date']); ?> · Describes how we handle personal information for <?php echo $product; ?></p>

        <p><?php echo $co; ?> (&quot;Veurion,&quot; &quot;we,&quot; &quot;us,&quot; or &quot;our&quot;) respects your privacy. This Privacy Policy explains how we collect, use, disclose, and protect personal information when you visit our websites, request a demo or trial, create a business account, use the <?php echo $brand; ?> platform (the &quot;Service&quot;), or otherwise interact with us as a customer, prospect, or authorized user.</p>
        <p>The Service is B2B software used by hotels and lodging operators. In most cases, information about hotel guests or on-property staff is submitted by our customers; those customers decide what to collect and why. We process that information on their behalf to provide the Service, as described in Section 6.</p>

        <h2>1. Information we collect</h2>
        <p>Depending on how you interact with us, we may collect:</p>
        <ul>
            <li><strong>Account and business contact information</strong> — name, business email, phone, property or company name, billing address, role, and credentials you choose.</li>
            <li><strong>Subscription and billing information</strong> — plan selection, invoices, payment status, and limited payment details processed by our payment providers (we do not store full payment card numbers on our servers).</li>
            <li><strong>Usage and technical information</strong> — IP address, browser type, device identifiers, log files, pages viewed, feature usage, error reports, and security events.</li>
            <li><strong>Communications</strong> — support tickets, chat or email correspondence, survey responses, and marketing preferences.</li>
            <li><strong>Customer-entered operational data</strong> — reservations, guest profiles, folio and payment records, housekeeping status, notes, documents, and configuration you or your users upload to operate your property. This may include personal information about guests and employees.</li>
            <li><strong>Integration data</strong> — information exchanged with connected services (for example, channel managers, payment gateways, or messaging providers) when you enable those integrations.</li>
        </ul>
        <p>We may combine information collected online with information you provide offline (for example, at a trade show or sales call).</p>

        <h2>2. How we use information</h2>
        <p>We use personal information to:</p>
        <ul>
            <li>provide, operate, secure, and maintain the Service;</li>
            <li>authenticate users and enforce account permissions;</li>
            <li>process subscriptions, trials, and payments;</li>
            <li>provide customer support and respond to inquiries;</li>
            <li>send service-related notices (for example, security alerts, billing, or product changes);</li>
            <li>send marketing communications where permitted and with opt-out options;</li>
            <li>analyze usage to improve reliability, performance, and features;</li>
            <li>detect, prevent, and investigate fraud, abuse, or security incidents; and</li>
            <li>comply with legal obligations and protect our rights.</li>
        </ul>

        <h2>3. Legal bases (EEA, UK, and Switzerland)</h2>
        <p>If you are in the European Economic Area, the United Kingdom, or Switzerland, we process personal information when:</p>
        <ul>
            <li>performance of a contract requires it (for example, to deliver the Service you subscribed to);</li>
            <li>you have given consent (for example, optional marketing emails);</li>
            <li>we have a legitimate interest that is not overridden by your rights (for example, securing the Service, improving features, or communicating about your account); or</li>
            <li>processing is necessary to comply with law.</li>
        </ul>

        <h2>4. How we share information</h2>
        <p>We do not sell personal information. We may share information with:</p>
        <ul>
            <li><strong>Service providers and subprocessors</strong> — hosting, database, email delivery, analytics, customer support tools, payment processors, and security vendors who process data under contractual obligations consistent with this policy.</li>
            <li><strong>Integration partners</strong> — when you connect third-party services, data is shared as needed to operate those integrations at your direction.</li>
            <li><strong>Professional advisors</strong> — lawyers, accountants, or insurers under confidentiality duties.</li>
            <li><strong>Business transfers</strong> — in connection with a merger, acquisition, financing, or sale of assets, subject to appropriate safeguards.</li>
            <li><strong>Legal and safety</strong> — when required by law, court order, or governmental request, or when we believe disclosure is necessary to protect rights, safety, or the integrity of the Service.</li>
        </ul>
        <p>We may share aggregated or de-identified information that cannot reasonably identify you.</p>

        <h2>5. International transfers</h2>
        <p>We and our providers may process information in Canada, the United States, and other countries where we or our subprocessors operate. When we transfer personal information internationally, we use appropriate safeguards such as standard contractual clauses or equivalent mechanisms where required by applicable law.</p>

        <h2>6. Customer data and our role as processor</h2>
        <p>When a hotel or operator uses the Service to manage guest stays, payments, or staff workflows, that customer is generally the data controller for guest and employee personal information, and Veurion acts as a processor/service provider processing such data on the customer&apos;s instructions to deliver the Service.</p>
        <p>Customers are responsible for providing appropriate privacy notices to guests and staff, obtaining required consents, and responding to data subject requests relating to information they control. If you are a guest and have questions about how a property uses your information, please contact that property directly. If you are a customer user and need help exporting or deleting data within your account, contact your property administrator or our support team.</p>
        <p>Upon request, we can provide a data processing addendum (DPA) for customers who require one for GDPR or similar regimes.</p>

        <h2>7. Retention</h2>
        <p>We retain personal information for as long as needed to provide the Service, meet contractual and legal obligations, resolve disputes, and enforce agreements. Retention periods may vary by data type. When an account is closed, we may delete or anonymize information within a reasonable period, subject to backup cycles, legal holds, and fraud prevention needs.</p>

        <h2>8. Security</h2>
        <p>We implement administrative, technical, and organizational measures designed to protect personal information, including access controls, encryption in transit where supported, monitoring, and staff training. No method of transmission or storage is completely secure; you are responsible for safeguarding credentials and configuring user permissions appropriately within your organization.</p>

        <h2>9. Your privacy rights</h2>
        <p>Depending on your location, you may have rights to access, correct, delete, restrict, or object to certain processing, or to receive a portable copy of your personal information. You may also withdraw consent where processing is consent-based, without affecting the lawfulness of prior processing.</p>
        <p>To exercise rights relating to your Veurion account or marketing preferences, contact <a href="mailto:<?php echo legal_h($l['privacy_email']); ?>"><?php echo legal_h($l['privacy_email']); ?></a>. We may verify your identity before responding. If you are a guest of a property using our Service, please contact that property for requests about guest records they control.</p>
        <p><strong>California residents.</strong> We do not sell personal information as defined by the California Consumer Privacy Act (CCPA/CPRA). California residents may request access, deletion, or correction of personal information we collect in a business capacity, and may designate an authorized agent subject to verification.</p>
        <p><strong>Complaints.</strong> EEA/UK residents may lodge a complaint with their local supervisory authority. We encourage you to contact us first so we can address your concern.</p>

        <h2>10. Cookies and similar technologies</h2>
        <p>We use cookies and similar technologies to keep you signed in, remember preferences, measure performance, and improve the Service. You can control cookies through browser settings; disabling certain cookies may limit functionality. Where required, we will obtain consent before placing non-essential cookies.</p>

        <h2>11. Marketing communications</h2>
        <p>We may send product updates, hospitality insights, or promotional emails to business contacts who have opted in or where permitted by law. You can unsubscribe using the link in our emails or by contacting us. Service-related messages (for example, security or billing notices) may still be sent while you maintain an account.</p>

        <h2>12. Children</h2>
        <p>The Service is not directed to children under 16, and we do not knowingly collect personal information from children for consumer purposes. Properties may record guest information about minors as part of legitimate hospitality operations under their own policies.</p>

        <h2>13. Third-party websites</h2>
        <p>Our websites or Service may link to third-party sites or services. Their privacy practices are governed by their own policies. We are not responsible for third-party sites you choose to visit or integrate.</p>

        <h2>14. Changes to this policy</h2>
        <p>We may update this Privacy Policy from time to time. We will post the revised version with an updated effective date and, where appropriate, provide additional notice for material changes. Your continued use of the Service after the effective date indicates acceptance of the updated policy, except where further consent is required by law.</p>

        <h2>15. Contact us</h2>
        <p>
            <?php echo $co; ?><br>
            Privacy inquiries: <a href="mailto:<?php echo legal_h($l['privacy_email']); ?>"><?php echo legal_h($l['privacy_email']); ?></a><br>
            General support: <a href="mailto:<?php echo legal_h($l['support_email']); ?>"><?php echo legal_h($l['support_email']); ?></a><br>
            Website: <a href="<?php echo legal_h($l['site_url']); ?>"><?php echo legal_h($l['site_url']); ?></a><br>
            <?php if ($l['phone']) { ?>Phone: <?php echo legal_h($l['phone']); ?><br><?php } ?>
            <?php echo legal_h($l['address']); ?>
        </p>
        <p>See also our <a href="<?php echo $l['terms_url']; ?>">Terms of Service</a>.</p>
        </article>
    </div>
</main>
<?php $this->load->view('includes/marketing_footer', marketing_layout_view_vars(get_defined_vars())); ?>
