<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finova Africa | Privacy Policy & Legal Agreement</title>

    <meta name="description" content="Official Privacy Policy, Terms of Service and Legal Agreement for Finova Africa.">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #f4f7fb;
            color: #444;
            line-height: 1.8;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: 92%;
            max-width: 1350px;
            margin: auto;
        }

        /***************************
HEADER
****************************/

        header {

            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .08);
            z-index: 999;

        }

        nav {

            height: 90px;
            display: flex;
            justify-content: space-between;
            align-items: center;

        }

        .logo {

            display: flex;
            align-items: center;

        }

        .logo img {

            height: 55px;
            margin-right: 15px;

        }

        .logo h2 {

            font-size: 28px;
            color: #003E91;
            font-weight: 700;

        }

        .logo span {

            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #777;

        }

        .menu {

            display: flex;
            list-style: none;

        }

        .menu li {

            margin-left: 30px;

        }

        .menu a {

            color: #333;
            font-weight: 600;
            transition: .3s;

        }

        .menu a:hover {

            color: #0066ff;

        }

        .download-btn {

            padding: 14px 28px;
            background: #0059d6;
            color: #fff;
            border-radius: 40px;
            font-weight: 600;

        }

        /***************************
HERO
****************************/

        .hero {

            margin-top: 90px;

            background:
                linear-gradient(rgba(0, 35, 90, .90), rgba(0, 35, 90, .90)),
                url(images/privacy-banner.jpg);

            background-size: cover;
            background-position: center;

            padding: 120px 0;

            color: #fff;

        }

        .hero-grid {

            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 60px;
            align-items: center;

        }

        .hero h1 {

            font-size: 58px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 25px;

        }

        .hero p {

            font-size: 18px;
            line-height: 1.9;
            opacity: .95;

        }

        .hero-card {

            background: #fff;
            color: #444;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .2);

        }

        .hero-card h3 {

            color: #003E91;
            margin-bottom: 20px;

        }

        .hero-card ul {

            list-style: none;

        }

        .hero-card li {

            padding: 12px 0;
            border-bottom: 1px solid #eee;

        }

        .hero-card i {

            color: #0059d6;
            margin-right: 10px;

        }

        /***************************
LEGAL NOTICE
****************************/

        .notice {

            padding: 90px 0;

        }

        .notice-box {

            background: #fff;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, .08);

            border-left: 8px solid #0059d6;

        }

        .notice-box h2 {

            font-size: 38px;
            color: #003E91;
            margin-bottom: 30px;

        }

        .notice-box p {

            margin-bottom: 20px;
            text-align: justify;

        }

        /***************************
TABLE OF CONTENTS
****************************/

        .toc {

            padding-bottom: 90px;

        }

        .toc h2 {

            text-align: center;
            font-size: 42px;
            margin-bottom: 50px;
            color: #003E91;

        }

        .grid {

            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;

        }

        .card {

            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .08);
            transition: .3s;

        }

        .card:hover {

            transform: translateY(-6px);

        }

        .card h3 {

            margin-bottom: 15px;
            color: #003E91;

        }

        .card p {

            font-size: 15px;
            color: #666;

        }

        .card i {

            font-size: 45px;
            margin-bottom: 20px;
            color: #0059d6;

        }

        /***************************
DEFINITIONS
****************************/

        .definitions {

            padding-bottom: 100px;

        }

        .definitions h2 {

            font-size: 42px;
            margin-bottom: 40px;
            color: #003E91;

        }

        .definition {

            background: #fff;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 5px solid #0059d6;
            border-radius: 10px;

        }

        .definition h4 {

            color: #003E91;
            margin-bottom: 10px;

        }

        @media(max-width:992px) {

            .hero-grid {

                grid-template-columns: 1fr;

            }

            .menu {

                display: none;

            }

            .hero h1 {

                font-size: 40px;

            }

        }
    </style>

</head>

<body>

    <header>

        <div class="container">

            <nav>

                <div class="logo">

                    <a href="https://core.finova.ng" class="sidebarCollapse"><img src="LOGO.png" alt="Finova Logo"
                            width="93" height="46"></a>

                    <div>

                        <h2>Finova Africa</h2>

                        <span>Privacy Policy & Legal Agreement</span>

                    </div>

                </div>

                <ul class="menu">

                    <li><a href="#notice">Legal Notice</a></li>

                    <li><a href="#toc">Contents</a></li>

                    <li><a href="#definitions">Definitions</a></li>

                    <li><a href="#privacy">Privacy</a></li>

                    <li><a href="#contact">Contact</a></li>

                </ul>

                <a href="https://core.finova.ng/register" class="download-btn">
                    Sign up</a>
            </nav>

        </div>

    </header>

    <!-- ===========================
