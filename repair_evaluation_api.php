<?php
/**
 * repair_evaluation_api.php
 *
 * API สำหรับ:
 *   GET  ?action=status&id={repair_request_id}
 *   POST ?action=send   FormData: id, notify_ids[]
 *
 * ไฟล์นี้แยกจาก handle_repair_requests.php โดยไม่ต้องแก้ไฟล์เดิม
 */

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

@session_start();
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/config_ctrl/connect.php';
require_once __DIR__ . '/config_ctrl/checksession.php';

mysqli_set_charset($connect, 'utf8mb4');

/* =========================================================
 * ตั้งค่าเฉพาะส่วนนี้
 * ========================================================= */

// URL สาธารณะของหน้าแบบประเมิน
define(
    'REPAIR_EVAL_PUBLIC_URL',
    'https://happylandgroup.biz/es/repair_evaluation.php'
);

/*
 * ระบบอีเมล Zoho SMTP
 * ใช้ร่วมกับ PHPMailer รุ่นเดิมของระบบได้
 */
define('SENDER_EMAIL', 'no-reply@proactivemanagement.co.th');
define('ZOHO_SMTP_HOST', 'smtp.zoho.com');
define('ZOHO_SMTP_PORT', 587);
define('ZOHO_SMTP_USER', 'no-reply@proactivemanagement.co.th');
define('ZOHO_SMTP_PASS', 'Pass@x1pro');
define('REPAIR_EVAL_FROM_NAME', 'ระบบแจ้งซ่อมและประเมินผลการให้บริการ');

/*
 * ตำแหน่ง PHPMailer รุ่นเก่า
 * ระบบจะลองค้นหาหลายตำแหน่งให้อัตโนมัติ
 * หากทราบตำแหน่งแน่นอน ให้แก้ค่าด้านล่างได้
 */
define('REPAIR_EVAL_PHPMAILER_FILE', '');

/* =========================================================
 * Helper
 * ========================================================= */

function eval_json_response(
    bool $success,
    string $message = '',
    $data = null,
    int $httpCode = 200
): void {
    http_response_code($httpCode);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

function eval_session_ag_id(): string
{
    global $sess_user_agency_es;

    $candidates = [
        $sess_user_agency_es ?? '',
        $_SESSION['sess_user_agency_es'] ?? '',
        $_SESSION['sess_user_agency'] ?? '',
        $_SESSION['ag_id'] ?? ''
    ];

    foreach ($candidates as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function eval_session_user_id(): string
{
    $candidates = [
        $_SESSION['sess_user_id_es'] ?? '',
        $_SESSION['sess_user_id'] ?? '',
        $_SESSION['sess_user_login'] ?? '',
        $_SESSION['username'] ?? ''
    ];

    foreach ($candidates as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            return $value;
        }
    }

    return 'system';
}

function eval_score_label($score): string
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
            return '';
    }
}

function eval_thai_datetime(?string $value): string
{
    if (!$value || $value === '0000-00-00 00:00:00') {
        return '';
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

    $day = (int)date('j', $ts);
    $month = $months[(int)date('n', $ts)] ?? '';
    $year = (int)date('Y', $ts) + 543;
    $time = date('H:i', $ts);

    return "{$day} {$month} {$year}, {$time} น.";
}

function eval_fetch_repair(
    mysqli $connect,
    int $repairId,
    string $agId
): ?array {
    $sql = "
        SELECT
            id,
            ag_id,
            rp_format,
            name,
            phone,
            problem_detail,
            status,
            report_date,
            report_time,
            completed_date,
            completed_time,
            completed_solution,
            has_feedback,
            created_by,
            created_at,
            updated_by,
            updated_at
        FROM repair_requests
        WHERE id = ?
          AND ag_id = ?
        LIMIT 1
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare repair failed: ' . $connect->error);
    }

    $stmt->bind_param('is', $repairId, $agId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $row['id'] = (int)$row['id'];
    $row['has_feedback'] = (int)$row['has_feedback'];

    return $row;
}

function eval_fetch_evaluation(
    mysqli $connect,
    int $repairId,
    string $agId
): array {
    $sql = "
        SELECT
            id,
            rp_id,
            ag_id,
            round_no,
            status,
            score,
            result,
            comment,
            submitted_by_email,
            submitted_at,
            created_by,
            created_at,
            updated_by,
            updated_at
        FROM tb_repair_evaluation
        WHERE rp_id = ?
          AND ag_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare evaluation failed: ' . $connect->error);
    }

    $stmt->bind_param('is', $repairId, $agId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return [
            'id' => null,
            'rp_id' => $repairId,
            'ag_id' => $agId,
            'round_no' => 0,
            'status' => 'waiting',
            'score' => null,
            'score_label' => '',
            'result' => '',
            'comment' => '',
            'submitted_by_email' => '',
            'submitted_at' => null,
            'submitted_at_text' => ''
        ];
    }

    $row['id'] = (int)$row['id'];
    $row['round_no'] = (int)($row['round_no'] ?? 1);
    $row['score'] = $row['score'] !== null ? (int)$row['score'] : null;
    $row['score_label'] = eval_score_label($row['score']);
    $row['submitted_at_text'] = eval_thai_datetime($row['submitted_at']);

    return $row;
}

