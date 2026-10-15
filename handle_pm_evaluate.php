<?php
// handle_pm_evaluate.php — การประเมินผลงาน PM
//  - ผู้ประเมิน: กำหนดในตั้งค่า (pm_evaluators) — บันทึกได้เฉพาะผู้ใช้ที่อยู่ในรายชื่อ ชื่อผู้ประเมิน = ผู้ใช้ที่ล็อกอิน
//  - ประเมินทีละใบหรือรวมหลายใบได้ (คะแนน/ความเห็น/ลายเซ็นชุดเดียว) — เก็บผลแยกทุกใบงานใน pm_work_evaluations
@session_start();
include 'config_ctrl/connect.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');

function out($arr) { echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $code = 200) { http_response_code($code); out(['success' => false, 'error' => $msg]); }

if (empty($_SESSION['sess_user_id_es'])) fail('กรุณาเข้าสู่ระบบใหม่', 401);

$AG = (int)($_SESSION['sess_user_agency'] ?? 0);
$ME = (string)$_SESSION['sess_user_id_es'];
$LEVEL = (string)($_SESSION['sess_user_level_es'] ?? '');
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

// ตารางผู้ประเมิน (สร้างให้อัตโนมัติถ้ายังไม่มี)
mysqli_query($connect, "CREATE TABLE IF NOT EXISTS pm_evaluators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ag_id INT NOT NULL,
    user_id VARCHAR(50) NOT NULL,
    created_by VARCHAR(50) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ag_user (ag_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// super_admin ประเมินได้เสมอ (ไม่ต้องอยู่ในรายชื่อ) / ผู้ใช้อื่นต้องอยู่ในรายชื่อผู้ประเมินของหน่วยงาน
function is_evaluator($connect, $ag, $uid) {
    if (($_SESSION['sess_user_level_es'] ?? '') === 'super_admin') return true;
    $st = $connect->prepare("SELECT 1 FROM pm_evaluators WHERE ag_id = ? AND user_id = ? LIMIT 1");
    $st->bind_param('is', $ag, $uid);
    $st->execute();
    $ok = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $ok;
}

function topics($connect, $ag) {
    $st = $connect->prepare("SELECT topic_title FROM pm_feedback WHERE ag_id = ? AND status = '1' ORDER BY order_no ASC, id ASC");
    $st->bind_param('i', $ag);
    $st->execute();
    $res = $st->get_result();
    $t = [];
    while ($r = $res->fetch_assoc()) $t[] = $r['topic_title'];
    $st->close();
    return $t;
}

function save_signature($b64) {
    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $b64, $m)) return null;
    $data = base64_decode(substr($b64, strpos($b64, ',') + 1));
    if ($data === false || strlen($data) < 50) return null;
    $dir = 'uploads/pm_work_records/eval_signatures/' . date('Y/m/d') . '/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $file = $dir . uniqid('eval_sign_', true) . '_' . time() . '.' . ($m[1] === 'jpeg' ? 'jpg' : $m[1]);
    return file_put_contents($file, $data) ? $file : null;
}

if ($AG <= 0) fail('ไม่พบหน่วยงานของผู้ใช้');