HERO
=========================== -->

    <section class="hero">

        <div class="container">

            <div class="hero-grid">

                <div>

                    <h1>

                        Privacy Policy<br>

                        Terms of Service<br>

                        Legal Agreement

                    </h1>

                    <p>

                        This Privacy Policy and Legal Agreement ("Agreement") constitutes a legally binding agreement
                        between Finova Africa ("Finova", "Company", "we", "our", or "us") and every individual,
                        merchant, cooperative society, financial institution, business entity, corporate organisation,
                        partner, developer or other person ("Customer", "User", "Partner" or "Client") accessing or
                        using any Finova Africa product, software, mobile application, web application, API,
                        infrastructure, cloud service or digital financial solution.

                        By creating an account, submitting information, signing any quotation, proposal, invoice,
                        agreement, onboarding form, making payment, clicking <strong>"Register"</strong>,
                        <strong>"Create Account"</strong>, <strong>"Continue"</strong>, <strong>"I Agree"</strong> or
                        otherwise accessing or using any Finova Africa service, you acknowledge that you have read,
                        understood and agreed to be legally bound by this Agreement.

                    </p>

                </div>

                <div class="hero-card">

                    <h3>

                        Document Includes

                    </h3>

                    <ul>

                        <li><i class="fa-solid fa-check"></i> Privacy Policy</li>

                        <li><i class="fa-solid fa-check"></i> Terms & Conditions</li>

                        <li><i class="fa-solid fa-check"></i> Service Wallet Authorisation</li>

                        <li><i class="fa-solid fa-check"></i> Subscription Policy</li>

                        <li><i class="fa-solid fa-check"></i> No Refund Policy</li>

                        <li><i class="fa-solid fa-check"></i> App Suspension Policy</li>

                        <li><i class="fa-solid fa-check"></i> Intellectual Property</li>

                        <li><i class="fa-solid fa-check"></i> Customer Responsibilities</li>

                        <li><i class="fa-solid fa-check"></i> Electronic Consent</li>

                        <li><i class="fa-solid fa-check"></i> Legal Notices</li>

                    </ul>

                </div>

            </div>

        </div>

    </section>

    <!-- ===========================
LEGAL NOTICE
=========================== -->

    <section class="notice" id="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    Legal Notice

                </h2>

                <p>

                    This Agreement governs the collection, processing, storage, disclosure and protection of personal
                    information as well as the contractual relationship between Finova Africa and its Customers,
                    Partners and Users. It also sets out the rights, responsibilities and obligations relating to the
                    use of Finova Africa's products and services.

                </p>

                <p>

                    This Agreement applies to all products and services provided by Finova Africa including but not
                    limited to mobile banking applications, core banking platforms, APIs, payment services, cloud
                    hosting, software licensing, administration portals, virtual accounts, digital wallets, payment
                    integrations, agency banking infrastructure, cooperative banking solutions, savings products and any
                    other products or services offered by Finova Africa now or in the future.

                </p>

                <p>

                    If you do not agree with any part of this Agreement, you must immediately discontinue the
                    registration process and cease all access to or use of Finova Africa's products and services.

                </p>

            </div>

        </div>

    </section>

    <!-- ===========================
TABLE OF CONTENTS
=========================== -->

    <section class="toc" id="toc">

        <div class="container">

            <h2>

                Table of Contents

            </h2>

            <div class="grid">

                <div class="card">
                    <i class="fa-solid fa-user-shield"></i>
                    <h3>Privacy & Data Protection</h3>
                    <p>Collection, processing, storage and disclosure of information.</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-wallet"></i>
                    <h3>Service Wallet Authorisation</h3>
                    <p>Mandatory wallet creation and operational account authorisation.</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-credit-card"></i>
                    <h3>Subscription Policy</h3>
                    <p>Billing, renewal, suspension and payment obligations.</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-ban"></i>
                    <h3>No Refund Policy</h3>
                    <p>Project commencement, cancellation and refund rules.</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-scale-balanced"></i>
                    <h3>Legal Rights</h3>
                    <p>Liability, indemnity, dispute resolution and governing law.</p>
                </div>

                <div class="card">
                    <i class="fa-solid fa-building-shield"></i>
                    <h3>KYC & Compliance</h3>
                    <p>Identity verification, AML and regulatory obligations.</p>
                </div>

            </div>

        </div>

    </section>

    <!-- ===========================
DEFINITIONS
=========================== -->

    <section class="definitions" id="definitions">

        <div class="container">

            <h2>

                Definitions

            </h2>

            <div class="definition">
                <h4>Finova Africa</h4>
                <p>Means Finova Africa and its affiliates, successors, assigns, employees, agents, contractors and
                    authorised representatives.</p>
            </div>

            <div class="definition">
                <h4>Customer</h4>
                <p>Means any individual or organisation using, subscribing to or accessing any Finova Africa service.
                </p>
            </div>

            <div class="definition">
                <h4>Services</h4>
                <p>Means all software, APIs, mobile applications, cloud infrastructure, payment services, technical
                    support and related solutions provided by Finova Africa.</p>
            </div>

            <div class="definition">
                <h4>Agreement</h4>
                <p>Means this Privacy Policy, Terms of Service and all other policies incorporated by reference.</p>
            </div>

        </div>

    </section>

    <!-- ============================================================