/**
 * ใช้รอบเดิมหากยังรอประเมิน
 * แต่ถ้ารอบล่าสุดประเมินเสร็จแล้ว จะสร้างรอบใหม่โดยไม่ลบประวัติเดิม
 */
function eval_get_or_create_evaluation(
    mysqli $connect,
    int $repairId,
    string $agId,
    string $createdBy
): array {
    $existing = eval_fetch_evaluation($connect, $repairId, $agId);

    if (
        !empty($existing['id'])
        && ($existing['status'] ?? '') !== 'completed'
    ) {
        return $existing;
    }

    $sql = "
        INSERT INTO tb_repair_evaluation (
            rp_id,
            ag_id,
            round_no,
            status,
            created_by
        )
        SELECT
            ?,
            ?,
            COALESCE(MAX(round_no), 0) + 1,
            'waiting',
            ?
        FROM tb_repair_evaluation
        WHERE rp_id = ?
          AND ag_id = ?
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception(
            'Prepare create evaluation round failed: ' . $connect->error
        );
    }

    $stmt->bind_param(
        'issis',
        $repairId,
        $agId,
        $createdBy,
        $repairId,
        $agId
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        // กันกรณีกดพร้อมกัน: อ่านรอบล่าสุดกลับมาอีกครั้ง
        $latest = eval_fetch_evaluation($connect, $repairId, $agId);

        if (
            !empty($latest['id'])
            && ($latest['status'] ?? '') !== 'completed'
        ) {
            return $latest;
        }

        throw new Exception(
            'Create evaluation round failed: ' . $error
        );
    }

    $stmt->close();

    return eval_fetch_evaluation($connect, $repairId, $agId);
}

function eval_resolve_recipient_status(
    array $row,
    bool $evaluationCompleted,
    bool $emailValid
): string {
    if (!$emailValid) {
        return 'invalid';
    }

    if ($evaluationCompleted) {
        return 'evaluated';
    }

    if (empty($row['last_log_id'])) {
        return 'not_sent';
    }

    if (($row['send_status'] ?? '') === 'failed') {
        return 'failed';
    }

    if (!empty($row['evaluated_at'])) {
        return 'evaluated';
    }

    if (!empty($row['clicked_at'])) {
        return 'opened';
    }

    if (($row['send_status'] ?? '') === 'sent') {
        return 'sent';
    }

    if (($row['send_status'] ?? '') === 'pending') {
        return 'pending';
    }

    return 'not_sent';
}

