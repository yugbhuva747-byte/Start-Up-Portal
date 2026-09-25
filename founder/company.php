<?php
/**
 * Founder Module: Company Master Profile & Data Room
 */
require_once __DIR__ . '/../config.php';
$user = require_auth('founder');
$db = get_db();
$pageTitle = 'Startup & Company Master Profile';

$company = null;
$documents = [];
$error = '';
$flash = get_flash();

if ($db) {
    // 1. Get Company
    $cStmt = $db->prepare("
        SELECT c.* FROM companies c
        JOIN company_founders cf ON c.id = cf.company_id
        WHERE cf.user_id = ? LIMIT 1
    ");
    $cStmt->execute([$user['id']]);
    $company = $cStmt->fetch();

    if ($company) {
        $dStmt = $db->prepare("SELECT * FROM company_documents WHERE company_id = ? ORDER BY uploaded_at DESC");
        $dStmt->execute([$company['id']]);
        $documents = $dStmt->fetchAll();
    }
}

// Handle Update Company POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security token invalid.';
    } else {
        $action = $_POST['form_action'] ?? 'update_company';

        if ($action === 'update_company') {
            $name = trim($_POST['name'] ?? '');
            $legalName = trim($_POST['legal_name'] ?? '');
            $cin = strtoupper(trim($_POST['cin_number'] ?? ''));
            $industry = trim($_POST['industry'] ?? 'AI/SaaS');
            $stage = trim($_POST['stage'] ?? 'Seed');
            $businessModel = trim($_POST['business_model'] ?? 'B2B SaaS');
            $pitch = trim($_POST['pitch'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $targetMarket = trim($_POST['target_market'] ?? '');
            $employeeCount = (int)($_POST['employee_count'] ?? 10);
            $website = trim($_POST['website'] ?? '');
            $city = trim($_POST['city'] ?? 'Bengaluru');
            $state = trim($_POST['state'] ?? 'Karnataka');
            $country = trim($_POST['country'] ?? 'India');

            if ($company) {
                $uStmt = $db->prepare("
                    UPDATE companies SET name = ?, legal_name = ?, cin_number = ?, industry = ?, stage = ?,
                    business_model = ?, pitch = ?, description = ?, target_market = ?, employee_count = ?,
                    website = ?, city = ?, state = ?, country = ?
                    WHERE id = ?
                ");
                $uStmt->execute([$name, $legalName, $cin, $industry, $stage, $businessModel, $pitch, $description, $targetMarket, $employeeCount, $website, $city, $state, $country, $company['id']]);
                
                log_audit($user['id'], 'UPDATE_COMPANY_PROFILE', 'companies', $company['id'], 'Founder updated company profile');
                set_flash('success', 'Company profile updated successfully.');
            } else {
                $iStmt = $db->prepare("
                    INSERT INTO companies (name, legal_name, cin_number, industry, stage, business_model, pitch, description, target_market, employee_count, website, city, state, country, verified_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $iStmt->execute([$name, $legalName, $cin, $industry, $stage, $businessModel, $pitch, $description, $targetMarket, $employeeCount, $website, $city, $state, $country]);
                $newCompId = $db->lastInsertId();

                $cfStmt = $db->prepare("INSERT INTO company_founders (company_id, user_id, designation, is_signatory) VALUES (?, ?, 'Founder & CEO', 1)");
                $cfStmt->execute([$newCompId, $user['id']]);

                log_audit($user['id'], 'CREATE_COMPANY', 'companies', $newCompId, 'Founder created new company master record');
                set_flash('success', 'Company record created successfully.');
            }
            header('Location: ' . url('founder/company.php'));
            exit;

        } elseif ($action === 'upload_doc' && $company) {
            $docType = trim($_POST['document_type'] ?? 'Pitch Deck');
            $docTitle = trim($_POST['title'] ?? 'Company Document');
            $accessLevel = $_POST['access_level'] ?? 'registered_investors';

            $filePath = 'uploads/documents/sample_doc.pdf';
            $fileSizeStr = '1.8 MB';

            if (!empty($_FILES['doc_file']['name'])) {
                $file = $_FILES['doc_file'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                    if (in_array($ext, $allowed) && $file['size'] <= 25 * 1024 * 1024) {
                        $targetDir = ROOT_PATH . '/uploads/documents';
                        if (!is_dir($targetDir)) @mkdir($targetDir, 0777, true);

                        $filename = 'comp_' . $company['id'] . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
                            $filePath = 'uploads/documents/' . $filename;
                            $fileSizeStr = round($file['size'] / (1024 * 1024), 2) . ' MB';
                        }
                    }
                }
            }

            $insDoc = $db->prepare("
                INSERT INTO company_documents (company_id, document_type, title, file_path, file_size, access_level, is_verified)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $insDoc->execute([$company['id'], $docType, $docTitle, $filePath, $fileSizeStr, $accessLevel]);
            $compDocId = $db->lastInsertId();

            // Also register in verification_documents for Admin review queue
            $db->prepare("
                INSERT INTO verification_documents (user_id, company_id, document_type, file_path, file_size, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ")->execute([$user['id'], $company['id'], $docType, $filePath, $fileSizeStr]);

            log_audit($user['id'], 'UPLOAD_DOCUMENT', 'company_documents', $compDocId, "Uploaded company document: $docTitle ($docType)");
            send_notification(1, 'New Company Document Uploaded', "Startup {$company['name']} uploaded {$docTitle} ({$docType}) for compliance review.", 'info', 'admin/verification_queue.php');
            set_flash('success', 'Document uploaded successfully and queued for compliance review.');
            header('Location: ' . url('founder/company.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile & Data Room • <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-clean {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03);
        }
    </style>
</head>
<body class="bg-[#FAFAFB] text-slate-900 flex min-h-screen">
    
    <!-- Founder Sidebar -->
    <?php include __DIR__ . '/../includes/founder/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <?php include __DIR__ . '/../includes/founder/navbar.php'; ?>

        <main class="p-3.5 sm:p-6 md:p-8 space-y-6 max-w-5xl w-full mx-auto" id="company-main">
            
            <?php if ($flash): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold border <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?> flex items-center space-x-2">
                    <i data-lucide="check-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center space-x-2">
                    <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Header Info -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Startup Company Profile</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Structured master profile and verified credentials presented to investors in discovery.</p>
                </div>
                <div>
                    <?= $company ? render_status_badge($company['verified_status']) : '<span class="text-[11px] font-semibold text-slate-400 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-full">Not Initialized</span>' ?>
                </div>
            </div>

            <!-- Form -->
            <div class="card-clean rounded-2xl p-6">
                <form action="<?= url('founder/company.php') ?>" method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="form_action" value="update_company">

                    <div class="space-y-4">
                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 pb-2.5">
                            <i data-lucide="building" class="w-3.5 h-3.5"></i>
                            <span>1. Legal & Corporate Identity</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Startup Brand Name *</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($company['name'] ?? '') ?>" placeholder="e.g. TechPulse AI"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Registered Legal Name</label>
                                <input type="text" name="legal_name" value="<?= htmlspecialchars($company['legal_name'] ?? '') ?>" placeholder="e.g. TechPulse Intelligence Pvt Ltd"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Corporate CIN Number *</label>
                                <input type="text" name="cin_number" required value="<?= htmlspecialchars($company['cin_number'] ?? '') ?>" placeholder="U72900KA2023PTC123456"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 uppercase outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Company Website</label>
                                <input type="url" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>" placeholder="https://example.com"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <h2 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 pb-2.5 pt-4">
                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                            <span>2. Industry, Stage & Market</span>
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Industry / Sector</label>
                                <select name="industry" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <?php 
                                    $industries = ['AI/SaaS', 'FinTech', 'HealthTech', 'CleanTech', 'DeepTech', 'E-Commerce', 'EdTech', 'AgriTech'];
                                    foreach ($industries as $ind): ?>
                                        <option value="<?= $ind ?>" <?= ($company['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= $ind ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Startup Stage</label>
                                <select name="stage" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                                    <?php 
                                    $stages = ['Idea / MVP', 'Pre-Seed', 'Seed', 'Pre-Series A', 'Series A', 'Growth'];
                                    foreach ($stages as $stg): ?>
                                        <option value="<?= $stg ?>" <?= ($company['stage'] ?? '') === $stg ? 'selected' : '' ?>><?= $stg ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Business Model</label>
                                <input type="text" name="business_model" value="<?= htmlspecialchars($company['business_model'] ?? 'B2B SaaS') ?>" placeholder="e.g. B2B Subscription"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">One-Line Elevator Pitch *</label>
                            <input type="text" name="pitch" required value="<?= htmlspecialchars($company['pitch'] ?? '') ?>" placeholder="e.g. Autonomous AI copilot for contract analysis in BFSI"
                                   class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Detailed Business Overview & Traction</label>
                            <textarea name="description" rows="4" placeholder="Detailed problem statement, unique proprietary moat, customer metrics..."
                                      class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Headquarters City</label>
                                <input type="text" name="city" value="<?= htmlspecialchars($company['city'] ?? 'Bengaluru') ?>"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">State</label>
                                <input type="text" name="state" value="<?= htmlspecialchars($company['state'] ?? 'Karnataka') ?>"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1 uppercase tracking-wider">Team Size</label>
                                <input type="number" name="employee_count" value="<?= htmlspecialchars($company['employee_count'] ?? 10) ?>"
                                       class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-indigo-600 rounded-lg text-xs text-slate-900 outline-none transition">
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg shadow-sm transition flex items-center space-x-1.5">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Data Room & Company Documents Section -->
            <?php if ($company): ?>
                <div class="card-clean rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center space-x-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-600"></i>
                                <span>Confidential Data Room & Documents</span>
                            </h2>
                            <p class="text-[11px] text-slate-500 mt-0.5">Documents are securely gated and accessible only to registered & verified investors.</p>
                        </div>
                        <button onclick="document.getElementById('upload-modal').classList.remove('hidden')" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition flex items-center space-x-1">
                            <i data-lucide="upload" class="w-3 h-3"></i>
                            <span>Add Document</span>
                        </button>
                    </div>

                    <div class="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                        <?php if (empty($documents)): ?>
                            <div class="p-8 text-center text-xs text-slate-400 bg-slate-50/50">
                                No data room documents attached yet. Add your Pitch Deck and Cap Table to improve investor engagement.
                            </div>
                        <?php else: ?>
                            <?php foreach ($documents as $doc): ?>
                                <div class="p-3.5 flex items-center justify-between text-xs bg-white hover:bg-slate-50/80 transition">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                            <i data-lucide="file" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($doc['title']) ?></div>
                                            <div class="text-[10px] text-slate-500"><?= htmlspecialchars($doc['document_type']) ?> • <?= htmlspecialchars($doc['file_size']) ?></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 font-medium border border-slate-200">
                                            <?= htmlspecialchars(str_replace('_', ' ', $doc['access_level'])) ?>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border <?= $doc['is_verified'] ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?>">
                                            <?= $doc['is_verified'] ? 'Verified' : 'Pending Review' ?>
                                        </span>
                                        <a href="<?= url($doc['file_path']) ?>" target="_blank" class="px-2 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="View Document in New Tab">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>View</span>
                                        </a>
                                        <a href="<?= url('download.php?id=' . $doc['id'] . '&type=company') ?>" class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold flex items-center space-x-1 transition shadow-sm" title="Download Document File">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>Download</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Simple Upload Modal -->
                <div id="upload-modal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                    <div class="bg-white border border-slate-200 shadow-xl max-w-md w-full rounded-2xl p-6 relative">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Add Data Room Document</h3>
                            <button onclick="document.getElementById('upload-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <form action="<?= url('founder/company.php') ?>" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="form_action" value="upload_doc">

                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Document Category</label>
                                <select name="document_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                                    <option value="Pitch Deck">Pitch Deck (PDF / PPT)</option>
                                    <option value="Certificate of Incorporation">Certificate of Incorporation</option>
                                    <option value="Financial Projections">5-Year Financial Model</option>
                                    <option value="Cap Table">Cap Table & Equity Breakdown</option>
                                    <option value="GST Certificate">GST Certificate</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Document Display Title</label>
                                <input type="text" name="title" required placeholder="e.g. Q1 2026 Seed Investor Presentation"
                                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Choose File (PDF, DOCX, PPT, JPG, PNG)</label>
                                <input type="file" name="doc_file" required accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png"
                                       class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none text-xs">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1 uppercase tracking-wider">Access Privacy</label>
                                <select name="access_level" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 outline-none focus:bg-white focus:border-indigo-600 text-xs">
                                    <option value="registered_investors">Registered & Verified Investors Only</option>
                                    <option value="request_only">Gated (Request Required)</option>
                                    <option value="public">Public</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition text-xs">
                                Attach & Submit Document
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        lucide.createIcons();
        gsap.from("#company-main", { duration: 0.4, y: 10, opacity: 0, ease: "power2.out" });
    </script>
</body>
</html>
