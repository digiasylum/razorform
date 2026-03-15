# RazorForms

> Build beautiful Razorpay payment forms inside WordPress — no code required.

**RazorForms** is a visual payment form builder for WordPress powered by the [Razorpay](https://razorpay.com) payment gateway. Create professional split-layout payment forms with drag-and-drop simplicity, manage every submission in a built-in CRM dashboard, and send fully branded HTML email receipts to clients.

---



## Features

### 🧱 Visual Form Builder
- Split-layout form: service selector on the left, client details on the right
- **Item List** — add unlimited service/package items with fixed prices or **Custom Price** (client enters their own amount)
- Drag-and-drop reordering for items and custom fields
- Customisable brand colour, title, subtitle, description, and CTA button label

### 📋 Custom Form Fields
- Core fields (Name, Email, Phone) with editable labels, placeholders, and Required/Optional toggle
- Additional fields: Text, Textarea, Dropdown, Checkbox Group, Radio Buttons, Date, Number, URL
- Per-field required toggle and half/full width control

### 💳 Razorpay Integration
- Hosted checkout — no card data stored on your server
- Webhook support: `payment.captured`, `payment.failed`, `refund.created`
- Multi-currency: INR, USD, EUR, GBP, SGD, AED
- Test mode support (`rzp_test_` keys)

### 📊 Submissions CRM
- All payments in one dashboard with search, filter by form/status, and pagination
- Individual submission detail view with contact info, payment details, and custom field responses
- Order summary for item-based forms

### 🧾 PDF Receipt Generator
- Auto-generated branded receipt for every payment
- Single-page layout — fits A4 without overflow
- Accessible from admin (one click) and via secure token link in client emails
- Print or save as PDF via browser

### 📧 Branded HTML Emails
- **Admin notification** — sent on every successful payment
- **Client confirmation** — optional, sent to the payer's email
- Fully customisable: subject line, headline, brand name, colours, footer
- Dynamic token variables: `{id}`, `{form}`, `{amount}`, `{name}`, `{email}`, `{brand}`, `{txn}`
- HTML-supported footer (links, bold, etc.)

### 🎨 Email & Receipt Design Controls
| Setting | Description |
|---------|-------------|
| Brand Name | Custom text shown in email header and receipt (uppercase, symbols, etc.) |
| Header Background | Colour picker for the email/receipt header |
| Header Text Colour | Colour of brand name — set dark for light backgrounds |
| Body Text Colour | Main text colour in email body and receipt |
| Footer Text | HTML-supported footer line |

---

## Installation

### From ZIP
1. Download the latest release ZIP
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the ZIP and click **Activate**

### Manual
1. Unzip and upload the `razorforms` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**

---

## Setup

### 1. Add Razorpay Keys
Go to **RazorForms → Settings** and enter your Razorpay Key ID.  
Get keys from: [Razorpay Dashboard → Settings → API Keys](https://dashboard.razorpay.com/app/keys)

Use `rzp_test_` keys for testing, `rzp_live_` for production.

### 2. Configure Webhook (Recommended)
Copy the **Webhook URL** from Settings and add it in [Razorpay → Webhooks](https://dashboard.razorpay.com/app/webhooks).  
Enable events: `payment.captured`, `payment.failed`, `refund.created`.  
Add a Webhook Secret in both Razorpay and RazorForms Settings.

### 3. Create a Form
Go to **RazorForms → Payment Forms → Add New**.  
Use the 4-tab builder to set up your form, then **Publish**.

### 4. Embed the Form
Copy the shortcode from the **Shortcode** meta box:
```
[razorform id="YOUR_FORM_ID"]
```
Paste into any page, post, Elementor shortcode widget, or Gutenberg block.

---

## Email Tokens

Use these in subject lines and headline fields:

| Token | Replaced with |
|-------|--------------|
| `{id}` | Submission number |
| `{form}` | Form name |
| `{amount}` | Amount paid |
| `{name}` | Client full name |
| `{email}` | Client email |
| `{brand}` | Brand / site name |
| `{txn}` | Razorpay payment ID |

**Example subject:** `New Payment Received | ₹{amount} from {name}`  
**Result:** `New Payment Received | ₹1,000.00 from Umesh Kumar`

---

## Changelog

### v1.3.0
- PDF receipt generator (single-page, branded, print/save as PDF)
- Email template designer: brand name, header colour, title colour, body colour
- HTML footer support in emails and receipts
- Custom email subject + headline with token variables
- Required/Optional toggle on core fields (Name, Email, Phone)
- Additional fields redesigned to match core field card style
- **Bug fix:** Item list no longer resets after form update (double-guard: PHP meta check + JS DOM check)
- **Bug fix:** Email preview cards now reflect saved colour and brand settings
- Success popup close button (✕) added
- Settings cleanup: shortcode section removed, email fields merged

### v1.2.0
- Frontend custom price input visibility fix (label → div restructure)
- Admin menu deduplication
- Correct Dashboard landing page for RazorForms menu icon
- Author cards fully clickable

### v1.1.0
- Templates tab removed (agency layout auto-applied)
- Pricing tab renamed to Item List
- Core fields made editable
- About/Dashboard page added
- Layout fixed to Split

### v1.0.0
- Initial release

---

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 6.0+ |
| PHP | 8.0+ |
| Razorpay Account | Any plan |

---

## Credits

Developed by **[Digiasylum](https://www.digiasylum.com)**  
Lead Developer: **[Umesh Kumar Sahai](https://linkedin.com/in/umeshkumarsahai)**  
Powered by **[Razorpay](https://razorpay.com)**

---

## License

GPL v2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)
