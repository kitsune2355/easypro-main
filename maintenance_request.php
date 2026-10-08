<?php
@session_start();
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
  <title>ระบบแจ้งซ่อม - One View Premium</title> 
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script>
  const Toast = Swal.mixin({
	  toast: true,
	  position: 'top',
	  showConfirmButton: false,
	  timer: 3200,
	  timerProgressBar: true,
	  showClass: { popup: 'swal2-show' },
	  hideClass: { popup: 'swal2-hide' },
	  didOpen: (toast) => {
		toast.addEventListener('mouseenter', Swal.stopTimer);
		toast.addEventListener('mouseleave', Swal.resumeTimer);
	  }
	});
	
	function swalToast(message, icon = 'info') {
	  Toast.fire({
		icon,              // 'success' | 'error' | 'warning' | 'info'
		title: message
	  });
	}
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Prompt', 'sans-serif'],
            display: ['Plus Jakarta Sans', 'sans-serif']
          },
          colors: {
            primary: '#006B9F',
            secondary: '#04ADFF',
            accent: '#3B82F6'
          }
        }
      }
    }
  </script>

  <style>
  /* ✅ Fix iPhone Safari auto zoom ตอนกด input */
@media screen and (max-width: 768px) {
  input,
  textarea,
  select,
  button {
    font-size: 14px !important;
  }

  .input-modern,
  input[type="text"],
  input[type="tel"],
  input[type="date"],
  input[type="time"],
  input[type="number"],
  input[type="email"],
  textarea,
  select {
    font-size: 16px !important;
    line-height: 1.4 !important;
  }

  body {
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
  }
}
  /* กรอบแดงเฉพาะ input/textarea/select */
	.field-invalid{
	  border-color: #ef4444 !important;
	  box-shadow: 0 0 0 3px rgba(239,68,68,.18) !important;
	  background: #fff !important;
	}
	
	/* (ถ้าเป็น input แบบโปร่งใส) */
	.field-invalid:focus{
	  outline: none !important;
	}
  .req-border {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.18) !important;
    background: #fff !important;
  }
  .req-flash{
	  border-color: #ef4444 !important;
	  box-shadow: 0 0 0 3px rgba(239,68,68,0.18) !important;
	  background: #fff !important;
	}
	.req-shake{ animation: reqShake .28s ease-in-out; }
	@keyframes reqShake{
	  0%,100%{ transform: translateX(0); }
	  25%{ transform: translateX(-5px); }
	  75%{ transform: translateX(5px); }
	}
    html, body { height: 100%; }
   body {
  background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
  color: #334155;
  margin: 0;
  padding: 0;
  overflow-x: hidden;   /* กันเลื่อนข้าง */
  overflow-y: auto;     /* ให้เลื่อนลงได้ตลอด */
}

    /* ✅ Fullscreen form */
 #repair_form{
  position: relative;   /* ไม่ต้อง fixed แล้ว */
  inset: 0;
  width: 100vw;
  min-height: 100vh;    /* อย่างน้อยสูงเท่าจอ */
  height: auto;         /* ถ้าของยาวก็ยืดต่อได้ */
}
    .glass-panel {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.1);
    }

    .input-modern {
      width: 100%;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 0.75rem;
      padding: 0.5rem 0.85rem;
      font-size: 0.875rem;
      transition: all 0.2s;
    }
    .input-modern:focus {
      outline: none;
      border-color: #006B9F;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(0, 107, 159, 0.1);
    }

    .label-head {
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 0.35rem;
      display: block;
    }

    .btn-gradient {
      background: linear-gradient(to right, #006B9F, #04ADFF);
      color: white;
      transition: all 0.3s ease;
    }
    .btn-gradient:hover {
      box-shadow: 0 10px 20px -5px rgba(0, 107, 159, 0.4);
      transform: translateY(-1px);
    }

    /* Modal blur overlay */
    .modal-blur {
      backdrop-filter: blur(12px) saturate(180%);
      background-color: rgba(15, 23, 42, 0.4);
    }

    .location-trigger {
      width: 100%;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 1.5rem;
      padding: 1rem;
      display: flex;

      flex-direction: column;
      gap: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .location-trigger:hover {
      border-color: rgba(0, 107, 159, 0.5);
      box-shadow: 0 10px 25px rgba(0, 107, 159, 0.05);
    }

    .location-card {
      display: flex;
      flex-direction: column;
      padding: 1.25rem;
      background: #fff;
      border: 1px solid #f1f5f9;
      border-radius: 1.75rem;
      transition: all 0.3s;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }
    .location-card:hover {
      box-shadow: 0 18px 35px rgba(0, 107, 159, 0.10);
      border-color: rgba(0, 107, 159, 0.30);
      transform: translateY(-2px);
    }
    .location-card.active {
      border-color: #006B9F;
      background: rgba(0, 107, 159, 0.05);
      box-shadow: 0 10px 20px rgba(0, 107, 159, 0.10);
      outline: 2px solid rgba(0, 107, 159, 0.10);
      outline-offset: 0px;
    }

    .step-indicator {
      height: 6px;
      border-radius: 9999px;
      background: #f1f5f9;
      overflow: hidden;
      position: relative;
      width: 128px;
    }
    .step-progress {
      position: absolute;
      inset: 0 auto 0 0;
      height: 100%;
      background: #006B9F;
      transition: width 0.5s ease-out;
      width: 33%;
    }

    .animate-fade-up { animation: fadeUp 0.4s ease-out forwards; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }

    .shake { animation: shake 0.35s ease-in-out; }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-6px); }
      75% { transform: translateX(6px); }
    }
	 .input-error {
  border-color: #ef4444 !important;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.22) !important;
  background: #fff !important;
}

/* ✅ Building card UI (ให้ธีมเดียวกับ floor-card) */
.building-card{
  border: 1px solid #e2e8f0;
  border-radius: 1.75rem;
  background: #fff;
  padding: 18px;
  cursor: pointer;
  transition: all .25s ease;
  min-height: 140px;
  box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
}
.building-card:hover{
  transform: translateY(-2px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
}
.building-card .bld-icon{
  width: 44px; height: 44px;
  border-radius: 14px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(0, 107, 159, 0.08);
  color: #006B9F;
  border: 1px solid rgba(0, 107, 159, 0.15);
}
.building-card .bld-title{
  font-size: 15px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.25;
  /* ✅ แสดงชื่อเต็ม ไม่ตัด ... */
  white-space: normal;
  overflow: visible;
  word-break: break-word;
}
.building-card .bld-sub{
  font-size: 12px;
  font-weight: 700;
  color:#64748b;
}
.building-card .bld-badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  font-size: 11px;
  font-weight: 800;
  color:#475569;
  white-space: nowrap;
}
.building-card.active{
  border-color: #006B9F;
  box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
  outline: 2px solid rgba(0, 107, 159, 0.10);
}




/* ✅ Floor card UI */
.floor-card{
  border: 1px solid #e2e8f0;
  border-radius: 1.75rem;
  background: #fff;
  padding: 18px;
  cursor: pointer;
  transition: all .25s ease;
  min-height: 140px;
  box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
}
.floor-card:hover{
  transform: translateY(-2px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
}
.floor-card .floor-icon{
  width: 44px; height: 44px;
  border-radius: 14px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(0, 107, 159, 0.08);
  color: #006B9F;
  border: 1px solid rgba(0, 107, 159, 0.15);
}
.floor-card .floor-no{
  font-size: 32px;
  font-weight: 800;
  line-height: 1;
  color: #0f172a;
}
.floor-card .floor-name{
  font-size: 13px;
  font-weight: 800;
  color: #334155;
}
.floor-card .room-badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  font-size: 11px;
  font-weight: 800;
  color:#475569;
}
.floor-card.active{
  border-color: #006B9F;
  box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
  outline: 2px solid rgba(0, 107, 159, 0.10);
}

