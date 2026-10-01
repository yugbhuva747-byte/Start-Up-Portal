# NEXORA — Complete Subscription Plans & Feature Matrix
> **Comprehensive Blueprint:** Module Access, Priority Levels, Feature Quotas, and Page Mapping for Startup Founders & Angel Investors.

---

## 📑 Table of Contents
1. [Overview & Priority Architecture](#1-overview--priority-architecture)
2. [Founder Subscription Plans Matrix](#2-founder-subscription-plans-matrix)
3. [Investor Subscription Plans Matrix](#3-investor-subscription-plans-matrix)
4. [Page & URL Mapping by Role & Plan](#4-page--url-mapping-by-role--plan)
5. [Micro-Feature Access & Quotas Matrix](#5-micro-feature-access--quotas-matrix)
6. [Database Schema & Permission Constants](#6-database-schema--permission-constants)

---

## 1. Overview & Priority Architecture

Every registered user has an active **Plan Tier** and an assigned **Priority Level (1 to 4)**. This priority level determines dealflow ranking, diligence room access, messaging limits, and badge visibility.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                       NEXORA PRIORITY LEVEL HIERARCHY                       │
├───────────────┬──────────────────┬─────────────────┬────────────────────────┤
│ Level         │ Plan Tier        │ Founder Badge   │ Investor Badge         │
├───────────────┼──────────────────┼─────────────────┼────────────────────────┤
│ Level 1 (🌱)  │ Free Trial       │ Standard Trial  │ Explorer Pass          │
│ Level 2 (⚡)  │ 1 Month Plan     │ Fast-Track Seed │ Verified Angel         │
│ Level 3 (⭐)  │ 6 Months Plan    │ Featured Deal   │ Syndicate Lead         │
│ Level 4 (👑)  │ 1 Year Plan      │ VIP Spotlight   │ Institutional Partner  │
└───────────────┴──────────────────┴─────────────────┴────────────────────────┘
```

---

## 2. Founder Subscription Plans Matrix

| Plan Tier | Price (Monthly / Annual) | Priority Level | Active Deal Rooms | Pitch Deck Listings | Cap Table & Diligence | Direct Investor DMs | Legal & Syndication |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **🌱 14-Day Free Trial** | ₹0 / 14 Days | **Level 1** | 1 Room | 1 Pitch Deck | Basic Viewer Only | ❌ Disabled | ❌ None |
| **⚡ 1 Month Sprint** | ₹2,499/mo <br> *(₹1,999/mo Annually)* | **Level 2** | 3 Rooms | 3 Pitch Decks | Interactive Cap Table + PDF Export | ✅ Direct 1-on-1 DMs | MCA KYC Badge |
| **⭐ 6 Months Dealmaker** <br> *(🔥 Most Popular)* | ₹1,666/mo (₹9,999 for 6mo) <br> *(₹1,499/mo Annually)* | **Level 3** | **Unlimited** | **Unlimited Decks** | Full Diligence Room + Access Permissions | ✅ Priority DMs + WhatsApp Intros | SEBI / MCA Legal SAFE Templates + Scout |
| **👑 1 Year Scale Pro** | ₹1,499/mo (₹17,999/yr) <br> *(₹1,249/mo Annually)* | **Level 4** | **Unlimited** | **Unlimited Decks** | White-Label LP Data Room + Audit Logs | ✅ VIP Dedicated WhatsApp Line | SPV Pooling, Syndicate Tools + REST API & Webhooks |

---

## 3. Investor Subscription Plans Matrix

| Plan Tier | Price (Monthly / Annual) | Priority Level | Dealflow Discovery | Diligence Room Access | Financials & Cap Tables | Founder Connect | Syndicate / SPV Tools |
| :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| **🌱 14-Day Explorer Pass** | ₹0 / 14 Days | **Level 1** | Public Summaries Only | ❌ Request Only | ❌ High-Level Summary | ❌ Community Only | ❌ None |
| **⚡ 1 Month Active Angel** | ₹3,499/mo <br> *(₹2,799/mo Annually)* | **Level 2** | 300+ Verified Deals | Full Pitch Decks (25/mo) | Audited MRR & Unit Economics | ✅ Direct Founder DMs | Co-Invest from ₹2 Lakhs |
| **⭐ 6 Months Syndicate Lead** <br> *(🔥 Most Popular)* | ₹2,499/mo (₹14,999 for 6mo) <br> *(₹1,999/mo Annually)* | **Level 3** | Priority Dealflow Stream | Unlimited Full Decks | Full Diligence Folders + Cap Tables | ✅ WhatsApp Founder Connect | Lead Syndicates, SPV Pooling + Carry CRM |
| **👑 1 Year Institutional** | ₹1,999/mo (₹23,999/yr) <br> *(₹1,699/mo Annually)* | **Level 4** | Instant Matching Pipeline | White-Label LP Deal Portal | Comprehensive Audited Metrics + MIS | ✅ VIP Dedicated Scout Intro | Full Platform REST API, Custom Research Reports |

---

## 4. Page & URL Mapping by Role & Plan

### 🚀 Founder Portal Pages (`/founder/`)

| Page File | URL Route | Module / Feature Description | Minimum Plan Required |
| :--- | :--- | :--- | :---: |
| `founder/dashboard.php` | `/founder/dashboard` | Main Founder Dashboard (Analytics, Pipeline Stats, Profile Overview) | **Free Trial (Level 1)** |
| `founder/company.php` | `/founder/company` | Company Profile, Pitch, Team, Logo, Video Links & Basic Metrics | **Free Trial (Level 1)** |
| `founder/profile.php` | `/founder/profile` | Personal Founder Profile, Bio, LinkedIn, Contact Details | **Free Trial (Level 1)** |
| `founder/view.php` | `/founder/view` | Public Company Showcase & Live Pitch Deck Preview | **Free Trial (Level 1)** |
| `founder/funding_rounds.php` | `/founder/funding_rounds` | Create, Edit & Launch Funding Rounds (Seed, Pre-Series A) | **1 Month Plan (Level 2)** |
| `founder/verification.php` | `/founder/verification` | DigiLocker KYC Verification & CIN/GST Badge Verification | **1 Month Plan (Level 2)** |
| `founder/messages.php` | `/founder/messages` | Direct 1-on-1 Messaging with Verified Angels & VCs | **1 Month Plan (Level 2)** |
| `founder/cap_table.php` | `/founder/cap_table` | Cap Table Modeling, Equity Simulation & Shareholder Register | **6 Months Plan (Level 3)** |
| `founder/updates.php` | `/founder/updates` | Investor Monthly Updates, Burn Rate & Runway Broadcasts | **6 Months Plan (Level 3)** |
| `founder/blogs.php` | `/founder/blogs` | Thought Leadership & PR Articles in Platform Academy | **6 Months Plan (Level 3)** |

---

### 💼 Investor Portal Pages (`/investor/`)

| Page File | URL Route | Module / Feature Description | Minimum Plan Required |
| :--- | :--- | :--- | :---: |
| `investor/dashboard.php` | `/investor/dashboard` | Investor Dashboard (Active Deals, Portfolio Overview, Allocation Stats) | **Free Trial (Level 1)** |
| `investor/profile.php` | `/investor/profile` | Investor Profile, Investment Thesis, Preferred Sectors & Ticket Size | **Free Trial (Level 1)** |
| `investor/view.php` | `/investor/view` | Public Investor Profile & Syndicate Overview | **Free Trial (Level 1)** |
| `investor/discover.php` | `/investor/discover` | Discover Curated Startups with Sector, Stage & ARR Filters | **Free Trial (Level 1)** |
| `investor/startup_detail.php` | `/investor/startup_detail` | In-Depth Startup Dossier (Financials, Traction, Pitch Decks) | **1 Month Plan (Level 2)** |
| `investor/messages.php` | `/investor/messages` | Direct Chat with Startup Founders & Co-Investors | **1 Month Plan (Level 2)** |
| `investor/verification.php` | `/investor/verification` | Accredited Investor KYC, SEBI Status & PAN Verification | **1 Month Plan (Level 2)** |
| `investor/invest.php` | `/investor/invest` | Commit Capital, E-Sign SAFE Notes & Process Escrow Deposits | **6 Months Plan (Level 3)** |
| `investor/portfolio.php` | `/investor/portfolio` | Real-Time Portfolio Tracking, Multiple on Invested Capital (MOIC), IRR | **6 Months Plan (Level 3)** |
| `investor/watchlist.php` | `/investor/watchlist` | Saved Deals, Notifications & Funding Round Closing Alerts | **6 Months Plan (Level 3)** |

---

## 5. Micro-Feature Access & Quotas Matrix

### Founder Micro-Features

| Micro-Feature | Free Trial (Level 1) | 1 Month (Level 2) | 6 Months (Level 3) | 1 Year Pro (Level 4) |
| :--- | :---: | :---: | :---: | :---: |
| **Pitch Deck Uploads** | 1 Deck (Max 10MB) | 3 Decks (Max 25MB) | Unlimited (Max 100MB) | Unlimited + Video Pitch |
| **Dealflow Placement Rank** | Standard Listing | Fast-Track Boost (+25%) | **⭐ Top Featured (+100%)** | **👑 #1 VIP Spotlight (+300%)** |
| **Active Funding Rounds** | 1 Round (Draft/Review) | 2 Active Rounds | Unlimited Active Rounds | Unlimited + Multi-Currency |
| **Real-time Deck View Analytics** | Basic View Count | Page-by-Page Time Spent | Full Investor Engagement Heatmap | Full Export + Webhooks |
| **Cap Table Export** | ❌ None | PDF Summary | CSV, Excel & Cap Table PDF | Legal Auditor Ready + ESOP Pool |
| **SAFE Legal Note Templates** | ❌ None | Standard Template | MCA & SEBI Compliant SAFE | Custom Legal Lawyer Reviewed |
| **WhatsApp Deal Warm Intros** | ❌ None | ❌ None | 5 Warm Intros / Month | Unlimited Dedicated Intros |
| **Dedicated Scout / Director** | ❌ None | ❌ None | Dedicated Scout Manager | Executive Partner Director |
| **REST API & Webhooks Access** | ❌ None | ❌ None | ❌ None | ✅ Full Platform REST API |

---

### Investor Micro-Features

| Micro-Feature | Free Trial (Level 1) | 1 Month (Level 2) | 6 Months (Level 3) | 1 Year Pro (Level 4) |
| :--- | :---: | :---: | :---: | :---: |
| **Pitch Deck Downloads** | ❌ View Summary Only | 25 Full Decks / Mo | Unlimited Deck Downloads | Unlimited + Raw Data Rooms |
| **Audited MRR & Financials** | ❌ Summary Range | ✅ Full Breakdown | ✅ Full Diligence Folder | ✅ Full Audited Reports + MIS |
| **Founder Direct Messaging** | ❌ Read-Only | ✅ 30 Direct Chats / Mo | Unlimited Direct Chats | Unlimited + WhatsApp Connect |
| **Syndicate & SPV Pooling** | ❌ None | ❌ None | ✅ Lead Syndicates & SPVs | ✅ White-Label Syndicate Portal |
| **Priority Allocation in Hot Deals**| ❌ Standard Queue | Standard Queue | **⭐ Priority Allocation** | **👑 Guaranteed Allocation** |
| **Automated Carry & Tax Reports** | ❌ None | ❌ None | ✅ Automated Carry CRM | ✅ Full GST & Tax Reports |
| **Custom Research Reports** | ❌ None | ❌ None | ❌ None | ✅ Bespoke Sector Research |

---

## 6. Database Schema & Permission Constants

### Database Relational Schema
```sql
-- Subscriptions Record Table
CREATE TABLE `subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `plan_code` ENUM('free_trial', '1_month', '6_months', '1_year') NOT NULL DEFAULT 'free_trial',
    `plan_name` VARCHAR(150) NOT NULL,
    `billing_cycle` ENUM('monthly', 'annually', 'trial') NOT NULL DEFAULT 'monthly',
    `role` ENUM('founder', 'investor') NOT NULL DEFAULT 'founder',
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `priority_level` INT NOT NULL DEFAULT 1, -- 1=Basic, 2=FastTrack, 3=Featured, 4=VIP Spotlight
    `status` ENUM('active', 'expired', 'cancelled', 'trial') NOT NULL DEFAULT 'active',
    `payment_method` VARCHAR(100) DEFAULT 'Card / UPI / NetBanking',
    `payment_status` ENUM('completed', 'trial', 'pending') DEFAULT 'completed',
    `transaction_ref` VARCHAR(100) NULL,
    `starts_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- Users Priority Reference
ALTER TABLE `users` ADD COLUMN `current_plan` VARCHAR(50) DEFAULT 'free_trial';
ALTER TABLE `users` ADD COLUMN `priority_level` INT DEFAULT 1;
ALTER TABLE `users` ADD COLUMN `plan_expires_at` DATETIME NULL;

-- Companies Dealflow Ranking
ALTER TABLE `companies` ADD COLUMN `priority_level` INT DEFAULT 1;
```

---

## 7. How to Check Plan & Priority in Code

```php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/plan_permissions.php';

$user = current_user();

// Check if user has minimum required priority level (e.g., Level 3 for Featured Tools)
if (!has_minimum_priority(3)) {
    // Show upgrade modal / notice
    require_upgrade_prompt('6_months', 'Featured Dealmaker Priority Required');
    exit;
}

// Check specific module access
if (!can_access_feature('cap_table_export', $user)) {
    echo "Feature requires 1 Month or higher plan.";
}
```