function eval_fetch_recipients_with_status(
    mysqli $connect,
    int $repairId,
    string $agId,
    int $evaluationId,
    bool $evaluationCompleted
): array {
    $sql = "
        SELECT
            n.id AS notify_id,
            n.name,
            n.position,
            n.department,
            n.division,
            n.email,
            n.phone,

            l.id AS last_log_id,
            l.send_status,
            l.sent_at,
            l.clicked_at,
            l.evaluated_at,
            l.error_message,
            l.created_at AS log_created_at

        FROM tb_email_notify_eval n

        LEFT JOIN tb_email_notify_eval_log l
          ON l.id = (
            SELECT MAX(l2.id)
            FROM tb_email_notify_eval_log l2
            WHERE l2.rp_id = ?
              AND l2.evaluation_id = ?
              AND l2.ag_id = n.ag_id
              AND l2.notify_id = n.id
          )

        WHERE n.ag_id = ?
          AND n.status = 1

        ORDER BY n.name ASC, n.id ASC
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare recipients failed: ' . $connect->error);
    }

    $stmt->bind_param('iis', $repairId, $evaluationId, $agId);
    $stmt->execute();

    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $email = strtolower(trim((string)($row['email'] ?? '')));
        $emailValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        $rows[] = [
            'notify_id' => (int)$row['notify_id'],
            'name' => $row['name'] ?? '',
            'position' => $row['position'] ?? '',
            'department' => $row['department'] ?? '',
            'division' => $row['division'] ?? '',
            'email' => $email,
            'phone' => $row['phone'] ?? '',
            'email_valid' => $emailValid,
            'status' => eval_resolve_recipient_status(
                $row,
                $evaluationCompleted,
                $emailValid
            ),
            'last_log_id' => !empty($row['last_log_id'])
                ? (int)$row['last_log_id']
                : null,
            'last_sent_at' => $row['sent_at'],
            'last_sent_at_text' => eval_thai_datetime($row['sent_at']),
            'clicked_at' => $row['clicked_at'],
            'clicked_at_text' => eval_thai_datetime($row['clicked_at']),
            'evaluated_at' => $row['evaluated_at'],
            'evaluated_at_text' => eval_thai_datetime($row['evaluated_at']),
            'last_error' => $row['error_message'] ?? ''
        ];
    }

    $stmt->close();

    // ไม่แสดงอีเมลซ้ำหลายแถว
    $unique = [];

    foreach ($rows as $row) {
        $key = trim(strtolower($row['email']));

        if ($key === '') {
            $key = 'notify-' . $row['notify_id'];
        }

        if (!isset($unique[$key])) {
            $unique[$key] = $row;
        }
    }

    return array_values($unique);
}

function eval_fetch_logs(
    mysqli $connect,
    int $repairId,
    string $agId,
    int $limit = 100
): array {
    $limit = max(1, min($limit, 500));

    $sql = "
        SELECT
            l.id,
            l.batch_id,
            l.evaluation_id,
            e.round_no,
            l.rp_id,
            l.rp_format,
            l.notify_id,
            l.ag_id,
            l.recipient_name,
            l.recipient_email,
            l.subject,
            l.send_status,
            l.attempt_no,
            l.error_message,
            l.provider_message_id,
            l.sent_at,
            l.clicked_at,
            l.evaluated_at,
            l.expires_at,
            l.created_at,
            l.created_by
        FROM tb_email_notify_eval_log l
        LEFT JOIN tb_repair_evaluation e
          ON e.id = l.evaluation_id
        WHERE l.rp_id = ?
          AND l.ag_id = ?
        ORDER BY l.id DESC
        LIMIT {$limit}
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare logs failed: ' . $connect->error);
    }

    $stmt->bind_param('is', $repairId, $agId);
    $stmt->execute();

    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['evaluation_id'] = (int)$row['evaluation_id'];
        $row['round_no'] = (int)($row['round_no'] ?? 1);
        $row['rp_id'] = (int)$row['rp_id'];
        $row['notify_id'] = (int)$row['notify_id'];
        $row['attempt_no'] = (int)$row['attempt_no'];
        $row['created_at_text'] = eval_thai_datetime($row['created_at']);
        $row['sent_at_text'] = eval_thai_datetime($row['sent_at']);
        $row['clicked_at_text'] = eval_thai_datetime($row['clicked_at']);
        $row['evaluated_at_text'] = eval_thai_datetime($row['evaluated_at']);

        if (!empty($row['evaluated_at'])) {
            $row['effective_status'] = 'evaluated';
        } elseif (!empty($row['clicked_at'])) {
            $row['effective_status'] = 'opened';
        } else {
            $row['effective_status'] = $row['send_status'];
        }

        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

function eval_build_summary(array $recipients): array
{
    $summary = [
        'total' => count($recipients),
        'not_sent' => 0,
        'pending' => 0,
        'sent' => 0,
        'opened' => 0,
        'failed' => 0,
        'evaluated' => 0,
        'invalid' => 0
    ];

    foreach ($recipients as $recipient) {
        $status = $recipient['status'] ?? 'not_sent';

        if (array_key_exists($status, $summary)) {
            $summary[$status]++;
        }
    }

    return $summary;
}

