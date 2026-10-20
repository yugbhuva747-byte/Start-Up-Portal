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
    require_once $basePath . '/includes/investor_home/hero.php';

    // 02. Curated Dealflow & Investment Flywheel
    require_once $basePath . '/includes/investor_home/dealflow-pillars.php';

    // 03. 3-Step Journey from Accreditation to Wire
    require_once $basePath . '/includes/investor_home/investment-steps.php';

    // 04. Proof Metrics, Live Deals, FAQ & Grand Syndicate CTA
    require_once $basePath . '/includes/investor_home/syndicate-cta.php';
    ?>

</main>

<?php
// Layout: Footer
require_once $basePath . '/includes/layout/footer.php';
?>
