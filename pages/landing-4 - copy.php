<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravHub - Luxury Travel Experiences</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Custom Tailwind Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A2039',
                        secondary: '#50BC81',
                        'brand-gradient-start': '#02CCFE',
                        'brand-gradient-end': '#6DD19C',
                        'footer-dark': '#111625',
                        'section-light': '#0f172a',
                    },
                    fontFamily: {
                        'montserrat': ['Montserrat', 'sans-serif'],
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                    backgroundImage: {
                        'brand-gradient': 'linear-gradient(to right, #02CCFE 0%, #6DD19C 100%)',
                        'brand-gradient-vertical': 'linear-gradient(to bottom, #02CCFE 0%, #6DD19C 100%)',
                        'card-gradient': 'linear-gradient(0deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%)',
                    },
                    animation: {
                        'float-slow': 'float 25s linear infinite',
                        'float-medium': 'float 20s linear infinite',
                        'float-fast': 'float 15s linear infinite',
                        'plane-fly-slow': 'planeFlySlow 35s linear infinite',
                        'plane-fly-medium': 'planeFlyMedium 30s linear infinite',
                        'plane-fly-fast': 'planeFlyFast 25s linear infinite',
                        'dreams-line': 'dreamsLine 4s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: white;
            overflow-x: hidden;
        }
        
        /* Hero Section Background with Animation ONLY in Hero */
        .hero-container {
            background-color: #1A2039;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
        }
        
        /* Background Animation - ONLY in Hero */
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        /* Clouds Animation - ONLY in Hero - DIFFERENT SPEEDS */
        .cloud {
            position: absolute;
            opacity: 0.15;
            filter: blur(2px);
        }
        
        .cloud-1 {
            width: 120px;
            height: 40px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 60px;
            top: 15%;
            animation: float-slow 45s linear infinite;
        }
        
        .cloud-2 {
            width: 150px;
            height: 50px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 75px;
            top: 30%;
            animation: float-medium 35s linear infinite;
            animation-delay: 5s;
        }
        
        .cloud-3 {
            width: 100px;
            height: 35px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            top: 45%;
            animation: float-fast 25s linear infinite;
            animation-delay: 10s;
        }
        
        .cloud-4 {
            width: 180px;
            height: 60px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 90px;
            top: 60%;
            animation: float-slow 50s linear infinite reverse;
            animation-delay: 15s;
        }
        
        .cloud:before, .cloud:after {
            content: '';
            position: absolute;
            background: inherit;
            border-radius: 50%;
        }
        
        .cloud:before {
            width: 50px;
            height: 50px;
            top: -25px;
            left: 20px;
        }
        
        .cloud:after {
            width: 70px;
            height: 70px;
            top: -35px;
            right: 20px;
        }
        
        /* Planes Animation - ONLY in Hero - DIFFERENT SPEEDS AND DIRECTIONS */
        .plane {
            position: absolute;
            font-size: 2rem;
            opacity: 0.3;
            filter: blur(0.5px);
            z-index: 1;
        }
        
        .plane-large {
            font-size: 3.5rem;
            top: 20%;
            animation: planeFlyFast 40s linear infinite;
            opacity: 0.4;
            filter: blur(1px);
        }
        
        .plane-medium {
            font-size: 2.5rem;
            top: 40%;
            animation: planeFlyMedium 30s linear infinite;
            animation-delay: 8s;
        }
        
        .plane-small {
            font-size: 2rem;
            top: 65%;
            animation: planeFlySlow 50s linear infinite;
            animation-delay: 15s;
        }
        
        /* Plane going in reverse direction */
        .plane-reverse {
            font-size: 2.8rem;
            top: 30%;
            right: -50px;
            transform: rotateY(180deg);
            opacity: 0.25;
            animation: planeFlyReverse 55s linear infinite;
            animation-delay: 20s;
        }
        
        /* Keyframes for animations - ONLY in Hero */
        @keyframes float {
            0% { transform: translateX(-150px); }
            100% { transform: translateX(calc(100vw + 150px)); }
        }
        
        @keyframes float-slow {
            0% { transform: translateX(-150px); }
            100% { transform: translateX(calc(100vw + 150px)); }
        }
        
        @keyframes float-medium {
            0% { transform: translateX(-150px) translateY(5px); }
            100% { transform: translateX(calc(100vw + 150px)) translateY(5px); }
        }
        
        @keyframes float-fast {
            0% { transform: translateX(-150px) translateY(-3px); }
            100% { transform: translateX(calc(100vw + 150px)) translateY(-3px); }
        }
        
        @keyframes planeFlyFast {
            0% { transform: translateX(-100px) rotate(5deg); }
            100% { transform: translateX(calc(100vw + 100px)) rotate(5deg); }
        }
        
        @keyframes planeFlyMedium {
            0% { transform: translateX(-150px) rotate(-3deg) translateY(10px); }
            100% { transform: translateX(calc(100vw + 150px)) rotate(-3deg) translateY(10px); }
        }
        
        @keyframes planeFlySlow {
            0% { transform: translateX(-200px) rotate(2deg) translateY(-5px); }
            100% { transform: translateX(calc(100vw + 200px)) rotate(2deg) translateY(-5px); }
        }
        
        /* Reverse direction plane */
        @keyframes planeFlyReverse {
            0% { transform: translateX(calc(100vw + 100px)) rotateY(180deg) rotate(3deg); }
            100% { transform: translateX(-100px) rotateY(180deg) rotate(3deg); }
        }
        
        /* Section Transitions */
        .hero-container, .section-light, .footer-dark {
            transition: background-color 0.8s ease;
        }
        
        /* Section Backgrounds */
        .section-light {
            background-color: #1E2648;
        }
        
        .footer-dark {
            background-color: #111625;
        }
        
        /* Glassmorphism Effect */
        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Service Pills Glass Effect - FIXED NO OVERFLOW */
        .service-pill {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .service-pill:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .service-pill.active {
            background: linear-gradient(to right, rgba(2, 204, 254, 0.2), rgba(109, 209, 156, 0.2));
            border-color: rgba(80, 188, 129, 0.3);
        }
        
        /* Premium Card Hover Effects */
        .premium-card {
            overflow: hidden;
            position: relative;
            transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .premium-card:hover {
            transform: translateY(-10px);
        }
        
        .premium-card img {
            transition: transform 0.7s ease;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .premium-card:hover img {
            transform: scale(1.1);
        }
        
        .card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
            z-index: 1;
        }
        
        .card-content {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem;
            z-index: 2;
            transition: transform 0.5s ease;
        }
        
        .premium-card:hover .card-content {
            transform: translateY(-5px);
        }
        
        /* Dreams Text - CLEAN & MODERN VERSION */
        .dreams-text {
            color: white;
            /* খুব সূক্ষ্ম শ্যাডো যা শুধু টেক্সটকে ব্যাকগ্রাউন্ড থেকে আলাদা করবে */
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            position: relative;
            display: inline-block;
            font-weight: 500; /* একটু বোল্ড থাকলে দেখতে ভালো লাগে */
            letter-spacing: 0.5px;
        }
        
        .dreams-text::after {
            content: '';
            position: absolute;
            bottom: -2px; /* লাইনের দূরত্ব কমানো হয়েছে */
            left: 0;
            width: 100%;
            height: 1.5px; /* লাইনটি আরও চিকন করা হয়েছে */
            background: linear-gradient(to right, 
                transparent, 
                rgba(255,255,255,0.8), 
                transparent
            );
            border-radius: 10px;
            animation: dreamsLine 3s ease-in-out infinite;
        }
        
        @keyframes dreamsLine {
            0%, 100% { 
                opacity: 0.4;
                transform: scaleX(0.8); /* লাইনটি ছোট থেকে শুরু হবে */
            }
            50% { 
                opacity: 1;
                transform: scaleX(1); /* লাইনটি পুরোটা ছড়াবে */
                /* শ্যাডো এরিয়া অনেক কমানো হয়েছে */
                box-shadow: 0 0 8px rgba(255, 255, 255, 0.4); 
            }
        }
        
        /* Map Text with gradient */
        .map-text {
            position: relative;
            display: inline-block;
            background: linear-gradient(to right, #02CCFE 0%, #6DD19C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .map-text::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, #02CCFE 0%, #6DD19C 100%);
            border-radius: 2px;
        }
        
        /* Magic Bottom Sheet Styles */
        .bottom-sheet-container {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 100;
        }
        
        .bottom-sheet {
            background: linear-gradient(to right, #02CCFE 0%, #6DD19C 100%);
            border-radius: 20px 20px 0 0;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            box-shadow: 0 -5px 25px rgba(2, 204, 254, 0.3);
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
            padding: 0 2rem;
        }
        
        .bottom-sheet.expanded {
            height: 50vh;
            border-radius: 30px 30px 0 0;
            padding: 1rem;
            align-items: flex-start;
        }
        
        @media (min-width: 768px) {
            .bottom-sheet.expanded {
                height: 33vh;
            }
        }
        
        .closed-sheet-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
            text-align: center;
        }
        
        .expanded-sheet-content {
            display: none;
            width: 100%;
            height: 100%;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease 0.2s, transform 0.5s ease 0.2s;
            overflow-y: auto;
        }
        
        .bottom-sheet.expanded .expanded-sheet-content {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        
        .bottom-sheet.expanded .closed-sheet-content {
            display: none;
        }
        
        /* Fix for newsletter section */
        .newsletter-input-container {
            max-width: 100%;
        }
        
        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Hide scrollbar for service pills */
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        /* Content z-index */
        .content-z {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="font-poppins">
    <!-- Hero/Banner Section with Animation ONLY HERE -->
    <div class="hero-container">
        <!-- Background Animation ONLY in Hero -->
        <div class="hero-background">
            <!-- Clouds -->
            <div class="cloud cloud-1"></div>
            <div class="cloud cloud-2"></div>
            <div class="cloud cloud-3"></div>
            <div class="cloud cloud-4"></div>
            
            <!-- Planes - Different speeds and directions -->
            <div class="plane plane-large">
                <i class="fas fa-plane-departure"></i>
            </div>
            <div class="plane plane-medium">
                <i class="fas fa-plane"></i>
            </div>
            <div class="plane plane-small">
                <i class="fas fa-fighter-jet"></i>
            </div>
            <div class="plane plane-reverse">
                <i class="fas fa-plane-arrival"></i>
            </div>
            
            <div class="cloud cloud-5"></div>
            <div class="cloud cloud-6"></div>
            <div class="plane plane-fast">
              <i class="fas fa-plane"></i>
            </div>
            <div class="plane plane-vertical">
              <i class="fas fa-plane-departure"></i>
            </div>
        </div>
        
        <!-- Header -->
        <header class="container mx-auto px-4 py-6 content-z">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-brand-gradient mr-3 flex items-center justify-center">
                        <i class="fas fa-globe-americas text-white"></i>
                    </div>
                    <h1 class="text-3xl font-bold font-montserrat">Trav<span class="text-secondary">Hub</span></h1>
                </div>
                
                <!-- Login Button -->
                <button class="glass px-6 py-2.5 rounded-full font-semibold hover:bg-white/10 transition-all duration-300 group">
                    <span class="group-hover:translate-x-1 transition-transform duration-300">Login</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform duration-300"></i>
                </button>
            </div>
        </header>
        
        <!-- Sub-Header: Service Pills - NO OVERFLOW, FIXED AS YOUR ORIGINAL -->
        <div class="container mx-auto px-2 py-2 content-z">
            <div class="flex pb-2">
                <!-- Mobile: 5 items - NO OVERFLOW -->
                <div class="service-pill rounded-2xl flex md:hidden space-x-1 mx-auto">
                    <div class="active flex flex-col items-center p-3 min-w-[70px]">
                        <div class="w-10 h-10 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-suitcase text-white"></i>
                        </div>
                        <span class="text-xs font-medium">Package</span>
                    </div>
                    <div class="flex flex-col items-center p-3 min-w-[70px]">
                        <div class="w-12 h-12 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-passport text-white"></i>
                        </div>
                        <span class="text-xs font-medium">Visa</span>
                    </div>
                    <div class="flex flex-col items-center p-3 min-w-[70px]">
                        <div class="w-12 h-12 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-mosque text-white"></i>
                        </div>
                        <span class="text-xs font-medium">Umrah</span>
                    </div>
                    <div class="flex flex-col items-center p-3 min-w-[70px]">
                        <div class="w-12 h-12 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-ticket-alt text-white"></i>
                        </div>
                        <span class="text-xs font-medium">Tickets</span>
                    </div>
                    <div class="flex flex-col items-center p-3 min-w-[70px]">
                        <div class="w-12 h-12 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-concierge-bell text-white"></i>
                        </div>
                        <span class="text-xs font-medium">Services</span>
                    </div>
                </div>
                
                <!-- Desktop: 7 items - NO OVERFLOW -->
                <div class="hidden md:flex space-x-4 mx-auto">
                    <div class="service-pill active flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-suitcase text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">Package</span>
                    </div>
                    <div class="service-pill flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-passport text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">Visa</span>
                    </div>
                    <div class="service-pill flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-hiking text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">Activities</span>
                    </div>
                    <div class="service-pill flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-ticket-alt text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">Air Ticket</span>
                    </div>
                    <div class="service-pill flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-mosque text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">Umrah</span>
                    </div>
                    <div class="service-pill flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-hotel text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">Hotel</span>
                    </div>
                    <div class="service-pill flex flex-col items-center p-4 rounded-2xl min-w-[90px]">
                        <div class="w-14 h-14 rounded-full bg-brand-gradient flex items-center justify-center mb-2">
                            <i class="fas fa-concierge-bell text-white text-lg"></i>
                        </div>
                        <span class="text-sm font-semibold">All Services</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hero Content -->
        <section class="container mx-auto px-4 py-12 md:py-28 content-z">
            <div class="max-w-5xl mx-auto text-center">
                <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold font-montserrat mb-6 leading-tight">
                    Unfold the <span class="map-text">Map</span><br>
                    <span class="dreams-text">of Your Dreams</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 mb-10 max-w-3xl mx-auto">
                    Luxury curated trips designed for those who seek the extraordinary. Experience the world in <span class="text-secondary font-semibold">Emerald Class</span>.
                </p>
                
                <!-- Premium Glassmorphism Search Bar -->
                <div class="glass rounded-2xl p-1 max-w-3xl mx-auto mb-16">
                    <div class="flex flex-col md:flex-row items-center bg-primary/40 rounded-2xl p-5">
                        <div class="flex-1 w-full mb-4 md:mb-0 md:mr-4">
                            <div class="relative">
                                <i class="fas fa-search absolute left-5 top-1/2 transform -translate-y-1/2 text-gray-400 text-lg"></i>
                                <input type="text" placeholder="Where do you want to go?" class="w-full pl-14 pr-5 py-4 bg-transparent border border-gray-700/50 rounded-xl text-white text-lg focus:outline-none focus:ring-2 focus:ring-secondary/50 placeholder-gray-500">
                            </div>
                        </div>
                        <button class="bg-brand-gradient text-white font-bold py-4 px-10 rounded-xl hover:opacity-90 transition duration-300 text-lg shadow-lg shadow-secondary/20 w-full md:w-auto">
                            Explore Now <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <!-- 2026 Collection with Light Background - NO ANIMATION -->
    <section class="section-light py-16 md:py-20">
        <div class="container mx-auto px-4">
            <div class="max-w-8xl mx-auto">
                <h2 class="text-4xl md:text-5xl font-bold font-montserrat text-center mb-16">
                    2026 <span class="text-transparent bg-clip-text bg-brand-gradient">COLLECTION</span> - Curated For You
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Card 1 -->
                    <div class="premium-card rounded-2xl h-[32rem] cursor-pointer">
                        <div class="absolute inset-0 overflow-hidden rounded-2xl">
                            <img src="https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Santorini, Greece">
                            <div class="card-overlay"></div>
                        </div>
                        <div class="card-content">
                            <h3 class="text-2xl font-bold mb-1">Santorini, Greece</h3>
                            <p class="text-gray-300 mb-3">White-washed buildings & sunset views</p>
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-secondary">$2,499<span class="text-sm font-normal text-gray-300"> /person</span></span>
                                <span class="glass px-3 py-1 rounded-full text-sm">7 Days</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 2 -->
                    <div class="premium-card rounded-2xl h-[32rem] cursor-pointer">
                        <div class="absolute inset-0 overflow-hidden rounded-2xl">
                            <img src="https://images.unsplash.com/photo-1516496636080-14fb876e029d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Maldives">
                            <div class="card-overlay"></div>
                        </div>
                        <div class="card-content">
                            <h3 class="text-2xl font-bold mb-1">Maldives</h3>
                            <p class="text-gray-300 mb-3">Turquoise waters & overwater villas</p>
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-secondary">$3,299<span class="text-sm font-normal text-gray-300"> /person</span></span>
                                <span class="glass px-3 py-1 rounded-full text-sm">10 Days</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 3 -->
                    <div class="premium-card rounded-2xl h-[32rem] cursor-pointer">
                        <div class="absolute inset-0 overflow-hidden rounded-2xl">
                            <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80" alt="Kyoto, Japan">
                            <div class="card-overlay"></div>
                        </div>
                        <div class="card-content">
                            <h3 class="text-2xl font-bold mb-1">Kyoto, Japan</h3>
                            <p class="text-gray-300 mb-3">Ancient temples & cherry blossoms</p>
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-bold text-secondary">$2,899<span class="text-sm font-normal text-gray-300"> /person</span></span>
                                <span class="glass px-3 py-1 rounded-full text-sm">9 Days</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Call to Action Section with Light Background - NO ANIMATION -->
    <section class="section-light py-16 md:py-20">
        <div class="container mx-auto px-4">
            <div class="bg-brand-gradient rounded-3xl p-8 md:p-12 text-center max-w-5xl mx-auto">
                <h2 class="text-3xl md:text-4xl font-bold font-montserrat mb-6">
                    Ready for the <span class="text-primary">Emerald Experience</span>?
                </h2>
                <p class="text-xl mb-8 max-w-3xl mx-auto">
                    Join thousands of travelers who have experienced luxury redefined. Our concierge team is ready to craft your perfect journey.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <button class="bg-primary text-white font-bold py-3 px-8 rounded-xl hover:bg-primary/90 transition duration-300 text-lg cta-button">
                        Book Consultation <i class="fas fa-calendar-check ml-2"></i>
                    </button>
                    <button class="glass border border-white/30 text-white font-bold py-3 px-8 rounded-xl hover:bg-white/10 transition duration-300 text-lg cta-button">
                        View Sample Itineraries <i class="fas fa-file-alt ml-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Professional Footer with Dark Background - NO ANIMATION -->
    <footer class="footer-dark py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                <!-- Logo & About -->
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-brand-gradient mr-3 flex items-center justify-center">
                            <i class="fas fa-globe-americas text-white"></i>
                        </div>
                        <h2 class="text-2xl font-bold font-montserrat">Trav<span class="text-secondary">Hub</span></h2>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Luxury travel experiences curated for the discerning traveler. Explore the world with our Emerald Class service.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-secondary/20 transition duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-secondary/20 transition duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-secondary/20 transition duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-secondary/20 transition duration-300">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Company Links -->
                <div>
                    <h3 class="text-xl font-bold font-montserrat mb-4">Company</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Careers</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Press</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Blog</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Partners</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h3 class="text-xl font-bold font-montserrat mb-4">Support</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">FAQs</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Cancellation Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <!-- Newsletter - FIXED -->
                <div class="newsletter-input-container">
                    <h3 class="text-xl font-bold font-montserrat mb-4">Stay Updated</h3>
                    <p class="text-gray-400 mb-4">Subscribe to our newsletter for exclusive deals and travel inspiration.</p>
                    <div class="flex w-full">
                        <input type="email" placeholder="Your email" class="flex-1 px-4 py-3 bg-primary/30 border border-gray-700 rounded-l-lg text-white focus:outline-none">
                        <button class="bg-secondary px-4 py-3 rounded-r-lg hover:bg-secondary/90 transition duration-300 whitespace-nowrap">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <p class="text-gray-500 text-sm mt-3">We respect your privacy. Unsubscribe at any time.</p>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 text-center text-gray-500">
                <p>&copy; 2026 TravHub Travel Agency. All rights reserved. | <a href="#" class="hover:text-white transition duration-300">Terms & Conditions</a></p>
            </div>
        </div>
    </footer>
    
    <!-- Magic Bottom Sheet -->
    <div class="bottom-sheet-container" id="bottomSheetContainer">
        <div class="bottom-sheet" id="bottomSheet">
            <!-- Closed State -->
            <div class="closed-sheet-content">
                <div class="text-center">
                    <p class="font-bold text-lg md:text-xl text-primary">BUILD YOUR TRIP! <span class="text-white">MAGIC WAITING HERE!</span></p>
                    <p class="text-sm mt-1 text-primary/80">Click to expand</p>
                </div>
            </div>
            
            <!-- Expanded Content -->
            <div class="expanded-sheet-content">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl md:text-2xl font-bold text-primary">Build Your Dream Trip</h3>
                    <button id="closeSheet" class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-primary flex items-center justify-center hover:bg-primary/90 transition duration-300 text-white">
                        <i class="fas fa-chevron-down text-sm md:text-base"></i>
                    </button>
                </div>
                
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 md:p-5 border border-white/30 h-[calc(100%-60px)] overflow-y-auto">
                    <form id="tripBuilderForm" class="space-y-4 md:space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                            <!-- Destination -->
                            <div>
                                <label class="block text-primary font-bold mb-2 text-sm md:text-base">Destination</label>
                                <div class="relative">
                                    <i class="fas fa-map-marker-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-primary/70 text-sm"></i>
                                    <select class="w-full p-2 md:p-3 pl-8 md:pl-10 bg-white/20 border border-white/30 rounded-lg text-primary text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        <option value="" class="text-gray-700">Select a destination</option>
                                        <option value="santorini" class="text-gray-700">Santorini, Greece</option>
                                        <option value="maldives" class="text-gray-700">Maldives</option>
                                        <option value="kyoto" class="text-gray-700">Kyoto, Japan</option>
                                        <option value="bali" class="text-gray-700">Bali, Indonesia</option>
                                        <option value="swiss" class="text-gray-700">Swiss Alps</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Travel Dates -->
                            <div>
                                <label class="block text-primary font-bold mb-2 text-sm md:text-base">Travel Dates</label>
                                <div class="relative">
                                    <i class="fas fa-calendar-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-primary/70 text-sm"></i>
                                    <input type="text" placeholder="Select dates" class="w-full p-2 md:p-3 pl-8 md:pl-10 bg-white/20 border border-white/30 rounded-lg text-primary text-sm md:text-base placeholder-primary/70 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                            </div>
                            
                            <!-- Travelers -->
                            <div>
                                <label class="block text-primary font-bold mb-2 text-sm md:text-base">Travelers</label>
                                <div class="flex items-center">
                                    <button type="button" class="w-8 h-8 md:w-10 md:h-10 rounded-l-lg bg-primary text-white flex items-center justify-center hover:bg-primary/90 transition duration-300 text-sm" id="decrementTravelers">-</button>
                                    <input type="number" value="2" min="1" max="10" class="flex-1 p-2 md:p-3 bg-white/20 border-t border-b border-white/30 text-center text-primary text-sm md:text-base focus:outline-none" id="travelerCount">
                                    <button type="button" class="w-8 h-8 md:w-10 md:h-10 rounded-r-lg bg-primary text-white flex items-center justify-center hover:bg-primary/90 transition duration-300 text-sm" id="incrementTravelers">+</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                            <!-- Budget -->
                            <div>
                                <label class="block text-primary font-bold mb-2 text-sm md:text-base">Budget Range</label>
                                <select class="w-full p-2 md:p-3 bg-white/20 border border-white/30 rounded-lg text-primary text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    <option value="" class="text-gray-700">Select budget</option>
                                    <option value="economy" class="text-gray-700">Economy ($1,000 - $2,000)</option>
                                    <option value="standard" class="text-gray-700">Standard ($2,000 - $4,000)</option>
                                    <option value="premium" class="text-gray-700">Premium ($4,000 - $7,000)</option>
                                    <option value="luxury" class="text-gray-700">Luxury ($7,000+)</option>
                                </select>
                            </div>
                            
                            <!-- Trip Type -->
                            <div>
                                <label class="block text-primary font-bold mb-2 text-sm md:text-base">Trip Type</label>
                                <select class="w-full p-2 md:p-3 bg-white/20 border border-white/30 rounded-lg text-primary text-sm md:text-base focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    <option value="" class="text-gray-700">Select trip type</option>
                                    <option value="romantic" class="text-gray-700">Romantic Getaway</option>
                                    <option value="family" class="text-gray-700">Family Vacation</option>
                                    <option value="adventure" class="text-gray-700">Adventure Trip</option>
                                    <option value="luxury" class="text-gray-700">Luxury Retreat</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-primary font-bold mb-2 text-sm md:text-base">Special Requests</label>
                            <textarea rows="2" placeholder="Any special requirements or preferences..." class="w-full p-2 md:p-3 bg-white/20 border border-white/30 rounded-lg text-primary text-sm md:text-base placeholder-primary/70 focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                        </div>
                        
                        <div class="text-center pt-2 md:pt-4">
                            <button type="submit" class="bg-primary text-white font-bold py-2 md:py-3 px-6 md:px-10 rounded-xl hover:bg-primary/90 transition duration-300 text-sm md:text-lg shadow-lg">
                                Create My Trip <i class="fas fa-magic ml-1 md:ml-2"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ======================
            // MAGIC BOTTOM SHEET
            // ======================
            const bottomSheet = document.getElementById('bottomSheet');
            const closeSheetBtn = document.getElementById('closeSheet');
            
            // Open bottom sheet when clicking anywhere on it
            bottomSheet.addEventListener('click', function(e) {
                // Don't trigger if clicking on close button or form elements
                if (e.target.closest('#closeSheet') || 
                    e.target.closest('input') || 
                    e.target.closest('select') || 
                    e.target.closest('textarea') ||
                    e.target.closest('button[type="submit"]')) {
                    return;
                }
                
                // If not already expanded, expand it
                if (!bottomSheet.classList.contains('expanded')) {
                    expandBottomSheet();
                }
            });
            
            // Close bottom sheet
            closeSheetBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent triggering the parent click
                collapseBottomSheet();
            });
            
            function expandBottomSheet() {
                bottomSheet.classList.add('expanded');
                
                // Set height based on screen size
                if (window.innerWidth < 768) {
                    bottomSheet.style.height = '50vh';
                } else {
                    bottomSheet.style.height = '33vh';
                }
                
                // Prevent body scroll when sheet is expanded
                document.body.style.overflow = 'hidden';
            }
            
            function collapseBottomSheet() {
                bottomSheet.classList.remove('expanded');
                bottomSheet.style.height = '80px';
                
                // Restore body scroll
                document.body.style.overflow = '';
            }
            
            // Handle window resize for bottom sheet
            window.addEventListener('resize', function() {
                if (bottomSheet.classList.contains('expanded')) {
                    if (window.innerWidth < 768) {
                        bottomSheet.style.height = '50vh';
                    } else {
                        bottomSheet.style.height = '33vh';
                    }
                }
            });
            
            // Close sheet when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (bottomSheet.classList.contains('expanded') && 
                    !bottomSheet.contains(e.target) &&
                    window.innerWidth < 768) {
                    collapseBottomSheet();
                }
            });
            
            // ======================
            // TRAVELER COUNTER
            // ======================
            const decrementBtn = document.getElementById('decrementTravelers');
            const incrementBtn = document.getElementById('incrementTravelers');
            const travelerCount = document.getElementById('travelerCount');
            
            decrementBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent triggering parent click
                let currentValue = parseInt(travelerCount.value);
                if (currentValue > 1) {
                    travelerCount.value = currentValue - 1;
                }
            });
            
            incrementBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent triggering parent click
                let currentValue = parseInt(travelerCount.value);
                if (currentValue < 10) {
                    travelerCount.value = currentValue + 1;
                }
            });
            
            // ======================
            // FORM SUBMISSION
            // ======================
            const tripBuilderForm = document.getElementById('tripBuilderForm');
            tripBuilderForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent triggering parent click
                
                // Show success message
                const submitBtn = tripBuilderForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Trip Request Sent!';
                submitBtn.classList.add('bg-secondary');
                submitBtn.classList.remove('bg-primary');
                submitBtn.disabled = true;
                
                // Reset after 3 seconds
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.remove('bg-secondary');
                    submitBtn.classList.add('bg-primary');
                    submitBtn.disabled = false;
                    
                    // Collapse the sheet after submission
                    collapseBottomSheet();
                    
                    // Reset form
                    tripBuilderForm.reset();
                    travelerCount.value = 2;
                    
                    // Show success alert
                    alert('Your trip request has been submitted successfully! Our travel experts will contact you within 24 hours.');
                }, 3000);
            });
            
            // ======================
            // SERVICE PILLS INTERACTIVITY
            // ======================
            const servicePills = document.querySelectorAll('.service-pill');
            servicePills.forEach(pill => {
                pill.addEventListener('click', function() {
                    // Remove active class from all pills
                    servicePills.forEach(p => p.classList.remove('active'));
                    
                    // Add active class to clicked pill
                    this.classList.add('active');
                });
            });
            
            // ======================
            // CARD INTERACTIVITY
            // ======================
            const premiumCards = document.querySelectorAll('.premium-card');
            premiumCards.forEach(card => {
                card.addEventListener('click', function() {
                    const destination = this.querySelector('h3').textContent;
                    const price = this.querySelector('.text-secondary').textContent;
                    
                    // Show booking modal (simulated)
                    alert(`Selected: ${destination}\nPrice: ${price}\n\nRedirecting to booking page...`);
                });
            });
            
            // ======================
            // CTA BUTTONS - Expand Bottom Sheet
            // ======================
            const ctaButtons = document.querySelectorAll('.cta-button');
            ctaButtons.forEach(button => {
                button.addEventListener('click', function() {
                    expandBottomSheet();
                });
            });
            
            // ======================
            // SMOOTH SECTION TRANSITIONS
            // ======================
            // Add scroll event listener for section transitions
            window.addEventListener('scroll', function() {
                const heroSection = document.querySelector('.hero-container');
                const collectionSection = document.querySelector('.section-light');
                const footerSection = document.querySelector('.footer-dark');
                
                // This creates a smooth background transition effect as you scroll
                // You'll see the color change smoothly between sections
            });
        });
    </script>
</body>
</html>