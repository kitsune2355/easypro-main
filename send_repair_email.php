<?php

@session_start();

header("Content-Type: application/json; charset=utf-8");
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/config_ctrl/connect.php';
require_once __DIR__ . '/PHPMailer_v5.0.2/class.phpmailer.php';
require_once __DIR__ . '/PHPMailer_v5.0.2/class.smtp.php';

/**
 * ส่ง JSON และจบการทำงาน
 */
function respondJson($connect, $statusCode, array $payload)
{
    http_response_code($statusCode);

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($connect instanceof mysqli) {
        $connect->close();
    }

    exit();
}

/**
 * แปลงข้อความเป็น HTML อย่างปลอดภัย
 */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| ตรวจสอบ Request
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson($connect ?? null, 405, [
        'status'  => 'error',
        'message' => 'Method not allowed'
    ]);
}

if (!isset($connect) || !($connect instanceof mysqli) || $connect->connect_errno) {
    respondJson($connect ?? null, 500, [
        'status'  => 'error',
        'message' => 'Database connection failed'
    ]);
}

$repairId = isset($_POST['repair_id'])
    ? (int) $_POST['repair_id']
    : 0;

if ($repairId <= 0) {
    respondJson($connect, 400, [
        'status'  => 'error',
        'message' => 'Invalid repair_id'
    ]);
}

/*
|--------------------------------------------------------------------------
| ดึงข้อมูลแจ้งซ่อม
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        rr.id,
        rr.rp_format,
        rr.name,
        rr.phone,
        rr.problem_detail,
        rr.urgency,
        rr.ag_id,
        ta.area_name AS building_name,
        tac.ac_name AS floor_name,
        tar.ar_name AS room_name
    FROM repair_requests rr
    LEFT JOIN tb_area ta
        ON rr.building = ta.area_id
    LEFT JOIN tb_area_class tac
        ON rr.floor = tac.ac_id
    LEFT JOIN tb_area_room tar
        ON rr.room = tar.ar_id
    WHERE rr.id = ?
    LIMIT 1
";

$stmt = $connect->prepare($sql);

if (!$stmt) {
    error_log(
        'send_repair_email.php repair prepare error: ' .
        $connect->error
    );

    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'Unable to prepare repair query'
    ]);
}

$stmt->bind_param('i', $repairId);

if (!$stmt->execute()) {
    $stmtError = $stmt->error;
    $stmt->close();

    error_log(
        'send_repair_email.php repair execute error: ' .
        $stmtError
    );

    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'Unable to load repair request'
    ]);
}

$result = $stmt->get_result();
$repair = $result->fetch_assoc();

$stmt->close();

if (!$repair) {
    respondJson($connect, 404, [
        'status'  => 'error',
        'message' => 'Repair request not found'
    ]);
}

/*
|--------------------------------------------------------------------------
| ดึงรายชื่อผู้รับอีเมล
|--------------------------------------------------------------------------
*/

$sqlEmail = "
    SELECT name, email
    FROM tb_email_notify
    WHERE ag_id = ?
      AND status = 1
      AND email IS NOT NULL
      AND TRIM(email) != ''
";

$stmtEmail = $connect->prepare($sqlEmail);

if (!$stmtEmail) {
    error_log(
        'send_repair_email.php recipient prepare error: ' .
        $connect->error
    );

    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'Unable to prepare recipient query'
    ]);
}

$stmtEmail->bind_param('s', $repair['ag_id']);

if (!$stmtEmail->execute()) {
    $stmtEmailError = $stmtEmail->error;
    $stmtEmail->close();

    error_log(
        'send_repair_email.php recipient execute error: ' .
        $stmtEmailError
    );

    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'Unable to load email recipients'
    ]);
}

$emailResult = $stmtEmail->get_result();
$recipients = [];

while ($row = $emailResult->fetch_assoc()) {
    $email = trim((string) ($row['email'] ?? ''));

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $recipients[] = [
            'name'  => trim((string) ($row['name'] ?? '')),
            'email' => $email
        ];
    }
}

$stmtEmail->close();

if (empty($recipients)) {
    respondJson($connect, 200, [
        'status'          => 'success',
        'message'         => 'No email recipients',
        'recipient_count' => 0
    ]);
}

/*
|--------------------------------------------------------------------------
| เตรียมข้อความและรูปแบบ
|--------------------------------------------------------------------------
*/

$urgencyMap = [
    'critical' => '🔴 เร่งด่วนมาก',
    'high'     => '🟠 สูง',
    'medium'   => '🟡 ปานกลาง',
    'low'      => '🟢 ต่ำ'
];

