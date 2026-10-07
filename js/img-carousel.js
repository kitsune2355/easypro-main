/**
 * Image Carousel Component (Integrated UI & Logic)
 * ปรับปรุง: ย้าย Toolbar ไปไว้ด้านล่าง
 */

const ImageCarousel = (function () {
  let currentSlide = 0;
  let rrUrls = [];
  let rrToolbarTimer = null;
  let rrKeyHandler = null;

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
          .rr-imgwrap { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; overflow: hidden; touch-action: none; }
          .rr-imgwrap img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform 0.1s ease-out; pointer-events: none; }
          
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
          
          .rr-toolbtn { color: white; padding: 0.5rem; border-radius: 50%; transition: all 0.2s; display: flex; align-items: center; }
          .rr-toolbtn:hover { background: rgba(255,255,255,0.2); transform: scale(1.1); }
          .rr-toolbtn:active { transform: scale(0.95); }
          .rr-toolsep { width: 1px; height: 20px; background: rgba(255,255,255,0.2); margin: auto 4px; }
          .rr-zoomlabel { color: white; font-size: 0.85rem; font-family: sans-serif; font-weight: 500; min-width: 50px; text-align: center; margin: auto 0; }
          
          /* ปรับ Dots ให้อยู่เหนือ Toolbar เล็กน้อย หรือซ่อนถ้าต้องการความสะอาด */
          .rr-dots { position: absolute; bottom: 0; left: 0; right: 0; display: flex; justify-content: center; gap: 0.5rem; z-index: 40; transition: opacity 0.3s; }
          .rr-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.3); cursor: pointer; transition: all 0.3s; }
          .rr-dot.active { background: #fff; transform: scale(1.3); box-shadow: 0 0 10px rgba(255,255,255,0.5); }
          
          .rr-grab { cursor: grab; }
          .rr-grabbing { cursor: grabbing; }
          .rr-hide { opacity: 0; pointer-events: none; }
          :fullscreen .swal-carousel-container { height: 100vh; width: 100vw; border-radius: 0; }
      `;
      document.head.appendChild(style);
  };

  // --- Core Logic ---
  function resetZoomState() { rrZoom = { ...rrZoom, scale: 1, tx: 0, ty: 0, rotate: 0, dragging: false }; }
  function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }
  function getActiveImg() { return document.getElementById('rr-img-' + currentSlide); }
  
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
      rrZoom.scale = clamp(newScale, rrZoom.min, rrZoom.max);
      if (rrZoom.scale <= 1.001) { rrZoom.scale = 1; rrZoom.tx = 0; rrZoom.ty = 0; }
      updateZoomLabel();
      applyTransformToActive();
  }

  function goToSlide(index) {
      if (rrUrls.length <= 0) return;
      const oldSlide = currentSlide;
      currentSlide = (index + rrUrls.length) % rrUrls.length;
      document.getElementById(`slide-${oldSlide}`)?.classList.remove('active');
      document.getElementById(`dot-${oldSlide}`)?.classList.remove('active');
      document.getElementById(`slide-${currentSlide}`)?.classList.add('active');
      document.getElementById(`dot-${currentSlide}`)?.classList.add('active');
      resetZoomState();
      updateZoomLabel();
      applyTransformToActive();
  }

  function attachPanZoomHandlers() {
      const container = document.getElementById('rr-carousel');
      
      container.addEventListener('wheel', (e) => {
          e.preventDefault();
          setZoom(e.deltaY > 0 ? rrZoom.scale / 1.15 : rrZoom.scale * 1.15);
      }, { passive: false });

      let isTouch = false;
      const handleStart = (e) => {
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
          if (rrZoom.scale <= 1 && isTouch) {
              const point = e.changedTouches[0];
              const dx = point.clientX - rrZoom.startX;
              if (Math.abs(dx) > 50) dx < 0 ? goToSlide(currentSlide + 1) : goToSlide(currentSlide - 1);
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
          tb?.classList.add('rr-show');
          dots?.classList.remove('rr-hide');
          if (rrToolbarTimer) clearTimeout(rrToolbarTimer);
          rrToolbarTimer = setTimeout(() => {
              tb?.classList.remove('rr-show');
              dots?.classList.add('rr-hide');
          }, 2000);
      };
      ['mousemove', 'mousedown', 'touchstart', 'keydown'].forEach(ev => container.addEventListener(ev, show));
      show();
  }

  // --- Public API ---
  return {
      open: function (images, title = 'Image Preview') {
          injectStyles();
          if (!images || images.length === 0) return Swal.fire("ไม่พบรูปภาพ", "", "info");
          
          rrUrls = images.filter(src => src);
          currentSlide = 0;
          resetZoomState();

          Swal.fire({
              title: title,
              html: `
              <div class="swal-carousel-container" id="rr-carousel">
                  <div class="rr-toolbar rr-show" id="rr-toolbar">
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.prev()" title="ก่อนหน้า"><i data-lucide="chevron-left"></i></button>
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.next()" title="ถัดไป"><i data-lucide="chevron-right"></i></button>
                      <div class="rr-toolsep"></div>
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.toggleFs()" title="เต็มจอ"><i data-lucide="maximize-2"></i></button>
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.zoom(0.8)" title="ซูมออก"><i data-lucide="minus"></i></button>
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.zoom(1.25)" title="ซูมเข้า"><i data-lucide="plus"></i></button>
                      <button type="button" class="rr-toolbtn" onclick="ImageCarousel.rotate()" title="หมุนภาพ"><i data-lucide="rotate-cw"></i></button>
                      <div class="rr-zoomlabel" id="rr-zoom-label">100%</div>
                  </div>
                  
                  ${rrUrls.map((img, idx) => `
                      <div class="carousel-item ${idx === 0 ? 'active' : ''}" id="slide-${idx}">
                          <div class="rr-imgwrap rr-grab"><img id="rr-img-${idx}" src="${img}"></div>
                      </div>`).join('')}

                  <div class="rr-dots" id="rr-dots">
                      ${rrUrls.map((_, d) => `<div class="rr-dot ${d === 0 ? 'active' : ''}" id="dot-${d}" onclick="ImageCarousel.jump(${d})"></div>`).join('')}
                  </div>
              </div>`,
              showConfirmButton: false, showCloseButton: true, width: '900px',
              customClass: { popup: 'rounded-3xl p-4' },
              didOpen: () => {
                  if (window.lucide) lucide.createIcons();
                  const container = document.getElementById('rr-carousel');
                  attachPanZoomHandlers();
                  bindAutoHide(container);

                  rrKeyHandler = (e) => {
                      if (e.key === 'ArrowRight') goToSlide(currentSlide + 1);
                      if (e.key === 'ArrowLeft') goToSlide(currentSlide - 1);
                      if (e.key === 'r' || e.key === 'R') ImageCarousel.rotate();
                  };
                  document.addEventListener('keydown', rrKeyHandler);
                  document.addEventListener('fullscreenchange', () => {
                    const fsBtn = document.querySelector('.rr-toolbtn[onclick="ImageCarousel.toggleFs()"]');
                    if (!document.fullscreenElement && fsBtn) {
                        fsBtn.innerHTML = '<i data-lucide="maximize-2"></i>';
                        if (window.lucide) lucide.createIcons();
                    }
                });
              },
              willClose: () => {
                  document.removeEventListener('keydown', rrKeyHandler);
                  if (rrToolbarTimer) clearTimeout(rrToolbarTimer);
              }
          });
      },
      next: () => goToSlide(currentSlide + 1),
      prev: () => goToSlide(currentSlide - 1),
      jump: (i) => goToSlide(i),
      zoom: (f) => setZoom(rrZoom.scale * f),
      rotate: () => { rrZoom.rotate = (rrZoom.rotate + 90) % 360; applyTransformToActive(); },
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