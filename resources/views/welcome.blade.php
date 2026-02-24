<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پنبه نت | دروازه‌ی اینترنت آزاد</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Tailwind CSS (via CDN for immediate effect without build) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Vazirmatn', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e', // Green core
                            600: '#16a34a',
                            900: '#14532d',
                            950: '#052e16',
                        },
                        dark: {
                            main: '#0a0a0b',
                            card: '#121214',
                            border: '#27272a'
                        }
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>

    <style type="text/tailwindcss">
        @layer utilities {
            .glass {
                @apply bg-white/5 backdrop-blur-xl border border-white/10 shadow-[0_8px_32px_0_rgba(0,0,0,0.36)];
            }
            .text-gradient {
                @apply bg-clip-text text-transparent bg-gradient-to-r from-brand-500 to-emerald-300;
            }
        }
        
        body {
            @apply antialiased bg-dark-main text-gray-100 overflow-x-hidden selection:bg-brand-500 selection:text-white;
        }

        /* Particles background */
        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
        }
    </style>
</head>
<body x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)" class="relative min-h-screen flex flex-col justify-between">
    
    <!-- Background Effects -->
    <div id="particles-js"></div>
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-brand-600/30 rounded-full mix-blend-screen filter blur-[100px] animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-96 h-96 bg-emerald-600/20 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-teal-600/20 rounded-full mix-blend-screen filter blur-[100px] animate-blob animation-delay-4000"></div>
    </div>

    <!-- Navigation -->
    <header :class="{ 'glass !bg-dark-main/60 py-3': scrolled, 'bg-transparent py-6': !scrolled }" class="fixed top-0 w-full z-50 transition-all duration-300 border-b border-transparent" :class="{ '!border-white/10': scrolled }">
        <div class="max-w-7xl mx-auto px-6 h-12 flex items-center justify-between">
            <div class="flex items-center gap-3 relative z-10 group cursor-pointer">
                <!-- Logo Animation -->
                <div class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-emerald-600 shadow-lg shadow-brand-500/30 group-hover:shadow-brand-500/50 transition-all duration-300">
                    <svg class="w-6 h-6 text-white group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="font-black text-xl tracking-tight text-white">پنبه‌<span class="text-brand-500">نت</span></span>
            </div>

            <nav class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-gray-300 hover:text-white transition-colors text-sm font-medium">قابلیت‌ها</a>
                <a href="#pricing" class="text-gray-300 hover:text-white transition-colors text-sm font-medium">تعرفه‌ها</a>
                <a href="#about" class="text-gray-300 hover:text-white transition-colors text-sm font-medium">درباره ما</a>
            </nav>

            <div class="flex items-center gap-3">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="relative group overflow-hidden rounded-xl bg-white/5 px-5 py-2.5 text-sm font-bold text-white shadow-sm border border-white/10 hover:bg-white/10 transition-all">
                            <span class="relative z-10 flex items-center gap-2">
                                داشبورد مدیریت
                                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-300 hover:text-white transition-colors px-4 py-2">ورود</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="relative group px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-bold overflow-hidden shadow-lg shadow-brand-500/30 hover:shadow-brand-500/50 hover:-translate-y-0.5 transition-all">
                                <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out"></div>
                                <span class="relative z-10">ثبت‌نام سریع</span>
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-grow flex items-center justify-center pt-32 pb-20 relative z-10">
        <div class="max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
            
            <!-- Text Content -->
            <div class="text-right space-y-8 animate-fade-in-up">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-brand-500/30 bg-brand-500/10 text-brand-400 text-xs font-semibold backdrop-blur-md">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                    </span>
                    سرویس‌های V2Ray نسل جدید
                </div>
                
                <h1 class="text-5xl lg:text-7xl font-black leading-[1.2] lg:leading-[1.1] tracking-tight">
                    اینترنت <span class="text-gradient">آزاد و بدون مرز</span><br>
                    با سرعت نور!
                </h1>
                
                <p class="text-lg lg:text-xl text-gray-400 font-light leading-relaxed max-w-lg">
                    تحریم‌ها را فراموش کنید. با پنبه‌نت، به پایداری بی‌نظیر، پینگ تایم پایین و تونل‌های رمزنگاری شده قدرتمند متصل شوید.
                </p>
                
                <div class="flex flex-wrap items-center gap-4 pt-4">
                    <a href="{{ route('register') ?? '#' }}" class="group relative px-8 py-4 bg-white text-dark-main font-bold rounded-2xl overflow-hidden shadow-[0_0_40px_rgba(255,255,255,0.3)] hover:shadow-[0_0_60px_rgba(255,255,255,0.5)] transition-all duration-300">
                        <div class="absolute inset-0 bg-brand-500 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out z-0"></div>
                        <span class="relative z-10 flex items-center gap-2 group-hover:text-white transition-colors">
                            شروع کنید
                            <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </span>
                    </a>
                    
                    <a href="https://t.me/panbehnet" target="_blank" class="px-8 py-4 rounded-2xl glass font-medium text-white hover:bg-white/10 border border-white/5 hover:border-white/20 transition-all flex items-center gap-2 group">
                        <svg class="w-5 h-5 text-[#0088cc] group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        ربات تلگرام
                    </a>
                </div>
                
                <!-- Server Stats -->
                <div class="grid grid-cols-3 gap-4 pt-8 border-t border-white/10 mt-8">
                    <div>
                        <div class="text-3xl font-black text-white">99.9<span class="text-brand-500">%</span></div>
                        <div class="text-xs text-gray-400 mt-1">آپتایم سرورها</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-white">50<span class="text-brand-500">+</span></div>
                        <div class="text-xs text-gray-400 mt-1">لوکیشن فعال</div>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-white"><span class="text-brand-500">AES</span>-256</div>
                        <div class="text-xs text-gray-400 mt-1">رمزنگاری نظامی</div>
                    </div>
                </div>
            </div>

            <!-- Visual Graphic -->
            <div class="relative animate-float lg:block hidden">
                <div class="absolute inset-0 bg-gradient-to-tr from-brand-600/20 to-teal-500/20 blur-3xl rounded-full z-0"></div>
                <div class="relative z-10 glass rounded-3xl p-2 border border-white/10 shadow-2xl overflow-hidden transform perspective-midrange rotate-y-[-10deg] rotate-x-[5deg]">
                    <div class="bg-dark-card rounded-2xl border border-white/5 overflow-hidden">
                        <!-- Mac OS style header -->
                        <div class="h-8 border-b border-white/5 bg-white/5 flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <!-- Terminal UI Mockup -->
                        <div class="p-6 font-mono text-sm text-gray-300 space-y-3 h-80 overflow-hidden relative">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-dark-card z-10 top-1/2"></div>
                            <div class="text-brand-400">root@panbehnet:~# <span class="text-white typing-anim">connect --server ger-01 --protocol vless</span></div>
                            <div class="text-cyan-400">[INFO] Initializing connection protocol...</div>
                            <div class="text-cyan-400">[INFO] Handshake successful with GER-01 (Frankfurt)</div>
                            <div class="text-yellow-400">[WARN] Bypassing DPI firewall...</div>
                            <div class="text-brand-400">[SUCCESS] Connection established! Ping: 42ms</div>
                            <div class="text-gray-500">rx: 2.4MB/s | tx: 1.1MB/s | wss: active</div>
                            <div class="text-gray-500 mt-4 leading-relaxed">
                                > Encrypting traffic...<br>
                                > Routing rules applied...<br>
                                > Welcome to the Free Web!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/5 bg-dark-main/80 backdrop-blur-md py-8 relative z-20">
        <div class="max-w-7xl mx-auto px-6 text-center text-sm font-medium text-gray-500 flex flex-col md:flex-row justify-between items-center gap-4">
            <p>تمامی حقوق برای <span class="text-white">پنبه‌نت</span> محفوظ است. © ۲۰۲۴</p>
            <div class="flex gap-6">
                <a href="#" class="hover:text-brand-400 transition-colors">قوانین و مقررات</a>
                <a href="#" class="hover:text-brand-400 transition-colors">پشتیبانی</a>
            </div>
        </div>
    </footer>

    <!-- Particles.js Script -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 60, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": ["#22c55e", "#10b981", "#ffffff"] },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.2, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#22c55e", "opacity": 0.1, "width": 1 },
                "move": { "enable": true, "speed": 1, "direction": "none", "random": true, "out_mode": "out" }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
                "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 0.5 } }, "push": { "particles_nb": 4 } }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>
