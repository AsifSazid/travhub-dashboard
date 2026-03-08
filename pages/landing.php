<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravelEase | Discover Your Next Adventure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1A2039;
            --secondary: #02CCFE;
            --accent: #FF7E5F;
            --light: #F8FAFC;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--primary);
            color: white;
            overflow-x: hidden;
            line-height: 1.6;
        }
        
        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }
        
        /* Main container with proper spacing */
        .main-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }
        
        /* Card styling with consistent margins */
        .content-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
        }
        
        /* Section spacing */
        .section {
            padding: 60px 0;
        }
        
        .section-header {
            margin-bottom: 40px;
        }
        
        /* Typing animation */
        .typing-container {
            display: inline-block;
            position: relative;
        }
        
        .typing-text {
            /*border-right: 3px solid var(--secondary);*/
            white-space: nowrap;
            overflow: hidden;
            display: inline-block;
        }
        
        /* Airplane animation */
        .airplane {
            position: fixed;
            z-index: 0;
            opacity: 0.08;
            pointer-events: none;
            font-size: 24px;
            color: white;
        }
        
        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        /* Bottom sheet */
        .bottom-sheet-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }
        
        .bottom-sheet {
            width: 100%;
            background: var(--primary);
            border-radius: 24px 24px 0 0;
            box-shadow: 0 -20px 50px rgba(0, 0, 0, 0.3);
            transform: translateY(calc(100% - 80px));
            transition: transform 0.4s cubic-bezier(0.32, 0.72, 0, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }
        
        .bottom-sheet.active {
            transform: translateY(30%);
        }
        
        .bottom-sheet.fullscreen {
            transform: translateY(5%);
            border-radius: 0;
            height: 95vh;
        }
        
        .sheet-handle {
            width: 100%;
            padding: 16px 0 8px;
            display: flex;
            justify-content: center;
            cursor: pointer;
        }
        
        .handle-bar {
            width: 60px;
            height: 5px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .bottom-sheet-content {
            padding: 0 24px 24px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
        }
        
        .bottom-sheet.active .bottom-sheet-content,
        .bottom-sheet.fullscreen .bottom-sheet-content {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .bottom-sheet.fullscreen .bottom-sheet-content {
            max-height: 85vh;
        }
        
        /* Backdrop */
        .backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .backdrop.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Hide scrollbar when bottom sheet is open */
        body.no-scroll {
            overflow: hidden;
        }
        
        /* Card hover effects */
        .travel-card {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
        }
        
        .travel-card:hover {
            transform: translateY(-8px);
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--secondary);
        }
        
        /* Floating action button */
        .fab {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), #6DD19C);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 10px 25px rgba(80, 188, 129, 0.4);
            z-index: 90;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 30px rgba(80, 188, 129, 0.6);
        }
        
        /* Category chips */
        .category-chip {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            color: white;
            transition: all 0.2s ease;
            cursor: pointer;
            margin: 4px;
        }
        
        .category-chip.active,
        .category-chip:hover {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        /* Price badge */
        .price-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(26, 32, 57, 0.9);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            color: var(--secondary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(80, 188, 129, 0.3);
        }
        
        /* Tag badges */
        .tag {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        
        .tag.bestseller {
            background: rgba(255, 126, 95, 0.2);
            color: #FF7E5F;
            border: 1px solid rgba(255, 126, 95, 0.3);
        }
        
        .tag.luxury {
            background: rgba(139, 92, 246, 0.2);
            color: #8B5CF6;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }
        
        .tag.best-value {
            background: rgba(80, 188, 129, 0.2);
            color: var(--secondary);
            border: 1px solid rgba(80, 188, 129, 0.3);
        }
        
        /* Stats cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--secondary);
        }
        
        /* Form elements */
        .form-input {
            width: 100%;
            padding: 16px 20px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            color: white;
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(80, 188, 129, 0.2);
        }
        
        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: white;
        }
        
        /* Budget slider */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 6px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--secondary);
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(80, 188, 129, 0.5);
        }
        
        /* Selection cards */
        .selection-card {
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            background: rgba(255, 255, 255, 0.03);
        }
        
        .selection-card:hover,
        .selection-card.selected {
            border-color: var(--secondary);
            background: rgba(80, 188, 129, 0.1);
        }
        
        /* CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 16px 32px;
            background: linear-gradient(135deg, var(--secondary), #6DD19C);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(80, 188, 129, 0.4);
        }
        
        .cta-button.secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .cta-button.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Mobile bottom nav */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(26, 32, 57, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 -5px 30px rgba(0, 0, 0, 0.3);
            z-index: 80;
            display: flex;
            justify-content: space-around;
            padding: 12px 0 20px;
        }
        
        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            transition: color 0.2s ease;
            padding: 8px 12px;
        }
        
        .mobile-nav-item.active,
        .mobile-nav-item:hover {
            color: var(--secondary);
        }
        
        .mobile-nav-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        
        /* Desktop top nav */
        .desktop-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(26, 32, 57, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 80;
            padding: 20px 40px;
        }
        
        /* Animation keyframes */
        @keyframes float {
            0%, 100% {
                transform: translateY(0) translateX(0);
            }
            50% {
                transform: translateY(-10px) translateX(5px);
            }
        }
        
        @keyframes flyAcross {
            0% {
                transform: translateX(-100px) translateY(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.1;
            }
            90% {
                opacity: 0.1;
            }
            100% {
                transform: translateX(calc(100vw + 100px)) translateY(0) rotate(5deg);
                opacity: 0;
            }
        }
        
        .floating {
            animation: float 8s ease-in-out infinite;
        }
        
        .flying {
            animation: flyAcross 40s linear infinite;
        }
        
        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #FFFFFF, var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }
            
            .section {
                padding: 40px 0;
            }
            
            .section-header {
                margin-bottom: 30px;
            }
            
            .main-container {
                padding: 0 16px;
            }
            
            .fab {
                bottom: 80px;
                right: 16px;
                width: 56px;
                height: 56px;
            }
            
            .bottom-sheet-content {
                padding: 0 20px 20px;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-nav {
                display: none;
            }
            
            .bottom-sheet-container {
                display: none;
            }
            
            .fab {
                display: none;
            }
        }
        
        /* Fix for Tailwind spacing conflicts */
        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }
        
        .space-y-4 > * + * {
            margin-top: 1rem;
        }
        
        /* Ensure images don't overflow */
        img {
            max-width: 100%;
            height: auto;
        }
        
        /* Fix for grid gaps */
        .grid-gap-6 {
            gap: 1.5rem;
        }
        
        /* Custom scrollbar for dark theme */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <!-- Desktop Navigation -->
    <nav class="desktop-nav hidden lg:flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] rounded-full flex items-center justify-center">
                <i class="fas fa-plane text-white text-lg"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Travel<span class="text-[#02CCFE]">Ease</span></h1>
        </div>
        
        <div class="flex-1 max-w-xl mx-8">
            <div class="relative">
                <input type="text" placeholder="Search destinations, hotels, activities..." class="w-full px-6 py-3 rounded-full bg-white/5 border border-white/20 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-[#02CCFE] focus:border-transparent">
                <button class="absolute right-4 top-3 text-white/60">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <div class="flex items-center space-x-8">
            <a href="#" class="font-medium text-white/80 hover:text-[#02CCFE] transition">Tours</a>
            <a href="#" class="font-medium text-white/80 hover:text-[#02CCFE] transition">Flights</a>
            <a href="#" class="font-medium text-white/80 hover:text-[#02CCFE] transition">Visa</a>
            <a href="#" class="font-medium text-white/80 hover:text-[#02CCFE] transition">Contact</a>
            <button class="px-6 py-2 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] text-white rounded-full font-medium hover:shadow-lg transition">Login</button>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav lg:hidden">
        <a href="#" class="mobile-nav-item active">
            <i class="fas fa-home mobile-nav-icon"></i>
            <span>Home</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-search mobile-nav-icon"></i>
            <span>Search</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-suitcase mobile-nav-icon"></i>
            <span>Packages</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-bookmark mobile-nav-icon"></i>
            <span>Saved</span>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-user mobile-nav-icon"></i>
            <span>Profile</span>
        </a>
    </nav>

    <!-- Hero Section -->
    <section class="section relative pt-20 lg:pt-24">
        <div class="main-container relative content-card mt-4 p-6 overflow-hidden">
    
            <!-- Background Image -->
            <div class="absolute inset-0 z-0">
            <img 
                src="../assets/images/banner-one.png"
                alt="Santorini, Greece"
                class="w-full h-full 
                       object-cover 
                       sm:object-contain 
                       object-center"
            />
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40"></div>
            </div>
    
            <!-- Content -->
            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between">
    
                <!-- Left Content -->
                <div class="lg:w-1/2 mb-12 lg:mb-0 lg:pr-12 text-white">
    
                    <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] rounded-full text-sm font-medium mb-6">
                        <i class="fas fa-plane-departure mr-2"></i>
                        Your Next Adventure Awaits
                    </div>
    
                    <h1 class="text-4xl lg:text-5xl xl:text-6xl font-bold mb-6 leading-tight">
                        <span class="gradient-text">
                            <span id="typing-text" class="typing-text">
                                Build Your Dream Trip
                            </span>
                        </span>
                    </h1>
    
                    <p class="text-lg lg:text-xl text-white/80 mb-8">
                        Explore breathtaking destinations, discover hidden gems, and create unforgettable memories with our personalized travel packages.
                    </p>
    
                    <div class="flex flex-wrap gap-4 mb-12">
                        <button class="cta-button flex items-center">
                            <i class="fas fa-play-circle mr-3"></i> Watch Our Story
                        </button>
                        <button class="cta-button secondary">
                            Explore Destinations
                        </button>
                    </div>
    
                    <div class="grid grid-cols-3 gap-4">
                        <div class="stat-card">
                            <h3 class="text-3xl font-bold">250+</h3>
                            <p class="text-white/70">Destinations</p>
                        </div>
                        <div class="stat-card">
                            <h3 class="text-3xl font-bold text-[#02CCFE]">5k+</h3>
                            <p class="text-white/70">Happy Travelers</p>
                        </div>
                        <div class="stat-card">
                            <h3 class="text-3xl font-bold text-[#FF7E5F]">24/7</h3>
                            <p class="text-white/70">Support</p>
                        </div>
                    </div>
                </div>
    
                <!-- Right Card -->
                <div class="lg:w-1/2 relative mx-4">
                    <div class="content-card floating overflow-hidden bg-black/60 backdrop-blur">
    
                        <img 
                            src="https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1474&q=80" 
                            alt="Mountain Landscape"
                            class="w-full h-64 lg:h-80 object-cover"
                        >
    
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold text-white">
                                    Swiss Alps Adventure
                                </h3>
                                <span class="px-4 py-2 bg-[#FF7E5F] text-white rounded-full font-bold">
                                    $1,299
                                </span>
                            </div>
    
                            <p class="text-white/80 mb-6">
                                Experience the breathtaking beauty of the Swiss Alps with guided hikes, cozy chalets, and alpine cuisine.
                            </p>
    
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <i class="fas fa-star text-amber-400 mr-1"></i>
                                    <span class="font-medium text-white">4.8</span>
                                    <span class="text-white/60 ml-1">(124 reviews)</span>
                                </div>
                                <button class="px-6 py-3 bg-[#02CCFE] text-white rounded-full font-medium hover:bg-[#6DD19C] transition">
                                    Book Now
                                </button>
                            </div>
                        </div>
    
                    </div>
                </div>
    
            </div>
        </div>
    </section>


    <!-- Travel Feed Section -->
    <section class="section">
        <div class="main-container">
            <div class="section-header">
                <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">Popular <span class="text-[#02CCFE]">Destinations</span></h2>
                <p class="text-white/70 mb-6">Discover our most sought-after travel experiences</p>
                
                <div class="flex flex-wrap gap-2">
                    <button class="category-chip active">All</button>
                    <button class="category-chip">Beaches</button>
                    <button class="category-chip">Mountains</button>
                    <button class="category-chip">Cities</button>
                    <button class="category-chip">Adventure</button>
                </div>
            </div>
            
            <!-- Travel Feed Grid -->
            <div id="travel-feed" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <!-- Card 1 -->
                <div class="travel-card">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1534008897995-27a23e859048?ixlib=rb-4.0.3&auto=format&fit=crop&w=1474&q=80" alt="Santorini, Greece" class="w-full h-64 object-cover">
                        <div class="price-badge">$899</div>
                        <div class="absolute top-4 left-4">
                            <span class="tag bestseller">Bestseller</span>
                        </div>
                        <button class="absolute bottom-4 right-4 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white/20">
                            <i class="far fa-bookmark text-white"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-white mb-2">Santorini, Greece</h3>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-white/50 text-sm mr-2"></i>
                                <p class="text-white/70">Cyclades, Greece</p>
                            </div>
                        </div>
                        <p class="text-white/80 mb-6">Experience the iconic white-washed buildings, stunning sunsets, and crystal-clear waters of the Aegean Sea.</p>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <i class="fas fa-star text-amber-400 mr-1"></i>
                                <span class="font-medium text-white">4.9</span>
                                <span class="text-white/60 ml-1">(342 reviews)</span>
                            </div>
                            <button class="px-6 py-3 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] text-white rounded-full font-medium hover:shadow-lg transition">Book Now</button>
                        </div>
                    </div>
                </div>
                
                <!-- Card 2 -->
                <div class="travel-card">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1474&q=80" alt="Kyoto, Japan" class="w-full h-64 object-cover">
                        <div class="price-badge">$1,199</div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-white mb-2">Kyoto, Japan</h3>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-white/50 text-sm mr-2"></i>
                                <p class="text-white/70">Kansai Region, Japan</p>
                            </div>
                        </div>
                        <p class="text-white/80 mb-6">Immerse yourself in traditional Japanese culture with ancient temples, peaceful gardens, and geisha districts.</p>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <i class="fas fa-star text-amber-400 mr-1"></i>
                                <span class="font-medium text-white">4.7</span>
                                <span class="text-white/60 ml-1">(287 reviews)</span>
                            </div>
                            <button class="px-6 py-3 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] text-white rounded-full font-medium hover:shadow-lg transition">Book Now</button>
                        </div>
                    </div>
                </div>
                
                <!-- Card 3 -->
                <div class="travel-card">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80" alt="Maldives" class="w-full h-64 object-cover">
                        <div class="price-badge">$2,499</div>
                        <div class="absolute top-4 left-4">
                            <span class="tag luxury">Luxury</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-white mb-2">Maldives Resort</h3>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-white/50 text-sm mr-2"></i>
                                <p class="text-white/70">Indian Ocean</p>
                            </div>
                        </div>
                        <p class="text-white/80 mb-6">Stay in overwater bungalows, snorkel in crystal clear lagoons, and experience ultimate luxury and relaxation.</p>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <i class="fas fa-star text-amber-400 mr-1"></i>
                                <span class="font-medium text-white">4.9</span>
                                <span class="text-white/60 ml-1">(512 reviews)</span>
                            </div>
                            <button class="px-6 py-3 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] text-white rounded-full font-medium hover:shadow-lg transition">Book Now</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button id="load-more" class="cta-button">
                    <i class="fas fa-compass mr-2"></i> Load More Destinations
                </button>
            </div>
        </div>
    </section>

    <!-- Floating Action Button (for mobile) -->
    <div class="fab lg:hidden" id="fab">
        <i class="fas fa-plus"></i>
    </div>

    <!-- Bottom Sheet for Mobile -->
    <div class="bottom-sheet-container lg:hidden">
        <!-- Backdrop -->
        <div class="backdrop" id="backdrop"></div>
        
        <!-- Bottom Sheet -->
        <div class="bottom-sheet" id="bottom-sheet">
            <!-- Sheet handle -->
            <div class="sheet-handle" id="sheet-handle">
                <div class="handle-bar"></div>
            </div>
            
            <!-- Build Package Button (Visible when collapsed) -->
            <div id="build-package-btn" class="px-6 py-6 flex justify-center">
                <button class="cta-button w-full max-w-md flex items-center justify-center">
                    <i class="fas fa-plus-circle mr-3"></i> Build Your Package
                </button>
            </div>
            
            <!-- Bottom Sheet Content -->
            <div class="bottom-sheet-content custom-scrollbar">
                <h3 class="text-2xl font-bold mb-6 text-center text-white">Build Your Dream Trip</h3>
                
                <div class="space-y-6">
                    <!-- Destination Selector -->
                    <div>
                        <label class="form-label">Destination Type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="selection-card" data-type="beach">
                                <i class="fas fa-umbrella-beach text-2xl text-blue-400 mb-3"></i>
                                <span class="font-medium text-white">Beach</span>
                            </div>
                            <div class="selection-card" data-type="mountain">
                                <i class="fas fa-mountain text-2xl text-emerald-400 mb-3"></i>
                                <span class="font-medium text-white">Mountain</span>
                            </div>
                            <div class="selection-card" data-type="city">
                                <i class="fas fa-city text-2xl text-amber-400 mb-3"></i>
                                <span class="font-medium text-white">City</span>
                            </div>
                            <div class="selection-card" data-type="adventure">
                                <i class="fas fa-hiking text-2xl text-purple-400 mb-3"></i>
                                <span class="font-medium text-white">Adventure</span>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="form-label">Specific Destination</label>
                            <select class="form-input">
                                <option value="">Select a destination</option>
                                <option value="bali">Bali, Indonesia</option>
                                <option value="santorini">Santorini, Greece</option>
                                <option value="kyoto">Kyoto, Japan</option>
                                <option value="swiss">Swiss Alps</option>
                                <option value="maldives">Maldives</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Date Picker -->
                    <div>
                        <label class="form-label">Travel Dates</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-white/70 text-sm mb-2">From</label>
                                <input type="date" class="form-input">
                            </div>
                            <div>
                                <label class="block text-white/70 text-sm mb-2">To</label>
                                <input type="date" class="form-input">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Budget Slider -->
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <label class="form-label">Budget</label>
                            <span id="budget-value" class="text-[#02CCFE] font-bold">$2,000</span>
                        </div>
                        <input type="range" id="budget-slider" min="500" max="10000" step="100" value="2000" class="w-full">
                        <div class="flex justify-between text-sm text-white/50 mt-2">
                            <span>$500</span>
                            <span>$5,000</span>
                            <span>$10,000+</span>
                        </div>
                    </div>
                    
                    <!-- Travelers Count -->
                    <div>
                        <label class="form-label">Travelers</label>
                        <div class="flex items-center space-x-6">
                            <button id="decrease-travelers" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-white/10">
                                <i class="fas fa-minus text-white"></i>
                            </button>
                            <div class="flex-1 text-center">
                                <span id="travelers-count" class="text-4xl font-bold text-[#02CCFE]">2</span>
                                <span class="block text-white/70">Travelers</span>
                            </div>
                            <button id="increase-travelers" class="w-12 h-12 rounded-full border border-white/20 flex items-center justify-center hover:bg-white/10">
                                <i class="fas fa-plus text-white"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Package Type -->
                    <div>
                        <label class="form-label">Package Type</label>
                        <div class="grid grid-cols-1 gap-3">
                            <label class="selection-card flex items-center">
                                <input type="radio" name="package-type" value="basic" class="mr-3" checked>
                                <div>
                                    <h4 class="font-medium text-white">Basic</h4>
                                    <p class="text-sm text-white/70">Flights + Hotel</p>
                                </div>
                            </label>
                            <label class="selection-card flex items-center">
                                <input type="radio" name="package-type" value="standard" class="mr-3">
                                <div>
                                    <h4 class="font-medium text-white">Standard</h4>
                                    <p class="text-sm text-white/70">+ Activities</p>
                                </div>
                            </label>
                            <label class="selection-card flex items-center">
                                <input type="radio" name="package-type" value="premium" class="mr-3">
                                <div>
                                    <h4 class="font-medium text-white">Premium</h4>
                                    <p class="text-sm text-white/70">All-Inclusive</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Continue Button -->
                    <div class="pt-4">
                        <button id="continue-btn" class="cta-button w-full">
                            Continue to Get Quote
                        </button>
                        <p class="text-center text-white/50 mt-3 text-sm">Get a personalized quote in under 24 hours</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Animations Container -->
    <div id="background-animations"></div>

    <script>
        // Typing Animation
        const phrases = [
            "Build Your Dream Trip",
            "Discover Bangladesh", 
            "Travel Smarter With Us",
            "Explore The World",
            "Create Memories"
        ];
        
        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        let isEnd = false;
        
        function typeEffect() {
            const currentPhrase = phrases[phraseIndex];
            const typingElement = document.getElementById('typing-text');
            
            if (isDeleting) {
                typingElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                typingElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }
            
            if (!isDeleting && charIndex === currentPhrase.length) {
                isEnd = true;
                setTimeout(() => {
                    isDeleting = true;
                }, 1500);
                return;
            }
            
            if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
            }
            
            const speed = isDeleting ? 50 : isEnd ? 100 : 150;
            setTimeout(typeEffect, speed);
        }
        
        // Initialize typing effect
        setTimeout(typeEffect, 1000);
        
        // Create background animations (airplanes)
        function createBackgroundAnimations() {
            const container = document.getElementById('background-animations');
            
            // Create airplanes
            for (let i = 0; i < 5; i++) {
                const airplane = document.createElement('div');
                airplane.className = 'airplane flying';
                airplane.innerHTML = '<i class="fas fa-plane"></i>';
                airplane.style.left = `-50px`;
                airplane.style.top = `${Math.random() * 80 + 10}%`;
                airplane.style.animationDelay = `${Math.random() * 10}s`;
                airplane.style.fontSize = `${Math.random() * 24 + 16}px`;
                airplane.style.opacity = `${Math.random() * 0.08 + 0.04}`;
                
                container.appendChild(airplane);
            }
        }
        
        // Bottom Sheet Functionality
        const bottomSheet = document.getElementById('bottom-sheet');
        const sheetHandle = document.getElementById('sheet-handle');
        const buildPackageBtn = document.getElementById('build-package-btn');
        const backdrop = document.getElementById('backdrop');
        const fab = document.getElementById('fab');
        const body = document.body;
        
        let isDragging = false;
        let startY = 0;
        let startTranslateY = 0;
        let sheetState = 'collapsed'; // collapsed, active, fullscreen
        
        // Toggle bottom sheet
        function toggleBottomSheet(state) {
            bottomSheet.classList.remove('active', 'fullscreen');
            backdrop.classList.remove('active');
            body.classList.remove('no-scroll');
            
            if (state === 'active') {
                bottomSheet.classList.add('active');
                backdrop.classList.add('active');
                body.classList.add('no-scroll');
                sheetState = 'active';
            } else if (state === 'fullscreen') {
                bottomSheet.classList.add('fullscreen');
                backdrop.classList.add('active');
                body.classList.add('no-scroll');
                sheetState = 'fullscreen';
            } else {
                sheetState = 'collapsed';
            }
        }
        
        // Event listeners for bottom sheet
        sheetHandle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (sheetState === 'collapsed') {
                toggleBottomSheet('active');
            } else if (sheetState === 'active') {
                toggleBottomSheet('fullscreen');
            } else {
                toggleBottomSheet('collapsed');
            }
        });
        
        buildPackageBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleBottomSheet('active');
        });
        
        fab.addEventListener('click', () => {
            toggleBottomSheet('active');
        });
        
        backdrop.addEventListener('click', () => {
            toggleBottomSheet('collapsed');
        });
        
        // Drag functionality for bottom sheet
        sheetHandle.addEventListener('mousedown', startDrag);
        sheetHandle.addEventListener('touchstart', startDrag);
        
        function startDrag(e) {
            isDragging = true;
            startY = e.type === 'mousedown' ? e.clientY : e.touches[0].clientY;
            
            // Get current translateY value
            const transform = window.getComputedStyle(bottomSheet).transform;
            const matrix = new DOMMatrix(transform);
            startTranslateY = matrix.m42;
            
            document.addEventListener('mousemove', drag);
            document.addEventListener('touchmove', drag);
            document.addEventListener('mouseup', stopDrag);
            document.addEventListener('touchend', stopDrag);
        }
        
        function drag(e) {
            if (!isDragging) return;
            
            e.preventDefault();
            const currentY = e.type === 'mousemove' ? e.clientY : e.touches[0].clientY;
            const diff = currentY - startY;
            const newTranslateY = Math.min(0, Math.max(startTranslateY + diff, -window.innerHeight * 0.9));
            
            bottomSheet.style.transform = `translateY(${newTranslateY}px)`;
            
            // Update sheet state based on position
            const translatePercentage = Math.abs(newTranslateY) / window.innerHeight * 100;
            
            if (translatePercentage > 70) {
                bottomSheet.classList.remove('active');
                bottomSheet.classList.add('fullscreen');
                sheetState = 'fullscreen';
            } else if (translatePercentage > 20) {
                bottomSheet.classList.add('active');
                bottomSheet.classList.remove('fullscreen');
                sheetState = 'active';
            } else {
                bottomSheet.classList.remove('active', 'fullscreen');
                sheetState = 'collapsed';
            }
        }
        
        function stopDrag() {
            isDragging = false;
            
            // Get current translateY value
            const transform = window.getComputedStyle(bottomSheet).transform;
            const matrix = new DOMMatrix(transform);
            const currentTranslateY = matrix.m42;
            const translatePercentage = Math.abs(currentTranslateY) / window.innerHeight * 100;
            
            // Snap to closest position
            if (translatePercentage > 70) {
                toggleBottomSheet('fullscreen');
            } else if (translatePercentage > 20) {
                toggleBottomSheet('active');
            } else {
                toggleBottomSheet('collapsed');
            }
            
            // Reset inline transform
            bottomSheet.style.transform = '';
            
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('touchmove', drag);
            document.removeEventListener('mouseup', stopDrag);
            document.removeEventListener('touchend', stopDrag);
        }
        
        // Form interactions
        const budgetSlider = document.getElementById('budget-slider');
        const budgetValue = document.getElementById('budget-value');
        const travelersCount = document.getElementById('travelers-count');
        const decreaseTravelers = document.getElementById('decrease-travelers');
        const increaseTravelers = document.getElementById('increase-travelers');
        const continueBtn = document.getElementById('continue-btn');
        const selectionCards = document.querySelectorAll('.selection-card');
        
        budgetSlider.addEventListener('input', function() {
            const value = parseInt(this.value);
            budgetValue.textContent = `$${value.toLocaleString()}`;
        });
        
        let travelers = 2;
        
        decreaseTravelers.addEventListener('click', function() {
            if (travelers > 1) {
                travelers--;
                travelersCount.textContent = travelers;
            }
        });
        
        increaseTravelers.addEventListener('click', function() {
            if (travelers < 10) {
                travelers++;
                travelersCount.textContent = travelers;
            }
        });
        
        // Selection card interaction
        selectionCards.forEach(card => {
            card.addEventListener('click', function() {
                // Remove selected class from all cards
                selectionCards.forEach(c => {
                    c.classList.remove('selected');
                    const radio = c.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = false;
                    }
                });
                
                // Add selected class to clicked card
                this.classList.add('selected');
                
                // If card has a radio input, check it
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
            });
        });
        
        continueBtn.addEventListener('click', function() {
            alert('Thank you! Your travel package request has been submitted. Our travel experts will contact you within 24 hours with a personalized quote.');
            toggleBottomSheet('collapsed');
        });
        
        // Category chip interaction
        const categoryChips = document.querySelectorAll('.category-chip');
        categoryChips.forEach(chip => {
            chip.addEventListener('click', function() {
                categoryChips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Load more button functionality
        const loadMoreBtn = document.getElementById('load-more');
        
        loadMoreBtn.addEventListener('click', function() {
            // Simulate loading
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading...';
            this.disabled = true;
            
            setTimeout(() => {
                // Add new cards (in a real app, this would come from an API)
                const travelFeed = document.getElementById('travel-feed');
                const newCards = [
                    {
                        title: "Bali, Indonesia",
                        location: "Indonesia",
                        price: "$799",
                        tag: "best-value",
                        description: "Find your zen with yoga retreats, vibrant culture, lush rice terraces, and stunning beaches.",
                        rating: "4.6",
                        reviews: "(421 reviews)",
                        image: "https://images.unsplash.com/photo-1516483638261-f4dbaf036963?ixlib=rb-4.0.3&auto=format&fit=crop&w=1472&q=80"
                    },
                    {
                        title: "Rome, Italy",
                        location: "Lazio, Italy",
                        price: "$1,099",
                        tag: "",
                        description: "Walk through history with ancient ruins, Renaissance art, and authentic Italian cuisine.",
                        rating: "4.8",
                        reviews: "(389 reviews)",
                        image: "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80"
                    }
                ];
                
                newCards.forEach(card => {
                    const cardElement = document.createElement('div');
                    cardElement.className = 'travel-card';
                    cardElement.innerHTML = `
                        <div class="relative">
                            <img src="${card.image}" alt="${card.title}" class="w-full h-64 object-cover">
                            <div class="price-badge">${card.price}</div>
                            ${card.tag ? `<div class="absolute top-4 left-4">
                                <span class="tag ${card.tag.replace('-', '')}">${card.tag === 'best-value' ? 'Best Value' : card.tag}</span>
                            </div>` : ''}
                            <button class="absolute bottom-4 right-4 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center hover:bg-white/20">
                                <i class="far fa-bookmark text-white"></i>
                            </button>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-xl font-bold text-white mb-2">${card.title}</h3>
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-white/50 text-sm mr-2"></i>
                                    <p class="text-white/70">${card.location}</p>
                                </div>
                            </div>
                            <p class="text-white/80 mb-6">${card.description}</p>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <i class="fas fa-star text-amber-400 mr-1"></i>
                                    <span class="font-medium text-white">${card.rating}</span>
                                    <span class="text-white/60 ml-1">${card.reviews}</span>
                                </div>
                                <button class="px-6 py-3 bg-gradient-to-r from-[#02CCFE] to-[#6DD19C] text-white rounded-full font-medium hover:shadow-lg transition">Book Now</button>
                            </div>
                        </div>
                    `;
                    
                    travelFeed.appendChild(cardElement);
                });
                
                // Reset button
                this.innerHTML = '<i class="fas fa-compass mr-2"></i> Load More Destinations';
                this.disabled = false;
            }, 1500);
        });
        
        // Add hover effects to all cards
        document.addEventListener('DOMContentLoaded', function() {
            // This will be handled by CSS now
        });
        
        // Initialize background animations
        createBackgroundAnimations();
        
        // Close bottom sheet when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (sheetState !== 'collapsed' && !bottomSheet.contains(e.target) && !fab.contains(e.target)) {
                toggleBottomSheet('collapsed');
            }
        });
        
        // Handle escape key to close bottom sheet
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sheetState !== 'collapsed') {
                toggleBottomSheet('collapsed');
            }
        });
    </script>
</body>
</html>