# Consultation & Medical References Audit

> **Purpose:** With the public consultation switch turned **OFF** (`wc_consultations_enabled = 0`), the website operates strictly as an **evidence-based educational publication**. 
> While the booking and tracking flows are hidden from public visitors and protected with 404 responses, this audit lists all remaining textual references to doctors, consultations, appointments, payments, and refunds across pages, theme files, and database options that the site owner may wish to reword manually.

---

## 1. WordPress Pages & Content

### 1.1 About Page (`/about/`)
* **Location:** WordPress Admin > Pages > About Us (`page-about.php`)
* **Current Text Elements:**
  * **Section 2 — Consultant Profile:** Displays "Meet Your Consultant", "Dr. M. A. Khan & Clinical Counseling Team", "MBBS, Certified Counselor in Psychosexual Wellness", and PMDC registration details.
  * **Section 3 — Clinical Scope:** References "tele-counseling", "clinical health education", and "consultations".
  * **Section 5 — Emergency Notice:** Mentions "The services provided on this platform are scheduled consultations and counseling sessions."
* **Recommendation:** If running permanently as an editorial-only publication without doctor profiles, change the profile section to an "Editorial Board & Medical Review Team" or reword the scope to "Evidence-based health writers and clinical reviewers".

### 1.2 Terms of Service (`/terms-of-service/`)
* **Location:** WordPress Admin > Pages > Terms of Service
* **Current Text Elements:**
  * Contains clauses defining consultation agreements, telehealth counseling limitations, fee structures, payment verification, and appointment booking age limits (18+).
* **Recommendation:** Soften or archive consultation clauses while the switch is OFF, or add an introductory note: *"Consultation services described herein are currently paused. Terms apply when consultations are active."*

### 1.3 Privacy Policy (`/privacy-policy/`)
* **Location:** WordPress Admin > Pages > Privacy Policy
* **Current Text Elements:**
  * Explains data retention for consultation intake notes, payment proof screenshots (FR-21), and third-party processors (Stripe, PayPal, Wise).
* **Recommendation:** Keep as-is for future compliance, or note that consultation intake is currently disabled.

### 1.4 Medical Disclaimer (`/medical-disclaimer/`)
* **Location:** WordPress Admin > Pages > Medical Disclaimer
* **Current Text Elements:**
  * States: *"The educational material and consultations provided on this platform are for informational and counseling purposes only..."*
* **Recommendation:** Edit to: *"The educational material provided on this platform is for informational and educational purposes only..."*

### 1.5 Refund & Reschedule Policy (`/refund-policy/`)
* **Location:** WordPress Admin > Pages > Refund & Reschedule Policy
* **Status:** This page is automatically set to **Private** (returns HTTP 404 to public visitors) while consultations are OFF.
* **Content:** Covers rescheduling notice windows (6 hours), refund rules, and payment processor fees. No visitor can view this page while consultations are OFF.

### 1.6 Contact Page (`/contact/`)
* **Location:** WordPress Admin > Pages > Contact Us
* **Current Text Elements:**
  * Text notes indicate that the contact form is not for consultations or emergencies. Quick action cards for "Book Consultation" and "Track Existing Request" are already conditionally hidden when consultations are OFF.

---

## 2. Theme Templates & Customizer Defaults

### 2.1 Theme Customizer Defaults (`inc/customizer.php`)
* **Hero Headline & Subtext Defaults:**
  * `wellness_hero_badge`: `"Confidential Care • Board-Certified Guidance • Worldwide Consultations"`
  * `wellness_hero_subtext`: `"Private consultations and evidence-based guidance in a completely discreet..."`
  * *(Note: `front-page.php` automatically falls back to clean editorial text when consultations are OFF, but Customizer saved values can be refreshed if customized by the owner).*
* **Footer About Text Default:**
  * `wellness_footer_about_text`: `"Private, non-judgmental guidance for individuals and couples worldwide."`
* **Medical Disclaimer Default:**
  * `wellness_medical_disclaimer_text`: Mentions `"and consultations provided on this platform"`. Can be edited in **Appearance > Customize > Medical Disclaimer**.

### 2.2 Template Tags (`inc/template-tags.php`)
* **Function `wellness_get_medical_disclaimer_text()`:**
  * Fallback string contains: *"The educational material and consultations provided on this platform..."*
* **Function `wellness_footer_about_text()`:**
  * Fallback string contains: *"discreet, evidence-based intimacy guidance..."*

### 2.3 Single Article Template (`single.php`)
* **"When to Consult a Qualified Doctor" alert box (Lines 83–91):**
  * Advises patients when symptoms warrant consulting a doctor in person. (Appropriate for an educational health publication; keep or adjust based on editorial guidelines).
* **"Consultant Author" reviewer badge (Line 104):**
  * Can be kept as medical reviewer attribution (SRS FR-35).

---

## 3. Plugin Database Configurations (`wellness-consult`)

These settings are stored safely in the database and are **inactive and hidden from visitors** when the switch is OFF:
* **Consultations > Types:** Quick Chat, Standard, Couple, Follow-up consultation types and mode fees.
* **Consultations > Payments:** Stripe/PayPal keys and manual transfer payment instructions.
* **Consultations > Availability:** Weekly consultant calendar hours and breaks.
* **Consultations > Email Templates:** Booking confirmations, schedule notifications, and appointment reminders.

---

## 4. Re-Enabling Consultations

To restore all consultation features, booking forms, tracking tools, navigation links, and policies:
1. Log in to WordPress Admin.
2. Navigate to **Consultations > Settings**.
3. Under **Public Feature Availability**, check:
   * `[x] Consultations are open to the public`
   * `[x] Anonymous questions are open to the public` (optional)
4. Click **Save Settings**.
5. All 4 pages will instantly be published, navigation links will reappear across the header and footer, and the homepage booking sections will be restored.
