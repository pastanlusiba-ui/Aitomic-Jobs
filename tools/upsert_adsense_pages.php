<?php
/**
 * Create or update the core Aitomic Jobs trust and AdSense-readiness pages.
 *
 * Usage:
 * wp eval-file /home/u710255073/aitomic-tools/upsert_adsense_pages.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$pages = [
    [
        'slug' => 'about',
        'title' => 'About Aitomic Jobs',
        'menu_order' => 10,
        'content' => <<<'HTML'
<p>Aitomic Jobs is a global opportunities directory built to help job seekers, consultants, interns, volunteers, institutions, and employers discover current opportunities across countries and sectors.</p>

<h2>What We Publish</h2>
<p>We focus on jobs, internships, tenders and consultancies, volunteer opportunities, remote work opportunities, training and short courses, and calls for applications. We do not publish scholarships, grants, fellowships, conferences, awards, or competitions on this website.</p>

<h2>How Listings Are Prepared</h2>
<p>Opportunities are gathered from official organizational websites, institutional career pages, procurement portals, and other credible public sources. Each listing is structured to help visitors quickly understand the organization, country, work mode, deadline, eligibility notes, and official application path.</p>

<h2>Our Purpose</h2>
<p>Our goal is to make opportunity discovery easier, especially for users who need one place to search across sectors and countries. Aitomic Jobs does not charge applicants to view opportunities and does not represent itself as the hiring organization unless explicitly stated.</p>

<h2>Source Links</h2>
<p>Every listing should point users to the official source or application page for final instructions. Applicants should always confirm details directly with the source organization before applying, bidding, registering, or submitting documents.</p>
HTML
    ],
    [
        'slug' => 'contact',
        'title' => 'Contact Aitomic Jobs',
        'menu_order' => 20,
        'content' => <<<'HTML'
<p>Use this page to contact Aitomic Jobs about corrections, source attribution, listing updates, partnerships, advertising, privacy requests, or general website enquiries.</p>

<h2>Before You Contact Us</h2>
<p>If your message is about a specific opportunity, please include the opportunity title and the page URL. If your message is about an application, tender, internship, or call, contact the source organization directly as Aitomic Jobs does not manage applications on behalf of listed organizations.</p>

[go_contact_form]
HTML
    ],
    [
        'slug' => 'privacy-policy',
        'title' => 'Privacy Policy',
        'menu_order' => 30,
        'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<p>This Privacy Policy explains how Aitomic Jobs collects, uses, stores, and shares information when visitors use this website.</p>

<h2>Information We Collect</h2>
<p>We may collect information that visitors provide directly, such as names, email addresses, and messages submitted through the contact form. We may also collect technical information automatically, including IP address, browser type, device information, pages visited, referring pages, approximate location, and interaction data.</p>

<h2>How We Use Information</h2>
<p>We use information to operate and improve the website, respond to enquiries, maintain security, analyze site performance, understand which pages are useful, detect spam or abuse, and improve opportunity listings and user experience.</p>

<h2>Cookies, Analytics, and Advertising</h2>
<p>Aitomic Jobs may use cookies, web beacons, IP addresses, local storage, and similar technologies for site functionality, analytics, measurement, security, and advertising. Cookies help websites remember preferences, measure traffic, and support advertising services.</p>

<p>We may use Google services, including Google Analytics, Google AdSense, Google Ad Manager, Google Ads, or related advertising and measurement products. When these services are used, Google and its partners may collect or receive information from this website and use that information to provide, personalize, measure, and improve ads and services.</p>

<p>Google explains how it uses information from sites and apps that use Google services here: <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">How Google uses information from sites or apps that use our services</a>.</p>

<h2>Third-Party Advertising Cookies</h2>
<p>Third parties, including Google, may place and read cookies on users' browsers or use web beacons, IP addresses, and other identifiers as a result of ad serving on this website. These technologies may be used for ad delivery, frequency capping, fraud prevention, reporting, and ad personalization where permitted by law and user settings.</p>

<h2>Managing Cookies and Ads Personalization</h2>
<p>Visitors can manage cookies through their browser settings. Visitors can also manage Google ads personalization through Google's ad settings and related privacy tools. Disabling some cookies may affect how parts of the website function.</p>

<h2>External Links</h2>
<p>Aitomic Jobs links to source websites where opportunities are originally published. We are not responsible for the privacy practices, content, application forms, or security of third-party websites. Visitors should review the privacy policy of any external site before submitting personal information.</p>

<h2>Data Sharing</h2>
<p>We do not sell contact form messages. We may share information with trusted service providers that help operate the website, analytics tools, advertising platforms, security services, hosting providers, and authorities where required by law.</p>

<h2>Data Retention</h2>
<p>We keep information only for as long as reasonably necessary for the purposes described in this policy, including responding to enquiries, maintaining records, preventing abuse, and complying with legal obligations.</p>

<h2>Children's Privacy</h2>
<p>Aitomic Jobs is not directed to children under 13. We do not knowingly collect personal information from children under 13.</p>

<h2>Your Choices</h2>
<p>You may contact us to request access, correction, or deletion of information you provided through the website, subject to applicable law and technical limits.</p>

<h2>Contact</h2>
<p>For privacy questions or requests, please use the <a href="/contact/">Contact page</a>.</p>
HTML
    ],
    [
        'slug' => 'cookie-policy',
        'title' => 'Cookie Policy',
        'menu_order' => 40,
        'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<p>This Cookie Policy explains how Aitomic Jobs may use cookies and similar technologies.</p>

<h2>What Cookies Are</h2>
<p>Cookies are small text files stored on a browser or device. They can help a website function, remember preferences, measure visits, improve security, and support advertising.</p>

<h2>Types of Cookies We May Use</h2>
<ul>
<li><strong>Essential cookies:</strong> support basic website operation, security, and form protection.</li>
<li><strong>Analytics cookies:</strong> help us understand traffic, popular pages, and website performance.</li>
<li><strong>Advertising cookies:</strong> may be used by advertising partners such as Google to deliver, measure, and improve ads.</li>
<li><strong>Preference cookies:</strong> may remember user choices where applicable.</li>
</ul>

<h2>Google Advertising Cookies</h2>
<p>If Google ads are enabled, Google may use cookies or similar identifiers to serve ads, limit repeated ads, measure ad performance, detect fraud, and personalize ads where allowed. Google provides more information about AdSense cookies here: <a href="https://support.google.com/adsense/answer/7549925" target="_blank" rel="noopener">How AdSense uses cookies</a>.</p>

<h2>Managing Cookies</h2>
<p>Most browsers allow visitors to block, delete, or control cookies through browser settings. Some website features may not work correctly if cookies are disabled.</p>

<h2>Updates</h2>
<p>We may update this Cookie Policy as the website, advertising setup, or legal requirements change.</p>
HTML
    ],
    [
        'slug' => 'terms-of-use',
        'title' => 'Terms of Use',
        'menu_order' => 50,
        'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<p>By using Aitomic Jobs, you agree to these Terms of Use. If you do not agree, please do not use the website.</p>

<h2>Website Purpose</h2>
<p>Aitomic Jobs provides public opportunity listings and related information. We are not the employer, procuring entity, training provider, or application manager unless explicitly stated.</p>

<h2>No Guarantee of Availability</h2>
<p>Opportunities may change, close, be withdrawn, or contain source updates after publication. We work to keep listings useful, but we do not guarantee that every opportunity remains open, complete, accurate, or suitable for every visitor.</p>

<h2>User Responsibility</h2>
<p>Visitors are responsible for reviewing official source pages, confirming deadlines, checking eligibility, preparing documents, and applying or submitting through the correct official channel.</p>

<h2>No Fees to Apply Through Aitomic Jobs</h2>
<p>Aitomic Jobs does not charge visitors to view opportunities. Be cautious of any third party asking for payment in exchange for employment, tenders, internships, volunteer placement, or guaranteed selection.</p>

<h2>Intellectual Property</h2>
<p>The website design, structure, summaries, and organization are owned by Aitomic Jobs or its licensors. Source organizations retain rights in their own names, logos, notices, and official materials.</p>

<h2>External Websites</h2>
<p>Links to external sites are provided for convenience and source verification. We are not responsible for third-party content, forms, decisions, security, or privacy practices.</p>

<h2>Acceptable Use</h2>
<p>Visitors must not misuse the website, attempt unauthorized access, scrape the site in a way that harms performance, submit spam, or use the site for unlawful activity.</p>

<h2>Changes</h2>
<p>We may update these Terms of Use from time to time. Continued use of the website after changes means you accept the updated terms.</p>
HTML
    ],
    [
        'slug' => 'editorial-policy-disclaimer',
        'title' => 'Editorial Policy and Disclaimer',
        'menu_order' => 60,
        'content' => <<<'HTML'
<p><strong>Last updated:</strong> July 20, 2026</p>

<h2>Editorial Approach</h2>
<p>Aitomic Jobs publishes opportunity listings from public and credible sources, including official institutional websites, career portals, procurement pages, and calls for applications. We aim to structure listings consistently so visitors can compare opportunities quickly.</p>

<h2>Verification</h2>
<p>We prioritize source links that lead to the original organization or official application page. When information is unclear, missing, or likely to change, visitors should use the official source link for the final version of the announcement.</p>

<h2>Corrections</h2>
<p>If you find an outdated deadline, incorrect country, broken source link, or other issue, please contact us through the <a href="/contact/">Contact page</a> and include the opportunity URL.</p>

<h2>Disclaimer</h2>
<p>Aitomic Jobs is an information directory. We do not guarantee selection, employment, award of contract, admission, training placement, visa approval, or any other outcome. We are not responsible for decisions made by employers, procuring entities, training providers, or source organizations.</p>

<h2>Advertising and Affiliate Disclosure</h2>
<p>Aitomic Jobs may display advertising or sponsored placements. Advertising helps support website operations. Sponsored or advertising content should not influence whether an opportunity is genuine, useful, or suitable for a visitor.</p>

<h2>Source Attribution</h2>
<p>Where possible, listings include source names and links so visitors can verify information directly. If you represent an organization and want a listing corrected or removed, please contact us.</p>
HTML
    ],
];

$results = [];

foreach ($pages as $page) {
    $existing = get_page_by_path($page['slug'], OBJECT, 'page');
    $post_data = [
        'post_title' => $page['title'],
        'post_name' => $page['slug'],
        'post_content' => $page['content'],
        'post_status' => 'publish',
        'post_type' => 'page',
        'menu_order' => $page['menu_order'],
    ];

    if ($existing instanceof WP_Post) {
        $post_data['ID'] = $existing->ID;
        $post_id = wp_update_post($post_data, true);
        $action = 'updated';
    } else {
        $post_id = wp_insert_post($post_data, true);
        $action = 'created';
    }

    if (is_wp_error($post_id)) {
        $results[] = [
            'slug' => $page['slug'],
            'status' => 'error',
            'message' => $post_id->get_error_message(),
        ];
        continue;
    }

    if ($page['slug'] === 'privacy-policy') {
        update_option('wp_page_for_privacy_policy', (int) $post_id);
    }

    $results[] = [
        'slug' => $page['slug'],
        'status' => $action,
        'id' => (int) $post_id,
        'url' => get_permalink((int) $post_id),
    ];
}

echo wp_json_encode([
    'status' => 'ok',
    'pages' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
