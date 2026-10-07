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
$stmt = $connect->prepare("SELECT rpg_id, rpg_name FROM tb_repair_group WHERE rpg_status = 0");
$stmt->execute();
$service_groups = fetch_all_assoc($stmt);
$stmt->close();

$repair_types = array();
$stmt = $connect->prepare("SELECT rps_id, rps_name FROM tb_repair_system WHERE rps_status = 0");
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
    $sql_pick = "SELECT mt_id FROM tb_meter WHERE mt_type = ? AND mr_ag_id = ?";
    if ($area_id) {
        $sql_pick .= " AND mt_rp_area_id = ?";
    }
    $sql_pick .= " ORDER BY mt_id ASC LIMIT 1";

    $stmt = $connect->prepare($sql_pick);
    if ($area_id) {
        $stmt->bind_param("sss", $mt_type, $ag_id, $area_id);
    } else {
        $stmt->bind_param("ss", $mt_type, $ag_id);
    }
    
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
               SUM(CASE WHEN status = 'inprogress' THEN 1 ELSE 0 END) AS inprogress_count
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
    }

    // completed by completed_date
    $sql = "
        SELECT DATE(completed_date) AS d, COUNT(*) AS completed_count
        FROM repair_requests
        WHERE ag_id = ?
          AND completed_date IS NOT NULL
          AND completed_date BETWEEN ? AND ?
          $buildingFilterSQL
        GROUP BY DATE(completed_date)
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
        $dailyMap[$d]['completed'] = (int)$r['completed_count'];
    }

    // canceled by updated_at + status=canceled
    $sql = "
        SELECT DATE(updated_at) AS d, COUNT(*) AS canceled_count
        FROM repair_requests
        WHERE ag_id = ?
          AND status = 'canceled'
          AND updated_at BETWEEN ? AND ?
          $buildingFilterSQL
        GROUP BY DATE(updated_at)
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
$meters = array(); 
$meterTextById = array();

