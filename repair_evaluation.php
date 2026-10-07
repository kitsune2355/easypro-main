<?php
/**
 * repair_evaluation.php
 *
 * หน้าแบบประเมินงานซ่อมแบบ Enterprise
 * - GET  ?t=TOKEN : แสดงรายละเอียดใบงานและแบบประเมิน
 * - POST token, score, comment : บันทึกผลประเมิน
 *
 * หมายเหตุ:
 * - ตัดคำถาม "สรุปผลการดำเนินงาน" ออกแล้ว
 * - result จะถูกบันทึกเป็น completed อัตโนมัติ เพราะใบงานปิดงานแล้ว
 * - คะแนนใช้ 5 ระดับ: 5 ดีมาก, 4 ดี, 3 ปานกลาง, 2 พอใช้, 1 ควรปรับปรุง
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

@session_start();
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');

function ev_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ev_is_absolute_url($url): bool
{
    return (bool)preg_match('/^https?:\/\//i', (string)$url);
}

function ev_base_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return $scheme . $host;
}

function ev_thai_date($value, bool $showTime = false): string
{
    $value = trim((string)$value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return '-';
    }

    $ts = strtotime($value);
    if (!$ts) {
        return $value;
    }

    $months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    $text = (int)date('j', $ts)
        . ' ' . ($months[(int)date('n', $ts)] ?? '')
        . ' ' . ((int)date('Y', $ts) + 543);

    if ($showTime) {
        $text .= ' ' . date('H:i', $ts) . ' น.';
    }

    return $text;
}

function ev_time($value): string
{
    $value = trim((string)$value);

    if ($value === '' || $value === '00:00:00') {
        return '-';
    }

    $ts = strtotime($value);
    if (!$ts) {
        return $value;
    }

    return date('H:i', $ts) . ' น.';
}


function ev_has_value($value): bool
{
    $value = trim((string)$value);
    $lower = strtolower($value);

    return $value !== ''
        && $value !== '-'
        && $value !== '0'
        && $lower !== 'null'
        && $lower !== 'undefined';
}

function ev_datetime_text($date, $time = ''): string
{
    $dateText = ev_thai_date($date);
    $timeText = ev_time($time);

    if ($dateText === '-' && $timeText === '-') {
        return '-';
    }

    if ($dateText === '-') {
        return $timeText;
    }

    if ($timeText === '-') {
        return $dateText;
    }

    return $dateText . ' · ' . $timeText;
}

function ev_status_label($status): string
{
    $status = strtolower(trim((string)$status));

    $map = [
        'pending' => 'รอดำเนินการ',
        'inprogress' => 'กำลังดำเนินการ',
        'in_progress' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'feedback' => 'ประเมินแล้ว',
        'cancel' => 'ยกเลิก',
        'cancelled' => 'ยกเลิก',
        'canceled' => 'ยกเลิก'
    ];

    return $map[$status] ?? ($status !== '' ? $status : '-');
}

function ev_urgency_label($urgency): string
{
    $urgency = strtolower(trim((string)$urgency));

    $map = [
        'low' => 'ต่ำ',
        'medium' => 'ปานกลาง',
        'high' => 'สูง',
        'critical' => 'เร่งด่วนมาก'
    ];

    return $map[$urgency] ?? ($urgency !== '' ? $urgency : 'ไม่ระบุ');
}

function ev_score_label($score): string
{
    switch ((int)$score) {
        case 5:
            return 'ดีมาก';
        case 4:
            return 'ดี';
        case 3:
            return 'ปานกลาง';
        case 2:
            return 'พอใช้';
        case 1:
            return 'ควรปรับปรุง';
        default:
            return '-';
    }
}

function ev_message(string $title, string $message, string $type = 'info'): void
{
    $colors = [
        'info' => ['#0369a1', '#e0f2fe', '#0284c7'],
        'success' => ['#047857', '#d1fae5', '#059669'],
        'warning' => ['#b45309', '#fef3c7', '#d97706'],
        'error' => ['#be123c', '#ffe4e6', '#e11d48']
    ];

    $icons = [
        'info' => 'i',
        'success' => '✓',
        'warning' => '!',
        'error' => '×'
    ];

    $palette = $colors[$type] ?? $colors['info'];
    $icon = $icons[$type] ?? $icons['info'];
    $safeTitle = ev_h($title);
    $safeMessage = nl2br(ev_h($message));

    echo <<<HTML
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:'Kanit',sans-serif;background:#eef3f8;color:#0f172a;display:grid;place-items:center;padding:24px}
    .message-card{width:min(100%,460px);background:#fff;border:1px solid #dbe4ee;border-radius:28px;box-shadow:0 24px 70px rgba(15,23,42,.12);padding:36px;text-align:center}
    .message-icon{width:72px;height:72px;border-radius:24px;margin:0 auto;display:grid;place-items:center;font-size:34px;font-weight:800;color:{$palette[0]};background:{$palette[1]}}
    h1{margin:22px 0 8px;font-size:24px}.message{color:#64748b;font-size:14px;line-height:1.8}
    button{margin-top:26px;border:0;border-radius:14px;background:{$palette[2]};color:#fff;font:600 14px 'Kanit';padding:12px 24px;cursor:pointer}
    @media(max-width:480px){body{padding:14px}.message-card{padding:28px 20px;border-radius:22px}h1{font-size:20px}}
  </style>
</head>
<body>
  <main class="message-card">
    <div class="message-icon">{$icon}</div>
    <h1>{$safeTitle}</h1>
    <div class="message">{$safeMessage}</div>
    <button type="button" onclick="window.close()">ปิดหน้าต่าง</button>
  </main>
</body>
</html>
HTML;
    exit;
}

function ev_find_by_token(mysqli $connect, string $tokenHash, bool $forUpdate = false): ?array
{
    $lock = $forUpdate ? ' FOR UPDATE' : '';

    $sql = "
        SELECT
            l.id AS log_id,
            l.evaluation_id,
            l.rp_id,
            l.rp_format,
            l.ag_id,
            l.recipient_name,
            l.recipient_email,
            l.send_status,
            l.expires_at,
            l.clicked_at,
            l.evaluated_at,

            e.status AS evaluation_status,
            e.round_no,
            e.score,
            e.result,
            e.comment,
            e.submitted_by_email,
            e.submitted_at,

            r.id AS repair_id,
            r.rp_format AS repair_format,
            r.report_date,
            r.report_time,
            r.name,
            r.phone,
            r.building,
            r.floor,
            r.room,
            r.problem_detail,
            r.urgency,
            r.status AS repair_status,
            r.machine_id,
            r.received_date,
            r.received_by,
            r.process_date,
            r.process_time,
            r.completed_by,
            r.completed_date,
            r.completed_time,
            r.completed_solution,
            r.service_type,
            r.job_type,
            r.created_by,
            r.created_at,
            r.updated_by,
            r.updated_at,
            r.has_feedback,

            ta.area_name AS building_name,
            tac.ac_name AS floor_name,
            tar.ar_name AS room_name,

            a.ass_code AS asset_code,
            a.asset_name,
            a.asset_sn,
            a.asset_model,

            rg.rpg_name AS service_type_name,
            rs.rps_name AS job_type_name,

          COALESCE(
				NULLIF(TRIM(CONCAT_WS(' ', u.user_name, u.user_fname)), ''),
				r.created_by
			) AS created_by_name,
            COALESCE(NULLIF(uc.user_fname, ''), NULLIF(uc.user_name, ''), r.completed_by) AS completed_by_name

        FROM tb_email_notify_eval_log l

        INNER JOIN tb_repair_evaluation e
            ON e.id = l.evaluation_id

        INNER JOIN repair_requests r
            ON r.id = l.rp_id
           AND r.ag_id = l.ag_id

        LEFT JOIN tb_area ta
            ON ta.area_id = r.building
        LEFT JOIN tb_area_class tac
            ON tac.ac_id = r.floor
        LEFT JOIN tb_area_room tar
            ON tar.ar_id = r.room
        LEFT JOIN tb_ass_list a
            ON a.ass_id = r.machine_id
        LEFT JOIN tb_repair_group rg
            ON rg.rpg_id = r.service_type
        LEFT JOIN tb_repair_system rs
            ON rs.rps_id = r.job_type
        LEFT JOIN tb_user u
            ON u.user_id = r.created_by
        LEFT JOIN tb_user uc
            ON uc.user_id = r.completed_by

        WHERE l.access_token_hash = ?
        LIMIT 1
        {$lock}
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare token failed: ' . $connect->error);
    }

    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function ev_validate_link(array $data): void
{
    if (($data['send_status'] ?? '') !== 'sent') {
        ev_message(
            'ลิงก์ยังไม่พร้อมใช้งาน',
            'รายการอีเมลนี้ส่งไม่สำเร็จ กรุณาติดต่อผู้ดูแลระบบ',
            'warning'
        );
    }

    $evaluationCompleted =
        ($data['evaluation_status'] ?? '') === 'completed'
        || !empty($data['evaluated_at']);

    /*
     * หากประเมินเสร็จแล้ว ยังคงเปิดดูรายละเอียดและผลประเมินได้
     * แม้ลิงก์จะเลยวันหมดอายุแล้ว
     */
    if (
        !$evaluationCompleted
        && (
            empty($data['expires_at'])
            || strtotime($data['expires_at']) < time()
        )
    ) {
        ev_message(
            'ลิงก์หมดอายุ',
            'กรุณาติดต่อผู้ดูแลระบบเพื่อส่งแบบประเมินใหม่',
            'warning'
        );
    }
}

