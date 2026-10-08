// handsontable-light.js — บังคับให้ตาราง Handsontable ทุกตัวใช้ธีมสว่าง (light)
// Handsontable 18 ขึ้นไปจะเปลี่ยนเป็นโหมดมืดตามการตั้งค่าของเครื่อง (prefers-color-scheme) ถ้าไม่ระบุ colorScheme
// โหลดไฟล์นี้ต่อจาก handsontable.full.min.js ทุกหน้า: ตารางที่สร้างด้วย new Handsontable(...) จะได้ colorScheme: 'light'
// เป็นค่าเริ่มต้น (ถ้าตารางไหนระบุ colorScheme เองก็ยังใช้ค่าของตารางนั้น)
(function () {
    if (!window.Handsontable || window.Handsontable.__lightTheme) return;
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
