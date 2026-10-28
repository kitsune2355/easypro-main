// ป๊อปอัปตรวจรับงาน PM: ลายมือชื่อผู้ตรวจรับ (บังคับ) + หมายเหตุ (ไม่บังคับ)
//  ใช้ใน pm_approve.php (แท็บตรวจรับ PM) และ pm_worksheet.php (ใบงานโหมดประวัติ) — ต้องโหลด SweetAlert2 และ signature_pad ก่อน
//  window.pmApproveSign({ title, text }) => Promise<{ signature: dataURL, comment } | null>
(function () {
    window.pmApproveSign = async function (opts) {
        opts = opts || {};
        let pad = null;
        const r = await Swal.fire({
            title: opts.title || 'ตรวจรับงาน PM',
            html: `<div style="text-align:left">
                    ${opts.text ? `<div style="font-size:13px;color:#475569;margin-bottom:10px">${opts.text}</div>` : ''}
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                        <label style="font-size:13px;font-weight:600;color:#334155">ลายมือชื่อผู้ตรวจรับ <span style="color:#dc2626">*</span></label>
                        <button type="button" id="pmap-sign-clear" style="font-size:12px;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;padding:2px 10px;background:#f8fafc">ล้าง</button>
                    </div>
                    <canvas id="pmap-sign" style="width:100%;height:160px;border:1px dashed #cbd5e1;border-radius:10px;background:#fff;touch-action:none;display:block"></canvas>
                    <textarea id="pmap-comment" maxlength="500" placeholder="หมายเหตุ (ไม่บังคับ)" style="margin-top:10px;width:100%;min-height:64px;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:14px;font-family:inherit"></textarea>
                </div>`,
            showCancelButton: true, reverseButtons: true, focusConfirm: false,
            confirmButtonText: '<i class="fas fa-check"></i> ตรวจรับ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#047857',
            didOpen: () => {
                const c = document.getElementById('pmap-sign');
                // ความคมชัดตามความหนาแน่นจอ (มือถือ/จอ retina)
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                c.width = c.offsetWidth * ratio; c.height = c.offsetHeight * ratio;
                c.getContext('2d').scale(ratio, ratio);
                pad = new SignaturePad(c, { penColor: 'rgb(15, 23, 42)', backgroundColor: 'rgba(255, 255, 255, 0)' });
                document.getElementById('pmap-sign-clear').addEventListener('click', () => pad.clear());
            },
            preConfirm: () => {
                if (!pad || pad.isEmpty()) { Swal.showValidationMessage('กรุณาลงลายมือชื่อผู้ตรวจรับ'); return false; }
                return { signature: pad.toDataURL('image/png'), comment: document.getElementById('pmap-comment').value.trim() };
            },
        });
        return r.isConfirmed ? r.value : null;
    };
})();