function ev_fetch_images(mysqli $connect, int $repairId, string $table, string $dateColumn): array
{
    $allowed = [
        'repair_images' => 'created_at',
        'repair_completed_images' => 'uploaded_at'
    ];

    if (!isset($allowed[$table]) || $allowed[$table] !== $dateColumn) {
        return [];
    }

    $sql = "
        SELECT path, file_name
        FROM {$table}
        WHERE repair_request_id = ?
        ORDER BY {$dateColumn} ASC
    ";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $repairId);
    $stmt->execute();
    $result = $stmt->get_result();

    $uploadBaseUrl = rtrim(ev_base_url(), '/') . '/API_es/uploads/';
    $images = [];

    while ($row = $result->fetch_assoc()) {
        $path = trim(str_replace('\\', '/', (string)($row['path'] ?? '')));
        $fileName = trim((string)($row['file_name'] ?? ''));

        if ($path === '' && $fileName === '') {
            continue;
        }

        if ($fileName !== '') {
            $path = trim($path, '/');
            $relative = $path !== '' ? $path . '/' . $fileName : $fileName;
        } else {
            $relative = ltrim($path, '/');
        }

        if ($relative === '') {
            continue;
        }

        $images[] = ev_is_absolute_url($relative)
            ? $relative
            : rtrim($uploadBaseUrl, '/') . '/' . ltrim($relative, '/');
    }

    $stmt->close();
    return array_values(array_unique($images));
}

function ev_fetch_parts(mysqli $connect, int $repairId): array
{
    $sql = "
        SELECT
            d.rpd_id,
            d.rpd_product_id,
            d.wh_id,
            d.pd_gen_code,
            d.rpd_details_head,
            d.rpd_details,
            d.rpd_brand,
            d.rpd_qty,
            d.rpd_price,

            d.pd_unit,
            d.rpd_sum_money,
            ws.wh_name
        FROM tb_repair_detail d
        LEFT JOIN tb_wh_stock ws
            ON ws.id = d.wh_id
        WHERE d.rpd_rp_id = ?
        ORDER BY d.rpd_id ASC
    ";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $repairId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;
}

function ev_fetch_responsibles(mysqli $connect, int $repairId): array
{
    $sql = "
        SELECT
            user_id,
            user_name,
            user_fname,
            user_department,
            user_tel
        FROM repair_request_responsible
        WHERE repair_request_id = ?
        ORDER BY id ASC
    ";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $repairId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $name = trim((string)($row['user_name'].' '.$row['user_fname'] ?? ''));
        if ($name === '') {
            $name = trim((string)($row['user_name'] ?? ''));
        }
        if ($name === '') {
            $name = trim((string)($row['user_id'] ?? ''));
        }

        $row['display_name'] = $name !== '' ? $name : '-';
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;
}

$token = trim((string)($_POST['token'] ?? $_GET['t'] ?? ''));

if ($token === '') {
    ev_message('ลิงก์ไม่ถูกต้อง', 'ไม่พบ Token สำหรับแบบประเมิน', 'error');
}

$tokenHash = hash('sha256', $token);

