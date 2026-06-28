<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>APMalls | India's Premium Smart Retail Ecosystem</title>

        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Cinzel:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

        <!-- Tailwind CSS -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
            <style>
                body {
                    font-family: 'Plus Jakarta Sans', sans-serif;
                }
                .font-serif {
                    font-family: 'Playfair Display', serif;
                }
                .font-royal {
                    font-family: 'Cinzel', serif;
                }
                .indian-jali {
                    background-image: radial-gradient(circle, #C5A880 1px, transparent 1px);
                    background-size: 24px 24px;
                    opacity: 0.04;
                }
            </style>
        @endif
    </head>
    <body class="bg-[#FAF7F2] text-[#22201D] selection:bg-[#9C805E] selection:text-[#FAF7F2] antialiased">

        <!-- Jali Pattern Overlay -->
        <div class="fixed inset-0 pointer-events-none indian-jali z-0"></div>

        <!-- Navigation Header -->
        <header class="sticky top-0 z-50 bg-[#FAF7F2]/90 backdrop-blur-md border-b border-[#EADFCB] transition-all duration-300">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <!-- Brand Logo (Indian Aesthetic Brand) -->
                <a href="/" class="flex items-center gap-2.5 group relative z-10">
                    <span class="text-xl font-semibold tracking-[0.05em] uppercase font-royal text-[#22201D]">
                        AP<span class="font-light text-[#9C805E]">Malls</span>
                    </span>
                    <!-- Small premium gold Indian accent dot -->
                    <span class="w-2 h-2 bg-[#C5A880] rounded-full shadow-sm"></span>
                </a>

                <!-- Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-xs uppercase tracking-widest font-medium text-[#736B5E]">
                    <a href="#heritage" class="hover:text-[#9C805E] transition-colors duration-200">The Heritage</a>
                    <a href="#destinations" class="hover:text-[#9C805E] transition-colors duration-200">Destinations</a>
                    <a href="#experience" class="hover:text-[#9C805E] transition-colors duration-200">Experience</a>
                    <a href="#contact" class="hover:text-[#9C805E] transition-colors duration-200">Contact</a>
                </nav>

                <!-- Auth/Actions -->
                <div class="flex items-center gap-4 relative z-10">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-xs uppercase tracking-wider font-semibold border border-[#9C805E] px-5 py-2.5 rounded-none bg-[#9C805E] text-[#FAF7F2] hover:bg-transparent hover:text-[#9C805E] transition-all duration-300">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-xs uppercase tracking-wider font-semibold text-[#22201D] hover:text-[#9C805E] transition-all duration-200">
                                Sign In
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-xs uppercase tracking-wider font-semibold border border-[#9C805E] px-5 py-2.5 rounded-none bg-[#9C805E] text-[#FAF7F2] hover:bg-transparent hover:text-[#9C805E] transition-all duration-300">
                                    Partner Portal
                                </a>
                            @endif
                        @endauth
                    @else
                        <!-- Premium Indian Concierge Call to Action -->
                        <a href="#contact" class="text-[11px] uppercase tracking-widest font-semibold border border-[#9C805E] px-6 py-3 rounded-none bg-[#9C805E] text-[#FAF7F2] hover:bg-transparent hover:text-[#9C805E] transition-all duration-300">
                            Connect Now
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative min-h-[85vh] flex items-center justify-center overflow-hidden py-20 z-10">
            <div class="max-w-5xl mx-auto px-6 text-center z-10">

                <!-- Welcome Traditional Greeting Tag -->
                <div class="inline-flex items-center gap-2.5 px-4 py-1.5 border border-[#C5A880]/30 bg-white/70 backdrop-blur-sm rounded-full mb-8">
                    <!-- Elegant Diya/Lotus Minimal SVG symbol -->
                    <svg class="w-3.5 h-3.5 text-[#C5A880]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2c-.5 2.5-2.5 4.5-5 5 .5.5 1 1.5 1 2.5C8 11.5 6 13 4 13c3 0 5 2.5 5 5 0-1.5 1-2.5 2-2.5s2 1 2 2.5c0-2.5 2-5 5-5-2 0-4-1.5-4-3.5 0-1 0-2 1-2.5-2.5-.5-4.5-2.5-5-5z"/>
                    </svg>
                    <span class="text-[10px] uppercase tracking-widest font-semibold text-[#8C7A64]">Namaste & Welcome</span>
                </div>

                <!-- Headline -->
                <h1 class="text-4xl sm:text-6xl md:text-7xl tracking-tight font-light mb-8 leading-[1.1] text-[#22201D]">
                    Experience <span class="font-serif italic font-normal text-[#9C805E]">hospitality & luxury</span> at India's iconic spaces.
                </h1>

                <!-- Subtitle -->
                <p class="max-w-2xl mx-auto text-base sm:text-lg text-[#736B5E] font-light leading-relaxed mb-12">
                    Bridging physical retail beauty with India's most advanced smart retail technology. Elevating shopping at premium destination malls across Delhi NCR, Mumbai, Bengaluru, and beyond.
                </p>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="#destinations" class="w-full sm:w-auto text-center text-xs uppercase tracking-widest font-semibold bg-[#22201D] text-[#FAF7F2] border border-[#22201D] px-8 py-4 hover:bg-transparent hover:text-[#22201D] transition-all duration-300">
                        Explore Destinations
                    </a>
                    <a href="#contact" class="w-full sm:w-auto text-center text-xs uppercase tracking-widest font-semibold bg-transparent text-[#22201D] border border-[#C5A880] hover:border-[#22201D] px-8 py-4 transition-all duration-300">
                        Become a Retail Partner
                    </a>
                </div>
            </div>

            <!-- Subtle Gold Mandala / Indian Motif Accents in background -->
            <div class="absolute inset-0 flex items-center justify-center opacity-[0.06] pointer-events-none">
                <!-- SVG Traditional Mandala -->
                <svg class="w-[700px] h-[700px] text-[#9C805E]" fill="none" viewBox="0 0 100 100" stroke="currentColor" stroke-width="0.3">
                    <circle cx="50" cy="50" r="45" />
                    <circle cx="50" cy="50" r="35" />
                    <circle cx="50" cy="50" r="25" />
                    <circle cx="50" cy="50" r="15" />
                    @for($i=0; $i<360; $i+=15)
                        <line x1="50" y1="50" x2="{{ 50 + 45 * cos(deg2rad($i)) }}" y2="{{ 50 + 45 * sin(deg2rad($i)) }}" />
                    @endfor
                </svg>
            </div>
        </section>

        <!-- Stats Grid (Indian Context) -->
        <section class="border-y border-[#EADFCB] bg-white/65 backdrop-blur-sm py-12 z-10 relative">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
                    <div class="md:border-r border-[#EADFCB] last:border-0 py-4 md:px-8">
                        <span class="block text-4xl font-light tracking-tight text-[#22201D] mb-1">12+ Cities</span>
                        <span class="text-xs uppercase tracking-widest text-[#8C7A64]">Across the Indian Subcontinent</span>
                    </div>
                    <div class="md:border-r border-[#EADFCB] last:border-0 py-4 md:px-8">
                        <span class="block text-4xl font-light tracking-tight text-[#22201D] mb-1">1.8 Million+</span>
                        <span class="text-xs uppercase tracking-widest text-[#8C7A64]">Smart Connected Shoppers</span>
                    </div>
                    <div class="py-4 md:px-8">
                        <span class="block text-4xl font-light tracking-tight text-[#22201D] mb-1">1,200+ Brands</span>
                        <span class="text-xs uppercase tracking-widest text-[#8C7A64]">Luxury & Contemporary Partners</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Heritage & Vision Section -->
        <section id="heritage" class="py-24 max-w-7xl mx-auto px-6 z-10 relative">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5 space-y-6">
                    <span class="text-xs uppercase tracking-widest font-semibold text-[#9C805E] block">Our Heritage</span>
                    <h2 class="text-3xl sm:text-4xl md:text-5xl tracking-tight font-light leading-tight text-[#22201D]">
                        The art of premium <span class="font-serif italic font-normal text-[#9C805E]">Atithi Devo Bhava</span>, digitized.
                    </h2>
                    <p class="text-[#736B5E] font-light leading-relaxed">
                        In India, guest service is a sacred tradition. At APMalls, we bring this timeless value of hospitality into the digital age. We craft seamless retail solutions that serve shoppers like royalty.
                    </p>
                    <p class="text-[#736B5E] font-light leading-relaxed">
                        Whether navigating massive premium malls with indoor maps, booking luxury dining, or experiencing unified one-tap rewards across India's high-end retail malls, our technology is elegant and intuitive.
                    </p>
                </div>
                <!-- Premium Gold/Ivory Card Display -->
                <div class="lg:col-span-7 bg-[#FAF5EE] p-8 md:p-12 border border-[#E5DAC4] aspect-video flex flex-col justify-between relative overflow-hidden shadow-sm">
                    <!-- Subtle ethnic lotus motif in background -->
                    <div class="absolute right-[-40px] bottom-[-40px] opacity-10 text-[#C5A880]">
                        <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>

                    <div class="relative z-10">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] uppercase tracking-widest font-semibold text-[#9C805E]">APMalls Golden Loyalty Club</span>
                            <span class="text-xs font-royal text-[#C5A880] font-semibold">PREMIUM</span>
                        </div>
                        <div class="h-px bg-[#EADFCB] my-4"></div>
                    </div>
                    <div class="my-auto py-6 relative z-10">
                        <span class="block text-[10px] uppercase tracking-widest text-[#8C7A64] mb-1">Ecosystem Identity</span>
                        <span class="text-2xl tracking-wider font-light text-[#22201D]">GOLD_MEMBER_9921</span>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-[#8C7A64] tracking-wider uppercase relative z-10">
                        <span>EST. 2026</span>
                        <span>Unified rewards & lounge access</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Indian Destinations Section -->
        <section id="destinations" class="py-24 bg-white/70 backdrop-blur-sm border-t border-[#EADFCB] z-10 relative">
            <div class="max-w-7xl mx-auto px-6">

                <div class="text-center max-w-3xl mx-auto mb-20">
                    <span class="text-xs uppercase tracking-widest font-semibold text-[#9C805E] block mb-4">Prime Destinations</span>
                    <h2 class="text-3xl sm:text-4xl tracking-tight font-light mb-6 text-[#22201D]">Empowering India's leading shopping hubs.</h2>
                    <p class="text-[#736B5E] font-light leading-relaxed">Connecting retail developers, global luxury brands, and urban explorers in one synchronized smart directory.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-px bg-[#EADFCB] border border-[#EADFCB]">

                    <!-- Destination 1 -->
                    <div class="bg-[#FAF7F2] p-8 md:p-10 hover:bg-[#F5EFE4]/30 transition-all duration-300 flex flex-col justify-between min-h-[300px]">
                        <div>
                            <span class="text-[10px] uppercase tracking-widest text-[#9C805E] font-bold block mb-6">MUMBAI</span>
                            <h3 class="text-xl font-light mb-3 text-[#22201D]">South Mumbai Luxury Hub</h3>
                            <p class="text-xs text-[#736B5E] font-light leading-relaxed">Elevating VIP guest services, contactless valet bookings, and personal luxury shopping in South Mumbai.</p>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold mt-8 block text-[#9C805E]">Explore Hub &rarr;</span>
                    </div>

                    <!-- Destination 2 -->
                    <div class="bg-[#FAF7F2] p-8 md:p-10 hover:bg-[#F5EFE4]/30 transition-all duration-300 flex flex-col justify-between min-h-[300px]">
                        <div>
                            <span class="text-[10px] uppercase tracking-widest text-[#9C9C95] font-bold block mb-6">DELHI NCR</span>
                            <h3 class="text-xl font-light mb-3 text-[#22201D]">Capital Smart Plaza</h3>
                            <p class="text-xs text-[#736B5E] font-light leading-relaxed">Integrated high-precision indoor navigation, smart directory kiosks, and real-time food-court ordering.</p>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold mt-8 block text-[#9C805E]">Explore Hub &rarr;</span>
                    </div>

                    <!-- Destination 3 -->
                    <div class="bg-[#FAF7F2] p-8 md:p-10 hover:bg-[#F5EFE4]/30 transition-all duration-300 flex flex-col justify-between min-h-[300px]">
                        <div>
                            <span class="text-[10px] uppercase tracking-widest text-[#9C805E] font-bold block mb-6">BENGALURU</span>
                            <h3 class="text-xl font-light mb-3 text-[#22201D]">Silicon Valley Galleria</h3>
                            <p class="text-xs text-[#736B5E] font-light leading-relaxed">Unified cross-mall loyalty privileges, tech-enabled concierges, and instant fashion reservation portals.</p>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold mt-8 block text-[#9C805E]">Explore Hub &rarr;</span>
                    </div>

                    <!-- Destination 4 -->
                    <div class="bg-[#FAF7F2] p-8 md:p-10 hover:bg-[#F5EFE4]/30 transition-all duration-300 flex flex-col justify-between min-h-[300px]">
                        <div>
                            <span class="text-[10px] uppercase tracking-widest text-[#9C9C95] font-bold block mb-6">HYDERABAD</span>
                            <h3 class="text-xl font-light mb-3 text-[#22201D]">The Deccan Pavilion</h3>
                            <p class="text-xs text-[#736B5E] font-light leading-relaxed">Providing high-end experiential retail, cultural showcases, and personalized localized discount offerings.</p>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest font-bold mt-8 block text-[#9C805E]">Explore Hub &rarr;</span>
                    </div>

                </div>
            </div>
        </section>

        <!-- Traditional Indian Hospitality Quote -->
        <section class="py-24 bg-[#FAF7F2] border-t border-[#EADFCB] z-10 relative">
            <div class="max-w-4xl mx-auto px-6 text-center">
                <!-- Golden Motif Top Ornament -->
                <div class="flex justify-center mb-6">
                    <svg class="w-8 h-8 text-[#C5A880]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3l1.5 4.5 4.5 1.5-4.5 1.5-1.5 4.5-1.5-4.5-4.5-1.5 4.5-1.5z"/>
                    </svg>
                </div>
                <span class="text-3xl font-light font-serif italic text-[#22201D] block leading-relaxed mb-8">
                    "APMalls understands our premium clientele perfectly. They merged Indian values of absolute dedication with state-of-the-art global smart shopping solutions."
                </span>
                <span class="text-xs uppercase tracking-widest font-semibold block text-[#9C805E] mb-1">Rajiv Singhania</span>
                <span class="text-[10px] uppercase tracking-widest text-[#8C7A64]">Chairman of India Heritage Luxury Retail</span>
            </div>
        </section>

        <!-- Become a Partner Contact Form -->
        <section id="contact" class="py-24 bg-white/70 backdrop-blur-sm border-t border-[#EADFCB] z-10 relative">
            <div class="max-w-3xl mx-auto px-6">
                <div class="text-center mb-16">
                    <span class="text-xs uppercase tracking-widest font-semibold text-[#9C805E] block mb-4">Collaborations</span>
                    <h2 class="text-3xl tracking-tight font-light mb-4 text-[#22201D]">Partner with India's Smart Retail Leader</h2>
                    <p class="text-[#736B5E] font-light text-sm max-w-lg mx-auto">Enhance your property's shopping experience, connect with major brands, and increase retail profits with digital intelligence.</p>
                </div>

                <form onsubmit="event.preventDefault(); alert('Dhanyawaad! Your partnership inquiry has been sent. Our team will connect with you soon.');" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-[#8C7A64] mb-2 font-semibold">Your Name</label>
                            <input type="text" required class="w-full bg-[#FAF7F2] border border-[#E5DAC4] px-4 py-3 text-sm focus:border-[#9C805E] focus:outline-none transition-colors duration-200" placeholder="e.g. Vikram Sharma">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-[#8C7A64] mb-2 font-semibold">Corporate Email</label>
                            <input type="email" required class="w-full bg-[#FAF7F2] border border-[#E5DAC4] px-4 py-3 text-sm focus:border-[#9C805E] focus:outline-none transition-colors duration-200" placeholder="e.g. sharma@luxuryplaza.in">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-[#8C7A64] mb-2 font-semibold">Company / Mall Name</label>
                            <input type="text" required class="w-full bg-[#FAF7F2] border border-[#E5DAC4] px-4 py-3 text-sm focus:border-[#9C805E] focus:outline-none transition-colors duration-200" placeholder="e.g. Deccan Developers">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-[#8C7A64] mb-2 font-semibold">HQ Location</label>
                            <input type="text" required class="w-full bg-[#FAF7F2] border border-[#E5DAC4] px-4 py-3 text-sm focus:border-[#9C805E] focus:outline-none transition-colors duration-200" placeholder="e.g. New Delhi, India">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-[#8C7A64] mb-2 font-semibold">Inquiry Details</label>
                        <textarea rows="4" required class="w-full bg-[#FAF7F2] border border-[#E5DAC4] px-4 py-3 text-sm focus:border-[#9C805E] focus:outline-none transition-colors duration-200 resize-none" placeholder="How can we assist your retail property?"></textarea>
                    </div>

                    <div class="text-center pt-4">
                        <button type="submit" class="w-full sm:w-auto text-xs uppercase tracking-widest font-semibold bg-[#22201D] text-[#FAF7F2] border border-[#22201D] px-10 py-4 hover:bg-transparent hover:text-[#9C805E] hover:border-[#9C805E] transition-all duration-300 cursor-pointer">
                            Submit Partnership Request
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-[#FAF7F2] border-t border-[#EADFCB] py-16 text-xs text-[#736B5E] z-10 relative">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-12 mb-16">

                    <div class="md:col-span-4 space-y-4">
                        <span class="text-base font-semibold tracking-[0.05em] uppercase text-[#22201D] font-royal">
                            AP<span class="font-light text-[#9C805E]">Malls</span>
                        </span>
                        <p class="font-light leading-relaxed max-w-xs">
                            Architecting refined and culturally aligned smart retail technology solutions for premier Indian destinations.
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <span class="block font-semibold uppercase tracking-wider text-[#22201D] mb-4">Nav</span>
                        <ul class="space-y-2">
                            <li><a href="#heritage" class="hover:text-[#9C805E] transition-colors duration-200">The Heritage</a></li>
                            <li><a href="#destinations" class="hover:text-[#9C805E] transition-colors duration-200">Destinations</a></li>
                            <li><a href="#contact" class="hover:text-[#9C805E] transition-colors duration-200">Contact</a></li>
                        </ul>
                    </div>

                    <div class="md:col-span-3">
                        <span class="block font-semibold uppercase tracking-wider text-[#22201D] mb-4">Tech Offerings</span>
                        <ul class="space-y-2 font-light">
                            <li>High-Fidelity Indoor Navigation</li>
                            <li>VIP Loyalty & Lounge Portals</li>
                            <li>Smart Parking Solutions</li>
                            <li>Unified POS Retailer APIs</li>
                        </ul>
                    </div>

                    <div class="md:col-span-3">
                        <span class="block font-semibold uppercase tracking-wider text-[#22201D] mb-4">Regional Office</span>
                        <p class="font-light leading-relaxed">
                            Cyber City, Phase 3, Sector 24<br>
                            Gurugram, Haryana 122002, India<br>
                            <span class="block mt-2">contact@apmalls.com</span>
                        </p>
                    </div>

                </div>

                <div class="h-px bg-[#EADFCB] mb-8"></div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p>&copy; 2026 APMalls.com. All rights reserved.</p>
                    <div class="flex items-center gap-6 font-light">
                        <a href="#" class="hover:text-[#22201D]">Privacy Policy</a>
                        <a href="#" class="hover:text-[#22201D]">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>

    </body>
</html>