PART 2
PRIVACY POLICY, DATA COLLECTION, DATA PROCESSING,
LEGAL BASIS, COOKIES & COMMUNICATIONS
Continue immediately after Part 1
=============================================================== -->

    <section id="privacy" class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>1. Privacy Commitment</h2>

                <p>

                    Finova Africa respects the privacy of every Customer, Merchant, Cooperative Society,
                    Business Entity, Financial Institution, Government Agency, Developer and Partner
                    using its products and services.

                    This Privacy Policy explains how information is collected, processed, verified,
                    stored, shared and protected whenever you use any Finova Africa software,
                    website, mobile application, API, cloud infrastructure or related digital
                    financial services.

                </p>

                <p>

                    Finova Africa processes personal and business information solely for legitimate
                    business purposes, contractual obligations, fraud prevention, regulatory
                    compliance, security monitoring, service delivery and platform administration.

                </p>

                <p>

                    By accessing or using any Finova Africa product or service, you expressly
                    consent to the processing of your information in accordance with this Agreement.

                </p>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                2. Information We Collect

            </h2>

            <div class="definition">

                <h4>Personal Information</h4>

                <p>

                    Finova Africa may collect your full name, residential address,
                    email address, telephone number, passport photograph,
                    date of birth, gender, nationality, occupation,
                    government-issued identification,
                    BVN, NIN, Tax Identification Number,
                    signature, biometric information where legally required,
                    and any other information necessary to verify your identity.

                </p>

            </div>

            <div class="definition">

                <h4>Business Information</h4>

                <p>

                    Where services are requested on behalf of a business,
                    we may collect business names,
                    CAC registration details,
                    incorporation documents,
                    registered address,
                    directors' information,
                    beneficial ownership information,
                    bank account details,
                    tax information,
                    licences and regulatory approvals.

                </p>

            </div>

            <div class="definition">

                <h4>Financial Information</h4>

                <p>

                    We may collect transaction history,
                    wallet information,
                    virtual account information,
                    bank account details,
                    payment references,
                    settlement records,
                    billing information,
                    subscription history,
                    API usage records,
                    merchant settlement records
                    and other financial information necessary
                    to provide requested services.

                </p>

            </div>

            <div class="definition">

                <h4>Technical Information</h4>

                <p>

                    Finova Africa automatically collects
                    IP addresses,
                    browser information,
                    operating system,
                    device identifiers,
                    MAC address where available,
                    login history,
                    location information where permitted,
                    API request logs,
                    audit trails,
                    session identifiers,
                    security logs,
                    cookies
                    and other technical information.

                </p>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    3. How We Use Your Information

                </h2>

                <p>

                    Information collected by Finova Africa may be used for one or more of the
                    following lawful purposes:

                </p>

                <ul>

                    <li>Creating and managing customer accounts.</li>

                    <li>Identity verification (KYC/KYB).</li>

                    <li>Fraud detection and fraud prevention.</li>

                    <li>Compliance with AML/CFT regulations.</li>

                    <li>Creation of service wallets and operational accounts.</li>

                    <li>Creation of virtual accounts.</li>

                    <li>Cloud hosting and infrastructure provisioning.</li>

                    <li>API configuration and integration.</li>

                    <li>Payment processing.</li>

                    <li>Subscription management.</li>

                    <li>Billing and invoicing.</li>

                    <li>Software licensing.</li>

                    <li>Customer support.</li>

                    <li>Security monitoring.</li>

                    <li>Platform analytics.</li>

                    <li>Risk management.</li>

                    <li>Internal audits.</li>

                    <li>Regulatory reporting.</li>

                    <li>Compliance investigations.</li>

                    <li>Business continuity planning.</li>

                    <li>Improving our products and services.</li>

                </ul>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                4. Legal Basis for Processing

            </h2>

            <div class="definition">

                <p>

                    Finova Africa processes information on one or more of the following legal bases:

                </p>

                <ul>

                    <li>Your consent.</li>

                    <li>The performance of a contract.</li>

                    <li>Compliance with applicable legal and regulatory obligations.</li>

                    <li>Protection of legitimate business interests.</li>

                    <li>Fraud prevention.</li>

                    <li>Security monitoring.</li>

                    <li>Protection of customers and financial systems.</li>

                    <li>Public interest where required by law.</li>

                </ul>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    5. Data Processing Authorisation

                </h2>

                <p>

                    By registering with Finova Africa,
                    submitting an application,
                    signing any quotation,
                    executing any agreement,
                    making payment,
                    clicking "Register",
                    "Create Account",
                    "Continue",
                    "I Agree",
                    or by using any Finova Africa service,
                    you expressly authorise Finova Africa
                    to collect,
                    verify,
                    process,
                    analyse,
                    store,
                    retrieve,
                    share,
                    transmit,
                    archive
                    and otherwise process your personal and business information
                    for the purposes described in this Agreement.

                </p>

                <p>

                    This authorisation remains valid throughout the duration of your relationship
                    with Finova Africa and thereafter for such period as may be necessary to comply
                    with legal, regulatory, contractual, audit, security and operational
                    requirements.

                </p>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                6. Cookies and Tracking Technologies

            </h2>

            <div class="definition">

                <p>

                    Finova Africa may use cookies,
                    web beacons,
                    pixels,
                    device identifiers,
                    log files
                    and similar technologies
                    to improve user experience,
                    maintain secure sessions,
                    remember preferences,
                    detect fraud,
                    analyse traffic,
                    monitor performance
                    and protect platform security.

                </p>

                <p>

                    Disabling cookies may affect the availability
                    or functionality of certain features.

                </p>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    7. Customer Communications

                </h2>

                <p>

                    You authorise Finova Africa to communicate with you using
                    email,
                    SMS,
                    telephone,
                    WhatsApp,
                    mobile push notifications,
                    letters,
                    system notifications,
                    in-app messaging
                    or any other communication channel provided during registration.

                </p>

                <p>

                    Such communications may include
                    security alerts,
                    transaction notifications,
                    service announcements,
                    maintenance notices,
                    billing reminders,
                    subscription notices,
                    regulatory communications,
                    legal notices
                    and other information relating to your account.

                </p>

                <p>

                    Electronic communications shall be deemed
                    received once successfully transmitted
                    to your last known contact information.

                </p>

            </div>

        </div>

    </section>

    <!-- ================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                8. Data Accuracy

            </h2>

            <div class="definition">

                <p>

                    The Customer warrants that
                    all information supplied to Finova Africa
                    is true,
                    complete,
                    accurate
                    and not misleading.

                    The Customer shall promptly notify Finova Africa
                    of any changes to information previously supplied.

                    Finova Africa shall not be responsible
                    for losses arising from inaccurate,
                    false,
                    outdated
                    or incomplete information
                    provided by the Customer.

                </p>

            </div>

        </div>

    </section>
    <!-- ===========================================================