/* ✅ Room card UI (ให้ธีมเดียวกับ floor card) */
.room-card{
  border: 1px solid #e2e8f0;
  border-radius: 1.75rem;
  background: #fff;
  padding: 18px;
  cursor: pointer;
  transition: all .25s ease;
  min-height: 180px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
}
.room-card:hover{
  transform: translateY(-2px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
}
.room-card .rm-icon{
  width: 46px; height: 46px;
  border-radius: 16px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(0, 107, 159, 0.08);
  color: #006B9F;
  border: 1px solid rgba(0, 107, 159, 0.15);
  margin: 0 auto 10px auto;
}
.room-card .rm-title{
  font-size: 14px;
  font-weight: 900;
  color:#0f172a;
  line-height: 1.25;
  white-space: normal;      /* ✅ ชื่อเต็ม */
  word-break: break-word;
}
.room-card .rm-sub{
  margin-top: 6px;
  font-size: 11px;
  font-weight: 800;
  color:#64748b;
}
.room-card.active{
  border-color: #006B9F;
  background: rgba(0, 107, 159, 0.04);
  box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
  outline: 2px solid rgba(0, 107, 159, 0.10);
}

    :root{
      --color-bg-light:#F0F4F8;
      --color-primary:#006B9F;
      --color-secondary:#04ADFF;

      --ag-accent-color:#006B9F;
      --ag-header-background-color:#f8fafc;
      --ag-row-hover-color:#f1f5f9;
      --ag-selected-row-color:#e2e8f0;
      --ag-font-family:'Kanit', sans-serif;
      --ag-font-size:13px;
    } 
	
  </style>
</head>

<body>

  <form id="repair_form" method="POST" enctype="multipart/form-data"
        class="glass-panel max-w-none max-h-none rounded-none lg:rounded-3xl flex flex-col relative z-10">

    <!-- TOP BAR --> 
	
	
  <!-- Header -->
  <nav class="flex-none px-3 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-30">
    <div class="flex items-center gap-3">
      <div class="p-2.5 bg-[--color-primary] rounded-xl shadow-lg text-white">
        <i data-lucide="wrench" class="w-6 h-6"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-900 leading-none">Maintenance System</h1>
        <p class="text-sm text-slate-500 mt-1">แจ้งซ่อมออนไลน์</p>
      </div>
    </div>

    
      <div class="flex items-center gap-4">
        <div id="realtime_clock" class="hidden md:block text-[10px] font-bold text-slate-400 uppercase tracking-widest font-display">00:00:00</div>
        <div class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">New Repair Request</div>
      </div>
  </nav>

    <!-- CONTENT -->
    <div class="flex-1 grid grid-cols-12">
<!-- LEFT PANEL -->
      <div class="col-span-12 lg:col-span-4 border-r border-slate-100 p-6 flex flex-col gap-6 bg-white/20">

        <!-- DATE/TIME -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 text-primary">
            <i data-lucide="clock" class="w-4 h-4"></i>
            <h2 class="text-sm font-bold uppercase tracking-tight">วัน/เวลา ที่แจ้งซ่อม</h2>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="bg-slate-50 border border-slate-100 p-3 rounded-2xl">
              <label class="label-head text-[10px]">วันที่แจ้ง</label>
              <input type="date" name="report_date" id="report_date"
                     class="w-full bg-transparent text-sm font-bold text-slate-700 focus:outline-none" required>
            </div>

            <div class="bg-slate-50 border border-slate-100 p-3 rounded-2xl">
              <label class="label-head text-[10px]">เวลาที่แจ้ง</label>
              <input type="time" name="report_time" id="report_time"
                     class="w-full bg-transparent text-sm font-bold text-slate-700 focus:outline-none" required>
            </div>
          </div>
        </div>

        <!-- REPORTER -->
        <div class="space-y-4 pt-4 border-t border-slate-100">
          <div class="flex items-center gap-2 text-primary">
            <i data-lucide="user-circle" class="w-4 h-4"></i>
            <h2 class="text-sm font-bold uppercase tracking-tight">ข้อมูลผู้แจ้ง</h2>
          </div>

          <div>
            <label class="label-head">ชื่อ / ฝ่าย / สำนักงาน ตัวอย่าง ( นาย ก / จบท. ) <span class="text-red-500">*</span></label>
            <input type="text" name="name" required class="input-modern" placeholder="ระบุชื่อผู้แจ้ง">
          </div>

          <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100/50 shadow-sm">
            <div class="flex justify-between items-center mb-2">
              <label class="label-head !mb-0">เบอร์โทรศัพท์ / เบอร์สำนักงาน <span class="text-red-500" id="phone_req">*</span></label>
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="no_phone" class="w-3.5 h-3.5 text-primary rounded border-slate-300">
                <span class="text-[10px] text-slate-500 font-medium">ไม่มีเบอร์โทร</span>
              </label>
            </div>

            <input type="hidden" name="no_phone" id="no_phone_flag" value="0">
            <input type="tel" name="phone" id="phone_input" required class="input-modern" placeholder="0xx-xxxxxxx" inputmode="tel">
            <p id="phone_hint" class="text-[10px] text-slate-400 mt-2 hidden">* เลือก “ไม่มีเบอร์โทร” ระบบจะบันทึกเป็น no_phone=1</p>
          </div>
        </div>

        <!-- LOCATION -->
        <div class="space-y-4 pt-4 border-t border-slate-100">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-primary">
              <i data-lucide="map-pin" class="w-4 h-4"></i>
              <h2 class="text-sm font-bold uppercase tracking-tight">ชั้น <span class="text-red-500">*</span></h2>
            </div>
            <span id="loc_status" class="text-[10px] text-slate-400 font-bold uppercase hidden">Selected</span>
          </div>

          <p id="loc_error" class="hidden text-[11px] text-red-500 font-semibold">กรุณาเลือกสถานที่ (อาคาร/ชั้น/ห้อง) ก่อนกดยืนยัน</p>

          <div id="loc_trigger" onClick="openSpatialModal()" class="location-trigger">
           

           <div id="loc_placeholder">
			  <div class="flex items-center gap-3">
				<!-- ICON LEFT -->
				<div class="w-11 h-11 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
				  <i data-lucide="map-pin" class="w-5 h-5"></i>
				</div>
			
				<!-- TEXT -->
				<div class="flex-1 min-w-0">
				  <p class="text-xs font-extrabold text-slate-700">
					ระบุตำแหน่งที่แจ้งซ่อม
				  </p>
				  <p class="text-[10px] text-slate-400 font-semibold">
					กดเพื่อเลือก อาคาร / สาขา → ชั้น → ห้อง
				  </p>
				</div>
			
				<!-- CHEVRON -->
				<div class="text-slate-300">
				  <i data-lucide="chevron-right" class="w-5 h-5"></i>
				</div>
			  </div>
			</div>
            <div id="loc_selected" class="hidden">
			  <div class="flex flex-col gap-2">
				<div class="flex items-center justify-between">
				  <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
					<i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
					Building
				  </span>
			
				  <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1 inline-flex items-center gap-1.5">
					<i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
					Selected
				  </span>
				</div>
			
				<span id="disp_building" class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
				  <i data-lucide="map-pin" class="w-4 h-4 text-primary"></i>
				  <span class="min-w-0 break-words"></span>
				</span>
			
				<div class="flex flex-wrap gap-2 mt-1">
				  <span class="px-2 py-1 bg-blue-50 text-primary text-[10px] font-extrabold rounded-lg border border-blue-100 inline-flex items-center gap-1.5">
					<i data-lucide="layers" class="w-3.5 h-3.5"></i>
					  <span id="disp_floor"></span>
				  </span>
			
				  <span class="px-2 py-1 bg-slate-50 text-slate-600 text-[10px] font-extrabold rounded-lg border border-slate-200 inline-flex items-center gap-1.5"
					  id="disp_room">
				  <i data-lucide="door-open" class="w-3.5 h-3.5"></i>
				  <span id="disp_room_text">ห้อง -</span>
				</span>
				</div>
			  </div>
			</div>


            <input type="hidden" name="building" id="in_building" required>
            <input type="hidden" name="floor" id="in_floor" required>
            <input type="hidden" name="room" id="in_room" required>
          </div>
        </div>

      </div>

      <!-- RIGHT PANEL (Enterprise + Minimal cards) -->
      <div class="col-span-12 lg:col-span-8 pt-0 px-6 pb-6 flex flex-col gap-2 bg-white/50">
<!-- Page Title -->
        <div class="relative z-10 flex items-center gap-2 text-slate-800 shrink-0">
<i data-lucide="clipboard-list" class="w-4 h-4 text-primary"></i>
          <div class="flex items-baseline gap-2"><br>

            <h2 class="text-sm font-bold uppercase tracking-tight">รายละเอียดปัญหา</h2>
            <span class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">Issue Details</span>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-2 min-h-0">

          <!-- CARD 1: Asset Selector --><section class="relative z-20 rounded-none border-0 bg-transparent shadow-none overflow-visible">

             

            <div class="pt-0 px-0 pb-2 space-y-3">

              <!-- Selected (compact) -->
              <div id="asset_selected_card" class="hidden rounded-xl border border-primary/15 bg-primary/5 px-4 py-3 max-w-3xl w-full">
                <div class="flex items-start justify-between gap-3">
                  
                  <div class="min-w-0 flex-1">
                    
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                      <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-2.5 py-1 text-[10px] font-extrabold text-primary border border-primary/15">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span> SELECTED 
                      </span>
                      <span id="asset_badge_type" class="text-[10px] font-extrabold text-slate-500 bg-white/70 border border-slate-200 rounded-full px-2.5 py-1 whitespace-nowrap">-</span>
                      <span id="asset_sel_status_wrap"></span>
                      <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold text-slate-600 bg-white/70 border border-slate-200 rounded-lg px-2.5 py-1 whitespace-nowrap">
                        <i data-lucide="users" class="w-4 h-4"></i> <span id="asset_sel_owner" class="text-slate-800">-</span>
                      </span>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mt-1">
                      <span class="font-extrabold text-slate-700 text-[13px] shrink-0" id="asset_sel_code">-</span>
                      <span class="text-[14px] sm:text-[15px] font-extrabold text-slate-800 leading-snug break-words" id="asset_sel_name">-</span>
                    </div>

                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[12px] text-slate-500 font-semibold">
                      สถานที่ <span class="text-slate-300 hidden sm:inline">•</span> 
                      <span id="asset_sel_loc_html" class="min-w-0 break-words w-full sm:w-auto"></span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2"> </div>
                  </div>
                  
                  <button type="button" id="asset_clear_btn" class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-white border border-slate-200 text-slate-500 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-all flex items-center justify-center">
                    <i data-lucide="x" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                  </button>
                </div>
              </div>

              <!-- Search -->
             <div>
  <label class="label-head">ค้นหาเครื่อง/ทรัพย์สิน</label>

  <!-- ✅ แทน relative เดิมด้วย flex -->
  <div class="flex items-center gap-2">
    <!-- input -->
    <div class="relative flex-1 min-w-0">
      <i data-lucide="search" class="absolute left-3 top-3.5 w-4 h-4 text-slate-300"></i>

      <input id="asset_search" type="text" autocomplete="off"
        class="input-modern pl-10 pr-3 font-semibold bg-white"
        placeholder="พิมพ์รหัส / ชื่อ / ห้อง / อาคาร..." />
 

                  <!-- Dropdown -->
                  <div id="asset_dropdown"
  class="hidden absolute z-50 left-0 right-0 mt-2 rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
<div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">
                      <div class="flex items-center justify-between gap-3">
                        <div class="text-[11px] font-extrabold text-slate-500">
                          ผลการค้นหา <span id="asset_count" class="text-slate-800">0</span> รายการ
                        </div>
                        <div class="hidden md:flex items-center gap-2">
                          <span class="text-[10px] font-extrabold text-slate-400 bg-white border border-slate-200 rounded-full px-2.5 py-1">
                            Enter เพื่อเลือก
                          </span>
                          <span class="text-[10px] font-extrabold text-slate-400 bg-white border border-slate-200 rounded-full px-2.5 py-1">
                            ↑↓ เลื่อน
                          </span>
                        </div>
                      </div>

                      <div id="asset_filters" class="hidden mt-3 flex flex-wrap gap-2"></div>
                    </div>

                    <div id="asset_list" class="max-h-[340px] overflow-y-auto"></div>

                    <div id="asset_empty" class="hidden px-4 py-8 text-center">
                      <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300">
                        <i data-lucide="inbox" class="w-6 h-6"></i>
                      </div>
                      <p class="mt-3 text-sm font-extrabold text-slate-700">ไม่พบรายการ</p>
                      <p class="text-[12px] text-slate-400 font-semibold">ลองค้นหาด้วย รหัส/ชื่อ/อาคาร/ชั้น/ห้อง</p>
                    </div>
                  </div>
                </div> 

    <!-- buttons -->
    <button type="button" id="asset_filter_btn"
      class="h-9 px-3 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 text-[11px] font-extrabold transition shrink-0">
      Filter
    </button>

    <button type="button" id="asset_open_btn"
      class="h-9 px-3 rounded-xl bg-primary text-white hover:brightness-110 text-[11px] font-extrabold transition shadow-sm shrink-0">
      Browse
    </button>
  </div>

  <input type="hidden" name="asset_id" id="asset_id" value="">
  <input type="hidden" name="asset_code" id="asset_code" value="">
</div>

            </div>
          </section>

          <!-- CARD 2: Urgency --> 
		  <div id="urgency_section">
            <div class="flex items-center justify-between">
              <label class="text-[12px] font-extrabold text-slate-700">
                ระดับความเร่งด่วน (Urgency Level) <span class="text-red-500">*</span>
              </label>
              <span class="text-[10px] font-extrabold text-slate-300 uppercase tracking-widest">Priority</span>
            </div>

            <input type="hidden" name="urgency" id="urgency_input" value="medium">

            <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3">
              <button type="button" onClick="setUrgency('low')" id="btn_urg_low"
                class="urgency-btn relative group flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border transition-all duration-300 bg-slate-50 border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 hover:scale-[1.02] active:scale-95">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-sm group-hover:scale-125 transition-transform duration-300"></span>
                <span class="text-xs font-bold text-slate-600 group-hover:text-emerald-700">ต่ำ</span>
              </button>

              <button type="button" onClick="setUrgency('medium')" id="btn_urg_medium"
                class="urgency-btn relative group flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border transition-all duration-300 bg-blue-500 border-blue-500 shadow-lg shadow-blue-500/30 scale-[1.02]">
                <span class="w-2 h-2 rounded-full bg-white shadow-sm"></span>
                <span class="text-xs font-bold text-white">ปานกลาง</span>
              </button>

              <button type="button" onClick="setUrgency('high')" id="btn_urg_high"
                class="urgency-btn relative group flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border transition-all duration-300 bg-slate-50 border-slate-200 hover:bg-amber-50 hover:border-amber-200 hover:scale-[1.02] active:scale-95">
                <span class="w-2 h-2 rounded-full bg-amber-500 shadow-sm group-hover:scale-125 transition-transform duration-300"></span>
                <span class="text-xs font-bold text-slate-600 group-hover:text-amber-700">สูง</span>
              </button>

              <button type="button" onClick="setUrgency('critical')" id="btn_urg_critical"
                class="urgency-btn relative group flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border transition-all duration-300 bg-slate-50 border-slate-200 hover:bg-rose-50 hover:border-rose-200 hover:scale-[1.02] active:scale-95">
                <span class="w-2 h-2 rounded-full bg-rose-500 shadow-sm group-hover:scale-125 transition-transform duration-300"></span>
                <span class="text-xs font-bold text-slate-600 group-hover:text-rose-700">เร่งด่วนมาก</span>
              </button>
            </div> 
          </div> 

          <!-- CARD 3: Problem -->
          <section class="rounded-3xl border border-slate-100 bg-white/70 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
              <label  for="problem_detail" class="text-[12px] font-extrabold text-slate-700">
                อาการเสีย/ปัญหาที่พบ <span class="text-red-500">*</span>
              </label>
              <span class="text-[10px] font-extrabold text-slate-300 uppercase tracking-widest">Description</span>
            </div>

            <textarea name="problem_detail" required
              class="w-full h-32 md:h-30 p-4 rounded-2xl bg-white border border-slate-200 focus:border-primary/30 focus:outline-none transition-all shadow-sm text-sm resize-none"
              placeholder="กรุณาระบุรายละเอียดปัญหาที่พบ..."></textarea>
          </section>

          <!-- CARD 4: Images -->
          <section class="rounded-3xl border border-slate-100 bg-white/70 shadow-sm p-5">
            <div class="flex items-center justify-between">
              <label class="text-[12px] font-extrabold text-slate-700">รูปภาพ (สูงสุด 5 รูป)</label>
              <span id="img_count" class="text-[10px] font-extrabold text-slate-400">0/5</span>
            </div>

            <p id="img_error" class="hidden text-[11px] text-red-500 font-semibold mt-2">
              เลือกได้สูงสุด 5 รูปเท่านั้น (ระบบจะตัดไฟล์เกินออกให้อัตโนมัติ)
            </p>

            <div id="dropzone"
              class="mt-3 relative flex gap-3 w-full rounded-2xl border border-dashed border-slate-200 bg-white p-3 transition-all"
              role="button" tabindex="0"
              aria-label="ลากไฟล์รูปมาวาง หรือกดเพื่อเลือกไฟล์">

              <input type="file" id="file_input" name="images[]" multiple class="hidden" accept="image/jpeg,image/png,image/webp">

              <button id="btn_add" type="button"
                class="w-[86px] h-[86px] shrink-0 rounded-2xl border-2 border-dashed border-slate-200 bg-white
                       flex flex-col items-center justify-center gap-1 cursor-pointer
                       hover:bg-slate-50 hover:border-slate-300 transition group">
                <i data-lucide="camera" class="w-6 h-6 text-slate-300 group-hover:text-slate-400 transition"></i>
                <span class="text-[9px] font-extrabold text-slate-500 uppercase tracking-widest">ADD PHOTO</span>
                <span class="text-[9px] font-semibold text-slate-400">ลากมาวางได้</span>
              </button>

              <div id="preview_images" class="flex-1 flex gap-2.5 overflow-x-auto scrollbar-hide items-center min-h-[86px]">
                <div id="empty_state" class="w-full text-center text-[10px] font-semibold text-slate-300">
                  ลากรูปมาวางที่นี่ หรือกด ADD PHOTO
                </div>
              </div>

              <div id="drop_overlay"
                class="absolute inset-0 rounded-2xl border-2 border-primary/40 bg-primary/5 hidden items-center justify-center pointer-events-auto">
                <div class="text-xs font-bold text-primary">ปล่อยไฟล์เพื่อแนบรูป</div>
              </div>
            </div>

            <div class="mt-2 text-[10px] text-slate-400">
              รองรับ: JPG / PNG / WEBP • ระบบจะเก็บสูงสุด 5 รูป
            </div>
          </section>

        </div>
      </div>

    </div>

    <!-- FOOTER -->
    <div class="h-16 bg-white border-t border-slate-100 px-8 flex justify-between items-center shrink-0">
      <button type="reset"
  class="group inline-flex items-center gap-2 px-4 py-2 rounded-xl
         bg-white border border-slate-200 text-slate-600
         shadow-sm hover:shadow-md hover:bg-blue-50 hover:border-blue-200
         hover:text-blue-700 active:scale-95 transition-all">
  
  <span class="w-8 h-8 rounded-xl bg-slate-50 border border-slate-200
               flex items-center justify-center text-slate-400
               group-hover:bg-blue-100 group-hover:border-blue-200 group-hover:text-blue-600 transition">
    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
  </span>

  <span class="text-xs font-extrabold uppercase tracking-widest">
    ล้างข้อมูล
  </span>
</button>

      <button type="submit" class="btn-gradient px-10 py-2.5 rounded-xl shadow-lg flex items-center gap-2.5 font-bold text-sm">
        <i data-lucide="send" class="w-4 h-4"></i>
        ยืนยันแจ้งซ่อม
      </button>
    </div>

  </form>

  <!-- LOCATION MODAL -->
  <div id="spatial_modal" class="modal-blur fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
    <div id="modal_backdrop" class="absolute inset-0"></div>

    <div class="modal-panel w-[92vw] max-w-6xl h-[88vh] bg-white rounded-[3rem] shadow-2xl flex flex-col overflow-hidden relative z-10">


      <div class="p-8 pb-4 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-4">
          <button onClick="goBackStep()" id="btn_back" class="hidden w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center hover:bg-slate-100 transition-all">
            <i data-lucide="arrow-left" class="w-5 h-5 text-slate-600"></i>
          </button>
          <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight" id="modal_title">เลือกอาคาร</h2>
            <div class="flex items-center gap-2 mt-1.5">
              <span class="text-[10px] font-bold text-primary uppercase tracking-wider" id="step_label">Step 1</span>
              <div class="step-indicator">
                <div id="step_bar" class="step-progress"></div>
              </div>
            </div>
          </div>
        </div>
        <button onClick="closeSpatialModal()" class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
          <i data-lucide="x" class="w-6 h-6"></i>
        </button>
      </div>

      <div class="px-8 mb-6 shrink-0">
        <div class="relative">
          <i data-lucide="search" class="absolute left-5 top-4 w-5 h-5 text-slate-400"></i>
          <input type="text" id="spatial_search" onKeyUp="filterSpatialList()"
                class="w-full bg-slate-50 rounded-2xl py-4 pl-14 pr-6 text-sm font-medium border-none focus:ring-2 focus:ring-primary/20 transition-all outline-none"
                placeholder="ค้นหา...">
        </div>
      </div>

      <div class="flex-1 overflow-hidden relative px-8 pb-8">
        <div id="view_building" class="h-full overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-20 animate-fade-up"></div>
        <div id="view_floor" class="hidden h-full overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-20"></div>
        <div id="view_room" class="hidden h-full overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-20"></div>
      </div>

      <div class="modal-footer absolute bottom-0 left-0 w-full h-24 bg-white/90 backdrop-blur-md border-t border-slate-100 px-8 flex items-center justify-between z-10">

        <div class="flex flex-col">
          <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Current Selection</span>
          <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
            <span id="stat_bld" class="opacity-50">...</span>
            <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
            <span id="stat_flr" class="opacity-50">...</span>
            <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
            <span id="stat_rm" class="opacity-50">...</span>
          </div>
        </div>

        <button id="btn_confirm" disabled onClick="confirmSelection()"
                class="btn-gradient px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-primary/20">
          Confirm Selection
        </button>
      </div>

    </div>
  </div>

  <!-- Lightbox -->
  <div id="img_lightbox" class="fixed inset-0 z-[999] hidden">
    <div id="img_lightbox_backdrop" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    <div class="relative z-10 h-full w-full flex items-center justify-center p-4">
      <div class="w-full max-w-4xl">
        <div class="flex items-center justify-between mb-3">
          <div class="text-[11px] font-bold text-white/80">
            <span id="lb_index">1</span>/<span id="lb_total">1</span>
          </div>
          <button id="lb_close" type="button"
            class="px-3 py-2 rounded-xl bg-white/10 text-white text-xs font-bold hover:bg-white/20">
            ปิด
          </button>
        </div>

        <div class="relative rounded-2xl overflow-hidden bg-black/20 border border-white/10">
          <img id="lb_img" src="" alt="preview" class="w-full max-h-[75vh] object-contain select-none">

          <button id="lb_prev" type="button"
            class="absolute left-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded-xl bg-white/10 text-white text-xs font-bold hover:bg-white/20">
            ◀
          </button>
          <button id="lb_next" type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded-xl bg-white/10 text-white text-xs font-bold hover:bg-white/20">
            ▶
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
   


/**
 * ✅ เช็ค required ที่เป็น input/textarea/select เท่านั้น
 * - ข้าม hidden
 * - ข้าม disabled
 * - รองรับ checkbox/radio required
 */ 
 
  // =========================
  // ✅ SAFE ICON INIT
  // =========================
var REPAIR_AG_ID = '<?php echo isset($sess_user_agency_es) ? addslashes($sess_user_agency_es) : ""; ?>';

async function loadOtherSettingsForRepairForm() {
  var urgencySection = document.getElementById('urgency_section');
  var urgencyInput = document.getElementById('urgency_input');

  if (urgencyInput) urgencyInput.value = urgencyInput.value || 'medium';

  try {
    var url = 'handle_repair_setting.php?action=get_setting&key=show_urgency_level&ag_id=' + encodeURIComponent(REPAIR_AG_ID || '');
    var res = await fetch(url, { cache: 'no-store' });
    var text = await res.text();
    var json = JSON.parse(text);

    if (!json.success) {
      console.error('OTHER SETTING API ERROR:', json);
      if (urgencySection) urgencySection.style.display = '';
      return;
    }

    var showUrgency = String(json.data.setting_value || '1') === '1';

    if (urgencySection) {
      urgencySection.style.display = showUrgency ? '' : 'none';
    }

    if (!showUrgency && urgencyInput) {
      urgencyInput.value = 'medium';
    }

    if (showUrgency && typeof setUrgency === 'function' && urgencyInput) {
      setUrgency(urgencyInput.value || 'medium');
    }
  } catch (err) {
    console.error('loadOtherSettingsForRepairForm error:', err);
    if (urgencySection) urgencySection.style.display = '';
  }
}


document.addEventListener('DOMContentLoaded', function () {
  loadOtherSettingsForRepairForm(); 
});

  function renderLocationIcons(locationRaw){
	  const loc = String(locationRaw || "").trim();
	  if(!loc) return `<span class="text-slate-400">-</span>`;
	
	  // รองรับรูปแบบ: "สาขาจักร - ชั้น 1 - ห้อง B" หรือ "สาขาจักร • ชั้น 1 • ห้อง B"
	  const parts = loc.split(/[-•]/).map(s => s.trim()).filter(Boolean);
	
	  const building = parts[0] || "";
	  const floor = parts.find(p => p.includes("ชั้น")) || "";
	  const room  = parts.find(p => p.includes("ห้อง")) || "";
	
	  const pill = (icon, text) => `
		<span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-extrabold bg-white/70 border-slate-200 text-slate-700">
		  <i data-lucide="${icon}" class="w-3.5 h-3.5 text-slate-400"></i>
		  ${escapeHtml(text)}
		</span>
	  `;
	
	  let html = "";
	  if(building) html += pill("map-pin", building);
	  if(floor)    html += pill("layers", floor);
	  if(room)     html += pill("door-open", room);
	
	  return html || `<span class="text-slate-400">-</span>`;
	}

  
  function getStatusToneThai(status){
 	 const s = String(status ?? "").trim(); // ✅ เก็บคำตรงๆ เช่น "ปกติ"

	  if (s === "ปกติ") { return { cls: "bg-emerald-50 text-emerald-700 border-emerald-200", dot: "bg-emerald-500" };  }
	  if (s === "แจ้งซ่อม") { return { cls: "bg-amber-50 text-amber-700 border-amber-200", dot: "bg-amber-500" }; }
	  if (s === "ชำรุด") { return { cls: "bg-rose-50 text-rose-700 border-rose-200", dot: "bg-rose-500" };  }
	  if (s === "สำรอง") { return { cls: "bg-sky-50 text-sky-700 border-sky-200", dot: "bg-sky-500" };   }
	  return { cls: "bg-slate-50 text-slate-600 border-slate-200", dot: "bg-slate-400" }; // ไม่ทราบสถานะ
	}

  function safeIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
      window.lucide.createIcons();
    }
  }
  
  function getStatusMeta(statusRaw){
  const s = String(statusRaw || "").trim();

  // ✅ รองรับทั้งไทย/อังกฤษ เผื่ออนาคต
  if (s === "ปกติ" || s.toLowerCase() === "active") {
    return {
      label: "ปกติ",
      wrap: "bg-emerald-50 text-emerald-700 border-emerald-200",
      dot:  "bg-emerald-500"
    };
  }
  if (s === "แจ้งซ่อม" || s.toLowerCase().includes("maint")) {
    return {
      label: "แจ้งซ่อม",
      wrap: "bg-amber-50 text-amber-800 border-amber-200",
      dot:  "bg-amber-400"
    };
  }
  if (s === "ชำรุด" || s.toLowerCase().includes("broken")) {
    return {
      label: "ชำรุด",
      wrap: "bg-rose-50 text-rose-700 border-rose-200",
      dot:  "bg-rose-500"
    };
  }
  if (s === "สำรอง" || s.toLowerCase().includes("spare")) {
    return {
      label: "สำรอง",
      wrap: "bg-sky-50 text-sky-700 border-sky-200",
      dot:  "bg-sky-500"
    };
  }
  // unknown
  return {
    label: (s || "ไม่ทราบสถานะ"),
    wrap: "bg-slate-50 text-slate-600 border-slate-200",
    dot:  "bg-slate-400"
  };
}