$urgencyStyles = [
    'critical' => [
        'background' => '#fef2f2',
        'color'      => '#b91c1c',
        'border'     => '#fecaca'
    ],
    'high' => [
        'background' => '#fff7ed',
        'color'      => '#c2410c',
        'border'     => '#fed7aa'
    ],
    'medium' => [
        'background' => '#fffbeb',
        'color'      => '#a16207',
        'border'     => '#fde68a'
    ],
    'low' => [
        'background' => '#ecfdf5',
        'color'      => '#047857',
        'border'     => '#a7f3d0'
    ]
];

$urgencyKey = strtolower(trim((string) ($repair['urgency'] ?? '')));

$urgencyText = $urgencyMap[$urgencyKey] ?? 'ไม่ระบุ';

$urgencyStyle = $urgencyStyles[$urgencyKey] ?? [
    'background' => '#f8fafc',
    'color'      => '#475569',
    'border'     => '#e2e8f0'
];

$locationParts = array_filter([
    trim((string) ($repair['building_name'] ?? '')),
    trim((string) ($repair['floor_name'] ?? '')),
    trim((string) ($repair['room_name'] ?? ''))
]);

$locationText = !empty($locationParts)
    ? implode(' / ', $locationParts)
    : '-';

$systemLink =
    'https://happylandgroup.biz/es/main.php#maintenance_info.php?id=' .
    $repairId;

/*
|--------------------------------------------------------------------------
| ตั้งค่า SMTP
|--------------------------------------------------------------------------
|
| แนะนำให้กำหนด SMTP_USERNAME และ SMTP_PASSWORD ในไฟล์ config
|
| ตัวอย่าง:
| define('SMTP_USERNAME', 'no-reply@proactivemanagement.co.th');
| define('SMTP_PASSWORD', 'APP_PASSWORD_NEW');
|
*/

$smtpUsername = defined('SMTP_USERNAME')
    ? SMTP_USERNAME
    : 'no-reply@proactivemanagement.co.th';

$smtpPassword = defined('SMTP_PASSWORD')
    ? SMTP_PASSWORD
    : 'Pass@x1pro';

if ($smtpPassword === '') {
    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'SMTP password is not configured'
    ]);
}

$mail = new PHPMailer(true);

$sentCount = 0;
$failedRecipients = [];