PART 3
SERVICE WALLET • THIRD-PARTY PARTNERS • SUBSCRIPTION
NO REFUND • APP DEACTIVATION POLICY
=========================================================== -->

    <section id="wallet" class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    9. Service Wallet Authorisation

                </h2>

                <h3><strong>9. Service Wallet Authorisation</strong></h3>
                <p>As a mandatory requirement for accessing and using Finova Africa's products and services, a
                    <strong>Service Wallet with Kuda</strong> will be automatically created upon the successful
                    completion of your registration.
                </p>
                <p>By clicking <strong>"Create Account," "Sign Up," "Register," "Continue," "I Agree,"</strong> or by
                    submitting your personal, business or company information during registration, you expressly
                    authorise Finova Africa to create, activate and administer your mandatory Service Wallet with Kuda.
                    Upon successful account creation, a confirmation email containing your Service Wallet details and
                    onboarding information will be sent to your registered email address.</p>
                <p>The Service Wallet shall serve as the primary account for the collection of applicable recurring
                    charges and service fees, including but not limited to <strong>cloud hosting fees, cloud maintenance
                        fees, SMS top-up charges, CAC search fees, platform subscription fees, API service charges,
                        verification service charges and any other approved fees</strong> applicable to your subscribed
                    services, in accordance with Finova Africa's prevailing pricing schedule. By registering for the
                    Services, you authorise Finova Africa to debit such charges from your Service Wallet as they become
                    due, subject to the availability of sufficient funds.</p>
                <p>By completing your registration, you confirm that you have read, understood and consented to the
                    automatic creation and use of the mandatory Service Wallet as a condition of accessing and using
                    Finova Africa's Services. Finova Africa may retain registration records, successful account opening
                    records, Service Wallet creation records, audit logs and other system-generated records as evidence
                    of your acceptance of this authorisation, to the extent permitted by applicable law</p>
            </div>

        </div>

    </section>

    <!-- ===================================================== -->

    <section class="definitions">

        <div class="container">

            <h2>

                10. Third-Party Financial Institution Partners

            </h2>

            <div class="definition">

                <p>

                    Finova Africa collaborates with licensed financial institutions,
                    payment service providers,
                    banks,
                    switching companies,
                    identity verification providers,
                    cloud infrastructure providers
                    and other technology partners
                    to provide certain services.

                </p>

                <p>

                    You expressly authorise Finova Africa to disclose information reasonably
                    required for the establishment, administration and operation of the services
                    you have requested.

                </p>

            </div>

            <div class="definition">

                <h4>Third-Party Disclaimer</h4>

                <p>

                    Finova Africa shall not be responsible for delays,
                    interruptions,
                    errors,
                    downtime,
                    failed transactions,
                    network outages,
                    regulatory restrictions,
                    maintenance activities
                    or other service disruptions arising directly or indirectly from the actions,
                    omissions or systems of third-party financial institutions,
                    payment processors,
                    telecommunication providers,
                    cloud infrastructure providers,
                    internet service providers
                    or regulatory authorities beyond Finova Africa's reasonable control.

                </p>

            </div>

        </div>

    </section>

    <!-- ===================================================== -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    11. Subscription Policy

                </h2>

                <p>

                    Certain Finova Africa products and services are provided on a recurring
                    subscription basis.

                    These include, without limitation:

                </p>

                <ul>

                    <li>Cloud Hosting</li>

                    <li>Software Maintenance</li>

                    <li>API Connectivity</li>

                    <li>Technical Support</li>

                    <li>Security Monitoring</li>

                    <li>Platform Monitoring</li>

                    <li>Server Infrastructure</li>

                    <li>Application Hosting</li>

                    <li>Database Hosting</li>

                    <li>Software Updates</li>

                    <li>Feature Enhancements</li>

                </ul>

                <p>

                    Customers shall maintain an active subscription throughout the duration of
                    their use of the Services.

                    Failure to maintain an active subscription constitutes a material breach of
                    this Agreement.

                </p>

            </div>

        </div>

    </section>

    <!-- ===================================================== -->

    <section class="definitions">

        <div class="container">

            <h2>

                12. Two (2) Consecutive Months of Non-Payment

            </h2>

            <div class="definition">

                <p>

                    Where subscription fees remain unpaid for two (2) consecutive calendar months,
                    Finova Africa reserves the right, subject to the applicable agreement and any
                    requirements of applicable law, to suspend or restrict the affected Services.

                    Such action may include one or more of the following:

                </p>

                <ul>

                    <li>Suspension of customer access.</li>

                    <li>Suspension of APIs and integrations.</li>

                    <li>Suspension of cloud hosting.</li>

                    <li>Suspension of technical support.</li>

                    <li>Suspension of maintenance services.</li>

                    <li>Suspension of software updates.</li>

                    <li>Restriction of administrator access.</li>

                    <li>Temporary deactivation of hosted mobile applications.</li>

                    <li>Temporary deactivation of hosted web applications.</li>

                    <li>Temporary suspension of transaction processing.</li>

                    <li>Termination of the subscription where permitted under this Agreement.</li>

                </ul>

            </div>

            <div class="definition">

                <h4>Restoration of Services</h4>

                <p>

                    Where Services have been suspended for non-payment,
                    Finova Africa may require payment of all outstanding subscription fees,
                    applicable reactivation charges
                    and any other outstanding contractual obligations before considering the
                    restoration of Services.

                    Restoration is subject to technical feasibility,
                    continued availability of infrastructure
                    and Finova Africa's approval.

                </p>

            </div>

        </div>

    </section>

    <!-- ===================================================== -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    13. Project Commencement and No Refund Policy

                </h2>

                <p>

                    All payments made to Finova Africa for software development,
                    customisation,
                    integration,
                    consultancy,
                    implementation,
                    licensing,
                    cloud provisioning,
                    project management,
                    training,
                    API development,
                    UI/UX design,
                    technical configuration,
                    or related professional services are non-refundable once the Project has
                    commenced, except where otherwise required by applicable law or expressly
                    agreed in writing by Finova Africa.

                </p>

                <p>

                    A Project shall be deemed to have commenced immediately upon the occurrence of
                    any one or more of the following events:

                </p>

                <ul>

                    <li>Execution of a contract or service agreement.</li>

                    <li>Acceptance of a quotation or proposal.</li>

                    <li>Receipt of a deposit, milestone payment or full payment.</li>

                    <li>Allocation of developers, engineers or project resources.</li>

                    <li>Commencement of design, development or implementation activities.</li>

                    <li>Provisioning of servers, databases or cloud infrastructure.</li>

                    <li>Configuration of APIs or third-party integrations.</li>

                    <li>Creation of project repositories or technical environments.</li>

                </ul>

                <p>

                    The Customer acknowledges that Finova Africa commits significant technical,
                    financial and operational resources upon project commencement and that payments
                    become earned accordingly.

                </p>

            </div>

        </div>

    </section>

    <!-- ===================================================== -->

    <section class="definitions">

        <div class="container">

            <h2>

                14. Customer Responsibilities

            </h2>

            <div class="definition">

                <p>

                    The Customer agrees to:

                </p>

                <ul>

                    <li>Provide accurate and complete information.</li>

                    <li>Maintain active subscription payments where applicable.</li>

                    <li>Keep login credentials secure.</li>

                    <li>Use the Services only for lawful purposes.</li>

                    <li>Promptly report unauthorised access or security incidents.</li>

                    <li>Comply with all applicable laws and regulatory requirements.</li>

                    <li>Obtain any licences or approvals required for the Customer's own business.</li>

                </ul>

                <p>

                    The Customer shall remain solely responsible for all activities conducted using
                    its account unless otherwise required by applicable law.

                </p>

            </div>

        </div>

    </section>

    <!-- ===========================================================
