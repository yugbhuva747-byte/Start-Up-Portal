<?php
/**
 * ============================================================================
 * includes/plan_permissions.php
 * ----------------------------------------------------------------------------
 * NEXORA Subscription Plan Access Control & Feature Permission Engine
 * Programmatic access checkers for modules, micro-features, and priority levels.
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Global Feature & Module Access Matrix
 */
const NEXORA_FEATURE_PERMISSIONS = [
    // --------------------------------------------------------
    // Founder Features & Modules
    // --------------------------------------------------------
    'founder_dashboard' => [
        'min_priority' => 1,
        'plans' => ['free_trial', '1_month', '6_months', '1_year'],
        'page' => 'founder/dashboard.php',
        'title' => 'Founder Dashboard'
    ],
    'company_profile' => [
        'min_priority' => 1,
        'plans' => ['free_trial', '1_month', '6_months', '1_year'],
        'page' => 'founder/company.php',
        'title' => 'Company Profile'
    ],
    'pitch_deck_basic' => [
        'min_priority' => 1,
        'plans' => ['free_trial', '1_month', '6_months', '1_year'],
        'page' => 'founder/company.php',
        'title' => 'Single Pitch Deck Listing'
    ],
    'funding_rounds_create' => [
        'min_priority' => 2,
        'plans' => ['1_month', '6_months', '1_year'],
        'page' => 'founder/funding_rounds.php',
        'title' => 'Create & Launch Funding Rounds'
    ],
    'founder_direct_messaging' => [
        'min_priority' => 2,
        'plans' => ['1_month', '6_months', '1_year'],
        'page' => 'founder/messages.php',
        'title' => 'Direct Investor 1-on-1 Messaging'
    ],
    'digilocker_kyc' => [
        'min_priority' => 2,
        'plans' => ['1_month', '6_months', '1_year'],
        'page' => 'founder/verification.php',
        'title' => 'DigiLocker & MCA Verification'
    ],
    'cap_table_management' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'founder/cap_table.php',
        'title' => 'Cap Table Modeling & Shareholder Register'
    ],
    'investor_updates_broadcast' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'founder/updates.php',
        'title' => 'Monthly Investor KPI Updates'
    ],
    'legal_safe_templates' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'founder/funding_rounds.php',
        'title' => 'SEBI & MCA Compliant SAFE Note Generator'
    ],
    'spv_syndication_founder' => [
        'min_priority' => 4,
        'plans' => ['1_year'],
        'page' => 'founder/funding_rounds.php',
        'title' => 'SPV Pooling & Syndicate Lead Tools'
    ],
    'founder_rest_api' => [
        'min_priority' => 4,
        'plans' => ['1_year'],
        'page' => 'api/',
        'title' => 'Platform REST API & Webhooks'
    ],

    // --------------------------------------------------------
    // Investor Features & Modules
    // --------------------------------------------------------
    'investor_dashboard' => [
        'min_priority' => 1,
        'plans' => ['free_trial', '1_month', '6_months', '1_year'],
        'page' => 'investor/dashboard.php',
        'title' => 'Investor Dashboard'
    ],
    'dealflow_browse' => [
        'min_priority' => 1,
        'plans' => ['free_trial', '1_month', '6_months', '1_year'],
        'page' => 'investor/discover.php',
        'title' => 'Browse Dealflow Summaries'
    ],
    'diligence_dossier_full' => [
        'min_priority' => 2,
        'plans' => ['1_month', '6_months', '1_year'],
        'page' => 'investor/startup_detail.php',
        'title' => 'Full Pitch Decks & MRR Financials'
    ],
    'investor_direct_messaging' => [
        'min_priority' => 2,
        'plans' => ['1_month', '6_months', '1_year'],
        'page' => 'investor/messages.php',
        'title' => 'Direct Founder 1-on-1 Chat'
    ],
    'commit_capital_invest' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'investor/invest.php',
        'title' => 'Commit Capital & E-Sign SAFE Notes'
    ],
    'portfolio_tracking' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'investor/portfolio.php',
        'title' => 'Real-Time Portfolio MOIC & IRR Tracking'
    ],
    'lead_syndicates_spv' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'investor/invest.php',
        'title' => 'Lead Syndicates & SPV Co-Investment Pooling'
    ],
    'priority_round_allocation' => [
        'min_priority' => 3,
        'plans' => ['6_months', '1_year'],
        'page' => 'investor/discover.php',
        'title' => 'Priority Allocation in Over-Subscribed Rounds'
    ],
    'white_label_lp_portal' => [
        'min_priority' => 4,
        'plans' => ['1_year'],
        'page' => 'investor/dashboard.php',
        'title' => 'White-Label LP Deal Portal'
    ],
    'investor_rest_api' => [
        'min_priority' => 4,
        'plans' => ['1_year'],
        'page' => 'api/',
        'title' => 'Dealflow REST API & Data Pipeline'
    ]
];

