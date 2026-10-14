<?php
include "config_ctrl/connect.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

date_default_timezone_set('Asia/Bangkok');

function json_exit($connect, $arr) {
    if ($connect) { $connect->close(); }
    echo json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

if ($connect->connect_error) {
    json_exit($connect, array("error" => "Connection failed: " . $connect->connect_error));
}

$startDate = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : null;
$endDate   = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : null;
$ag_id     = isset($_REQUEST['ag_id']) ? $_REQUEST['ag_id'] : null;
$mt_type   = isset($_REQUEST['mt_type']) ? $_REQUEST['mt_type'] : null;
$mt_id     = isset($_REQUEST['mt_id']) ? $_REQUEST['mt_id'] : null;
$area_id   = isset($_REQUEST['area_id']) ? $_REQUEST['area_id'] : null;

if (!$startDate || !$endDate) {
    json_exit($connect, array("error" => "startDate and endDate parameters are required."));
}

$startDate = date('Y-m-d', strtotime($startDate));
$endDate   = date('Y-m-d', strtotime($endDate));

if ($startDate > $endDate) {
    json_exit($connect, array("error" => "startDate cannot be later than endDate."));
}

$startDT = $startDate . " 00:00:00";
$endDT   = $endDate   . " 23:59:59";

function fetch_all_assoc($stmt) {
    $res = $stmt->get_result();
    $rows = array();
    if ($res) {
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    }
    return $rows;
}

function fetch_one_assoc($stmt) {
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) return $res->fetch_assoc();
    return null;
}

/* --------------------------
   1) Load master lists
-------------------------- */
$service_groups = array();
$stmt = $connect->prepare("SELECT rpg_id, rpg_name FROM tb_repair_group WHERE rpg_ag_id = ? AND rpg_status = 0");
 $stmt->bind_param("s", $ag_id);
$stmt->execute();
$service_groups = fetch_all_assoc($stmt);
$stmt->close();

$repair_types = array();
$stmt = $connect->prepare("SELECT rps_id, rps_name FROM tb_repair_system WHERE rps_ag_id = ?  AND rps_status = 0");
 $stmt->bind_param("s", $ag_id);
$stmt->execute();
$repair_types = fetch_all_assoc($stmt);
$stmt->close();

$areas = array();
if ($ag_id !== null && $ag_id !== '') {
    $stmt = $connect->prepare("SELECT area_id, area_name FROM tb_area WHERE ag_id = ? AND area_status = 0");
    $stmt->bind_param("s", $ag_id);
    $stmt->execute();
    $areas = fetch_all_assoc($stmt);
    $stmt->close();
}

/* --------------------------
   2) If mt_id not provided, pick first meter by mt_type & ag_id (same behavior)
-------------------------- */
if (!$mt_id && $mt_type && $ag_id) {
    $stmt = $connect->prepare("SELECT mt_id FROM tb_meter WHERE mt_type = ? AND mr_ag_id = ? ORDER BY mt_id ASC LIMIT 1");
    $stmt->bind_param("ss", $mt_type, $ag_id);
    $stmt->execute();
    $row = fetch_one_assoc($stmt);
    $stmt->close();
    if ($row && isset($row['mt_id'])) $mt_id = $row['mt_id'];
}

/* --------------------------
   3) Build daily skeleton (keep same response shape)
-------------------------- */
$dailyMap = array(); // date => item
$dailyDates = array();

$cur = $startDate;
while (strtotime($cur) <= strtotime($endDate)) {
    $dailyDates[] = $cur;
    $dailyMap[$cur] = array(
        "date" => $cur,
        "repairs" => 0,
        "pending" => 0,
        "inprogress" => 0,
        "completed" => 0,
        "canceled" => 0,
        "service" => array(),
        "type" => array(),
        "area" => array(),
        "meter" => array("wt" => array(), "et" => array(), "tou" => array())
    );
    $cur = date('Y-m-d', strtotime('+1 day', strtotime($cur)));
}