PART 4
INTELLECTUAL PROPERTY • SOURCE CODE • CONFIDENTIALITY
SECURITY • KYC/AML • CUSTOMER WARRANTIES
=========================================================== -->

    <section id="intellectual" class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    15. Intellectual Property Rights

                </h2>

                <p>

                    Unless otherwise expressly agreed in writing, all intellectual property rights
                    in and relating to Finova Africa's software, applications, APIs, databases,
                    cloud infrastructure, payment systems, documentation, website, source code,
                    algorithms, business processes, designs, trademarks, trade names, logos,
                    graphics, user interfaces, workflows, architecture, proprietary technologies,
                    technical documentation and other materials remain the exclusive property of
                    Finova Africa.

                </p>

                <p>

                    Nothing contained in this Agreement shall be interpreted as transferring any
                    ownership rights, copyrights, patents, trademarks or other intellectual
                    property rights to the Customer.

                    The Customer receives only a limited, non-exclusive,
                    non-transferable and revocable licence to use the Services strictly in
                    accordance with this Agreement.

                </p>

                <p>

                    The Customer shall not copy, reproduce, modify, reverse engineer,
                    decompile, distribute, licence, sell, lease, assign or exploit any part of the
                    Services except with the prior written approval of Finova Africa.

                </p>

            </div>

        </div>

    </section>

    <!-- ========================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                16. Source Code Ownership

            </h2>

            <div class="definition">

                <p>

                    All source code developed, customised or deployed by Finova Africa shall remain
                    the exclusive intellectual property of Finova Africa unless expressly assigned
                    under a separate written Source Code Transfer Agreement signed by authorised
                    representatives of both parties and only after all agreed fees have been paid
                    in full.

                </p>

            </div>

            <div class="definition">

                <h4>Licence Only</h4>

                <p>

                    Unless otherwise agreed in writing, Customers receive only the right to use the
                    software during the term of the applicable agreement.

                    Ownership of the software, source code, APIs, databases, deployment scripts,
                    security keys, documentation and related intellectual property shall remain
                    with Finova Africa.

                </p>

            </div>

            <div class="definition">

                <h4>Restrictions</h4>

                <p>

                    The Customer shall not attempt to obtain, copy, extract, reproduce,
                    reverse-engineer, decrypt, bypass, disassemble or otherwise gain access to any
                    part of the source code or proprietary technology without Finova Africa's prior
                    written consent.

                </p>

            </div>

        </div>

    </section>

    <!-- ========================================= -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    17. Confidentiality

                </h2>

                <p>

                    The Customer acknowledges that confidential information belonging to Finova
                    Africa may be disclosed during the course of the business relationship.

                    Confidential Information includes but is not limited to software architecture,
                    pricing models, APIs, documentation, implementation methods, security
                    procedures, commercial information, technical specifications, source code,
                    customer lists, financial information and other proprietary information.

                </p>

                <p>

                    The Customer agrees to keep all Confidential Information strictly confidential
                    and shall not disclose such information to any third party without Finova
                    Africa's prior written consent except where disclosure is required by law.

                </p>

                <p>

                    This confidentiality obligation survives termination of the Agreement.

                </p>

            </div>

        </div>

    </section>

    <!-- ========================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                18. Information Security

            </h2>

            <div class="definition">

                <p>

                    Finova Africa implements commercially reasonable administrative, technical and
                    organisational security measures designed to protect customer information and
                    maintain the confidentiality, integrity and availability of its systems.

                </p>

            </div>

            <div class="definition">

                <h4>Security Measures</h4>

                <ul>

                    <li>Role-based access controls.</li>

                    <li>Encryption of sensitive information where appropriate.</li>

                    <li>Multi-factor authentication where supported.</li>

                    <li>Continuous security monitoring.</li>

                    <li>Audit logging.</li>

                    <li>Infrastructure monitoring.</li>

                    <li>Regular security updates and patches.</li>

                    <li>Backup and disaster recovery procedures.</li>

                    <li>Incident response procedures.</li>

                </ul>

            </div>

            <div class="definition">

                <h4>Customer Responsibilities</h4>

                <p>

                    The Customer shall maintain the confidentiality of login credentials,
                    implement appropriate internal security controls and immediately notify
                    Finova Africa of any suspected unauthorised access, data breach or compromise.

                </p>

            </div>

        </div>

    </section>

    <!-- ========================================= -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    19. KYC, KYB and AML Compliance

                </h2>

                <p>

                    Finova Africa may require Customers to complete Know Your Customer (KYC),
                    Know Your Business (KYB), Anti-Money Laundering (AML), Counter-Terrorism
                    Financing (CTF), sanctions screening or other regulatory verification
                    requirements before providing or continuing certain Services.

                </p>

                <p>

                    Finova Africa reserves the right to request additional documentation at any
                    time where required to satisfy legal, regulatory, security or operational
                    requirements.

                </p>

                <p>

                    Failure to provide requested information within the specified period may result
                    in suspension, restriction or termination of Services.

                </p>

            </div>

        </div>

    </section>

    <!-- ========================================= -->

    <section class="definitions">

        <div class="container">

            <h2>

                20. Customer Warranties and Representations

            </h2>

            <div class="definition">

                <p>

                    The Customer represents and warrants that:

                </p>

                <ul>

                    <li>All information supplied is true, accurate and complete.</li>

                    <li>The Customer has full legal authority to enter into this Agreement.</li>

                    <li>The Customer will comply with all applicable laws and regulations.</li>

                    <li>The Services will not be used for unlawful, fraudulent or prohibited activities.</li>

                    <li>The Customer has obtained all licences, approvals and authorisations necessary for its business.
                    </li>

                    <li>The Customer will not infringe the intellectual property rights of any third party.</li>

                    <li>The Customer will promptly notify Finova Africa of any material change affecting the Services or
                        information previously provided.</li>

                </ul>

            </div>

            <div class="definition">

                <h4>Breach of Warranty</h4>

                <p>

                    Where Finova Africa reasonably believes that any representation or warranty is
                    false, misleading or has been breached, Finova Africa may suspend, restrict or
                    terminate the affected Services, without prejudice to any other rights or
                    remedies available under this Agreement or applicable law.

                </p>

            </div>

        </div>

    </section>

    <!-- ===========================================================
