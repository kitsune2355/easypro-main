/**
 * Image Carousel Component (Integrated UI & Logic)
 * ✅ รองรับ "วิดีโอ + รูปภาพ" ใน carousel เดียวกัน (วิดีโอมาก่อนเสมอ)
 *
 * ใช้งาน:
 *   ImageCarousel.open(['a.jpg','b.jpg'])                      
 *   ImageCarousel.open(['clip.mp4','a.jpg'])                    // ตรวจจับวิดีโอจากนามสกุลไฟล์
 *   ImageCarousel.open([{type:'video',src:'x'},{type:'image',src:'y'}])
 *   ImageCarousel.open(list, 'หัวข้อ', { videoFirst:false, startIndex:2 })
 */

const ImageCarousel = (function () {
  let currentSlide = 0;
  let rrItems = [];              // [{ type:'video'|'image', src }]
  let rrToolbarTimer = null;
  let rrKeyHandler = null;
  let rrFsBound = false;

  const VIDEO_EXT_RE = /\.(mp4|mov|m4v|webm|ogg|ogv|avi|mkv|3gp|quicktime)(\?.*)?$/i;

  let rrZoom = {
      scale: 1, min: 1, max: 6,
      tx: 0, ty: 0, rotate: 0,
      dragging: false, startX: 0, startY: 0,
      baseTX: 0, baseTY: 0
  };

  const injectStyles = () => {
      if (document.getElementById('rr-carousel-styles')) return;
      const style = document.createElement('style');
      style.id = 'rr-carousel-styles';
      style.innerHTML = `
          .swal-carousel-container { position: relative; width: 100%; height: 500px; background: #000; overflow: hidden; border-radius: 1rem; user-select: none; -webkit-user-select: none; }
          .carousel-item { display: none; width: 100%; height: 100%; }
          .carousel-item.active { display: flex; align-items: center; justify-content: center; }
          /* ✅ สไลด์ที่โหลดไม่ได้ ถูกตัดออกจาก carousel */
          .carousel-item.rr-dead { display: none !important; }
          .rr-imgwrap { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; touch-action: none; }
          .rr-imgwrap img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.1s ease-out; pointer-events: none; }

          /* ✅ สไลด์วิดีโอ: ต้องคลิก/แตะได้ (ไม่ล็อก touch-action) เพื่อให้ controls ทำงาน */
          .rr-vidwrap { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; }
          .rr-vidwrap video { max-width: 100%; max-height: 100%; object-fit: contain; background: #000; }

          /* ปรับ Toolbar ให้อยู่ด้านล่าง */
          .rr-toolbar {
              position: absolute;
              bottom: 1.5rem; /* ระยะห่างจากขอบล่าง */
              left: 50%;
              transform: translateX(-50%) translateY(20px); /* เริ่มต้นจากด้านล่างขึ้นมา */
              z-index: 50;
              display: flex;
              gap: 0.5rem;
              padding: 0.6rem 1.2rem;
              background: rgba(15, 23, 42, 0.8); /* สีเข้มขึ้นเพื่อให้ตัดกับรูป */
              backdrop-filter: blur(12px);
              border-radius: 999px;
              opacity: 0;
              transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
              pointer-events: none;
              border: 1px solid rgba(255,255,255,0.15);
              box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
          }
          .rr-toolbar.rr-show { opacity: 1; transform: translateX(-50%) translateY(0); pointer-events: auto; }

          /* ✅ สไลด์วิดีโอ: toolbar ยังอยู่ "ด้านล่าง" เหมือนสไลด์รูป
             แค่ยกสูงขึ้นให้พ้นแถบ controls ของวิดีโอ + ซ่อนปุ่มซูม/หมุน */
          .rr-toolbar.rr-video { bottom: 4.5rem; }
          .rr-toolbar.rr-video .rr-imgonly { display: none; }

          .rr-toolbtn { color: white; padding: 0.5rem; border-radius: 50%; transition: all 0.2s; display: flex; align-items: center; }
          .rr-toolbtn:hover { background: rgba(255,255,255,0.2); transform: scale(1.1); }
          .rr-toolbtn:active { transform: scale(0.95); }
          .rr-toolsep { width: 1px; height: 20px; background: rgba(255,255,255,0.2); margin: auto 4px; }
          .rr-zoomlabel { color: white; font-size: 0.85rem; font-family: sans-serif; font-weight: 500; min-width: 50px; text-align: center; margin: auto 0; }

          /* ป้ายบอกชนิด + ลำดับสื่อ */
          .rr-kind { position: absolute; top: 1rem; left: 1rem; z-index: 45; display: flex; align-items: center; gap: 6px;
              color: #fff; font-size: 0.75rem; font-weight: 700; font-family: sans-serif; letter-spacing: .02em;
              background: rgba(15,23,42,0.75); border: 1px solid rgba(255,255,255,0.18); backdrop-filter: blur(8px);
              padding: 5px 12px; border-radius: 999px; transition: opacity 0.3s; }

          /* ปรับ Dots ให้อยู่เหนือ Toolbar เล็กน้อย หรือด้านข้างเพื่อความสะอาด */
          .rr-dots { position: absolute; bottom: 0; left: 0; right: 0; display: flex; justify-content: center; gap: 0.5rem; z-index: 40; transition: opacity 0.3s; }
          .rr-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.3); cursor: pointer; transition: all 0.3s; }
          .rr-dot.active { background: #fff; transform: scale(1.3); box-shadow: 0 0 10px rgba(255,255,255,0.5); }
          .rr-dot.rr-dot-video { border-radius: 3px; width: 10px; height: 8px; background: rgba(56,189,248,0.55); }
          .rr-dot.rr-dot-video.active { background: #38bdf8; }
          /* ✅ สไลด์วิดีโอ: ยก dots ขึ้นเหนือแถบ controls จะได้ไม่ไปกดโดน seek bar */
          .swal-carousel-container.rr-video-slide .rr-dots { bottom: 3rem; }

          .rr-grab { cursor: grab; }
          .rr-grabbing { cursor: grabbing; }
          .rr-hide { opacity: 0; pointer-events: none; }
          :fullscreen .swal-carousel-container { height: 100vh; width: 100vw; border-radius: 0; }
      `;
      document.head.appendChild(style);
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

  // รับได้ทั้ง array ของ string และ array ของ {type, src}
  function normalizeItems(list, videoFirst) {
      const arr = (Array.isArray(list) ? list : [list])
          .map(it => ({ raw: it, src: pickUrl(it) }))
          .filter(it => it.src && !/^\[object/i.test(it.src))
          .map(it => ({
              src: it.src,
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

  const isVideoAt = (i) => rrItems[i]?.type === 'video';
  const hasVideo  = () => rrItems.some(it => it.type === 'video');

  function pauseAllVideos() {
      document.querySelectorAll('#rr-carousel video').forEach(v => { try { v.pause(); } catch (e) {} });
  }

  // --- Core Logic ---
  function resetZoomState() { rrZoom = { ...rrZoom, scale: 1, tx: 0, ty: 0, rotate: 0, dragging: false }; }
  function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }
  function getActiveImg() { return isVideoAt(currentSlide) ? null : document.getElementById('rr-img-' + currentSlide); }

  function updateZoomLabel() {
      const lb = document.getElementById('rr-zoom-label');
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
      const onVideo = isVideoAt(currentSlide);

      const tb = document.getElementById('rr-toolbar');
      tb?.classList.toggle('rr-video', onVideo);

      document.getElementById('rr-carousel')?.classList.toggle('rr-video-slide', onVideo);

      const kind = document.getElementById('rr-kind');
      if (kind) {
          // ✅ นับเฉพาะสื่อที่โหลดได้จริง (ตัวที่พังถูกตัดทิ้งไปแล้ว)
          const alive = rrItems.filter(it => !it.broken);
          const pos   = alive.indexOf(rrItems[currentSlide]) + 1;
          kind.innerHTML = `<span>${onVideo ? 'วิดีโอ' : 'รูปภาพ'}</span>` +
                           `<span style="opacity:.7">${pos} / ${alive.length}</span>`;
      }
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
      if (!item || item.broken) return;
      item.broken = true;

      document.getElementById(`slide-${idx}`)?.classList.add('rr-dead');
      document.getElementById(`dot-${idx}`)?.remove();

      const alive = rrItems.filter(it => !it.broken);

      if (!alive.length) {
          const stage = document.getElementById('rr-carousel');
          if (stage) stage.innerHTML = `<div style="color:#94a3b8;font-family:sans-serif;font-size:.9rem;
              display:flex;align-items:center;justify-content:center;height:100%;">ไม่สามารถโหลดไฟล์สื่อได้</div>`;
          return;
      }

      const dots = document.getElementById('rr-dots');
      if (dots) dots.style.display = alive.length > 1 ? 'flex' : 'none';

      if (idx === currentSlide) {
          const to = nextAliveIndex(idx, 1);
          if (to >= 0) goToSlide(to);
      } else {
          syncMediaUI();
      }
  }

  function goToSlide(index) {
      if (rrItems.length <= 0) return;
      const oldSlide = currentSlide;
      let target = ((index % rrItems.length) + rrItems.length) % rrItems.length;
      if (rrItems[target].broken) {
          target = nextAliveIndex(target, 1);
          if (target < 0) return;
      }
      currentSlide = target;

      pauseAllVideos();   // ✅ ออกจากสไลด์ไหน ก็หยุดวิดีโอสไลด์นั้น

      document.getElementById(`slide-${oldSlide}`)?.classList.remove('active');
      document.getElementById(`dot-${oldSlide}`)?.classList.remove('active');
      document.getElementById(`slide-${currentSlide}`)?.classList.add('active');
      document.getElementById(`dot-${currentSlide}`)?.classList.add('active');

      resetZoomState();
      updateZoomLabel();
      applyTransformToActive();
      syncMediaUI();
  }

  function attachPanZoomHandlers() {
      const container = document.getElementById('rr-carousel');

      container.addEventListener('wheel', (e) => {
          if (isVideoAt(currentSlide)) return;   // ปล่อยให้วิดีโอทำงานตามปกติ
          e.preventDefault();
          setZoom(e.deltaY > 0 ? rrZoom.scale / 1.15 : rrZoom.scale * 1.15);
      }, { passive: false });

      let isTouch = false;
      const handleStart = (e) => {
          if (isVideoAt(currentSlide)) return;   // ✅ อย่าไปขวาง controls ของวิดีโอ
          if (e.cancelable) e.preventDefault();
          if (rrZoom.scale <= 1 && !e.touches) return;
          rrZoom.dragging = true;
          const point = e.touches ? e.touches[0] : e;
          rrZoom.startX = point.clientX; rrZoom.startY = point.clientY;
          rrZoom.baseTX = rrZoom.tx; rrZoom.baseTY = rrZoom.ty;
          if(!e.touches) document.querySelector('.carousel-item.active .rr-imgwrap')?.classList.replace('rr-grab', 'rr-grabbing');
      };

      const handleMove = (e) => {
          if (!rrZoom.dragging) return;
          const point = e.touches ? e.touches[0] : e;
          if (rrZoom.scale > 1) {
              rrZoom.tx = rrZoom.baseTX + (point.clientX - rrZoom.startX);
              rrZoom.ty = rrZoom.baseTY + (point.clientY - rrZoom.startY);
              applyTransformToActive();
          }
      };

      const handleEnd = (e) => {
          if (isVideoAt(currentSlide)) { rrZoom.dragging = false; return; }
          if (rrZoom.scale <= 1 && isTouch && e.changedTouches?.length) {
              const point = e.changedTouches[0];
              const dx = point.clientX - rrZoom.startX;
              if (Math.abs(dx) > 50) dx < 0 ? ImageCarousel.next() : ImageCarousel.prev();
          }
          rrZoom.dragging = false;
          document.querySelectorAll('.rr-imgwrap').forEach(w => w.classList.replace('rr-grabbing', 'rr-grab'));
      };

      container.addEventListener('mousedown', (e) => { isTouch = false; handleStart(e); });
      container.addEventListener('touchstart', (e) => { isTouch = true; handleStart(e); }, { passive: true });
      window.addEventListener('mousemove', handleMove);
      window.addEventListener('touchmove', handleMove, { passive: true });
      window.addEventListener('mouseup', handleEnd);
      window.addEventListener('touchend', handleEnd);
  }

  function bindAutoHide(container) {
      const show = () => {
          const tb = document.getElementById('rr-toolbar');
          const dots = document.getElementById('rr-dots');
          const kind = document.getElementById('rr-kind');
          tb?.classList.add('rr-show');
          dots?.classList.remove('rr-hide');
          kind?.classList.remove('rr-hide');
          if (rrToolbarTimer) clearTimeout(rrToolbarTimer);
          rrToolbarTimer = setTimeout(() => {
              tb?.classList.remove('rr-show');
              dots?.classList.add('rr-hide');
              kind?.classList.add('rr-hide');
          }, 2000);
      };
      ['mousemove', 'mousedown', 'touchstart', 'keydown'].forEach(ev => container.addEventListener(ev, show));
      show();
  }

  function slideHtml(item, idx) {
      // onerror → ตัดสไลด์ที่โหลดไม่ได้ทิ้ง (กันวิดีโอ/รูป "เปล่า ๆ" โผล่มา)
      if (item.type === 'video') {
          return `
              <div class="carousel-item ${idx === 0 ? 'active' : ''}" id="slide-${idx}">
                  <div class="rr-vidwrap">
                      <video id="rr-vid-${idx}" src="${item.src}" controls playsinline preload="metadata"
                             controlsList="nodownload"
                             onerror="ImageCarousel._onMediaError(${idx})"></video>
                  </div>
              </div>`;
      }
      return `
          <div class="carousel-item ${idx === 0 ? 'active' : ''}" id="slide-${idx}">
              <div class="rr-imgwrap rr-grab">
                  <img id="rr-img-${idx}" src="${item.src}" onerror="ImageCarousel._onMediaError(${idx})">
              </div>
          </div>`;
  }

  // --- Public API ---
  return {
      /**
       * @param {Array<string|{type:string,src:string}>} media
       * @param {string} [title]
       * @param {{videoFirst?:boolean, startIndex?:number}} [opts]
       */
      open: function (media, title, opts) {
          injectStyles();

          const o = opts || {};
          rrItems = normalizeItems(media, o.videoFirst);
          if (!rrItems.length) return Swal.fire("ไม่มีไฟล์สื่อ", "", "info");

          currentSlide = clamp(Number(o.startIndex) || 0, 0, rrItems.length - 1);
          resetZoomState();

          const heading = title || (hasVideo() ? 'Media Preview' : 'Image Preview');

          Swal.fire({
              title: heading,
              html: `
              <div class="swal-carousel-container" id="rr-carousel">
                  <div class="rr-kind" id="rr-kind"></div>

                  <div class="rr-toolbar rr-show" id="rr-toolbar">
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.prev()" title="ก่อนหน้า"><i data-lucide="chevron-left"></i></button>
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.next()" title="ถัดไป"><i data-lucide="chevron-right"></i></button>
                      <div class="rr-toolsep rr-imgonly"></div>
                      <button type="button" class="rr-toolbtn rr-imgonly" onclick="ImageCarousel.toggleFs()" title="เต็มจอ"><i data-lucide="maximize-2"></i></button>
                      <button type="button" class="rr-toolbtn rr-imgonly" onclick="ImageCarousel.zoom(0.8)" title="ซูมออก"><i data-lucide="minus"></i></button>
                      <button type="button" class="rr-toolbtn rr-imgonly" onclick="ImageCarousel.zoom(1.25)" title="ซูมเข้า"><i data-lucide="plus"></i></button>
                      <button type="button" class="rr-toolbtn rr-imgonly" onclick="ImageCarousel.rotate()" title="หมุนภาพ"><i data-lucide="rotate-cw"></i></button>
                      <div class="rr-zoomlabel rr-imgonly" id="rr-zoom-label">100%</div>
                  </div>

                  ${rrItems.map((item, idx) => slideHtml(item, idx)).join('')}

                  <div class="rr-dots" id="rr-dots">
                      ${rrItems.map((item, d) => `<div class="rr-dot ${item.type === 'video' ? 'rr-dot-video' : ''} ${d === 0 ? 'active' : ''}" id="dot-${d}" onclick="ImageCarousel.jump(${d})"></div>`).join('')}
                  </div>
              </div>`,
              showConfirmButton: false, showCloseButton: true, width: '900px',
              customClass: { popup: 'rounded-3xl p-4' },
              didOpen: () => {
                  if (window.lucide) lucide.createIcons();
                  const container = document.getElementById('rr-carousel');
                  attachPanZoomHandlers();
                  bindAutoHide(container);

                  // เปิดที่สไลด์เริ่มต้น (เผื่อ startIndex ไม่ใช่ 0)
                  if (currentSlide !== 0) {
                      const target = currentSlide;
                      currentSlide = 0;
                      goToSlide(target);
                  } else {
                      syncMediaUI();
                  }

                  rrKeyHandler = (e) => {
                      if (e.key === 'ArrowRight') ImageCarousel.next();
                      if (e.key === 'ArrowLeft')  ImageCarousel.prev();
                      if ((e.key === 'r' || e.key === 'R') && !isVideoAt(currentSlide)) ImageCarousel.rotate();
                  };
                  document.addEventListener('keydown', rrKeyHandler);

                  if (!rrFsBound) {
                      rrFsBound = true;
                      document.addEventListener('fullscreenchange', () => {
                          const fsBtn = document.querySelector('.rr-toolbtn[onclick="ImageCarousel.toggleFs()"]');
                          if (!document.fullscreenElement && fsBtn) {
                              fsBtn.innerHTML = '<i data-lucide="maximize-2"></i>';
                              if (window.lucide) lucide.createIcons();
                          }
                      });
                  }
              },
              willClose: () => {
                  pauseAllVideos();   // ✅ ปิด popup แล้วต้องไม่มีเสียงค้าง
                  document.removeEventListener('keydown', rrKeyHandler);
                  if (rrToolbarTimer) clearTimeout(rrToolbarTimer);
              }
          });
      },
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
      toggleFs: () => {
        const el = document.getElementById('rr-carousel');
        const fsBtn = document.querySelector('.rr-toolbtn[onclick="ImageCarousel.toggleFs()"]');

        if (!document.fullscreenElement) {
            if (el.requestFullscreen) {
                el.requestFullscreen().then(() => {
                    if (fsBtn) fsBtn.innerHTML = '<i data-lucide="minimize-2"></i>';
                    if (window.lucide) lucide.createIcons();
                });
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
                if (fsBtn) fsBtn.innerHTML = '<i data-lucide="maximize-2"></i>';
                if (window.lucide) lucide.createIcons();
            }
        }
    }
  };
})();