/**
 * Check if the currently logged in user (or provided user) can access a specific feature
 */
function can_access_feature(string $featureKey, ?array $user = null): bool {
    if (!$user) {
        if (!function_exists('auth_check') || !auth_check()) return false;
        $user = current_user();
    }
    if (!$user) return false;

    // Admins always have access to everything
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }

    $feature = NEXORA_FEATURE_PERMISSIONS[$featureKey] ?? null;
    if (!$feature) {
        return true; // Unrestricted feature
    }

    $userPriority = (int)($user['priority_level'] ?? 1);
    $userPlan = (string)($user['current_plan'] ?? 'free_trial');

    // Check Priority Level
    if ($userPriority < $feature['min_priority']) {
        return false;
    }

    // Check Plan Code list
    if (!empty($feature['plans']) && !in_array($userPlan, $feature['plans'], true)) {
        return false;
    }

    return true;
}

/**
 * Check if the user has at least a specific priority level (1 to 4)
 */
function has_minimum_priority(int $requiredLevel, ?array $user = null): bool {
    if (!$user) {
        if (!function_exists('auth_check') || !auth_check()) return false;
        $user = current_user();
    }
    if (!$user) return false;

    if (($user['role'] ?? '') === 'admin') return true;

    $userPriority = (int)($user['priority_level'] ?? 1);
    return $userPriority >= $requiredLevel;
}

/**
 * Render a beautiful Upgrade Gate Notice if the user does not have permission
 */
function render_upgrade_gate(string $requiredPlanName, int $requiredLevel, string $featureName = 'This feature'): void {
    $planDetails = get_plan_details($requiredPlanName);
    ?>
    <div class="my-8 p-8 rounded-3xl bg-gradient-to-br from-purple-50 via-pink-50 to-amber-50 border border-purple-200 text-center max-w-[620px] mx-auto shadow-sm">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-600 text-white flex items-center justify-center text-2xl mx-auto shadow-md mb-4">
            🔒
        </div>
        <span class="inline-block px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-[11px] font-extrabold uppercase tracking-wide mb-2">
            Priority Level <?= $requiredLevel ?> Required
        </span>
        <h3 class="text-[22px] font-extrabold text-slate-900 leading-tight">
            Unlock <?= htmlspecialchars($featureName) ?>
        </h3>
        <p class="text-[14px] text-slate-600 mt-2 max-w-[480px] mx-auto leading-relaxed">
            This module is available on the <strong><?= htmlspecialchars($planDetails['name'] ?? $requiredPlanName) ?></strong> tier. Upgrade your plan to activate higher dealflow priority and unlock full access.
        </p>
        <div class="mt-6 flex items-center justify-center gap-3">
            <a href="<?= function_exists('url') ? url('index.php#nx-pricing') : 'index.php#nx-pricing' ?>" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 hover:opacity-95 text-white text-xs sm:text-sm font-bold shadow-md shadow-purple-600/25 transition flex items-center gap-1.5">
                <span>⚡ Upgrade to <?= htmlspecialchars($planDetails['name'] ?? $requiredPlanName) ?></span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>
    <?php
}
