<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Command Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .animate-slide-up {
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Custom Range Slider */
        input[type=range] {
            -webkit-appearance: none;
            background: transparent;
        }
        input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 48px;
            width: 48px;
            background: transparent;
            cursor: pointer;
        }
        
        .grid-bg {
            background-image: radial-gradient(circle at 1px 1px, rgba(51, 65, 85, 0.3) 1px, transparent 0);
            background-size: 40px 40px;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 selection:bg-blue-500 selection:text-white min-h-screen overflow-x-hidden">
    <div class="fixed inset-0 z-0 pointer-events-none grid-bg"></div>

    <main id="app" class="relative z-10 max-w-6xl mx-auto p-6">
        <!-- Content will be injected here by JavaScript -->
    </main>

    <script>
        // --- State Management ---
        let cases = [
            { id: 'JOB-8842', requester: 'คุณสมชาย (IT)', location: 'Server Room B', issue: 'Overheating Alert', status: 'pending', priority: 'high', created_at: '2024-05-20 09:30' },
            { id: 'JOB-8843', requester: 'คุณวิภา (HR)', location: 'Meeting Room 2', issue: 'Projector Signal Loss', status: 'scheduled', priority: 'medium', created_at: '2024-05-20 10:15', scheduleStart: '2024-05-21T10:00', scheduleEnd: '2024-05-21T11:30' },
            { id: 'JOB-8844', requester: 'คุณก้อง (MKT)', location: 'Pantry Floor 5', issue: 'Water Leakage', status: 'completed', priority: 'low', created_at: '2024-05-19 14:00', scheduleStart: '2024-05-20T13:00', scheduleEnd: '2024-05-20T14:00', closedAt: '2024-05-20T14:15' },
        ];

        let currentView = 'dashboard';
        let selectedCase = null;
        let scheduleDate = new Date().toISOString().split('T')[0];
        let duration = 60;
        let startTimePercent = 30;
        let closeStep = 1;
        let closeData = { diagnosis: '', action: '', parts: [] };

        const WORK_START = 8;
        const WORK_END = 18;

        // --- Core Functions ---
        function render() {
            const app = document.getElementById('app');
            app.innerHTML = '';

            if (currentView === 'dashboard') {
                app.appendChild(createDashboard());
            } else if (currentView === 'smart-schedule') {
                app.appendChild(createSmartSchedule());
            } else if (currentView === 'pro-close') {
                app.appendChild(createProClose());
            }

            lucide.createIcons();
        }

        function setView(view, caseData = null) {
            currentView = view;
            selectedCase = caseData;
            if (view === 'pro-close') closeStep = 1;
            render();
        }

        // --- Dashboard View ---
        function createDashboard() {
            const container = document.createElement('div');
            container.className = 'space-y-8 animate-fade-in text-slate-100';

            const header = `
                <div class="flex flex-col md:flex-row justify-between items-center bg-slate-800/50 backdrop-blur-md p-6 rounded-2xl border border-slate-700/50 shadow-xl gap-4">
                    <div>
                        <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-cyan-300">COMMAND CENTER</h1>
                        <p class="text-slate-400 text-sm mt-1 flex items-center">
                            <i data-lucide="activity" class="w-4 h-4 mr-1 text-green-400"></i> System Operational • ${cases.length} Active Jobs
                        </p>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div class="text-right">
                             <div class="text-xs text-slate-400">TODAY'S EFFICIENCY</div>
                             <div class="text-2xl font-mono font-bold text-green-400">94%</div>
                        </div>
                        <button class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-blue-500/20 transition-all flex items-center">
                            <i data-lucide="search" class="w-4 h-4 mr-2"></i> Find Job
                        </button>
                    </div>
                </div>
            `;

            const grid = document.createElement('div');
            grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';

            cases.forEach(c => {
                const card = document.createElement('div');
                card.className = 'group relative bg-slate-800/40 border border-slate-700 hover:border-blue-500/50 rounded-xl p-6 transition-all duration-300 hover:shadow-2xl hover:shadow-blue-900/10 hover:-translate-y-1 overflow-hidden';
                
                const priorityClass = c.priority === 'high' ? 'bg-red-500/10 text-red-400 border-red-500/20' : 
                                    c.priority === 'medium' ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' : 
                                    'bg-blue-500/10 text-blue-400 border-blue-500/20';

                const progress = c.status === 'completed' ? '100%' : c.status === 'scheduled' ? '50%' : '10%';
                const progressColor = c.status === 'completed' ? 'bg-green-500' : c.status === 'scheduled' ? 'bg-blue-500' : 'bg-slate-500';

                card.innerHTML = `
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl -mr-16 -mt-16 transition-opacity opacity-0 group-hover:opacity-100"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase border ${priorityClass}">
                            ${c.priority} Priority
                        </div>
                        <span class="font-mono text-slate-500 text-xs">${c.id}</span>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-200 mb-2 truncate">${c.issue}</h3>
                    <div class="flex items-center text-slate-400 text-sm mb-6">
                        <i data-lucide="map-pin" class="w-3 h-3 mr-1"></i> ${c.location}
                    </div>
                    <div class="mb-6">
                        <div class="flex justify-between text-xs text-slate-500 mb-1">
                            <span>Progress</span>
                            <span>${progress}</span>
                        </div>
                        <div class="h-1.5 w-full bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 ${progressColor}" style="width: ${progress}"></div>
                        </div>
                    </div>
                    <div class="relative z-10 action-area"></div>
                `;

                const actionArea = card.querySelector('.action-area');
                if (c.status === 'pending') {
                    const btn = document.createElement('button');
                    btn.className = 'w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 rounded-lg text-white font-medium text-sm flex items-center justify-center transition-all shadow-lg shadow-blue-900/20';
                    btn.innerHTML = '<i data-lucide="clock" class="w-4 h-4 mr-2"></i> Initialize Schedule';
                    btn.onclick = () => setView('smart-schedule', c);
                    actionArea.appendChild(btn);
                } else if (c.status === 'scheduled') {
                    const btn = document.createElement('button');
                    btn.className = 'w-full py-3 bg-slate-700 hover:bg-slate-600 border border-slate-600 hover:border-green-500/50 text-green-400 rounded-lg font-medium text-sm flex items-center justify-center transition-all';
                    btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 mr-2"></i> Complete Mission';
                    btn.onclick = () => setView('pro-close', c);
                    actionArea.appendChild(btn);
                } else {
                    actionArea.innerHTML = '<div class="text-center py-2 text-slate-500 text-sm font-mono flex items-center justify-center opacity-75"><i data-lucide="check-circle" class="w-3 h-3 mr-1"></i> ARCHIVED</div>';
                }

                grid.appendChild(card);
            });

            container.innerHTML = header;
            container.appendChild(grid);
            return container;
        }

        // --- Smart Schedule View ---
        function createSmartSchedule() {
            const container = document.createElement('div');
            container.className = 'max-w-4xl mx-auto animate-slide-up';

            const totalWorkMins = (WORK_END - WORK_START) * 60;
            const bookedBlocks = cases
                .filter(c => c.status === 'scheduled' && c.id !== selectedCase.id)
                .map(c => {
                    const start = new Date(c.scheduleStart);
                    const end = new Date(c.scheduleEnd);
                    const startMins = (start.getHours() * 60 + start.getMinutes()) - (WORK_START * 60);
                    const durationMins = (end.getTime() - start.getTime()) / 60000;
                    return { left: (startMins / totalWorkMins) * 100, width: (durationMins / totalWorkMins) * 100 };
                });

            const myStartPct = startTimePercent;
            const myEndPct = startTimePercent + ((duration / totalWorkMins) * 100);
            const conflict = bookedBlocks.some(b => (myStartPct < (b.left + b.width) && myEndPct > b.left));

            const formatTime = (pct) => {
                const mins = (pct / 100) * totalWorkMins;
                const h = Math.floor(mins / 60) + WORK_START;
                const m = Math.floor(mins % 60);
                return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
            };

            container.innerHTML = `
                <button id="back-btn" class="mb-6 flex items-center text-slate-400 hover:text-white transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back to Command Center
                </button>
                <div class="bg-slate-800 rounded-2xl border border-slate-700 shadow-2xl overflow-hidden">
                    <div class="p-8 border-b border-slate-700 bg-gradient-to-r from-slate-800 to-slate-900 flex flex-col md:flex-row justify-between items-start gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2 flex items-center">
                                <i data-lucide="zap" class="w-6 h-6 text-yellow-400 mr-2"></i> Tactical Scheduling
                            </h2>
                            <p class="text-slate-400">Drag the slider to find an optimal slot.</p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-slate-500 uppercase tracking-wider mb-1">Duration</div>
                            <div class="flex bg-slate-900 rounded-lg p-1 border border-slate-700 duration-btns"></div>
                        </div>
                    </div>
                    <div class="p-10 bg-slate-900/50 relative select-none">
                        <div class="mb-8 text-center">
                            <span class="text-slate-400 mr-2">Target Date:</span>
                            <input type="date" value="${scheduleDate}" class="bg-transparent text-white font-mono font-bold text-lg border-b border-slate-600 focus:border-blue-500 outline-none date-input" />
                        </div>
                        <div class="relative h-32 w-full bg-slate-800 rounded-xl border border-slate-700 flex items-center px-4 overflow-hidden">
                            <div class="absolute inset-0 flex justify-between px-4 opacity-20 pointer-events-none grid-lines"></div>
                            <div class="booked-areas"></div>
                            <div class="relative w-full h-12">
                                <input type="range" min="0" max="${100 - ((duration / totalWorkMins) * 100)}" value="${startTimePercent}" class="absolute w-full opacity-0 z-20 cursor-grab active:cursor-grabbing h-full time-slider" />
                                <div class="absolute h-16 -top-2 rounded-lg border-2 backdrop-blur-sm transition-colors flex items-center justify-center shadow-2xl z-10 pointer-events-none selection-box" style="left: ${startTimePercent}%; width: ${(duration / totalWorkMins) * 100}%">
                                    <div class="text-center leading-tight">
                                        <div class="font-bold text-sm">${formatTime(startTimePercent)}</div>
                                        <div class="text-[10px] opacity-75">${formatTime(myEndPct)}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-center status-feedback"></div>
                    </div>
                    <div class="p-6 border-t border-slate-700 bg-slate-800 flex justify-end">
                        <button id="confirm-sched" class="bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-blue-500/30 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105">
                            CONFIRM DEPLOYMENT
                        </button>
                    </div>
                </div>
            `;

            // Setup listeners and dynamic elements
            container.querySelector('#back-btn').onclick = () => setView('dashboard');
            
            const durContainer = container.querySelector('.duration-btns');
            [30, 60, 90, 120].forEach(m => {
                const b = document.createElement('button');
                b.className = `px-3 py-1 rounded text-sm transition-all ${duration === m ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-slate-200'}`;
                b.innerText = `${m}m`;
                b.onclick = () => { duration = m; render(); };
                durContainer.appendChild(b);
            });

            const gridLines = container.querySelector('.grid-lines');
            for(let i=0; i<=(WORK_END-WORK_START); i++) {
                gridLines.innerHTML += `<div class="h-full border-r border-slate-400 flex flex-col justify-end pb-2"><span class="text-[10px] transform -translate-x-1/2">${WORK_START+i}:00</span></div>`;
            }

            const bookedAreas = container.querySelector('.booked-areas');
            bookedBlocks.forEach(b => {
                bookedAreas.innerHTML += `<div class="absolute h-16 bg-red-500/20 border border-red-500/50 rounded pointer-events-none flex items-center justify-center" style="left: ${b.left}%; width: ${b.width}%"><span class="text-[10px] text-red-400 font-bold">BUSY</span></div>`;
            });

            const selBox = container.querySelector('.selection-box');
            if (conflict) {
                selBox.classList.add('bg-red-600/30', 'border-red-500', 'text-red-200');
                container.querySelector('.status-feedback').innerHTML = `<div class="flex items-center text-red-400 animate-pulse bg-red-900/20 px-4 py-2 rounded-full border border-red-900/50"><i data-lucide="alert-triangle" class="w-4 h-4 mr-2"></i>Conflict Detected</div>`;
                container.querySelector('#confirm-sched').disabled = true;
            } else {
                selBox.classList.add('bg-blue-500/30', 'border-blue-400', 'text-blue-100');
                container.querySelector('.status-feedback').innerHTML = `<div class="flex items-center text-green-400 bg-green-900/20 px-4 py-2 rounded-full border border-green-900/50"><i data-lucide="check-circle" class="w-4 h-4 mr-2"></i>Slot Available</div>`;
            }

            container.querySelector('.time-slider').oninput = (e) => {
                startTimePercent = Number(e.target.value);
                render();
            };

            container.querySelector('.date-input').onchange = (e) => scheduleDate = e.target.value;

            container.querySelector('#confirm-sched').onclick = () => {
                const totalMinutes = (WORK_END - WORK_START) * 60;
                const startMinuteFromOpening = (startTimePercent / 100) * totalMinutes;
                const hour = Math.floor(startMinuteFromOpening / 60) + WORK_START;
                const minute = Math.floor(startMinuteFromOpening % 60);
                const startDateTime = new Date(scheduleDate);
                startDateTime.setHours(hour, minute, 0, 0);
                const endDateTime = new Date(startDateTime.getTime() + duration * 60000);

                cases = cases.map(c => c.id === selectedCase.id ? { ...c, status: 'scheduled', scheduleStart: startDateTime.toISOString(), scheduleEnd: endDateTime.toISOString() } : c);
                setView('dashboard');
            };

            return container;
        }

        // --- Pro Close View ---
        function createProClose() {
            const container = document.createElement('div');
            container.className = 'max-w-2xl mx-auto animate-slide-up text-slate-200';
            
            let stepContent = '';
            if (closeStep === 1) {
                stepContent = `
                    <div class="animate-fade-in">
                        <h3 class="text-xl font-semibold mb-4 text-blue-300">Phase 1: Diagnosis</h3>
                        <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700 mb-4">
                            <label class="block text-sm text-slate-400 mb-2">Root Cause Analysis</label>
                            <textarea id="diag-input" class="w-full bg-transparent text-white focus:outline-none h-24 resize-none placeholder-slate-600" placeholder="Identify the core malfunction...">${closeData.diagnosis}</textarea>
                        </div>
                        <button onclick="closeStep=2; render();" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold mt-4 hover:bg-blue-500">Next Phase</button>
                    </div>
                `;
            } else if (closeStep === 2) {
                stepContent = `
                    <div class="animate-fade-in">
                        <h3 class="text-xl font-semibold mb-4 text-purple-300">Phase 2: Execution</h3>
                        <div class="bg-slate-900/50 p-4 rounded-xl border border-slate-700 mb-4">
                            <label class="block text-sm text-slate-400 mb-2">Actions Performed</label>
                            <textarea id="act-input" class="w-full bg-transparent text-white focus:outline-none h-24 resize-none placeholder-slate-600" placeholder="Detail your repair procedure...">${closeData.action}</textarea>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button onclick="closeStep=1; render();" class="flex-1 bg-slate-700 text-white py-3 rounded-xl">Back</button>
                            <button onclick="closeStep=3; render();" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-500">Next Phase</button>
                        </div>
                    </div>
                `;
            } else {
                stepContent = `
                    <div class="animate-fade-in">
                        <h3 class="text-xl font-semibold mb-4 text-green-300">Phase 3: Verification</h3>
                        <div class="flex items-center justify-between bg-gradient-to-r from-green-900/30 to-slate-900 p-4 rounded-xl border border-green-500/30 mb-6">
                            <div><div class="text-xs text-green-400 uppercase tracking-wider">Performance Metrics</div><div class="font-bold text-lg">Within SLA (Top 15%)</div></div>
                            <div class="h-10 w-10 rounded-full border-4 border-green-500 flex items-center justify-center text-xs font-bold text-green-400">A+</div>
                        </div>
                        <button id="final-confirm" class="w-full bg-green-600 hover:bg-green-500 text-white py-4 rounded-xl font-bold shadow-lg shadow-green-900/20 flex items-center justify-center transform hover:scale-[1.02] transition-all">
                            <i data-lucide="save" class="w-5 h-5 mr-2"></i> COMPLETE OPERATION
                        </button>
                    </div>
                `;
            }

            container.innerHTML = `
                <button onclick="setView('dashboard')" class="mb-6 flex items-center text-slate-400 hover:text-white transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Abort Mission
                </button>
                <div class="bg-slate-800 rounded-2xl border border-slate-700 shadow-2xl overflow-hidden relative">
                    <div class="absolute top-0 left-0 h-1 bg-slate-700 w-full">
                        <div class="h-full bg-green-500 transition-all duration-500" style="width: ${(closeStep/3)*100}%"></div>
                    </div>
                    <div class="p-8">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-white">Mission Debrief</h2>
                            <div class="flex justify-center space-x-2 mt-2">
                                <div class="h-2 w-8 rounded-full ${closeStep>=1?'bg-green-500':'bg-slate-600'}"></div>
                                <div class="h-2 w-8 rounded-full ${closeStep>=2?'bg-green-500':'bg-slate-600'}"></div>
                                <div class="h-2 w-8 rounded-full ${closeStep>=3?'bg-green-500':'bg-slate-600'}"></div>
                            </div>
                        </div>
                        ${stepContent}
                    </div>
                </div>
            `;

            const diagInput = container.querySelector('#diag-input');
            if(diagInput) diagInput.oninput = (e) => closeData.diagnosis = e.target.value;
            
            const actInput = container.querySelector('#act-input');
            if(actInput) actInput.oninput = (e) => closeData.action = e.target.value;

            const finalBtn = container.querySelector('#final-confirm');
            if(finalBtn) finalBtn.onclick = () => {
                cases = cases.map(c => c.id === selectedCase.id ? { ...c, status: 'completed', closedAt: new Date().toISOString() } : c);
                setView('dashboard');
            };

            return container;
        }

        // Initial Render
        render();
    </script>
</body>
</html>