<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TravHub Pure Travel & Accounting Spelling App</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #50BC81; /* Amber Green */
            --dark: #0F172A;
            --light: #F0FDF4; /* Light Amber Green Tint */
            --border: #A7F3D0;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--light); color: var(--dark); margin: 0; padding: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden;}
        
        /* HEADER & TOP BAR */
        .header { background: linear-gradient(135deg, #0F9F59, #059669); width: 100%; padding: 18px 30px; display: flex; justify-content: space-between; align-items: center; color: white; border-bottom: 4px solid #064E3B; z-index: 10; box-shadow: 0 5px 15px rgba(15, 159, 89, 0.4);}
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; font-weight: 800; display: flex; align-items: center; gap: 10px; text-shadow: 1px 2px 3px rgba(0,0,0,0.2);}
        .count-badge { background: white; color: var(--primary); padding: 6px 15px; border-radius: 20px; font-weight: 800; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.15);}

        .action-bar { background: white; padding: 15px 30px; display: flex; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); z-index: 5; align-items: center; border-bottom: 2px solid var(--border);}
        
        /* SEARCH & ADD FORM */
        .search-box { flex: 1.5; position: relative; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 16px; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; border: 2px solid var(--primary); border-radius: 8px; font-size: 15px; outline: none; background: #F8FAFC; font-weight: 600; color: var(--dark); transition: 0.2s;}
        .search-box input:focus { background: white; box-shadow: 0 0 0 3px rgba(15, 159, 89, 0.2);}
        
        .add-form { flex: 2; display: flex; gap: 10px; align-items: center; border-left: 2px solid #E2E8F0; padding-left: 15px;}
        .add-form input { flex: 1; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: 0.2s; font-weight: 500;}
        .add-form input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15, 159, 89, 0.1);}
        .btn-add { background: var(--dark); color: white; padding: 12px 25px; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; text-transform: uppercase; display: flex; align-items: center; gap: 5px;}
        .btn-add:hover { background: #1E293B; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2);}

        /* MAIN GRID AREA */
        .main-content { padding: 20px 30px; flex: 1; overflow-y: auto; background: var(--light); }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            width: 100%;
        }

        /* WORD CARD */
        .word-card {
            display: flex;
            align-items: center;
            background: white;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            padding: 12px 15px;
            transition: 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            min-height: 55px;
        }
        .word-card:hover { border-color: var(--primary); box-shadow: 0 8px 20px rgba(15, 159, 89, 0.2); transform: translateY(-3px);}
        
        .w-no { font-weight: 800; color: var(--primary); font-size: 14px; width: 35px; text-align: left; background: #D1FAE5; padding: 4px 8px; border-radius: 4px; display: inline-block;}
        .w-eng { font-weight: 800; color: var(--dark); font-size: 16px; flex: 1.2; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.3; margin-left: 10px;}
        .w-ben { font-weight: 600; color: #475569; font-size: 14px; flex: 1.5; border-left: 2px solid #E2E8F0; padding-left: 12px; margin-left: 10px; word-wrap: break-word; overflow-wrap: break-word; line-height: 1.3;}
        
        .w-del { color: #EF4444; font-weight: bold; cursor: pointer; border: none; background: none; font-size: 16px; padding: 0; margin-left: 10px; transition: 0.2s; opacity: 0.3;}
        .word-card:hover .w-del { opacity: 1; }
        .w-del:hover { color: #B91C1C; transform: scale(1.3); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: var(--light); }
        ::-webkit-scrollbar-thumb { background: #A7F3D0; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }
        
        /* FOOTER BRANDING */
        .footer-branding { text-align: center; padding: 12px; font-weight: 700; color: white; font-size: 15px; background: var(--dark); letter-spacing: 1.5px; border-top: 3px solid var(--primary);}
        .footer-branding span { color: #10B981; font-weight: 800;}
    </style>
</head>
<body>

    <div class="header">
        <h1><i class="fa-solid fa-earth-americas"></i> TravHub Spelling Master</h1>
        <div class="count-badge"><i class="fa-solid fa-layer-group"></i> Words: <span id="wordCount">0</span></div>
    </div>

    <!-- Top Action Bar (Search & Add) -->
    <div class="action-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search spelling or meaning..." onkeyup="filterWords()">
        </div>
        <div class="add-form">
            <input type="text" id="engWord" placeholder="English Word (e.g. Ledger)" autocomplete="off">
            <input type="text" id="benMeaning" placeholder="Bengali Meaning (e.g. হিসাবের খাতা)" autocomplete="off" onkeypress="if(event.key === 'Enter') addNewWord()">
            <button class="btn-add" onclick="addNewWord()"><i class="fa-solid fa-plus"></i> Add</button>
        </div>
    </div>

    <!-- Main Grid Area -->
    <div class="main-content">
        <div class="grid-container" id="gridContainer">
            <!-- Words will be injected here via JavaScript -->
        </div>
    </div>
    
    <!-- PERSONALIZED BRANDING -->
    <div class="footer-branding">
        Created By <span>Abu Saiyd Sujon</span>
    </div>

    <script>
        // Travel, Aviation & Accounting Words (Total 174 Words)
        const defaultDatabase = [
            // Travel & Aviation
            { w: "Trip", m: "ভ্রমণ" }, { w: "Tour", m: "সফর" }, { w: "Map", m: "মানচিত্র" }, { w: "Bag", m: "ব্যাগ" },
            { w: "Cab", m: "ট্যাক্সি" }, { w: "Air", m: "আকাশপথ" }, { w: "Sea", m: "সাগর" }, { w: "Jet", m: "জেট বিমান" },
            { w: "Fare", m: "ভাড়া" }, { w: "Row", m: "সারি" }, { w: "Bed", m: "বিছানা" }, { w: "Fly", m: "ওড়া" }, 
            { w: "Bus", m: "বাস" }, { w: "Car", m: "গাড়ি" }, { w: "Van", m: "ভ্যান গাড়ি" }, { w: "Port", m: "বন্দর" },
            { w: "Seat", m: "আসন" }, { w: "Pass", m: "অনুমতিপত্র" }, { w: "Boat", m: "নৌকা" }, { w: "Ship", m: "জাহাজ" }, 
            { w: "Cruise", m: "প্রমোদতরী" }, { w: "Rail", m: "রেলগাড়ি" }, { w: "Tag", m: "ট্যাগ / লেবেল" }, { w: "Hub", m: "কেন্দ্র" },
            { w: "Arrival", m: "আগমন" }, { w: "Departure", m: "প্রস্থান" }, { w: "Journey", m: "যাত্রা" },
            { w: "Passenger", m: "যাত্রী" }, { w: "Luggage", m: "মালপত্র" }, { w: "Baggage", m: "যাত্রীর মালপত্র" },
            { w: "Route", m: "যাত্রাপথ" }, { w: "Guide", m: "পথপ্রদর্শক" }, { w: "Vacation", m: "অবকাশ" },
            { w: "Sightseeing", m: "দর্শনীয় স্থান দর্শন" }, { w: "Booking", m: "বুকিং" }, { w: "Ticket", m: "টিকিট" },
            { w: "Passport", m: "পাসপোর্ট" }, { w: "Visa", m: "ভিসা" }, { w: "Hotel", m: "আবাসিক হোটেল" },
            { w: "Resort", m: "রিসোর্ট" }, { w: "Reception", m: "অভ্যর্থনা কক্ষ" }, { w: "Lobby", m: "বিশ্রামকক্ষ" },
            { w: "Room", m: "কক্ষ" }, { w: "Terminal", m: "টার্মিনাল" }, { w: "Gate", m: "বহির্গমন পথ" },
            { w: "Transit", m: "মাঝপথে বিরতি" }, { w: "Layover", m: "লম্বা বিরতি" }, { w: "Stopover", m: "যাত্রাবিরতি" },
            { w: "Direct", m: "সরাসরি" }, { w: "Aisle", m: "পথের ধারের সিট" }, { w: "Window", m: "জানালার সিট" },
            { w: "Customs", m: "শুল্ক বিভাগ" }, { w: "Standby", m: "অপেক্ষমান" }, { w: "Suite", m: "বড় রুম" },
            { w: "Twin", m: "জোড়া বিছানা" }, { w: "Double", m: "বড় বিছানার রুম" }, { w: "Vacancy", m: "খালি রুম" },
            { w: "Deposit", m: "অগ্রিম জমা" }, { w: "Tariff", m: "ভাড়ার তালিকা" }, { w: "Voucher", m: "প্রমাণপত্র" }, 
            { w: "Board", m: "খাবারের প্যাকেজ" }, { w: "Itinerary", m: "ভ্রমণের দিনপঞ্জি" }, { w: "Excursion", m: "প্রমোদভ্রমণ" }, 
            { w: "Transfer", m: "গাড়িতে নেয়া" }, { w: "Escort", m: "সহযাত্রী গাইড" }, { w: "Embassy", m: "দূতাবাস" }, 
            { w: "Consulate", m: "বাণিজ্যিক দূতাবাস" }, { w: "Biometric", m: "আঙুলের ছাপ" }, { w: "Immigration", m: "অভিবাসন" }, 
            { w: "Emigration", m: "দেশত্যাগ" }, { w: "Overstay", m: "বেশি সময় থাকা" }, { w: "Deport", m: "দেশ থেকে বিতাড়ন" }, 
            { w: "Embark", m: "বিমানে ওঠা" }, { w: "Disembark", m: "বিমান থেকে নামা" }, { w: "Mileage", m: "মাইলেজ / দূরত্ব" }, 
            { w: "Economy", m: "সাধারণ শ্রেণি" }, { w: "Premium", m: "উন্নত শ্রেণি" }, { w: "Business", m: "বিজনেস ক্লাস" }, 
            { w: "Charter", m: "ভাড়া করা বিমান" }, { w: "Ferry", m: "খেয়া / ফেরি" }, { w: "Coach", m: "আরামদায়ক বাস" }, 
            { w: "Sedan", m: "প্রাইভেট কার" }, { w: "Shuttle", m: "শাটল সার্ভিস" }, { w: "Hospitality", m: "আতিথেয়তা" }, 
            { w: "Reservation", m: "সংরক্ষণ" }, { w: "Occupancy", m: "রুমের ধরন" }, { w: "Amenities", m: "সুযোগ-সুবিধা" }, 
            { w: "Lounge", m: "লাউঞ্জ" }, { w: "Concierge", m: "হোটেল সহায়তাকারী" }, { w: "Buffet", m: "বুফে খাবার" }, 
            { w: "Turbulence", m: "ঝাঁকুনি" }, { w: "Cockpit", m: "ককপিট" }, { w: "Takeoff", m: "উড্ডয়ন" }, 
            { w: "Landing", m: "অবতরণ" }, { w: "Carousel", m: "লাগেজ বেল্ট" }, { w: "Jetlag", m: "ভ্রমণক্লান্তি" }, 
            { w: "Bellboy", m: "হোটেল বয়" }, { w: "Chauffeur", m: "গাড়িচালক" }, { w: "Museum", m: "জাদুঘর" }, 
            { w: "Souvenir", m: "স্মারক" }, { w: "Altitude", m: "উচ্চতা" }, { w: "Backpack", m: "ব্যাকপ্যাক" }, 
            { w: "Compass", m: "দিকদর্শন যন্ত্র" }, { w: "Destination", m: "গন্তব্য" }, { w: "Expedition", m: "অভিযান" }, 
            { w: "Heritage", m: "ঐতিহ্য" }, { w: "Monument", m: "স্মৃতিস্তম্ভ" }, { w: "Safari", m: "বন্যপ্রাণী দর্শন" }, 
            { w: "Voyage", m: "সমুদ্রযাত্রা" }, { w: "Yacht", m: "প্রমোদতরণী" }, { w: "Motel", m: "সস্তা হোটেল" }, 
            { w: "Hostel", m: "ছাত্রাবাস" }, { w: "Cabin", m: "কেবিন" }, { w: "Check-in", m: "নিবন্ধন করা" },
            
            // Accounting & Agency Finance Words
            { w: "Account", m: "হিসাব" }, { w: "Advance", m: "অগ্রিম" }, { w: "Agreement", m: "চুক্তি" }, 
            { w: "Amount", m: "পরিমাণ" }, { w: "Approval", m: "অনুমোদন" }, { w: "Asset", m: "সম্পদ" }, 
            { w: "Audit", m: "নিরীক্ষা" }, { w: "Authorization", m: "অনুমোদন ক্ষমতা" }, { w: "Balance", m: "অবশিষ্ট জের" }, 
            { w: "Bank", m: "ব্যাংক" }, { w: "Bill", m: "বিল / পাওনা" }, { w: "Budget", m: "বাজেট" }, 
            { w: "Capital", m: "মূলধন" }, { w: "Cash", m: "নগদ টাকা" }, { w: "Cheque", m: "চেক" }, 
            { w: "Commission", m: "কমিশন" }, { w: "Contract", m: "চুক্তিপত্র" }, { w: "Cost", m: "খরচ" }, 
            { w: "Credit", m: "ক্রেডিট / জমা" }, { w: "Currency", m: "মুদ্রা" }, { w: "Deadline", m: "শেষ সময়" }, 
            { w: "Debit", m: "ডেবিট / খরচ" }, { w: "Deficit", m: "ঘাটতি" }, { w: "Discount", m: "ছাড়" }, 
            { w: "Dividend", m: "লভ্যাংশ" }, { w: "Due", m: "বকেয়া" }, { w: "Exchange", m: "বিনিময়" }, 
            { w: "Expense", m: "খরচ / ব্যয়" }, { w: "Finance", m: "অর্থায়ন" }, { w: "Forecast", m: "পূর্বাভাস" }, 
            { w: "Fund", m: "তহবিল" }, { w: "Gross", m: "সর্বমোট" }, { w: "Installment", m: "কিস্তি" }, 
            { w: "Investment", m: "বিনিয়োগ" }, { w: "Invoice", m: "চালান" }, { w: "Ledger", m: "হিসাবের খাতা" }, 
            { w: "Liability", m: "দায়বদ্ধতা" }, { w: "Margin", m: "লাভের হার" }, { w: "Markup", m: "লাভের অংশ" }, 
            { w: "Net", m: "আসল / নীট" }, { w: "Nonrefundable", m: "অফেরতযোগ্য" }, { w: "Outstanding", m: "বকেয়া পাওনা" }, 
            { w: "Pay", m: "পরিশোধ করা" }, { w: "Payable", m: "প্রদেয়" }, { w: "Payment", m: "মূল্যপরিশোধ" }, 
            { w: "Penalty", m: "জরিমানা" }, { w: "Profit", m: "লাভ" }, { w: "Quotation", m: "মূল্য প্রস্তাবনা" }, 
            { w: "Receipt", m: "রশিদ" }, { w: "Receivable", m: "প্রাপ্য" }, { w: "Refund", m: "টাকা ফেরত" }, 
            { w: "Refundable", m: "ফেরতযোগ্য" }, { w: "Remittance", m: "টাকা পাঠানো" }, { w: "Revenue", m: "রাজস্ব" }, 
            { w: "Signature", m: "স্বাক্ষর" }, { w: "Statement", m: "বিবরণী" }, { w: "Subsidy", m: "ভর্তুকি" }, 
            { w: "Surcharge", m: "অতিরিক্ত ফি" }, { w: "Surplus", m: "উদ্বৃত্ত" }, { w: "Tax", m: "কর / ট্যাক্স" }, 
            { w: "Transaction", m: "লেনদেন" }, { w: "VAT", m: "ভ্যাট" }
        ];

        let myWords = [];

        // INITIALIZE APP (Changed localStorage key to force fresh load of 174 words)
        function initApp() {
            const savedData = localStorage.getItem('travhub_accounting_words_v3');
            if (savedData) {
                myWords = JSON.parse(savedData);
            } else {
                myWords = [...defaultDatabase];
                saveData();
            }
            renderGrid(myWords);
        }

        function saveData() {
            localStorage.setItem('travhub_accounting_words_v3', JSON.stringify(myWords));
        }

        // RENDER GRID 
        function renderGrid(dataArray) {
            const container = document.getElementById('gridContainer');
            container.innerHTML = '';
            
            // Sort Alphabetically
            dataArray.sort((a, b) => a.w.toLowerCase().localeCompare(b.w.toLowerCase()));

            dataArray.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'word-card';
                card.innerHTML = `
                    <div class="w-no">${index + 1}.</div>
                    <div class="w-eng">${item.w}</div>
                    <div class="w-ben">${item.m}</div>
                    <button class="w-del" onclick="deleteWord('${item.w.replace(/'/g, "\\'")}')" title="Delete"><i class="fa-solid fa-trash-can"></i></button>
                `;
                container.appendChild(card);
            });
            
            document.getElementById('wordCount').innerText = dataArray.length;
        }

        // ADD NEW WORD
        function addNewWord() {
            const eng = document.getElementById('engWord').value.trim();
            const ben = document.getElementById('benMeaning').value.trim();

            if(eng === "" || ben === "") {
                alert("Please type both Word and Meaning!");
                return;
            }

            const formattedEng = eng.charAt(0).toUpperCase() + eng.slice(1);
            
            // Prevent duplicate
            const exists = myWords.find(w => w.w.toLowerCase() === eng.toLowerCase());
            if(exists) {
                alert("This word is already in your dictionary!");
                return;
            }

            myWords.push({ w: formattedEng, m: ben });
            saveData();
            
            document.getElementById('engWord').value = "";
            document.getElementById('benMeaning').value = "";
            
            filterWords(); 
            
            const addBtn = document.querySelector('.btn-add');
            addBtn.innerHTML = '<i class="fa-solid fa-check"></i> Added';
            addBtn.style.background = '#0F9F59';
            addBtn.style.color = 'white';
            setTimeout(() => { 
                addBtn.innerHTML = '<i class="fa-solid fa-plus"></i> Add'; 
                addBtn.style.background = '#0F172A';
            }, 1500);
        }

        // DELETE WORD
        function deleteWord(wordToDelete) {
            if(confirm(`Are you sure you want to delete "${wordToDelete}"?`)) {
                myWords = myWords.filter(item => item.w !== wordToDelete);
                saveData();
                filterWords();
            }
        }

        // LIVE SEARCH
        function filterWords() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const filtered = myWords.filter(item => 
                item.w.toLowerCase().includes(input) || 
                item.m.toLowerCase().includes(input)
            );
            renderGrid(filtered);
        }

        // Start App on Load
        window.onload = initApp;
    </script>
</body>
</html>