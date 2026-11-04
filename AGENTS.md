# SRS v2.0 - Global Sexual Wellness Consultation Website
Version 2.0 · Replaces v1.0 · Platform: WordPress · Build tool: Antigravity IDE · Local server: Laragon · Audience: worldwide

## 0. What Changed from v1.0
| Area   | v1.0   | v2.0   |
| Audience   | Pakistan, Urdu / Roman Urdu   | Worldwide; English is the main language, Urdu and Roman Urdu are secondary   |
| Payment   | JazzCash, Easypaisa, bank + screenshot   | Card / PayPal checkout (automatic) plus manual transfer with screenshot upload as a second option   |
| Currency   | PKR   | USD default, other currencies shown for information   |
| Time   | PKT only   | Patient picks own time zone; appointments shown in both zones   |
| Legal   | Basic policies   | GDPR / CCPA, country restrictions, cross-border consultation rules   |
| Admin   | Request list   | Full control panel: dashboard, calendar, reports, roles   |
| Tracking   | Short note   | Full Track My Request process with security rules   |
| Content   | Page names   | Page-by-page content plan   |

## 1. Introduction
### 1.1 Purpose
This document defines all requirements of a worldwide website that provides sexual wellness education and paid private consultations with a qualified doctor or counselor. It is written to be given to Antigravity one phase at a time.

### 1.2 Scope
Content site: articles, myths vs facts, free anonymous Q&A.
Paid consultation system: the patient pays first (online checkout or manual transfer with a screenshot), the system or admin confirms payment, then a consultation time is assigned.
Admin control panel to run everything from the WordPress dashboard.

### 1.3 Technology
WordPress (latest), custom lightweight theme wellness-theme
Custom plugin wellness-consult for booking, payments, scheduling and tracking
Stripe Checkout and PayPal (hosted pages, so no card data touches the site)
Rank Math (SEO), Polylang or TranslatePress (languages), cookie-consent plugin, SMTP plugin, Cloudflare (CDN and security), backup plugin

### 1.4 Definitions
| Term   | Meaning   |
| Patient   | Any visitor who books a consultation   |
| Consultant   | Qualified doctor or counselor who gives the consultation   |
| Admin   | Site owner who manages payments, schedule and content   |
| Proof   | Payment screenshot uploaded for manual transfers   |
| Reference ID   | Unique request code, e.g. WC-2026-00123   |

## 2. User Roles
| Role   | Permissions   |
| Visitor   | Read content, ask a free anonymous question, start a booking   |
| Patient (guest, no account)   | Submit a booking, pay, upload proof, track status with Reference ID + contact detail   |
| Consultant   | See only own assigned requests, mark completed, add private notes, set own availability   |
| Editor (optional)   | Articles and pages only; no access to patient data   |
| Admin   | Everything   |

Patients do not need an account. This protects privacy and removes friction.

## 3. End-to-End Flow (A to Z)
1. Patient clicks Book a Private Consultation.
2. Patient chooses a consultation type; fee is shown in USD (and approximate local currency).
3. Patient chooses a payment method:
   A. Online (card / PayPal): redirected to the secure hosted checkout, pays, returns to the site. The system confirms payment automatically.
   B. Manual transfer (bank, Wise, local wallet): the site shows account details and exact amount. The patient pays from their own app, then uploads the payment screenshot.
4. Patient fills the booking form (alias allowed, age, gender, country, time zone, contact, concern, message, preferred time window).
5. Patient submits. Confirmation page shows the Reference ID and status. Confirmation email is sent if an email was given.
6. Admin receives an email and a dashboard alert.
7. Path A: status becomes Paid - Awaiting Scheduling automatically. Path B: status is Pending Verification; admin opens the screenshot, checks the money in their own account, then approves or rejects (reason required). A rejected patient can upload a new screenshot.
8. Admin (or the auto-scheduler, if enabled) assigns consultant, date, time, duration and mode (Google Meet, Zoom, WhatsApp call, phone or chat) and adds the meeting link.
9. Patient is notified by email (and the admin can send a WhatsApp message with one click). Details also show on the Track page.
10. Reminders go out 24 hours and 1 hour before, in the patient's local time.
11. Consultation takes place. Consultant or admin marks it Completed.
12. Patient gets a thank-you message, an optional anonymous feedback form and a follow-up booking button.
13. Cancellation, reschedule and refund follow the refund policy.