/* =========================================================
 * POST: บันทึกผล
 * ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = (int)($_POST['score'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));

    if (!in_array($score, [1, 2, 3, 4, 5], true)) {
        ev_message('ข้อมูลไม่ถูกต้อง', 'กรุณาเลือกระดับความพึงพอใจ', 'warning');
    }

    if (function_exists('mb_strlen') && mb_strlen($comment, 'UTF-8') > 2000) {
        ev_message('ข้อมูลยาวเกินไป', 'ข้อเสนอแนะต้องไม่เกิน 2,000 ตัวอักษร', 'warning');
    }

    // ตัดคำถามข้อ 2 ออก และบันทึกผลเป็น completed อัตโนมัติ
    $resultValue = 'completed';

    $connect->begin_transaction();

    try {
        $data = ev_find_by_token($connect, $tokenHash, true);

        if (!$data) {
            throw new Exception('ไม่พบข้อมูลแบบประเมิน หรือลิงก์ไม่ถูกต้อง');
        }

        if (($data['send_status'] ?? '') !== 'sent') {
            throw new Exception('รายการอีเมลนี้ส่งไม่สำเร็จ');
        }

        if (empty($data['expires_at']) || strtotime($data['expires_at']) < time()) {
            throw new Exception('ลิงก์แบบประเมินหมดอายุแล้ว');
        }

        if (
            ($data['evaluation_status'] ?? '') === 'completed'
            || !empty($data['evaluated_at'])
        ) {
            throw new Exception('แบบประเมินรอบนี้ถูกบันทึกแล้ว');
        }

        $updatedBy = trim((string)($data['recipient_email'] ?? ''));
        if ($updatedBy === '') {
            $updatedBy = 'public-evaluation';
        }

        $sqlEvaluation = "
            UPDATE tb_repair_evaluation
            SET
                status = 'completed',
                score = ?,
                result = ?,
                comment = ?,
                submitted_by_email = ?,
                submitted_at = NOW(),
                updated_by = ?
            WHERE id = ?
              AND status <> 'completed'
            LIMIT 1
        ";

        $stmtEvaluation = $connect->prepare($sqlEvaluation);
        if (!$stmtEvaluation) {
            throw new Exception('Prepare evaluation update failed: ' . $connect->error);
        }

        $stmtEvaluation->bind_param(
            'issssi',
            $score,
            $resultValue,
            $comment,
            $data['recipient_email'],
            $updatedBy,
            $data['evaluation_id']
        );
        $stmtEvaluation->execute();

        if ($stmtEvaluation->affected_rows !== 1) {
            $stmtEvaluation->close();
            throw new Exception('ไม่สามารถบันทึกผลได้ หรือมีผู้ประเมินไปแล้ว');
        }
        $stmtEvaluation->close();

        $sqlLog = "
            UPDATE tb_email_notify_eval_log
            SET
                clicked_at = COALESCE(clicked_at, NOW()),
                evaluated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ";

        $stmtLog = $connect->prepare($sqlLog);
        if (!$stmtLog) {
            throw new Exception('Prepare log update failed: ' . $connect->error);
        }
        $stmtLog->bind_param('i', $data['log_id']);
        $stmtLog->execute();
        $stmtLog->close();

        $sqlRepair = "
            UPDATE repair_requests
            SET 
                has_feedback = 1,
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
              AND ag_id = ?
            LIMIT 1
        ";

        $stmtRepair = $connect->prepare($sqlRepair);
        if (!$stmtRepair) {
            throw new Exception('Prepare repair update failed: ' . $connect->error);
        }
        $stmtRepair->bind_param('sis', $updatedBy, $data['rp_id'], $data['ag_id']);
        $stmtRepair->execute();
        $stmtRepair->close();

        $connect->commit();

        /*
         * กลับมาเปิดหน้าเดิม เพื่อให้ผู้ประเมินยังเห็นข้อมูลใบงาน
         * และผลการประเมินที่เพิ่งบันทึกแบบอ่านอย่างเดียว
         */
        $selfPath = strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '?');
        $redirectUrl = $selfPath
            . '?t=' . urlencode($token)
            . '&saved=1';

        header('Location: ' . $redirectUrl, true, 303);
        exit;
    } catch (Throwable $error) {
        $connect->rollback();
        ev_message('บันทึกไม่สำเร็จ', $error->getMessage(), 'error');
    }
}

/* =========================================================
 * GET: โหลดรายละเอียดใบงาน
 * ========================================================= */
try {
    $data = ev_find_by_token($connect, $tokenHash, false);

    if (!$data) {
        ev_message('ไม่พบแบบประเมิน', 'ลิงก์นี้ไม่ถูกต้องหรือถูกยกเลิกแล้ว', 'error');
    }

    ev_validate_link($data);

    $sqlClick = "
        UPDATE tb_email_notify_eval_log
        SET clicked_at = COALESCE(clicked_at, NOW())
        WHERE id = ?
        LIMIT 1
    ";

    $stmtClick = $connect->prepare($sqlClick);
    if ($stmtClick) {
        $stmtClick->bind_param('i', $data['log_id']);
        $stmtClick->execute();
        $stmtClick->close();
    }

    $repairId = (int)$data['rp_id'];
    $beforeImages = ev_fetch_images($connect, $repairId, 'repair_images', 'created_at');
    $afterImages = ev_fetch_images($connect, $repairId, 'repair_completed_images', 'uploaded_at');
    $parts = ev_fetch_parts($connect, $repairId);
    $responsibles = ev_fetch_responsibles($connect, $repairId);
} catch (Throwable $error) {
    ev_message('เกิดข้อผิดพลาด', $error->getMessage(), 'error');
}

$isEvaluationCompleted =
    ($data['evaluation_status'] ?? '') === 'completed'
    || !empty($data['evaluated_at']);

$evaluationRound = max(1, (int)($data['round_no'] ?? 1));
$evaluationScore = (int)($data['score'] ?? 0);
$evaluationScoreLabel = ev_score_label($evaluationScore);
$evaluationComment = trim((string)($data['comment'] ?? ''));
$evaluationSubmittedAt = ev_thai_date($data['submitted_at'] ?? '', true);
$evaluationSubmittedBy = trim((string)($data['recipient_name'] ?? ''));
if ($evaluationSubmittedBy === '') {
    $evaluationSubmittedBy = trim((string)($data['submitted_by_email'] ?? ''));
}
$showSavedBanner = isset($_GET['saved']) && $_GET['saved'] === '1';

$rpFormat = ev_h($data['repair_format'] ?: ($data['rp_format'] ?? '-'));
$statusText = ev_h(ev_status_label($data['repair_status'] ?? ''));
$urgencyText = ev_h(ev_urgency_label($data['urgency'] ?? ''));
$locationText = trim(
    ($data['building_name'] ?? '')
    . (($data['floor_name'] ?? '') !== '' ? ' / ' . $data['floor_name'] : '')
    . (($data['room_name'] ?? '') !== '' ? ' / ' . $data['room_name'] : '')
);
$locationText = $locationText !== '' ? $locationText : '-';

$hasAssetCode = ev_has_value($data['asset_code'] ?? '');
$hasAssetName = ev_has_value($data['asset_name'] ?? '');
$hasAssetModel = ev_has_value($data['asset_model'] ?? '');
$hasAssetSn = ev_has_value($data['asset_sn'] ?? '');
$hasAssetData = $hasAssetCode || $hasAssetName || $hasAssetModel || $hasAssetSn;

$hasServiceType = ev_has_value($data['service_type_name'] ?? '');
$hasJobType = ev_has_value($data['job_type_name'] ?? '');
$hasWorkClassification = $hasServiceType || $hasJobType;

$partsTotal = 0.0;
foreach ($parts as $part) {
    $partsTotal += (float)($part['rpd_sum_money'] ?? 0);
}

