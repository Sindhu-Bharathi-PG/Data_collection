<?php
// detail.php - View detailed hospital profile
session_start();

// ===== Authentication Check =====
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Load DB config
require_once 'db_config.php';
$pgConn = get_db_connection();

// Get hospital ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: admin.php');
    exit;
}

// Fetch hospital
$result = pg_query_params($pgConn, "SELECT * FROM hospital_profiles WHERE id = $1", [$id]);
$hospital = pg_fetch_assoc($result);

if (!$hospital) {
    header('Location: admin.php?msg=' . urlencode('Hospital not found'));
    exit;
}

// Decode JSON fields
$location = json_decode($hospital['location'], true) ?: [];
$contact = json_decode($hospital['contact'], true) ?: [];
$description = json_decode($hospital['description'], true) ?: [];
$patient_count = json_decode($hospital['patient_count'], true) ?: [];
$accreditations = json_decode($hospital['accreditations'], true) ?: [];
$departments = json_decode($hospital['departments'], true) ?: [];
$specialties = json_decode($hospital['specialties'], true) ?: [];
$equipment = json_decode($hospital['equipment'], true) ?: [];
$facilities = json_decode($hospital['facilities'], true) ?: [];
$doctors = json_decode($hospital['doctors'], true) ?: [];
$treatments = json_decode($hospital['treatments'], true) ?: [];
$packages = json_decode($hospital['packages'], true) ?: [];
$photos = json_decode($hospital['photos'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hospital['name']) ?> - Hospital Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 min-h-screen">
    <!-- Header -->
    <header class="glass sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-6 py-4 flex justify-between items-center">
            <a href="admin.php" class="flex items-center gap-2 text-gray-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
            <span class="px-4 py-1 rounded-full text-sm font-medium
                <?= $hospital['status'] === 'approved' ? 'bg-green-500/20 text-green-400' : 
                   ($hospital['status'] === 'rejected' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400') ?>">
                <?= ucfirst($hospital['status']) ?>
            </span>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-8">
        <!-- Hospital Header -->
        <div class="glass rounded-2xl p-8 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2"><?= htmlspecialchars($hospital['name']) ?></h1>
                    <div class="flex flex-wrap gap-3 text-gray-400">
                        <span class="flex items-center gap-1">
                            <span class="px-2 py-0.5 rounded text-xs <?= $hospital['type'] === 'Government' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400' ?>">
                                <?= $hospital['type'] ?>
                            </span>
                        </span>
                        <?php if ($hospital['establishment_year']): ?>
                            <span>Est. <?= $hospital['establishment_year'] ?></span>
                        <?php endif; ?>
                        <?php if ($hospital['beds']): ?>
                            <span><?= $hospital['beds'] ?> beds</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <p>ID: #<?= $hospital['id'] ?></p>
                    <p>Submitted: <?= date('M d, Y h:i A', strtotime($hospital['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Location & Contact -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Location & Contact
                </h2>
                <div class="space-y-3 text-gray-300">
                    <?php if (!empty($location['address'])): ?>
                        <p><span class="text-gray-500">Address:</span> <?= htmlspecialchars($location['address']) ?></p>
                    <?php endif; ?>
                    <p><span class="text-gray-500">City:</span> <?= htmlspecialchars($location['city'] ?? 'N/A') ?></p>
                    <?php if (!empty($location['state'])): ?>
                        <p><span class="text-gray-500">State:</span> <?= htmlspecialchars($location['state']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($location['lat']) && !empty($location['lng'])): ?>
                        <p><span class="text-gray-500">Coordinates:</span> <?= $location['lat'] ?>, <?= $location['lng'] ?></p>
                    <?php endif; ?>
                    <hr class="border-white/10 my-4">
                    <?php if (!empty($contact['general'])): ?>
                        <p><span class="text-gray-500">Phone:</span> <?= htmlspecialchars($contact['general']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contact['emergency'])): ?>
                        <p><span class="text-gray-500">Emergency:</span> <?= htmlspecialchars($contact['emergency']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contact['email'])): ?>
                        <p><span class="text-gray-500">Email:</span> <?= htmlspecialchars($contact['email']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($contact['website'])): ?>
                        <p><span class="text-gray-500">Website:</span> <a href="<?= htmlspecialchars($contact['website']) ?>" target="_blank" class="text-blue-400 hover:underline"><?= htmlspecialchars($contact['website']) ?></a></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Description
                </h2>
                <div class="space-y-3 text-gray-300">
                    <?php if (!empty($description['brief'])): ?>
                        <p class="text-white"><?= nl2br(htmlspecialchars($description['brief'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($description['detailed'])): ?>
                        <p class="text-sm"><?= nl2br(htmlspecialchars($description['detailed'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($description['highlights'])): ?>
                        <div class="mt-4">
                            <p class="text-gray-500 text-sm mb-2">Highlights:</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($description['highlights'] as $h): ?>
                                    <span class="px-2 py-1 bg-white/10 rounded text-xs"><?= htmlspecialchars($h) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accreditations & Facilities -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Accreditations & Facilities
                </h2>
                <div class="space-y-4">
                    <?php if (!empty($accreditations)): ?>
                        <div>
                            <p class="text-gray-500 text-sm mb-2">Accreditations:</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($accreditations as $a): ?>
                                    <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded text-xs"><?= htmlspecialchars($a) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($facilities)): ?>
                        <div>
                            <p class="text-gray-500 text-sm mb-2">Facilities:</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($facilities as $f): ?>
                                    <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded text-xs"><?= htmlspecialchars($f) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Patient Stats -->
            <div class="glass rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Statistics
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php if (!empty($patient_count['total'])): ?>
                        <div class="bg-white/5 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-white"><?= number_format($patient_count['total']) ?></p>
                            <p class="text-gray-500 text-sm">Total Patients</p>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($patient_count['annual'])): ?>
                        <div class="bg-white/5 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-white"><?= number_format($patient_count['annual']) ?></p>
                            <p class="text-gray-500 text-sm">Annual Patients</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Doctors Section -->
        <?php if (!empty($doctors)): ?>
        <div class="glass rounded-xl p-6 mt-6">
            <h2 class="text-lg font-semibold text-white mb-4">Doctors (<?= count($doctors) ?>)</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($doctors as $doc): ?>
                    <div class="bg-white/5 rounded-lg p-4 flex gap-4 items-start">
                        <div class="w-16 h-16 bg-white/10 rounded-full flex-shrink-0 overflow-hidden">
                            <?php if (!empty($doc['photoUrl'])): ?>
                                <img src="<?= htmlspecialchars($doc['photoUrl']) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-full h-full text-gray-400 p-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="font-medium text-white"><?= htmlspecialchars($doc['name'] ?? 'N/A') ?></p>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($doc['specialty'] ?? '') ?></p>
                            <?php if (!empty($doc['experience'])): ?>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($doc['experience']) ?> years exp.</p>
                            <?php endif; ?>
                            <?php if (!empty($doc['qualification'])): ?>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($doc['qualification']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Treatments Section -->
        <?php if (!empty($treatments)): ?>
        <div class="glass rounded-xl p-6 mt-6">
            <h2 class="text-lg font-semibold text-white mb-4">Treatments (<?= count($treatments) ?>)</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($treatments as $t): ?>
                    <div class="bg-white/5 rounded-lg p-4">
                        <p class="font-medium text-white"><?= htmlspecialchars($t['name'] ?? 'N/A') ?></p>
                        <?php if (!empty($t['category'])): ?>
                            <p class="text-sm text-gray-400"><?= htmlspecialchars($t['category']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($t['price'])): ?>
                            <p class="text-green-400 text-sm mt-1">₹<?= number_format($t['price']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Packages Section -->
        <?php if (!empty($packages)): ?>
        <div class="glass rounded-xl p-6 mt-6">
            <h2 class="text-lg font-semibold text-white mb-4">Packages (<?= count($packages) ?>)</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <?php foreach ($packages as $p): ?>
                    <div class="bg-white/5 rounded-lg p-4">
                        <p class="font-medium text-white"><?= htmlspecialchars($p['name'] ?? 'N/A') ?></p>
                        <?php if (!empty($p['description'])): ?>
                            <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($p['description']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($p['price'])): ?>
                            <p class="text-green-400 font-semibold mt-2">₹<?= number_format($p['price']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Photos Section -->
        <?php if (!empty($photos)): ?>
        <div class="glass rounded-xl p-6 mt-6">
            <h2 class="text-lg font-semibold text-white mb-4">Photos (<?= count($photos) ?>)</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($photos as $photo): ?>
                    <a href="<?= htmlspecialchars($photo['url'] ?? $photo) ?>" target="_blank" class="block aspect-square bg-white/10 rounded-lg overflow-hidden">
                        <img src="<?= htmlspecialchars($photo['url'] ?? $photo) ?>" alt="Hospital Photo" class="w-full h-full object-cover">
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Raw JSON Data -->
        <div class="glass rounded-xl p-6 mt-6">
            <details>
                <summary class="text-gray-400 cursor-pointer hover:text-white transition">View Raw JSON Data</summary>
                <pre class="mt-4 p-4 bg-black/30 rounded-lg overflow-x-auto text-xs text-gray-300"><?= htmlspecialchars(json_encode($hospital, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </details>
        </div>
    </main>
</body>
</html>
<?php pg_close($pgConn); ?>
