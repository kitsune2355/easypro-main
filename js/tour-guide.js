document.addEventListener('DOMContentLoaded', () => {
    // 1. ย้ายการสร้าง Style ไปไว้ในตัวแปรเพื่อความสะอาด
    const injectStyles = () => {
        if (document.getElementById('driver-js-custom-style')) return;
        const style = document.createElement('style');
        style.id = 'driver-js-custom-style';
        style.innerHTML = `
        /* ปรับแต่งกล่อง Popover ให้ดู Modern */
        .driver-popover.easypro-custom-popover {
            border-radius: 16px !important;
            padding: 20px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            font-family: 'Kanit', sans-serif;
            max-width: 350px !important;
        }

        /* หัวข้อ */
        .driver-popover-title {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #004a6f !important;
            margin-bottom: 8px !important;
        }

        /* เนื้อหา */
        .driver-popover-description {
            font-size: 0.95rem !important;
            color: #4b5563 !important;
            line-height: 1.6 !important;
        }

        /* ปรับแต่งปุ่ม 'ถัดไป' และ 'เสร็จสิ้น' */
        .driver-popover-next-btn {
            background-color: #004a6f !important;
            color: white !important;
            text-shadow: none !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 500 !important;
            transition: all 0.2s;
        }

        .driver-popover-next-btn:hover {
            background-color: #003550 !important;
        }

        /* ปรับแต่งปุ่ม 'ก่อนหน้า' */
        .driver-popover-prev-btn {
            color: #6b7280 !important;
            border: 1px solid #e5e7eb !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            background: transparent !important;
            text-shadow: none !important;
        }

        /* จุด Progress Dot */
        .driver-popover-progress-text {
            color: #9ca3af !important;
            font-size: 0.85rem !important;
        }
    `;
        document.head.appendChild(style);
    };

    injectStyles();

    const driver = window.driver.js.driver;

    // --- Steps Configurations ---
    // 1. กำหนด Steps พื้นฐานสำหรับทุกคน (Main App)
    const mainSteps = [
        {
            element: '#logo-text',
            popover: {
                title: 'ยินดีต้อนรับสู่ EasyPro',
                description: 'ระบบจัดการงานบำรุงรักษาแบบครบวงจร',
                side: "bottom", align: 'start'
            }
        },
        {
            element: '#menu-container',
            popover: {
                title: 'เมนูการใช้งาน',
                description: 'เลือกเข้าถึงส่วนต่างๆ เช่น แจ้งซ่อม, ดูข้อมูลมิเตอร์ หรือจัดการสต็อกได้จากแถบนี้',
                side: "right", align: 'start'
            }
        },
        {
            element: '#page-title',
            popover: {
                title: 'หัวข้อหน้าปัจจุบัน',
                description: 'แสดงชื่อหน้าจอที่คุณกำลังใช้งานอยู่ในขณะนี้',
                side: "bottom", align: 'start'
            }
        },
        {
            element: '.glass-card',
            popover: {
                title: 'พื้นที่แสดงผล',
                description: 'ข้อมูลและฟอร์มต่างๆ จะแสดงผลในพื้นที่ส่วนกลางนี้',
                side: "left", align: 'center'
            }
        },
        {
            element: '#agency-selector-trigger',
            popover: {
                title: 'หน่วยงานที่กำลังใช้งาน',
                description: 'แสดงชื่อหน่วยงานปัจจุบัน และสำหรับ Super Admin สามารถคลิกเพื่อเปลี่ยนหน่วยงานได้ที่นี่',
                side: "left", align: 'end'
            }
        },
        {
            element: '#logout-btn',
            popover: {
                title: 'ออกจากระบบ',
                description: 'เมื่อใช้งานเสร็จสิ้น อย่าลืมคลิกที่นี่เพื่อความปลอดภัยของข้อมูล',
                side: "top", align: 'start'
            }
        }
    ];

    // 2. กำหนด Steps พิเศษสำหรับ Agency Modal (Super Admin)
    const agencyModalSteps = [
        {
            element: '#agencyModal .relative', // เจาะจงตัว Modal Container
            popover: {
                title: 'ขั้นตอนการเลือกหน่วยงาน',
                description: 'ในฐานะ Super Admin คุณต้องเลือกหน่วยงานที่ต้องการจัดการก่อนเข้าสู่ระบบหลัก',
                side: "bottom", align: 'center'
            }
        },
        {
            element: '#fCode',
            popover: {
                title: 'ค้นหาหน่วยงาน',
                description: 'คุณสามารถค้นหาด้วยรหัสหน่วยงาน หรือชื่อหน่วยงานเพื่อความรวดเร็ว',
                side: "top", align: 'start'
            }
        },
        {
            element: '#agencyTbody',
            popover: {
                title: 'รายการหน่วยงาน',
                description: 'คลิกเลือกหน่วยงานที่ต้องการจากตารางนี้',
                side: "top", align: 'center'
            }
        },
        {
            element: '#btnSelectAgency',
            popover: {
                title: 'ยืนยันการเลือก',
                description: 'เมื่อเลือกหน่วยงานแล้ว กดปุ่มยืนยันเพื่อเข้าสู่ EasyPro',
                side: "top", align: 'center'
            }
        }
    ];

    // --- ฟังก์ชันเริ่ม Tour ---
    window.startTour = (mode = 'main') => {
        const key = mode === 'agency' ? 'hasSeenAgencyTour' : 'hasSeenMainTour';
        localStorage.setItem(key, 'true');

        const activeSteps = mode === 'agency' ? agencyModalSteps : mainSteps;

        const tour = driver({
            showProgress: true,
            allowClose: true,
            overlayColor: 'rgb(0, 51, 77)',
            stagePadding: 10,
            animate: true,
            popoverClass: 'easypro-custom-popover',
            nextBtnText: 'ถัดไป',
            prevBtnText: 'ก่อนหน้า',
            doneBtnText: 'เริ่มใช้งานเลย',
            steps: activeSteps,

            onDestroy: () => {
                localStorage.setItem(key, 'true');
            }
        });

        tour.drive();
    };
});