<?php
session_start();
require_once 'config.php';

// 1. SYSTEM CHECKS
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// SUPABASE CONFIG
$supabase_url = "https://kunfvqsrryhjrbzlndgi.supabase.co/rest/v1/students";
$supabase_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im9lcnZxYm12dmxzeGJydGhnYWprIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzQzNDY5MTUsImV4cCI6MjA4OTkyMjkxNX0.gE67glJsmPHw69zB1PPZdetLtrfKBy_0TGjKYTxZWq0";

// =========================================================
// 2. PRINT PDF LOGIC (COR)
// =========================================================
if (isset($_GET['print'])) {
    $student_id = $_GET['print'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if ($student) {
        $course_prefix = str_replace('BS', '', $student['course']); 
        $year_digit = substr($student['year_level'], 0, 1);
        $sem_digit = substr($student['semester'], 0, 1);
        $dynamic_section = $course_prefix . $year_digit . $sem_digit;

        $loadQuery = "SELECT s.subject_code, s.subject_name, s.units, sl.day, sl.start_time, sl.end_time, sl.room, sl.section_code 
                      FROM student_loads sl JOIN subjects s ON sl.subject_id = s.id WHERE sl.student_id = ?";
        $stmtLoad = $pdo->prepare($loadQuery);
        $stmtLoad->execute([$student['id']]);
        $enrolled_subjects = $stmtLoad->fetchAll();

        if (empty($enrolled_subjects)) {
            $defQuery = "SELECT subject_code, subject_name, units FROM subjects WHERE course = ?";
            $stmtDef = $pdo->prepare($defQuery);
            $stmtDef->execute([$student['course']]);
            $course_subjects = $stmtDef->fetchAll();
            
            $static_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($course_subjects as $index => $sub) {
                $dayIdx = $index % count($static_days);
                $enrolled_subjects[] = [
                    'section_code' => $dynamic_section,
                    'subject_code' => $sub['subject_code'],
                    'subject_name' => $sub['subject_name'],
                    'day' => $static_days[$dayIdx],
                    'start_time' => '08:00:00',
                    'end_time' => '11:00:00',
                    'room' => 'Room ' . (100 + $index),
                    'units' => $sub['units']
                ];
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8"><title>COR - <?php echo htmlspecialchars($student['last_name']); ?></title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 50px; color: #1a1a1a; max-width: 900px; margin: 0 auto; background: #f5f5f5; }
                .header { text-align: center; border-bottom: 3px solid #1a1a6e; padding-bottom: 20px; margin-bottom: 30px; }
                .header img { width: 70px; height: 70px; margin-bottom: 8px; }
                .header h2 { margin: 0; font-size: 1.8rem; color: #1a1a6e; font-weight: 700; letter-spacing: 1px; }
                .header p { margin: 5px 0 0 0; font-size: 1rem; color: #666; font-weight: 500; }
                .student-info { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .student-info div { flex: 1; }
                .student-info p { margin: 8px 0; line-height: 1.6; }
                .student-info strong { color: #1a1a6e; }
                table { width: 100%; border-collapse: collapse; font-size: 13px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
                th { background: linear-gradient(135deg, #1a1a6e 0%, #2d2da0 100%); color: white; font-weight: 700; text-transform: uppercase; font-size: 12px; }
                td { color: #333; }
                tbody tr:hover { background-color: #f9fafb; }
                .footer { margin-top: 60px; display: flex; justify-content: space-around; text-align: center; }
                .sign-line { border-top: 2px solid #1a1a1a; width: 200px; padding-top: 10px; font-weight: 600; color: #1a1a1a; font-size: 12px; }
                @media print { .no-print { display: none !important; } body { background: white; } }
            </style>
        </head>
        <body onload="window.print()">
            <div class="header">
                <img src="logo.png" alt="ACLC Logo">
                <h2>ACLC COLLEGE</h2>
                <p>CERTIFICATE OF REGISTRATION</p>
            </div>
            <div class="student-info">
                <div>
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                    <p><strong>Name:</strong> <?php echo strtoupper(htmlspecialchars($student['last_name'].', '.$student['first_name'])); ?></p>
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course']); ?></p>
                </div>
                <div>
                    <p><strong>Year/Sem:</strong> <?php echo htmlspecialchars($student['year_level'].' / '.$student['semester']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($student['student_type']); ?></p>
                    <p><strong>Section:</strong> <?php echo $dynamic_section; ?></p>
                </div>
            </div>
            <table>
                <thead><tr><th>Section</th><th>Code</th><th>Description</th><th>Day/Time</th><th>Room</th><th>Units</th></tr></thead>
                <tbody>
                    <?php $total = 0; foreach ($enrolled_subjects as $sub): $total += (float)$sub['units']; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['section_code'] == 'TBA' ? $dynamic_section : $sub['section_code']); ?></td>
                        <td><?php echo htmlspecialchars($sub['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($sub['day']).' '.date('h:i A', strtotime($sub['start_time'])).' - '.date('h:i A', strtotime($sub['end_time'])); ?></td>
                        <td><?php echo htmlspecialchars($sub['room']); ?></td>
                        <td><?php echo htmlspecialchars($sub['units']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: #eef0ff; font-weight: 700;"><td colspan="5" style="text-align:right;">Total Units</td><td><?php echo $total; ?></td></tr>
                </tbody>
            </table>
            <div class="footer"><div class="sign-line">Student Signature</div><div class="sign-line">Registrar</div></div>
            <button class="no-print" onclick="window.history.back()" style="margin-top: 30px; padding: 12px 24px; cursor: pointer; background: linear-gradient(135deg, #1a1a6e 0%, #2d2da0 100%); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px;">Back</button>
        </body></html>
        <?php exit;
    }
}

// 3. DATABASE ACTIONS
if (isset($_GET['accept'])) {
    $pdo->prepare("UPDATE students SET is_accepted = 'ACCEPTED' WHERE id = ?")->execute([$_GET['accept']]);
    $_SESSION['message'] = "Student Accepted!";
    header("Location: admindashboard.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_accept'])) {
    if (!empty($_POST['student_ids'])) {
        $ids = $_POST['student_ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE students SET is_accepted = 'ACCEPTED' WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $_SESSION['message'] = count($ids) . " Students Accepted Successfully!";
    }
    header("Location: admindashboard.php"); exit;
}

if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE students SET is_accepted = 'DELETED' WHERE id = ?")->execute([$_GET['delete']]);
    $_SESSION['message'] = "Record moved to Deleted tab.";
    header("Location: admindashboard.php"); exit;
}

if (isset($_GET['restore'])) {
    $pdo->prepare("UPDATE students SET is_accepted = 'PENDING' WHERE id = ?")->execute([$_GET['restore']]);
    $_SESSION['message'] = "Record restored.";
    header("Location: admindashboard.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $sql = "UPDATE students SET first_name=?, last_name=?, date_of_birth=?, student_type=?, course=?, year_level=?, semester=?, address=? WHERE id=?";
    $pdo->prepare($sql)->execute([$_POST['first_name'], $_POST['last_name'], $_POST['dob'], $_POST['student_type'], $_POST['course'], $_POST['year_level'], $_POST['semester'], $_POST['address'], $_POST['id']]);
    $_SESSION['message'] = "Update successful!";
    header("Location: admindashboard.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, email, phone, date_of_birth, student_type, course, year_level, semester, address, payment_status, is_accepted, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ONSITE', 'PENDING', NOW())");
    $stmt->execute([$_POST['student_id'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['dob'], $_POST['student_type'], $_POST['course'], $_POST['year_level'], $_POST['semester'], $_POST['address']]);
    $_SESSION['message'] = "New Onsite Enrollment Added!";
    header("Location: admindashboard.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_docs'])) {
    $sid    = (int)$_POST['doc_student_id'];
    $fields = ['report_card', 'tor_dismissal', 'good_moral', 'psa_birth_cert'];
    $setClauses = [];
    $params     = [];

    if (!is_dir('uploads')) { mkdir('uploads', 0775, true); }

    foreach ($fields as $field) {
        if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','gif','pdf','webp'];
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'uploads/' . $field . '_' . $sid . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $filename)) {
                    $setClauses[] = "$field = ?";
                    $params[]     = $filename;
                }
            }
        }
    }

    if (!empty($setClauses)) {
        $params[] = $sid;
        $pdo->prepare("UPDATE students SET " . implode(', ', $setClauses) . " WHERE id = ?")
            ->execute($params);
        $_SESSION['message'] = "Documents updated successfully!";
    } else {
        $_SESSION['message'] = "No new files were uploaded.";
    }
    header("Location: admindashboard.php"); exit;
}

// 4. FETCH DATA
$pending = $pdo->query("SELECT * FROM students WHERE is_accepted = 'PENDING' ORDER BY id DESC")->fetchAll();
$accepted = $pdo->query("SELECT * FROM students WHERE is_accepted = 'ACCEPTED' ORDER BY id DESC")->fetchAll();
$deleted = $pdo->query("SELECT * FROM students WHERE is_accepted = 'DELETED' ORDER BY id DESC")->fetchAll();
$docs_students = $pdo->query("SELECT id, student_id, first_name, last_name, course, year_level, report_card, tor_dismissal, good_moral, psa_birth_cert FROM students WHERE is_accepted = 'ACCEPTED' ORDER BY id DESC")->fetchAll();

$counts = [
    'total' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
    'accepted' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_accepted = 'ACCEPTED'")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_accepted = 'PENDING'")->fetchColumn(),
    'deleted' => $pdo->query("SELECT COUNT(*) FROM students WHERE is_accepted = 'DELETED'")->fetchColumn()
];

$admin_name = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACLC College - Enrollment System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #1a1a6e;
            --primary-mid: #2d2da0;
            --primary-light: #4f46e5;
            --primary-glow: #6366f1;
            --primary-bg: #eef2ff;
            --primary-bg-alt: #e0e7ff;
            --accent: #f59e0b;
            --accent-bg: #fffbeb;
            --success: #059669;
            --success-bg: #d1fae5;
            --danger: #dc2626;
            --danger-bg: #fee2e2;
            --warning: #d97706;
            --warning-bg: #fef3c7;
            --bg: #f1f5f9;
            --sidebar: #0f172a;
            --sidebar-hover: #1e293b;
            --card: #ffffff;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --text: #0f172a;
            --text-sec: #64748b;
            --text-muted: #94a3b8;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.05);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html { background: var(--bg); }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); display: flex; color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }

        /* ══════════ SIDEBAR ══════════ */
        .sidebar {
            position: fixed; width: 272px; height: 100vh; background: var(--sidebar);
            display: flex; flex-direction: column; z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-brand {
            padding: 1.75rem 1.5rem; display: flex; align-items: center; gap: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-brand img {
            width: 46px; height: 46px; border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.1); padding: 3px;
        }
        .sidebar-brand-text h2 {
            font-size: 1.05rem; font-weight: 800; color: white; letter-spacing: 0.5px; line-height: 1.2;
        }
        .sidebar-brand-text span {
            font-size: 0.7rem; color: var(--text-muted); font-weight: 500;
            text-transform: uppercase; letter-spacing: 1px;
        }

        .nav-section {
            padding: 1.25rem 0.75rem; flex: 1; overflow-y: auto;
        }
        .nav-label {
            font-size: 0.65rem; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 1.5px; padding: 0 0.75rem; margin-bottom: 0.6rem; margin-top: 1rem;
        }
        .nav-label:first-child { margin-top: 0; }

        .nav-item {
            display: flex; align-items: center; padding: 0.75rem 1rem; color: #94a3b8; text-decoration: none; cursor: pointer;
            transition: var(--transition); position: relative; font-weight: 500; font-size: 0.9rem;
            border-radius: var(--radius-sm); margin-bottom: 2px;
        }
        .nav-item:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-item.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%);
            color: white; font-weight: 600;
            box-shadow: 0 4px 12px rgba(26,26,110,0.4);
        }
        .nav-item i { width: 20px; margin-right: 12px; font-size: 0.95rem; text-align: center; }
        .nav-item .nav-badge {
            margin-left: auto; background: rgba(255,255,255,0.15); color: white;
            font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; min-width: 24px; text-align: center;
        }
        .nav-item.active .nav-badge { background: rgba(255,255,255,0.25); }

        .sidebar-footer {
            padding: 1.25rem; border-top: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 0.5rem 0;
        }
        .sidebar-user-avatar {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-glow));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.85rem;
        }
        .sidebar-user-info { flex: 1; }
        .sidebar-user-name { color: white; font-weight: 600; font-size: 0.85rem; }
        .sidebar-user-role { color: var(--text-muted); font-size: 0.7rem; font-weight: 500; }

        /* ══════════ MAIN CONTENT ══════════ */
        .main { margin-left: 272px; width: calc(100% - 272px); min-height: 100vh; }

        .topbar {
            background: var(--card); padding: 1.25rem 2rem; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 90;
        }
        .topbar-left h1 { font-size: 1.4rem; font-weight: 800; color: var(--text); }
        .topbar-left p { font-size: 0.8rem; color: var(--text-sec); margin-top: 2px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }

        .content { padding: 2rem; max-width: 1440px; margin: 0 auto; }

        /* ══════════ STAT CARDS ══════════ */
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card {
            background: var(--card); padding: 1.5rem; border-radius: var(--radius);
            box-shadow: var(--shadow); border: 1px solid var(--border);
            transition: var(--transition); position: relative; overflow: hidden;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
        }
        .stat-card.blue::after { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
        .stat-card.green::after { background: linear-gradient(90deg, #059669, #10b981); }
        .stat-card.amber::after { background: linear-gradient(90deg, #d97706, #f59e0b); }
        .stat-card.red::after { background: linear-gradient(90deg, #dc2626, #ef4444); }

        .stat-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }
        .stat-card.blue .stat-icon { background: var(--primary-bg); color: var(--primary-light); }
        .stat-card.green .stat-icon { background: var(--success-bg); color: var(--success); }
        .stat-card.amber .stat-icon { background: var(--warning-bg); color: var(--warning); }
        .stat-card.red .stat-icon { background: var(--danger-bg); color: var(--danger); }

        .stat-value { font-size: 2.25rem; font-weight: 900; color: var(--text); line-height: 1; }
        .stat-label { font-size: 0.8rem; color: var(--text-sec); font-weight: 600; margin-top: 0.35rem; text-transform: uppercase; letter-spacing: 0.3px; }

        /* ══════════ CARDS ══════════ */
        .card {
            background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow);
            border: 1px solid var(--border); margin-bottom: 1.5rem; overflow: hidden;
        }
        .card-head {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-head-title {
            font-weight: 700; font-size: 1rem; color: var(--text);
            display: flex; align-items: center; gap: 10px;
        }
        .card-head-title i { color: var(--primary-light); font-size: 1.1rem; }
        .card-head-badge {
            font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px;
        }

        .bulk-bar {
            padding: 1rem 1.5rem; background: var(--border-light); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .bulk-bar label { font-size: 0.85rem; font-weight: 600; color: var(--text); }

        /* ══════════ TABLES ══════════ */
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--border-light); }
        th {
            padding: 0.85rem 1.25rem; text-align: left; font-size: 0.7rem; text-transform: uppercase;
            font-weight: 700; color: var(--text-sec); border-bottom: 1px solid var(--border); letter-spacing: 0.5px;
        }
        td {
            padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; color: var(--text);
        }
        tbody tr { transition: var(--transition); }
        tbody tr:hover { background: #f8fafc; }
        tbody tr:last-child td { border-bottom: none; }

        .student-name { font-weight: 600; color: var(--text); }
        .student-id { font-weight: 700; color: var(--primary-light); font-size: 0.85rem; font-family: 'Courier New', monospace; }
        .student-meta { font-size: 0.8rem; color: var(--text-sec); margin-top: 2px; }

        /* ══════════ BUTTONS ══════════ */
        .btn {
            padding: 0.55rem 1.1rem; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; border: none;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none; color: white;
            transition: var(--transition); font-size: 0.8rem; font-family: inherit;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
        .btn:active { transform: translateY(0); }
        .btn-primary { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-mid) 100%); }
        .btn-primary:hover { background: linear-gradient(135deg, var(--primary-mid) 0%, var(--primary-light) 100%); }
        .btn-success { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .btn-danger { background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); }
        .btn-warning { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
        .btn-outline {
            background: transparent; border: 1.5px solid var(--border); color: var(--text-sec);
        }
        .btn-outline:hover { border-color: var(--primary-light); color: var(--primary-light); background: var(--primary-bg); box-shadow: none; }
        .btn-sm { padding: 0.45rem 0.85rem; font-size: 0.75rem; border-radius: 8px; }
        .btn-lg { padding: 0.75rem 1.5rem; font-size: 0.9rem; }
        .btn-block { width: 100%; justify-content: center; }

        .action-group { display: flex; gap: 6px; flex-wrap: wrap; }

        /* ══════════ MODALS ══════════ */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6);
            z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(4px);
        }
        .modal.active { display: flex; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: var(--card); border-radius: 20px; width: 92%; max-width: 580px; padding: 0;
            max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-header {
            padding: 1.5rem 1.75rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-header h2 {
            font-size: 1.2rem; font-weight: 700; color: var(--text);
            display: flex; align-items: center; gap: 10px;
        }
        .modal-header h2 i { color: var(--primary-light); }
        .modal-close {
            width: 36px; height: 36px; border-radius: 10px; border: 1px solid var(--border);
            background: var(--border-light); display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--text-sec); transition: var(--transition); font-size: 1rem;
        }
        .modal-close:hover { background: var(--danger-bg); color: var(--danger); border-color: transparent; }

        .modal-body { padding: 1.75rem; }

        label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text); margin-bottom: 6px; }
        .form-input {
            width: 100%; padding: 0.7rem 0.9rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            margin-bottom: 1.1rem; font-family: inherit; font-size: 0.9rem; transition: var(--transition);
            background: var(--card); color: var(--text); outline: none;
        }
        .form-input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px var(--primary-bg); }
        .form-input:disabled { background: var(--border-light); color: var(--text-sec); }
        select.form-input { cursor: pointer; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-row > div { margin-bottom: 0; }

        .modal-footer {
            padding: 1.25rem 1.75rem; border-top: 1px solid var(--border); background: var(--border-light);
            display: flex; gap: 10px; border-radius: 0 0 20px 20px;
        }

        /* ══════════ ALERT ══════════ */
        .alert-bar {
            background: var(--success-bg); color: #065f46; padding: 1rem 1.5rem; border-radius: var(--radius-sm);
            margin-bottom: 1.5rem; display: flex; gap: 10px; align-items: center;
            border: 1px solid #a7f3d0; font-weight: 500; font-size: 0.9rem;
        }
        .alert-bar i { font-size: 1.1rem; color: var(--success); }

        /* ══════════ TABS ══════════ */
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeInTab 0.3s ease; }
        @keyframes fadeInTab { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: var(--primary-light); border-radius: 4px; }

        /* ══════════ DOCUMENTS TAB ══════════ */
        .doc-search {
            display: flex; gap: 12px; margin-bottom: 1.5rem;
        }
        .doc-search input {
            flex: 1; padding: 0.75rem 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm);
            font-family: inherit; font-size: 0.9rem; transition: var(--transition); outline: none; background: var(--card);
        }
        .doc-search input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px var(--primary-bg); }

        .doc-card {
            background: var(--card); border-radius: var(--radius); padding: 1.5rem;
            box-shadow: var(--shadow); border: 1px solid var(--border); margin-bottom: 1.25rem;
            transition: var(--transition);
        }
        .doc-card:hover { box-shadow: var(--shadow-md); }
        .doc-card-top {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);
        }
        .doc-card-name { font-size: 1rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; }
        .doc-card-name i { color: var(--primary-light); }
        .doc-card-sub { font-size: 0.78rem; color: var(--text-sec); margin-top: 3px; }
        .doc-card-badge {
            font-size: 0.72rem; font-weight: 700; padding: 4px 10px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .badge-complete { background: var(--success-bg); color: var(--success); }
        .badge-incomplete { background: var(--warning-bg); color: var(--warning); }

        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
        .doc-item {
            background: var(--border-light); border-radius: 12px; padding: 1rem; text-align: center;
            border: 1.5px solid var(--border); transition: var(--transition);
        }
        .doc-item.uploaded { border-color: #a7f3d0; background: #f0fdf4; }
        .doc-item-label {
            font-size: 0.68rem; font-weight: 700; color: var(--text-sec); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 8px;
        }
        .doc-item img {
            width: 100%; height: 110px; object-fit: cover; border-radius: 8px;
            cursor: pointer; transition: var(--transition); border: 2px solid transparent;
        }
        .doc-item img:hover { border-color: var(--primary-light); transform: scale(1.02); }
        .doc-item .doc-placeholder {
            height: 110px; display: flex; align-items: center; justify-content: center;
            color: #cbd5e1; font-size: 2rem;
        }
        .doc-item .doc-status {
            margin-top: 8px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 20px;
        }
        .doc-status.done { background: var(--success-bg); color: var(--success); }
        .doc-status.pending { background: var(--warning-bg); color: var(--warning); }

        .doc-progress { margin-top: 4px; }
        .doc-progress-track { height: 5px; background: #e5e7eb; border-radius: 3px; overflow: hidden; }
        .doc-progress-bar { height: 5px; background: linear-gradient(90deg, var(--primary), var(--primary-light)); border-radius: 3px; transition: width 0.4s ease; }

        /* ══════════ IMAGE VIEWER ══════════ */
        .viewer {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15,23,42,0.9); z-index: 3000; justify-content: center; align-items: center;
            backdrop-filter: blur(8px); flex-direction: column;
        }
        .viewer.active { display: flex; animation: fadeIn 0.25s ease; }
        .viewer img { max-width: 90%; max-height: 78vh; border-radius: var(--radius); box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .viewer-title {
            color: white; font-size: 0.9rem; font-weight: 600; margin-bottom: 16px;
            background: rgba(255,255,255,0.1); padding: 8px 20px; border-radius: 20px;
        }
        .viewer-close {
            position: absolute; top: 24px; right: 32px; color: white; font-size: 1.5rem;
            cursor: pointer; background: rgba(255,255,255,0.1); width: 44px; height: 44px;
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            transition: var(--transition); border: 1px solid rgba(255,255,255,0.15);
        }
        .viewer-close:hover { background: rgba(255,255,255,0.2); }

        /* ══════════ EMPTY STATE ══════════ */
        .empty-state {
            padding: 3.5rem 2rem; text-align: center;
        }
        .empty-state i { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; }
        .empty-state p { color: var(--text-sec); font-size: 0.95rem; max-width: 400px; margin: 0 auto; line-height: 1.6; }

        /* ══════════ HAMBURGER MENU ══════════ */
        .hamburger {
            display: none; background: none; border: none; font-size: 1.4rem; color: var(--text);
            cursor: pointer; padding: 6px; border-radius: 8px; transition: var(--transition);
        }
        .hamburger:hover { background: var(--border-light); }
        .sidebar-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15,23,42,0.5); z-index: 99; backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { display: block; }

        /* ══════════ TABLE WRAPPER ══════════ */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-wrap table { min-width: 560px; }

        /* ══════════ RESPONSIVE ══════════ */
        @media (max-width: 1024px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .action-group { gap: 4px; }
            .btn-sm { padding: 0.4rem 0.65rem; font-size: 0.7rem; }
        }
        @media (max-width: 768px) {
            .hamburger { display: block; }
            .sidebar {
                width: 272px; transform: translateX(-100%); transition: transform 0.3s ease;
                position: fixed; z-index: 100;
            }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; width: 100%; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .stat-value { font-size: 1.75rem; }
            .stat-icon { width: 40px; height: 40px; font-size: 1rem; }
            .form-row { grid-template-columns: 1fr; }
            .topbar { padding: 0.85rem 1rem; }
            .topbar-left h1 { font-size: 1.1rem; }
            .topbar-left p { font-size: 0.7rem; }
            .content { padding: 1rem; }
            td, th { padding: 0.6rem 0.75rem; font-size: 0.8rem; }
            .bulk-bar { flex-direction: column; align-items: stretch; gap: 8px; }
            .bulk-bar select.form-input { width: 100% !important; }
            .action-group { flex-direction: column; gap: 4px; }
            .btn-sm { width: 100%; justify-content: center; font-size: 0.75rem; padding: 0.5rem 0.7rem; }
            .card-head { flex-direction: column; gap: 8px; align-items: flex-start; }
            .doc-grid { grid-template-columns: 1fr 1fr; }
            .doc-card-top { flex-direction: column; gap: 10px; align-items: flex-start; }
            .doc-card-top > div:last-child { text-align: left; width: 100%; }
            .doc-progress { width: 100% !important; }
            .modal-box { width: 96%; border-radius: 16px; }
            .modal-footer { flex-direction: column; }
            .viewer img { max-width: 96%; max-height: 70vh; border-radius: 10px; }
            .viewer-close { top: 12px; right: 12px; width: 38px; height: 38px; }
        }
        @media (max-width: 480px) {
            .stats-row { grid-template-columns: 1fr; gap: 0.75rem; }
            .stat-card { padding: 1.1rem; }
            .stat-value { font-size: 1.5rem; }
            .content { padding: 0.75rem; }
            .doc-grid { grid-template-columns: 1fr; }
            .doc-item img { height: 140px; }
            .topbar-left h1 { font-size: 1rem; }
            .topbar-left h1 i { display: none; }
            .btn-outline i + span, .btn-outline span { display: inline; }
        }
    </style>
</head>
<body>

    <!-- ══════════ SIDEBAR ══════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="logo.png" alt="ACLC Logo">
            <div class="sidebar-brand-text">
                <h2>ACLC College</h2>
                <span>Enrollment System</span>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-label">Main</div>
            <a class="nav-item active" onclick="switchTab('overview', this)">
                <i class="fas fa-th-large"></i> Dashboard
            </a>

            <div class="nav-label">Enrollment</div>
            <a class="nav-item" onclick="switchTab('pending', this)">
                <i class="fas fa-clock"></i> Pending
                <?php if($counts['pending'] > 0): ?><span class="nav-badge"><?php echo $counts['pending']; ?></span><?php endif; ?>
            </a>
            <a class="nav-item" onclick="switchTab('accepted', this)">
                <i class="fas fa-user-check"></i> Accepted
                <?php if($counts['accepted'] > 0): ?><span class="nav-badge"><?php echo $counts['accepted']; ?></span><?php endif; ?>
            </a>
            <a class="nav-item" onclick="switchTab('documents', this)">
                <i class="fas fa-folder-open"></i> Documents
            </a>

            <div class="nav-label">Archive</div>
            <a class="nav-item" onclick="switchTab('deleted', this)">
                <i class="fas fa-archive"></i> Deleted
                <?php if($counts['deleted'] > 0): ?><span class="nav-badge"><?php echo $counts['deleted']; ?></span><?php endif; ?>
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="sidebar-user-role">Administrator</div>
                </div>
            </div>
            <button class="btn btn-primary btn-block" onclick="openEnrollModal()">
                <i class="fas fa-plus"></i> New Enrollment
            </button>
        </div>
    </aside>

    <!-- MOBILE OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ══════════ MAIN ══════════ -->
    <main class="main">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <div class="topbar-left">
                    <h1 id="page-title"><i class="fas fa-th-large" style="color:var(--primary-light); margin-right:8px;"></i> Dashboard</h1>
                    <p id="page-subtitle">Overview of enrollment activity</p>
                </div>
            </div>
            <div class="topbar-right">
                <a href="?logout=1" class="btn btn-outline"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-bar">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></span>
                </div>
            <?php endif; ?>

            <!-- ── DASHBOARD ── -->
            <div id="overview" class="tab-content active">
                <div class="stats-row">
                    <div class="stat-card blue">
                        <div class="stat-top">
                            <div>
                                <div class="stat-value"><?php echo $counts['total']; ?></div>
                                <div class="stat-label">Total Students</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                        </div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-top">
                            <div>
                                <div class="stat-value"><?php echo $counts['accepted']; ?></div>
                                <div class="stat-label">Accepted</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                        </div>
                    </div>
                    <div class="stat-card amber">
                        <div class="stat-top">
                            <div>
                                <div class="stat-value"><?php echo $counts['pending']; ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                        </div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-top">
                            <div>
                                <div class="stat-value"><?php echo $counts['deleted']; ?></div>
                                <div class="stat-label">Deleted</div>
                            </div>
                            <div class="stat-icon"><i class="fas fa-trash-alt"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── PENDING ── -->
            <div id="pending" class="tab-content">
                <form method="POST">
                    <div class="card">
                        <div class="card-head">
                            <div class="card-head-title"><i class="fas fa-hourglass-half"></i> Pending Applications</div>
                            <span class="card-head-badge" style="background:var(--warning-bg);color:var(--warning);"><?php echo count($pending); ?> waiting</span>
                        </div>
                        <div class="bulk-bar">
                            <label><i class="fas fa-layer-group"></i> Bulk Enroll:</label>
                            <select id="bulkSelect" class="form-input" style="width:160px;margin-bottom:0;" onchange="selectBulk(this.value)">
                                <option value="0">Select count</option>
                                <option value="5">Next 5</option>
                                <option value="10">Next 10</option>
                                <option value="20">Next 20</option>
                                <option value="all">All Pending</option>
                            </select>
                            <button type="submit" name="bulk_accept" class="btn btn-success btn-sm" onclick="return confirm('Accept selected students?')">
                                <i class="fas fa-check-double"></i> Accept Selected
                            </button>
                        </div>
                        <?php if(empty($pending)): ?>
                            <div class="empty-state"><i class="fas fa-inbox"></i><p>No pending applications right now.</p></div>
                        <?php else: ?>
                        <div class="table-wrap"><table>
                            <thead><tr><th width="40"><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th><th>Student</th><th>Course / Year</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach($pending as $s): ?>
                                <tr>
                                    <td><input type="checkbox" name="student_ids[]" value="<?php echo $s['id']; ?>" class="student-checkbox"></td>
                                    <td>
                                        <div class="student-name"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></div>
                                        <div class="student-id"><?php echo htmlspecialchars($s['student_id']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($s['course']); ?></div>
                                        <div class="student-meta"><?php echo htmlspecialchars($s['year_level']); ?></div>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="?accept=<?php echo $s['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Accept</a>
                                            <button type="button" class="btn btn-outline btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fas fa-pen"></i> Edit</button>
                                            <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Move this student to deleted?')"><i class="fas fa-trash"></i> Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table></div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- ── ACCEPTED ── -->
            <div id="accepted" class="tab-content">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-title"><i class="fas fa-user-check"></i> Enrolled Students</div>
                        <span class="card-head-badge" style="background:var(--success-bg);color:var(--success);"><?php echo count($accepted); ?> enrolled</span>
                    </div>
                    <?php if(empty($accepted)): ?>
                        <div class="empty-state"><i class="fas fa-user-plus"></i><p>No accepted students yet.</p></div>
                    <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Student</th><th>Year / Semester</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($accepted as $s): ?>
                            <tr>
                                <td>
                                    <div class="student-name"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></div>
                                    <div class="student-id"><?php echo htmlspecialchars($s['student_id']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($s['year_level']); ?></div>
                                    <div class="student-meta"><?php echo htmlspecialchars($s['semester']); ?></div>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="?print=<?php echo $s['id']; ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> COR</a>
                                        <button class="btn btn-outline btn-sm" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($s)); ?>)"><i class="fas fa-pen"></i> Edit</button>
                                        <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Move to deleted?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── DOCUMENTS ── -->
            <div id="documents" class="tab-content">
                <div class="doc-search">
                    <input type="text" id="docSearch" placeholder="Search by name or student ID..." onkeyup="filterDocs()">
                </div>

                <?php if (empty($docs_students)): ?>
                    <div class="card"><div class="empty-state"><i class="fas fa-folder-open"></i><p>No documents yet. Documents appear here once accepted students upload their requirements.</p></div></div>
                <?php else: ?>
                    <?php
                    $dk = ['report_card','tor_dismissal','good_moral','psa_birth_cert'];
                    $dl = ['report_card'=>'Report Card','tor_dismissal'=>'TOR & Dismissal','good_moral'=>'Good Moral','psa_birth_cert'=>'PSA Birth Cert'];
                    ?>
                    <?php foreach ($docs_students as $ds):
                        $up = 0; foreach ($dk as $k) { if (!empty($ds[$k])) $up++; }
                        $pct = ($up / count($dk)) * 100;
                    ?>
                    <div class="doc-card" data-name="<?php echo strtolower($ds['first_name'].' '.$ds['last_name']); ?>" data-sid="<?php echo strtolower($ds['student_id']); ?>">
                        <div class="doc-card-top">
                            <div>
                                <div class="doc-card-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($ds['first_name'].' '.$ds['last_name']); ?></div>
                                <div class="doc-card-sub"><?php echo htmlspecialchars($ds['student_id'].' · '.$ds['course'].' · '.$ds['year_level']); ?></div>
                            </div>
                            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                                <span class="doc-card-badge <?php echo $up === count($dk) ? 'badge-complete' : 'badge-incomplete'; ?>">
                                    <i class="fas <?php echo $up === count($dk) ? 'fa-check-circle' : 'fa-clock'; ?>"></i>
                                    <?php echo $up.'/'.count($dk); ?> uploaded
                                </span>
                                <div class="doc-progress" style="width:120px;">
                                    <div class="doc-progress-track"><div class="doc-progress-bar" style="width:<?php echo $pct; ?>%"></div></div>
                                </div>
                                <button type="button" class="btn btn-warning btn-sm"
                                    onclick="openDocEditModal(<?php echo $ds['id']; ?>, '<?php echo htmlspecialchars(addslashes($ds['first_name'].' '.$ds['last_name'])); ?>')">
                                    <i class="fas fa-file-pen"></i> Edit Docs
                                </button>
                            </div>
                        </div>
                        <div class="doc-grid">
                            <?php foreach ($dk as $key): ?>
                            <div class="doc-item <?php echo !empty($ds[$key]) ? 'uploaded' : ''; ?>">
                                <div class="doc-item-label"><?php echo $dl[$key]; ?></div>
                                <?php if (!empty($ds[$key])): ?>
                                    <img src="<?php echo htmlspecialchars($ds[$key]); ?>" alt="<?php echo $dl[$key]; ?>"
                                         onclick="viewImage('<?php echo htmlspecialchars($ds[$key]); ?>','<?php echo htmlspecialchars($ds['first_name'].' '.$ds['last_name'].' - '.$dl[$key]); ?>')">
                                    <span class="doc-status done"><i class="fas fa-check"></i> Uploaded</span>
                                <?php else: ?>
                                    <div class="doc-placeholder"><i class="fas fa-image"></i></div>
                                    <span class="doc-status pending"><i class="fas fa-clock"></i> Missing</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ── DELETED ── -->
            <div id="deleted" class="tab-content">
                <div class="card">
                    <div class="card-head">
                        <div class="card-head-title"><i class="fas fa-archive"></i> Archived Records</div>
                        <span class="card-head-badge" style="background:var(--danger-bg);color:var(--danger);"><?php echo count($deleted); ?> archived</span>
                    </div>
                    <?php if(empty($deleted)): ?>
                        <div class="empty-state"><i class="fas fa-archive"></i><p>No archived records.</p></div>
                    <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>Student</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($deleted as $s): ?>
                            <tr>
                                <td>
                                    <div class="student-name"><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></div>
                                    <div class="student-id"><?php echo htmlspecialchars($s['student_id']); ?></div>
                                </td>
                                <td><a href="?restore=<?php echo $s['id']; ?>" class="btn btn-success btn-sm"><i class="fas fa-redo"></i> Restore</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- ══════════ IMAGE VIEWER ══════════ -->
    <div id="imageViewer" class="viewer" onclick="closeViewer(event)">
        <button class="viewer-close" onclick="closeViewer(event)"><i class="fas fa-times"></i></button>
        <div class="viewer-title" id="viewerTitle"></div>
        <img id="viewerImg" src="" alt="Document">
    </div>

    <!-- ══════════ EDIT MODAL ══════════ -->
    <div id="editModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2><i class="fas fa-user-pen"></i> Edit Student</h2>
                <button class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_student" value="1"><input type="hidden" name="id" id="edit_id">
                    <div class="form-row">
                        <div><label>First Name</label><input type="text" name="first_name" id="edit_fn" class="form-input" required></div>
                        <div><label>Last Name</label><input type="text" name="last_name" id="edit_ln" class="form-input" required></div>
                    </div>
                    <div class="form-row">
                        <div><label>Birthday</label><input type="date" name="dob" id="edit_dob" class="form-input" required></div>
                        <div><label>Year Level</label><select name="year_level" id="edit_year" class="form-input"><option value="1st Year">1st Year</option><option value="2nd Year">2nd Year</option><option value="3rd Year">3rd Year</option><option value="4th Year">4th Year</option></select></div>
                    </div>
                    <div class="form-row">
                        <div><label>Semester</label><select name="semester" id="edit_sem" class="form-input"><option value="1st Semester">1st Semester</option><option value="2nd Semester">2nd Semester</option></select></div>
                        <div><label>Student Type</label><select name="student_type" id="edit_type" class="form-input"><option value="Regular">Regular</option><option value="Irregular">Irregular</option><option value="Tesda">Tesda</option></select></div>
                    </div>
                    <label>Course</label>
                    <select name="course" id="edit_course" class="form-input"><option value="BSIT">BSIT</option><option value="BSCS">BSCS</option><option value="BSBA">BSBA</option><option value="BSHM">BSHM</option></select>
                    <label>Address</label>
                    <textarea name="address" id="edit_address" class="form-input" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-save"></i> Save Changes</button>
                    <button type="button" class="btn btn-outline" style="flex:1" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════ ENROLL MODAL ══════════ -->
    <div id="enrollModal" class="modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> New Enrollment</h2>
                <button class="modal-close" onclick="closeModal('enrollModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="enroll" value="1">
                    <label>Student ID</label>
                    <input type="text" name="student_id" id="generated_sid" class="form-input" readonly style="background:var(--border-light);font-weight:700;font-family:'Courier New',monospace;">
                    <div class="form-row">
                        <div><label>First Name</label><input type="text" name="first_name" class="form-input" placeholder="First Name" required></div>
                        <div><label>Last Name</label><input type="text" name="last_name" class="form-input" placeholder="Last Name" required></div>
                    </div>
                    <div class="form-row">
                        <div><label>Email</label><input type="email" name="email" class="form-input" required></div>
                        <div><label>Phone</label><input type="text" name="phone" class="form-input" required></div>
                    </div>
                    <div class="form-row">
                        <div><label>Birthday</label><input type="date" name="dob" class="form-input" required></div>
                        <div><label>Year Level</label><select name="year_level" class="form-input"><option value="1st Year">1st Year</option><option value="2nd Year">2nd Year</option><option value="3rd Year">3rd Year</option><option value="4th Year">4th Year</option></select></div>
                    </div>
                    <div class="form-row">
                        <div><label>Semester</label><select name="semester" class="form-input"><option value="1st Semester">1st Semester</option><option value="2nd Semester">2nd Semester</option></select></div>
                        <div><label>Student Type</label><select name="student_type" class="form-input"><option value="Regular">Regular</option><option value="Irregular">Irregular</option><option value="Tesda">Tesda</option></select></div>
                    </div>
                    <label>Course</label>
                    <select name="course" class="form-input"><option value="BSIT">BSIT</option><option value="BSCS">BSCS</option><option value="BSBA">BSBA</option><option value="BSHM">BSHM</option></select>
                    <label>Address</label>
                    <textarea name="address" class="form-input" rows="2" placeholder="Complete Address"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" style="flex:1"><i class="fas fa-user-plus"></i> Enroll Student</button>
                    <button type="button" class="btn btn-outline" style="flex:1" onclick="closeModal('enrollModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sidebar toggle for mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }

        const tabTitles = {
            overview: { title: 'Dashboard', sub: 'Overview of enrollment activity', icon: 'fa-th-large' },
            pending: { title: 'Pending', sub: 'Applications waiting for approval', icon: 'fa-clock' },
            accepted: { title: 'Accepted', sub: 'Enrolled students list', icon: 'fa-user-check' },
            documents: { title: 'Documents', sub: 'Student submitted requirements', icon: 'fa-folder-open' },
            deleted: { title: 'Deleted', sub: 'Archived student records', icon: 'fa-archive' }
        };

        function switchTab(id, el) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            if (el) el.classList.add('active');
            const info = tabTitles[id] || {};
            document.getElementById('page-title').innerHTML = '<i class="fas ' + (info.icon||'') + '" style="color:var(--primary-light);margin-right:8px;"></i> ' + (info.title || id);
            document.getElementById('page-subtitle').innerText = info.sub || '';
            closeSidebar();
        }

        function openEnrollModal() {
            document.getElementById('generated_sid').value = 'STU' + Math.floor(100000 + Math.random() * 900000);
            document.getElementById('enrollModal').classList.add('active');
        }

        function openEditModal(s) {
            document.getElementById('edit_id').value = s.id;
            document.getElementById('edit_fn').value = s.first_name;
            document.getElementById('edit_ln').value = s.last_name;
            document.getElementById('edit_dob').value = s.date_of_birth;
            document.getElementById('edit_year').value = s.year_level;
            document.getElementById('edit_sem').value = s.semester;
            document.getElementById('edit_type').value = s.student_type;
            document.getElementById('edit_course').value = s.course;
            document.getElementById('edit_address').value = s.address;
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function toggleAll(master) {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = master.checked);
        }

        function selectBulk(val) {
            const cbs = document.querySelectorAll('.student-checkbox');
            cbs.forEach(cb => cb.checked = false);
            if (val === 'all') { cbs.forEach(cb => cb.checked = true); }
            else if (val > 0) { for (let i = 0; i < val && i < cbs.length; i++) cbs[i].checked = true; }
        }

        function viewImage(url, title) {
            document.getElementById('viewerImg').src = url;
            document.getElementById('viewerTitle').innerText = title;
            document.getElementById('imageViewer').classList.add('active');
        }

        function closeViewer(e) {
            if (e.target === document.getElementById('imageViewer') || e.target.closest('.viewer-close')) {
                document.getElementById('imageViewer').classList.remove('active');
                document.getElementById('viewerImg').src = '';
            }
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.getElementById('imageViewer').classList.remove('active');
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
            }
        });

        function openDocEditModal(id, name) {
            document.getElementById('editDocsId').value = id;
            document.getElementById('editDocsName').innerText = name;
            document.querySelectorAll('#editDocsModal input[type="file"]').forEach(i => i.value = '');
            document.getElementById('editDocsModal').classList.add('active');
        }

        function filterDocs() {
            const q = document.getElementById('docSearch').value.toLowerCase();
            document.querySelectorAll('.doc-card').forEach(c => {
                const n = c.getAttribute('data-name'), s = c.getAttribute('data-sid');
                c.style.display = (n.includes(q) || s.includes(q)) ? '' : 'none';
            });
        }
    </script>

    <!-- ══════════ EDIT DOCS MODAL ══════════ -->
    <div id="editDocsModal" class="modal">
        <div class="modal-box" style="max-width:640px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-pen"></i> Edit Documents &mdash; <span id="editDocsName" style="color:var(--primary-light);font-weight:600;"></span></h2>
                <button class="modal-close" onclick="closeModal('editDocsModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_docs" value="1">
                <input type="hidden" name="doc_student_id" id="editDocsId">
                <div class="modal-body">
                    <p style="font-size:0.82rem;color:var(--text-sec);margin-bottom:1.25rem;background:var(--primary-bg);padding:0.75rem 1rem;border-radius:var(--radius-sm);">
                        <i class="fas fa-info-circle" style="color:var(--primary-light);margin-right:6px;"></i>
                        Upload a new file to replace a document. Leave blank to keep the current file.
                        Accepted: JPG, PNG, GIF, WEBP, PDF.
                    </p>
                    <div class="form-row">
                        <div>
                            <label><i class="fas fa-file-image" style="color:var(--primary-light);margin-right:5px;"></i> Report Card</label>
                            <input type="file" name="report_card" class="form-input" accept="image/*,.pdf" style="padding:0.5rem 0.7rem;">
                        </div>
                        <div>
                            <label><i class="fas fa-file-image" style="color:var(--primary-light);margin-right:5px;"></i> TOR &amp; Dismissal</label>
                            <input type="file" name="tor_dismissal" class="form-input" accept="image/*,.pdf" style="padding:0.5rem 0.7rem;">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label><i class="fas fa-file-image" style="color:var(--primary-light);margin-right:5px;"></i> Good Moral</label>
                            <input type="file" name="good_moral" class="form-input" accept="image/*,.pdf" style="padding:0.5rem 0.7rem;">
                        </div>
                        <div>
                            <label><i class="fas fa-file-image" style="color:var(--primary-light);margin-right:5px;"></i> PSA Birth Cert</label>
                            <input type="file" name="psa_birth_cert" class="form-input" accept="image/*,.pdf" style="padding:0.5rem 0.7rem;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning" style="flex:1;"><i class="fas fa-upload"></i> Upload &amp; Save</button>
                    <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeModal('editDocsModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
