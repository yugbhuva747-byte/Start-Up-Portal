<?php
/**
 * Authentication: Multi-Role Registration
 * Clean White Theme, Small Crisp Typography, Professional Look
 */
require_once __DIR__ . '/../config.php';

if (auth_check()) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = '';
$selectedRole = $_GET['role'] ?? 'founder';
if (!in_array($selectedRole, ['founder', 'investor'])) {
    $selectedRole = 'founder';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirmation'] ?? '';
        $role = $_POST['role'] ?? 'founder';
        $city = trim($_POST['city'] ?? 'Bengaluru');
        $country = trim($_POST['country'] ?? 'India');
        $acceptTerms = isset($_POST['terms']);

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all mandatory fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Passwords do not match.';
        } elseif (!$acceptTerms) {
            $error = 'You must agree to the Terms of Use and Risk Disclosures.';
        } else {
            $db = get_db();
            if ($db) {
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'An account with this email address already exists. Please sign in.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("
                        INSERT INTO users (name, email, phone, password_hash, role, status, is_verified, city, country, created_at)
                        VALUES (?, ?, ?, ?, ?, 'active', 0, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $email, $phone, $passwordHash, $role, $city, $country]);
                    $userId = (int)$db->lastInsertId();

                    if ($role === 'founder') {
                        $fpStmt = $db->prepare("INSERT INTO founder_profiles (user_id, designation) VALUES (?, 'Founder & CEO')");
                        $fpStmt->execute([$userId]);
                    } elseif ($role === 'investor') {
                        $ipStmt = $db->prepare("INSERT INTO investor_profiles (user_id, investor_type, risk_disclosure_accepted) VALUES (?, 'Angel Investor', 1)");
                        $ipStmt->execute([$userId]);
                        
                        $prefStmt = $db->prepare("INSERT INTO investor_preferences (user_id) VALUES (?)");
                        $prefStmt->execute([$userId]);
                    }

                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_name'] = $name;

                    log_audit($userId, 'USER_REGISTERED', 'users', $userId, "User registered as {$role}");
                    header('Location: ' . url('auth/onboarding.php'));
                    exit;
                }
            } else {
                $error = 'Database connection error. Please run setup.php.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #FAFAFB;
            color: #0F172A;
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 min-h-screen flex items-center justify-center p-4 selection:bg-indigo-100 selection:text-indigo-900">

    <div class="max-w-md w-full my-8" id="register-container">
        
        <!-- Header Logo -->
        <div class="text-center mb-6">
            <a href="<?= url('index.php') ?>" class="inline-flex items-center space-x-2 group">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition">
                    <i data-lucide="zap" class="w-4 h-4"></i>
                </div>
                <div class="text-left">
                    <span class="text-sm font-black tracking-tight text-slate-900 flex items-center gap-1">
                        STARTUP <span class="text-indigo-600">×</span> INVESTOR
                    </span>
                    <span class="block text-[8px] tracking-widest text-slate-400 uppercase font-bold">Registration</span>
                </div>
            </a>
        </div>

        <!-- White Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
            
            <div class="mb-5 text-center">
                <h1 class="text-lg font-bold tracking-tight text-slate-900">Create Your Account</h1>
                <p class="text-[11px] text-slate-500 mt-0.5">Select your role to start fundraising or investing</p>
            </div>

            <!-- Role Selector -->
            <div class="grid grid-cols-2 gap-2 mb-5 p-1 bg-slate-100 rounded-xl">
                <button type="button" onclick="selectRole('founder')" id="role-btn-founder" 
                        class="py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1.5 transition <?= $selectedRole === 'founder' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">
                    <i data-lucide="rocket" class="w-3.5 h-3.5"></i>
                    <span>Founder</span>
                </button>
                <button type="button" onclick="selectRole('investor')" id="role-btn-investor" 
                        class="py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1.5 transition <?= $selectedRole === 'investor' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' ?>">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                    <span>Investor</span>
                </button>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= url('auth/register.php') ?>" method="POST" class="space-y-3">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="role" id="form-role" value="<?= htmlspecialchars($selectedRole) ?>">

                <div>
                    <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Aarav Sharma"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Work Email *</label>
                        <input type="email" name="email" required placeholder="name@domain.com"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Phone</label>
                        <input type="tel" name="phone" placeholder="+91 9876543210"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Password *</label>
                        <input type="password" name="password" required placeholder="Min 6 chars"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Confirm *</label>
                        <input type="password" name="password_confirmation" required placeholder="Repeat"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">City</label>
                        <input type="text" name="city" value="Bengaluru" required
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-xs text-slate-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-[10.5px] font-bold uppercase tracking-wider text-slate-600 mb-1">Country</label>
                        <input type="text" name="country" value="India" required
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 focus:bg-white rounded-lg text-xs text-slate-900 outline-none">
                    </div>
                </div>

                <div class="pt-1">
                    <label class="flex items-start space-x-2 cursor-pointer">
                        <input type="checkbox" name="terms" required class="mt-0.5 w-3.5 h-3.5 text-indigo-600 rounded border-slate-300">
                        <span class="text-[10px] text-slate-500 leading-tight">
                            I agree to the Terms of Use and Risk Disclosures.
                        </span>
                    </label>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5 mt-2">
                    <span>Complete Registration</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </button>
            </form>

            <div class="mt-4 pt-3 border-t border-slate-100 text-center text-[11px] text-slate-500">
                Already registered? 
                <a href="<?= url('auth/login.php') ?>" class="text-indigo-600 hover:text-indigo-700 font-bold ml-0.5">
                    Sign In
                </a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function selectRole(role) {
            document.getElementById('form-role').value = role;
            const f = document.getElementById('role-btn-founder');
            const i = document.getElementById('role-btn-investor');
            if (role === 'founder') {
                f.className = "py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1.5 transition bg-white text-indigo-600 shadow-sm";
                i.className = "py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1.5 transition text-slate-500 hover:text-slate-800";
            } else {
                i.className = "py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1.5 transition bg-white text-indigo-600 shadow-sm";
                f.className = "py-2 px-3 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1.5 transition text-slate-500 hover:text-slate-800";
            }
        }
    </script>
</body>
</html>