function renderStatusBadge(statusRaw){
  const m = getStatusMeta(statusRaw);
  return `
    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-extrabold ${m.wrap}">
      <span class="w-2 h-2 rounded-full ${m.dot}"></span>
      ${escapeHtml(m.label)}
    </span>
  `;
}

  // =========================
  // Date/Time init
  // =========================
  function pad(n){ return String(n).padStart(2,'0'); }
  function initDateTimeInput(){
    const now = new Date();
    const dateInput = document.getElementById('report_date');
    const timeInput = document.getElementById('report_time');

    if (dateInput && !dateInput.value) {
      dateInput.value = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    }
    if (timeInput && !timeInput.value) {
      timeInput.value = `${pad(now.getHours())}:${pad(now.getMinutes())}`;
    }
  }

  // =========================
  // CLOCK
  // =========================
  function updateClockOnly() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('th-TH', { hour12: false });
    const clockEl = document.getElementById('realtime_clock');
    if (clockEl) clockEl.innerText = timeString;
  }

  // =========================
  // PHONE
  // =========================
  function initPhoneToggle() {
	  const noPhoneCheck = document.getElementById('no_phone');
	  const phoneInput  = document.getElementById('phone_input');
	  const noPhoneFlag = document.getElementById('no_phone_flag');
	  const phoneHint   = document.getElementById('phone_hint');
	
	  if (!noPhoneCheck || !phoneInput || !noPhoneFlag) return;
	
	  noPhoneCheck.addEventListener('change', function () {
		if (this.checked) {
		  noPhoneFlag.value = "1";
	
		  phoneInput.value = "";
		  phoneInput.disabled = true;
		  phoneInput.required = false;
	
		  // ✅ เคลียร์ error ทุกชนิด
		  phoneInput.classList.remove(
			'req-border',
			'req-flash',
			'req-shake',
			'input-error',
			'field-invalid'
		  );
	
		  phoneInput.blur();
		  phoneHint?.classList.remove('hidden');
	
		} else {
		  noPhoneFlag.value = "0";
	
		  phoneInput.disabled = false;
		  phoneInput.required = true;
	
		  phoneHint?.classList.add('hidden');
		}
	  });
	}


  // =========================
  // LOCATION DB
  // =========================
  