function eval_fetch_selected_recipients(
    mysqli $connect,
    string $agId,
    array $notifyIds
): array {
    $notifyIds = array_values(array_unique(array_filter(
		array_map('intval', $notifyIds),
		static function ($id) {
			return $id > 0;
		}
	)));

    if (!$notifyIds) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($notifyIds), '?'));

    $sql = "
        SELECT
            id,
            ag_id,
            name,
            position,
            department,
            division,
            email,
            phone,
            status
        FROM tb_email_notify_eval
        WHERE ag_id = ?
          AND status = 1
          AND id IN ({$placeholders})
        ORDER BY id ASC
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare selected recipients failed: ' . $connect->error);
    }

    $types = 's' . str_repeat('i', count($notifyIds));
    $params = array_merge([$agId], $notifyIds);

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $email = strtolower(trim((string)($row['email'] ?? '')));

        $row['id'] = (int)$row['id'];
        $row['email'] = $email;
        $row['email_valid'] = filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        ) !== false;

        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

function eval_next_attempt(
    mysqli $connect,
    int $evaluationId,
    int $repairId,
    int $notifyId
): int {
    $sql = "
        SELECT COALESCE(MAX(attempt_no), 0) + 1 AS next_attempt
        FROM tb_email_notify_eval_log
        WHERE evaluation_id = ?
          AND rp_id = ?
          AND notify_id = ?
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare attempt failed: ' . $connect->error);
    }

    $stmt->bind_param('iii', $evaluationId, $repairId, $notifyId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return max(1, (int)($row['next_attempt'] ?? 1));
}

function eval_create_log(
    mysqli $connect,
    array $data
): int {
    $sql = "
        INSERT INTO tb_email_notify_eval_log (
            batch_id,
            evaluation_id,
            rp_id,
            rp_format,
            notify_id,
            ag_id,
            recipient_name,
            recipient_email,
            subject,
            access_token_hash,
            expires_at,
            send_status,
            attempt_no,
            created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare log failed: ' . $connect->error);
    }

    $stmt->bind_param(
        'siisisssssssis',
        $data['batch_id'],
        $data['evaluation_id'],
        $data['rp_id'],
        $data['rp_format'],
        $data['notify_id'],
        $data['ag_id'],
        $data['recipient_name'],
        $data['recipient_email'],
        $data['subject'],
        $data['token_hash'],
        $data['expires_at'],
        $data['send_status'],
        $data['attempt_no'],
        $data['created_by']
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Insert log failed: ' . $error);
    }

    $logId = (int)$connect->insert_id;
    $stmt->close();

    return $logId;
}

function eval_mark_sent(
    mysqli $connect,
    int $logId,
    string $providerMessageId = ''
): void {
    $sql = "
        UPDATE tb_email_notify_eval_log
        SET
            send_status = 'sent',
            provider_message_id = ?,
            error_message = NULL,
            sent_at = NOW()
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare mark sent failed: ' . $connect->error);
    }

    $stmt->bind_param('si', $providerMessageId, $logId);
    $stmt->execute();
    $stmt->close();
}

function eval_mark_failed(
    mysqli $connect,
    int $logId,
    string $errorMessage
): void {
    $errorMessage = trim($errorMessage);

    if (function_exists('mb_substr')) {
        $errorMessage = mb_substr($errorMessage, 0, 5000, 'UTF-8');
    } else {
        $errorMessage = substr($errorMessage, 0, 5000);
    }

    $sql = "
        UPDATE tb_email_notify_eval_log
        SET
            send_status = 'failed',
            error_message = ?,
            sent_at = NULL
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $connect->prepare($sql);

    if (!$stmt) {
        return;
    }

    $stmt->bind_param('si', $errorMessage, $logId);
    $stmt->execute();
    $stmt->close();
}

function eval_public_url(string $plainToken): string
{
    return rtrim(REPAIR_EVAL_PUBLIC_URL, '?&')
        . '?t='
        . urlencode($plainToken);
}