PART 5
LIMITATION OF LIABILITY • INDEMNITY • TERMINATION
ELECTRONIC CONSENT • GOVERNING LAW • CONTACT
FOOTER
=========================================================== -->

    <section id="liability" class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    21. Limitation of Liability

                </h2>

                <p>

                    To the fullest extent permitted by applicable law, Finova Africa shall not be liable
                    for any indirect, incidental, consequential, exemplary or special damages arising
                    from or relating to the use of the Services.

                </p>

                <p>

                    This includes, without limitation:

                </p>

                <ul>

                    <li>Loss of revenue.</li>

                    <li>Loss of profits.</li>

                    <li>Loss of anticipated savings.</li>

                    <li>Loss of customers.</li>

                    <li>Business interruption.</li>

                    <li>Loss of goodwill.</li>

                    <li>Loss of contracts.</li>

                    <li>Loss of opportunity.</li>

                    <li>Reputational damage.</li>

                    <li>Loss of or corruption of data caused by third parties or circumstances beyond Finova Africa's
                        reasonable control.</li>

                    <li>API failures.</li>

                    <li>Bank network downtime.</li>

                    <li>NIBSS interruptions.</li>

                    <li>Telecommunication failures.</li>

                    <li>Power failures.</li>

                    <li>Internet disruptions.</li>

                    <li>Cyber attacks by third parties.</li>

                    <li>Regulatory directives affecting service availability.</li>

                </ul>

                <p>

                    Finova Africa provides software infrastructure and technology services.
                    Where financial services are delivered through licensed financial institution
                    partners or other regulated third parties, those services remain subject to the
                    terms, policies and operational availability of those providers.

                </p>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <section class="definitions">

        <div class="container">

            <h2>

                22. Customer Indemnity

            </h2>

            <div class="definition">

                <p>

                    The Customer agrees to indemnify, defend and hold harmless Finova Africa,
                    its directors, shareholders, employees, officers, consultants, contractors,
                    successors, affiliates and authorised representatives against any claim,
                    action, investigation, loss, liability, damage, cost or expense (including
                    reasonable legal fees) arising from:

                </p>

                <ul>

                    <li>Breach of this Agreement.</li>

                    <li>Misuse of the Services.</li>

                    <li>Fraudulent activity.</li>

                    <li>Violation of any applicable law.</li>

                    <li>Infringement of third-party rights.</li>

                    <li>Information supplied by the Customer that is false, inaccurate or misleading.</li>

                    <li>The Customer's products, services or business activities.</li>

                </ul>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    23. Suspension and Termination

                </h2>

                <p>

                    Finova Africa reserves the right to suspend, restrict or terminate any account,
                    service or agreement immediately where it reasonably determines that:

                </p>

                <ul>

                    <li>This Agreement has been breached.</li>

                    <li>Required subscription fees remain unpaid.</li>

                    <li>Fraud or suspicious activity is detected.</li>

                    <li>False information has been supplied.</li>

                    <li>A regulatory or law enforcement directive requires suspension.</li>

                    <li>The Customer's activities expose Finova Africa or its partners to unacceptable legal, regulatory
                        or security risks.</li>

                </ul>

                <p>

                    Termination or suspension shall not affect accrued rights, outstanding payment
                    obligations or any provision intended to survive termination.

                </p>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <section class="definitions">

        <div class="container">

            <h2>

                24. Electronic Consent and Digital Acceptance

            </h2>

            <div class="definition">

                <p>

                    By clicking any button labelled "Register", "Create Account", "Continue",
                    "I Agree", "Accept", "Proceed", "Submit", signing electronically,
                    or otherwise accessing or using any Finova Africa product or service,
                    you acknowledge that:

                </p>

                <ul>

                    <li>You have read and understood this Agreement.</li>

                    <li>You freely consent to be legally bound by this Agreement.</li>

                    <li>Your electronic acceptance has the same legal effect as a handwritten signature, to the extent
                        recognised under applicable law.</li>

                    <li>Electronic records, timestamps, audit logs, IP addresses and system logs may be retained and
                        relied upon as evidence of acceptance and transactions.</li>

                </ul>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <section class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    25. Governing Law and Dispute Resolution

                </h2>

                <p>

                    This Agreement shall be governed by and interpreted in accordance with the
                    laws applicable to the jurisdiction agreed between the parties.

                </p>

                <p>

                    Before commencing legal proceedings, the parties shall use reasonable efforts
                    to resolve disputes through good-faith negotiation.

                    Where a dispute cannot be resolved amicably, either party may pursue the
                    remedies available under applicable law before a court or tribunal of
                    competent jurisdiction.

                </p>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <section class="definitions">

        <div class="container">

            <h2>

                26. Miscellaneous Provisions

            </h2>

            <div class="definition">

                <h4>Entire Agreement</h4>

                <p>

                    This Agreement constitutes the entire understanding between Finova Africa and
                    the Customer relating to the Services and supersedes prior discussions or
                    understandings concerning the same subject matter.

                </p>

            </div>

            <div class="definition">

                <h4>Severability</h4>

                <p>

                    If any provision of this Agreement is held to be invalid or unenforceable,
                    the remaining provisions shall continue in full force and effect.

                </p>

            </div>

            <div class="definition">

                <h4>No Waiver</h4>

                <p>

                    Failure by Finova Africa to enforce any right under this Agreement shall not
                    constitute a waiver of that right.

                </p>

            </div>

            <div class="definition">

                <h4>Assignment</h4>

                <p>

                    Finova Africa may assign or transfer its rights or obligations under this
                    Agreement to an affiliate, successor or purchaser of its business without
                    affecting the validity of this Agreement.

                </p>

            </div>

            <div class="definition">

                <h4>Survival</h4>

                <p>

                    Clauses relating to payment obligations, confidentiality, intellectual
                    property, limitation of liability, indemnity, dispute resolution and any
                    provision intended by its nature to survive shall remain effective following
                    termination of this Agreement.

                </p>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <section id="contact" class="notice">

        <div class="container">

            <div class="notice-box">

                <h2>

                    27. Contact Information

                </h2>

                <p>

                    For enquiries relating to this Agreement, privacy, compliance or legal matters,
                    please contact:

                </p>

                <p>

                    <strong>Finova Africa – Legal & Compliance Department</strong><br>

                    Email: legal@finova.ng<br>

                    lagosbranchoperation@finova.ng
                </p>

                <p>

                    Finova Africa reserves the right to amend, revise or update this Agreement at
                    any time. The latest version shall be published on the Finova Africa website
                    and shall become effective from the date of publication unless otherwise stated.

                </p>

            </div>

        </div>

    </section>

    <!-- =========================================================== -->

    <footer style="background:#012a63;color:#fff;padding:60px 0;text-align:center;">

        <div class="container">

            <h2 style="margin-bottom:20px;">

                Finova Africa

            </h2>

            <p style="max-width:900px;margin:auto;line-height:2;">

                This Privacy Policy, Terms of Service and Legal Agreement governs the use of all
                Finova Africa products and services. By accessing or using the Services, you
                acknowledge that you have read, understood and agreed to be bound by this
                Agreement.

            </p>

            <hr style="margin:40px 0;border:1px solid rgba(255,255,255,.15);">
            <br>

            © 2026 Finova Africa. All Rights Reserved.

        </div>
    </footer>

</body>

</html>