async function loadAssetTypeFilters(){
  const building = document.getElementById('in_building')?.value?.trim();
  if(!building) return;

  const params = new URLSearchParams({
    action: "types",
    building
  });

  try{
    const res = await fetch("get_asset_search.php?" + params, { cache:"no-store" });
    const json = await res.json();

    const types = (json.ok && Array.isArray(json.data)) ? json.data : [];
    renderTypeFilters(types);

    // ✅ reset เป็น "ทั้งหมด" ทุกครั้งที่เปลี่ยนอาคาร
    window.assetCurrentTypeId = 0;

    console.log("TYPE API:", json);
  }catch(err){
    console.error("loadAssetTypeFilters error:", err);
  }
}

  
function showSoftAlert(message, type = 'info') {
  // ลบตัวเดิมถ้ามี (กันซ้อน)
  const old = document.getElementById('soft_alert_top');
  if (old) old.remove();

  const toast = document.createElement('div');
  toast.id = 'soft_alert_top';
  toast.className = `
    fixed top-6 left-1/2 -translate-x-1/2 z-[9999]
    px-4 py-3 rounded-2xl shadow-lg
    text-sm font-bold flex items-center gap-2
    border
    ${type === 'info'
      ? 'bg-amber-50 text-amber-800 border-amber-200'
      : 'bg-amber-50 text-amber-800 border-amber-200'}
  `;

  toast.innerHTML = `
    <span class="w-8 h-8 rounded-xl bg-white border border-amber-200
                 flex items-center justify-center text-amber-700 shrink-0">
      <i data-lucide="alert-triangle" class="w-4 h-4"></i>
    </span>
    <span class="leading-snug">${message}</span>
  `;

  // initial state (ซ่อนอยู่ด้านบน)
  toast.style.opacity = '0';
  toast.style.transform = 'translate(-50%, -16px)';

  document.body.appendChild(toast);
  safeIcons();

  // slide down
  requestAnimationFrame(() => {
    toast.style.transition = 'all 0.3s cubic-bezier(.2,.9,.2,1)';
    toast.style.opacity = '1';
    toast.style.transform = 'translate(-50%, 0)';
  });

  // hide
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translate(-50%, -12px)';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}

	
	let focusAssetAfterLocation = false;
  let spatialDB = [];

  let currentStep = 1;
  let selection = { bld: null, flr: null, rm: null };

  
  async function loadSpatialDB(){
  try{
    const res = await fetch('get_building_all.php', { cache: 'no-store' });
    const json = await res.json();

    // ✅ รองรับทั้ง 2 แบบ: {ok,data} หรือ {building}
    if (json.ok && Array.isArray(json.data)) {
      spatialDB = json.data;
    } else if (Array.isArray(json.building)) {
      // ✅ map ให้เป็นรูปแบบ spatialDB ที่หน้าใช้
      spatialDB = json.building.map(b => ({
        id: b.area_id,
        name: b.area_name,
        type: "building",
        icon: "building-2",
        floors: (b.floor || []).map(f => ({
          id: f.ac_id,
          name: f.ac_name,
          rooms: (f.room || []).map(r => ({
            id: r.ar_id,
            name: r.ar_name
          }))
        }))
      }));
    } else {
      throw new Error('รูปแบบ JSON ไม่ถูกต้อง');
    }

    console.log('Loaded spatialDB:', spatialDB);
  }catch(err){
    console.error('loadSpatialDB error:', err);
    spatialDB = [];
  }
}
	
	async function openSpatialModal() {
	  const m = document.getElementById('spatial_modal');
	  m.classList.remove('hidden');
	  requestAnimationFrame(() => m.classList.remove('opacity-0'));
	
	  const container = document.getElementById('view_building');
	  container.innerHTML = `<div class="col-span-full text-center text-slate-400 font-bold py-10">กำลังโหลดข้อมูล...</div>`;
	
	  document.getElementById('spatial_search').value = '';
	
	  // ✅ โหลดทุกครั้งกัน cache + กันว่าง
	  await loadSpatialDB();
	
	  if (!spatialDB.length) {
		container.innerHTML = `<div class="col-span-full text-center text-rose-500 font-bold py-10">ไม่พบข้อมูลอาคาร</div>`;
		return;
	  }
	
	  renderBuildings();
	}
 
  function closeSpatialModal() {
    const m = document.getElementById('spatial_modal');
    m.classList.add('opacity-0');
    setTimeout(() => m.classList.add('hidden'), 300);
  }

  function updateModalUI(title, progressPercent, showBack) {
    document.getElementById('modal_title').innerText = title;
    document.getElementById('step_label').innerText = `Step ${currentStep}`;
    document.getElementById('step_bar').style.width = `${progressPercent}%`;

    const btnBack = document.getElementById('btn_back');
    if (showBack) btnBack.classList.remove('hidden');
    else btnBack.classList.add('hidden');

    if (currentStep < 3) document.getElementById('btn_confirm').disabled = true;
  }

  function switchView(viewId) {
    ['view_building', 'view_floor', 'view_room'].forEach(id => {
      const el = document.getElementById(id);
      if (id === viewId) {
        el.classList.remove('hidden');
        el.classList.add('animate-fade-up');
        setTimeout(() => el.classList.remove('animate-fade-up'), 450);
      } else {
        el.classList.add('hidden');
        el.classList.remove('animate-fade-up');
      }
    });
  }

  function renderBuildings() {
  currentStep = 1;
  updateModalUI('เลือกสาขา / อาคาร', 33, false);

  const container = document.getElementById('view_building');

  container.innerHTML = (spatialDB || []).map(b => {
    const floorsCount = (b.floors || []).length;

    return `
      <div onclick="selectBuilding('${b.id}', this)" class="building-card group">
        <div class="flex items-start justify-between">
          <div class="bld-icon">
            <i data-lucide="building-2" class="w-5 h-5"></i>
          </div>

          <span class="bld-badge">
            <i data-lucide="layers" class="w-4 h-4"></i>
            ${floorsCount} ชั้น
          </span>
        </div>

        <div class="mt-4">
          <div class="bld-title">${escapeHtml(b.name)}</div>
          <div class="mt-1 bld-sub">แตะเพื่อเลือกสาขา/อาคาร</div>
        </div>

        
      </div>
    `;
  }).join('');

  switchView('view_building');
  safeIcons();
  filterSpatialList();
}

function setConfirmEnabled(enabled, label) {
  const btn = document.getElementById('btn_confirm');
  if (!btn) return;
  btn.disabled = !enabled;
  if (label) btn.textContent = label;
}

function getConfirmLabel() {
  if (selection.bld && !selection.flr) return "Confirm Building";
  if (selection.bld && selection.flr && !selection.rm) return "Confirm Floor";
  if (selection.bld && selection.flr && selection.rm) return "Confirm Selection";
  return "Confirm Selection";
}


function selectBuilding(id, el) {
  document.querySelectorAll('#view_building .building-card').forEach(x => x.classList.remove('active'));
  if (el) el.classList.add('active');

  selection.bld = spatialDB.find(b => String(b.id) === String(id));
  selection.flr = null;
  selection.rm  = null;

  updateStatusText();

  const floors = (selection.bld?.floors || []);
  if (floors.length === 0) {
    // ✅ ไม่มีชั้น -> confirm ได้เลย และ "อยู่หน้าอาคาร"
    currentStep = 1;
    updateModalUI(`เลือกสาขา / อาคาร`, 33, false);
    switchView('view_building');

    setConfirmEnabled(true, "Confirm Building");
	showSoftAlert("อาคารนี้ไม่มีข้อมูลชั้น สามารถยืนยันได้ทันที");
    return;
  }

  // ✅ มีชั้น -> ไปต่อ step 2 ปกติ
  setConfirmEnabled(false, "Confirm Selection");
  setTimeout(renderFloors, 120);
}


  function renderFloors() {
      currentStep = 2;
	  updateModalUI(`เลือกชั้น (${selection.bld.name})`, 66, true);
	
	  const container = document.getElementById('view_floor');
	
	  container.innerHTML = (selection.bld.floors || []).map(f => {
		const roomCount = (f.rooms || []).length;
	
		return `
		  <div onclick="selectFloor('${f.id}', this)" class="floor-card group">
			<div class="flex items-start justify-between">
			  <div class="floor-icon">
				<i data-lucide="layers" class="w-5 h-5"></i>
			  </div>
	
			  <span class="room-badge">
				<i data-lucide="door-open" class="w-4 h-4"></i>
				${roomCount} ห้อง
			  </span>
			</div>
	
			<div class="mt-4">
			  <div class="floor-no">${escapeHtml(f.name)}</div> 
			</div>
	
			<div class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
			  แตะเพื่อเลือกชั้น
			</div>
		  </div>
		`;
	  }).join('');
	
	  switchView('view_floor');
	  safeIcons();
	  filterSpatialList();
  }

function selectFloor(id, el) {
  document.querySelectorAll('#view_floor .floor-card').forEach(x => x.classList.remove('active'));
  if (el) el.classList.add('active');

  selection.flr = (selection.bld?.floors || []).find(f => String(f.id) === String(id));
  selection.rm  = null;

  updateStatusText();

  const rooms = (selection.flr?.rooms || []);
  if (rooms.length === 0) {
    // ✅ ไม่มีห้อง -> confirm ได้เลย และ "อยู่หน้าชั้น"
    currentStep = 2;
    updateModalUI(`เลือกชั้น (${selection.bld.name})`, 66, true);
    switchView('view_floor');

    setConfirmEnabled(true, "Confirm Floor");
    showSoftAlert("ชั้นนี้ไม่มีข้อมูลห้อง สามารถยืนยันได้ทันที");
    return;
  }

  // ✅ มีห้อง -> ไปต่อ step 3 ปกติ
  setConfirmEnabled(false, "Confirm Selection");
  setTimeout(renderRooms, 120);
}


  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function renderRooms() {
	  currentStep = 3;
	  updateModalUI(`เลือกห้อง (${selection.flr.name})`, 100, true);
	
	  const container = document.getElementById('view_room');
	  container.innerHTML = (selection.flr.rooms || []).map(r => `
		<div class="room-card" onclick="selectRoom(${r.id}, '${escapeHtml(r.name)}', this)">
		  <div>
			<div class="rm-icon"> 
			  <i data-lucide="door-open" class="w-5 h-5"></i>
			</div>
			<div class="rm-title">${escapeHtml(r.name)}</div>
			<div class="rm-sub">แตะเพื่อเลือกห้อง</div>
		  </div>
		</div>
	  `).join('');
	
	  switchView('view_room');
	  safeIcons();
	  filterSpatialList();
	}

	function selectRoom(id, name, el) {
	  document.querySelectorAll('#view_room .room-card').forEach(x => x.classList.remove('active'));
	  if (el) el.classList.add('active');
	
	  selection.rm = { id, name };
	  updateStatusText();
	
	  document.getElementById('btn_confirm').disabled = false;
	}

 function confirmSelection() {
	  if (!selection.bld) return;
	
	  // ✅ fallback ค่า เมื่อไม่มี floor/room
	  const floorName = selection.flr ? selection.flr.name : "-";
	  const roomName  = selection.rm  ? selection.rm.name  : "-";
	
	  document.getElementById('loc_placeholder').classList.add('hidden');
	  document.getElementById('loc_selected').classList.remove('hidden');
	  document.getElementById('loc_status').classList.remove('hidden');
	
	  const bEl = document.getElementById('disp_building');
		if (bEl) {
		  const nameHolder = bEl.querySelector('span');
		  if (nameHolder) nameHolder.innerText = selection.bld.name;
		}
	  document.getElementById('disp_floor').innerText    = floorName;
	  const roomText = document.getElementById('disp_room_text');
		if (roomText) roomText.innerText = roomName;
	
	  // ✅ บันทึก ID (ถ้าไม่มี ให้ส่งค่าว่าง)
	  document.getElementById('in_building').value = selection.bld.id;
	  document.getElementById('in_floor').value    = selection.flr ? selection.flr.id : "";
	  document.getElementById('in_room').value     = selection.rm  ? selection.rm.id  : "";
	
	  hideLocationError();
	  closeSpatialModal();
	  loadAssetTypeFilters();
	  
	  if (focusAssetAfterLocation) {
		focusAssetAfterLocation = false;
	
		setTimeout(() => {
		  const assetSearch = document.getElementById('asset_search');
		  if (assetSearch) {
			assetSearch.focus();
			assetSearch.select(); // optional: ไฮไลท์ข้อความเดิม
		  }
		}, 350); // รอ modal fade-out
	}
	
	}
	

  function goBackStep() {
    if (currentStep === 3) renderFloors();
    else if (currentStep === 2) renderBuildings();
  }

 function updateStatusText() {
  const b = document.getElementById('stat_bld');
  const f = document.getElementById('stat_flr');
  const r = document.getElementById('stat_rm');

  b.innerText = selection.bld ? selection.bld.name : '...';
  b.className = selection.bld ? 'text-primary' : 'opacity-50';

  f.innerText = selection.flr ? selection.flr.name : '...';
  f.className = selection.flr ? 'text-primary' : 'opacity-50';

  r.innerText = selection.rm ? selection.rm.name : '...';
  r.className = selection.rm ? 'text-primary' : 'opacity-50';
}

  function filterSpatialList() {
    const q = document.getElementById('spatial_search').value.toLowerCase().trim();
    const currentView = currentStep === 1 ? 'view_building' : (currentStep === 2 ? 'view_floor' : 'view_room');
    const items = document.getElementById(currentView).children;

    for (let item of items) {
      const text = item.innerText.toLowerCase();
      const match = text.includes(q);
      item.classList.toggle('hidden', !match);
    }
  }

  // =========================
  // URGENCY
  // =========================
  const urgencyConfig = {
    low:      { activeClass: 'bg-emerald-500 border-emerald-500 text-white shadow-lg shadow-emerald-500/30', dotClass: 'bg-white', textClass: 'text-white' },
    medium:   { activeClass: 'bg-blue-500 border-blue-500 text-white shadow-lg shadow-blue-500/30',       dotClass: 'bg-white', textClass: 'text-white' },
    high:     { activeClass: 'bg-amber-500 border-amber-500 text-white shadow-lg shadow-amber-500/30',    dotClass: 'bg-white', textClass: 'text-white' },
    critical: { activeClass: 'bg-rose-500 border-rose-500 text-white shadow-lg shadow-rose-500/30',       dotClass: 'bg-white', textClass: 'text-white' }
  };
  const defaultDotBase = "w-2 h-2 rounded-full shadow-sm transition-transform group-hover:scale-125 duration-300";

  function setUrgency(level) {
    const input = document.getElementById('urgency_input');
    if (input) input.value = level;

    ['low', 'medium', 'high', 'critical'].forEach(key => {
      const btn = document.getElementById(`btn_urg_${key}`);
      if (!btn) return;

      const dot = btn.querySelector('span:first-child');
      const text = btn.querySelector('span:last-child');

      let hoverClass = '';
      if (key === 'low') hoverClass = 'hover:bg-emerald-50 hover:border-emerald-200';
      if (key === 'medium') hoverClass = 'hover:bg-blue-50 hover:border-blue-200';
      if (key === 'high') hoverClass = 'hover:bg-amber-50 hover:border-amber-200';
      if (key === 'critical') hoverClass = 'hover:bg-rose-50 hover:border-rose-200';

      btn.className = `urgency-btn relative group flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border transition-all duration-300 bg-slate-50 border-slate-200 hover:scale-[1.02] active:scale-95 ${hoverClass}`;

      let dotColor = '';
      if (key === 'low') dotColor = 'bg-emerald-500';
      if (key === 'medium') dotColor = 'bg-blue-500';
      if (key === 'high') dotColor = 'bg-amber-500';
      if (key === 'critical') dotColor = 'bg-rose-500';

      if (dot) dot.className = `${defaultDotBase} ${dotColor}`;

      if (text) {
        text.className = "text-xs font-bold text-slate-600 group-hover:text-slate-800";
        if (key === 'low') text.classList.add('group-hover:text-emerald-700');
        if (key === 'medium') text.classList.add('group-hover:text-blue-700');
        if (key === 'high') text.classList.add('group-hover:text-amber-700');
        if (key === 'critical') text.classList.add('group-hover:text-rose-700');
      }
    });

    const activeBtn = document.getElementById(`btn_urg_${level}`);
    if (activeBtn) {
      const activeDot = activeBtn.querySelector('span:first-child');
      const activeText = activeBtn.querySelector('span:last-child');
      const config = urgencyConfig[level];

      activeBtn.className = `urgency-btn relative group flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border transition-all duration-300 ${config.activeClass} scale-[1.02]`;
      if (activeDot) activeDot.className = `${defaultDotBase} ${config.dotClass}`;
      if (activeText) activeText.className = `text-xs font-bold ${config.textClass}`;
    }
  }

  // =========================
  // IMAGES
  // =========================
  function initImages() {
    const MAX_FILES = 5;

    const dropzone = document.getElementById('dropzone');
    const overlay = document.getElementById('drop_overlay');
    const fileInput = document.getElementById('file_input');
    const btnAdd = document.getElementById('btn_add');
    const imgError = document.getElementById('img_error');
    const imgCount = document.getElementById('img_count');
    const preview = document.getElementById('preview_images');
    const emptyState = document.getElementById('empty_state');

    const lb = document.getElementById('img_lightbox');
    const lbBackdrop = document.getElementById('img_lightbox_backdrop');
    const lbImg = document.getElementById('lb_img');
    const lbClose = document.getElementById('lb_close');
    const lbPrev = document.getElementById('lb_prev');
    const lbNext = document.getElementById('lb_next');
    const lbIndex = document.getElementById('lb_index');
    const lbTotal = document.getElementById('lb_total');

    if (!dropzone || !overlay || !fileInput || !preview || !emptyState) return;

    let selectedFiles = [];
    let urls = [];
    let activeIndex = 0;

    const fileKey = (f) => `${f.name}-${f.size}-${f.lastModified}`;

    function setError(show) {
      if (!imgError) return;
      imgError.classList.toggle('hidden', !show);
    }

    function syncFileInput() {
      const dt = new DataTransfer();
      selectedFiles.forEach(f => dt.items.add(f));
      fileInput.files = dt.files;
    }

    function revokeUrls() {
      urls.forEach(u => URL.revokeObjectURL(u));
      urls = [];
    }

    function renderCount() {
      if (imgCount) imgCount.textContent = `${selectedFiles.length}/${MAX_FILES}`;
    }

    function openLightbox(i) {
      if (!lb || selectedFiles.length === 0) return;
      activeIndex = Math.max(0, Math.min(i, selectedFiles.length - 1));
      lbTotal.textContent = String(selectedFiles.length);
      lbIndex.textContent = String(activeIndex + 1);
      lbImg.src = urls[activeIndex];

      lb.classList.remove('hidden');
      document.documentElement.style.overflow = 'hidden';
    }

    function closeLightbox() {
      if (!lb) return;
      lb.classList.add('hidden');
      document.documentElement.style.overflow = '';
    }

    function stepLightbox(dir) {
      if (selectedFiles.length === 0) return;
      activeIndex = (activeIndex + dir + selectedFiles.length) % selectedFiles.length;
      lbIndex.textContent = String(activeIndex + 1);
      lbImg.src = urls[activeIndex];
    }

    lbClose?.addEventListener('click', closeLightbox);
    lbBackdrop?.addEventListener('click', closeLightbox);
    lbPrev?.addEventListener('click', (e) => { e.preventDefault(); stepLightbox(-1); });
    lbNext?.addEventListener('click', (e) => { e.preventDefault(); stepLightbox(1); });

    document.addEventListener('keydown', (e) => {
      if (!lb || lb.classList.contains('hidden')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') stepLightbox(-1);
      if (e.key === 'ArrowRight') stepLightbox(1);
    });

    function renderPreview() {
      renderCount();
      preview.innerHTML = '';

      if (selectedFiles.length === 0) {
        preview.appendChild(emptyState);
        emptyState.style.display = 'block';
        return;
      }

      emptyState.style.display = 'none';

      revokeUrls();
      urls = selectedFiles.map(f => URL.createObjectURL(f));

      selectedFiles.forEach((file, idx) => {
        const url = urls[idx];

        const thumb = document.createElement('button');
        thumb.type = 'button';
        thumb.className =
          "relative w-[86px] h-[86px] rounded-2xl overflow-hidden border border-slate-200 bg-white shrink-0 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary/30";
        thumb.title = "กดเพื่อดูรูปใหญ่";

        thumb.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          openLightbox(idx);
        });

        thumb.innerHTML = `
          <img src="${url}" class="object-cover w-full h-full" alt="preview-${idx}">
          <button type="button"
            class="absolute top-1 right-1 w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center
                   shadow-md ring-2 ring-white hover:bg-rose-600 active:scale-95 transition text-[12px] leading-none"
            aria-label="remove"
            data-remove="${idx}"
            title="ลบรูป">×</button>
        `;

        thumb.querySelector('[data-remove]').addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const i = Number(e.currentTarget.getAttribute('data-remove'));
          selectedFiles.splice(i, 1);
          syncFileInput();
          renderPreview();
        });

        preview.appendChild(thumb);
      });
    }

    function addFiles(incoming) {
      const files = Array.from(incoming || []).filter(f => f.type && f.type.startsWith('image/'));
      if (files.length === 0) return;

      const existing = new Set(selectedFiles.map(fileKey));
      let merged = [...selectedFiles];

      for (const f of files) {
        if (!existing.has(fileKey(f))) merged.push(f);
      }

      const over = merged.length > MAX_FILES;
      if (over) merged = merged.slice(0, MAX_FILES);

      selectedFiles = merged;
      setError(over);
      syncFileInput();
      renderPreview();
    }

  // ✅ ปุ่ม ADD PHOTO
    btnAdd?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      fileInput.value = '';
      fileInput.click();
    });

    // ✅ คลิกในกรอบเพื่อเลือกไฟล์ (ยกเว้นปุ่มลบ)
    dropzone.addEventListener('click', (e) => {
      if (e.target.closest('[data-remove]')) return;
      fileInput.value = '';
      fileInput.click();
    });

