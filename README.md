# 🚀 Startup × Investor Portal

A comprehensive, full-featured web platform connecting high-growth startups with venture capitalists, angel syndicates, and investors. Built with PHP, MySQL, Tailwind CSS, and ultra-smooth Lenis interactions.

---

## 🌟 Key Features

### 🏢 Founder Module
- **Interactive Founder Dashboard**: Real-time KPI cards (Funds Raised, Active Rounds, Investor Views, Blog Posts, Pending Docs).
- **Company Profile Management**: Company story, mission, stage, sector, pitch deck, website, and team size.
- **Dynamic Blog & Announcements**: Publish updates, insights, and media with photo uploads and rich cards.
- **Funding Rounds Management**: Create rounds, specify valuation, target amount, minimum ticket, and track round commitments.
- **Direct Deal Messaging**: Real-time 1-on-1 chat with accredited investors.
- **Compliance & Verification**: Upload KYC documents, CIN certificates, pitch decks, and financial models.

### 💼 Investor Module
- **Discovery & Watchlist**: Filter startups by sector, funding stage, target amount, and compliance status.
- **Comprehensive Startup View**: Key metrics, pitch deck viewer, founder details, and company timeline.
- **Investment Flow**: Structured investment proposal workflow with cheque size, equity expectation, and status tracking.
- **Advanced Portfolio Dashboard**: 
  - Total deployed capital, active stakes, portfolio valuations, IRR tracking.
  - Sector allocation breakdown with dynamic visual distribution.
  - Interactive investment timeline and cap table overview.
- **Investor Verification**: Tiered accreditation verification process.

### 🛡️ Admin & Compliance Module
- **Executive Oversight**: Real-time platform-wide stats, active deals, transactions, and user counts.
- **Verification Queue**: Review, approve, or reject startup documents and identity checks with download & review capability.
- **Reports & Analytics**: Comprehensive platform analytics, top performing startups, transaction history, and compliance metrics.
- **Audit Logs & Security**: Detailed audit trail of platform events, role-based access matrix, and security monitor.
- **Settings Management**: Dynamic sector/category management and platform configuration.

### 🔒 Core Platform Features
- **Role-Based Access Control**: Strict access controls for Founders, Investors, and Admins.
- **Secure Password Recovery**: Token-based forgot/reset password system with expiration and password strength checker.
- **Ultra-Smooth UX**: Lenis smooth scroll engine with micro-animations and responsive mobile-first UI.

---

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL 8.x / MariaDB (PDO with Prepared Statements)
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Animations & Scrolling**: Lenis Smooth Scroll, Feather/Lucide Icons
- **Server**: Apache (XAMPP / WAMP / Native)

---

## 🚀 Getting Started

### 1. Prerequisites
- XAMPP / WAMP / LAMP installed with PHP 8+ and MySQL.
- Apache `mod_rewrite` enabled.

### 2. Installation
1. Clone the repository into your web root (e.g. `c:/xampp/htdocs/start up portal`):
   ```bash
   git clone https://github.com/yugbhuva747-byte/Start-Up-Portal.git "start up portal"
   ```
2. Copy `.env.example` to `.env` and configure your database credentials:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=startup_portal
   DB_USER=root
   DB_PASS=
   APP_URL=http://localhost/start%20up%20portal
   ```
3. Start Apache and MySQL from the XAMPP Control Panel.
4. Run the automated database setup by navigating to:
   ```
   http://localhost/start%20up%20portal/setup.php
   ```
   This will automatically create all tables, seed sample startups, founders, investors, documents, and admin accounts.

---

## 🔑 Demo Credentials

All test accounts use the password: **`password123`**

| Role | Email | Password |
|---|---|---|
| **Admin / Compliance** | `admin@portal.com` | `password123` |
| **Founder (TechPulse AI)** | `founder@techpulse.io` | `password123` |
| **Founder (BioZenith)** | `priya@biozenith.com` | `password123` |
| **Investor (Angel Syndicate)** | `investor@venturecapital.com` | `password123` |
| **Investor (VC Partner)** | `ananya@angelsyn.io` | `password123` |

---

## 📄 License
This project is open source and available under the [MIT License](LICENSE).
