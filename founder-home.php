<?php
/**
 * ============================================================================
 * NEXORA — Dedicated Founder Home Page
 * ============================================================================
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Set active persona to founder
$_SESSION['portal_role'] = 'founder';
if (!headers_sent()) {
    setcookie('portal_role', 'founder', time() + (86400 * 30), '/');
}

// Base path
$basePath = __DIR__;
$currentRole = 'founder';

// Layout: Header
require_once $basePath . '/includes/layout/header.php';
?>

<main id="nx-page" class="nx-page relative w-full overflow-x-clip bg-[#F8FAFC] text-[#0F172A]">

    <?php
    // ------------------------------------------------------------
    // FOUNDER HOME EXPERIENCE: 4 Growth Sections
    // ------------------------------------------------------------
    // 01. Founder Hero & Live Raise Room Engine
    require_once $basePath . '/includes/founder_home/hero.php';

    // 02. The 4-Pillar Founder Growth Flywheel
    require_once $basePath . '/includes/founder_home/growth-pillars.php';

    // 03. 3-Step Journey from Pitch to Wire
    require_once $basePath . '/includes/founder_home/raise-steps.php';

    // 04. Proof Metrics & Grand Final Founder CTA
    require_once $basePath . '/includes/founder_home/perks-cta.php';
    ?>

</main>

<?php
// Layout: Footer
require_once $basePath . '/includes/layout/footer.php';
?>