dropzone.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        fileInput.click();
      }
    });

    fileInput.addEventListener('change', function () {
      addFiles(this.files); 
    });

    function showOverlay() {
      overlay.classList.remove('hidden');
      overlay.classList.add('flex');
      dropzone.classList.add('border-primary/40', 'bg-primary/5');
    }
    function hideOverlay() {
      overlay.classList.add('hidden');
      overlay.classList.remove('flex');
      dropzone.classList.remove('border-primary/40', 'bg-primary/5');
    }

    ['dragenter', 'dragover', 'drop'].forEach(evt => {
      document.body.addEventListener(evt, (e) => {
        if (!e.target.closest('#dropzone')) e.preventDefault();
      }, { passive: false });
    });

    dropzone.addEventListener('dragenter', (e) => { e.preventDefault(); showOverlay(); });
    dropzone.addEventListener('dragover',  (e) => { e.preventDefault(); showOverlay(); });
    dropzone.addEventListener('dragleave', (e) => {
      e.preventDefault();
      const rect = dropzone.getBoundingClientRect();
      const x = e.clientX, y = e.clientY;
      const inside = x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
      if (!inside) hideOverlay();
    });
    dropzone.addEventListener('drop', (e) => {
      e.preventDefault();
      hideOverlay();
      const dt = e.dataTransfer;
      if (!dt) return;
      addFiles(dt.files);
    });
	
	window.resetRepairImages = function () {
	  selectedFiles = [];
	  activeIndex = 0;
	
	  revokeUrls();
	
	  if (fileInput) {
		fileInput.value = '';
	
		// ✅ ล้าง FileList จริง
		try {
		  const dt = new DataTransfer();
		  fileInput.files = dt.files;
		} catch (e) {
		  console.warn('Cannot clear fileInput.files:', e);
		}
	  }
	
	  setError(false);
	  closeLightbox();
	  renderPreview();
	};

    renderPreview();
  }

  // =========================
  // LOCATION REQUIRED UX
  // =========================
  function initLocationRequired() {
    const form = document.getElementById('repair_form');
    const locError = document.getElementById('loc_error');
    const locTrigger = document.getElementById('loc_trigger');
    if (!form || !locError || !locTrigger) return;

    window.showLocationError = function () {
      locError.classList.remove('hidden');
      locTrigger.classList.add('shake');
      setTimeout(() => locTrigger.classList.remove('shake'), 400);
    }
    window.hideLocationError = function () {
      locError.classList.add('hidden');
    }

    form.addEventListener('submit', function (e) {
      const b = document.getElementById('in_building').value.trim();
      const f = document.getElementById('in_floor').value.trim();
      const r = document.getElementById('in_room').value.trim();

    const bObj = spatialDB.find(x => String(x.id) === String(b));
	const bHasFloors = (bObj?.floors || []).length > 0;
	
	let needFloor = bHasFloors;
	let needRoom  = false;
	
	if (needFloor) {
	  const fObj = (bObj?.floors || []).find(x => String(x.id) === String(f));
	  needRoom = ((fObj?.rooms || []).length > 0);
	} 
    });

    document.addEventListener('keydown', function (e) {
      const m = document.getElementById('spatial_modal');
      const isOpen = m && !m.classList.contains('hidden');
      if (isOpen && e.key === 'Escape') closeSpatialModal();
    });

    document.getElementById('modal_backdrop')?.addEventListener('click', function () {
      closeSpatialModal();
    });
  }

  // =========================
  // ✅ ASSET SELECTOR (Mock -> API ได้)
  // =========================
  const MOCK_ASSETS = [];
  
  // ✅ ให้ currentType ใช้งานร่วมกันทั้งระบบ