try {
    $mail->CharSet = 'utf-8';
    $mail->IsSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = 'smtp.zoho.com';
    $mail->Port = 587;
    $mail->SMTPSecure = 'tls';

    $mail->SMTPKeepAlive = true;
    $mail->Timeout = 30;

    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;

    $mail->SetFrom(
        $smtpUsername,
        'Maintenance System'
    );

    foreach ($recipients as $recipient) {
        $mail->ClearAddresses();
        $mail->ClearAttachments();
        $mail->ClearCustomHeaders();

        $recipientName = $recipient['name'] !== ''
            ? $recipient['name']
            : 'ผู้เกี่ยวข้อง';

        $mail->AddAddress(
            $recipient['email'],
            $recipientName
        );

        $safeRecipientName = e($recipientName);
        $safeRpFormat = e($repair['rp_format'] ?? '-');
        $safeReporterName = e($repair['name'] ?? '-');
        $safePhone = e(
            trim((string) ($repair['phone'] ?? '')) !== ''
                ? $repair['phone']
                : '-'
        );

        $safeProblemDetail = nl2br(
            e($repair['problem_detail'] ?? '-')
        );

        $safeLocation = e($locationText);
        $safeUrgency = e($urgencyText);
        $safeSystemLink = e($systemLink);

        $urgencyBackground = $urgencyStyle['background'];
        $urgencyColor = $urgencyStyle['color'];
        $urgencyBorder = $urgencyStyle['border'];

        $body = <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งเตือนการแจ้งซ่อมใหม่</title>
</head>
<body style="
    margin:0;
    padding:0;
    background-color:#f1f5f9;
    font-family:Arial,Tahoma,sans-serif;
    color:#334155;
">
<table role="presentation"
       width="100%"
       cellspacing="0"
       cellpadding="0"
       border="0"
       style="width:100%;background-color:#f1f5f9;padding:30px 12px;">
    <tr>
        <td align="center">
            <table role="presentation"
                   width="100%"
                   cellspacing="0"
                   cellpadding="0"
                   border="0"
                   style="
                       width:100%;
                       max-width:680px;
                       background:#ffffff;
                       border-radius:18px;
                       overflow:hidden;
                       border:1px solid #e2e8f0;
                       box-shadow:0 12px 30px rgba(15,23,42,0.08);
                   ">
                <tr>
                    <td style="
                        padding:30px;
                        background-color:#006B9F;
                        background-image:linear-gradient(135deg,#006B9F,#04ADFF);
                        color:#ffffff;
                    ">
                        <div style="
                            margin-bottom:8px;
                            font-size:13px;
                            font-weight:bold;
                            letter-spacing:1px;
                            opacity:0.88;
                        ">
                            MAINTENANCE SYSTEM
                        </div>

                        <div style="
                            font-size:26px;
                            font-weight:bold;
                            line-height:1.4;
                        ">
                            แจ้งเตือนการแจ้งซ่อมใหม่
                        </div>

                        <div style="
                            margin-top:10px;
                            font-size:14px;
                            line-height:1.7;
                            color:#e0f2fe;
                        ">
                            มีรายการแจ้งซ่อมใหม่เข้าสู่ระบบ กรุณาตรวจสอบและดำเนินการ
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:30px;">
                        <p style="
                            margin:0 0 10px;
                            font-size:15px;
                            line-height:1.8;
                        ">
                            เรียน
                            <strong style="color:#0f172a;">
                                {$safeRecipientName}
                            </strong>
                        </p>

                        <p style="
                            margin:0 0 24px;
                            font-size:14px;
                            line-height:1.8;
                            color:#64748b;
                        ">
                            ระบบได้รับรายการแจ้งซ่อมใหม่ โดยมีรายละเอียดดังต่อไปนี้
                        </p>

                        <table role="presentation"
                               width="100%"
                               cellspacing="0"
                               cellpadding="0"
                               border="0"
                               style="
                                   width:100%;
                                   margin-bottom:18px;
                                   background:#eff6ff;
                                   border:1px solid #bfdbfe;
                                   border-radius:12px;
                               ">
                            <tr>
                                <td style="padding:18px 20px;">
                                    <div style="
                                        margin-bottom:6px;
                                        font-size:11px;
                                        font-weight:bold;
                                        color:#64748b;
                                        letter-spacing:0.8px;
                                    ">
                                        เลขที่เอกสาร
                                    </div>

                                    <div style="
                                        font-size:21px;
                                        font-weight:bold;
                                        color:#006B9F;
                                    ">
                                        {$safeRpFormat}
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <table role="presentation"
                               width="100%"
                               cellspacing="0"
                               cellpadding="0"
                               border="0"
                               style="
                                   width:100%;
                                   border-collapse:separate;
                                   border-spacing:0;
                                   border:1px solid #e2e8f0;
                                   border-radius:12px;
                                   overflow:hidden;
                               ">
                            <tr>
                                <td width="32%"
                                    valign="top"
                                    style="
                                        padding:14px 16px;
                                        background:#f8fafc;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:13px;
                                        font-weight:bold;
                                        color:#64748b;
                                    ">
                                    ผู้แจ้ง
                                </td>

                                <td valign="top"
                                    style="
                                        padding:14px 16px;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:14px;
                                        font-weight:bold;
                                        color:#0f172a;
                                    ">
                                    {$safeReporterName}
                                </td>
                            </tr>

                            <tr>
                                <td width="32%"
                                    valign="top"
                                    style="
                                        padding:14px 16px;
                                        background:#f8fafc;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:13px;
                                        font-weight:bold;
                                        color:#64748b;
                                    ">
                                    เบอร์ติดต่อ
                                </td>

                                <td valign="top"
                                    style="
                                        padding:14px 16px;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:14px;
                                        color:#334155;
                                    ">
                                    {$safePhone}
                                </td>
                            </tr>

                            <tr>
                                <td width="32%"
                                    valign="top"
                                    style="
                                        padding:14px 16px;
                                        background:#f8fafc;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:13px;
                                        font-weight:bold;
                                        color:#64748b;
                                    ">
                                    สถานที่
                                </td>

                                <td valign="top"
                                    style="
                                        padding:14px 16px;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:14px;
                                        line-height:1.7;
                                        color:#334155;
                                    ">
                                    {$safeLocation}
                                </td>
                            </tr>

                            <tr>
                                <td width="32%"
                                    valign="top"
                                    style="
                                        padding:14px 16px;
                                        background:#f8fafc;
                                        border-bottom:1px solid #e2e8f0;
                                        font-size:13px;
                                        font-weight:bold;
                                        color:#64748b;
                                    ">
                                    ความเร่งด่วน
                                </td>

                                <td valign="top"
                                    style="
                                        padding:14px 16px;
                                        border-bottom:1px solid #e2e8f0;
                                    ">
                                    <span style="
                                        display:inline-block;
                                        padding:7px 12px;
                                        border-radius:999px;
                                        background:{$urgencyBackground};
                                        color:{$urgencyColor};
                                        border:1px solid {$urgencyBorder};
                                        font-size:13px;
                                        font-weight:bold;
                                    ">
                                        {$safeUrgency}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td width="32%"
                                    valign="top"
                                    style="
                                        padding:14px 16px;
                                        background:#f8fafc;
                                        font-size:13px;
                                        font-weight:bold;
                                        color:#64748b;
                                    ">
                                    รายละเอียดปัญหา
                                </td>

                                <td valign="top"
                                    style="
                                        padding:14px 16px;
                                        font-size:14px;
                                        line-height:1.8;
                                        color:#334155;
                                    ">
                                    {$safeProblemDetail}
                                </td>
                            </tr>
                        </table>

                        <table role="presentation"
                               width="100%"
                               cellspacing="0"
                               cellpadding="0"
                               border="0"
                               style="width:100%;margin-top:28px;">
                            <tr>
                                <td align="center">
                                    <a href="{$safeSystemLink}"
                                       target="_blank"
                                       style="
                                           display:inline-block;
                                           padding:14px 26px;
                                           background:#006B9F;
                                           color:#ffffff;
                                           text-decoration:none;
                                           border-radius:10px;
                                           font-size:14px;
                                           font-weight:bold;
                                           box-shadow:0 8px 18px rgba(0,107,159,0.22);
                                       ">
                                        ดูรายละเอียดการแจ้งซ่อม
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="
                            margin:26px 0 0;
                            padding-top:20px;
                            border-top:1px solid #e2e8f0;
                            font-size:12px;
                            line-height:1.7;
                            color:#94a3b8;
                            text-align:center;
                        ">
                            อีเมลฉบับนี้ส่งจากระบบอัตโนมัติ กรุณาอย่าตอบกลับอีเมลนี้
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:18px 25px;
                        background:#0f172a;
                        color:#cbd5e1;
                        text-align:center;
                        font-size:12px;
                        line-height:1.6;
                    ">
                        Maintenance System<br>
                        Proactive Management
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
HTML;

        $mail->Subject =
            'แจ้งเตือนงานซ่อมใหม่: ' .
            ($repair['rp_format'] ?? '-');

        $mail->MsgHTML($body);

        $mail->AltBody =
            "แจ้งเตือนการแจ้งซ่อมใหม่\n" .
            "เลขที่เอกสาร: " . ($repair['rp_format'] ?? '-') . "\n" .
            "ผู้แจ้ง: " . ($repair['name'] ?? '-') . "\n" .
            "เบอร์ติดต่อ: " .
                (
                    trim((string) ($repair['phone'] ?? '')) !== ''
                        ? $repair['phone']
                        : '-'
                ) .
                "\n" .
            "สถานที่: " . $locationText . "\n" .
            "ความเร่งด่วน: " . $urgencyText . "\n" .
            "รายละเอียด: " . ($repair['problem_detail'] ?? '-') . "\n" .
            "ดูรายละเอียด: " . $systemLink;

        try {
            $mail->Send();
            $sentCount++;
        } catch (Exception $sendException) {
            $failedRecipients[] = $recipient['email'];

            error_log(
                'send_repair_email.php send failed, repair_id=' .
                $repairId .
                ', recipient=' .
                $recipient['email'] .
                ': ' .
                $sendException->getMessage()
            );
        }
    }

    $mail->SmtpClose();

    if ($sentCount <= 0) {
        respondJson($connect, 500, [
            'status'            => 'error',
            'message'           => 'Unable to send email',
            'recipient_count'   => count($recipients),
            'sent_count'        => 0,
            'failed_recipients' => $failedRecipients
        ]);
    }

    respondJson($connect, 200, [
        'status'            => 'success',
        'message'           => 'Email sent',
        'recipient_count'   => count($recipients),
        'sent_count'        => $sentCount,
        'failed_count'      => count($failedRecipients),
        'failed_recipients' => $failedRecipients
    ]);

} catch (phpmailerException $e) {
    if ($mail->SMTPKeepAlive) {
        $mail->SmtpClose();
    }

    error_log(
        'send_repair_email.php PHPMailer error, repair_id=' .
        $repairId .
        ': ' .
        $e->errorMessage()
    );

    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'Unable to send email'
    ]);

} catch (Exception $e) {
    if ($mail->SMTPKeepAlive) {
        $mail->SmtpClose();
    }

    error_log(
        'send_repair_email.php error, repair_id=' .
        $repairId .
        ': ' .
        $e->getMessage()
    );

    respondJson($connect, 500, [
        'status'  => 'error',
        'message' => 'Unable to send email'
    ]);
}