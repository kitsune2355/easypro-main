<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tactical Maintenance HUD</title>
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

        /* Tactical Grid Background */
        .tactical-grid {
            background-image: 
                linear-gradient(to right, rgba(30, 41, 59, 0.5) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(30, 41, 59, 0.5) 1px, transparent 1px);
            background-size: 50px 50px;
        }

        /* Pulse Animation for Critical Jobs */
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        .node-critical {
            animation: pulse-red 2s infinite;
        }

        /* Layout */
        .map-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 120px);
            border: 1px solid #1e293b;
            background: rgba(15, 23, 42, 0.8);
            border-radius: 12px;
        }

        /* HUD Overlay Elements */
        .hud-border {
            border: 2px solid #3b82f6;
            clip-path: polygon(0 0, 100% 0, 100% 80%, 90% 100%, 0 100%);
        }

        .scanner-line {
            width: 100%;
            height: 2px;
            background: linear-gradient(to bottom, transparent, #3b82f6, transparent);
            position: absolute;
            top: 0;
            left: 0;
            animation: scan 4s linear infinite;
            z-index: 5;
            opacity: 0.3;
        }

        @keyframes scan {
            0% { top: 0; }
            100% { top: 100%; }
        }
    </style>
</head>
<body class="p-6 tactical-grid">

    <!-- Header HUD -->
    <header class="flex justify-between items-end mb-6">
        <div>
            <div class="text-blue-500 text-xs font-bold mb-1 tracking-widest">SYSTEM STATUS: ACTIVE</div>
            <h1 class="text-4xl font-black text-white italic tracking-tighter">TACTICAL VIEW v2.0</h1>
        </div>
        <div class="flex gap-8 text-right">
            <div>
                <div class="text-slate-500 text-[10px]">TOTAL INCIDENTS</div>
                <div class="text-2xl font-bold">04</div>
            </div>
            <div>
                <div class="text-slate-500 text-[10px]">AVG RESPONSE</div>
                <div class="text-2xl font-bold text-green-400">12:45 <span class="text-xs">m</span></div>
            </div>
        </div>
    </header>

    <div class="flex gap-6 h-[75vh]">
        
        <!-- Sidebar: Pulse List (No more cards, just vital stats) -->
        <aside class="w-80 flex flex-col gap-4">
            <div class="text-xs text-blue-400 font-bold border-b border-blue-900 pb-2">ACTIVE NODES</div>
            
            <div class="space-y-3 overflow-y-auto pr-2 custom-scrollbar">
                <!-- Node 1 -->
                <div class="p-3 border-l-4 border-red-500 bg-red-500/5 hover:bg-red-500/10 cursor-pointer transition-all" onclick="focusNode('node-1')">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-[10px] text-red-400 font-bold">CRITICAL</span>
                        <span class="text-[10px] text-slate-500">09:30 AM</span>
                    </div>
                    <div class="text-sm font-bold truncate">SERVER ROOM B: OVERHEAT</div>
                    <div class="text-[10px] text-slate-400 mt-1">SLA: <span class="text-red-400">00:15:42 LEFT</span></div>
                </div>

                <!-- Node 2 -->
                <div class="p-3 border-l-4 border-blue-500 bg-blue-500/5 hover:bg-blue-500/10 cursor-pointer transition-all" onclick="focusNode('node-2')">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-[10px] text-blue-400 font-bold">STABLE</span>
                        <span class="text-[10px] text-slate-500">10:15 AM</span>
                    </div>
                    <div class="text-sm font-bold truncate">MTG ROOM 2: SIGNAL LOSS</div>
                    <div class="text-[10px] text-slate-400 mt-1">SLA: 02:40:00 LEFT</div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace: The Map -->
        <section class="flex-1 relative map-container overflow-hidden">
            <div class="scanner-line"></div>
            
            <!-- Mock Floor Plan SVG (Simplified) -->
            <svg class="absolute inset-0 w-full h-full opacity-30" viewBox="0 0 800 500">
                <path d="M 50 50 L 750 50 L 750 450 L 50 450 Z" fill="none" stroke="#334155" stroke-width="2" />
                <line x1="400" y1="50" x2="400" y2="450" stroke="#334155" stroke-dasharray="5,5" />
                <rect x="100" y="100" width="150" height="100" fill="none" stroke="#334155" />
                <rect x="100" y="300" width="150" height="100" fill="none" stroke="#334155" />
                <rect x="550" y="100" width="150" height="300" fill="none" stroke="#334155" />
                <text x="110" y="120" fill="#334155" font-size="12">SERVER ROOM B</text>
                <text x="560" y="120" fill="#334155" font-size="12">MEETING WING</text>
            </svg>

            <!-- Incident Nodes (Pins on Map) -->
            <div id="node-1" class="absolute left-[175px] top-[150px] z-20 group">
                <div class="node-critical w-6 h-6 bg-red-600 rounded-full flex items-center justify-center cursor-pointer border-2 border-white">
                    <i data-lucide="alert-triangle" class="w-3 h-3 text-white"></i>
                </div>
                <!-- Mini Tooltip -->
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-48 bg-slate-900 border border-red-500 p-2 text-[10px] rounded shadow-2xl">
                    <div class="font-bold text-red-500 mb-1">NODE CRITICAL</div>
                    <div>Temp: 82°C (Warning)</div>
                    <div class="mt-2 text-blue-400 underline">VIEW DETAILS</div>
                </div>
            </div>

            <div id="node-2" class="absolute left-[620px] top-[250px] z-20 group">
                <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center cursor-pointer border-2 border-white hover:scale-125 transition-transform">
                    <i data-lucide="wrench" class="w-3 h-3 text-white"></i>
                </div>
            </div>

            <!-- Dashboard Bottom Overlay -->
            <div class="absolute bottom-6 left-6 right-6 bg-slate-900/90 backdrop-blur-md border border-slate-700 p-4 rounded-xl flex items-center justify-between">
                <div class="flex gap-6">
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Selected Unit</span>
                        <span id="focused-title" class="text-sm font-bold text-blue-400 font-mono tracking-widest">--- SELECT NODE ---</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 block uppercase">Maintenance Staff</span>
                        <span class="text-sm font-bold">SOMCHAI.W (STANDBY)</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded text-[10px] font-bold border border-slate-600">SCHEDULER</button>
                    <button id="action-btn" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 rounded text-[10px] font-bold opacity-50 cursor-not-allowed">DEPLOY TEAM</button>
                </div>
            </div>
        </section>
    </div>

    <!-- HUD Footer Decorations -->
    <footer class="mt-6 flex justify-between text-[10px] text-slate-600 font-mono italic">
        <div>COORDINATES: 13.7563° N, 100.5018° E</div>
        <div>ENCRYPTED CONNECTION [AES-256]</div>
        <div id="clock">TIME: 12:45:01 PM</div>
    </footer>

    <script>
        function focusNode(nodeId) {
            // Reset node effects
            document.querySelectorAll('.node-critical').forEach(el => el.classList.remove('ring-4', 'ring-white'));
            
            const node = document.getElementById(nodeId);
            const title = document.getElementById('focused-title');
            const btn = document.getElementById('action-btn');

            if (nodeId === 'node-1') {
                title.innerText = 'UNIT: SERVER_ROOM_B';
                title.classList.replace('text-blue-400', 'text-red-400');
            } else {
                title.innerText = 'UNIT: MTG_ROOM_2';
                title.classList.replace('text-red-400', 'text-blue-400');
            }

            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            node.querySelector('div').classList.add('ring-4', 'ring-white');
        }

        // Live Clock
        setInterval(() => {
            const now = new Date();
            document.getElementById('clock').innerText = `TIME: ${now.toLocaleTimeString()}`;
        }, 1000);

        lucide.createIcons();
    </script>
</body>
</html>