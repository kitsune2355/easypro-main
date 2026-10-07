function getTextById(id) {
    const el = document.getElementById(id);
    if (!el) return '-';

    const span = el.querySelector('span');
    if (span) return span.innerText.trim() || '-';

    return el.innerText.trim() || '-';
}

function escapeHtml(value) {
    return String(value ?? '-')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getPrintRecordStatus(status) {
    if (!status) return '-';

    if (status === 'Pass') return '✓';
    if (status === 'Fail') return 'X';
    if (status === 'N/A') return 'N/A';

    return status;
}

function getPrintStatusClass(status) {
    if (status === 'Fail') return 'pm-print-fail';
    return 'pm-print-pass';
}

function getPrintableItems() {
    if (Array.isArray(window.pmHistoryItems) && window.pmHistoryItems.length > 0) {
        return window.pmHistoryItems.map(item => ({
            check_point: item.check_point || '-',
            standard_text: item.standard_text || '-',
            method_text: item.method_text || '-',
            action_text: item.action_abnormal || item.action_text || '-',
            result_status: item.result_status || '-',
            actual_value: item.actual_value || '-',
            unit: item.unit || '',
            expected_value: item.expected_value || ''
        }));
    }

    if (Array.isArray(worksheetItems) && worksheetItems.length > 0) {
        return worksheetItems.map(item => {
            const itemId = item.id;
            const checked = document.querySelector(`input[name="status_${itemId}"]:checked`);
            const valueInput = document.querySelector(`[name="value_${itemId}"], #value_${itemId}`);

            return {
                check_point: item.check_point || '-',
                standard_text: item.standard_text || '-',
                method_text: item.method_text || '-',
                action_text: item.action_text || item.action_abnormal || '-',
                result_status: checked ? checked.value : '-',
                actual_value: valueInput ? valueInput.value : '-',
                unit: item.unit || '',
                expected_value: item.expected_value || ''
            };
        });
    }

    return [];
}



function buildPmPrintReportHtml() {
    const docNo = getTextById('info-doc-no');
    const revNo = getTextById('info-rev-no');
    const checksheetName = getTextById('info-checksheet-name');
    const machineName = getTextById('info-machine-name');
    const machineSn = getTextById('info-machine-sn');
    const machineType = getTextById('info-machine-type');
    const locationText = getTextById('info-location');
    const planDate = getTextById('info-plan-date');
    const actualDate = getTextById('info-actual-date');

    const remarksEl = document.getElementById('remarks');
    const remarks = remarksEl ? remarksEl.value : '-';

    const inspectorNameEl = document.getElementById('inspector_name');
    const inspectorName = inspectorNameEl ? inspectorNameEl.value : '-';

    const signatureImg = document.querySelector('#inspector-signature-pad') 
        ? ''
        : '';

    const historySignaturePath = window.pmHistoryRecord?.inspector_signature_path || '';

    const items = getPrintableItems();

    let rowsHtml = '';

    rowsHtml += `
        <tr class="pm-print-category-row">
            <td class="pm-print-no"></td>
            <td colspan="3">Monthly P.M. / Maintenance Tasks</td>
        </tr>
    `;

    if (items.length === 0) {
        rowsHtml += `
            <tr>
                <td colspan="4" style="text-align:center;height:40px;">ไม่พบรายการตรวจสอบ</td>
            </tr>
        `;
    } else {
        items.forEach((item, index) => {
            const status = item.result_status || '-';
            const recordValue = status === 'Pass' || status === 'Fail' || status === 'N/A'
                ? getPrintRecordStatus(status)
                : escapeHtml(status);

            const actualText = item.actual_value && item.actual_value !== '-'
                ? `<div style="font-size:7px;">${escapeHtml(item.actual_value)} ${escapeHtml(item.unit || '')}</div>`
                : '';

            const standardText = item.expected_value
                ? `${escapeHtml(item.expected_value)} ${escapeHtml(item.unit || '')}`
                : escapeHtml(item.standard_text || '-');

            rowsHtml += `
                <tr>
                    <td class="pm-print-no">${index + 1}</td>
                    <td class="pm-print-task">
                        ${escapeHtml(item.check_point)}
                    </td>
                    <td class="pm-print-standard">${standardText}</td>
                    <td class="pm-print-record ${getPrintStatusClass(status)}">
                        ${recordValue}
                        ${actualText}
                    </td>
                </tr>
            `;
        });
    }

    const signImgHtml = historySignaturePath
        ? `<img src="${escapeHtml(historySignaturePath)}" class="pm-print-sign-img">`
        : `<div class="pm-print-line"></div>`;

    return `
        <div class="pm-print-page">
            <div class="pm-print-title">
                MAINTENANCE TASKS REPORT
                <div class="pm-print-logo">PROACTIVE<br><span style="font-size:7px;color:#555;">Management Co.,Ltd.</span></div>
            </div>

            <div class="pm-print-machine-title">
                ${escapeHtml(machineName || checksheetName)}
            </div>

            <table class="pm-print-info-table">
                <tr>
                    <td style="width:16%;">
                        <div class="pm-print-label">PROJECT TITLE :</div>
                    </td>
                    <td style="width:34%;">
                        <div class="pm-print-value">${escapeHtml(checksheetName)}</div>
                    </td>
                    <td style="width:12%;">
                        <div class="pm-print-label">DATE :</div>
                    </td>
                    <td style="width:18%;">
                        <div class="pm-print-value">${escapeHtml(actualDate)}</div>
                    </td>
                    <td style="width:10%;">
                        <div class="pm-print-label">DOC NO :</div>
                    </td>
                    <td style="width:10%;">
                        <div class="pm-print-value">${escapeHtml(docNo)}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="pm-print-label">ADDRESS :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(locationText)}</div>
                    </td>
                    <td>
                        <div class="pm-print-label">LOCATION :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(locationText)}</div>
                    </td>
                    <td>
                        <div class="pm-print-label">REV NO :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(revNo)}</div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" rowspan="3" class="pm-print-equipment-name">
                        ${escapeHtml(machineName)}
                    </td>
                    <td>
                        <div class="pm-print-label">EQUIPMENT CODE :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(machineType)}</div>
                    </td>
                    <td>
                        <div class="pm-print-label">PLAN DATE :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(planDate)}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="pm-print-label">BRAND / MODEL :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">-</div>
                    </td>
                    <td>
                        <div class="pm-print-label">S/N :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(machineSn)}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="pm-print-label">PERIOD :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">PM</div>
                    </td>
                    <td>
                        <div class="pm-print-label">SYSTEM :</div>
                    </td>
                    <td>
                        <div class="pm-print-value">${escapeHtml(machineType)}</div>
                    </td>
                </tr>
            </table>

            <table class="pm-print-task-table">
                <thead>
                    <tr>
                        <th colspan="2">TASKS</th>
                        <th>STANDARD</th>
                        <th>RECORD</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml}
                </tbody>
            </table>

            <div class="pm-print-legend">
                ✓ = Do PM &nbsp;&nbsp;&nbsp; X = Don't PM &nbsp;&nbsp;&nbsp; N = Normal &nbsp;&nbsp;&nbsp; AB = Abnormal &nbsp;&nbsp;&nbsp; - = Non Initial
            </div>

            <div class="pm-print-note-box">
                <div class="pm-print-note-title">RECOMMENDATIONS / REMARKS</div>
                <div>${escapeHtml(remarks)}</div>
            </div>

            <table class="pm-print-sign-table">
                <tr>
                    <td style="width:33.33%;">
                        <div class="pm-print-sign-title">Done By / ดำเนินการโดย</div>
                        <div class="pm-print-sign-sub">Signature/ลงนาม (Tech./ช่าง)</div>
                        ${signImgHtml}
                        <div>${escapeHtml(inspectorName)}</div>
                        <div style="margin-top:6px;">Date/วันที่ ${escapeHtml(actualDate)}</div>
                    </td>
                    <td style="width:33.33%;">
                        <div class="pm-print-sign-title">Checked By / ตรวจสอบโดย</div>
                        <div class="pm-print-sign-sub">Signature/ลงนาม (Engineer / Supervisor)</div>
                        <div class="pm-print-line"></div>
                        <div style="margin-top:6px;">Date/วันที่ __________________</div>
                    </td>
                    <td style="width:33.33%;">
                        <div class="pm-print-sign-title">Verified By / ทวนสอบโดย</div>
                        <div class="pm-print-sign-sub">Signature/ลงนาม (B.M./ผู้จัดการอาคาร)</div>
                        <div class="pm-print-line"></div>
                        <div style="margin-top:6px;">Date/วันที่ __________________</div>
                        <div style="background:#bdd7ee;border:1px solid #000;margin:8px 12px 4px;padding:3px;">
                            CUSTOMER'S ACCEPTANCE
                        </div>
                        <div class="pm-print-line"></div>
                    </td>
                </tr>
            </table>

            <div class="pm-print-footer">
                FM-PM-001 Rev.00 : ${escapeHtml(actualDate)}
            </div>
        </div>
    `;
}

function printPmWorksheetReport() {
    const printContainer = document.getElementById('print-pm-report');

    if (!printContainer) {
        Swal.fire('ผิดพลาด', 'ไม่พบพื้นที่สำหรับพิมพ์รายงาน', 'error');
        return;
    }

    printContainer.innerHTML = buildPmPrintReportHtml();

    setTimeout(() => {
        window.print();
    }, 300);
}