<?php
session_start();

// --- AUTH GUARD ---
// Redirect to login if assessor is not logged in
if (!isset($_SESSION['assessor_id']) || !isset($_SESSION['assessor_name'])) {
    header("Location: login.php");
    exit();
}

// --- DB CONNECTION ---
$host     = 'localhost';
$dbname   = 'internship_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --- FETCH ASSIGNED STUDENTS ---
// Join student_assessors with Student table to get full student details
// Filter by the logged-in assessor's name
$assessor_name = $_SESSION['assessor_name'];

$stmt = $pdo->prepare("
    SELECT 
        s.student_id,
        s.student_name,
        s.programme
    FROM student_assessors sa
    JOIN Student s ON sa.student_name = s.student_name
    WHERE sa.assessor_name = :assessor_name
    ORDER BY s.student_name ASC
");
$stmt->execute([':assessor_name' => $assessor_name]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students — Assessor Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0f0f13;
            --surface:   #17171e;
            --border:    #2a2a35;
            --accent:    #c8a96e;
            --accent-dim:#8a7249;
            --text:      #e8e6e0;
            --muted:     #7a7880;
            --danger:    #e05c5c;
            --tag-bg:    #1e1e2a;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOP NAV ── */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5rem;
            height: 64px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            font-family: 'DM Serif Display', serif;
            font-size: 1.2rem;
            color: var(--accent);
            letter-spacing: 0.02em;
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-user span {
            font-size: 0.85rem;
            color: var(--muted);
        }

        .nav-user strong {
            color: var(--text);
            font-weight: 500;
        }

        .btn-logout {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
            background: transparent;
            border: 1px solid var(--border);
            padding: 0.35rem 0.9rem;
            border-radius: 6px;
            cursor: pointer;
            letter-spacing: 0.03em;
            transition: color 0.2s, border-color 0.2s;
            text-decoration: none;
        }

        .btn-logout:hover {
            color: var(--danger);
            border-color: var(--danger);
        }

        /* ── MAIN ── */
        main {
            flex: 1;
            padding: 3rem 2.5rem;
            max-width: 960px;
            width: 100%;
            margin: 0 auto;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            margin-bottom: 2.5rem;
            animation: fadeUp 0.5s ease both;
        }

        .page-header h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 2.2rem;
            font-weight: 400;
            color: var(--text);
            line-height: 1.2;
        }

        .page-header h1 em {
            color: var(--accent);
            font-style: italic;
        }

        .page-header p {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--muted);
        }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.5s 0.1s ease both;
        }

        .stat-pill {
            background: var(--tag-bg);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.3rem 0.9rem;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .stat-pill strong {
            color: var(--accent);
            font-weight: 600;
        }

        /* ── SEARCH ── */
        .search-wrap {
            position: relative;
            margin-bottom: 1.5rem;
            animation: fadeUp 0.5s 0.15s ease both;
        }

        .search-wrap svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        #searchInput {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.75rem 1rem 0.75rem 2.8rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s;
        }

        #searchInput:focus {
            border-color: var(--accent-dim);
        }

        #searchInput::placeholder { color: var(--muted); }

        /* ── STUDENT TABLE ── */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            animation: fadeUp 0.5s 0.2s ease both;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
        }

        thead th {
            padding: 0.9rem 1.4rem;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
            animation: rowIn 0.4s ease both;
        }

        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: rgba(200, 169, 110, 0.04); }

        td {
            padding: 1rem 1.4rem;
            font-size: 0.88rem;
            vertical-align: middle;
        }

        /* avatar + name cell */
        .cell-name {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--tag-bg);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent);
            flex-shrink: 0;
            letter-spacing: 0.04em;
        }

        .student-name { font-weight: 500; }
        .student-id   { font-size: 0.76rem; color: var(--muted); margin-top: 1px; }

        /* programme badge */
        .badge {
            display: inline-block;
            background: var(--tag-bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.25rem 0.7rem;
            font-size: 0.78rem;
            color: var(--muted);
        }

        /* action button */
        .btn-view {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--accent);
            background: transparent;
            border: 1px solid var(--accent-dim);
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            letter-spacing: 0.02em;
            transition: background 0.2s, color 0.2s;
        }

        .btn-view:hover {
            background: var(--accent);
            color: var(--bg);
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--muted);
        }

        .empty-state svg { margin-bottom: 1rem; opacity: 0.4; }
        .empty-state p   { font-size: 0.9rem; }

        /* ── NO RESULTS (search) ── */
        #noResults {
            display: none;
            padding: 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: var(--muted);
        }

        /* ── ANIMATIONS ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes rowIn {
            from { opacity: 0; transform: translateX(-8px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* stagger rows */
        tbody tr:nth-child(1)  { animation-delay: 0.22s; }
        tbody tr:nth-child(2)  { animation-delay: 0.28s; }
        tbody tr:nth-child(3)  { animation-delay: 0.34s; }
        tbody tr:nth-child(4)  { animation-delay: 0.40s; }
        tbody tr:nth-child(5)  { animation-delay: 0.46s; }
        tbody tr:nth-child(6)  { animation-delay: 0.52s; }
        tbody tr:nth-child(n+7){ animation-delay: 0.56s; }

        /* ── RESPONSIVE ── */
        @media (max-width: 640px) {
            nav { padding: 0 1.2rem; }
            main { padding: 2rem 1.2rem; }
            .page-header h1 { font-size: 1.7rem; }
            td, thead th { padding: 0.8rem 1rem; }
            .col-programme, .col-action { display: none; }
        }
    </style>