window.assetCurrentType = "all";

// ✅ สร้างปุ่ม filter จาก API
function renderTypeFilters(types){
  const elFilters = document.getElementById('asset_filters');
  if(!elFilters) return;

  // types = [{id:0,label:"ทั้งหมด"},{id:-1,label:"Asset"},{id:3,label:"ปั๊มน้ำ"}...]
  elFilters.innerHTML = (types || []).map(t => `
    <button type="button" data-type-id="${Number(t.id)}"
      class="asset-chip rounded-full px-3 py-1.5 text-[11px] font-extrabold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition">
      ${escapeHtml(t.label)}
    </button>
  `).join('');

  // ✅ active เริ่มต้น = id 0
  const first = elFilters.querySelector('.asset-chip[data-type-id="0"]') || elFilters.querySelector('.asset-chip');
  if(first) setChipActive(first);

  // ✅ bind click
  elFilters.querySelectorAll('.asset-chip').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      window.assetCurrentTypeId = Number(btn.dataset.typeId || 0);
      setChipActive(btn);

      // ค้นหาใหม่ทันที (ถ้ากำลังเปิด dropdown)
      document.getElementById('asset_search')?.dispatchEvent(new Event('input'));
    });
  });

  safeIcons();
}

function setChipActive(btn){
  document.querySelectorAll('#asset_filters .asset-chip').forEach(b=>{
    b.classList.remove('active','border-primary/30','bg-primary/5','text-primary');
    b.classList.add('border-slate-200','bg-white','text-slate-700');
  });
  btn.classList.add('active','border-primary/30','bg-primary/5','text-primary');
  btn.classList.remove('border-slate-200','bg-white','text-slate-700');
}


// ใช้ escapeHtml ตัวเดิมของคุณ หรือถ้ายังไม่มีใน scope นี้ให้ใช้ตัวนี้
function escapeHtml(str){
  return String(str)
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}


  function initAssetSelector(){
    const elSearch   = document.getElementById('asset_search');
    const elDrop     = document.getElementById('asset_dropdown');
    const elList     = document.getElementById('asset_list');
    const elEmpty    = document.getElementById('asset_empty');
    const elCount    = document.getElementById('asset_count');
    const elFilterBtn= document.getElementById('asset_filter_btn');
    const elFilters  = document.getElementById('asset_filters');
    const elOpenBtn  = document.getElementById('asset_open_btn');

    const elSelCard  = document.getElementById('asset_selected_card');
    const elClearBtn = document.getElementById('asset_clear_btn');
    const elSelName  = document.getElementById('asset_sel_name');
    const elSelCode  = document.getElementById('asset_sel_code');
    const elSelLoc   = document.getElementById('asset_sel_loc');
    const elSelStatus= document.getElementById('asset_sel_status');
    const elSelOwner = document.getElementById('asset_sel_owner');
    const elBadgeType= document.getElementById('asset_badge_type');

    const inAssetId  = document.getElementById('asset_id');
    const inAssetCode= document.getElementById('asset_code');

    if(!elSearch || !elDrop || !elList || !elEmpty || !elCount) return;

    let activeIndex = -1; 
    let results = [];

    function esc(s){
      return String(s).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;");
    }

    function openDropdown(){
      elDrop.classList.remove('hidden');
      activeIndex = -1;
    }
    function closeDropdown(){
      elDrop.classList.add('hidden');
      activeIndex = -1;
    }

    function chipActive(btn){
      document.querySelectorAll('.asset-chip').forEach(b=>{
        b.classList.remove('active','border-primary/30','bg-primary/5','text-primary');
        b.classList.add('border-slate-200','bg-white','text-slate-700');
      });
      btn.classList.add('active','border-primary/30','bg-primary/5','text-primary');
      btn.classList.remove('border-slate-200','bg-white','text-slate-700');
    }

  async function filterData(q){
	  const inBuilding = document.getElementById('in_building');
	  const inFloor    = document.getElementById('in_floor');
	  const inRoom     = document.getElementById('in_room');
	
	  const building = inBuilding?.value?.trim();
	
	  // ✅ ยังไม่เลือกอาคาร
	  if(!building){
		focusAssetAfterLocation = true;
		showSoftAlert("กรุณาเลือกสถานที่ (อาคาร/ชั้น/ห้อง) ก่อนค้นหาเครื่อง/ทรัพย์สิน");
		await openSpatialModal();
		setTimeout(() => document.getElementById('spatial_search')?.focus(), 50);
		return [];
	  }
	
	  const params = new URLSearchParams({
		q: q || "",
		building: building,
		type: String(Number(window.assetCurrentTypeId || 0)), // ✅ ส่งเป็น ID
		floor: inFloor?.value || "",
		room: inRoom?.value || "",
		limit: 10
	  });
	
	  try{
		const res  = await fetch("get_asset_search.php?" + params, { cache:"no-store" });
		const json = await res.json();
		return Array.isArray(json.data) ? json.data : [];
	  }catch(err){
		console.error("asset_search error:", err);
		showSoftAlert("ดึงข้อมูลทรัพย์สินไม่สำเร็จ (เช็ค Console/Network)");
		return [];
	  }
	}




    function highlight(idx){
      activeIndex = idx;
      [...elList.children].forEach((row,i)=>{
        row.classList.toggle('bg-primary/5', i===idx);
      });
    }

    function selectItem(idx){
      const x = results[idx];
      if(!x) return;

      elSelName.textContent   = x.name;
      elSelCode.textContent   = x.code;
	  const elSelLocHtml = document.getElementById('asset_sel_loc_html');
		if (elSelLocHtml) elSelLocHtml.innerHTML = renderLocationIcons(x.location);
		safeIcons();
      const elSelStatusWrap = document.getElementById('asset_sel_status_wrap');
		if (elSelStatusWrap) {
		  elSelStatusWrap.innerHTML = renderStatusBadge(x.status); // ✅ ใช้ฟังก์ชันเดียวกับ list
		}
		safeIcons();
      elSelOwner.textContent  = x.owner;
      elBadgeType.textContent = (x.type_name && String(x.type_name).trim() !== "") ? x.type_name : "Asset";


      inAssetId.value   = x.id;
      inAssetCode.value = x.code;

      elSelCard.classList.remove('hidden');
      closeDropdown();
      elSearch.value = `${x.code} — ${x.name}`;
    }

    function clearSelection(){
      inAssetId.value = "";
      inAssetCode.value = "";
      elSelCard.classList.add('hidden');
      elSearch.value = "";
      elSearch.focus();
    }

    function render(){
      elCount.textContent = results.length;
      elList.innerHTML = "";

      if(results.length === 0){
        elEmpty.classList.remove('hidden');
        safeIcons();
        return;
      }
      elEmpty.classList.add('hidden');

      results.forEach((x,idx)=>{
	  const typeLabel = (x.type_name && String(x.type_name).trim() !== "") ? x.type_name : "Asset";
        const statusLabel = String(x.status ?? "").trim() || "ไม่ทราบสถานะ";
		const tone = getStatusToneThai(statusLabel);

        const row = document.createElement('button');
        row.type = "button";
        row.className = "w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-slate-50/70 transition flex items-start gap-3";
        row.setAttribute("data-idx", idx);

        row.innerHTML = `
		  <div class="shrink-0 w-10 h-10 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-400">
			<i data-lucide="cpu" class="w-5 h-5"></i>
		  </div>
		
		  <div class="min-w-0 flex-1">
			<div class="flex items-center gap-2 flex-wrap">
			  <span class="text-[10px] font-extrabold text-slate-700 bg-slate-50 border border-slate-200 rounded-full px-2.5 py-1">
				${esc(typeLabel)}
			  </span>
		
			  <span class="text-[10px] font-extrabold text-slate-700 bg-white border border-slate-200 rounded-full px-2.5 py-1">
				${esc(x.code)}
			  </span>
		
			  ${renderStatusBadge(x.status)}
			</div>
		
			<p class="mt-1 text-sm font-extrabold text-slate-800 break-words">${esc(x.name)}</p>
			<div class="mt-1 flex flex-wrap gap-1.5">
			  ${renderLocationIcons(x.location)}
			</div>
		
			<div class="mt-2 flex items-center gap-2 text-[11px] text-slate-400 font-semibold break-words">
			  <span class="inline-flex items-center gap-1">
				<i data-lucide="users" class="w-4 h-4"></i> ${esc(x.owner)}
			  </span>
			</div>
		  </div>
		
		  <div class="shrink-0 mt-1 text-slate-300">
			<i data-lucide="chevron-right" class="w-5 h-5"></i>
		  </div>
		`;


        row.addEventListener('mouseenter', ()=> highlight(idx));
        row.addEventListener('click', ()=> selectItem(idx));
        elList.appendChild(row);
      });

      safeIcons();
    }

  	let reqNo = 0;
	
	async function doSearch(){
	  openDropdown();
	  const myReq = ++reqNo;
	
	  results = await filterData(elSearch.value);
	
	  if (myReq !== reqNo) return; // ถ้ามี request ใหม่กว่า ให้ทิ้งผลนี้
	  render();
	}
    // events
    elSearch.addEventListener('focus', doSearch);
    elSearch.addEventListener('input', doSearch);

    elSearch.addEventListener('keydown', (e)=>{
      if(elDrop.classList.contains('hidden')) return;

      if(e.key === "ArrowDown"){
        e.preventDefault();
        highlight(Math.min(activeIndex+1, results.length-1));
      }
      if(e.key === "ArrowUp"){
        e.preventDefault();
        highlight(Math.max(activeIndex-1, 0));
      }
      if(e.key === "Enter"){
        e.preventDefault();
        if(activeIndex >= 0) selectItem(activeIndex);
      }
      if(e.key === "Escape"){
        closeDropdown();
      }
    });

    document.addEventListener('click', (e)=>{
      const inside = e.target.closest('#asset_dropdown') || e.target.closest('#asset_search') || e.target.closest('#asset_open_btn') || e.target.closest('#asset_filter_btn');
      if(!inside) closeDropdown();
    });

    elOpenBtn?.addEventListener('click', ()=>{
      elSearch.focus();
      doSearch();
    });

    elFilterBtn?.addEventListener('click', ()=>{
      elFilters.classList.toggle('hidden');
    });
 

    elClearBtn?.addEventListener('click', clearSelection);

    // init chip style
    setTimeout(()=>{
      const first = document.querySelector('.asset-chip[data-type="all"]');
      if(first) chipActive(first);
      safeIcons();
    }, 0);

    // =========================
    // ✅ Auto-select เครื่องจักร/สถานที่ จาก query string
    // ใช้เมื่อมาจากปุ่ม "แจ้งซ่อม" ในหน้าประวัติเครื่องจักร (machine_history_view.php)
    // ซึ่งจะแนบ asset_code, asset_name, area_id (อาคาร), ac_id (ชั้น), ar_id (ห้อง) มาด้วย
    // =========================
    (async function autoFillFromQuery(){
      const qp = new URLSearchParams(window.location.search);
      const qAssetCode = (qp.get('asset_code') || '').trim();
      const qAreaId    = qp.get('area_id');
      const qAcId      = qp.get('ac_id');
      const qArId      = qp.get('ar_id');

      if (!qAssetCode && !qAreaId) return; // ไม่มีค่าที่ต้อง auto-fill ก็จบเลย ไม่ต้องทำอะไร

      // 1) ตั้งค่าอาคาร/ชั้น/ห้อง ก่อน (ต้องเลือกสถานที่ก่อนถึงจะค้นหาเครื่องจักรได้)
      if (qAreaId) {
        try {
          await loadSpatialDB();
          const bld = spatialDB.find(b => String(b.id) === String(qAreaId));
          if (bld) {
            selection.bld = bld;
            selection.flr = qAcId ? ((bld.floors || []).find(f => String(f.id) === String(qAcId)) || null) : null;
            selection.rm  = qArId ? (((selection.flr && selection.flr.rooms) || []).find(r => String(r.id) === String(qArId)) || null) : null;
            if (typeof confirmSelection === 'function') confirmSelection();
          } else {
            console.warn('autoFillFromQuery: ไม่พบอาคารตาม area_id =', qAreaId);
          }
        } catch (err) {
          console.error('autoFillFromQuery: loadSpatialDB error', err);
        }
      }

      // 2) ค้นหาและเลือกเครื่องจักรจาก asset_code (ต้องทำหลังตั้งค่าสถานที่แล้วเท่านั้น)
      if (qAssetCode && document.getElementById('in_building')?.value) {
        elSearch.value = qAssetCode;
        try {
          results = await filterData(qAssetCode);
          render();
          const idx = results.findIndex(x => String(x.code) === qAssetCode);
          if (idx >= 0) {
            selectItem(idx);
          } else if (results.length > 0) {
            // ไม่เจอรหัสตรงเป๊ะ แต่มีผลลัพธ์ใกล้เคียง ให้ผู้ใช้เห็นรายการเพื่อเลือกเอง
            openDropdown();
          } else {
            showSoftAlert('ไม่พบเครื่องจักร/ทรัพย์สินรหัส "' + qAssetCode + '" ในสถานที่ที่เลือก กรุณาค้นหาด้วยตนเอง');
          }
        } catch (err) {
          console.error('autoFillFromQuery: filterData error', err);
        }
      }
    })();
  }

  // =========================
  // ✅ DOM READY
  // =========================