## 4. Functional Requirements
### 4.1 Public website
| ID   | Requirement   |
| FR-1   | Pages: Home, Articles, Category pages, Single article, Myths vs Facts, Ask a Question, Book Consultation, Track My Request, About / Our Consultant, Contact, Privacy Policy, Terms, Refund Policy, Disclaimer, 404   |
| FR-2   | Header with logo, menu, search, language switcher and a Quick Exit button that redirects instantly to a neutral site and replaces the history entry   |
| FR-3   | Categories: Intimacy, Myths vs Facts, Men's Health, Women's Health, Lifestyle   |
| FR-4   | Disclaimer under every article and in the footer   |
| FR-5   | No explicit images; illustrations and abstract icons only   |
| FR-6   | Languages: English (default), Urdu (RTL), Roman Urdu. Each article can exist in one or more languages with hreflang tags   |

### 4.2 Consultation types (all editable by admin)
| Type   | Example duration   | Fee   |
| Quick chat consultation   | 15 min   | Set by admin   |
| Standard consultation   | 30 min   | Set by admin   |
| Couple consultation   | 45 min   | Set by admin   |
| Follow-up   | 15 min   | Set by admin   |
FR-7: Admin can add, edit, disable types and change duration, fee and description. Fees are stored in USD.

### 4.3 Payments
FR-8: Online checkout: Stripe Checkout and PayPal. A webhook confirms payment and moves the request to Paid - Awaiting Scheduling. If the patient abandons checkout, the request stays Awaiting Payment and expires after 24 hours.
FR-9: Manual transfer: admin keeps a list of methods (bank / IBAN, Wise, local wallet) with account title, number and instructions; each can be switched on or off, and shown only to selected countries if wanted.
FR-10: The booking page clearly says the booking is not confirmed until payment is received (online) or verified (manual).
FR-11: Fees are charged in USD. The page may show an approximate local amount for information, using a daily exchange-rate lookup.
FR-12: Refunds for online payments are started from the request screen (calls the gateway refund); manual refunds are logged by the admin.
FR-13: No card numbers are ever stored by the site.