$buildingFilterSQL = "";
$buildingParam = null;
$buildingParamType = "";
if ($area_id) {
    // repair_requests uses "building" field in your code
    $buildingFilterSQL = " AND building = ? ";
    $buildingParam = $area_id;
    $buildingParamType = "s";
}

/* --------------------------
   4) Aggregate repairs counts (created_at)
-------------------------- */
if ($ag_id) {
    $sql = "
		SELECT DATE(created_at) AS d,
			   SUM(CASE WHEN status <> 'canceled' THEN 1 ELSE 0 END) AS repairs_count,
			   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
			   SUM(CASE WHEN status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_count,
			   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count
		FROM repair_requests
		WHERE ag_id = ?
		  AND created_at BETWEEN ? AND ?
		  $buildingFilterSQL
		GROUP BY DATE(created_at)
	";
    $stmt = $connect->prepare($sql);
    if ($buildingParam !== null) {
        $stmt->bind_param("ssss", $ag_id, $startDT, $endDT, $buildingParam);
    } else {
        $stmt->bind_param("sss", $ag_id, $startDT, $endDT);
    }
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();

    foreach ($rows as $r) {
		$d = $r['d'];
		if (!isset($dailyMap[$d])) continue;
		$dailyMap[$d]['repairs']    = (int)$r['repairs_count'];
		$dailyMap[$d]['pending']    = (int)$r['pending_count'];
		$dailyMap[$d]['inprogress'] = (int)$r['inprogress_count'];
		$dailyMap[$d]['completed']  = (int)$r['completed_count'];
	}

    // completed by completed_date
//    $sql = "
//        SELECT DATE(completed_date) AS d, COUNT(*) AS completed_count
//        FROM repair_requests
//        WHERE ag_id = ?
//          AND completed_date IS NOT NULL
//          AND completed_date BETWEEN ? AND ?
//          $buildingFilterSQL
//        GROUP BY DATE(completed_date)
//    ";
//    $stmt = $connect->prepare($sql);
//    if ($buildingParam !== null) {
//        $stmt->bind_param("ssss", $ag_id, $startDT, $endDT, $buildingParam);
//    } else {
//        $stmt->bind_param("sss", $ag_id, $startDT, $endDT);
//    }
//    $stmt->execute();
//    $rows = fetch_all_assoc($stmt);
//    $stmt->close();
//    foreach ($rows as $r) {
//        $d = $r['d'];
//        if (!isset($dailyMap[$d])) continue;
//        $dailyMap[$d]['completed'] = (int)$r['completed_count'];
//    }

    // canceled by updated_at + status=canceled
    $sql = "
        SELECT DATE(updated_at) AS d, COUNT(*) AS canceled_count
        FROM repair_requests
        WHERE ag_id = ?
          AND status = 'cancel'
          AND report_date BETWEEN ? AND ?
          $buildingFilterSQL
    ";
    $stmt = $connect->prepare($sql);
    if ($buildingParam !== null) {
        $stmt->bind_param("ssss", $ag_id, $startDT, $endDT, $buildingParam);
    } else {
        $stmt->bind_param("sss", $ag_id, $startDT, $endDT);
    }
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();
    foreach ($rows as $r) {
        $d = $r['d'];
        if (!isset($dailyMap[$d])) continue;
        $dailyMap[$d]['canceled'] = (int)$r['canceled_count'];
    }
}

/* --------------------------
   5) Aggregate service/type/area counts (created_at)
-------------------------- */
$serviceCountMap = array(); // [date][service_type]=count
$typeCountMap    = array(); // [date][job_type]=count
$areaCountMap    = array(); // [date][building]=count

if ($ag_id) {
    // service_type
    $sql = "
        SELECT DATE(created_at) AS d, service_type, COUNT(*) AS c
        FROM repair_requests
        WHERE ag_id = ?
          AND created_at BETWEEN ? AND ?
          $buildingFilterSQL
        GROUP BY DATE(created_at), service_type
    ";
    $stmt = $connect->prepare($sql);
    if ($buildingParam !== null) {
        $stmt->bind_param("ssss", $ag_id, $startDT, $endDT, $buildingParam);
    } else {
        $stmt->bind_param("sss", $ag_id, $startDT, $endDT);
    }
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();
    foreach ($rows as $r) {
        $d = $r['d'];
        $k = (string)$r['service_type'];
        if (!isset($serviceCountMap[$d])) $serviceCountMap[$d] = array();
        $serviceCountMap[$d][$k] = (int)$r['c'];
    }

    // job_type
    $sql = "
        SELECT DATE(created_at) AS d, job_type, COUNT(*) AS c
        FROM repair_requests
        WHERE ag_id = ?
          AND created_at BETWEEN ? AND ?
          $buildingFilterSQL
        GROUP BY DATE(created_at), job_type
    ";
    $stmt = $connect->prepare($sql);
    if ($buildingParam !== null) {
        $stmt->bind_param("ssss", $ag_id, $startDT, $endDT, $buildingParam);
    } else {
        $stmt->bind_param("sss", $ag_id, $startDT, $endDT);
    }
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();
    foreach ($rows as $r) {
        $d = $r['d'];
        $k = (string)$r['job_type'];
        if (!isset($typeCountMap[$d])) $typeCountMap[$d] = array();
        $typeCountMap[$d][$k] = (int)$r['c'];
    }

    // area/building
    $sql = "
        SELECT DATE(created_at) AS d, building, COUNT(*) AS c
        FROM repair_requests
        WHERE ag_id = ?
          AND created_at BETWEEN ? AND ?
          $buildingFilterSQL
        GROUP BY DATE(created_at), building
    ";
    $stmt = $connect->prepare($sql);
    if ($buildingParam !== null) {
        $stmt->bind_param("ssss", $ag_id, $startDT, $endDT, $buildingParam);
    } else {
        $stmt->bind_param("sss", $ag_id, $startDT, $endDT);
    }
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();
    foreach ($rows as $r) {
        $d = $r['d'];
        $k = (string)$r['building'];
        if (!isset($areaCountMap[$d])) $areaCountMap[$d] = array();
        $areaCountMap[$d][$k] = (int)$r['c'];
    }
}

// Fill per-day service/type/area arrays (keep same output structure)
foreach ($dailyDates as $d) {
    // service
    foreach ($service_groups as $g) {
        $gid = (string)$g['rpg_id'];
        $val = 0;
        if (isset($serviceCountMap[$d]) && isset($serviceCountMap[$d][$gid])) $val = (int)$serviceCountMap[$d][$gid];
        $dailyMap[$d]['service'][] = array(
            "rpg_id" => (int)$g['rpg_id'],
            "text" => $g['rpg_name'],
            "value" => $val
        );
    }

    // type
    foreach ($repair_types as $t) {
        $tid = (string)$t['rps_id'];
        $val = 0;
        if (isset($typeCountMap[$d]) && isset($typeCountMap[$d][$tid])) $val = (int)$typeCountMap[$d][$tid];
        $dailyMap[$d]['type'][] = array(
            "rps_id" => (int)$t['rps_id'],
            "text" => $t['rps_name'],
            "value" => $val
        );
    }

    // area (respect area_id filter behavior)
    foreach ($areas as $a) {
        if ($area_id && (string)$a['area_id'] !== (string)$area_id) continue;
        $aid = (string)$a['area_id'];
        $val = 0;
        if (isset($areaCountMap[$d]) && isset($areaCountMap[$d][$aid])) $val = (int)$areaCountMap[$d][$aid];
        $dailyMap[$d]['area'][] = array(
            "area_id" => (int)$a['area_id'],
            "text" => $a['area_name'],
            "value" => $val
        );
    }
}

/* --------------------------
   6) Meter section (fast batch)
   - Keep same shape:
     if mt_id provided => meter arrays contain 1 item for that mt_type only
     else => meter arrays contain all meters (grouped by type) each day
-------------------------- */
$sum_tou_total = 0.0;
$sum_tou_on_peak = 0.0;
$sum_tou_off_peak = 0.0;

// Load meters list only if needed (mt_id null => all meters, mt_id set => just one name text)
$meters = array(); // each: mt_id, mt_type, text
$meterTextById = array();

if ($ag_id) {
    if ($mt_id) {
        // build text like original: mt_name + ' ' + area_name
        $stmt = $connect->prepare("
            SELECT m.mt_id, m.mt_type, m.mt_name, a.area_name
            FROM tb_meter m
            LEFT JOIN tb_area a ON a.area_id = m.mt_rp_area_id
            WHERE m.mt_id = ? AND m.mr_ag_id = ?
            LIMIT 1
        ");
        $mt_id_int = (int)$mt_id;
        $stmt->bind_param("is", $mt_id_int, $ag_id);
        $stmt->execute();
        $row = fetch_one_assoc($stmt);
        $stmt->close();

        if ($row) {
            $text = $row['mt_name'] . ' ' . $row['area_name'];
            $meters[] = array("mt_id" => (int)$row['mt_id'], "mt_type" => strtolower($row['mt_type']), "text" => $text);
            $meterTextById[(int)$row['mt_id']] = $text;
            // keep mt_type from request as primary switch like original behavior
        }
    } else {
        $stmt = $connect->prepare("
            SELECT m.mt_id, m.mt_type, m.mt_name, a.area_name
            FROM tb_meter m
            LEFT JOIN tb_area a ON a.area_id = m.mt_rp_area_id
            WHERE m.mr_ag_id = ?
        ");
        $stmt->bind_param("s", $ag_id);
        $stmt->execute();
        $rows = fetch_all_assoc($stmt);
        $stmt->close();

        foreach ($rows as $r) {
            $id = (int)$r['mt_id'];
            $type = strtolower($r['mt_type']);
            $text = $r['mt_name'] . ' ' . $r['area_name'];
            $meters[] = array("mt_id" => $id, "mt_type" => $type, "text" => $text);
            $meterTextById[$id] = $text;
        }
    }
}

// ปริมาณการใช้มิเตอร์รายวัน — คำนวณแบบเดียวกับหน้าข้อมูลมิเตอร์ (handle_meter_info.php calc_usage)
//  - ทุกครั้งที่จด: ใช้ = เลขที่อ่านได้ - เลขครั้งก่อนหน้า (ข้ามวันที่ไม่ได้จดได้ ไม่ถือว่าเป็น 0), ติดลบ = 0
//  - ติ๊ก "เริ่มนับใหม่" (is_rollover = 1): ใช้ = เลขที่อ่านได้
//  - ฐานของครั้งแรกในช่วง = เลขครั้งล่าสุดก่อนช่วงวันที่ (ไม่มี = 0)
//  - ปริมาณรายวัน = ผลรวมของทุกครั้งที่จดในวันนั้น
function meter_calc_usage($curr, $prev, $is_roll) {
    if ((int)$is_roll === 1) return $curr < 0 ? 0.0 : $curr;
    $u = $curr - $prev;
    return $u < 0 ? 0.0 : $u;
}

// เลขครั้งล่าสุดก่อนช่วงวันที่ ของแต่ละมิเตอร์ — [mt_id] => array(col => value)
function meter_baseline($connect, $table, $idCol, $timeCol, $pkCol, $cols, $startDT, $meterIds) {
    if (!$meterIds) return array();
    $in = implode(',', array_map('intval', $meterIds));
    $sel = implode(', ', array_map(function ($c) { return "t.$c"; }, $cols));
    $sql = "
        SELECT t.$idCol AS mt_id, $sel
        FROM $table t
        JOIN (
            SELECT $idCol AS mt_id, MAX($timeCol) AS mx
            FROM $table
            WHERE $idCol IN ($in) AND $timeCol < ?
            GROUP BY $idCol
        ) x ON x.mt_id = t.$idCol AND x.mx = t.$timeCol
        ORDER BY t.$pkCol ASC
    ";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $startDT);
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();
    $map = array();
    foreach ($rows as $r) {   // เวลาเดียวกันหลายแถว => แถวหลังสุด (pk มากสุด) ทับ
        $v = array();
        foreach ($cols as $c) $v[$c] = (float)$r[$c];
        $map[(int)$r['mt_id']] = $v;
    }
    return $map;
}

// ทุกครั้งที่จดในช่วงวันที่ เรียงตามเวลา — [mt_id] => list of array(d, is_roll, col => value)
function meter_readings($connect, $table, $idCol, $timeCol, $pkCol, $rollCol, $cols, $startDT, $endDT, $meterIds) {
    if (!$meterIds) return array();
    $in = implode(',', array_map('intval', $meterIds));
    $sel = implode(', ', $cols);
    $sql = "
        SELECT $idCol AS mt_id, DATE($timeCol) AS d, $rollCol AS is_roll, $sel
        FROM $table
        WHERE $idCol IN ($in) AND $timeCol BETWEEN ? AND ?
        ORDER BY $idCol, $timeCol, $pkCol
    ";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $startDT, $endDT);
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();
    $map = array();
    foreach ($rows as $r) {
        $v = array("d" => $r['d'], "is_roll" => (int)$r['is_roll']);
        foreach ($cols as $c) $v[$c] = (float)$r[$c];
        $map[(int)$r['mt_id']][] = $v;
    }
    return $map;
}

// มิเตอร์ธรรมดา (น้ำ/ไฟ) — [mt_id][date] => array(value = เลขล่าสุดของวัน, usage = ปริมาณที่ใช้ในวัน)
function meter_daily_simple($connect, $kind, $startDT, $endDT, $meterIds) {
    $table = "tb_toum_detail_$kind"; $p = "toumdt_{$kind}";
    $base = meter_baseline($connect, $table, "{$p}_toum_id", "{$p}_time", "{$p}_id", array("{$p}_mt"), $startDT, $meterIds);
    $reads = meter_readings($connect, $table, "{$p}_toum_id", "{$p}_time", "{$p}_id", "{$p}_is_rollover", array("{$p}_mt"), $startDT, $endDT, $meterIds);
    $out = array();
    foreach ($meterIds as $mid) {
        $mid = (int)$mid;
        $prev = isset($base[$mid]) ? $base[$mid]["{$p}_mt"] : 0.0;
        $out[$mid] = array("_base" => $prev);
        foreach (isset($reads[$mid]) ? $reads[$mid] : array() as $r) {
            $curr = $r["{$p}_mt"];
            $u = meter_calc_usage($curr, $prev, $r['is_roll']);
            if (!isset($out[$mid][$r['d']])) $out[$mid][$r['d']] = array("value" => 0.0, "usage" => 0.0);
            $out[$mid][$r['d']]['usage'] += $u;
            $out[$mid][$r['d']]['value'] = $curr;
            $prev = $curr;
        }
    }
    return $out;
}

// มิเตอร์ TOU — ใช้ On Peak (011) + Off Peak (012) แบบเดียวกับหน้าข้อมูลมิเตอร์; [mt_id][date] => array(total,on_peak,off_peak, on_unit, off_unit)
function meter_daily_tou($connect, $startDT, $endDT, $meterIds) {
    $cols = array('toudt_010', 'toudt_011', 'toudt_012');
    $base = meter_baseline($connect, 'tb_tou_detail', 'toudt_tou_id', 'toudt_time', 'toudt_id', $cols, $startDT, $meterIds);
    $reads = meter_readings($connect, 'tb_tou_detail', 'toudt_tou_id', 'toudt_time', 'toudt_id', 'toudt_is_rollover', $cols, $startDT, $endDT, $meterIds);
    $out = array();
    foreach ($meterIds as $mid) {
        $mid = (int)$mid;
        $prev = isset($base[$mid]) ? $base[$mid] : array('toudt_010' => 0.0, 'toudt_011' => 0.0, 'toudt_012' => 0.0);
        $out[$mid] = array("_base" => $prev);
        foreach (isset($reads[$mid]) ? $reads[$mid] : array() as $r) {
            $on  = meter_calc_usage($r['toudt_011'], $prev['toudt_011'], $r['is_roll']);
            $off = meter_calc_usage($r['toudt_012'], $prev['toudt_012'], $r['is_roll']);
            if (!isset($out[$mid][$r['d']])) $out[$mid][$r['d']] = array("on_unit" => 0.0, "off_unit" => 0.0);
            $out[$mid][$r['d']]['on_unit'] += $on;
            $out[$mid][$r['d']]['off_unit'] += $off;
            $out[$mid][$r['d']]['total'] = $r['toudt_010'];
            $out[$mid][$r['d']]['on_peak'] = $r['toudt_011'];
            $out[$mid][$r['d']]['off_peak'] = $r['toudt_012'];
            $prev = $r;
        }
    }
    return $out;
}

// Prepare meter ids by type
$wtIds = array();
$etIds = array();
$touIds = array();

foreach ($meters as $m) {
    if ($mt_id) {
        // if mt_id specified, only compute for requested mt_type (same as original switch)
        // BUT: original code uses request mt_type to decide which table to query.
        // We'll follow that.
        break;
    } else {
        if ($m['mt_type'] === 'wt') $wtIds[] = $m['mt_id'];
        if ($m['mt_type'] === 'et') $etIds[] = $m['mt_id'];
        if ($m['mt_type'] === 'tou') $touIds[] = $m['mt_id'];
    }
}

if ($mt_id && $mt_type && $meters) {
    $mt_id_int = (int)$mt_id;
    $typeReq = strtolower($mt_type);

    if ($typeReq === 'wt') $wtIds = array($mt_id_int);
    if ($typeReq === 'et') $etIds = array($mt_id_int);
    if ($typeReq === 'tou') $touIds = array($mt_id_int);
}

// Fetch readings + ปริมาณการใช้รายวัน
$wtMap  = meter_daily_simple($connect, 'wt', $startDT, $endDT, $wtIds);
$etMap  = meter_daily_simple($connect, 'et', $startDT, $endDT, $etIds);
$touMap = meter_daily_tou($connect, $startDT, $endDT, $touIds);

// เลขมิเตอร์ล่าสุด ณ วันนั้น (วันที่ไม่ได้จด = เลขครั้งก่อนหน้า)
$lastWt = array(); $lastEt = array(); $lastTou = array();
foreach ($wtIds as $mid)  $lastWt[(int)$mid]  = $wtMap[(int)$mid]['_base'];
foreach ($etIds as $mid)  $lastEt[(int)$mid]  = $etMap[(int)$mid]['_base'];
foreach ($touIds as $mid) $lastTou[(int)$mid] = $touMap[(int)$mid]['_base'];

foreach ($dailyDates as $d) {
    foreach (array('wt' => $wtIds, 'et' => $etIds) as $kind => $ids) {
        foreach ($ids as $mid) {
            $mid = (int)$mid;
            $day = $kind === 'wt' ? (isset($wtMap[$mid][$d]) ? $wtMap[$mid][$d] : null) : (isset($etMap[$mid][$d]) ? $etMap[$mid][$d] : null);
            if ($kind === 'wt') { if ($day) $lastWt[$mid] = $day['value']; $val = $lastWt[$mid]; }
            else                { if ($day) $lastEt[$mid] = $day['value']; $val = $lastEt[$mid]; }

            $dailyMap[$d]['meter'][$kind][] = array(
                "mt_id" => $mid,
                "text" => isset($meterTextById[$mid]) ? $meterTextById[$mid] : "",
                "value" => (float)$val,
                "unitDay" => $day ? round($day['usage'], 3) : 0.0
            );
        }
    }

    // TOU
    foreach ($touIds as $mid) {
        $mid = (int)$mid;
        $day = isset($touMap[$mid][$d]) ? $touMap[$mid][$d] : null;
        if ($day) $lastTou[$mid] = array('toudt_010' => $day['total'], 'toudt_011' => $day['on_peak'], 'toudt_012' => $day['off_peak']);
        $on_unit  = $day ? $day['on_unit'] : 0.0;
        $off_unit = $day ? $day['off_unit'] : 0.0;
        $total_unit = $on_unit + $off_unit;

        $sum_tou_total += $total_unit;
        $sum_tou_on_peak += $on_unit;
        $sum_tou_off_peak += $off_unit;

        $dailyMap[$d]['meter']['tou'][] = array(
            "mt_id" => $mid,
            "text" => isset($meterTextById[$mid]) ? $meterTextById[$mid] : "",
            "total" => (float)$lastTou[$mid]['toudt_010'],
            "on_peak" => (float)$lastTou[$mid]['toudt_011'],
            "off_peak" => (float)$lastTou[$mid]['toudt_012'],
            "total_unit" => round($total_unit, 2),
            "on_peak_unit" => round($on_unit, 2),
            "off_peak_unit" => round($off_unit, 2)
        );
    }
}

/* --------------------------
   6.5) งานค้าง ณ ปัจจุบัน (ไม่ขึ้นกับช่วงวันที่ที่กรอง)
        กันงานตกหล่นข้ามเดือน เช่น แจ้ง 31/01 แต่ดู dashboard วันที่ 01/02
-------------------------- */
$backlog = array("pending" => 0, "inprogress" => 0, "before_range" => 0, "oldest_date" => null);
if ($ag_id) {
    $sql = "
		SELECT SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_all,
			   SUM(CASE WHEN status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_all,
			   SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) AS before_range,
			   MIN(CASE WHEN created_at < ? THEN created_at END) AS oldest_before
		FROM repair_requests
		WHERE ag_id = ?
		  AND status IN ('pending', 'inprogress')
		  $buildingFilterSQL
	";
    $stmt = $connect->prepare($sql);
    if ($buildingParam !== null) {
        $stmt->bind_param("ssss", $startDT, $startDT, $ag_id, $buildingParam);
    } else {
        $stmt->bind_param("sss", $startDT, $startDT, $ag_id);
    }
    $stmt->execute();
    $row = fetch_one_assoc($stmt);
    $stmt->close();
    if ($row) {
        $backlog = array(
            "pending" => (int)$row['pending_all'],
            "inprogress" => (int)$row['inprogress_all'],
            "before_range" => (int)$row['before_range'],
            "oldest_date" => $row['oldest_before'] ? date('Y-m-d', strtotime($row['oldest_before'])) : null
        );
    }
}

/* --------------------------
   7) Output (same structure)
-------------------------- */
$dailyData = array();
foreach ($dailyDates as $d) {
    $dailyData[] = $dailyMap[$d];
}

$response = array(
    "data" => $dailyData,
    "backlog" => $backlog,
    "summary" => array(
        "sum_tou_total" => round($sum_tou_total, 2),
        "sum_tou_on_peak" => round($sum_tou_on_peak, 2),
        "sum_tou_off_peak" => round($sum_tou_off_peak, 2)
    )
);

json_exit($connect, $response);
?>
