<?php
// view.php - View submitted hospital profiles

// Load DB config
require_once 'db_config.php';
$pgConn = get_db_connection();

// Fetch all hospital profiles
$result = pg_query($pgConn, "SELECT * FROM hospital_profiles ORDER BY created_at DESC");
$hospitals = pg_fetch_all($result) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Submissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">Hospital Submissions</h1>
            <a href="index.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                + New Submission
            </a>
        </div>

        <?php if (empty($hospitals)): ?>
            <div class="bg-white/10 backdrop-blur rounded-xl p-8 text-center">
                <p class="text-gray-300 text-lg">No hospital profiles submitted yet.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($hospitals as $hospital): ?>
                    <?php 
                        $location = json_decode($hospital['location'], true);
                        $contact = json_decode($hospital['contact'], true);
                    ?>
                    <div class="bg-white/10 backdrop-blur rounded-xl p-6 border border-white/20">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($hospital['name']) ?></h2>
                                <p class="text-gray-400">
                                    <?= htmlspecialchars($hospital['type']) ?> Hospital
                                    <?php if ($hospital['establishment_year']): ?>
                                        • Est. <?= $hospital['establishment_year'] ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                <?= $hospital['status'] === 'approved' ? 'bg-green-500/20 text-green-400' : 
                                   ($hospital['status'] === 'rejected' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400') ?>">
                                <?= ucfirst($hospital['status']) ?>
                            </span>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-4 text-gray-300">
                            <div>
                                <p class="text-gray-500 text-sm">Location</p>
                                <p><?= htmlspecialchars($location['city'] ?? 'N/A') ?>, <?= htmlspecialchars($location['state'] ?? '') ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Contact</p>
                                <p><?= htmlspecialchars($contact['general'] ?? $contact['email'] ?? 'N/A') ?></p>
                            </div>
                            <?php if ($hospital['beds']): ?>
                            <div>
                                <p class="text-gray-500 text-sm">Beds</p>
                                <p><?= $hospital['beds'] ?></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-gray-500 text-sm">Submitted</p>
                                <p><?= date('M d, Y - h:i A', strtotime($hospital['created_at'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-white/10">
                            <details class="text-gray-400">
                                <summary class="cursor-pointer hover:text-white transition">View Full Data</summary>
                                <pre class="mt-2 p-4 bg-black/30 rounded-lg overflow-x-auto text-xs"><?= htmlspecialchars(json_encode($hospital, JSON_PRETTY_PRINT)) ?></pre>
                            </details>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <p class="text-center text-gray-500 mt-8">
            Total: <?= count($hospitals) ?> submission(s)
        </p>
    </div>
</body>
</html>
<?php pg_close($pgConn); ?>
