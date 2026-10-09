/**
 * Image Carousel Component (Integrated UI & Logic)
 * ✅ รองรับ "วิดีโอ + รูปภาพ" ใน carousel เดียวกัน (วิดีโอมาก่อนเสมอ)
 * ✅ เปิดแบบเต็มหน้าจอ ทับทุกอย่าง (รวม navbar / sidebar ของ main.php)
 *    — หน้าที่เปิดอยู่ใน iframe ของ main.php จะสร้างตัวพรีวิวไว้ที่หน้าต่างบนสุด
 *
 * ใช้งาน:
 *   ImageCarousel.open(['a.jpg','b.jpg'])
 *   ImageCarousel.open(['clip.mp4','a.jpg'])                    // ตรวจจับวิดีโอจากนามสกุลไฟล์
 *   ImageCarousel.open([{type:'video',src:'x'},{type:'image',src:'y'}])
 *   ImageCarousel.open(list, 'หัวข้อ', { videoFirst:false, startIndex:2 })
 */

const ImageCarousel = (function () {
  let currentSlide = 0;
  let rrItems = [];              // [{ type:'video'|'image', src, broken? }]
  let rrIdleTimer = null;
  let host = window;             // หน้าต่างที่ใช้แสดงพรีวิว (บนสุดถ้าเข้าถึงได้)
  let doc = document;
  let root = null;               // overlay element
  let cleanups = [];             // ตัวถอด event listener ตอนปิด
  let savedOverflow = '';

  const VIDEO_EXT_RE = /\.(mp4|mov|m4v|webm|ogg|ogv|avi|mkv|3gp|quicktime)(\?.*)?$/i;

  let rrZoom = {
      scale: 1, min: 1, max: 6,
      tx: 0, ty: 0, rotate: 0,
      dragging: false, startX: 0, startY: 0,
      baseTX: 0, baseTY: 0
  };

  // ไอคอน (SVG ในตัว ไม่พึ่ง lucide เพราะหน้าต่างบนสุดอาจไม่มี)
  const svg = (d, s = 20) => `<svg xmlns="http://www.w3.org/2000/svg" width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
  const ICON = {
      left:  svg('<path d="m15 18-6-6 6-6"/>'),
      right: svg('<path d="m9 18 6-6-6-6"/>'),
      bigL:  svg('<path d="m15 18-6-6 6-6"/>', 26),
      bigR:  svg('<path d="m9 18 6-6-6-6"/>', 26),
      close: svg('<path d="M18 6 6 18"/><path d="m6 6 12 12"/>', 22),
      max:   svg('<path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="m21 3-7 7"/><path d="m3 21 7-7"/>'),
      min:   svg('<path d="m14 10 7-7"/><path d="M20 10h-6V4"/><path d="m3 21 7-7"/><path d="M4 14h6v6"/>'),
      minus: svg('<path d="M5 12h14"/>'),
      plus:  svg('<path d="M5 12h14"/><path d="M12 5v14"/>'),
      rot:   svg('<path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/>')
  };

  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

  function pickHost() {
      try {
          if (window.top !== window && window.top.document && window.top.document.body) return window.top;
      } catch (e) { /* ต่าง origin: แสดงในหน้าตัวเอง */ }
      return window;
  }

  const injectStyles = () => {
      if (doc.getElementById('rrv-styles')) return;
      const style = doc.createElement('style');
      style.id = 'rrv-styles';
      style.textContent = `
          .rrv-overlay { position: fixed; inset: 0; z-index: 2147483000; background: rgba(2,6,23,.96); color: #fff;
              user-select: none; -webkit-user-select: none; opacity: 0; transition: opacity .2s ease; overscroll-behavior: contain; }
          .rrv-overlay.rrv-in { opacity: 1; }
          .rrv-overlay button { font: inherit; color: inherit; background: none; border: 0; cursor: pointer; -webkit-tap-highlight-color: transparent; }

          .rrv-stage { position: absolute; inset: 0; overflow: hidden; }
          .rrv-item { display: none; position: absolute; inset: 0; align-items: center; justify-content: center; }
          .rrv-item.active { display: flex; }
          .rrv-item.rrv-dead { display: none !important; }
          .rrv-imgwrap { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; touch-action: none; cursor: grab; }
          .rrv-imgwrap.rrv-grabbing { cursor: grabbing; }
          .rrv-imgwrap img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform .12s ease-out; pointer-events: none; -webkit-user-drag: none; }
          /* วิดีโอ: เว้นที่บน/ล่างให้แถบหัว/เครื่องมือ ไม่บังปุ่มควบคุมของวิดีโอ */
          .rrv-vidwrap { position: absolute; inset: 64px 0 112px; display: flex; align-items: center; justify-content: center; }
          .rrv-vidwrap video { max-width: 100%; max-height: 100%; object-fit: contain; background: #000; }

          .rrv-top { position: absolute; top: 0; left: 0; right: 0; z-index: 5; display: flex; align-items: center; gap: .75rem;
              padding: max(.75rem, env(safe-area-inset-top)) .75rem 1.5rem 1rem; background: linear-gradient(rgba(2,6,23,.8), rgba(2,6,23,0)); }
          .rrv-title { flex: 1; min-width: 0; font-weight: 700; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
          .rrv-kind { flex: none; display: inline-flex; gap: 6px; align-items: center; font-size: 12px; font-weight: 700;
              background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); padding: 4px 11px; border-radius: 999px; }
          .rrv-close { flex: none; width: 44px; height: 44px; border-radius: 999px; display: flex; align-items: center; justify-content: center;
              background: rgba(255,255,255,.14) !important; transition: background .15s; }
          .rrv-close:hover { background: rgba(255,255,255,.26) !important; }

          .rrv-side { position: absolute; top: 50%; z-index: 4; width: 52px; height: 52px; margin-top: -26px; border-radius: 999px;
              display: none; align-items: center; justify-content: center; background: rgba(255,255,255,.12) !important; transition: background .15s, opacity .3s; }
          .rrv-side:hover { background: rgba(255,255,255,.25) !important; }
          .rrv-side.rrv-l { left: 1.25rem; } .rrv-side.rrv-r { right: 1.25rem; }
          .rrv-multi .rrv-side { display: flex; }

          .rrv-bottom { position: absolute; left: 0; right: 0; bottom: 0; z-index: 5; display: flex; flex-direction: column; align-items: center; gap: .7rem;
              padding: 1.5rem .75rem max(1rem, env(safe-area-inset-bottom)); background: linear-gradient(rgba(2,6,23,0), rgba(2,6,23,.8)); pointer-events: none; }
          .rrv-bottom > * { pointer-events: auto; }
          .rrv-toolbar { display: flex; align-items: center; gap: .25rem; padding: .35rem .6rem; max-width: 100%; flex-wrap: wrap; justify-content: center;
              background: rgba(15,23,42,.85); border: 1px solid rgba(255,255,255,.15); border-radius: 999px; box-shadow: 0 10px 25px -5px rgba(0,0,0,.5); }
          .rrv-btn { width: 40px; height: 40px; border-radius: 999px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
          .rrv-btn:hover { background: rgba(255,255,255,.18) !important; }
          .rrv-sep { width: 1px; height: 20px; background: rgba(255,255,255,.2); margin: 0 4px; }
          .rrv-zoom { min-width: 48px; text-align: center; font-size: 13px; font-weight: 600; }
          .rrv-video-slide .rrv-imgonly { display: none; }
          .rrv-solo .rrv-navonly { display: none; }

          .rrv-dots { display: flex; justify-content: center; gap: .5rem; flex-wrap: wrap; max-width: 90vw; }
          .rrv-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.35) !important; padding: 0; transition: all .25s; }
          .rrv-dot.active { background: #fff !important; transform: scale(1.3); }
          .rrv-dot.rrv-dot-video { border-radius: 3px; width: 11px; background: rgba(56,189,248,.55) !important; }
          .rrv-dot.rrv-dot-video.active { background: #38bdf8 !important; }

          /* ไม่ขยับเมาส์/แตะสักพัก => ซ่อนแถบต่าง ๆ ให้เห็นรูปเต็ม ๆ (ปุ่มปิดยังอยู่) */
          .rrv-top, .rrv-bottom, .rrv-side, .rrv-kind { transition: opacity .3s; }
          .rrv-idle .rrv-bottom, .rrv-idle .rrv-side, .rrv-idle .rrv-title, .rrv-idle .rrv-kind { opacity: 0; pointer-events: none; }
          .rrv-idle .rrv-top { background: none; }
          .rrv-idle.rrv-video-slide .rrv-bottom { opacity: 1; pointer-events: none; }

          .rrv-empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: .95rem; }

          @media (max-width: 640px) {
              .rrv-multi .rrv-side { display: none; }      /* มือถือใช้ปัดซ้าย/ขวา */
              .rrv-btn { width: 36px; height: 36px; }
              .rrv-btn svg { width: 18px; height: 18px; }
              .rrv-zoom { min-width: 40px; font-size: 12px; }
              .rrv-toolbar { gap: 0; padding: .25rem .4rem; }
              .rrv-vidwrap { inset: 60px 0 104px; }
          }
      `;
      doc.head.appendChild(style);
  };

  // --- Helpers ---
  const isVideoSrc = (src) => VIDEO_EXT_RE.test(String(src || ''));

  // ดึง URL จาก item ได้ทั้ง string, {src}, {url}, {path}, ...
  function pickUrl(it) {
      if (it == null) return '';
      if (typeof it === 'string') return it.trim();
      if (typeof it !== 'object') return '';
      const key = ['src', 'url', 'path', 'file', 'link', 'image', 'img', 'video', 'full_url', 'file_url']
          .find(k => typeof it[k] === 'string' && it[k].trim());
      return key ? it[key].trim() : '';
  }

  // URL สัมพัทธ์ต้องอ้างจากหน้าที่เรียก (หน้าต่างบนสุดอาจอยู่คนละโฟลเดอร์)
  function absUrl(src) {
      try { return new URL(src, window.location.href).href; } catch (e) { return src; }
  }

  // รับได้ทั้ง array ของ string และ array ของ {type, src}
  function normalizeItems(list, videoFirst) {
      const arr = (Array.isArray(list) ? list : [list])
          .map(it => ({ raw: it, src: pickUrl(it) }))
          .filter(it => it.src && !/^\[object/i.test(it.src))
          .map(it => ({
              src: absUrl(it.src),
              type: it.raw?.type === 'video' || it.raw?.type === 'image'
                  ? it.raw.type
                  : (isVideoSrc(it.src) ? 'video' : 'image')
          }));

      if (videoFirst === false) return arr;

      // ✅ วิดีโอมาก่อนรูปภาพ (stable sort — ลำดับภายในกลุ่มเดิมไม่เปลี่ยน)
      return [
          ...arr.filter(it => it.type === 'video'),
          ...arr.filter(it => it.type !== 'video')
      ];
  }

  const $ = id => doc.getElementById(id);
  const isVideoAt = (i) => rrItems[i]?.type === 'video';
  const hasVideo  = () => rrItems.some(it => it.type === 'video');
  const aliveItems = () => rrItems.filter(it => !it.broken);

  function pauseAllVideos() {
      root?.querySelectorAll('video').forEach(v => { try { v.pause(); } catch (e) {} });
  }

  function on(target, ev, fn, opt) { target.addEventListener(ev, fn, opt); cleanups.push(() => target.removeEventListener(ev, fn, opt)); }

  // --- Core Logic ---
  function resetZoomState() { rrZoom = { ...rrZoom, scale: 1, tx: 0, ty: 0, rotate: 0, dragging: false }; }
  function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }
  function getActiveImg() { return isVideoAt(currentSlide) ? null : $('rrv-img-' + currentSlide); }

  function updateZoomLabel() {
      const lb = $('rrv-zoom');
      if (lb) lb.textContent = Math.round(rrZoom.scale * 100) + '%';
  }

  function applyTransformToActive() {
      const img = getActiveImg();
      if (!img) return;
      img.style.transform = `translate(${rrZoom.tx}px,${rrZoom.ty}px) scale(${rrZoom.scale}) rotate(${rrZoom.rotate}deg)`;
  }

  function setZoom(newScale) {
      if (isVideoAt(currentSlide)) return;   // วิดีโอไม่ต้องซูม
      rrZoom.scale = clamp(newScale, rrZoom.min, rrZoom.max);
      if (rrZoom.scale <= 1.001) { rrZoom.scale = 1; rrZoom.tx = 0; rrZoom.ty = 0; }
      updateZoomLabel();
      applyTransformToActive();
  }

  // อัปเดต toolbar / ป้ายชนิดสื่อ ตามสไลด์ปัจจุบัน
  function syncMediaUI() {
      if (!root) return;
      const onVideo = isVideoAt(currentSlide);
      root.classList.toggle('rrv-video-slide', onVideo);
      const alive = aliveItems();
      root.classList.toggle('rrv-multi', alive.length > 1);
      root.classList.toggle('rrv-solo', alive.length <= 1);
      const kind = $('rrv-kind');
      if (kind) {
          // ✅ นับเฉพาะสื่อที่โหลดได้จริง (ตัวที่พังถูกตัดทิ้งไปแล้ว)
          const pos = alive.indexOf(rrItems[currentSlide]) + 1;
          kind.innerHTML = `<span>${onVideo ? 'วิดีโอ' : 'รูปภาพ'}</span><span style="opacity:.7">${pos} / ${alive.length}</span>`;
      }
      const dots = $('rrv-dots');
      if (dots) dots.style.display = alive.length > 1 ? 'flex' : 'none';
  }

  // หา index ถัดไป/ก่อนหน้า ที่ยังโหลดได้ (ข้ามตัวที่พัง)
  function nextAliveIndex(from, dir) {
      const n = rrItems.length;
      for (let i = 1; i <= n; i++) {
          const idx = ((from + dir * i) % n + n) % n;
          if (!rrItems[idx].broken) return idx;
      }
      return -1;
  }

  // ✅ ไฟล์โหลดไม่ได้ (404/ฟอร์แมตไม่รองรับ/ค่าใน DB ไม่ใช่ URL) → ตัดสไลด์นั้นทิ้งเงียบ ๆ
  function markBroken(idx) {
      const item = rrItems[idx];
      if (!item || item.broken || !root) return;
      item.broken = true;

      $(`rrv-slide-${idx}`)?.classList.add('rrv-dead');
      $(`rrv-dot-${idx}`)?.remove();

      if (!aliveItems().length) {
          const stage = $('rrv-stage');
          if (stage) stage.innerHTML = `<div class="rrv-empty">ไม่สามารถโหลดไฟล์สื่อได้</div>`;
          root.classList.add('rrv-solo');
          return;
      }
      if (idx === currentSlide) {
          const to = nextAliveIndex(idx, 1);
          if (to >= 0) goToSlide(to);
      } else {
          syncMediaUI();
      }
  }

  function goToSlide(index) {
      if (rrItems.length <= 0 || !root) return;
      const oldSlide = currentSlide;
      let target = ((index % rrItems.length) + rrItems.length) % rrItems.length;
      if (rrItems[target].broken) {
          target = nextAliveIndex(target, 1);
          if (target < 0) return;
      }
      currentSlide = target;

      pauseAllVideos();   // ✅ ออกจากสไลด์ไหน ก็หยุดวิดีโอสไลด์นั้น

      $(`rrv-slide-${oldSlide}`)?.classList.remove('active');
      $(`rrv-dot-${oldSlide}`)?.classList.remove('active');
      $(`rrv-slide-${currentSlide}`)?.classList.add('active');
      $(`rrv-dot-${currentSlide}`)?.classList.add('active');

      resetZoomState();
      updateZoomLabel();
      applyTransformToActive();
      syncMediaUI();
  }

  function attachPanZoomHandlers(stage) {
      // ล้อเมาส์ = ซูม | ปัดแนวนอนบนทัชแพด = เลื่อนรูป
      let wheelLock = 0;
      on(stage, 'wheel', (e) => {
          if (isVideoAt(currentSlide)) return;   // ปล่อยให้วิดีโอทำงานตามปกติ
          e.preventDefault();
          if (!e.ctrlKey && Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
              if (rrZoom.scale > 1 || Math.abs(e.deltaX) < 15) return;
              const now = Date.now();
              if (now - wheelLock < 450) return;
              wheelLock = now;
              e.deltaX > 0 ? api.next() : api.prev();
              return;
          }
          if (!e.deltaY) return;
          setZoom(e.deltaY > 0 ? rrZoom.scale / 1.15 : rrZoom.scale * 1.15);
      }, { passive: false });

      // ลาก/ปัด ด้วย Pointer Events (เมาส์ + นิ้ว + ปากกา ใช้โค้ดเดียวกัน)
      //  - ไม่ได้ซูม: ลากซ้าย/ขวาเกิน 50px = เปลี่ยนรูป (รูปเลื่อนตามนิ้วระหว่างลาก)
      //  - ซูมอยู่: ลากเพื่อเลื่อนดูส่วนต่าง ๆ ของรูป
      //  - สองนิ้ว: ถ่าง/บีบ เพื่อซูม
      const pts = new Map();
      let pinch = null, lastTap = 0, moved = false, lastType = 'mouse';
      const activeWrap = () => root?.querySelector('.rrv-item.active .rrv-imgwrap');

      on(stage, 'pointerdown', (e) => {
          lastType = e.pointerType;
          if (isVideoAt(currentSlide) || e.target.closest('button')) return;
          if (e.pointerType === 'mouse' && e.button !== 0) return;
          try { stage.setPointerCapture(e.pointerId); } catch (err) {}
          pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
          if (pts.size === 2) {
              const [a, b] = [...pts.values()];
              pinch = { d: Math.hypot(a.x - b.x, a.y - b.y) || 1, scale: rrZoom.scale };
              rrZoom.dragging = false;
              return;
          }
          moved = false;
          rrZoom.dragging = true;
          rrZoom.startX = e.clientX; rrZoom.startY = e.clientY;
          rrZoom.baseTX = rrZoom.tx; rrZoom.baseTY = rrZoom.ty;
          activeWrap()?.classList.add('rrv-grabbing');
          const img = getActiveImg();
          if (img) img.style.transition = 'none';
      });

      on(stage, 'pointermove', (e) => {
          if (!pts.has(e.pointerId)) return;
          pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
          if (pinch && pts.size >= 2) {
              const [a, b] = [...pts.values()];
              setZoom(pinch.scale * (Math.hypot(a.x - b.x, a.y - b.y) / pinch.d));
              return;
          }
          if (!rrZoom.dragging) return;
          const dx = e.clientX - rrZoom.startX, dy = e.clientY - rrZoom.startY;
          if (Math.abs(dx) > 5 || Math.abs(dy) > 5) moved = true;
          if (rrZoom.scale > 1) {
              rrZoom.tx = rrZoom.baseTX + dx;
              rrZoom.ty = rrZoom.baseTY + dy;
          } else if (aliveItems().length > 1) {
              rrZoom.tx = dx; rrZoom.ty = 0;      // รูปเลื่อนตามนิ้ว ให้รู้ว่ากำลังปัด
          }
          applyTransformToActive();
      });

      const end = (e) => {
          if (!pts.has(e.pointerId)) return;
          pts.delete(e.pointerId);
          if (pinch) { if (pts.size < 2) pinch = null; rrZoom.dragging = false; return; }
          if (!rrZoom.dragging) return;
          rrZoom.dragging = false;
          activeWrap()?.classList.remove('rrv-grabbing');
          const img = getActiveImg();
          if (img) img.style.transition = '';
          const dx = e.clientX - rrZoom.startX, dy = e.clientY - rrZoom.startY;
          if (rrZoom.scale <= 1) {
              rrZoom.tx = 0; rrZoom.ty = 0;
              if (e.type !== 'pointercancel' && Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
                  dx < 0 ? api.next() : api.prev();
                  return;
              }
              applyTransformToActive();
          }
          // แตะสองครั้ง (นิ้ว) = ซูมเข้า/ออก — เมาส์ใช้ดับเบิลคลิก
          if (e.pointerType !== 'mouse' && !moved && e.type === 'pointerup') {
              const now = Date.now();
              if (now - lastTap < 300) { setZoom(rrZoom.scale > 1 ? 1 : 2.5); lastTap = 0; } else lastTap = now;
          }
      };
      on(stage, 'pointerup', end);
      on(stage, 'pointercancel', end);
      // ดับเบิลคลิกเฉพาะเมาส์ (นิ้วใช้แตะสองครั้งด้านบน — เบราว์เซอร์มือถือยิง dblclick ซ้ำมาด้วย)
      on(stage, 'dblclick', () => { if (lastType === 'mouse' && !isVideoAt(currentSlide)) setZoom(rrZoom.scale > 1 ? 1 : 2.5); });
  }

  function bindAutoHide() {
      const show = () => {
          if (!root) return;
          root.classList.remove('rrv-idle');
          clearTimeout(rrIdleTimer);
          rrIdleTimer = setTimeout(() => root && root.classList.add('rrv-idle'), 2500);
      };
      ['pointermove', 'pointerdown', 'keydown', 'wheel'].forEach(ev => on(root, ev, show, { passive: true }));
      show();
  }

  function slideHtml(item, idx) {
      if (item.type === 'video') {
          return `
              <div class="rrv-item" id="rrv-slide-${idx}">
                  <div class="rrv-vidwrap">
                      <video id="rrv-vid-${idx}" data-i="${idx}" src="${esc(item.src)}" controls playsinline preload="metadata" controlsList="nodownload"></video>
                  </div>
              </div>`;
      }
      return `
          <div class="rrv-item" id="rrv-slide-${idx}">
              <div class="rrv-imgwrap">
                  <img id="rrv-img-${idx}" data-i="${idx}" src="${esc(item.src)}" alt="" draggable="false">
              </div>
          </div>`;
  }

  function updateFsIcon() {
      const b = $('rrv-fs');
      if (b) b.innerHTML = doc.fullscreenElement ? ICON.min : ICON.max;
  }

  function close(ev) {
      if (!root) return;
      pauseAllVideos();
      clearTimeout(rrIdleTimer);
      cleanups.forEach(fn => { try { fn(); } catch (e) {} });
      cleanups = [];
      if (doc.fullscreenElement) { try { doc.exitFullscreen(); } catch (e) {} }
      const el = root;
      root = null;
      el.classList.remove('rrv-in');
      // เปลี่ยนเมนู (หน้าใน iframe กำลังถูกทิ้ง) => ลบทันที เพราะ callback ของหน้าที่ถูกทิ้งจะไม่ถูกเรียกอีก
      if (ev && ev.type === 'pagehide') el.remove();
      else host.setTimeout(() => el.remove(), 200);
      doc.body.style.overflow = savedOverflow;
  }

  // --- Public API ---
  const api = {
      /**
       * @param {Array<string|{type:string,src:string}>} media
       * @param {string} [title]
       * @param {{videoFirst?:boolean, startIndex?:number}} [opts]
       */
      open: function (media, title, opts) {
          const o = opts || {};
          const items = normalizeItems(media, o.videoFirst);
          if (!items.length) {
              if (window.Swal) return Swal.fire("ไม่มีไฟล์สื่อ", "", "info");
              return alert("ไม่มีไฟล์สื่อ");
          }
          if (root) close();

          host = pickHost();
          doc = host.document;
          injectStyles();

          rrItems = items;
          currentSlide = clamp(Number(o.startIndex) || 0, 0, rrItems.length - 1);
          resetZoomState();

          const heading = title || (hasVideo() ? 'Media Preview' : 'Image Preview');

          root = doc.createElement('div');
          root.className = 'rrv-overlay';
          root.setAttribute('role', 'dialog');
          root.setAttribute('aria-modal', 'true');
          root.setAttribute('aria-label', heading);
          root.innerHTML = `
              <div class="rrv-stage" id="rrv-stage">${rrItems.map(slideHtml).join('')}</div>
              <div class="rrv-top">
                  <span class="rrv-kind" id="rrv-kind"></span>
                  <div class="rrv-title">${esc(heading)}</div>
                  <button type="button" class="rrv-close" data-act="close" title="ปิด (Esc)" aria-label="ปิด">${ICON.close}</button>
              </div>
              <button type="button" class="rrv-side rrv-l" data-act="prev" title="ก่อนหน้า" aria-label="ก่อนหน้า">${ICON.bigL}</button>
              <button type="button" class="rrv-side rrv-r" data-act="next" title="ถัดไป" aria-label="ถัดไป">${ICON.bigR}</button>
              <div class="rrv-bottom">
                  <div class="rrv-dots" id="rrv-dots">
                      ${rrItems.map((item, d) => `<button type="button" class="rrv-dot ${item.type === 'video' ? 'rrv-dot-video' : ''}" id="rrv-dot-${d}" data-act="jump" data-i="${d}" aria-label="สื่อที่ ${d + 1}"></button>`).join('')}
                  </div>
                  <div class="rrv-toolbar">
                      <button type="button" class="rrv-btn rrv-navonly" data-act="prev" title="ก่อนหน้า" aria-label="ก่อนหน้า">${ICON.left}</button>
                      <button type="button" class="rrv-btn rrv-navonly" data-act="next" title="ถัดไป" aria-label="ถัดไป">${ICON.right}</button>
                      <div class="rrv-sep rrv-navonly rrv-imgonly"></div>
                      <button type="button" class="rrv-btn rrv-imgonly" data-act="zout" title="ซูมออก" aria-label="ซูมออก">${ICON.minus}</button>
                      <div class="rrv-zoom rrv-imgonly" id="rrv-zoom">100%</div>
                      <button type="button" class="rrv-btn rrv-imgonly" data-act="zin" title="ซูมเข้า" aria-label="ซูมเข้า">${ICON.plus}</button>
                      <button type="button" class="rrv-btn rrv-imgonly" data-act="rot" title="หมุนภาพ (R)" aria-label="หมุนภาพ">${ICON.rot}</button>
                      ${doc.documentElement.requestFullscreen ? `<button type="button" class="rrv-btn" id="rrv-fs" data-act="fs" title="เต็มจอ (ซ่อนแถบเบราว์เซอร์)" aria-label="เต็มจอ">${ICON.max}</button>` : ''}
                  </div>
              </div>`;
          doc.body.appendChild(root);

          savedOverflow = doc.body.style.overflow;
          doc.body.style.overflow = 'hidden';

          // ไฟล์โหลดไม่ได้ => ตัดสไลด์ทิ้ง
          root.querySelectorAll('img[data-i], video[data-i]').forEach(m => m.addEventListener('error', () => markBroken(+m.dataset.i)));

          on(root, 'click', (e) => {
              const b = e.target.closest('[data-act]');
              if (!b) return;
              const act = b.dataset.act;
              if (act === 'close') close();
              else if (act === 'prev') api.prev();
              else if (act === 'next') api.next();
              else if (act === 'jump') api.jump(+b.dataset.i);
              else if (act === 'zin') api.zoom(1.25);
              else if (act === 'zout') api.zoom(0.8);
              else if (act === 'rot') api.rotate();
              else if (act === 'fs') api.toggleFs();
          });

          const onKey = (e) => {
              if (!root) return;
              if (e.key === 'Escape' && !doc.fullscreenElement) { e.preventDefault(); close(); }
              else if (e.key === 'ArrowRight') api.next();
              else if (e.key === 'ArrowLeft') api.prev();
              else if ((e.key === 'r' || e.key === 'R') && !isVideoAt(currentSlide)) api.rotate();
          };
          on(doc, 'keydown', onKey);
          if (doc !== document) {
              on(document, 'keydown', onKey);   // โฟกัสอาจยังอยู่ใน iframe
              on(window, 'pagehide', close);    // เปลี่ยนเมนู (iframe โหลดหน้าใหม่) => ปิดพรีวิวที่ค้างบนหน้าต่างบนสุด
          }
          on(doc, 'fullscreenchange', updateFsIcon);

          attachPanZoomHandlers($('rrv-stage'));
          bindAutoHide();

          const start = rrItems[currentSlide].broken ? nextAliveIndex(currentSlide, 1) : currentSlide;
          $(`rrv-slide-${start}`)?.classList.add('active');
          $(`rrv-dot-${start}`)?.classList.add('active');
          currentSlide = start;
          syncMediaUI();
          updateZoomLabel();

          host.requestAnimationFrame(() => root && root.classList.add('rrv-in'));
          root.querySelector('.rrv-close').focus({ preventScroll: true });
      },
      close,
      next: () => { const i = nextAliveIndex(currentSlide, 1);  if (i >= 0) goToSlide(i); },
      prev: () => { const i = nextAliveIndex(currentSlide, -1); if (i >= 0) goToSlide(i); },
      jump: (i) => goToSlide(i),
      _onMediaError: (idx) => markBroken(idx),
      zoom: (f) => setZoom(rrZoom.scale * f),
      rotate: () => {
          if (isVideoAt(currentSlide)) return;
          rrZoom.rotate = (rrZoom.rotate + 90) % 360;
          applyTransformToActive();
      },
      isVideoSrc,
      // เต็มจอจริงของเบราว์เซอร์ (ซ่อนแถบที่อยู่/แท็บ) — ตัวพรีวิวทับทั้งหน้าอยู่แล้ว
      toggleFs: () => {
          if (!root) return;
          if (!doc.fullscreenElement) {
              root.requestFullscreen?.().then(updateFsIcon).catch(() => {});
          } else {
              doc.exitFullscreen?.().then(updateFsIcon).catch(() => {});
          }
      }
  };
  return api;
})();
