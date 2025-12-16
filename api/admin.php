<?php
// admin.php - Admin Dashboard for Hospital Profile Management
session_start();

// ===== Authentication Check =====
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: /api/login.php');
    exit;
}

// Load DB config
require_once __DIR__ . '/db_config.php';
$pgConn = get_db_connection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject', 'pending'])) {
        $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'pending');
        pg_query_params($pgConn, "UPDATE hospital_profiles SET status = $1, updated_at = NOW() WHERE id = $2", [$status, $id]);
        header('Location: /api/admin.php?msg=' . urlencode("Hospital #$id status updated to $status"));
        exit;
    }
    
    if ($action === 'delete') {
        pg_query_params($pgConn, "DELETE FROM hospital_profiles WHERE id = $1", [$id]);
        header('Location: /api/admin.php?msg=' . urlencode("Hospital #$id deleted"));
        exit;
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];
$paramCount = 0;

if ($statusFilter !== 'all') {
    $paramCount++;
    $where[] = "status = $$paramCount";
    $params[] = $statusFilter;
}

if ($search) {
    $paramCount++;
    $where[] = "(name ILIKE $$paramCount OR location->>'city' ILIKE $$paramCount)";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM hospital_profiles $whereClause ORDER BY created_at DESC";

$result = pg_query_params($pgConn, $sql, $params);
$hospitals = pg_fetch_all($result) ?: [];

// Get statistics
$statsResult = pg_query($pgConn, "
    SELECT 
        COUNT(*) as total,
        COUNT(*) FILTER (WHERE status = 'pending') as pending,
        COUNT(*) FILTER (WHERE status = 'approved') as approved,
        COUNT(*) FILTER (WHERE status = 'rejected') as rejected
    FROM hospital_profiles
");
$stats = pg_fetch_assoc($statsResult);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hospital Submissions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        .table-row { transition: all 0.2s ease; }
        .table-row:hover { background: rgba(255,255,255,0.05); }
        .btn { transition: all 0.2s ease; }
        .btn:hover { transform: scale(1.02); }
        .dropdown-content { display: none; }
        .dropdown:hover .dropdown-content { display: block; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 min-h-screen">
    <!-- Header -->
    <header class="glass sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Hospital Admin</h1>
                    <p class="text-xs text-gray-400">Manage Submissions</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-gray-400 text-sm hidden sm:inline">
                    Welcome, <span class="text-white font-medium"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                </span>
                <a href="/index_fragment.html" class="btn px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                    + New Submission
                </a>
                <a href="/api/logout.php" class="btn px-4 py-2 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <!-- Flash Message -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 bg-green-500/20 border border-green-500/30 rounded-lg text-green-400">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="?status=all" class="stat-card glass rounded-xl p-5 <?= $statusFilter === 'all' ? 'ring-2 ring-blue-500' : '' ?>">
                <p class="text-gray-400 text-sm">Total</p>
                <p class="text-3xl font-bold text-white mt-1"><?= $stats['total'] ?></p>
            </a>
            <a href="?status=pending" class="stat-card glass rounded-xl p-5 <?= $statusFilter === 'pending' ? 'ring-2 ring-yellow-500' : '' ?>">
                <p class="text-gray-400 text-sm">Pending</p>
                <p class="text-3xl font-bold text-yellow-400 mt-1"><?= $stats['pending'] ?></p>
            </a>
            <a href="?status=approved" class="stat-card glass rounded-xl p-5 <?= $statusFilter === 'approved' ? 'ring-2 ring-green-500' : '' ?>">
                <p class="text-gray-400 text-sm">Approved</p>
                <p class="text-3xl font-bold text-green-400 mt-1"><?= $stats['approved'] ?></p>
            </a>
            <a href="?status=rejected" class="stat-card glass rounded-xl p-5 <?= $statusFilter === 'rejected' ? 'ring-2 ring-red-500' : '' ?>">
                <p class="text-gray-400 text-sm">Rejected</p>
                <p class="text-3xl font-bold text-red-400 mt-1"><?= $stats['rejected'] ?></p>
            </a>
        </div>

        <!-- Search & Filter Bar -->
        <div class="glass rounded-xl p-4 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" placeholder="Search by name or city..." 
                        value="<?= htmlspecialchars($search) ?>"
                        class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                <button type="submit" class="btn px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    Search
                </button>
                <?php if ($search): ?>
                    <a href="?status=<?= htmlspecialchars($statusFilter) ?>" class="text-gray-400 hover:text-white">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Hospital Table -->
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Hospital</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (empty($hospitals)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                    No hospitals found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($hospitals as $h): ?>
                                <?php $location = json_decode($h['location'], true); ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4 text-gray-500 text-sm">#<?= $h['id'] ?></td>
                                    <td class="px-6 py-4">
                                        <p class="text-white font-medium"><?= htmlspecialchars($h['name']) ?></p>
                                        <?php if ($h['beds']): ?>
                                            <p class="text-gray-500 text-xs"><?= $h['beds'] ?> beds</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full <?= $h['type'] === 'Government' ? 'bg-blue-500/20 text-blue-400' : 'bg-purple-500/20 text-purple-400' ?>">
                                            <?= $h['type'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-300 text-sm">
                                        <?= htmlspecialchars($location['city'] ?? 'N/A') ?>
                                        <?php if (!empty($location['state'])): ?>
                                            <span class="text-gray-500">, <?= htmlspecialchars($location['state']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 text-xs font-medium rounded-full
                                            <?= $h['status'] === 'approved' ? 'bg-green-500/20 text-green-400' : 
                                               ($h['status'] === 'rejected' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400') ?>">
                                            <?= ucfirst($h['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 text-sm">
                                        <?= date('M d, Y', strtotime($h['created_at'])) ?>
                                        <br><span class="text-xs text-gray-500"><?= date('h:i A', strtotime($h['created_at'])) ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- View Details -->
                                            <a href="/api/detail.php?id=<?= $h['id'] ?>" 
                                               class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 rounded-lg transition text-sm font-medium"
                                               title="View Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span>View</span>
                                            </a>
                                            
                                            <!-- Approve Button -->
                                            <?php if ($h['status'] !== 'approved'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded-lg transition text-sm font-medium"
                                                        title="Approve this submission">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        <span>Approve</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500/30 text-green-300 rounded-lg text-sm font-medium">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                    <span>Approved</span>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Reject Button -->
                                            <?php if ($h['status'] !== 'rejected'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" 
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg transition text-sm font-medium"
                                                        title="Reject this submission">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                        <span>Reject</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500/30 text-red-300 rounded-lg text-sm font-medium">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <span>Rejected</span>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- More Actions Dropdown -->
                                            <div class="dropdown relative">
                                                <button class="p-2 text-gray-400 hover:text-white hover:bg-white/10 rounded-lg transition" title="More actions">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                    </svg>
                                                </button>
                                                <div class="dropdown-content absolute right-0 top-full mt-1 w-40 glass rounded-lg overflow-hidden z-10">
                                                    <?php if ($h['status'] !== 'pending'): ?>
                                                        <form method="POST" class="block">
                                                            <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                                            <input type="hidden" name="action" value="pending">
                                                            <button type="submit" class="w-full px-4 py-2 text-left text-yellow-400 hover:bg-white/10 text-sm flex items-center gap-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                                </svg>
                                                                Set Pending
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this hospital? This action cannot be undone.')">
                                                        <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="w-full px-4 py-2 text-left text-red-500 hover:bg-red-500/20 text-sm flex items-center gap-2">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer Stats -->
        <div class="mt-6 text-center text-gray-500 text-sm">
            Showing <?= count($hospitals) ?> of <?= $stats['total'] ?> total submissions
        </div>
    </main>
</body>
</html>
<?php pg_close($pgConn); ?>