</head>
<body>

<!-- ── NAV ── -->
<nav>
    <span class="nav-brand">Internship Portal</span>
    <div class="nav-user">
        <span>Signed in as <strong><?= htmlspecialchars($assessor_name) ?></strong></span>
        <a href="logout.php" class="btn-logout">Log out</a>
    </div>
</nav>

<!-- ── MAIN ── -->
<main>

    <div class="page-header">
        <h1>My <em>Assigned</em> Students</h1>
        <p>Students currently under your supervision this term.</p>
    </div>

    <?php if (empty($students)): ?>

        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87
                         M16 3.13a4 4 0 010 7.75M12 14a4 4 0 100-8 4 4 0 000 8z"/>
            </svg>
            <p>No students are assigned to you yet.<br>Please contact the admin if this seems incorrect.</p>
        </div>

    <?php else: ?>

        <!-- stats -->
        <div class="stats-bar">
            <div class="stat-pill">
                <strong><?= count($students) ?></strong> student<?= count($students) !== 1 ? 's' : '' ?> assigned
            </div>
        </div>

        <!-- search -->
        <div class="search-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" id="searchInput" placeholder="Search by name or programme…" autocomplete="off">
        </div>

        <!-- table -->
        <div class="table-wrap">
            <table id="studentTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th class="col-programme">Programme</th>
                        <th class="col-action">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $i => $student): ?>
                    <tr class="student-row"
                        data-name="<?= strtolower(htmlspecialchars($student['student_name'])) ?>"
                        data-programme="<?= strtolower(htmlspecialchars($student['programme'])) ?>">
                        <td style="color:var(--muted);font-size:0.8rem;"><?= $i + 1 ?></td>

                        <td>
                            <div class="cell-name">
                                <div class="avatar">
                                    <?php
                                        // initials from name
                                        $parts = explode(' ', trim($student['student_name']));
                                        echo strtoupper(substr($parts[0], 0, 1));
                                        if (count($parts) > 1) echo strtoupper(substr(end($parts), 0, 1));
                                    ?>
                                </div>
                                <div>
                                    <div class="student-name"><?= htmlspecialchars($student['student_name']) ?></div>
                                    <div class="student-id">ID: <?= htmlspecialchars($student['student_id']) ?></div>
                                </div>
                            </div>
                        </td>

                        <td class="col-programme">
                            <span class="badge"><?= htmlspecialchars($student['programme']) ?></span>
                        </td>

                        <td class="col-action">
                            <a href="student_detail.php?id=<?= $student['student_id'] ?>" class="btn-view">
                                View&nbsp;→
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div id="noResults">No students match your search.</div>
        </div>

    <?php endif; ?>
</main>

<!-- ── LIVE SEARCH ── -->
<script>
    const input = document.getElementById('searchInput');
    const noResults = document.getElementById('noResults');

    if (input) {
        input.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll('.student-row');
            let visible = 0;

            rows.forEach(row => {
                const match = !q ||
                    row.dataset.name.includes(q) ||
                    row.dataset.programme.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });

            if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
        });
    }
</script>

</body>
</html>