function eval_mail_html(
    array $recipient,
    array $repair,
    string $evaluationUrl,
    string $expiresAt
): string {
    $name = htmlspecialchars(
        trim((string)($recipient['name'] ?? 'ผู้ประเมิน')) ?: 'ผู้ประเมิน',
        ENT_QUOTES,
        'UTF-8'
    );

    $rpFormat = htmlspecialchars(
        (string)($repair['rp_format'] ?? ('#' . $repair['id'])),
        ENT_QUOTES,
        'UTF-8'
    );

    $problem = htmlspecialchars(
        trim((string)($repair['problem_detail'] ?? '')) ?: '-',
        ENT_QUOTES,
        'UTF-8'
    );

    $solution = htmlspecialchars(
        trim((string)($repair['completed_solution'] ?? '')) ?: '-',
        ENT_QUOTES,
        'UTF-8'
    );

    $url = htmlspecialchars($evaluationUrl, ENT_QUOTES, 'UTF-8');
    $expireText = htmlspecialchars(
        eval_thai_datetime($expiresAt),
        ENT_QUOTES,
        'UTF-8'
    );

    return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แบบประเมินงานซ่อม</title>
</head>
<body style="margin:0;background:#eef3f8;font-family:Arial,Tahoma,sans-serif;color:#334155;">
  <div style="max-width:640px;margin:32px auto;padding:0 14px;">
    <div style="background:#ffffff;border:1px solid #dbe5ef;border-radius:20px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,.08);">
      <div style="padding:24px 28px;background:linear-gradient(135deg,#006B9F,#04ADFF);color:#ffffff;">
        <div style="font-size:22px;font-weight:700;">แบบประเมินงานซ่อม</div>
        <div style="font-size:13px;margin-top:7px;opacity:.9;">เลขที่ใบงาน {$rpFormat}</div>
      </div>

      <div style="padding:28px;">
        <p style="margin-top:0;">เรียน {$name}</p>
        <p style="line-height:1.7;">
          งานซ่อมรายการนี้ดำเนินการเสร็จเรียบร้อยแล้ว
          กรุณาตรวจสอบและประเมินผลการให้บริการ
        </p>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:16px;margin:20px 0;">
          <div style="font-size:12px;color:#64748b;font-weight:700;">รายละเอียดปัญหา</div>
          <div style="font-size:14px;margin-top:5px;">{$problem}</div>

          <div style="font-size:12px;color:#64748b;font-weight:700;margin-top:14px;">ผลการดำเนินงาน</div>
          <div style="font-size:14px;margin-top:5px;">{$solution}</div>
        </div>

        <div style="text-align:center;margin:28px 0;">
          <a href="{$url}"
             style="display:inline-block;padding:14px 30px;background:#059669;color:#ffffff;text-decoration:none;border-radius:12px;font-weight:700;">
            ทำแบบประเมิน
          </a>
        </div>

        <div style="font-size:12px;color:#64748b;line-height:1.6;">
          ลิงก์นี้หมดอายุ {$expireText}<br>
          แบบประเมินหนึ่งใบงานสามารถบันทึกได้เพียงหนึ่งครั้ง
        </div>
      </div>
    </div>
  </div>
</body>
</html>
HTML;
}

function eval_load_phpmailer(&$errorMessage)
{
    $errorMessage = '';

    if (class_exists('PHPMailer', false)) {
        return 'legacy';
    }

    if (class_exists('\PHPMailer\PHPMailer\PHPMailer', false)) {
        return 'namespaced';
    }

    $candidates = [];

    if (defined('REPAIR_EVAL_PHPMAILER_FILE')) {
        $configured = trim((string)REPAIR_EVAL_PHPMAILER_FILE);
        if ($configured !== '') {
            $candidates[] = $configured;
        }
    }

    $candidates = array_merge($candidates, [
        __DIR__ . '/PHPMailer_v5.0.2/class.phpmailer.php',
        __DIR__ . '/PHPMailer/class.phpmailer.php',
        __DIR__ . '/phpmailer/class.phpmailer.php',
        __DIR__ . '/class.phpmailer.php',
        __DIR__ . '/include/class.phpmailer.php',
        __DIR__ . '/config_ctrl/class.phpmailer.php'
    ]);

    foreach ($candidates as $mailerFile) {
        if (!is_file($mailerFile)) {
            continue;
        }

        $smtpFile = dirname($mailerFile) . '/class.smtp.php';
        if (is_file($smtpFile)) {
            require_once $smtpFile;
        }

        require_once $mailerFile;

        if (class_exists('PHPMailer', false)) {
            return 'legacy';
        }
    }

    $autoloadCandidates = [
        __DIR__ . '/vendor/autoload.php',
        dirname(__DIR__) . '/vendor/autoload.php'

    ];

    foreach ($autoloadCandidates as $autoloadFile) {
        if (!is_file($autoloadFile)) {
            continue;
        }

        require_once $autoloadFile;

        if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
            return 'namespaced';
        }
    }

    $errorMessage = 'ไม่พบ PHPMailer กรุณาตรวจตำแหน่ง class.phpmailer.php หรือ vendor/autoload.php';
    return '';
}