// =========================================
// ตั้งค่าผู้ประเมิน (settings_pm_evaluators.php) — ผู้ดูแลระบบ
// =========================================
if ($action === 'setting_list') {
    if (!in_array($LEVEL, ['super_admin', 'admin'], true)) fail('เฉพาะผู้ดูแลระบบ', 403);
    $st = $connect->prepare("
        SELECT u.user_id, TRIM(CONCAT(u.user_name, ' ', u.user_fname)) AS name, u.user_position, u.user_level,
               (ev.id IS NOT NULL) AS is_evaluator
        FROM tb_user u
        LEFT JOIN pm_evaluators ev ON ev.ag_id = ? AND ev.user_id = u.user_id
        WHERE u.user_agency = ? OR u.user_id IN (SELECT age_user_id FROM tb_agency_employer WHERE age_ag_id = ?) OR ev.id IS NOT NULL
        ORDER BY is_evaluator DESC, u.user_name ASC");
    $ags = (string)$AG;
    $st->bind_param('isi', $AG, $ags, $AG);
    $st->execute();
    $res = $st->get_result();
    $data = [];
    while ($r = $res->fetch_assoc()) { $r['is_evaluator'] = (int)$r['is_evaluator'] === 1; $data[] = $r; }
    out(['success' => true, 'data' => $data]);
}

if ($action === 'setting_save') {
    if (!in_array($LEVEL, ['super_admin', 'admin'], true)) fail('เฉพาะผู้ดูแลระบบ', 403);
    $ids = array_values(array_unique(array_filter(array_map('strval', (array)($input['user_ids'] ?? [])), 'strlen')));
    mysqli_begin_transaction($connect);
    try {
        $st = $connect->prepare("DELETE FROM pm_evaluators WHERE ag_id = ?");
        $st->bind_param('i', $AG); $st->execute(); $st->close();
        $st = $connect->prepare("INSERT INTO pm_evaluators (ag_id, user_id, created_by) VALUES (?, ?, ?)");
        foreach ($ids as $uid) { $st->bind_param('iss', $AG, $uid, $ME); if (!$st->execute()) throw new Exception($st->error); }
        $st->close();
        mysqli_commit($connect);
        out(['success' => true, 'count' => count($ids)]);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        fail('บันทึกไม่สำเร็จ: ' . $e->getMessage());
    }
}

// =========================================
// ข้อมูลผู้ใช้ปัจจุบัน: เป็นผู้ประเมินหรือไม่ + หัวข้อประเมิน
// =========================================
if ($action === 'me') {
    $st = $connect->prepare("SELECT TRIM(CONCAT(user_name, ' ', user_fname)) n FROM tb_user WHERE user_id = ? LIMIT 1");
    $st->bind_param('s', $ME); $st->execute();
    $name = ($st->get_result()->fetch_assoc()['n'] ?? $ME);
    $st->close();
    $cnt = $connect->query("SELECT COUNT(*) c FROM pm_evaluators WHERE ag_id = $AG")->fetch_assoc()['c'];
    out(['success' => true, 'is_evaluator' => is_evaluator($connect, $AG, $ME), 'name' => $name, 'topics' => topics($connect, $AG), 'evaluator_count' => (int)$cnt]);
}

// =========================================
// แท็บประเมิน PM: กลุ่ม วัน × เช็คชีต ของใบงานที่บันทึกผลแล้ว
// =========================================
if ($action === 'groups') {
    if (!is_evaluator($connect, $AG, $ME)) fail('แท็บประเมิน PM ใช้ได้เฉพาะ super admin และผู้ประเมิน PM ของหน่วยงาน (กำหนดได้ที่ ตั้งค่าข้อมูล → ผู้ประเมิน PM)', 403);
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
    $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-t');
    $cs = trim($_GET['cs'] ?? '');
    $status = $_GET['status'] ?? 'pending';
    $sql = "SELECT e.event_date AS d, e.checksheet_id AS cs_id, MAX(c.name) AS cs, COUNT(*) AS t, SUM(e.evaluation_status = 1) AS ev
            FROM pm_plan_events e
            LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
            WHERE e.ag_id = ? AND e.status = 1 AND e.work_record_id IS NOT NULL AND e.event_date BETWEEN ? AND ?";
    $types = 'sss'; $params = [(string)$AG, $from, $to];
    if ($cs !== '') { $sql .= " AND e.checksheet_id = ?"; $types .= 's'; $params[] = $cs; }
    $sql .= " GROUP BY e.event_date, e.checksheet_id";
    if ($status === 'pending') $sql .= " HAVING t > ev";
    elseif ($status === 'done') $sql .= " HAVING ev > 0";
    $sql .= " ORDER BY e.event_date DESC, cs ASC LIMIT 500";
    $st = $connect->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result();
    $data = [];
    while ($r = $res->fetch_assoc()) { $r['t'] = (int)$r['t']; $r['ev'] = (int)$r['ev']; $data[] = $r; }
    $st->close();
    // เช็คชีตสำหรับตัวกรอง
    $cs_list = [];
    $q = $connect->query("SELECT DISTINCT c.id, c.name FROM pm_plan_events e JOIN pm_checksheets c ON c.id = e.checksheet_id WHERE e.ag_id = '$AG' AND e.status = 1 ORDER BY c.name");
    while ($q && ($r = $q->fetch_assoc())) $cs_list[] = $r;
    out(['success' => true, 'data' => $data, 'checksheets' => $cs_list, 'from' => $from, 'to' => $to]);
}

// ใบงานในกลุ่ม (หรือตามรายการ id) — ใช้ทั้งรายใบในแท็บ และหน้าประเมิน
if ($action === 'items') {
    if (!is_evaluator($connect, $AG, $ME)) fail('แท็บประเมิน PM ใช้ได้เฉพาะ super admin และผู้ประเมิน PM ของหน่วยงาน (กำหนดได้ที่ ตั้งค่าข้อมูล → ผู้ประเมิน PM)', 403);
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))));
    $sql = "SELECT e.id, e.event_date, e.evaluation_status, wr.doc_no, wr.machine_code, wr.machine_name, wr.location, wr.actual_date,
                   wr.inspector_name, wr.checksheet_name, ev.average_score, ev.evaluator_name, ev.eval_date
            FROM pm_plan_events e
            JOIN pm_work_records wr ON wr.id = e.work_record_id
            LEFT JOIN pm_work_evaluations ev ON ev.id = e.evaluation_id
            WHERE e.ag_id = ? AND e.status = 1";
    $types = 's'; $params = [(string)$AG];
    if ($ids) {
        $sql .= " AND e.id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $types .= str_repeat('i', count($ids)); $params = array_merge($params, $ids);
    } else {
        $d = $_GET['date'] ?? ''; $cs = (string)($_GET['cs'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) fail('ไม่ระบุวันที่');
        $sql .= " AND e.event_date = ? AND e.checksheet_id = ?";
        $types .= 'ss'; $params[] = $d; $params[] = $cs;
    }
    if (($_GET['pending'] ?? '') === '1') $sql .= " AND e.evaluation_status = 0";
    $sql .= " ORDER BY e.evaluation_status ASC, wr.machine_code ASC, e.id ASC LIMIT 1000";
    $st = $connect->prepare($sql);
    $st->bind_param($types, ...$params);
    $st->execute();
    $res = $st->get_result();
    $data = [];
    while ($r = $res->fetch_assoc()) $data[] = $r;
    out(['success' => true, 'data' => $data]);
}

