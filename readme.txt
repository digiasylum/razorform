=== RazorForms ===
Contributors: digiasylum, umeshkumarsahai
Tags: razorpay, payment, payment form, order form, service booking
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build beautiful Razorpay payment forms with a drag-and-drop builder. Accept service bookings, custom-priced items, and one-time payments — no coding required.

== Description ==

RazorForms is a visual payment form builder for WordPress powered by the Razorpay payment gateway. Create professional payment forms in minutes with the drag-and-drop builder, send branded email confirmations, and track every payment inside your WordPress dashboard.

**Key Features**

* Visual split-layout form builder — no code required
* Service / package item list with fixed or client-entered (Custom Price) amounts
* Custom additional fields: text, textarea, dropdown, checkbox, radio, date, number, URL
* CRM-style submissions dashboard with search, filter, and status tracking
* Branded HTML email notifications (admin + client) with fully customisable subject lines, headlines, and token variables
* PDF receipt generator — auto-generated, single-page branded receipt accessible from admin and emailed to clients
* Email template design controls: brand name, header colour, header text colour, body text colour, HTML footer
* Webhook support for payment.captured, payment.failed, refund.created events
* Multi-currency support: INR, USD, EUR, GBP, SGD, AED
* Trust badges, order summary toggle, custom success message
* Secure token-based client receipt links (no login required)

== Installation ==

1. Upload the `razorforms` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **RazorForms → Settings** and enter your Razorpay Key ID
4. Go to **RazorForms → Payment Forms → Add New** to create your first form
5. Copy the shortcode and paste it into any page or post

== Configuration ==

**Razorpay Keys**
Obtain your API keys from [Razorpay Dashboard → Settings → API Keys](https://dashboard.razorpay.com/app/keys).
Use `rzp_test_` prefix keys for testing and `rzp_live_` for production.

**Webhook (Recommended)**
Copy the Webhook URL from RazorForms → Settings and add it in [Razorpay → Webhooks](https://dashboard.razorpay.com/app/webhooks).
Enable events: `payment.captured`, `payment.failed`, `refund.created`.
Set a Webhook Secret and enter it in Settings for verified delivery.

**Shortcode Usage**
`[razorform id="YOUR_FORM_ID"]`
The form ID is shown in the Shortcode meta box on each form's edit page.

== Email Tokens ==

Use these tokens in email subject lines and headlines:

| Token | Value |
|-------|-------|
| `{id}` | Submission number |
| `{form}` | Form name |
| `{amount}` | Amount paid |
| `{name}` | Client full name |
| `{email}` | Client email address |
| `{brand}` | Your brand/site name |
| `{txn}` | Razorpay payment ID |

== Frequently Asked Questions ==

= Does this plugin store card details? =
No. All payment processing happens through Razorpay's hosted checkout. RazorForms only stores the payment ID and form field responses.

= Can I use test mode? =
Yes. Enter a `rzp_test_` key in Settings. All payments will go through Razorpay's test environment.

= Can I have multiple forms? =
Yes. Create as many forms as needed, each with its own items, fields, and settings.

= Does it work with Elementor / Gutenberg? =
Yes. Paste the shortcode into any Elementor shortcode widget or Gutenberg shortcode block.

= Can clients access their receipt without logging in? =
Yes. The client confirmation email includes a secure receipt link valid for that specific payment using an HMAC token.

== Screenshots ==

1. Form Builder — Item List tab with custom pricing
2. Form Builder — Form Fields tab with drag-and-drop additional fields
3. Frontend payment form (split layout)
4. All Submissions CRM dashboard
5. PDF Receipt
6. Settings — Email Template Design

== Changelog ==

= 1.5.0 =
* Fixed success popup close button — moved to bottom of card as a proper full-width button, no longer floating outside the card
* Fixed Form Fields admin builder — removed legacy CSS conflicts; saved and new fields now display consistently with correct full-width inputs
* Fixed admin notification email — recipient now always resolved fresh from RazorForms Settings, never depends on stale per-form value
* Fixed Submission detail items table — Item column now has text-align:left matching the Price column
* Version bump to 1.5.0

= 1.3.0 =
* Added PDF receipt generator — single-page branded receipt with print/save functionality
* Added email template design controls: brand name, header background colour, header text colour, body text colour
* Added HTML-supported footer textarea for emails and receipts
* Added custom email subject and headline fields for both admin and client emails with token support
* Added Required/Optional toggle to core form fields (Name, Email, Phone)
* Additional fields redesigned to match core field card style
* Fixed item list resetting to defaults on form update (double guard: PHP meta check + JS DOM check)
* Fixed email preview cards not reflecting new colour and brand settings
* Fixed success popup — added ✕ close button at top-right of overlay
* Removed shortcode section from Settings (already shown per-form)
* Merged admin/from email into single notification email field

= 1.2.0 =
* Fixed frontend custom price input visibility (restructured from label to div chips)
* Fixed admin menu duplication — Payment Forms no longer appears twice
* Fixed RazorForms icon now correctly lands on Dashboard
* CSS cache-bust via version bump
* Author cards in About page are now fully clickable rows

= 1.1.0 =
* Removed Templates tab — agency layout applied automatically
* Renamed Pricing tab to Item List (service items only)
* Core form fields (Name, Email, Phone) are now editable (label + placeholder)
* Added About/Dashboard page with plugin info and contributor credits
* Layout selector removed — split layout applied permanently
* Admin menu deduplication

= 1.0.0 =
* Initial release
* 5-tab visual form builder: Design, Pricing, Form Fields, Settings, Templates
* Razorpay hosted checkout integration
* Three pricing modes: fixed, custom, item list
* CRM submissions dashboard
* Webhook handler for payment events
* Multi-currency support
* Client confirmation emails
* Five built-in templates

== Upgrade Notice ==

= 1.3.0 =
Major update with PDF receipts, email template designer, and critical bug fixes. Recommended for all users.

== Credits ==

Developed by [Digiasylum](https://www.digiasylum.com).
Lead developer: Umesh Kumar Sahai.
Powered by [Razorpay](https://razorpay.com).
