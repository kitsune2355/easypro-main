<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tactical Maintenance HUD - Matrix View</title>
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
                linear-gradient(to right, rgba(30, 41, 59, 0.2) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(30, 41, 59, 0.2) 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .matrix-cell {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid #1e293b;
            transition: all 0.2s ease;
        }

        .matrix-cell:hover {
            background: rgba(59, 130, 246, 0.05);
            border-color: #3b82f6;
        }

        @keyframes pulse-border {
            0% { border-color: #ef4444; }
            50% { border-color: transparent; }
            100% { border-color: #ef4444; }
        }

        .urgent-border {
            animation: pulse-border 1.5s infinite;
            border-width: 2px;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1e293b;
            border-radius: 10px;
        }
    </style>
</head>
<body class="p-4 tactical-grid">

    <!-- Header: Quick Stats & Command -->
    <header class="flex justify-between items-start mb-6">
        <div class="flex gap-6">
            <div class="bg-blue-600 px-4 py-2 rounded-sm skew-x-[-15deg]">
                <h1 class="text-xl font-black text-white italic skew-x-[15deg] tracking-tighter">RESOURCE MATRIX</h1>
            </div>
            <div class="flex gap-4 items-center">
                <div class="h-8 w-[2px] bg-slate-800"></div>
                <div>
                    <div class="text-[10px] text-slate-500 uppercase">System Load</div>
                    <div class="flex gap-1">
                        <div class="w-3 h-2 bg-blue-500"></div>
                        <div class="w-3 h-2 bg-blue-500"></div>
                        <div class="w-3 h-2 bg-blue-500"></div>
                        <div class="w-3 h-2 bg-slate-700"></div>
                        <div class="w-3 h-2 bg-slate-700"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex gap-4">
            <div class="text-right">
                <div class="text-[10px] text-slate-500">NETWORK STATUS</div>
                <div class="text-xs font-bold text-green-400">ENCRYPTED / ACTIVE</div>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-2 rounded flex items-center gap-3">
                <i data-lucide="clock" class="w-4 h-4 text-blue-500"></i>
                <span id="clock" class="text-sm font-bold font-mono">12:00:00</span>
            </div>
        </div>
    </header>

    <div class="grid grid-cols-12 gap-4 h-[82vh]">
        
        <!-- Left: Queue of UNASSIGNED Incidents (Input Box) -->
        <aside class="col-span-3 flex flex-col gap-4">
            <div class="bg-slate-900/80 border border-slate-800 p-4 rounded-xl flex-1 flex flex-col">
                <h2 class="text-xs font-bold text-slate-400 mb-4 flex items-center justify-between">
                    <span>PENDING QUEUE</span>
                    <span class="bg-red-500/20 text-red-500 px-2 py-0.5 rounded text-[10px]">3 WAITING</span>
                </h2>
                
                <div class="space-y-3 overflow-y-auto custom-scrollbar pr-1 flex-1">
                    <!-- Critical Issue -->
                    <div class="matrix-cell p-3 rounded-lg urgent-border cursor-pointer group">
                        <div class="flex justify-between text-[10px] mb-1">
                            <span class="text-red-500 font-bold">#CRITICAL</span>
                            <span class="text-slate-500">5m ago</span>
                        </div>
                        <div class="text-sm font-bold group-hover:text-blue-400 transition-colors">Water Leak: Server RM 2</div>
                        <div class="mt-2 flex gap-2">
                            <span class="text-[9px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-400">ZONE-A</span>
                            <span class="text-[9px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-400 italic">SLA: 15m</span>
                        </div>
                    </div>

                    <!-- Medium Issue -->
                    <div class="matrix-cell p-3 rounded-lg border-amber-500/30 cursor-pointer group">
                        <div class="flex justify-between text-[10px] mb-1">
                            <span class="text-amber-500 font-bold">#STABLE</span>
                            <span class="text-slate-500">12m ago</span>
                        </div>
                        <div class="text-sm font-bold group-hover:text-blue-400 transition-colors">Lighting: East Corridor</div>
                        <div class="mt-2 flex gap-2">
                            <span class="text-[9px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-400">ZONE-C</span>
                        </div>
                    </div>

                    <!-- Low Issue -->
                    <div class="matrix-cell p-3 rounded-lg opacity-60 hover:opacity-100 cursor-pointer group">
                        <div class="flex justify-between text-[10px] mb-1">
                            <span class="text-blue-400 font-bold">#ROUTINE</span>
                            <span class="text-slate-500">1h ago</span>
                        </div>
                        <div class="text-sm font-bold group-hover:text-blue-400 transition-colors">Door Sensor Check</div>
                        <div class="mt-2 flex gap-2">
                            <span class="text-[9px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-400">ZONE-B</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-800">
                    <button class="w-full py-2 bg-blue-600 hover:bg-blue-500 rounded text-[10px] font-bold transition-all active:scale-95">MANUAL TICKET +</button>
                </div>
            </div>
        </aside>

        <!-- Right: Resource Matrix (Assignment Grid) -->
        <main class="col-span-9 bg-slate-900/50 border border-slate-800 rounded-xl p-4 flex flex-col">
            <!-- Matrix Header -->
            <div class="grid grid-cols-6 gap-2 mb-4">
                <div class="col-span-2 text-[10px] text-slate-500 uppercase font-bold pl-2">Available Staff</div>
                <div class="text-[10px] text-slate-500 uppercase font-bold text-center">Status</div>
                <div class="text-[10px] text-slate-500 uppercase font-bold text-center">Current Task</div>
                <div class="text-[10px] text-slate-500 uppercase font-bold text-center">Workload</div>
                <div class="text-[10px] text-slate-500 uppercase font-bold text-center">Action</div>
            </div>

            <!-- Matrix Rows -->
            <div class="space-y-2 flex-1 overflow-y-auto custom-scrollbar">
                
                <!-- Row 1: Busy Staff -->
                <div class="grid grid-cols-6 gap-2 items-center bg-slate-800/20 border border-slate-800 p-2 rounded-lg group">
                    <div class="col-span-2 flex items-center gap-3 pl-2">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400 border border-blue-500/50">W</div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-amber-500 border-2 border-slate-900 rounded-full"></div>
                        </div>
                        <div>
                            <div class="text-sm font-bold">วิชัย สายซ่อม</div>
                            <div class="text-[10px] text-slate-500 italic">Senior Electrical</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="text-[10px] text-amber-500 bg-amber-500/10 px-2 py-1 rounded">ON-MISSION</span>
                    </div>
                    <div class="text-center text-[11px] text-slate-300">#INC-9854 (Lobby)</div>
                    <div class="px-4">
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500" style="width: 75%"></div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button class="p-2 hover:bg-slate-700 rounded transition-colors text-slate-400">
                            <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Row 2: Ready Staff -->
                <div class="grid grid-cols-6 gap-2 items-center matrix-cell p-2 rounded-lg border-dashed border-blue-500/50">
                    <div class="col-span-2 flex items-center gap-3 pl-2">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center text-green-500 border border-green-500/50">S</div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-slate-900 rounded-full"></div>
                        </div>
                        <div>
                            <div class="text-sm font-bold">สมศักดิ์ ช่างแอร์</div>
                            <div class="text-[10px] text-slate-500 italic">HVAC Specialist</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="text-[10px] text-green-400 bg-green-500/10 px-2 py-1 rounded">AVAILABLE</span>
                    </div>
                    <div class="text-center text-[11px] text-slate-500 italic">--- IDLE ---</div>
                    <div class="px-4">
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500" style="width: 10%"></div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button class="bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded text-[10px] font-bold text-white shadow-lg shadow-blue-900/40">ASSIGN</button>
                    </div>
                </div>

                <!-- Row 3: Ready Staff -->
                <div class="grid grid-cols-6 gap-2 items-center matrix-cell p-2 rounded-lg border-dashed border-blue-500/50">
                    <div class="col-span-2 flex items-center gap-3 pl-2">
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center text-green-500 border border-green-500/50">N</div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-slate-900 rounded-full"></div>
                        </div>
                        <div>
                            <div class="text-sm font-bold">นริศ ฝีมือดี</div>
                            <div class="text-[10px] text-slate-500 italic">General Technician</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="text-[10px] text-green-400 bg-green-500/10 px-2 py-1 rounded">AVAILABLE</span>
                    </div>
                    <div class="text-center text-[11px] text-slate-500 italic">--- IDLE ---</div>
                    <div class="px-4">
                        <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500" style="width: 5%"></div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button class="bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded text-[10px] font-bold text-white shadow-lg shadow-blue-900/40">ASSIGN</button>
                    </div>
                </div>

                <!-- Empty States / Shadow Rows -->
                <div class="grid grid-cols-6 gap-2 items-center border border-slate-800/50 p-2 rounded-lg opacity-20 italic">
                    <div class="col-span-2 pl-2 text-[10px]">AWAITING CONNECTION...</div>
                    <div class="col-span-4 h-4 bg-slate-800/50 rounded"></div>
                </div>

            </div>

            <!-- Matrix Control Footer -->
            <div class="mt-4 p-4 bg-slate-950 border border-slate-800 rounded-lg flex items-center justify-between">
                <div class="flex gap-4 items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-[10px] text-slate-400">READY TO MATCH</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                        <span class="text-[10px] text-slate-400">OPTIMAL LOAD</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button class="px-4 py-2 text-[10px] font-bold text-slate-400 border border-slate-700 rounded hover:bg-slate-800">RE-BALANCE ALL</button>
                    <button class="px-4 py-2 text-[10px] font-bold text-blue-400 border border-blue-500/50 rounded hover:bg-blue-500/10 uppercase tracking-widest">Auto-Dispatch Mode</button>
                </div>
            </div>
        </main>
    </div>

    <!-- System Logs Footer -->
    <footer class="mt-4 flex justify-between text-[9px] text-slate-700 font-mono">
        <div class="flex gap-6">
            <span>LOG: [12:00:15] AUTO-ASSIGNMENT CALCULATION COMPLETE</span>
            <span>LOG: [12:00:10] STAFF 'สมศักดิ์' UPDATED STATUS TO READY</span>
        </div>
        <div class="uppercase">V.2.5.0-STABLE / MATRIX_MODULE_ACTIVE</div>
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