function eval_send_mail(
    string $to,
    string $subject,
    string $htmlBody,
    string &$providerMessageId,
    string &$errorMessage
): bool {
    $providerMessageId = '';
    $errorMessage = '';
    $to = trim($to);

    if ($to === '' || $to === '-') {
        $errorMessage = 'ไม่พบอีเมลผู้รับ';
        return false;
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'รูปแบบอีเมลผู้รับไม่ถูกต้อง';
        return false;
    }

    $mailerMode = eval_load_phpmailer($errorMessage);
    if ($mailerMode === '') {
        return false;
    }

    try {
        if ($mailerMode === 'namespaced') {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'tls';
            $mail->Host = ZOHO_SMTP_HOST;
            $mail->Port = ZOHO_SMTP_PORT;
            $mail->Username = ZOHO_SMTP_USER;
            $mail->Password = ZOHO_SMTP_PASS;
            $mail->Timeout = 30;
            $mail->SMTPKeepAlive = false;

            $mail->setFrom(SENDER_EMAIL, REPAIR_EVAL_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = trim(html_entity_decode(
                strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)),
                ENT_QUOTES,
                'UTF-8'
            ));

            $mail->send();

            if (method_exists($mail, 'getLastMessageID')) {
                $providerMessageId = (string)$mail->getLastMessageID();
            }

            return true;
        }

        // PHPMailer รุ่นเก่าของระบบ
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->CharSet = 'utf-8';
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host = ZOHO_SMTP_HOST;
        $mail->Port = ZOHO_SMTP_PORT;
        $mail->Username = ZOHO_SMTP_USER;
        $mail->Password = ZOHO_SMTP_PASS;
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;

        $mail->From = SENDER_EMAIL;
        $mail->FromName = REPAIR_EVAL_FROM_NAME;
        $mail->AddAddress($to);
        $mail->IsHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(html_entity_decode(
            strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)),
            ENT_QUOTES,
            'UTF-8'
        ));

        if ($mail->Send()) {
            return true;
        }

        $errorMessage = trim((string)$mail->ErrorInfo);
        if ($errorMessage === '') {
            $errorMessage = 'PHPMailer ส่งอีเมลไม่สำเร็จ';
        }

        return false;

    } catch (Exception $error) {
        $errorMessage = $error->getMessage();
        return false;
    } catch (Throwable $error) {
        $errorMessage = $error->getMessage();
        return false;
    }
}