document.addEventListener('DOMContentLoaded', () => {
   initDateTimeInput();
  safeIcons();

  initPhoneToggle();
  initImages();
  initLocationRequired(); // ✅ แต่อย่ามี submit listener ซ่อนอยู่
  initAssetSelector();

  initSwalStepValidationNoAuto(); // ✅ ตัวเดียวพอ

  updateClockOnly();
  setInterval(updateClockOnly, 1000); 
});


  
  // ✅ RESET ALL (location + asset + images) for <button type="reset">
(() => {
  const form = document.getElementById('repair_form');
  if (!form) return;

  // --- helpers ---
  const $ = (id) => document.getElementById(id);
  const hide = (id) => $(id)?.classList.add('hidden');
  const show = (id) => $(id)?.classList.remove('hidden');

  function resetLocation(){
    show('loc_placeholder'); hide('loc_selected'); hide('loc_status');
    hideLocationError?.();

    ['in_building','in_floor','in_room'].forEach(id => { const el=$(id); if(el) el.value=''; });

    const b = $('disp_building')?.querySelector('span'); if (b) b.innerText = '';
    if ($('disp_floor')) $('disp_floor').innerText = '';
    if ($('disp_room_text')) $('disp_room_text').innerText = 'ห้อง -';

    // reset modal state
    selection = { bld:null, flr:null, rm:null };
    currentStep = 1;
    $('spatial_search') && ($('spatial_search').value = '');
    updateStatusText?.();
    renderBuildings?.();
  }

  function resetAsset(){
    ['asset_id','asset_code'].forEach(id => { const el=$(id); if(el) el.value=''; });

    hide('asset_selected_card');
    if ($('asset_sel_name')) $('asset_sel_name').textContent = '-';
    if ($('asset_sel_code')) $('asset_sel_code').textContent = '-';
    if ($('asset_sel_owner')) $('asset_sel_owner').textContent = '-';
    if ($('asset_badge_type')) $('asset_badge_type').textContent = '-';
    if ($('asset_sel_status_wrap')) $('asset_sel_status_wrap').innerHTML = '';
    if ($('asset_sel_loc_html')) $('asset_sel_loc_html').innerHTML = '';

    if ($('asset_search')) $('asset_search').value = '';
    hide('asset_dropdown');
    if ($('asset_list')) $('asset_list').innerHTML = '';
    if ($('asset_count')) $('asset_count').textContent = '0';
    hide('asset_empty');

    window.assetCurrentTypeId = 0;
    hide('asset_filters');
    if ($('asset_filters')) $('asset_filters').innerHTML = '';
  }

  function resetImages(){
    // ล้างไฟล์ input
    const fi = $('file_input');
    if (fi) fi.value = '';
	
	  if (typeof window.resetRepairImages === 'function') {
		window.resetRepairImages();
		return;
	  }

    // ล้าง preview
    const preview = $('preview_images');
    const empty = $('empty_state');
    if (preview) preview.innerHTML = '';
    if (preview && empty) preview.appendChild(empty), (empty.style.display = 'block');

    // reset counter + hide error
    if ($('img_count')) $('img_count').textContent = '0/5';
    hide('img_error');

    // ปิด lightbox ถ้าเปิดอยู่
    hide('img_lightbox');
    document.documentElement.style.overflow = '';
    if ($('lb_img')) $('lb_img').src = '';
    if ($('lb_index')) $('lb_index').textContent = '1';
    if ($('lb_total')) $('lb_total').textContent = '1';
  }

  function resetUrgency(){
    setUrgency?.('medium');
  }

  // ✅ hook reset
  form.addEventListener('reset', () => {
    setTimeout(() => {
      resetLocation();
      resetAsset();
      resetImages();
      resetUrgency();

      // ตั้งวัน/เวลาใหม่เป็นปัจจุบัน
      if ($('report_date')) $('report_date').value = '';
      if ($('report_time')) $('report_time').value = '';
      initDateTimeInput?.();

      safeIcons?.();
    }, 0);
  });
})();function markErrorAuto(el, form) {
  if (!el) return;

  el.classList.add('input-error');

  // focus + scroll
  try { el.focus({ preventScroll: true }); } catch { el.focus(); }
  try { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch {}

  const handler = async () => {
    const val = (el.value || '').trim();

    // ถ้ายังกรอกไม่ครบ → ยังแดงค้าง
    if (!val) return;

    // ✅ กรอกแล้ว → ลบแดงช่องนี้
    el.classList.remove('input-error');
    el.removeEventListener('blur', handler);
    el.removeEventListener('change', handler);

    // 🔁 auto ไปเช็คช่องถัดไป
    const nextBad = firstInvalidField(form);
    if (nextBad) {
      await Swal.fire({
        icon: 'warning',
        title: 'กรุณากรอกข้อมูลให้ครบ',
        text: `กรุณากรอก: ${getFieldLabel(nextBad)}`,
        confirmButtonText: 'ไปกรอก',
        confirmButtonColor: '#006B9F',
        allowOutsideClick: false
      });

      markErrorAuto(nextBad, form);
    }
  };

  // ใช้ blur + change (ไม่ใช้ input เพื่อไม่รัว)
  el.addEventListener('blur', handler);
  el.addEventListener('change', handler);
}

function clearAllErrors(form) {
  form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
}
function firstInvalidField(form) {
  // เช็คเฉพาะ required ที่เป็น input/textarea/select (ไม่เอา hidden)
  const fields = form.querySelectorAll('input[required], textarea[required], select[required]');

  for (const el of fields) {
    if (el.disabled) continue;
    if (el.type === 'hidden') continue;

    // กรณี checkbox required: ต้อง checked
    if (el.type === 'checkbox' || el.type === 'radio') {
      if (!el.checked) return el;
      continue;
    }

    const val = (el.value || '').trim();
    if (!val) return el;
  }
  return null;
}

function getFieldLabel(el) {
  // พยายามหา label ใกล้ ๆ (แบบง่ายและไม่พัง)
  const wrap = el.closest('div');
  const lbl = wrap?.querySelector('label');
  if (lbl?.innerText) return lbl.innerText.replace('*', '').trim();

  return el.getAttribute('placeholder') || el.name || 'ช่องที่จำเป็นต้องกรอก';
}
 
  
  function isVisible(el){
  if (!el) return false;
  if (el.offsetParent === null) return false; // hidden/display none
  return true;
}

function getLabelText(el){
  // หา label ที่ใกล้ที่สุด (จากโครงสร้างของคุณส่วนใหญ่ label อยู่ก่อน input ใน div เดียวกัน)
  const wrap = el.closest('div');
  const label = wrap?.querySelector('label');
  const t = (label?.innerText || '').replace('*','').trim();
  return t || el.getAttribute('placeholder') || el.name || 'ช่องที่จำเป็นต้องกรอก';
}

function addRed(el){
  el.classList.add('req-border');
}

function removeRed(el){
  el.classList.remove('req-border');
}

function focusOnly(el){
  try { el.focus({ preventScroll: true }); } catch(e){}
  try { el.scrollIntoView({ behavior:'smooth', block:'center' }); } catch(e){}
}

function isEmptyRequired(el){
  if (!el) return false;
  if (el.disabled) return false;
  if (!isVisible(el)) return false;
  if (el.type === 'hidden') return false; // hidden ไม่เอาใน step-by-step
  const v = (el.value || '').trim();
  return !v;
}

// ✅ เงื่อนไขเฉพาะของ "เบอร์โทร"
function validatePhoneSpecial(){
  const noPhone = document.getElementById('no_phone')?.checked;
  const phone   = document.getElementById('phone_input');

  if (noPhone) return { ok:true }; // ✅ สำคัญมาก

  if (!phone || phone.disabled) return { ok:true };

  if ((phone.value || '').trim() === '') {
    return { ok:false, el: phone, msg:'กรุณากรอกเบอร์โทรศัพท์ หรือเลือก “ไม่มีเบอร์โทร”' };
  }

  return { ok:true };
}


// ✅ เงื่อนไขเฉพาะของ "สถานที่" (คุณต้องการ step-by-step ด้วย)
function validateLocationSpecial(){
  const b = document.getElementById('in_building')?.value?.trim() || '';
  const f = document.getElementById('in_floor')?.value?.trim() || '';
  const r = document.getElementById('in_room')?.value?.trim() || '';

  // ถ้าคุณต้องการบังคับแค่ building ให้เช็คแค่ b
  // แต่จากที่คุณใส่ required ทั้ง 3 ตัวไว้: ให้เช็คตาม required จริง
  // (ถ้าจะปรับเป็นแค่ building ให้ comment needFloor/needRoom ได้)

  // ใช้ logic เดิมของคุณ (ต้องมี spatialDB พร้อม)
  const bObj = (window.spatialDB || []).find(x => String(x.id) === String(b));
  const bHasFloors = (bObj?.floors || []).length > 0;

  let needFloor = bHasFloors;
  let needRoom  = false;

  if (needFloor) {
    const fObj = (bObj?.floors || []).find(x => String(x.id) === String(f));
    needRoom = ((fObj?.rooms || []).length > 0);
  }

  if (!b) {
    const trigger = document.getElementById('loc_trigger');
    return { ok:false, el: trigger, msg: 'กรุณาเลือกอาคาร/สาขา' , openModal:true };
  }
  if (needFloor && !f) {
    const trigger = document.getElementById('loc_trigger');
    return { ok:false, el: trigger, msg: 'กรุณาเลือก “ชั้น”' , openModal:true };
  }
  if (needRoom && !r) {
    const trigger = document.getElementById('loc_trigger');
    return { ok:false, el: trigger, msg: 'กรุณาเลือก “ห้อง”' , openModal:true };
  }

  return { ok:true };
}

// ✅ ตัวนี้จะ “รอ” ให้ผู้ใช้กรอกช่องนั้นให้ผ่าน แล้วค่อย resolve
function waitUntilValid(el, validatorFn){
  return new Promise((resolve) => {
    const handler = () => {
      const ok = validatorFn ? validatorFn() : !isEmptyRequired(el);
      if (ok) {
        removeRed(el);
        el.removeEventListener('input', handler);
        el.removeEventListener('change', handler);
        el.removeEventListener('blur', handler);
        resolve(true);
      }
    };

    el.addEventListener('input', handler);
    el.addEventListener('change', handler);
    el.addEventListener('blur', handler);

    // เผื่อ user กรอกอยู่แล้ว
    handler();
  });
}

// ✅ หา “ตัวแรก” ที่ไม่ผ่าน ตามลำดับที่ต้องการ
function getFirstInvalidStep(form){
  // 1) required ทั่วไป (ยกเว้น phone/location เพราะมี special)
  const requiredEls = Array.from(
    form.querySelectorAll('input[required], textarea[required], select[required]')
  ).filter(el => {
    if (el.id === 'phone_input') return false;
    if (el.id === 'in_building' || el.id === 'in_floor' || el.id === 'in_room') return false;
    return true;
  });

  for (const el of requiredEls) {
    if (isEmptyRequired(el)) {
      return { type:'required', el, msg:`กรุณากรอก: ${getLabelText(el)}` };
    }
  }

  // 2) phone special
  const phone = validatePhoneSpecial();
  if (!phone.ok) return { type:'phone', ...phone };

  // 3) location special
  const loc = validateLocationSpecial();
  if (!loc.ok) return { type:'location', ...loc };

  return null;
}

async function validateStepByStepAuto(form){
  // loop ทีละข้อ จนกว่าจะผ่านหมด
  while (true) {
    const bad = getFirstInvalidStep(form);
    if (!bad) return true;

    // ทำกรอบแดงค้าง + โฟกัส
    const focusEl = bad.el;

    // ถ้าเป็น div (loc_trigger) ให้ใส่ tabindex เพื่อโฟกัสได้
    if (focusEl && focusEl.tagName === 'DIV' && !focusEl.hasAttribute('tabindex')) {
      focusEl.setAttribute('tabindex', '0');
    }

    addRed(focusEl);
    focusOnly(focusEl);

    await Swal.fire({
      icon: 'warning',
      title: 'กรุณากรอกข้อมูลให้ครบ',
      text: bad.msg,
      confirmButtonText: 'ไปกรอก',
      confirmButtonColor: '#006B9F',
      allowOutsideClick: false,
      allowEscapeKey: true
    });

    // ถ้าเป็น location ให้เปิด modal แล้ว “รอ” จนกว่าจะเลือกครบ
    if (bad.type === 'location' && bad.openModal) {
      window.openSpatialModal?.();
      // รอจนกว่าจะเลือกครบ (เช็คด้วย validateLocationSpecial)
      await waitUntilValid(document.getElementById('loc_trigger'), () => validateLocationSpecial().ok);
      continue;
    }

    // ถ้าเป็น phone ให้รอจน phone ผ่าน
    if (bad.type === 'phone') {
      await waitUntilValid(document.getElementById('phone_input'), () => validatePhoneSpecial().ok);
      continue;
    }

    // required ปกติ: รอจนไม่ว่าง
    await waitUntilValid(focusEl);
  }
}
 
function isSpatialOpen(){
  const m = document.getElementById('spatial_modal');
  return m && !m.classList.contains('hidden');
}
function sleep(ms){ return new Promise(r=>setTimeout(r, ms)); }

function isSpatialOpen(){
  const m = document.getElementById('spatial_modal');
  return m && !m.classList.contains('hidden');
}

function markInvalid(el){
  if(!el) return;
  el.classList.add('field-invalid'); // ใช้ class ที่คุณมีแล้ว
  if (el.tagName === 'DIV' && !el.hasAttribute('tabindex')) el.setAttribute('tabindex','0');

  try { el.focus({ preventScroll:true }); } catch { try{ el.focus(); }catch{} }
  try { el.scrollIntoView({behavior:'smooth', block:'center'}); } catch {}
}

function clearInvalid(el){
  if(!el) return;
  el.classList.remove('field-invalid');
}

function getLabel(el, fallback){
  if(!el) return fallback || 'ช่องที่จำเป็นต้องกรอก';

  // ✅ เอา label[for=id] มาก่อน (แม่นสุด)
  if (el.id) {
    const lb = document.querySelector(`label[for="${CSS.escape(el.id)}"]`);
    const t = lb?.innerText?.replace('*','')?.trim();
    if (t) return t;
  }

  // fallback เดิม
  const wrap = el.closest('div, section, article');
  const lbl = wrap?.querySelector('label');
  const t2 = lbl?.innerText?.replace('*','')?.trim();
  return t2 || el.getAttribute('placeholder') || el.name || fallback || 'ช่องที่จำเป็นต้องกรอก';
}

async function swalWarn(msg){
  if (Swal.isVisible()) Swal.close();
  await Swal.fire({
    icon: 'warning',
    title: 'กรุณากรอกข้อมูลให้ครบ',
    text: msg,
    confirmButtonText: 'ไปกรอก',
    confirmButtonColor: '#006B9F',
    allowOutsideClick: false,
    allowEscapeKey: true
  });
}

function checkPhone(){
  const noPhone = document.getElementById('no_phone')?.checked;
  const phone = document.getElementById('phone_input');
  if (!phone) return true;
  if (noPhone) return true;
  return phone.value.trim() !== '';
}

function checkLocation(){
  const b = document.getElementById('in_building')?.value?.trim() || '';
  const f = document.getElementById('in_floor')?.value?.trim() || '';
  const r = document.getElementById('in_room')?.value?.trim() || '';

  if(!b) return false;

  const bObj = (window.spatialDB || []).find(x => String(x.id) === String(b));
  const bHasFloors = (bObj?.floors || []).length > 0;

  let needFloor = bHasFloors;
  let needRoom = false;

  if (needFloor) {
    const fObj = (bObj?.floors || []).find(x => String(x.id) === String(f));
    needRoom = ((fObj?.rooms || []).length > 0);
  }

  if (needFloor && !f) return false;
  if (needRoom && !r) return false;

  return true;
}

// ✅ แปลง/ย่อรูปฝั่ง Browser ก่อนส่งเข้า PHP
function fileToImage(file) {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();

    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve(img);
    };

    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('ไม่สามารถอ่านรูปภาพนี้ได้'));
    };

    img.src = url;
  });
}

