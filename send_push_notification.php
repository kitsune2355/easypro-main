<?php
/**
 * ส่งการแจ้งเตือนแบบพุชไปยัง Expo Push API
 *
 * @param array $expo_tokens อาร์เรย์ของ Expo Push Token(s)
 * @param string $title หัวข้อของการแจ้งเตือน
 * @param string $body เนื้อหาของการแจ้งเตือน
 * @param array $data ข้อมูลเพิ่มเติมที่จะส่งไปกับการแจ้งเตือน (เป็น JSON)
 * @return string|false ผลลัพธ์จาก cURL หรือ false หากเกิดข้อผิดพลาดเบื้องต้น
 */
function send_push_notification(array $expo_tokens, $title, $body, $data = [])
{
    if (empty($expo_tokens)) {
        error_log("❗ Expo tokens array is empty. No notifications sent.");
        return false;
    }

    $messages = [];
    foreach ($expo_tokens as $token) {
        // ตรวจสอบความถูกต้องของ token เล็กน้อย (เช่น ต้องไม่ว่างเปล่า)
        if (empty($token) || !is_string($token)) {
             error_log("❗ Invalid or empty token found in the array. Skipping: " . json_encode($token));
             continue; // ข้าม token ที่ไม่ถูกต้องไป
        }

        // สร้าง notification data ที่มี screen และ params
        $notification_data = [
            'screen' => $data['screen'] ?? 'NotificationScreen',
            'params' => $data['params'] ?? []
        ];

        $messages[] = [
            'to' => $token,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'channelId' => 'default', // ต้องมีการกำหนด channel 'default' ในแอปฝั่ง React Native
            'data' => (object)$notification_data, // Expo แนะนำให้ data เป็น Object แทนที่จะเป็น Array ตรงๆ
        ];
    }

    // หากไม่มีข้อความที่ถูกต้องให้ส่งออก
    if (empty($messages)) {
        error_log("❗ No valid messages to send after filtering tokens.");
        return false;
    }

    $ch = curl_init('https://exp.host/--/api/v2/push/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Accept-Encoding: gzip, deflate',
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($messages)); // ส่งอาร์เรย์ของข้อความ

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        error_log("❌ Curl error: " . $error_msg);
        curl_close($ch);
        return false;
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        error_log("❌ Expo API returned HTTP status code: " . $http_code . " with response: " . $result);
    } else {
        error_log("✅ Expo push result: " . $result);
    }

    curl_close($ch);

    return $result;
}

function checkExpoTokenValid($token) {
    $url = "https://exp.host/--/api/v2/push/send";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    $res = json_decode($response, true);
    if (isset($res['data']['status']) && $res['data']['status'] === 'error') {
        if ($res['data']['details']['error'] === 'DeviceNotRegistered') {
            return false; // token หมดอายุ/ไม่ใช้แล้ว
        }
    }

    return true; // token ใช้ได้
}

?> 