function eval_process_send_batch(
    mysqli $connect,
    array $repair,
    array $evaluation,
    array $recipients,
    string $agId,
    string $createdBy
): array {
    $batchId = 'EV-'
        . date('YmdHis')
        . '-'
        . strtoupper(bin2hex(random_bytes(3)));

    $repairId = (int)$repair['id'];
    $rpFormat = trim((string)($repair['rp_format'] ?? ''));

    if ($rpFormat === '') {
        $rpFormat = '#' . $repairId;
    }

    $roundNo = max(1, (int)($evaluation['round_no'] ?? 1));
    $subject = 'แบบประเมินงานซ่อม ' . $rpFormat . ' (รอบที่ ' . $roundNo . ')';

    $sentCount = 0;
    $failedCount = 0;
    $results = [];

    foreach ($recipients as $recipient) {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $attemptNo = eval_next_attempt(
            $connect,
            (int)$evaluation['id'],
            $repairId,
            (int)$recipient['id']
        );

        $emailValid = !empty($recipient['email_valid']);

        $logId = eval_create_log(
            $connect,
            [
                'batch_id' => $batchId,
                'evaluation_id' => (int)$evaluation['id'],
                'rp_id' => $repairId,
                'rp_format' => $rpFormat,
                'notify_id' => (int)$recipient['id'],
                'ag_id' => $agId,
                'recipient_name' => $recipient['name'] ?? '',
                'recipient_email' => $recipient['email'] ?? '',
                'subject' => $subject,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'send_status' => $emailValid ? 'pending' : 'failed',
                'attempt_no' => $attemptNo,
                'created_by' => $createdBy
            ]
        );

        if (!$emailValid) {
            eval_mark_failed(
                $connect,
                $logId,
                'รูปแบบอีเมลไม่ถูกต้อง'
            );

            $failedCount++;

            $results[] = [
                'notify_id' => (int)$recipient['id'],
                'name' => $recipient['name'] ?? '',
                'email' => $recipient['email'] ?? '',
                'status' => 'failed',
                'error' => 'รูปแบบอีเมลไม่ถูกต้อง'
            ];

            continue;
        }

        $evaluationUrl = eval_public_url($plainToken);

        $htmlBody = eval_mail_html(
            $recipient,
            $repair,
            $evaluationUrl,
            $expiresAt
        );

        $providerMessageId = '';
        $mailError = '';

        $mailSent = eval_send_mail(
            $recipient['email'],
            $subject,
            $htmlBody,
            $providerMessageId,
            $mailError
        );

        if ($mailSent) {
            eval_mark_sent(
                $connect,
                $logId,
                $providerMessageId
            );

            $sentCount++;

            $results[] = [
                'notify_id' => (int)$recipient['id'],
                'name' => $recipient['name'] ?? '',
                'email' => $recipient['email'],
                'status' => 'sent',
                'error' => ''
            ];
        } else {
            eval_mark_failed(
                $connect,
                $logId,
                $mailError ?: 'ส่งอีเมลไม่สำเร็จ'
            );

            $failedCount++;

            $results[] = [
                'notify_id' => (int)$recipient['id'],
                'name' => $recipient['name'] ?? '',
                'email' => $recipient['email'],
                'status' => 'failed',
                'error' => $mailError ?: 'ส่งอีเมลไม่สำเร็จ'
            ];
        }
    }

    return [
        'batch_id' => $batchId,
        'evaluation_id' => (int)$evaluation['id'],
        'round_no' => $roundNo,
        'recipient_count' => count($recipients),
        'sent_count' => $sentCount,
        'failed_count' => $failedCount,
        'results' => $results
    ];
}

/* =========================================================
 * Routing
 * ========================================================= */

$action = trim((string)($_GET['action'] ?? ''));

if ($action === '') {
    eval_json_response(false, 'Missing action', null, 400);
}

/* ---------------------------------------------------------
 * GET status
 * --------------------------------------------------------- */
if ($action === 'status') {
    try {
        $repairId = (int)($_GET['id'] ?? 0);
        $agId = eval_session_ag_id();

        if ($repairId <= 0) {
            throw new Exception('ไม่พบรหัสใบงาน');
        }

        if ($agId === '') {
            throw new Exception('ไม่พบข้อมูลหน่วยงานจาก Session');
        }

        $repair = eval_fetch_repair($connect, $repairId, $agId);

        if (!$repair) {
            throw new Exception(
                'ไม่พบใบงาน หรือใบงานไม่ได้อยู่ในหน่วยงานนี้'
            );
        }

        $evaluation = eval_fetch_evaluation(
            $connect,
            $repairId,
            $agId
        );

        // ใช้สถานะของ "รอบล่าสุด" เท่านั้น
        // has_feedback เป็นเพียงประวัติว่าเคยมีการประเมินแล้ว
        $evaluationCompleted =
            ($evaluation['status'] ?? '') === 'completed';

        $recipients = eval_fetch_recipients_with_status(
            $connect,
            $repairId,
            $agId,
            (int)($evaluation['id'] ?? 0),
            $evaluationCompleted
        );

        $logs = eval_fetch_logs(
            $connect,
            $repairId,
            $agId,
            100
        );

        $summary = eval_build_summary($recipients);

        $validRecipientCount = count(array_filter(
			$recipients,
			static function ($item) {
				return !empty($item['email_valid']);
			}
		));

        $repairStatus = strtolower(
            trim((string)$repair['status'])
        );

        $canSend = true;
        $canSendReason = '';

        if (!in_array($repairStatus, ['completed', 'feedback'], true)) {
            $canSend = false;
            $canSendReason = 'ต้องปิดงานให้เป็นสถานะเสร็จสิ้นก่อน';
        } elseif ($validRecipientCount === 0) {
            $canSend = false;
            $canSendReason = 'ไม่พบผู้รับอีเมลที่ถูกต้องในหน่วยงานนี้';
        }

        $sendMode = $evaluationCompleted
            ? 'new_round'
            : 'current_round';

        eval_json_response(
            true,
            'โหลดสถานะแบบประเมินเรียบร้อย',
            [
                'can_send' => $canSend,
                'can_send_reason' => $canSendReason,
                'send_mode' => $sendMode,
                'has_previous_evaluation' => (int)$repair['has_feedback'] === 1,
                'repair' => $repair,
                'evaluation' => $evaluation,
                'summary' => $summary,
                'recipients' => $recipients,
                'logs' => $logs
            ]
        );

    } catch (\Throwable $error) {
        eval_json_response(
            false,
            $error->getMessage(),
            null,
            400
        );
    }
}

