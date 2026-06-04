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
        <h1><?php echo $co; ?> Terms of Service</h1>
        <p class="legal-meta">Effective <?php echo legal_h($l['effective_date']); ?> · Applies to business use of <?php echo $product; ?></p>

        <p>These Terms of Service (&quot;Terms&quot;) govern access to and use of the <?php echo $brand; ?> cloud platform, websites, APIs, and related services (collectively, the &quot;Service&quot;) provided by <?php echo $co; ?> (&quot;Veurion,&quot; &quot;we,&quot; &quot;us,&quot; or &quot;our&quot;). The Service is a business-to-business (B2B) hotel property management system offered on a subscription basis to hotels, lodging operators, and their authorized staff.</p>
        <p>By creating an account, starting a trial, or using the Service, you agree to these Terms and to our <a href="<?php echo $l['privacy_url']; ?>">Privacy Policy</a>, which is incorporated by reference. If you accept on behalf of an organization, you represent that you have authority to bind that organization, and &quot;you&quot; means that organization and its authorized users.</p>

        <h2>1. Eligibility and accounts</h2>
        <ol>
            <li>The Service is intended for commercial hospitality use by adults acting on behalf of a business. You must provide accurate registration information and keep account credentials confidential. You are responsible for all activity under your account.</li>
            <li>You may invite or provision users (for example, front desk, housekeeping, or management staff) subject to your subscription plan. You are responsible for permissions you grant and for ensuring users comply with these Terms.</li>
            <li>We may suspend or refuse accounts that violate these Terms, pose security risk, or are used in a manner inconsistent with a legitimate hotel or lodging operation.</li>
        </ol>

        <h2>2. The Service</h2>
        <ol>
            <li>The Service may include reservations, guest folios, housekeeping, night audit, reporting, channel connectivity, online booking, payment processing integrations, messaging, and other features described on our website or in your plan. Features may vary by subscription tier and may change over time as we improve the product.</li>
            <li>We strive for high availability but do not guarantee uninterrupted or error-free operation. Scheduled maintenance, emergency fixes, third-party outages, and events outside our reasonable control may affect access. We will use commercially reasonable efforts to restore the Service when issues occur.</li>
            <li>Optional professional services, custom development, or onboarding assistance, if offered, will be governed by a separate written statement of work unless otherwise agreed in writing.</li>
        </ol>

        <h2>3. Subscriptions, trials, and payment</h2>
        <ol>
            <li><strong>Plans.</strong> Paid subscriptions are offered on monthly or annual terms (or as otherwise specified at checkout). Fees, included properties, room limits, user seats, and feature entitlements are described in your plan selection or order confirmation.</li>
            <li><strong>Free trials.</strong> If you start a free trial, you may use the Service for the trial period without charge. Unless you cancel before the trial ends, your account may convert to a paid subscription and your payment method may be charged automatically at the then-current rates.</li>
            <li><strong>Billing.</strong> You authorize us and our payment processors to charge applicable subscription fees, taxes, and overage amounts to your designated payment method. Fees are generally non-refundable except where required by law or expressly stated in writing. Failed payments may result in suspension or downgrade until amounts due are paid.</li>
            <li><strong>Changes.</strong> We may change prices or plan limits on reasonable notice. Price changes typically apply at the next renewal after notice. Upgrades may take effect immediately; downgrades may take effect at the next billing period and may reduce available features or capacity.</li>
            <li><strong>Taxes.</strong> Fees are exclusive of applicable sales, use, VAT, GST, or similar taxes unless stated otherwise. You are responsible for taxes associated with your purchase, excluding taxes based on our net income.</li>
        </ol>

        <h2>4. Customer data and privacy roles</h2>
        <ol>
            <li><strong>Your content.</strong> You retain ownership of data, content, and records you or your users submit to the Service (&quot;Customer Data&quot;), including reservation details, guest profiles, rates, notes, and uploaded materials. You grant us a limited license to host, process, transmit, back up, and display Customer Data solely to provide and improve the Service, comply with law, and as described in our Privacy Policy.</li>
            <li><strong>Guest and employee information.</strong> You are responsible for obtaining any notices and consents required to collect and process personal information about guests, employees, and other individuals through the Service. When you enter guest or staff personal data, you generally act as the data controller and Veurion acts as a service provider/processor processing that data on your instructions to deliver the Service.</li>
            <li><strong>Data export and deletion.</strong> During an active subscription, you may export Customer Data using available product features. After termination, we may delete or retain Customer Data according to our retention practices and applicable law. We recommend maintaining your own backups of business-critical records.</li>
        </ol>

        <h2>5. Acceptable use</h2>
        <p>You agree not to, and not to permit others to:</p>
        <ul>
            <li>use the Service for unlawful, fraudulent, or deceptive purposes;</li>
            <li>reverse engineer, scrape, or attempt to access non-public areas of the Service except as permitted by law;</li>
            <li>resell, sublicense, or white-label the Service without our written consent;</li>
            <li>introduce malware, interfere with security, or overload systems;</li>
            <li>send unsolicited bulk messages or violate anti-spam, telecom, or payment network rules;</li>
            <li>infringe intellectual property or privacy rights of third parties; or</li>
            <li>use the Service to store or transmit content that is illegal, defamatory, or harmful.</li>
        </ul>
        <p>We may investigate suspected violations and remove content or suspend access where reasonably necessary to protect the Service, users, or third parties.</p>

        <h2>6. Third-party services and integrations</h2>
        <p>The Service may interoperate with payment gateways, channel managers, OTAs, email providers, SMS or messaging platforms, accounting tools, and other third-party services (&quot;Third-Party Services&quot;). Your use of Third-Party Services is subject to their terms and privacy practices. We are not responsible for Third-Party Services and do not guarantee their availability, accuracy, or compliance. You are responsible for credentials, fees, and configuration you authorize with those providers.</p>

        <h2>7. Intellectual property</h2>
        <ol>
            <li>We and our licensors own the Service, software, documentation, branding, and all related intellectual property. Except for the limited rights expressly granted in these Terms, no rights are transferred to you.</li>
            <li>You may not copy, modify, or create derivative works of the Service except as permitted by applicable law or with our prior written consent.</li>
            <li>If you provide feedback or suggestions, you grant us a perpetual, irrevocable, royalty-free license to use them without restriction or compensation.</li>
        </ol>

        <h2>8. Confidentiality</h2>
        <p>Each party may receive non-public information from the other. The receiving party will use reasonable care to protect confidential information and will use it only for purposes related to the Service. This obligation does not apply to information that is public, independently developed, rightfully received without restriction, or required to be disclosed by law (with notice where permitted).</p>

        <h2>9. Warranty disclaimer</h2>
        <p><strong>The Service is provided &quot;as is&quot; and &quot;as available.&quot;</strong> To the maximum extent permitted by law, we disclaim all warranties, whether express, implied, or statutory, including implied warranties of merchantability, fitness for a particular purpose, title, and non-infringement. We do not warrant that the Service will meet your requirements, operate without interruption, or that results (including revenue, occupancy, or accounting outcomes) will be accurate or achieved. Hospitality operations remain your responsibility.</p>

        <h2>10. Limitation of liability</h2>
        <p>To the maximum extent permitted by law, neither party will be liable for indirect, incidental, special, consequential, exemplary, or punitive damages, or for loss of profits, revenue, goodwill, data, or business interruption, even if advised of the possibility. Except for your payment obligations, your indemnification obligations, or liability that cannot be limited by law, each party&apos;s aggregate liability arising out of or related to the Service in any twelve (12) month period will not exceed the fees you paid to us for the Service in that period (or, if you are on a free trial, one hundred U.S. dollars (USD $100)).</p>
        <p>Some jurisdictions do not allow certain limitations; in those cases, the above limits apply only to the extent permitted.</p>

        <h2>11. Indemnification</h2>
        <p>You will defend, indemnify, and hold harmless Veurion and its affiliates, officers, directors, employees, and agents from claims, damages, losses, and expenses (including reasonable legal fees) arising from Customer Data, your use of the Service, your violation of these Terms, or your violation of applicable law or third-party rights, except to the extent caused by our gross negligence or willful misconduct.</p>

        <h2>12. Term, suspension, and termination</h2>
        <ol>
            <li>These Terms remain in effect while you use the Service. You may cancel a paid subscription according to in-product cancellation flows or by contacting support; cancellation typically takes effect at the end of the current billing period unless otherwise stated.</li>
            <li>We may suspend or terminate access for non-payment, material breach, security risk, or prolonged inactivity, with notice where practicable.</li>
            <li>Upon termination, your right to access the Service ends. Sections that by nature should survive (including payment obligations accrued, confidentiality, disclaimers, limitations of liability, indemnity, and governing law) will survive.</li>
        </ol>

        <h2>13. Export and international use</h2>
        <p>The Service may be hosted in the United States, Canada, or other regions where we or our subprocessors operate. You agree to comply with applicable export control and sanctions laws. If you access the Service from outside Canada or the United States, you consent to cross-border processing of information as described in our Privacy Policy.</p>

        <h2>14. Changes to these Terms</h2>
        <p>We may update these Terms from time to time. We will post the revised version with a new effective date and, for material changes, provide notice by email, in-product message, or on our website. Continued use after the effective date constitutes acceptance, except where prohibited by law.</p>

        <h2>15. Governing law and disputes</h2>
        <p>These Terms are governed by the laws of the <?php echo legal_h($l['governing_law']); ?>, without regard to conflict-of-law rules. The parties submit to the exclusive jurisdiction of the courts located in <?php echo legal_h($l['courts']); ?>, except that either party may seek injunctive relief in any court of competent jurisdiction to protect intellectual property or confidential information.</p>
        <p>Any claim arising out of the Service must be brought within one (1) year after the claim arose, to the extent permitted by law.</p>

        <h2>16. General</h2>
        <ol>
            <li>These Terms, together with the Privacy Policy and any order or plan description, constitute the entire agreement regarding the Service and supersede prior agreements on the same subject.</li>
            <li>If a provision is held invalid, the remaining provisions remain in effect.</li>
            <li>Our failure to enforce a provision is not a waiver. You may not assign these Terms without our consent; we may assign them in connection with a merger, acquisition, or sale of assets.</li>
            <li>Notices to you may be sent to the email associated with your account. Notices to us should be sent to <a href="mailto:<?php echo legal_h($l['support_email']); ?>"><?php echo legal_h($l['support_email']); ?></a>.</li>
        </ol>

        <h2>17. Contact</h2>
        <p>
            <?php echo $co; ?><br>
            Email: <a href="mailto:<?php echo legal_h($l['support_email']); ?>"><?php echo legal_h($l['support_email']); ?></a><br>
            <?php if ($l['phone']) { ?>Phone: <?php echo legal_h($l['phone']); ?><br><?php } ?>
            <?php echo legal_h($l['address']); ?>
        </p>
        </article>
    </div>
</main>
<?php $this->load->view('includes/marketing_footer', marketing_layout_view_vars(get_defined_vars())); ?>