// ผลประเมินของใบงาน (แสดงในใบงานโหมดประวัติ)
if ($action === 'get_one') {
    $id = (int)($_GET['event_id'] ?? 0);
    $st = $connect->prepare("SELECT ev.*, e.evaluation_status FROM pm_plan_events e LEFT JOIN pm_work_evaluations ev ON ev.id = e.evaluation_id WHERE e.id = ? AND e.ag_id = ?");
    $ags = (string)$AG;
    $st->bind_param('is', $id, $ags);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if (!$r) fail('ไม่พบใบงาน');
    $r['topics'] = json_decode($r['topics_json'] ?? '[]', true) ?: [];
    unset($r['topics_json']);
    out(['success' => true, 'data' => $r, 'can_evaluate' => is_evaluator($connect, $AG, $ME)]);
}

// =========================================
// บันทึกการประเมิน (ทีละไม่เกิน 100 ใบต่อครั้ง หน้าเว็บส่งเป็นชุด)
// =========================================
if ($action === 'save') {
    @set_time_limit(0);
    if (!is_evaluator($connect, $AG, $ME)) fail('คุณไม่ได้อยู่ในรายชื่อผู้ประเมิน PM ของหน่วยงานนี้ (กำหนดได้ที่ ตั้งค่าข้อมูล → การประเมินผล PM → ผู้ประเมิน)', 403);
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($input['event_ids'] ?? [])))));
    if (!$ids) fail('ไม่ได้เลือกใบงาน');
    if (count($ids) > 100) fail('ส่งได้ครั้งละไม่เกิน 100 ใบ');

    // คะแนนต้องครบทุกหัวข้อที่เปิดใช้ และอยู่ระหว่าง 1–5
    $titles = topics($connect, $AG);
    if (!$titles) fail('ยังไม่มีหัวข้อประเมิน — เพิ่มได้ที่ ตั้งค่าข้อมูล → การประเมินผล PM');
    $scores = (array)($input['scores'] ?? []);
    $topics = [];
    foreach ($titles as $t) {
        $v = (int)($scores[$t] ?? 0);
        if ($v < 1 || $v > 5) fail('ให้คะแนนให้ครบทุกหัวข้อ: ' . $t);
        $topics[] = ['title' => $t, 'score' => $v];
    }
    $avg = round(array_sum(array_column($topics, 'score')) / count($topics), 2);
    $comments = trim((string)($input['comments'] ?? ''));

    // ลายเซ็น: ชุดแรกส่งรูปมา => บันทึกครั้งเดียว ชุดต่อไปส่ง path กลับมา (ต้องอยู่ในโฟลเดอร์ลายเซ็นประเมินเท่านั้น)
    $sig = trim((string)($input['signature_path'] ?? ''));
    if ($sig !== '') {
        $base = realpath('uploads/pm_work_records/eval_signatures');
        $real = realpath($sig);
        if (!$base || !$real || strpos($real, $base) !== 0 || !is_file($real)) fail('ไฟล์ลายเซ็นไม่ถูกต้อง กรุณาเซ็นใหม่');
    } else {
        $sig = save_signature((string)($input['signature'] ?? ''));
        if (!$sig) fail('กรุณาลงลายมือชื่อผู้ประเมิน');
    }

    $st = $connect->prepare("SELECT TRIM(CONCAT(user_name, ' ', user_fname)) n FROM tb_user WHERE user_id = ? LIMIT 1");
    $st->bind_param('s', $ME); $st->execute();
    $name = ($st->get_result()->fetch_assoc()['n'] ?? $ME);
    $st->close();

    $topics_json = json_encode($topics, JSON_UNESCAPED_UNICODE);
    $today = date('Y-m-d');
    $chk = $connect->prepare("SELECT work_record_id, evaluation_status FROM pm_plan_events WHERE id = ? AND ag_id = ? AND status = 1 FOR UPDATE");
    $ins = $connect->prepare("INSERT INTO pm_work_evaluations (work_record_id, plan_event_id, evaluator_name, evaluator_signature_path, eval_date, eval_comments, average_score, topics_json, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $upd = $connect->prepare("UPDATE pm_plan_events SET evaluation_status = 1, evaluation_id = ?, evaluated_at = NOW(), updated_at = NOW() WHERE id = ?");
    $ags = (string)$AG;
    $results = [];
    foreach ($ids as $eid) {
        mysqli_begin_transaction($connect);
        try {
            $chk->bind_param('is', $eid, $ags); $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            if (!$row || !$row['work_record_id']) throw new Exception('ใบงานยังไม่ได้บันทึกผล หรือไม่อยู่ในหน่วยงานนี้');
            if ((int)$row['evaluation_status'] === 1) throw new Exception('ใบงานนี้ประเมินแล้ว');
            $wr = (int)$row['work_record_id'];
            $ins->bind_param('iissssdss', $wr, $eid, $name, $sig, $today, $comments, $avg, $topics_json, $ME);
            if (!$ins->execute()) throw new Exception($ins->errno == 1062 ? 'ใบงานนี้ประเมินแล้ว' : $ins->error);
            $evid = $connect->insert_id;
            $upd->bind_param('ii', $evid, $eid);
            if (!$upd->execute()) throw new Exception($upd->error);
            mysqli_commit($connect);
            $results[] = ['event_id' => $eid, 'success' => true];
        } catch (Exception $e) {
            mysqli_rollback($connect);
            $results[] = ['event_id' => $eid, 'success' => false, 'error' => $e->getMessage()];
        }
    }
    out(['success' => true, 'results' => $results, 'signature_path' => $sig, 'average' => $avg]);
}

fail('ไม่รู้จักคำสั่ง (action)');