### 4.4 Booking form
| Field   | Rules   |
| Consultation type   | Required   |
| Name or alias   | Required, any name allowed   |
| Age   | Required, 18 or above; under 18 is blocked with a message   |
| Gender   | Required   |
| Country   | Required; checked against the admin's allowed-countries list   |
| Time zone   | Required, auto-detected and editable   |
| Contact   | WhatsApp number with country code or email (at least one; email needed for automatic emails)   |
| Concern category   | Required   |
| Message   | Optional, max 1000 characters   |
| Preferred time window   | Required (morning, afternoon, evening, night in the patient's time zone)   |
| Payment method   | Required   |
| Transaction ID   | Optional for manual transfer   |
| Payment screenshot   | Required for manual transfer: JPG, PNG or PDF, max 5 MB   |
| Consent   | Required: Terms, Privacy Policy, Refund Policy, and consent to process health-related data   |
FR-14: Client and server validation with clear messages.
FR-15: Spam protection: honeypot, nonce, rate limit (3 submissions per IP per hour), Cloudflare Turnstile or reCAPTCHA.
FR-16: After submission, redirect to a confirmation page with the Reference ID.

### 4.5 Screenshot upload (manual transfer)
FR-17: Accept only image/jpeg, image/png, application/pdf; verify the real MIME type.
FR-18: Rename files randomly and store them in a protected folder that cannot be opened by direct URL; admins view them through a secure handler.
FR-19: Preview before submit; allow replacing the file.
FR-20: A rejected patient can upload a new proof; old proofs stay in history.
FR-21: Proofs are deleted automatically a set time after completion (default 90 days).

### 4.6 Scheduling and time zones
FR-22: Admin and each consultant define weekly availability, breaks and blocked dates in one base time zone.
FR-23: The system never allows double booking of a slot.
FR-24: Admin assigns the slot manually in version 1. Optional later: patient picks from free slots after payment.
FR-25: Every appointment is shown in the consultant's time zone and the patient's time zone, with the zone name.
FR-26: Reminders are sent using WP-Cron (or a real server cron) at the patient's local time.

### 4.7 Admin verification and scheduling
FR-27: List of all requests with filters (status, date, country, consultant, payment method), search and bulk actions.
FR-28: Request detail screen: form data, payment info, screenshot (zoomable), status history, internal notes.
FR-29: Actions: Approve payment, Reject (reason required), Request new screenshot, Schedule, Reschedule, Cancel, Mark completed, No-show, Refund.
FR-30: Every action is logged (who, what, when) and triggers a patient notification.

### 4.8 Track My Request (process)
Patient opens the page and enters Reference ID plus the same email or WhatsApp number used in the booking. No login.
If both match, the status card is shown; if not, the page only says Request not found. After 5 failed attempts the visitor is locked out for 15 minutes.
The card shows status badge, consultation type, appointment date and time in both time zones, mode and meeting link (only inside the last 24 hours if the admin chooses), and any rejection reason.
Buttons depend on status:
| Status   | Message   | Buttons   |
| Awaiting Payment   | Payment not completed   | Pay now, Cancel   |
| Pending Verification   | Proof is being checked, usually within a few hours   | Cancel   |
| Rejected   | Reason shown   | Upload new screenshot, Cancel   |
| Paid - Awaiting Scheduling   | Payment confirmed, time will be assigned soon   | Cancel   |
| Scheduled   | Appointment details   | Reschedule (once, at least 6 hours before), Cancel   |
| Completed   | Thank-you, optional feedback   | Book follow-up   |
| Cancelled / Refunded   | Refund state and expected time   | Book again   |
Reschedule: patient states a preferred window; admin assigns a new time; patient is notified; the new time replaces the old one in the log.
Cancel: patient chooses a reason; refund rules are applied automatically as a suggestion and the admin confirms.
No personal medical text is shown on this page.

### 4.9 Notifications
| Event   | Patient   | Admin / Consultant   |
| Request submitted   | Email + on-screen   | Email + dashboard badge   |
| Payment confirmed (online)   | Email   | Email   |
| Rejected   | Email + status page   | -   |
| Scheduled   | Email + status page   | Consultant email   |
| Reminder (24 h, 1 h)   | Email   | Consultant email   |
| Completed   | Thank-you email   | -   |
FR-31: Email templates are editable in settings, in each supported language.
FR-32: A Send on WhatsApp button opens a wa.me link with a ready message (no paid API in v1).

### 4.10 Free anonymous Q&A
FR-33: Public form with question, category and optional email. No name needed.
FR-34: Questions go to a moderation queue. Admin or consultant answers and can publish a version with all personal details removed.

### 4.11 Blog and SEO
FR-35: Standard posts with category, tags, featured image, reading time, author box and a Reviewed by Dr. [Name] line.
FR-36: Rank Math, Article and FAQ schema, sitemap, breadcrumbs, canonical and hreflang tags.
FR-37: Keyword research is done in English for the US, UK, Canada, Australia and Gulf markets first, then Urdu and Roman Urdu.

### 4.12 Consultant profile
FR-38: Name, qualification, license or registration number, countries where licensed, experience and photo. Mandatory for trust and compliance.

## 5. Request Status Lifecycle
| Status   | Meaning   | Next   |
| Awaiting Payment   | Online checkout started, not paid   | Paid / Expired / Cancelled   |
| Pending Verification   | Manual transfer with proof submitted   | Paid / Rejected   |
| Rejected   | Proof invalid   | New proof, then Pending Verification   |
| Paid - Awaiting Scheduling   | Payment confirmed   | Scheduled / Cancelled   |
| Scheduled   | Time assigned   | Completed / Rescheduled / Cancelled / No-show   |
| Completed   | Consultation done   | Closed   |
| Cancelled   | Cancelled by patient or admin   | Refunded (if applicable)   |
| No-show   | Patient did not attend   | Closed   |
| Refunded   | Money returned   | Closed   |
| Expired   | Online payment never finished   | Closed   |

## 6. Admin Control Panel
The plugin adds a Consultations menu in the WordPress dashboard.
| Menu   | What it controls   |
| Dashboard   | New requests, pending verification, today's appointments, weekly income, quick actions   |
| All Requests   | List, filters, request detail, screenshot viewer, all actions from FR-29   |
| Calendar   | Day and week view of appointments, free and booked slots   |
| Consultation Types   | Types, duration, fee, description, on / off   |
| Payments   | Gateway keys, manual transfer methods, country rules, refund log   |
| Availability   | Working days and hours per consultant, blocked dates, base time zone   |
| Countries   | Allowed or blocked countries for booking   |
| Anonymous Questions   | Moderation queue, answers, publish or delete   |
| Email Templates   | All patient and staff messages, per language   |
| Settings   | Refund and reschedule rules, reminder timing, proof retention, spam limits, privacy options   |
| Reports   | Requests, income, completed, cancelled, by country and date range, export to CSV   |
| Activity Log   | Every admin action with user and time   |
The rest of the site is managed with standard WordPress tools: Posts (articles), Pages (policies, About, FAQ), Appearance (menus, logo, colors), Rank Math (SEO), Polylang (translations).
Daily admin routine: check the dashboard, verify new proofs, schedule paid requests, reply to questions, mark completed consultations.

## 7. Data Requirements
Requests are stored in a custom post type wc_request (or a custom table) with:
reference_id, status, type_id, fee_usd, payment_method, payment_provider_id, paid_at, name, age, gender, country, timezone, contact_phone, contact_email, concern, message, preferred_window, transaction_id, proof_file, consultant_id, appointment_utc, duration, mode, meeting_link, admin_notes, rejection_reason, language, created_at, updated_at, ip_hash
Additional data: consultation types, payment methods, availability, allowed countries, status log, email templates, consent records (what was accepted, when, which policy version).

## 8. Page-by-Page Content Plan
### 8.1 Home
| Block   | Content   |
| Header   | Logo, menu (Home, Articles, Myths vs Facts, Ask a Question, Book Consultation, About), search, language switcher, Quick Exit button   |
| Hero   | Headline: Honest, private answers about intimacy and sexual health. Short subtext. Buttons: Book a Private Consultation and Ask Anonymously   |
| Trust strip   | Qualified doctor, 100% private, Reviewed content, Worldwide, secure payment   |
| Categories   | 5 cards with icon and one line each   |
| Latest articles   | 6 newest posts as cards   |
| Myths vs Facts   | 3 rotating pairs with a link to the full page   |
| How it works   | 4 steps: Choose type, Pay (card, PayPal or transfer + screenshot), We confirm, Get your time and consult   |
| Consultant preview   | Photo, name, qualification, link to About   |
| FAQ   | 6 questions: privacy, payment, time zones, refund, who is the doctor, is it anonymous (FAQ schema)   |
| Newsletter   | Email field with privacy note   |
| Footer   | Quick links, policy links, contact, disclaimer   |

### 8.2 Articles
Title and intro, search bar, category chips, grid of cards (9 per page) with pagination, call-to-action blocks for booking and anonymous questions.

### 8.3 Category pages (5)
Title, 2 to 3 line SEO description, featured article, article grid, related categories, call-to-action.

### 8.4 Single Article
| Block   | Content   |
| Top   | Breadcrumb, category, title, reading time, updated date, Reviewed by Dr. [Name]   |
| Body   | Short intro, clear headings, simple language, key-points box, When to see a doctor box   |
| Disclaimer   | Educational information, not medical advice   |
| Author box   | Consultant photo, name, qualification   |
| Call-to-action   | Book a consultation, Ask anonymously   |
| Related   | 3 related articles; share options: copy link and WhatsApp only   |

### 8.5 Myths vs Facts
Intro line, cards with Myth, Fact, 2 to 3 line explanation and a link to the full article. Category filter. At least 15 cards at launch.

### 8.6 Ask a Question
Short explanation of privacy, form (question, category, optional email), notice that answers may be published without personal details and that this is not for emergencies, list of answered questions below.

### 8.7 Book Consultation
Intro (who gives it, privacy promise), Step 1 consultation types and fees, Step 2 payment method choice with instructions, Step 3 booking form including screenshot upload for manual transfers, side box with What happens next and refund summary. Confirmation page shows Reference ID, status and a link to the Track page.

### 8.8 Track My Request
Form (Reference ID + email or WhatsApp), status card and buttons as defined in section 4.8.

### 8.9 About / Our Consultant
Mission (2 short paragraphs), consultant profile with license number and countries of practice, how content is reviewed, privacy and honesty promise, call-to-action.

### 8.10 Contact
Form (email, message), support email, working hours, note that this form is not for consultations or emergencies.

### 8.11 Policy pages
Privacy Policy: data collected, purpose, legal basis, retention, who can see it, security, cookies, deletion and access requests, international transfers.
Terms: adults only, educational purpose, not for emergencies, correct information, payment and verification rules, limitation of liability, governing law.
Refund and Reschedule Policy: when refunds apply, timelines, one free reschedule, no-show rule.
Medical Disclaimer: education only, no diagnosis or treatment guarantees, emergency warning.

### 8.12 404
Friendly message, search, links to Home, Articles, Ask a Question, and the Quick Exit button.

### 8.13 Minimum content at launch
| Item   | Amount   |
| Articles   | 20 (4 per category), English first   |
| Myths vs Facts cards   | 15   |
| Answered Q&A   | 5   |
| FAQ entries   | 6   |
| Policy pages   | 4 complete   |
| Consultant profile   | Complete with photo and license number   |

## 9. Non-Functional Requirements
| Area   | Requirement   |
| Privacy   | Health-related data: HTTPS everywhere, minimum data, no personal data in URLs, no ad or tracking scripts on booking and track pages   |
| Security   | Nonces, capability checks, prepared SQL, escaped output, hardened uploads, admin 2FA, login limits, Cloudflare WAF, daily backups   |
| Performance   | Mobile-first, under 3 seconds on 4G, WebP, lazy loading, CDN, caching   |
| Compatibility   | Latest Chrome, Safari, Firefox, Edge; Android and iOS   |
| Accessibility   | Good contrast, large tap targets, keyboard use, alt text   |
| Localization   | English, Urdu RTL, Roman Urdu; correct time zones and date formats; currency shown clearly   |
| Maintainability   | Commented code, no hardcoded fees, accounts or numbers, WordPress standards and hooks   |

## 10. Legal and Compliance (Worldwide)
Who may consult: only a qualified, registered doctor or counselor. Nobody may be called a sexologist unless qualified.
Cross-border practice: giving medical advice to patients in another country often needs a license valid in the patient's location (strict in the USA, state by state). Therefore:
Offer consultations as counseling and education (no diagnosis, no prescriptions) unless the consultant is licensed for the patient's country.
Admin keeps an allowed-countries list (section 6). Booking from other countries is blocked with a clear message.
A lawyer and the consultant must confirm the list before launch.
Privacy laws: GDPR (EU, UK), CCPA (California) and similar. Needs: cookie consent banner, clear privacy notice, explicit consent for health-related data, data access and deletion on request, record of consent, retention limits (see FR-21), and a data processing note for Stripe, PayPal, email and hosting providers.
Adults only (18+). Under-18 bookings are blocked.
Emergency notice on the booking and contact pages.
No sale of medicines or supplements in v1.
Payment providers and ads: Stripe, PayPal and AdSense can restrict accounts in this niche. Keep content educational, terms clear, and do not run ads on booking or track pages.
Records: invoices or receipts for online payments are emailed automatically; keep tax records as required in the owner's country.

## 11. Build Plan for Antigravity
Give one phase at a time and test on Laragon before moving on.
| Phase   | Task   | Done when   |
| 1   | Install WordPress locally, create theme skeleton, global colors and fonts, header with Quick Exit, footer   | Theme activates and looks right on mobile   |
| 2   | Homepage and blog templates, single post with disclaimer   | Sample posts display correctly   |
| 3   | Plugin skeleton: custom post type, statuses, settings (types, payment methods, countries, availability)   | Admin can add types and methods   |
| 4   | Booking page and form, validation, secure screenshot upload   | Test request saved with private proof   |
| 5   | Stripe and PayPal checkout with webhooks   | Test payment moves status automatically   |
| 6   | Admin list, detail screen, approve or reject, scheduling, calendar, activity log   | Admin completes the full flow   |
| 7   | Track My Request page with lock-out rules and re-upload   | Patient sees status and appointment   |
| 8   | Emails, reminders in patient time zone, WhatsApp buttons   | All test emails arrive   |
| 9   | Dashboard and reports, Anonymous Q&A moderation   | Reports and queue work   |
| 10   | Languages, hreflang, cookie consent, policy pages, SEO setup   | Language switch and consent banner work   |
| 11   | Security, speed, Cloudflare, backups   | Checklist below passes   |
| 12   | Move to hosting, SSL, SMTP, live payment test with a small amount   | Live site works end to end   |

## 12. Acceptance Checklist
- [ ] Booking, payment and (if manual) screenshot upload works on a phone in under 3 minutes
- [ ] Manual booking without a screenshot is blocked; wrong file types and oversized files are rejected
- [ ] Screenshots cannot be opened by direct URL
- [ ] Online payment updates the status automatically and a refund works from the admin screen
- [ ] Booking from a blocked country is stopped with a clear message
- [ ] Appointment shows correctly in both time zones; reminders arrive at the right local time
- [ ] Double booking is impossible
- [ ] Track page locks after 5 failed attempts and shows the right buttons for each status
- [ ] Rejected patient can upload a new screenshot; old one stays in history
- [ ] Consultant sees only own requests; editor sees no patient data
- [ ] Cookie banner, privacy and consent records work
- [ ] Quick Exit works on every page; disclaimer appears on every article and in the footer
- [ ] Passes mobile speed test and has valid SSL

## 13. Decisions Needed Before the Build
- Site name and logo
- Consultant name, qualification, license number and the countries where licensed
- Consultation scope: only a few countries at launch, or worldwide from day one (see section 10)
- Consultation types, durations and fees in USD
- Payment methods: Stripe, PayPal, and which manual transfer accounts (bank, Wise, local wallet)
- Consultation mode: Google Meet, Zoom, WhatsApp, phone
- Consultant working days, hours and base time zone
- Refund and reschedule rules
- Languages at launch (English only, or English plus Urdu)
- Final homepage design direction (new designs still to be chosen)
