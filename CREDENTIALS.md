# 🔑 Platform Login Credentials & Access Guide

All demo accounts share the same default password: **`password123`**

---

## 🛡️ 1. Compliance Admin Account

| Field | Detail |
| :--- | :--- |
| **Role** | Platform Administrator / Compliance Officer |
| **Name** | Portal Compliance Admin |
| **Email** | `admin@portal.com` |
| **Password** | `*password123*` |
| **Login URL** | [http://localhost/start%20up%20portal/auth/login.php](http://localhost/start%20up%20portal/auth/login.php) |
| **Direct Dashboard** | [http://localhost/start%20up%20portal/admin/dashboard.php](http://localhost/start%20up%20portal/admin/dashboard.php) |
| **Key Features** | KYC Review Queue, Funding Round Approvals, User Governance, Escrow Ledger, Audit Trail |

---

## 🚀 2. Founder Accounts

### Founder 1 (Primary Demo Founder)
- **Name:** Vivek Morwadiya (Founder & CEO)
- **Company:** TechPulse AI (Enterprise B2B SaaS)
- **Email:** `founder@techpulse.io`
- **Password:** `password123`
- **Dashboard:** [http://localhost/start%20up%20portal/founder/dashboard.php](http://localhost/start%20up%20portal/founder/dashboard.php)
- **Full Profile View:** [http://localhost/start%20up%20portal/founder/view.php](http://localhost/start%20up%20portal/founder/view.php)

### Founder 2 (BioZenith Diagnostics)
- **Name:** Priya Patel (Co-Founder & CEO)
- **Company:** BioZenith (AI HealthTech)
- **Email:** `priya@biozenith.com`
- **Password:** `password123`
- **Dashboard:** [http://localhost/start%20up%20portal/founder/dashboard.php](http://localhost/start%20up%20portal/founder/dashboard.php)

### Founder 3 (SolarisPulse Energy)
- **Name:** Mr. Kaushik Shah (Founder & CTO)
- **Company:** SolarisPulse Energy (CleanTech & EV)
- **Email:** `rohan@solarispulse.in`
- **Password:** `password123`
- **Dashboard:** [http://localhost/start%20up%20portal/founder/dashboard.php](http://localhost/start%20up%20portal/founder/dashboard.php)
- **Profile Picture:** `uploads/avatars/kaushik_shah.png`

---

## 💼 3. Investor Accounts

### Investor 1 (Primary Demo Investor - Angel Syndicate Lead)
- **Name:** Vikramaditya Singhania
- **Type:** Angel Syndicate Lead (DeepTech / AI / FinTech)
- **Email:** `investor@venturecapital.com`
- **Password:** `password123`
- **Dashboard:** [http://localhost/start%20up%20portal/investor/discover.php](http://localhost/start%20up%20portal/investor/discover.php)
- **Full Profile View:** [http://localhost/start%20up%20portal/investor/view.php](http://localhost/start%20up%20portal/investor/view.php)

### Investor 2 (Venture Capital Partner)
- **Name:** Ananya Deshmukh
- **Type:** Venture Capital Partner (HealthTech / CleanTech / SaaS)
- **Email:** `ananya@angelsyn.io`
- **Password:** `password123`
- **Dashboard:** [http://localhost/start%20up%20portal/investor/discover.php](http://localhost/start%20up%20portal/investor/discover.php)
- **Full Profile View:** [http://localhost/start%20up%20portal/investor/view.php](http://localhost/start%20up%20portal/investor/view.php)

---

## ⚡ Quick 1-Click Login Tip
On the login screen ([http://localhost/start%20up%20portal/auth/login.php](http://localhost/start%20up%20portal/auth/login.php)), you can simply click the **"Admin"**, **"Founder"**, or **"Investor"** buttons at the bottom of the card to automatically fill the email and password!

---

## 📩 6-Digit One-Time Password (OTP) Registration Verification
Whenever a new user registers as a **Founder** or **Investor** ([http://localhost/start%20up%20portal/auth/register.php](http://localhost/start%20up%20portal/auth/register.php)):
1. Fill out the registration form (Name, Email, Mobile Number, Password, Role details).
2. A **6-digit verification code (OTP)** is automatically generated and sent to:
   - 📱 **Mobile Number via SMS** (Stored in `sms_logs` table, gateway/simulator ready)
   - ✉️ **Email Address** (Branded HTML email saved to `uploads/emails/` and database `email_logs`)
3. On the **Verification Screen** ([http://localhost/start%20up%20portal/auth/verify_otp.php](http://localhost/start%20up%20portal/auth/verify_otp.php)):
   - Check your Email Inbox or Phone SMS for the 6-digit code.
   - Type or paste the 6-digit code into the verification boxes.
   - 30-second Resend OTP countdown timer & brute-force attack protection.
4. Once verified, the account is activated (`is_verified = 1`) and the user is taken directly into their **Founder Panel** (`founder/dashboard.php`) or **Investor Panel** (`investor/dashboard.php`)!
*(Note: Standard login remains direct with email & password without 2FA prompt).*

---

## 🗄️ Database Connection Details
- **Host:** `127.0.0.1`
- **Port:** `3307`
- **Database Name:** `startup_portal`
- **User:** `root`
- **Password:** *(empty)*
- **Reset/Re-seed URL:** [http://localhost/start%20up%20portal/setup.php](http://localhost/start%20up%20portal/setup.php)
- **Security Operations Center (WAF/Firewall):** [http://localhost/start%20up%20portal/admin/security.php](http://localhost/start%20up%20portal/admin/security.php)



back up 2fa code 

709A-3BEC
16AA-19B3
6453-C596
C6D5-151E
4856-F51F