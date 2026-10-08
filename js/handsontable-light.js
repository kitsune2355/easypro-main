// handsontable-light.js — ค่าเริ่มต้น + CSS แก้ UI ของ Handsontable ใช้ทุกหน้า (โหลดต่อจาก handsontable.full.min.js)
//
// ทุกหน้าล็อก Handsontable ไว้ที่ 17.0.1 (npm/handsontable@17.0.1) — รุ่น 18 มี bug: ปุ่ม OK/Cancel ในเมนูกรองถูกตัด
// และ dropdown ในเซลล์มีแถบเลื่อนทั้งสองทิศ  ถ้าจะอัปเดตรุ่น ให้ทดสอบเมนูกรอง/dropdown/โหมดมืดของเครื่องก่อน
// (ตั้งแต่รุ่น 17 ไม่มีไฟล์ dist/handsontable.full.min.css แล้ว — CSS มากับ JS)
//
// 1) ธีมสว่างเสมอ: รุ่น 17+ เลือกสีด้วย light-dark() ตามโหมดของเครื่อง => บังคับ color-scheme: light ด้วย CSS
//    และตั้ง colorScheme: 'light' เป็นค่าเริ่มต้น (ตัวเลือกนี้มีในรุ่น 18 — รุ่นที่ไม่รู้จักจะข้ามไป)
// 2) กัน bug ของรุ่น 18 (เผื่ออัปเดตรุ่น): ตารางที่ซ้อนอยู่ในตารางอื่น — รายการ dropdown ในเซลล์ และรายการค่าในเมนูกรอง —
//    มีเส้นขอบเกินมา 1px แต่ Handsontable คำนวณขนาดกล่องโดยไม่นับ => เนื้อหาใหญ่กว่ากล่อง 2px => แถบเลื่อนทั้งสองทิศ
//    รายการไม่ต้องเลื่อนแนวนอน ข้อความยาวตัดด้วย …
(function () {
    if (!window.Handsontable || window.Handsontable.__lightTheme) return;

    var css = [
        // ธีม Handsontable 17+ ใช้ light-dark() + color-scheme: light dark => บังคับ light (ใช้ได้ทั้งรุ่นที่ไม่มีตัวเลือก colorScheme)
        '.handsontable, .ht-root-wrapper, [class*="ht-theme-"], .ht-portal { color-scheme: light !important; }',
        '.handsontable.listbox td, .handsontable.listbox th,',
        '.htUIMultipleSelectHot .handsontable td, .htUIMultipleSelectHot .handsontable th {',
        '    border-left-width: 0 !important;',
        '    border-top-width: 0 !important;',
        '}',
        '.handsontable.listbox table.htCore, .htUIMultipleSelectHot table.htCore { border-width: 0 !important; }',
        '.handsontable.listbox .wtHolder, .htUIMultipleSelectHot .wtHolder { overflow-x: hidden !important; }',
        '.handsontable.listbox td { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }'
    ].join('\n');
    var style = document.createElement('style');
    style.setAttribute('data-source', 'handsontable-light.js');
    style.textContent = css;
    (document.head || document.documentElement).appendChild(style);

    var Original = window.Handsontable;
    // Proxy ส่งต่อทุกอย่างไปที่ Handsontable ตัวจริง (renderers, hooks, plugins, instanceof ฯลฯ) — แก้แค่ตอน new
    window.Handsontable = new Proxy(Original, {
        construct: function (Target, args) {
            var settings = Object.assign({ colorScheme: 'light' }, args[1] || {});
            return new Target(args[0], settings);
        },
        get: function (target, prop, receiver) {
            if (prop === '__lightTheme') return true;
            return Reflect.get(target, prop, receiver);
        }
    });
})();