function canvasToBlob(canvas, type, quality) {
  return new Promise((resolve) => {
    if (canvas.toBlob) {
      canvas.toBlob(resolve, type, quality);
    } else {
      const dataUrl = canvas.toDataURL(type, quality);
      const arr = dataUrl.split(',');
      const mime = arr[0].match(/:(.*?);/)[1];
      const bstr = atob(arr[1]);
      let n = bstr.length;
      const u8arr = new Uint8Array(n);

      while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
      }

      resolve(new Blob([u8arr], { type: mime }));
    }
  });
}

async function compressImageBeforeUpload(file, maxSide = 1600, quality = 0.8) {
  if (!file || !file.type || !file.type.startsWith('image/')) {
    return file;
  }

  const img = await fileToImage(file);

  let width = img.naturalWidth || img.width;
  let height = img.naturalHeight || img.height;

	if (!width || !height) {
	  throw new Error('ไม่สามารถอ่านขนาดรูปได้');
	}

  // ✅ คำนวณขนาดใหม่ โดยให้ด้านที่ยาวที่สุดไม่เกิน 1600px
  if (width > height) {
    if (width > maxSide) {
      height = Math.round(height * (maxSide / width));
      width = maxSide;
    }
  } else {
    if (height > maxSide) {
      width = Math.round(width * (maxSide / height));
      height = maxSide;
    }
  }

  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = height;

  const ctx = canvas.getContext('2d');

  // ✅ พื้นหลังขาว กันไฟล์ PNG โปร่งใสกลายเป็นดำ
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, width, height);
  ctx.drawImage(img, 0, 0, width, height);

  const blob = await canvasToBlob(canvas, 'image/jpeg', quality);

	if (!blob) {
	  throw new Error('ไม่สามารถแปลงรูปเป็น JPG ได้');
	}
  // ✅ เปลี่ยนชื่อไฟล์เป็น jpg จริง
  const newName = 'repair_' + Date.now() + '_' + Math.floor(Math.random() * 100000) + '.jpg';

  return new File([blob], newName, {
    type: 'image/jpeg',
    lastModified: Date.now()
  });
}

async function buildCompressedRepairFormData(form) {
  // ✅ สร้าง FormData ใหม่เอง เพื่อกันไฟล์ต้นฉบับติดเข้าไป
  const fd = new FormData();

  const all = new FormData(form);

  all.forEach((value, key) => {
    // ✅ ไม่เอาไฟล์รูปต้นฉบับจาก form
    if (key === 'images[]' || key === 'image[]') return;

    fd.append(key, value);
  });

  const fileInput = document.getElementById('file_input');
  const files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];

  for (let i = 0; i < files.length && i < 5; i++) {
    try {
      const compressedFile = await compressImageBeforeUpload(files[i], 1600, 0.8);

      // ✅ กันพลาด ต้องเป็น JPG จริงเท่านั้น
      if (!compressedFile || compressedFile.type !== 'image/jpeg') {
        throw new Error('convert_failed');
      }

      fd.append('images[]', compressedFile, compressedFile.name);

    } catch (err) {
      console.error('compress image error:', err, files[i]);

      await Swal.fire({
        icon: 'error',
        title: 'รูปภาพนี้ไม่สามารถอัปโหลดได้',
        html: 'กรุณาถ่ายรูปใหม่ หรือเลือกรูปที่เป็น <b>JPG / PNG</b><br>ถ้าเป็น iPhone อาจเป็นไฟล์ HEIC ที่ระบบอ่านไม่ได้',
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#006B9F'
      });

      throw err;
    }
  }

  // ✅ debug ชั่วคราว
  fd.append('__client_compress', '1');

  return fd;
}

function initSwalStepValidationNoAuto(){
  const form = document.getElementById('repair_form');
  if(!form) return;

  form.setAttribute('novalidate','novalidate');

  // ✅ ลำดับเช็ค (ปรับเพิ่ม/ลดได้)
  const steps = [
    { id:'date', el:()=>document.getElementById('report_date'),
      check:(el)=> (el?.value||'').trim() !== '',
      msg:(el)=> `กรุณากรอก: ${getLabel(el,'วันที่แจ้ง')}` },

    { id:'time', el:()=>document.getElementById('report_time'),
      check:(el)=> (el?.value||'').trim() !== '',
      msg:(el)=> `กรุณากรอก: ${getLabel(el,'เวลาที่แจ้ง')}` },

    { id:'name', el:()=>form.querySelector('input[name="name"]'),
      check:(el)=> (el?.value||'').trim() !== '',
      msg:(el)=> `กรุณากรอก: ${getLabel(el,'ชื่อ-นามสกุล')}` },

    { id:'phone', el:()=>document.getElementById('phone_input'),
      check:()=> checkPhone(),
      msg:()=> 'กรุณากรอกเบอร์โทรศัพท์ หรือเลือก “ไม่มีเบอร์โทร”' },

    { id:'location', el:()=>document.getElementById('loc_trigger'),
      check:()=> checkLocation(),
      msg:()=> 'กรุณาเลือกสถานที่ (อาคาร/ชั้น/ห้อง)',
      onFail: async ()=>{
        window.showLocationError?.();
        if(!isSpatialOpen()) window.openSpatialModal?.();
      }
    },

  { id:'problem', el:()=>form.querySelector('textarea[name="problem_detail"]'),
  check:(el)=> (el?.value||'').trim() !== '',
  msg:()=> 'กรุณากรอก: อาการเสีย/ปัญหาที่พบ' },
  ];

  // ✅ กรอบแดงค้าง: พอกรอกผ่านค่อยเอาออก (แต่ไม่ auto ไปถัดไป)
  function bindClearOnValid(step){
    const el = step.el();
    if(!el) return;

    const handler = ()=>{
      const nowEl = step.el();
      const ok = step.check(nowEl);
      if(ok) clearInvalid(nowEl);
    };

    el.addEventListener('input', handler);
    el.addEventListener('change', handler);
    el.addEventListener('blur', handler);

    // location ใช้ interval ช่วย เพราะค่าอยู่ใน hidden
    if(step.id === 'location'){
      const t = setInterval(()=>{ handler(); }, 300);
      // ไม่ต้อง clearInterval ก็ได้ (แต่ถ้าจะชัวร์ ให้ clear ตอน submit สำเร็จ)
    }
  }

  // bind ทุก step (ครั้งเดียว)
  steps.forEach(bindClearOnValid);

  let running = false;

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    if(running) return;
    running = true;

    // ✅ ไล่หา “ตัวแรกที่ยังไม่ผ่าน” แล้วหยุดทันที
    for(const step of steps){
      const el = step.el();
      if(!el) continue;

      if(step.check(el)){
        clearInvalid(el);
        continue;
      }

      // กัน Swal ซ้อนกับ modal
      if(step.id !== 'location' && isSpatialOpen()){
        window.closeSpatialModal?.();
        await sleep(260);
      }

      markInvalid(el);
      if(step.onFail) await step.onFail();
      await swalWarn(step.msg(el));

      running = false;
      return; // ❌ ไม่เช็คต่อ (ไม่ auto)
    }

    // ✅ ผ่านหมดแล้ว ค่อย submit จริง
    try {
  const fd = await buildCompressedRepairFormData(form);

const fi = document.getElementById('file_input');
console.log('file_input.files.length =', fi?.files?.length || 0);
console.log('Compressed FormData images[] count =', fd.getAll('images[]').length);
console.log('Compressed FormData images[] =', fd.getAll('images[]'));


  Swal.fire({
    title: 'กำลังบันทึก...',
    html: 'กรุณารอสักครู่',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => Swal.showLoading()
  });

  const res = await fetch('save_repair_api1.php', { method: 'POST', body: fd });
  const json = await res.json().catch(() => ({}));

  Swal.close();

  if (!res.ok || json.status !== 'success') {
    await Swal.fire({
      icon: 'error',
      title: 'บันทึกไม่สำเร็จ',
      text: json.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่',
      confirmButtonText: 'ปิด',
      confirmButtonColor: '#006B9F'
    });
    running = false;
    return;
  }

  await Swal.fire({
    icon: 'success',
    title: 'บันทึกสำเร็จ',
    text: `เลขที่เอกสาร: ${json.rp_format || '-'}`,
    confirmButtonText: 'ตกลง',
    confirmButtonColor: '#006B9F'
  });
  
 

// ผู้ใช้ใช้งานหน้าต่อได้ทันที
form.reset();

if (typeof window.resetRepairImages === 'function') {
  window.resetRepairImages();
}

// ปิด try หลัก
} catch (err) {
  console.error('Save repair error:', err);

  Swal.close();

  await Swal.fire({
    icon: 'error',
    title: 'เชื่อมต่อ API ไม่ได้',
    text: 'กรุณาตรวจสอบอินเทอร์เน็ต/เซิร์ฟเวอร์ แล้วลองใหม่',
    confirmButtonText: 'ปิด',
    confirmButtonColor: '#006B9F'
  });
}

running = false;
return;
  }, {capture:true});
}

 

  </script>


</body>
</html>