if ($ag_id) {
    if ($mt_id) {
        // กรณีระบุ mt_id มาโดยตรง (ตรวจสอบ area_id ด้วยเพื่อความถูกต้อง)
        $sql_mt = "
            SELECT m.mt_id, m.mt_type, m.mt_name, a.area_name
            FROM tb_meter m
            LEFT JOIN tb_area a ON a.area_id = m.mt_rp_area_id
            WHERE m.mt_id = ? AND m.mr_ag_id = ?
        ";
        if ($area_id) { $sql_mt .= " AND m.mt_rp_area_id = ?"; }
        $sql_mt .= " LIMIT 1";

        $stmt = $connect->prepare($sql_mt);
        $mt_id_int = (int)$mt_id;
        if ($area_id) {
            $stmt->bind_param("iss", $mt_id_int, $ag_id, $area_id);
        } else {
            $stmt->bind_param("is", $mt_id_int, $ag_id);
        }
        $stmt->execute();
        $row = fetch_one_assoc($stmt);
        $stmt->close();

        if ($row) {
            $text = $row['mt_name'] . ' ' . $row['area_name'];
            $meters[] = array("mt_id" => (int)$row['mt_id'], "mt_type" => strtolower($row['mt_type']), "text" => $text);
            $meterTextById[(int)$row['mt_id']] = $text;
        }
    } else {
        // กรณีดึง Meter ทั้งหมด (กรองตาม area_id)
        $sql_mt = "
            SELECT m.mt_id, m.mt_type, m.mt_name, a.area_name
            FROM tb_meter m
            LEFT JOIN tb_area a ON a.area_id = m.mt_rp_area_id
            WHERE m.mr_ag_id = ?
        ";
        if ($area_id) { $sql_mt .= " AND m.mt_rp_area_id = ?"; }

        $stmt = $connect->prepare($sql_mt);
        if ($area_id) {
            $stmt->bind_param("ss", $ag_id, $area_id);
        } else {
            $stmt->bind_param("s", $ag_id);
        }
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

// Helpers to fetch "last reading per day" for WT/ET/TOU (works on MySQL old)
function fetch_last_per_day_wt($connect, $startDT, $endDT, $meterIds) {
    if (!$meterIds) return array(); // [mt_id][date]=value
    $in = implode(',', array_map('intval', $meterIds));
    $sql = "
        SELECT x.mt_id, x.d, t.toumdt_wt_mt AS value
        FROM (
            SELECT toumdt_wt_toum_id AS mt_id, DATE(toumdt_wt_time) AS d, MAX(toumdt_wt_time) AS mx
            FROM tb_toum_detail_wt
            WHERE toumdt_wt_toum_id IN ($in)
              AND toumdt_wt_time BETWEEN ? AND ?
            GROUP BY toumdt_wt_toum_id, DATE(toumdt_wt_time)
        ) x
        JOIN tb_toum_detail_wt t
          ON t.toumdt_wt_toum_id = x.mt_id AND t.toumdt_wt_time = x.mx
    ";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $startDT, $endDT);
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();

    $map = array();
    foreach ($rows as $r) {
        $mid = (int)$r['mt_id'];
        $d = $r['d'];
        if (!isset($map[$mid])) $map[$mid] = array();
        $map[$mid][$d] = (float)$r['value'];
    }
    return $map;
}

function fetch_last_per_day_et($connect, $startDT, $endDT, $meterIds) {
    if (!$meterIds) return array();
    $in = implode(',', array_map('intval', $meterIds));
    $sql = "
        SELECT x.mt_id, x.d, t.toumdt_et_mt AS value
        FROM (
            SELECT toumdt_et_toum_id AS mt_id, DATE(toumdt_et_time) AS d, MAX(toumdt_et_time) AS mx
            FROM tb_toum_detail_et
            WHERE toumdt_et_toum_id IN ($in)
              AND toumdt_et_time BETWEEN ? AND ?
            GROUP BY toumdt_et_toum_id, DATE(toumdt_et_time)
        ) x
        JOIN tb_toum_detail_et t
          ON t.toumdt_et_toum_id = x.mt_id AND t.toumdt_et_time = x.mx
    ";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $startDT, $endDT);
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();

    $map = array();
    foreach ($rows as $r) {
        $mid = (int)$r['mt_id'];
        $d = $r['d'];
        if (!isset($map[$mid])) $map[$mid] = array();
        $map[$mid][$d] = (float)$r['value'];
    }
    return $map;
}

function fetch_last_per_day_tou($connect, $startDT, $endDT, $meterIds) {
    if (!$meterIds) return array(); // [mt_id][date]=array(total,on,off)
    $in = implode(',', array_map('intval', $meterIds));
    $sql = "
        SELECT x.mt_id, x.d,
               t.toudt_010 AS total,
               t.toudt_011 AS on_peak,
               t.toudt_012 AS off_peak
        FROM (
            SELECT toudt_tou_id AS mt_id, DATE(toudt_time) AS d, MAX(toudt_time) AS mx
            FROM tb_tou_detail
            WHERE toudt_tou_id IN ($in)
              AND toudt_time BETWEEN ? AND ?
            GROUP BY toudt_tou_id, DATE(toudt_time)
        ) x
        JOIN tb_tou_detail t
          ON t.toudt_tou_id = x.mt_id AND t.toudt_time = x.mx
    ";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $startDT, $endDT);
    $stmt->execute();
    $rows = fetch_all_assoc($stmt);
    $stmt->close();

    $map = array();
    foreach ($rows as $r) {
        $mid = (int)$r['mt_id'];
        $d = $r['d'];
        if (!isset($map[$mid])) $map[$mid] = array();
        $map[$mid][$d] = array(
            "total" => (float)$r['total'],
            "on_peak" => (float)$r['on_peak'],
            "off_peak" => (float)$r['off_peak']
        );
    }
    return $map;
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

// Fetch readings
$wtMap  = fetch_last_per_day_wt($connect, $startDT, $endDT, $wtIds);
$etMap  = fetch_last_per_day_et($connect, $startDT, $endDT, $etIds);
$touMap = fetch_last_per_day_tou($connect, $startDT, $endDT, $touIds);

// Compute per-day meter arrays with unitDay (same logic as original: diff from previous day, clamp >=0)
$prevWtValues = array();
$prevEtValues = array();
$prevTouValues = array();

foreach ($dailyDates as $d) {
    // WT
    foreach ($wtIds as $mid) {
        $val = 0.0;
        if (isset($wtMap[$mid]) && isset($wtMap[$mid][$d])) $val = (float)$wtMap[$mid][$d];

        $prev = isset($prevWtValues[$mid]) ? $prevWtValues[$mid] : $val;
        $unitDay = $val - $prev;
        if ($unitDay < 0) $unitDay = 0;

        $prevWtValues[$mid] = $val;

        $dailyMap[$d]['meter']['wt'][] = array(
            "mt_id" => (int)$mid,
            "text" => isset($meterTextById[$mid]) ? $meterTextById[$mid] : "",
            "value" => (float)$val,
            "unitDay" => (float)$unitDay
        );
    }

    // ET
    foreach ($etIds as $mid) {
        $val = 0.0;
        if (isset($etMap[$mid]) && isset($etMap[$mid][$d])) $val = (float)$etMap[$mid][$d];

        $prev = isset($prevEtValues[$mid]) ? $prevEtValues[$mid] : $val;
        $unitDay = $val - $prev;
        if ($unitDay < 0) $unitDay = 0;

        $prevEtValues[$mid] = $val;

        $dailyMap[$d]['meter']['et'][] = array(
            "mt_id" => (int)$mid,
            "text" => isset($meterTextById[$mid]) ? $meterTextById[$mid] : "",
            "value" => (float)$val,
            "unitDay" => (float)$unitDay
        );
    }

    // TOU
    foreach ($touIds as $mid) {
        $tou = array("total" => 0.0, "on_peak" => 0.0, "off_peak" => 0.0);
        if (isset($touMap[$mid]) && isset($touMap[$mid][$d])) $tou = $touMap[$mid][$d];

        $prev = isset($prevTouValues[$mid]) ? $prevTouValues[$mid] : $tou;

        $total_unit = $tou['total'] - $prev['total'];
        $on_unit    = $tou['on_peak'] - $prev['on_peak'];
        $off_unit   = $tou['off_peak'] - $prev['off_peak'];

        if ($total_unit < 0) $total_unit = 0;
        if ($on_unit < 0)    $on_unit = 0;
        if ($off_unit < 0)   $off_unit = 0;

        $prevTouValues[$mid] = $tou;

        $sum_tou_total += $total_unit;
        $sum_tou_on_peak += $on_unit;
        $sum_tou_off_peak += $off_unit;

        $dailyMap[$d]['meter']['tou'][] = array(
            "mt_id" => (int)$mid,
            "text" => isset($meterTextById[$mid]) ? $meterTextById[$mid] : "",
            "total" => (float)$tou['total'],
            "on_peak" => (float)$tou['on_peak'],
            "off_peak" => (float)$tou['off_peak'],
            "total_unit" => round($total_unit, 2),
            "on_peak_unit" => round($on_unit, 2),
            "off_peak_unit" => round($off_unit, 2)
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
    "summary" => array(
        "sum_tou_total" => round($sum_tou_total, 2),
        "sum_tou_on_peak" => round($sum_tou_on_peak, 2),
        "sum_tou_off_peak" => round($sum_tou_off_peak, 2)
    )
);

json_exit($connect, $response);
?>
