<?php
/**
 * ============================================================================
 * NEXORA — Dedicated Investor Home Page
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Set active persona to investor
$_SESSION['portal_role'] = 'investor';
if (!headers_sent()) {
    setcookie('portal_role', 'investor', time() + (86400 * 30), '/');
}

// Base path
$basePath = __DIR__;
$currentRole = 'investor';

// Layout: Header
require_once $basePath . '/includes/layout/header.php';
?>

<main id="nx-page" class="nx-page relative w-full overflow-x-clip bg-[#F8FAFC] text-[#0F172A]">

    <?php
    // ------------------------------------------------------------
    // INVESTOR HOME EXPERIENCE: Curated Institutional Journey
    // ------------------------------------------------------------
    // 01. Hero — Value Proposition & Live Dealroom Showcase
    require_once $basePath . '/includes/home/hero.php';

    // 02. Opportunities — Live Regional & Sector Dealflow
    require_once $basePath . '/includes/home/opportunities.php';

    // 03. Ecosystem — How It Works (Proof, Trust, Co-Investing)
    require_once $basePath . '/includes/home/ecosystem.php';

    // 04. Investor — Institutional Diligence Dossier & 3D Terminal
    require_once $basePath . '/includes/home/investors.php';

    // 05. Pricing — Institutional & Accredited Angel Subscriptions
    require_once $basePath . '/includes/home/pricing.php';
    ?>

</main>

<?php
// Layout: Footer
require_once $basePath . '/includes/layout/footer.php';
?>