/* ---------------------------------------------------------
 * POST send
 * --------------------------------------------------------- */
if ($action === 'send') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        eval_json_response(
            false,
            'Method not allowed',
            null,
            405
        );
    }

    try {
        $repairId = (int)($_POST['id'] ?? 0);
        $notifyIds = $_POST['notify_ids'] ?? [];

        if (!is_array($notifyIds)) {
            $notifyIds = [$notifyIds];
        }

        $notifyIds = array_values(array_unique(array_filter(
			array_map('intval', $notifyIds),
			static function ($id) {
				return $id > 0;
			}
		)));

        $agId = eval_session_ag_id();
        $createdBy = eval_session_user_id();

        if ($repairId <= 0) {
            throw new Exception('ไม่พบรหัสใบงาน');
        }

        if ($agId === '') {
            throw new Exception('ไม่พบข้อมูลหน่วยงานจาก Session');
        }

        if (!$notifyIds) {
            throw new Exception(
                'กรุณาเลือกผู้รับอีเมลอย่างน้อย 1 คน'
            );
        }

        $repair = eval_fetch_repair(
            $connect,
            $repairId,
            $agId
        );

        if (!$repair) {
            throw new Exception('ไม่พบใบงานในหน่วยงานนี้');
        }

        $repairStatus = strtolower(
            trim((string)$repair['status'])
        );

        if (!in_array($repairStatus, ['completed', 'feedback'], true)) {
            throw new Exception(
                'สามารถส่งแบบประเมินได้เฉพาะใบงานที่ปิดงานแล้ว'
            );
        }

        $evaluation = eval_get_or_create_evaluation(
            $connect,
            $repairId,
            $agId,
            $createdBy
        );

        $recipients = eval_fetch_selected_recipients(
            $connect,
            $agId,
            $notifyIds
        );

        if (!$recipients) {
            throw new Exception(
                'ไม่พบผู้รับอีเมลที่เปิดใช้งาน'
            );
        }

        // ต้องพบทุก ID ที่หน้าเว็บส่งมา ป้องกันข้ามหน่วยงาน/ปิดใช้งาน
        if (count($recipients) !== count($notifyIds)) {
            throw new Exception(
                'มีผู้รับบางรายการไม่ถูกต้อง ไม่เปิดใช้งาน หรืออยู่นอกหน่วยงาน'
            );
        }

        $result = eval_process_send_batch(
            $connect,
            $repair,
            $evaluation,
            $recipients,
            $agId,
            $createdBy
        );

        eval_json_response(
            true,
            ((int)($result['round_no'] ?? 1) > 1)
                ? 'ส่งแบบประเมินรอบใหม่เรียบร้อย'
                : 'ดำเนินการส่งอีเมลเรียบร้อย',
            $result
        );

    } catch (\Throwable $error) {
        eval_json_response(
            false,
            $error->getMessage(),
            null,
            400
        );
    }
}

eval_json_response(false, 'Unknown action', null, 404);