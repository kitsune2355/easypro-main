<?php
// ============================================================
//  รายงานส่งมอบ PM — ดึง/สรุปข้อมูล (ใช้ร่วมกัน: แท็บรายงาน, หน้าพิมพ์, Export Excel)
//  ช่วงข้อมูลอิงวันที่ตามแผน (pm_plan_events.event_date)
// ============================================================

if (!function_exists('pmr_can_manage')) {

    // สิทธิ์เดียวกับโหมด "จัดการแผน" ใน pm.php — ช่าง (employer ตำแหน่งขึ้นต้น "ช่าง") ใช้ไม่ได้
    function pmr_can_manage($connect) {
        if (($_SESSION['sess_user_level_es'] ?? '') !== 'employer') return true;
        $st = $connect->prepare("SELECT user_position FROM tb_user WHERE user_id = ?");
        $uid = (string)($_SESSION['sess_user_id_es'] ?? '');
        $st->bind_param('s', $uid);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        return !($row && mb_strpos(trim((string)$row['user_position']), 'ช่าง') === 0);
    }

    function pmr_date($v, $def) {
        $v = trim((string)$v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : $def;
    }

    // ตัวกรองจาก GET/POST: from, to, type (กลุ่มเครื่อง), cs (เช็คชีต), m (รหัสเครื่อง คั่นด้วย ,)
    function pmr_filters($src) {
        $from = pmr_date($src['from'] ?? '', date('Y-m-01'));
        $to   = pmr_date($src['to'] ?? '', date('Y-m-t'));
        if ($to < $from) { $t = $from; $from = $to; $to = $t; }
        $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($src['m'] ?? ''))), 'strlen')));
        return [
            'from' => $from,
            'to'   => $to,
            'type' => trim((string)($src['type'] ?? '')),
            'cs'   => trim((string)($src['cs'] ?? '')),
            'm'    => $ids,
        ];
    }

    function pmr_query($connect, $sql, $types = '', $params = []) {
        $st = $connect->prepare($sql);
        if (!$st) throw new Exception('prepare: ' . $connect->error);
        if ($types !== '') $st->bind_param($types, ...$params);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        return $rows;
    }

    // แบ่ง IN (...) เป็นชุดละ 500
    function pmr_in_chunks($connect, $sqlTpl, array $ids, $type = 'i') {
        $out = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            $out = array_merge($out, pmr_query($connect, str_replace('{IN}', $ph, $sqlTpl), str_repeat($type, count($chunk)), $chunk));
        }
        return $out;
    }

    function pmr_agency($connect, $ag) {
        $r = pmr_query($connect, "SELECT ag_id, ag_contract, ag_located, ag_job FROM tb_agency WHERE ag_id = ?", 's', [$ag]);
        return $r ? $r[0] : ['ag_id' => $ag, 'ag_contract' => '', 'ag_located' => '', 'ag_job' => ''];
    }

    // ตัวเลือกตัวกรอง: กลุ่มเครื่อง / เช็คชีต ที่มีแผน PM ในหน่วยงาน
    function pmr_options($connect, $ag) {
        $types = pmr_query($connect, "
            SELECT DISTINCT g.GroupId AS id, g.TGroupName AS name
            FROM pm_plans p
            JOIN tb_ass_list a ON a.ass_id = p.machine_id
            JOIN tb_asset_group g ON g.GroupId = a.asset_type
            WHERE p.ag_id = ? ORDER BY g.TGroupName", 's', [$ag]);
        $cs = pmr_query($connect, "
            SELECT c.id, c.name, c.doc_no FROM pm_checksheets c
            WHERE c.ag_id = ? AND EXISTS (SELECT 1 FROM pm_plans p WHERE p.checksheet_id = c.id)
            ORDER BY c.name", 's', [$ag]);
        return ['types' => $types, 'checksheets' => $cs];
    }

    // ใบงาน/แผนทุกรอบในช่วง พร้อมข้อมูลเครื่อง เช็คชีต ผลบันทึก ผลประเมิน
    function pmr_events($connect, $ag, $f) {
        $where = " WHERE e.ag_id = ? AND e.event_date BETWEEN ? AND ? ";
        $types = 'sss';
        $params = [$ag, $f['from'], $f['to']];
        if ($f['type'] !== '') { $where .= " AND a.asset_type = ? "; $types .= 's'; $params[] = $f['type']; }
        if ($f['cs'] !== '')   { $where .= " AND e.checksheet_id = ? "; $types .= 's'; $params[] = $f['cs']; }
        if ($f['m']) {
            $where .= " AND e.machine_id IN (" . implode(',', array_fill(0, count($f['m']), '?')) . ") ";
            $types .= str_repeat('s', count($f['m']));
            $params = array_merge($params, $f['m']);
        }
        return pmr_query($connect, "
            SELECT e.id AS event_id, e.plan_id, e.machine_id, e.checksheet_id, e.event_date, e.status, e.completed_at,
                   a.ass_code, a.asset_name, a.asset_sn, a.asset_model, a.asset_type,
                   g.TGroupName AS type_name,
                   CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location,
                   c.name AS cs_name, c.doc_no,
                   (SELECT fo.description FROM pm_freq_options fo WHERE fo.freq_value = p.frequency LIMIT 1) AS freq,
                   w.id AS wr_id, w.actual_date, w.inspector_name, w.remarks,
                   ev.average_score, ev.evaluator_name, ev.eval_date, ev.eval_comments
            FROM pm_plan_events e
            LEFT JOIN tb_ass_list a ON a.ass_id = e.machine_id
            LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type
            LEFT JOIN tb_area ar ON ar.area_id = a.asset_rp_area_id
            LEFT JOIN tb_area_class ac ON ac.ac_id = a.asset_rp_ac_id
            LEFT JOIN tb_area_room rm ON rm.ar_id = a.asset_rp_ar_id
            LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
            LEFT JOIN pm_plans p ON p.id = e.plan_id
            LEFT JOIN pm_work_records w ON w.id = COALESCE(e.work_record_id, (SELECT w2.id FROM pm_work_records w2 WHERE w2.plan_event_id = e.id ORDER BY w2.id DESC LIMIT 1))
            LEFT JOIN pm_work_evaluations ev ON ev.work_record_id = w.id
            $where
            ORDER BY a.ass_code, a.asset_name, e.machine_id, e.event_date, e.id", $types, $params);
    }

    // สรุปทั้งหมด — $opt['detail'] = true จะแนบรายการจุดไม่ผ่าน/อะไหล่/ประเมิน/เลื่อนแผน ระดับรายการ
    function pmr_build($connect, $ag, $f, $opt = []) {
        $today = date('Y-m-d');
        $events = pmr_events($connect, $ag, $f);
        $wrIds = [];
        $eventIds = [];
        $machineIds = [];
        foreach ($events as $e) {
            if ($e['wr_id']) $wrIds[] = (int)$e['wr_id'];
            $eventIds[] = (int)$e['event_id'];
            $machineIds[(string)$e['machine_id']] = true;
        }

        // ผลตรวจรายใบงาน (Pass/Fail/N/A)
        $res = [];
        foreach (pmr_in_chunks($connect, "
            SELECT work_record_id,
                   SUM(result_status = 'Pass') AS pass, SUM(result_status = 'Fail') AS fail,
                   SUM(result_status = 'N/A') AS na, COUNT(*) AS total
            FROM pm_work_record_items WHERE work_record_id IN ({IN}) GROUP BY work_record_id", $wrIds) as $r) {
            $res[(int)$r['work_record_id']] = $r;
        }
        // จุดที่ไม่ผ่าน
        $fails = pmr_in_chunks($connect, "
            SELECT work_record_id, check_point, actual_value, unit, expected_value, value_name
            FROM pm_work_record_items WHERE result_status = 'Fail' AND work_record_id IN ({IN})
            ORDER BY work_record_id, sort_order, id", $wrIds);
        // อะไหล่ที่ใช้จริง
        $spares = pmr_in_chunks($connect, "
            SELECT work_record_id, pd_gen_code, rpd_details_head, rpd_details, rpd_qty, pd_unit, rpd_price, rpd_sum_money
            FROM pm_work_record_actual_spares WHERE work_record_id IN ({IN}) ORDER BY id", $wrIds);
        // จำนวนรูปผลตรวจ (ประมาณจำนวนหน้าแนบ)
        $photos = [];
        foreach (pmr_in_chunks($connect, "
            SELECT work_record_id, COUNT(*) AS n FROM pm_work_record_items
            WHERE result_photo_path IS NOT NULL AND result_photo_path <> '' AND work_record_id IN ({IN})
            GROUP BY work_record_id", $wrIds) as $r) {
            $photos[(int)$r['work_record_id']] = (int)$r['n'];
        }
        // เลื่อนแผน — pm_plan_postpone_history.plan_id คือ id ของ pm_plan_events (รอบแผน) ไม่ใช่ pm_plans
        $postpones = pmr_in_chunks($connect, "
            SELECT plan_id, old_date, new_date, reason, created_by, created_at
            FROM pm_plan_postpone_history WHERE plan_id IN ({IN})
            ORDER BY created_at", $eventIds);
        // ทำล่าสุด / ครั้งก่อนหน้า ของแต่ละเครื่อง (วันที่ปิดงานแบบเดียวกับใบงาน PM) นับย้อนหลังทั้งหมดจนถึงวันสิ้นสุดของรายงาน
        //  ครั้งก่อนหน้า = วันที่ทำล่าสุดที่อยู่ก่อนวัน "ทำล่าสุด" (ตรงกับ "ทำครั้งล่าสุด" ในใบงานของรอบล่าสุด)
        $lastDone = [];
        $prevDone = [];
        foreach ($machineIds ? array_chunk(array_keys($machineIds), 500) : [] as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            foreach (pmr_query($connect, "
                SELECT x.machine_id, x.d,
                       (SELECT MAX(COALESCE(DATE(p.completed_at), p.event_date)) FROM pm_plan_events p
                        WHERE p.ag_id = ? AND p.status = 1 AND p.machine_id = x.machine_id
                          AND COALESCE(DATE(p.completed_at), p.event_date) < x.d) AS prev
                FROM (SELECT machine_id, MAX(COALESCE(DATE(completed_at), event_date)) AS d FROM pm_plan_events
                      WHERE ag_id = ? AND status = 1 AND COALESCE(DATE(completed_at), event_date) <= ? AND machine_id IN ($ph)
                      GROUP BY machine_id) x", 'sss' . str_repeat('s', count($chunk)), array_merge([$ag, $ag, $f['to']], array_map('strval', $chunk))) as $r) {
                $lastDone[(string)$r['machine_id']] = $r['d'];
                $prevDone[(string)$r['machine_id']] = $r['prev'];
            }
        }
        $postpones = array_values(array_filter($postpones, function ($p) use ($f) {
            return ($p['old_date'] >= $f['from'] && $p['old_date'] <= $f['to']) || ($p['new_date'] >= $f['from'] && $p['new_date'] <= $f['to']);
        }));

        $kpi = ['planned' => 0, 'done' => 0, 'ontime' => 0, 'late' => 0, 'overdue' => 0, 'upcoming' => 0,
                'pass' => 0, 'fail' => 0, 'na' => 0, 'items' => 0, 'evals' => 0, 'score_sum' => 0.0,
                'postponed' => count($postpones), 'spare_value' => 0.0, 'photos' => 0];
        $machines = [];
        $byType = [];
        $byCs = [];
        $wrInfo = [];
        foreach ($events as &$e) {
            $done = (int)$e['status'] === 1 || $e['wr_id'];
            // วันที่เข้าทำจริง = actual_date ของใบงาน หรือ completed_at ของแผน — ห้ามใช้วันที่ตามแผนแทน
            if (!$e['actual_date'] && $done && $e['completed_at']) $e['actual_date'] = substr($e['completed_at'], 0, 10);
            $r = $e['wr_id'] ? ($res[(int)$e['wr_id']] ?? null) : null;
            if ($done) {
                $st = ($e['actual_date'] && $e['actual_date'] > $e['event_date']) ? 'late' : 'done';
            } else {
                $st = $e['event_date'] < $today ? 'overdue' : 'upcoming';
            }
            $e['state'] = $st;
            $e['pass'] = $r ? (int)$r['pass'] : 0;
            $e['fail'] = $r ? (int)$r['fail'] : 0;
            $e['na'] = $r ? (int)$r['na'] : 0;
            $e['photos'] = $e['wr_id'] ? ($photos[(int)$e['wr_id']] ?? 0) : 0;

            $kpi['planned']++;
            if ($done) { $kpi['done']++; $kpi[$st === 'late' ? 'late' : 'ontime']++; } else { $kpi[$st]++; }
            $kpi['pass'] += $e['pass']; $kpi['fail'] += $e['fail']; $kpi['na'] += $e['na'];
            $kpi['photos'] += $e['photos'];
            if ($e['average_score'] !== null) { $kpi['evals']++; $kpi['score_sum'] += (float)$e['average_score']; }

            $mid = (string)$e['machine_id'];
            if (!isset($machines[$mid])) {
                $machines[$mid] = ['id' => $mid, 'code' => $e['ass_code'], 'name' => $e['asset_name'], 'sn' => $e['asset_sn'],
                    'model' => $e['asset_model'], 'type' => $e['type_name'], 'location' => $e['location'],
                    'checksheets' => [], 'planned' => 0, 'done' => 0, 'overdue' => 0, 'fail' => 0, 'prev' => $prevDone[$mid] ?? null, 'last' => $lastDone[$mid] ?? null,
                    'wrs' => 0, 'photos' => 0, 'events' => []];
            }
            $m = &$machines[$mid];
            $m['planned']++;
            if ($done) { $m['done']++; $m['wrs']++; $m['photos'] += $e['photos']; }
            if ($st === 'overdue') $m['overdue']++;
            $m['fail'] += $e['fail'];
            if ($e['cs_name'] && !in_array($e['cs_name'], $m['checksheets'], true)) $m['checksheets'][] = $e['cs_name'];
            $m['events'][] = $e;
            unset($m);

            foreach ([['byType', $e['type_name'] ?: 'ไม่ระบุประเภท'], ['byCs', $e['cs_name'] ?: 'ไม่ระบุเช็คชีต']] as $g) {
                $arr = &${$g[0]};
                if (!isset($arr[$g[1]])) $arr[$g[1]] = ['name' => $g[1], 'machines' => [], 'planned' => 0, 'done' => 0, 'overdue' => 0, 'fail' => 0];
                $arr[$g[1]]['machines'][$mid] = true;
                $arr[$g[1]]['planned']++;
                if ($done) $arr[$g[1]]['done']++;
                if ($st === 'overdue') $arr[$g[1]]['overdue']++;
                $arr[$g[1]]['fail'] += $e['fail'];
                unset($arr);
            }
            if ($e['wr_id']) $wrInfo[(int)$e['wr_id']] = $e;
        }
        unset($e);
        $kpi['items'] = $kpi['pass'] + $kpi['fail'] + $kpi['na'];
        $kpi['rate'] = $kpi['planned'] ? round($kpi['done'] * 100 / $kpi['planned'], 1) : 0;
        $kpi['ontime_rate'] = $kpi['done'] ? round($kpi['ontime'] * 100 / $kpi['done'], 1) : 0;
        $kpi['pass_rate'] = ($kpi['pass'] + $kpi['fail']) ? round($kpi['pass'] * 100 / ($kpi['pass'] + $kpi['fail']), 1) : 0;
        $kpi['score_avg'] = $kpi['evals'] ? round($kpi['score_sum'] / $kpi['evals'], 2) : null;
        $kpi['machines'] = count($machines);

        // ผูกจุดไม่ผ่าน/อะไหล่ กับเครื่องและวันที่
        $failRows = [];
        foreach ($fails as $x) {
            $e = $wrInfo[(int)$x['work_record_id']] ?? null;
            if (!$e) continue;
            $failRows[] = ['machine_id' => (string)$e['machine_id'], 'code' => $e['ass_code'], 'name' => $e['asset_name'],
                'date' => $e['actual_date'] ?: $e['event_date'], 'check_point' => $x['check_point'],
                'actual' => trim($x['actual_value'] . ' ' . $x['unit']),
                'standard' => $x['expected_value'] !== null && $x['expected_value'] !== '' ? trim($x['expected_value'] . ' ' . $x['unit']) : (string)$x['value_name'],
                'remarks' => $e['remarks'], 'wr_id' => (int)$x['work_record_id']];
        }
        $spareRows = [];
        $spareSum = [];
        foreach ($spares as $x) {
            $e = $wrInfo[(int)$x['work_record_id']] ?? null;
            if (!$e) continue;
            $name = trim($x['rpd_details_head'] ?: $x['rpd_details']);
            $spareRows[] = ['machine_id' => (string)$e['machine_id'], 'code' => $e['ass_code'], 'machine' => $e['asset_name'],
                'date' => $e['actual_date'] ?: $e['event_date'], 'item_code' => $x['pd_gen_code'], 'item' => $name,
                'qty' => (float)$x['rpd_qty'], 'unit' => $x['pd_unit'], 'price' => (float)$x['rpd_price'], 'total' => (float)$x['rpd_sum_money']];
            $k = $x['pd_gen_code'] . '|' . $name . '|' . $x['pd_unit'];
            if (!isset($spareSum[$k])) $spareSum[$k] = ['item_code' => $x['pd_gen_code'], 'item' => $name, 'unit' => $x['pd_unit'], 'qty' => 0, 'total' => 0, 'times' => 0];
            $spareSum[$k]['qty'] += (float)$x['rpd_qty'];
            $spareSum[$k]['total'] += (float)$x['rpd_sum_money'];
            $spareSum[$k]['times']++;
            $kpi['spare_value'] += (float)$x['rpd_sum_money'];
        }
        $evalRows = [];
        foreach ($events as $e) {
            if ($e['average_score'] === null) continue;
            $evalRows[] = ['machine_id' => (string)$e['machine_id'], 'code' => $e['ass_code'], 'name' => $e['asset_name'],
                'date' => $e['eval_date'], 'pm_date' => $e['actual_date'], 'evaluator' => $e['evaluator_name'],
                'score' => (float)$e['average_score'], 'comments' => $e['eval_comments']];
        }
        $planMachine = [];
        foreach ($events as $e) $planMachine[(int)$e['event_id']] = $e;
        $postRows = [];
        foreach ($postpones as $p) {
            $e = $planMachine[(int)$p['plan_id']] ?? null;
            $postRows[] = ['machine_id' => $e ? (string)$e['machine_id'] : '', 'code' => $e['ass_code'] ?? '', 'name' => $e['asset_name'] ?? '',
                'old_date' => $p['old_date'], 'new_date' => $p['new_date'], 'reason' => $p['reason'], 'by' => $p['created_by']];
        }

        $finishGroups = function ($arr) {
            $out = [];
            foreach ($arr as $g) {
                $g['machines'] = count($g['machines']);
                $g['rate'] = $g['planned'] ? round($g['done'] * 100 / $g['planned'], 1) : 0;
                $out[] = $g;
            }
            usort($out, function ($a, $b) { return strcmp($a['name'], $b['name']); });
            return $out;
        };
        $spareSum = array_values($spareSum);
        usort($spareSum, function ($a, $b) { return $b['total'] <=> $a['total'] ?: strcmp($a['item'], $b['item']); });

        return [
            'agency' => pmr_agency($connect, $ag),
            'filters' => $f,
            'generated_at' => date('Y-m-d H:i'),
            'kpi' => $kpi,
            'by_type' => $finishGroups($byType),
            'by_checksheet' => $finishGroups($byCs),
            'machines' => array_values($machines),
            'fails' => $failRows,
            'spares' => $spareRows,
            'spare_summary' => $spareSum,
            'evaluations' => $evalRows,
            'postpones' => $postRows,
        ];
    }

    // PM History — 1 แถวต่อใบงานที่บันทึกแล้ว (แผนอยู่ในช่วงที่เลือก) เรียงตามวันที่ตามแผน
    function pmr_history($connect, $ag, $f) {
        $where = " WHERE e.ag_id = ? AND e.event_date BETWEEN ? AND ? ";
        $types = 'sss';
        $params = [$ag, $f['from'], $f['to']];
        if ($f['type'] !== '') { $where .= " AND a.asset_type = ? "; $types .= 's'; $params[] = $f['type']; }
        if ($f['cs'] !== '')   { $where .= " AND e.checksheet_id = ? "; $types .= 's'; $params[] = $f['cs']; }
        if ($f['m']) {
            $where .= " AND e.machine_id IN (" . implode(',', array_fill(0, count($f['m']), '?')) . ") ";
            $types .= str_repeat('s', count($f['m']));
            $params = array_merge($params, $f['m']);
        }
        $rows = pmr_query($connect, "
            SELECT w.id AS wr_id, e.event_date AS plan_date, w.actual_date, w.created_at, w.inspector_name, w.remarks,
                   a.ass_code, a.asset_name, a.asset_sn, g.TGroupName AS type_name,
                   CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location,
                   COALESCE(c.name, w.checksheet_name) AS cs_name, e.completed_at,
                   (SELECT fo.description FROM pm_freq_options fo WHERE fo.freq_value = p.frequency LIMIT 1) AS freq_text
            FROM pm_plan_events e
            JOIN pm_work_records w ON w.id = COALESCE(e.work_record_id, (SELECT w2.id FROM pm_work_records w2 WHERE w2.plan_event_id = e.id ORDER BY w2.id DESC LIMIT 1))
            LEFT JOIN tb_ass_list a ON a.ass_id = e.machine_id
            LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type
            LEFT JOIN tb_area ar ON ar.area_id = a.asset_rp_area_id
            LEFT JOIN tb_area_class ac ON ac.ac_id = a.asset_rp_ac_id
            LEFT JOIN tb_area_room rm ON rm.ar_id = a.asset_rp_ar_id
            LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
            LEFT JOIN pm_plans p ON p.id = e.plan_id
            $where
            ORDER BY e.event_date, a.ass_code, w.id", $types, $params);

        $ids = array_map(function ($r) { return (int)$r['wr_id']; }, $rows);
        $res = [];
        foreach (pmr_in_chunks($connect, "
            SELECT work_record_id, SUM(result_status = 'Pass') AS pass, SUM(result_status = 'Fail') AS fail, SUM(result_status = 'N/A') AS na
            FROM pm_work_record_items WHERE work_record_id IN ({IN}) GROUP BY work_record_id", $ids) as $r) {
            $res[(int)$r['work_record_id']] = $r;
        }
        $money = [];
        foreach (pmr_in_chunks($connect, "
            SELECT work_record_id, SUM(rpd_sum_money) AS total
            FROM pm_work_record_actual_spares WHERE work_record_id IN ({IN}) GROUP BY work_record_id", $ids) as $r) {
            $money[(int)$r['work_record_id']] = $r;
        }

        $out = [];
        foreach ($rows as $r) {
            $id = (int)$r['wr_id'];
            $x = $res[$id] ?? ['pass' => 0, 'fail' => 0, 'na' => 0];
            $pass = (int)$x['pass']; $fail = (int)$x['fail']; $na = (int)$x['na'];
            // วันที่เข้าทำจริง: actual_date → completed_at → เวลาบันทึกใบงาน
            $done = $r['actual_date'] ?: ($r['completed_at'] ? substr($r['completed_at'], 0, 10) : substr((string)$r['created_at'], 0, 10));
            $delay = ($done && $r['plan_date']) ? (int)floor((strtotime($done) - strtotime($r['plan_date'])) / 86400) : 0;
            $out[] = [
                'wr_id'      => $id,
                'done_date'  => $done,
                'done_time'  => $r['created_at'] ? date('H:i', strtotime($r['created_at'])) : '',
                'late'       => $delay > 0,
                'delay'      => max(0, $delay),
                'doc'        => 'PM ' . date('d/m/Y', strtotime($r['plan_date'])) . '_' . str_pad((string)$id, 4, '0', STR_PAD_LEFT),
                'plan_date'  => $r['plan_date'],
                'code'       => $r['ass_code'],
                'name'       => $r['asset_name'],
                'type'       => $r['type_name'],
                'machine'    => trim(($r['type_name'] ? $r['type_name'] . ' : ' : '') . '(' . $r['asset_name'] . ')'),
                'location'   => $r['location'],
                'serial'     => $r['asset_sn'],
                'checksheet' => $r['cs_name'],
                'freq'       => $r['freq_text'],
                'inspector'  => $r['inspector_name'],
                'pass'       => $pass, 'fail' => $fail, 'na' => $na,
                'pass_pct'   => ($pass + $fail) ? (int)round($pass * 100 / ($pass + $fail)) : null,
                'spare'      => (float)($money[$id]['total'] ?? 0),
                'remarks'    => $r['remarks'],
            ];
        }
        return $out;
    }

    // ---------- ตัวช่วยแสดงผล ----------
    function pmr_h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

    function pmr_thai_date($d, $short = true) {
        if (!$d || $d === '0000-00-00') return '-';
        $ts = strtotime($d);
        if (!$ts) return $d;
        $mS = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $mL = [1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $m = (int)date('n', $ts);
        return date('j', $ts) . ' ' . ($short ? $mS[$m] : $mL[$m]) . ' ' . (date('Y', $ts) + 543);
    }

    function pmr_period_text($f) {
        return pmr_thai_date($f['from'], false) . ' – ' . pmr_thai_date($f['to'], false);
    }

    function pmr_state_label($st) {
        $map = ['done' => 'ทำแล้ว', 'late' => 'ทำแล้ว (ล่าช้า)', 'overdue' => 'ค้าง/เกินกำหนด', 'upcoming' => 'ยังไม่ถึงกำหนด'];
        return $map[$st] ?? $st;
    }
}