$beforeImagesJson = json_encode($beforeImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$afterImagesJson = json_encode($afterImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light">
  <title>ประเมินงานซ่อม <?= $rpFormat ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --navy:#0b2f4f;
      --primary:#006b9f;
      --primary-2:#04adff;
      --surface:#ffffff;
      --surface-soft:#f7fafc;
      --page:#edf3f8;
      --line:#dce5ee;
      --line-soft:#eaf0f5;
      --text:#0f172a;
      --muted:#64748b;
      --success:#059669;
      --warning:#d97706;
      --danger:#e11d48;
      --shadow:0 22px 60px rgba(15,23,42,.10);
      --radius:22px;
    }

    *{box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{
      margin:0;
      min-height:100vh;
      font-family:'Kanit',sans-serif;
      color:var(--text);
      background:
        radial-gradient(circle at 12% -10%, rgba(4,173,255,.14), transparent 26rem),
        radial-gradient(circle at 100% 15%, rgba(0,107,159,.08), transparent 28rem),
        var(--page);
    }
    button,input,textarea{font:inherit}
    img{max-width:100%;display:block}
    a{color:inherit}

    .page-shell{width:min(1500px,100%);margin:0 auto;padding:20px}
    .topbar{
      display:flex;align-items:center;justify-content:space-between;gap:18px;
      padding:18px 22px;border:1px solid rgba(220,229,238,.9);border-radius:24px;
      background:rgba(255,255,255,.92);backdrop-filter:blur(16px);box-shadow:0 12px 34px rgba(15,23,42,.07);
      position:sticky;top:12px;z-index:30;
    }
    .brand{display:flex;align-items:center;gap:14px;min-width:0}
    .brand-icon{width:48px;height:48px;border-radius:16px;background:linear-gradient(135deg,var(--primary),var(--primary-2));color:#fff;display:grid;place-items:center;box-shadow:0 12px 24px rgba(0,107,159,.24);flex:0 0 auto}
    .brand-icon svg{width:24px;height:24px}
    .eyebrow{font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#0c83be}
    .brand-title{margin:2px 0 0;font-size:20px;line-height:1.25;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .top-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .pill{display:inline-flex;align-items:center;gap:7px;min-height:34px;padding:7px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
    .pill.success{background:#ecfdf5;border-color:#a7f3d0;color:#047857}
    .pill.info{background:#eff8ff;border-color:#bae6fd;color:#0369a1}
    .pill-dot{width:7px;height:7px;border-radius:50%;background:currentColor}

    .layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(330px,430px);gap:20px;margin-top:20px;align-items:start}
    .content-stack{display:grid;gap:18px;min-width:0}
    .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:0 10px 30px rgba(15,23,42,.055);overflow:hidden}
    .card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid var(--line-soft);background:linear-gradient(180deg,#fff,#fbfdff)}
    .card-title{display:flex;align-items:center;gap:11px;font-size:15px;font-weight:700}
    .section-icon{width:36px;height:36px;border-radius:12px;background:#eff8ff;color:#0369a1;display:grid;place-items:center;flex:0 0 auto}
    .section-icon svg{width:18px;height:18px}
    .card-sub{font-size:11px;color:#94a3b8;margin-top:2px}
    .card-body{padding:20px}

    .summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .summary-item{min-width:0;padding:14px;border:1px solid var(--line-soft);border-radius:16px;background:var(--surface-soft)}
    .summary-label{font-size:11px;font-weight:600;color:#94a3b8}
    .summary-value{margin-top:5px;font-size:13px;font-weight:700;color:#334155;line-height:1.45;overflow-wrap:anywhere}

    .detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .detail-box{padding:15px;border:1px solid var(--line-soft);border-radius:16px;background:#fff;min-width:0}
    .detail-box.span-2{grid-column:1/-1}
    .detail-label{font-size:11px;font-weight:600;color:#94a3b8}
    .detail-value{margin-top:6px;font-size:13px;font-weight:600;color:#334155;line-height:1.65;overflow-wrap:anywhere}
    .detail-value.strong{font-size:14px;color:#0f172a;font-weight:700}
    .detail-value.good{color:#047857}

    .timeline-wrap{overflow-x:auto;padding:2px 2px 8px;scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
    .timeline-wrap::-webkit-scrollbar{height:8px}.timeline-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:999px}
    .timeline{display:grid;grid-template-columns:repeat(4,minmax(170px,1fr));min-width:760px;position:relative;padding:6px 0 2px}
    .timeline::before{content:"";position:absolute;left:12.5%;right:12.5%;top:16px;height:2px;background:linear-gradient(90deg,#bae6fd,#7dd3fc,#bae6fd)}
    .timeline-row{position:relative;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 14px;min-width:0}
    .timeline-dot{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;background:#0284c7;z-index:1;border:5px solid #fff;box-shadow:0 0 0 2px #bae6fd,0 6px 14px rgba(2,132,199,.16)}
    .timeline-title{font-size:12px;font-weight:700;color:#334155;margin-top:11px;line-height:1.45}
    .timeline-meta{font-size:11px;color:#94a3b8;margin-top:4px;line-height:1.5;white-space:normal}
    .work-type-grid{margin-bottom:18px}

    .gallery-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
    .image-button{position:relative;border:0;padding:0;border-radius:16px;overflow:hidden;background:#e2e8f0;aspect-ratio:16/10;cursor:zoom-in;box-shadow:inset 0 0 0 1px rgba(15,23,42,.06)}
    .image-button img{width:100%;height:100%;object-fit:cover;transition:transform .25s ease}
    .image-button:hover img{transform:scale(1.035)}
    .image-index{position:absolute;right:10px;bottom:10px;background:rgba(15,23,42,.78);color:#fff;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:700}
    .empty-state{padding:28px 18px;border:1px dashed #cbd5e1;border-radius:16px;background:#f8fafc;text-align:center;color:#94a3b8;font-size:12px}

    .parts-table-wrap{overflow-x:auto;border:1px solid var(--line-soft);border-radius:16px}
    table{border-collapse:collapse;width:100%;min-width:720px;background:#fff}
    th,td{padding:12px 13px;border-bottom:1px solid var(--line-soft);text-align:left;font-size:12px;vertical-align:top}
    th{background:#f8fafc;color:#64748b;font-size:11px;font-weight:700;white-space:nowrap}
    td{color:#334155}
    tr:last-child td{border-bottom:0}
    .money{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
    .part-total{display:flex;justify-content:flex-end;gap:12px;padding-top:14px;font-size:13px}.part-total strong{font-size:16px;color:#0f766e}

    .person-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .person{display:flex;align-items:center;gap:11px;padding:12px;border:1px solid var(--line-soft);border-radius:15px;background:#fff;min-width:0}
    .avatar{width:38px;height:38px;border-radius:13px;display:grid;place-items:center;background:#e0f2fe;color:#0369a1;font-size:13px;font-weight:800;flex:0 0 auto}
    .person-name{font-size:12px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.person-meta{font-size:10px;color:#94a3b8;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

    .evaluation-card{position:sticky;top:106px;background:#fff;border:1px solid #cfe3ef;border-radius:26px;box-shadow:var(--shadow);overflow:hidden}
    .evaluation-head{padding:24px;background:linear-gradient(135deg,#0b4f73 0%,#006b9f 58%,#04adff 100%);color:#fff}
    .evaluation-kicker{font-size:11px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#d8f3ff}
    .evaluation-title{font-size:22px;font-weight:700;line-height:1.3;margin:6px 0 0}
    .evaluation-desc{font-size:12px;line-height:1.7;color:#d9f2ff;margin:8px 0 0}
    .evaluation-body{padding:22px}
    .required-label{font-size:13px;font-weight:700;color:#1e293b}.required-label em{font-style:normal;color:#e11d48}
    .score-list{display:grid;grid-template-columns:1fr;gap:10px;margin-top:14px}
    .score-option{position:relative;display:block;cursor:pointer}
    .score-option input{position:absolute;opacity:0;pointer-events:none}
    .score-ui{display:grid;grid-template-columns:44px minmax(0,1fr) 26px;gap:12px;align-items:center;padding:12px;border:1px solid var(--line);border-radius:16px;background:#fff;transition:.18s ease}
    .score-option:hover .score-ui{border-color:#7dd3fc;background:#f8fcff;transform:translateY(-1px)}
    .score-option input:checked + .score-ui{border-color:#0284c7;background:#f0f9ff;box-shadow:0 0 0 4px rgba(14,165,233,.11)}
    .score-face{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;font-size:23px;background:#f8fafc;border:1px solid #eef2f6}
    .score-name{font-size:13px;font-weight:700;color:#1e293b}.score-help{font-size:10px;color:#94a3b8;margin-top:2px}
    .radio-mark{width:20px;height:20px;border:2px solid #cbd5e1;border-radius:50%;position:relative}
    .score-option input:checked + .score-ui .radio-mark{border-color:#0284c7}
    .score-option input:checked + .score-ui .radio-mark::after{content:"";position:absolute;inset:4px;border-radius:50%;background:#0284c7}

    .comment-wrap{margin-top:20px}.comment-head{display:flex;justify-content:space-between;gap:12px;align-items:center}.counter{font-size:10px;color:#94a3b8}
    textarea{width:100%;min-height:120px;margin-top:10px;resize:vertical;border:1px solid var(--line);border-radius:16px;background:#f8fafc;padding:13px 14px;color:#334155;font-size:13px;line-height:1.65;outline:none;transition:.18s}
    textarea:focus{background:#fff;border-color:#38bdf8;box-shadow:0 0 0 4px rgba(14,165,233,.10)}
    .privacy-note{display:flex;gap:9px;align-items:flex-start;margin-top:14px;padding:11px 12px;border-radius:14px;background:#f8fafc;color:#64748b;font-size:10px;line-height:1.55}
    .submit-button{width:100%;border:0;border-radius:16px;margin-top:18px;padding:14px 18px;background:linear-gradient(135deg,#006b9f,#04adff);color:#fff;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 14px 26px rgba(0,107,159,.22);transition:.2s}
    .submit-button:hover{transform:translateY(-1px);box-shadow:0 18px 30px rgba(0,107,159,.28)}
    .submit-button:disabled{opacity:.65;cursor:wait;transform:none}
    .validation-error{display:none;margin-top:10px;color:#be123c;background:#fff1f2;border:1px solid #fecdd3;border-radius:12px;padding:9px 11px;font-size:11px}
    .validation-error.show{display:block}
    .saved-banner{display:flex;align-items:flex-start;gap:10px;margin:0 0 16px;padding:13px 14px;border:1px solid #a7f3d0;border-radius:16px;background:#ecfdf5;color:#047857;font-size:12px;line-height:1.65}
    .result-panel{padding:22px}
    .result-hero{padding:20px;border:1px solid #a7f3d0;border-radius:20px;background:linear-gradient(145deg,#ecfdf5,#f8fffc);text-align:center}
    .result-round{font-size:11px;font-weight:700;color:#059669;letter-spacing:.04em}
    .result-face{width:74px;height:74px;margin:14px auto 10px;border-radius:24px;display:grid;place-items:center;background:#fff;border:1px solid #d1fae5;font-size:38px;box-shadow:0 12px 28px rgba(5,150,105,.12)}
    .result-score{font-size:24px;font-weight:800;color:#047857}
    .result-caption{margin-top:5px;font-size:11px;color:#6b7280}
    .result-meta{display:grid;gap:10px;margin-top:16px}
    .result-meta-item{padding:12px 13px;border:1px solid var(--line-soft);border-radius:15px;background:#fff}
    .result-meta-label{font-size:10px;font-weight:700;color:#94a3b8}
    .result-meta-value{margin-top:4px;font-size:12px;font-weight:600;color:#334155;line-height:1.65;overflow-wrap:anywhere}
    .view-note{display:flex;gap:9px;align-items:flex-start;margin-top:14px;padding:11px 12px;border-radius:14px;background:#eff8ff;color:#0369a1;font-size:10px;line-height:1.55}

    .lightbox{position:fixed;inset:0;background:rgba(2,6,23,.88);z-index:100;display:none;align-items:center;justify-content:center;padding:22px;backdrop-filter:blur(8px)}
    .lightbox.open{display:flex}.lightbox img{max-width:min(1200px,94vw);max-height:86vh;border-radius:18px;box-shadow:0 30px 90px rgba(0,0,0,.45)}
    .lightbox-close,.lightbox-nav{position:absolute;border:1px solid rgba(255,255,255,.2);background:rgba(15,23,42,.6);color:#fff;border-radius:50%;display:grid;place-items:center;cursor:pointer}
    .lightbox-close{width:44px;height:44px;right:18px;top:18px;font-size:24px}.lightbox-nav{width:46px;height:46px;top:50%;transform:translateY(-50%);font-size:24px}.lightbox-prev{left:18px}.lightbox-next{right:18px}.lightbox-count{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);color:#fff;background:rgba(15,23,42,.65);padding:6px 11px;border-radius:999px;font-size:11px}

    @media(max-width:1180px){
      .layout{grid-template-columns:minmax(0,1fr) 360px}
      .summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media(max-width:920px){
      .page-shell{padding:14px}
      .topbar{position:relative;top:0}
      .layout{grid-template-columns:1fr}
      .evaluation-card{position:relative;top:auto}
      .evaluation-body{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,.9fr);gap:18px;align-items:start}
      .comment-wrap{margin-top:0}.privacy-note,.submit-button,.validation-error{grid-column:1/-1}
      .score-list{grid-template-columns:repeat(2,minmax(0,1fr))}
    }
    @media(max-width:700px){
      .topbar{align-items:flex-start;padding:16px}.top-meta{display:none}.brand-title{font-size:17px;white-space:normal}
      .summary-grid,.detail-grid,.person-list{grid-template-columns:1fr}
      .timeline{min-width:680px;grid-template-columns:repeat(4,minmax(155px,1fr))}
      .detail-box.span-2{grid-column:auto}
      .gallery-grid{grid-template-columns:1fr}
      .evaluation-body{display:block;padding:18px}
      .comment-wrap{margin-top:18px}
      .score-list{grid-template-columns:1fr}
      .card-head{padding:15px 16px}.card-body{padding:16px}
      .evaluation-head{padding:20px}.evaluation-title{font-size:20px}
      .submit-button{position:sticky;bottom:calc(10px + env(safe-area-inset-bottom));z-index:8}
    }
    @media(max-width:420px){
      .page-shell{padding:10px}.topbar{border-radius:18px}.brand-icon{width:42px;height:42px;border-radius:14px}
      .card,.evaluation-card{border-radius:19px}.summary-item,.detail-box{border-radius:14px}
      .score-ui{grid-template-columns:40px minmax(0,1fr) 22px;padding:10px}.score-face{width:40px;height:40px}
      .lightbox-nav{width:40px;height:40px}.lightbox-prev{left:8px}.lightbox-next{right:8px}
    }
    @media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important;animation:none!important}}
  </style>
</head>
<body>
  <div class="page-shell">
    <header class="topbar">
      <div class="brand">
        <div class="brand-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M14.7 6.3a4 4 0 0 0-5.2 5.2L3 18l3 3 6.5-6.5a4 4 0 0 0 5.2-5.2l-2.4 2.4-3-3 2.4-2.4Z"/>
          </svg>
        </div>
        <div style="min-width:0">
          <div class="eyebrow">Repair Service Evaluation</div>
          <h1 class="brand-title">ประเมินผลการให้บริการงานซ่อม</h1>
        </div>
      </div>
      <div class="top-meta">
        <span class="pill info"><span class="pill-dot"></span><?= $rpFormat ?></span>
        <span class="pill success"><span class="pill-dot"></span><?= $statusText ?></span>
      </div>
    </header>

    <main class="layout">
      <div class="content-stack">
        <section class="card">
          <div class="card-head">
            <div>
              <div class="card-title">
                <span class="section-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
                </span>
                ข้อมูลใบแจ้งซ่อม
              </div>
              <div class="card-sub">ข้อมูลต้นทาง วันที่ เวลา ผู้แจ้ง สถานที่ และสถานะงาน</div>
            </div>
            <span class="pill info"><?= $urgencyText ?></span>
          </div>
          <div class="card-body">
            <div class="summary-grid">
              <div class="summary-item"><div class="summary-label">เลขที่ใบงาน</div><div class="summary-value"><?= $rpFormat ?></div></div>
              <div class="summary-item"><div class="summary-label">วันที่และเวลาที่แจ้ง</div><div class="summary-value"><?= ev_h(ev_thai_date($data['report_date'] ?? '')) ?> · <?= ev_h(ev_time($data['report_time'] ?? '')) ?></div></div>
              <div class="summary-item"><div class="summary-label">สถานะใบงาน</div><div class="summary-value"><?= $statusText ?></div></div>
              <div class="summary-item"><div class="summary-label">ความเร่งด่วน</div><div class="summary-value"><?= $urgencyText ?></div></div>
            </div>

            <div class="detail-grid" style="margin-top:12px">
              <div class="detail-box">
                <div class="detail-label">ผู้แจ้ง</div>
                <div class="detail-value strong"><?= ev_h($data['name'] ?? '-') ?></div>
              </div>
              <div class="detail-box">
                <div class="detail-label">เบอร์โทรศัพท์</div>
                <div class="detail-value strong"><?= ev_h($data['phone'] ?? '-') ?></div>
              </div>
              <div class="detail-box span-2">
                <div class="detail-label">สถานที่</div>
                <div class="detail-value strong"><?= ev_h($locationText) ?></div>
              </div>
              <div class="detail-box span-2">
                <div class="detail-label">รายละเอียด / สาเหตุที่แจ้งซ่อม</div>
                <div class="detail-value"><?= nl2br(ev_h($data['problem_detail'] ?? '-')) ?></div>
              </div>
              <!--<div class="detail-box">
                <div class="detail-label">ผู้รับแจ้ง / ผู้บันทึก</div>
                <div class="detail-value"><?= ev_h($data['created_by_name'] ?? '-') ?></div>
              </div>
              <div class="detail-box">
                <div class="detail-label">วันที่บันทึกข้อมูล</div>
                <div class="detail-value"><?= ev_h(ev_thai_date($data['created_at'] ?? '', true)) ?></div>
              </div>-->
            </div>
          </div>
        </section>

        <?php if ($hasAssetData): ?>
        <section class="card">
          <div class="card-head">
            <div>
              <div class="card-title">
                <span class="section-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="4"/><path d="M8 8h8v8H8zM12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
                </span>
                ข้อมูลทรัพย์สิน
              </div>
              <div class="card-sub">รายละเอียดอุปกรณ์หรือทรัพย์สินที่เกี่ยวข้องกับใบงาน</div>
            </div>
          </div>
          <div class="card-body">
            <div class="detail-grid">
              <?php if ($hasAssetCode): ?>
                <div class="detail-box"><div class="detail-label">รหัสอุปกรณ์</div><div class="detail-value strong"><?= ev_h($data['asset_code']) ?></div></div>
              <?php endif; ?>
              <?php if ($hasAssetName): ?>
                <div class="detail-box"><div class="detail-label">ชื่ออุปกรณ์</div><div class="detail-value strong"><?= ev_h($data['asset_name']) ?></div></div>
              <?php endif; ?>
              <?php if ($hasAssetModel): ?>
                <div class="detail-box"><div class="detail-label">รุ่น / Model</div><div class="detail-value"><?= ev_h($data['asset_model']) ?></div></div>
              <?php endif; ?>
              <?php if ($hasAssetSn): ?>
                <div class="detail-box"><div class="detail-label">Serial Number</div><div class="detail-value"><?= ev_h($data['asset_sn']) ?></div></div>
              <?php endif; ?>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <section class="card">
          <div class="card-head">
            <div>
              <div class="card-title">
                <span class="section-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg>
                </span>
                การรับงานและการดำเนินงาน
              </div>
              <div class="card-sub">ลำดับเวลาตั้งแต่รับแจ้ง เริ่มดำเนินการ จนถึงปิดงาน</div>
            </div>
          </div>
          <div class="card-body">
            <?php if ($hasWorkClassification): ?>
              <div class="detail-grid work-type-grid">
                <?php if ($hasServiceType): ?>
                  <div class="detail-box<?= !$hasJobType ? ' span-2' : '' ?>">
                    <div class="detail-label">ชนิดของการบริการ</div>
                    <div class="detail-value strong"><?= ev_h($data['service_type_name']) ?></div>
                  </div>
                <?php endif; ?>
                <?php if ($hasJobType): ?>
                  <div class="detail-box<?= !$hasServiceType ? ' span-2' : '' ?>">
                    <div class="detail-label">ประเภทงาน</div>
                    <div class="detail-value strong"><?= ev_h($data['job_type_name']) ?></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="timeline-wrap" aria-label="ลำดับเวลาการดำเนินงาน">
              <div class="timeline">
                <div class="timeline-row">
                  <span class="timeline-dot"></span>
                  <div class="timeline-title">รับแจ้งงานซ่อม</div>
                  <div class="timeline-meta"><?= ev_h(ev_datetime_text($data['report_date'] ?? '', $data['report_time'] ?? '')) ?></div>
                </div>
                <div class="timeline-row">
                  <span class="timeline-dot"></span>
                  <div class="timeline-title">รับงาน / จ่ายงาน</div>
                  <div class="timeline-meta"><?= ev_h(ev_datetime_text($data['received_date'] ?? '')) ?></div>
                </div>
                <div class="timeline-row">
                  <span class="timeline-dot"></span>
                  <div class="timeline-title">เริ่มดำเนินการ</div>
                  <div class="timeline-meta"><?= ev_h(ev_datetime_text($data['process_date'] ?? '', $data['process_time'] ?? '')) ?></div>
                </div>
                <div class="timeline-row">
                  <span class="timeline-dot"></span>
                  <div class="timeline-title">ดำเนินงานเสร็จสิ้น</div>
                  <div class="timeline-meta"><?= ev_h(ev_datetime_text($data['completed_date'] ?? '', $data['completed_time'] ?? '')) ?></div>
                </div>
              </div>
            </div>

            <div class="detail-grid" style="margin-top:18px">
              <div class="detail-box span-2">
                <div class="detail-label">วิธีดำเนินการ / ผลการแก้ไข</div>
                <div class="detail-value good"><?= nl2br(ev_h($data['completed_solution'] ?? '-')) ?></div>
              </div>
              <!--<div class="detail-box">
                <div class="detail-label">ผู้ปิดงาน</div>
                <div class="detail-value"><?= ev_h($data['completed_by_name'] ?? '-') ?></div>
              </div>
              <div class="detail-box">
                <div class="detail-label">วันที่แก้ไขล่าสุด</div>
                <div class="detail-value"><?= ev_h(ev_thai_date($data['updated_at'] ?? '', true)) ?></div>
              </div>-->
            </div>

            <div style="margin-top:18px">
              <div class="detail-label" style="margin-bottom:9px">ช่าง / ผู้รับผิดชอบ</div>
              <?php if (!empty($responsibles)): ?>
                <div class="person-list">
                  <?php foreach ($responsibles as $person): ?>
                    <div class="person">
                      <div class="avatar"><?= ev_h(function_exists('mb_substr') ? mb_substr($person['display_name'], 0, 1, 'UTF-8') : substr($person['display_name'], 0, 1)) ?></div>
                      <div style="min-width:0">
                        <div class="person-name"><?= ev_h($person['display_name']) ?></div>
                        <div class="person-meta"><?= ev_h($person['user_department'] ?: '-') ?><?= !empty($person['user_tel']) ? ' · ' . ev_h($person['user_tel']) : '' ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="empty-state">ไม่พบรายชื่อช่างหรือผู้รับผิดชอบ</div>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <section class="card">
          <div class="card-head">
            <div>
              <div class="card-title">
                <span class="section-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg>
                </span>
                รูปภาพประกอบ
              </div>
              <div class="card-sub">รูปก่อนดำเนินการและรูปหลังแก้ไข</div>
            </div>
          </div>
          <div class="card-body">
            <div class="detail-grid">
              <div>
                <div class="detail-label" style="margin-bottom:9px">รูปภาพแจ้งซ่อม (<?= count($beforeImages) ?>)</div>
                <?php if (!empty($beforeImages)): ?>
                  <div class="gallery-grid">
                    <?php foreach ($beforeImages as $index => $image): ?>
                      <button class="image-button" type="button" onClick="openLightbox('before', <?= (int)$index ?>)">
                        <img src="<?= ev_h($image) ?>" alt="รูปแจ้งซ่อม <?= $index + 1 ?>" loading="lazy">
                        <span class="image-index"><?= $index + 1 ?> / <?= count($beforeImages) ?></span>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="empty-state">ไม่มีรูปภาพแจ้งซ่อม</div>
                <?php endif; ?>
              </div>
              <div>
                <div class="detail-label" style="margin-bottom:9px">รูปภาพหลังแก้ไข (<?= count($afterImages) ?>)</div>
                <?php if (!empty($afterImages)): ?>
                  <div class="gallery-grid">
                    <?php foreach ($afterImages as $index => $image): ?>
                      <button class="image-button" type="button" onClick="openLightbox('after', <?= (int)$index ?>)">
                        <img src="<?= ev_h($image) ?>" alt="รูปหลังแก้ไข <?= $index + 1 ?>" loading="lazy">
                        <span class="image-index"><?= $index + 1 ?> / <?= count($afterImages) ?></span>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="empty-state">ไม่มีรูปภาพหลังแก้ไข</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </section>

        <?php if (!empty($parts)): ?>
        <section class="card">
          <div class="card-head">
            <div>
              <div class="card-title">
                <span class="section-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M6 6V4h12v2M5 6l1 14h12l1-14M9 10v6M15 10v6"/></svg>
                </span>
                อุปกรณ์และอะไหล่ที่ใช้จริง
              </div>
              <div class="card-sub">รายการวัสดุ ปริมาณ ราคา และคลังที่เบิกใช้</div>
            </div>
          </div>
          <div class="card-body">
            <div class="parts-table-wrap">
                <table>
                  <thead><tr><th>#</th><th>รหัส / รายการ</th><th>ยี่ห้อ / รายละเอียด</th><th>คลัง</th><th class="money">จำนวน</th><th class="money">ราคา/หน่วย</th><th class="money">รวม</th></tr></thead>
                  <tbody>
                    <?php foreach ($parts as $index => $part): ?>
                      <tr>
                        <td><?= $index + 1 ?></td>
                        <td><strong><?= ev_h($part['pd_gen_code'] ?: '-') ?></strong><br><?= ev_h($part['rpd_details_head'] ?: '-') ?></td>
                        <td><?= ev_h($part['rpd_brand'] ?: '-') ?><br><span style="color:#94a3b8"><?= ev_h($part['rpd_details'] ?: '-') ?></span></td>
                        <td><?= ev_h($part['wh_name'] ?: '-') ?></td>
                        <td class="money"><?= number_format((float)$part['rpd_qty'], 2) ?> <?= ev_h($part['pd_unit'] ?: '') ?></td>
                        <td class="money"><?= number_format((float)$part['rpd_price'], 2) ?></td>
                        <td class="money"><strong><?= number_format((float)$part['rpd_sum_money'], 2) ?></strong></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="part-total"><span>รวมมูลค่าอุปกรณ์และอะไหล่</span><strong><?= number_format($partsTotal, 2) ?> บาท</strong></div>
          </div>
        </section>
        <?php endif; ?>
      </div>

      <aside>
        <?php if ($showSavedBanner): ?>
          <div class="saved-banner">
            <span aria-hidden="true">✓</span>
            <span>บันทึกผลการประเมินเรียบร้อยแล้ว ท่านยังสามารถตรวจสอบข้อมูลใบงานและผลการประเมินได้จากหน้านี้</span>
          </div>
        <?php endif; ?>

        <?php if ($isEvaluationCompleted): ?>
          <section class="evaluation-card" aria-label="ผลการประเมิน">
            <div class="evaluation-head">
              <div class="evaluation-kicker">Evaluation Result</div>
              <h2 class="evaluation-title">ผลการประเมินเรียบร้อยแล้ว</h2>
              <p class="evaluation-desc">
                แบบประเมินรอบนี้ถูกบันทึกแล้ว และยังสามารถเปิดดูรายละเอียดใบงานได้ตามปกติ
              </p>
            </div>

            <div class="result-panel">
              <div class="result-hero">
                <div class="result-round">รอบการประเมินที่ <?= $evaluationRound ?></div>
                <div class="result-face" aria-hidden="true">
                  <?php
                    $faceMap = [5 => '😍', 4 => '😊', 3 => '🙂', 2 => '😐', 1 => '🙁'];
                    echo $faceMap[$evaluationScore] ?? '✓';
                  ?>
                </div>
                <div class="result-score"><?= ev_h($evaluationScoreLabel) ?></div>
                <div class="result-caption">ระดับความพึงพอใจที่บันทึกไว้</div>
              </div>

              <div class="result-meta">
                <div class="result-meta-item">
                  <div class="result-meta-label">วันที่ประเมิน</div>
                  <div class="result-meta-value"><?= ev_h($evaluationSubmittedAt) ?></div>
                </div>

                <div class="result-meta-item">
                  <div class="result-meta-label">ผู้ประเมิน / ผู้รับอีเมล</div>
                  <div class="result-meta-value"><?= ev_h($evaluationSubmittedBy !== '' ? $evaluationSubmittedBy : '-') ?></div>
                </div>

                <div class="result-meta-item">
                  <div class="result-meta-label">ข้อเสนอแนะเพิ่มเติม</div>
                  <div class="result-meta-value">
                    <?= $evaluationComment !== '' ? nl2br(ev_h($evaluationComment)) : 'ไม่มีข้อเสนอแนะเพิ่มเติม' ?>
                  </div>
                </div>
              </div>

              <div class="view-note">
                <span aria-hidden="true">ℹ</span>
                <span>ลิงก์นี้ใช้ดูข้อมูลและผลประเมินของรอบที่ <?= $evaluationRound ?> หากผู้ดูแลส่งแบบประเมินรอบใหม่ ระบบจะออกลิงก์ใหม่สำหรับทำแบบประเมินครั้งถัดไป</span>
              </div>
            </div>
          </section>
        <?php else: ?>
          <form method="post" id="evaluationForm" class="evaluation-card" novalidate>
            <input type="hidden" name="token" value="<?= ev_h($token) ?>">

            <div class="evaluation-head">
              <div class="evaluation-kicker">Customer Feedback · Round <?= $evaluationRound ?></div>
              <h2 class="evaluation-title">ประเมินความพึงพอใจ</h2>
              <p class="evaluation-desc">กรุณาเลือกคะแนนที่ตรงกับประสบการณ์ของท่าน ข้อมูลของรอบนี้จะถูกบันทึกเพียงหนึ่งครั้ง</p>
            </div>

            <div class="evaluation-body">
              <div>
                <div class="required-label">ระดับการประเมิน <em>*</em></div>
                <div class="score-list">
                  <label class="score-option">
                    <input type="radio" name="score" value="5" required>
                    <span class="score-ui"><span class="score-face">😍</span><span><span class="score-name">ดีมาก</span><span class="score-help">ประทับใจมาก เกินความคาดหวัง</span></span><span class="radio-mark"></span></span>
                  </label>
                  <label class="score-option">
                    <input type="radio" name="score" value="4">
                    <span class="score-ui"><span class="score-face">😊</span><span><span class="score-name">ดี</span><span class="score-help">งานเรียบร้อยและบริการดี</span></span><span class="radio-mark"></span></span>
                  </label>
                  <label class="score-option">
                    <input type="radio" name="score" value="3">
                    <span class="score-ui"><span class="score-face">🙂</span><span><span class="score-name">ปานกลาง</span><span class="score-help">โดยรวมอยู่ในระดับมาตรฐาน</span></span><span class="radio-mark"></span></span>
                  </label>
                  <label class="score-option">
                    <input type="radio" name="score" value="2">
                    <span class="score-ui"><span class="score-face">😐</span><span><span class="score-name">พอใช้</span><span class="score-help">ยังมีบางส่วนที่ควรปรับปรุง</span></span><span class="radio-mark"></span></span>
                  </label>
                  <label class="score-option">
                    <input type="radio" name="score" value="1">
                    <span class="score-ui"><span class="score-face">🙁</span><span><span class="score-name">ควรปรับปรุง</span><span class="score-help">ผลลัพธ์หรือบริการยังไม่เป็นที่พอใจ</span></span><span class="radio-mark"></span></span>
                  </label>
                </div>
              </div>

              <div class="comment-wrap">
                <div class="comment-head"><label class="required-label" for="comment">ข้อเสนอแนะเพิ่มเติม</label><span class="counter"><span id="commentCount">0</span>/2000</span></div>
                <textarea id="comment" name="comment" maxlength="2000" placeholder="แจ้งรายละเอียดเพิ่มเติมเพื่อช่วยให้เราปรับปรุงบริการ (ไม่บังคับ)"></textarea>
              </div>

              <div class="validation-error" id="scoreError">กรุณาเลือกระดับการประเมินก่อนส่งข้อมูล</div>

              <div class="privacy-note">
                <span aria-hidden="true">🔒</span>
                <span>ข้อมูลนี้ใช้เพื่อพัฒนาคุณภาพงานซ่อมและการให้บริการเท่านั้น ระบบจะไม่เปิดเผยข้อมูลแก่บุคคลภายนอก</span>
              </div>

              <button class="submit-button" id="submitButton" type="submit">ส่งผลการประเมิน</button>
            </div>
          </form>
        <?php endif; ?>
      </aside>
    </main>
  </div>

  <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="ดูรูปภาพขนาดใหญ่">
    <button type="button" class="lightbox-close" onClick="closeLightbox()" aria-label="ปิด">×</button>
    <button type="button" class="lightbox-nav lightbox-prev" onClick="moveLightbox(-1)" aria-label="รูปก่อนหน้า">‹</button>
    <img id="lightboxImage" src="" alt="รูปภาพขนาดใหญ่">
    <button type="button" class="lightbox-nav lightbox-next" onClick="moveLightbox(1)" aria-label="รูปถัดไป">›</button>
    <div class="lightbox-count" id="lightboxCount"></div>
  </div>

  <script>
    const beforeImages = <?= $beforeImagesJson ?: '[]' ?>;
    const afterImages = <?= $afterImagesJson ?: '[]' ?>;
    let activeImages = [];
    let activeIndex = 0;

    function openLightbox(type, index) {
      activeImages = type === 'after' ? afterImages : beforeImages;
      activeIndex = Number(index) || 0;
      if (!activeImages.length) return;
      renderLightbox();
      document.getElementById('lightbox').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function renderLightbox() {
      if (!activeImages.length) return;
      if (activeIndex < 0) activeIndex = activeImages.length - 1;
      if (activeIndex >= activeImages.length) activeIndex = 0;
      document.getElementById('lightboxImage').src = activeImages[activeIndex];
      document.getElementById('lightboxCount').textContent = (activeIndex + 1) + ' / ' + activeImages.length;
      const showNav = activeImages.length > 1;
      document.querySelectorAll('.lightbox-nav').forEach(function(button) {
        button.style.display = showNav ? 'grid' : 'none';
      });
    }

    function moveLightbox(step) {
      activeIndex += step;
      renderLightbox();
    }

    function closeLightbox() {
      document.getElementById('lightbox').classList.remove('open');
      document.body.style.overflow = '';
    }

    document.getElementById('lightbox').addEventListener('click', function(event) {
      if (event.target === this) closeLightbox();
    });

    document.addEventListener('keydown', function(event) {
      const lightbox = document.getElementById('lightbox');
      if (!lightbox.classList.contains('open')) return;
      if (event.key === 'Escape') closeLightbox();
      if (event.key === 'ArrowLeft') moveLightbox(-1);
      if (event.key === 'ArrowRight') moveLightbox(1);
    });

    const comment = document.getElementById('comment');
    const commentCount = document.getElementById('commentCount');

    if (comment && commentCount) {
      comment.addEventListener('input', function() {
        commentCount.textContent = this.value.length;
      });
    }

    const form = document.getElementById('evaluationForm');
    const scoreError = document.getElementById('scoreError');
    const submitButton = document.getElementById('submitButton');

    if (form && scoreError && submitButton) {
      form.addEventListener('submit', function(event) {
        const selected = form.querySelector('input[name="score"]:checked');

        if (!selected) {
          event.preventDefault();
          scoreError.classList.add('show');

          const scoreList = form.querySelector('.score-list');
          if (scoreList) {
            scoreList.scrollIntoView({
              behavior: 'smooth',
              block: 'center'
            });
          }

          return;
        }

        scoreError.classList.remove('show');
        submitButton.disabled = true;
        submitButton.textContent = 'กำลังบันทึกผลการประเมิน...';
      });

      form.querySelectorAll('input[name="score"]').forEach(function(input) {
        input.addEventListener('change', function() {
          scoreError.classList.remove('show');
        });
      });
    }
  </script>
</body>
</html>
