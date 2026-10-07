<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tactical Maintenance HUD - Pipeline View</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');
        
        body {
            font-family: 'JetBrains Mono', monospace;
            background-color: #020617;
            color: #e2e8f0;
            overflow: hidden;
        }

        .tactical-grid {
            background-image: 
                linear-gradient(to right, rgba(30, 41, 59, 0.3) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(30, 41, 59, 0.3) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Status Animations */
        @keyframes blink {
            50% { opacity: 0.3; }
        }
        .status-urgent {
            animation: blink 1s infinite;
        }

        .pipeline-track {
            position: relative;
            background: rgba(15, 23, 42, 0.6);
            border-left: 2px solid #1e293b;
        }

        .step-node {
            position: relative;
            transition: all 0.3s ease;
        }

        .step-node::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 50%;
            width: 16px;
            height: 16px;
            background: #020617;
            border: 2px solid #3b82f6;
            border-radius: 50%;
            transform: translateY(-50%);
        }

        .step-node.active::before {
            background: #3b82f6;
            box-shadow: 0 0 15px #3b82f6;
        }

        .step-node.urgent::before {
            border-color: #ef4444;
            background: #ef4444;
            box-shadow: 0 0 15px #ef4444;
        }
    </style>
</head>
<body class="p-6 tactical-grid">

    <!-- Top Status Bar: Fast Decisions -->
    <header class="flex justify-between items-center mb-8 bg-slate-900/50 p-4 border border-slate-800 rounded-lg">
        <div class="flex gap-10">
            <div>
                <div class="text-[10px] text-slate-500 uppercase tracking-widest">Active Incidents</div>
                <div class="text-2xl font-bold text-red-500">03 <span class="text-xs text-slate-400">Critical</span></div>
            </div>
            <div class="border-l border-slate-800 pl-10">
                <div class="text-[10px] text-slate-500 uppercase tracking-widest">Available Staff</div>
                <div class="text-2xl font-bold text-green-400">05 <span class="text-xs text-slate-400">Online</span></div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-blue-500 font-bold italic tracking-tighter">OPERATIONAL COMMAND</div>
            <div id="clock" class="text-sm font-mono text-slate-400">12:45:01</div>
        </div>
    </header>

    <div class="grid grid-cols-12 gap-6 h-[70vh]">
        
        <!-- Left: Incident Pipeline (What needs to be done NOW) -->
        <main class="col-span-8 space-y-4">
            <h2 class="text-xs font-bold text-slate-500 mb-4 uppercase flex items-center gap-2">
                <i data-lucide="activity" class="w-4 h-4"></i> Live Incident Pipeline
            </h2>

            <div class="pipeline-track h-full space-y-6 pl-8 py-4 overflow-y-auto">
                
                <!-- Action Required Item -->
                <div class="step-node urgent active p-4 bg-red-500/10 border border-red-500/30 rounded-r-lg flex justify-between items-center group hover:bg-red-500/20 cursor-pointer">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="px-2 py-0.5 bg-red-500 text-[10px] font-bold text-white rounded status-urgent">IMMEDIATE ACTION</span>
                            <span class="text-xs text-slate-400">#INC-9902</span>
                        </div>
                        <h3 class="text-lg font-bold">Chiller System: Pump Failure</h3>
                        <p class="text-xs text-slate-400">Location: Floor 4, Zone C (Data Center)</p>
                    </div>
                    <div class="text-right flex items-center gap-6">
                        <div>
                            <div class="text-[10px] text-slate-500 uppercase">SLA Breach In</div>
                            <div class="text-xl font-bold text-red-500 font-mono">04:22</div>
                        </div>
                        <button class="bg-red-600 hover:bg-red-500 px-6 py-2 rounded text-xs font-bold shadow-lg shadow-red-900/20">DEPLOY NOW</button>
                    </div>
                </div>

                <!-- Pending Item -->
                <div class="step-node active p-4 bg-blue-500/5 border border-slate-800 rounded-r-lg flex justify-between items-center opacity-80 hover:opacity-100 transition-all cursor-pointer">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="px-2 py-0.5 bg-blue-500/20 text-[10px] font-bold text-blue-400 rounded">ASSIGNED</span>
                            <span class="text-xs text-slate-400">#INC-9854</span>
                        </div>
                        <h3 class="text-lg font-bold">Elevator #3: Unusual Noise</h3>
                        <p class="text-xs text-slate-400">Location: Main Lobby</p>
                    </div>
                    <div class="text-right flex items-center gap-6">
                        <div class="flex -space-x-2">
                            <div class="w-8 h-8 rounded-full bg-slate-700 border-2 border-slate-900 flex items-center justify-center text-[10px]">ช</div>
                            <div class="w-8 h-8 rounded-full bg-blue-600 border-2 border-slate-900 flex items-center justify-center text-[10px]">W</div>
                        </div>
                        <button class="border border-blue-500/50 text-blue-400 px-4 py-2 rounded text-xs font-bold hover:bg-blue-500/10">VIEW PROGRESS</button>
                    </div>
                </div>

                <!-- Warning Item -->
                <div class="step-node p-4 bg-amber-500/5 border border-slate-800 rounded-r-lg flex justify-between items-center opacity-60">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="px-2 py-0.5 bg-amber-500/20 text-[10px] font-bold text-amber-500 rounded">SCHEDULED</span>
                            <span class="text-xs text-slate-400">#INC-9721</span>
                        </div>
                        <h3 class="text-lg font-bold">Routine Filter Exchange</h3>
                        <p class="text-xs text-slate-400">Location: AHU Room 12</p>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] text-slate-500 uppercase">Scheduled For</div>
                        <div class="text-sm font-bold text-slate-300">Today, 15:00</div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Right: Resource Allocation (Who can do it?) -->
        <aside class="col-span-4 bg-slate-900/30 border border-slate-800 rounded-xl p-5 overflow-hidden flex flex-col">
            <h2 class="text-xs font-bold text-slate-500 mb-6 uppercase flex justify-between items-center">
                <span>Deployment Slots</span>
                <span class="text-green-500 flex items-center gap-1"><i data-lucide="circle" class="w-2 h-2 fill-green-500"></i> Auto-Match ON</span>
            </h2>

            <div class="space-y-4 flex-1 overflow-y-auto pr-2">
                <!-- Staff Slot 1 -->
                <div class="p-3 bg-slate-800/40 border border-slate-700 rounded-lg flex items-center gap-4">
                    <div class="w-10 h-10 rounded bg-green-500/20 border border-green-500/50 flex items-center justify-center text-green-500">
                        <i data-lucide="user"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold">วิชัย สายซ่อม</div>
                        <div class="text-[10px] text-green-400">READY - ใกล้จุดเกิดเหตุ (200m)</div>
                    </div>
                    <button class="w-8 h-8 rounded border border-slate-600 flex items-center justify-center hover:bg-blue-600 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Staff Slot 2 (Busy) -->
                <div class="p-3 bg-slate-900/20 border border-slate-800 rounded-lg flex items-center gap-4 opacity-50">
                    <div class="w-10 h-10 rounded bg-blue-500/10 border border-blue-500/30 flex items-center justify-center text-blue-400">
                        <i data-lucide="user"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold">สมศักดิ์ ช่างแอร์</div>
                        <div class="text-[10px] text-slate-500">BUSY - คาดว่าจะว่างใน 15 นาที</div>
                    </div>
                </div>

                <!-- Staff Slot 3 -->
                <div class="p-3 bg-slate-800/40 border border-slate-700 rounded-lg flex items-center gap-4">
                    <div class="w-10 h-10 rounded bg-green-500/20 border border-green-500/50 flex items-center justify-center text-green-500">
                        <i data-lucide="user"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold">นริศ ฝีมือดี</div>
                        <div class="text-[10px] text-green-400">READY - Standby ห้องช่าง</div>
                    </div>
                    <button class="w-8 h-8 rounded border border-slate-600 flex items-center justify-center hover:bg-blue-600">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Summary Action -->
            <div class="mt-6 pt-6 border-t border-slate-800">
                <div class="bg-blue-600/10 p-4 rounded-lg border border-blue-500/20">
                    <div class="text-xs text-blue-400 mb-2 font-bold">SMART DISPATCH ADVICE:</div>
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        แนะนำให้ส่ง <span class="text-white font-bold">วิชัย</span> ไปยังเคส <span class="text-white font-bold">Pump Failure</span> เนื่องจากความชำนาญระบบน้ำและอยู่ใกล้ที่สุด
                    </p>
                </div>
            </div>
        </aside>
    </div>

    <!-- Minimal Footer -->
    <footer class="mt-8 pt-4 border-t border-slate-800 flex justify-between items-center text-[10px] text-slate-600">
        <div class="flex gap-4 uppercase font-bold tracking-widest">
            <span>Server: TH-BKK-01</span>
            <span class="text-green-900">● Latency: 12ms</span>
        </div>
        <div class="flex gap-4">
            <span class="hover:text-blue-500 cursor-pointer">LOGS</span>
            <span class="hover:text-blue-500 cursor-pointer">SETTINGS</span>
            <span class="hover:text-blue-500 cursor-pointer text-slate-400">LOGOUT</span>
        </div>
    </footer>

    <script>
        // Live Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString();
        }, 1000);

        lucide.createIcons();
    </script>
</body>
</html>