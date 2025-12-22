<?php
require 'server/db_connection.php';

$pnr = $_GET['pnr'] ?? null;
$dbApplicationData = null;

if ($pnr) {
    // 1. Fetch application info from DATABASE FIRST
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE pnr = ?");
    $stmt->execute([$pnr]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        // 2. Fetch all applicants from DATABASE
        $stmt2 = $pdo->prepare("SELECT * FROM applicants WHERE pnr = ?");
        $stmt2->execute([$pnr]);
        $appRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $applicants = [];
        foreach ($appRows as $ap) {
            $applicants[] = [
                "id" => $ap['user_pnr'],
                "pnr" => $ap['pnr'],
                "user_pnr" => $ap['user_pnr'],
                "completed" => (bool)$ap['completed'],
                "passportInfo" => json_decode($ap['passport_info'], true) ?? [],
                "nidInfo" => json_decode($ap['nid_info'], true) ?? [],
                "contactInfo" => json_decode($ap['contact_info'], true) ?? [],
                "familyInfo" => json_decode($ap['family_info'], true) ?? [],
                "accommodationDetails" => json_decode($ap['accommodation_details'], true) ?? [],
                "employmentInfo" => json_decode($ap['employment_info'], true) ?? [],
                "incomeExpenditure" => json_decode($ap['income_expenditure'], true) ?? [],
                "travelInfo" => json_decode($ap['travel_info'], true) ?? [],
                "travelHistory" => json_decode($ap['travel_history'], true) ?? []
            ];
        }

        // 3. Prepare DB data for JS
        $dbApplicationData = [
            'pnr' => $application['pnr'],
            'nameOfApplicant' => $applicants[0]['passportInfo']['pp_family_name'] ?? '',
            'totalApplicants' => count($applicants),
            'applicants' => $applicants,
            'currentApplicant' => 0,
            'currentStep' => 0,
            'timestamp' => $application['created_at'],
            'source' => 'database'
        ];
    }
}
?>


<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .step {
        display: none;
    }

    .step.active {
        display: block;
    }

    .tab {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab.active {
        background-color: #3b82f6;
        color: white;
    }

    .progress-bar {
        transition: width 0.5s ease-in-out;
    }

    .form-section {
        border-left: 4px solid #3b82f6;
    }

    .summary-item {
        border-bottom: 1px solid #e5e7eb;
        padding: 12px 0;
    }

    .applicant-progress {
        height: 6px;
        border-radius: 3px;
    }

    .applicant-complete {
        background-color: #10b981;
    }

    .applicant-incomplete {
        background-color: #d1d5db;
    }

    .step-nav-item {
        cursor: pointer;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }

    .step-nav-item:hover {
        background-color: #f3f4f6;
    }

    .step-nav-item.active {
        border-left-color: #3b82f6;
        background-color: #eff6ff;
    }

    .step-nav-item.completed .step-icon {
        background-color: #10b981;
        color: white;
    }

    .step-nav-item.current .step-icon {
        background-color: #3b82f6;
        color: white;
    }

    .dynamic-field-group {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #f9fafb;
    }

    .address-group {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #f9fafb;
    }

    .family-member-group {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #f9fafb;
    }

    .travel-history-group {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
        background-color: #f9fafb;
    }
</style>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Header -->
    <header class="text-center mb-12">
        <div class="flex items-center justify-center mb-4">
            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-passport text-white text-xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Applicant's Info</h1>
        </div>
    </header>

    <!-- Main Application Container -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Initial Screen -->
        <div id="initial-screen" class="p-8">
            <div class="max-w-md mx-auto text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">How many applicants are under the same PNR?</h2>
                <div class="mb-8">
                    <label for="applicant-count" class="block text-gray-700 mb-2">Number of Applicants</label>
                    <select id="applicant-count" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="1">1 Applicant</option>
                        <option value="2">2 Applicants</option>
                        <option value="3">3 Applicants</option>
                        <option value="4">4 Applicants</option>
                        <option value="5">5 Applicants</option>
                    </select>
                </div>
                <button id="start-application" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition duration-300">
                    Start Application
                </button>

                <!-- Load Saved Application -->
                <div id="saved-application-section" class="mt-8 p-4 bg-yellow-50 rounded-lg border border-yellow-200 hidden">
                    <h3 class="font-medium text-yellow-800 mb-2">Saved Application Found</h3>
                    <p class="text-yellow-700 text-sm mb-3">We found a saved application with PNR: <span id="saved-pnr" class="font-mono font-bold"></span></p>
                    <button id="load-application" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300">
                        Load Saved Application
                    </button>
                </div>
            </div>
        </div>

        <!-- Multi-Applicant Form (Hidden Initially) -->
        <div id="multi-applicant-form" class="hidden">
            <!-- PNR Display -->
            <div class="px-8 pt-8 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Application PNR: <span id="pnr-display" class="font-mono text-blue-600"></span></h2>
                    <p class="text-gray-600 text-sm">Your application is automatically saved as you progress</p>
                </div>
                <div class="flex space-x-2">
                    <button id="back-to-dashboard" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 text-sm">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </button>
                    <button id="save-exit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 text-sm">
                        <i class="fas fa-save mr-2"></i>Save & Exit
                    </button>
                </div>
            </div>

            <!-- Overall Progress -->
            <div class="px-8 pt-4">
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-blue-600">Overall Progress</span>
                    <span class="text-sm font-medium text-gray-500"><span id="completed-applicants">0</span> of <span id="total-applicants">1</span> applicants completed</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-6">
                    <div id="overall-progress-bar" class="bg-blue-600 h-2.5 rounded-full progress-bar" style="width: 0%"></div>
                </div>
            </div>

            <!-- Applicant Tabs with Individual Progress -->
            <div id="applicant-tabs" class="flex overflow-x-auto border-b border-gray-200 px-8">
                <!-- Tabs will be dynamically generated here -->
            </div>

            <!-- Current Applicant Progress -->
            <div class="px-8 pt-4">
                <div class="flex justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Applicant <span id="current-applicant-number">1</span> Progress</span>
                    <span class="text-sm font-medium text-gray-500"><span id="current-step">1</span> of <span id="total-steps">9</span></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div id="individual-progress-bar" class="bg-green-600 h-2.5 rounded-full progress-bar" style="width: 11.11%"></div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex flex-col md:flex-row p-8">
                <!-- Step Navigation Sidebar -->
                <div class="w-full md:w-1/4 mb-6 md:mb-0 md:pr-6">
                    <div class="bg-gray-50 rounded-lg p-4 sticky top-4">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-list-ol mr-2 text-blue-500"></i>
                            Application Steps
                        </h3>
                        <div id="step-navigation" class="space-y-2">
                            <!-- Step navigation items will be dynamically generated here -->
                        </div>
                    </div>
                </div>

                <!-- Form Steps -->
                <div id="form-steps" class="w-full md:w-3/4">
                    <!-- Steps will be dynamically generated here -->
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between px-8 pb-8">
                <button id="prev-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Previous
                </button>
                <div class="flex space-x-4">
                    <button id="next-applicant-btn" class="hidden bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                        Save & Next Applicant <i class="fas fa-user-plus ml-2"></i>
                    </button>
                    <button id="next-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                        Save & Next Step <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
                <button id="submit-btn" class="hidden bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
                    Submit Application
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // তারিখ validation ফাংশন
    function isValidDate(dateString) {
        // DD/MM/YYYY format validate
        const pattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        if (!pattern.test(dateString)) return false;

        const [_, day, month, year] = pattern.exec(dateString);
        const date = new Date(year, month - 1, day);

        return date.getDate() == day &&
            date.getMonth() == month - 1 &&
            date.getFullYear() == year;
    }

    // DD/MM/YYYY থেকে YYYY-MM-DD তে convert
    function convertToISO(dateString) {
        if (!isValidDate(dateString)) return '';

        const [day, month, year] = dateString.split('/');
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    // YYYY-MM-DD থেকে DD/MM/YYYY তে convert
    function convertToDisplay(isoDate) {
        if (!isoDate) return '';

        const [year, month, day] = isoDate.split('-');
        return `${day}/${month}/${year}`;
    }
    // Application state
    const state = {
        currentApplicant: 0,
        currentStep: 0,
        totalSteps: 9,
        totalApplicants: 1,
        pnr: '',
        applicants: [],
        steps: [{
                name: 'Passport Information',
                icon: 'fa-passport',
                description: 'Provide your passport details'
            },
            {
                name: 'Personal & Contact Information',
                icon: 'fa-address-book',
                description: 'Your contact details'
            },
            {
                name: 'NID Information',
                icon: 'fa-id-card',
                description: 'National Identity Card details'
            },
            {
                name: 'Family Information',
                icon: 'fa-users',
                description: 'Information about your family'
            },
            {
                name: 'Accommodation Details',
                icon: 'fa-hotel',
                description: 'Where you will stay in the UK'
            },
            {
                name: 'Employment Information',
                icon: 'fa-briefcase',
                description: 'Your employment details'
            },
            {
                name: 'Income & Expenditure',
                icon: 'fa-chart-line',
                description: 'Financial information'
            },
            {
                name: 'Travel Information',
                icon: 'fa-plane',
                description: 'Your travel plans'
            },
            {
                name: 'Travel History',
                icon: 'fa-globe-americas',
                description: 'Previous travel history'
            },
            {
                name: 'Travel Information (TI) for USA',
                icon: 'fa-plane',
                description: 'Travel plans and purpose'
            },
            {
                name: 'Travel Companion Information (TCI) for USA',
                icon: 'fa-users',
                description: 'Travel companions details'
            },
            {
                name: 'Previous U.S. Travel (PUST)',
                icon: 'fa-history',
                description: 'Previous travel history to USA'
            },
            {
                name: 'U.S. Contact Information (USCI)',
                icon: 'fa-address-book',
                description: 'Contacts in USA'
            },
            {
                name: 'Work Information (WI)',
                icon: 'fa-briefcase',
                description: 'Employment and work history'
            },
            {
                name: 'Educational Information (EDI)',
                icon: 'fa-graduation-cap',
                description: 'Educational background'
            },
            {
                name: 'Other Information (OI)',
                icon: 'fa-info-circle',
                description: 'Additional information'
            }

        ]
    };

    // Check for URL parameters on page load
    function checkURLParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const pnr = urlParams.get('pnr');

        if (pnr) {
            // FIRST: Try to load from DATABASE (if PHP found data)
            <?php if ($dbApplicationData): ?>
                const dbApplicationData = <?php echo json_encode($dbApplicationData); ?>;
                if (loadApplicationFromDB(dbApplicationData)) {
                    console.log('Application loaded from DATABASE');
                    return;
                }
            <?php endif; ?>

            // SECOND: If no DB data, try localStorage
            if (loadApplicationByPNR(pnr)) {
                console.log('Application loaded from LOCALSTORAGE');
                return;
            }

            // THIRD: If nothing found, show error
            alert(`Application with PNR ${pnr} not found in Database or LocalStorage. Starting a new application.`);
        }
    }

    // Load application from DB data
    function loadApplicationFromDB(applicationData) {
        if (applicationData && applicationData.pnr) {
            // Restore state from DB data
            state.totalApplicants = applicationData.totalApplicants;
            state.pnr = applicationData.pnr;
            state.applicants = applicationData.applicants;
            state.currentApplicant = applicationData.currentApplicant || 0;
            state.currentStep = applicationData.currentStep || 0;

            // Hide initial screen and show form directly
            document.getElementById('initial-screen').classList.add('hidden');
            document.getElementById('multi-applicant-form').classList.remove('hidden');

            // Display PNR
            document.getElementById('pnr-display').textContent = state.pnr;
            document.getElementById('total-applicants').textContent = state.totalApplicants;

            // Generate tabs
            generateTabs();

            // Generate step navigation
            generateStepNavigation();

            // Generate form steps for the current applicant
            generateFormSteps();

            // Update UI
            updateUI();

            return true;
        }
        return false;
    }

    // Load specific application by PNR (for localStorage)
    function loadApplicationByPNR(pnr) {
        const savedApplication = localStorage.getItem('ukVisaApplication-' + pnr);

        if (savedApplication) {
            const applicationData = JSON.parse(savedApplication);

            if (applicationData.pnr === pnr) {
                // Restore state from saved data
                state.totalApplicants = applicationData.totalApplicants;
                state.pnr = applicationData.pnr;
                state.applicants = applicationData.applicants;
                state.currentApplicant = applicationData.currentApplicant || 0;
                state.currentStep = applicationData.currentStep || 0;

                // Hide initial screen and show form directly
                document.getElementById('initial-screen').classList.add('hidden');
                document.getElementById('multi-applicant-form').classList.remove('hidden');

                // Display PNR
                document.getElementById('pnr-display').textContent = state.pnr;
                document.getElementById('total-applicants').textContent = state.totalApplicants;

                // Generate tabs
                generateTabs();

                // Generate step navigation
                generateStepNavigation();

                // Generate form steps for the current applicant
                generateFormSteps();

                // Update UI
                updateUI();

                return true;
            }
        }

        return false;
    }

    // Check if there's a saved application in localStorage
    function checkForSavedApplication() {
        let lastApplication = null;
        let latestTimestamp = 0;

        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);

            if (key.startsWith('ukVisaApplication-')) {
                try {
                    const storedValue = JSON.parse(localStorage.getItem(key));

                    if (storedValue && storedValue.timestamp) {
                        const ts = new Date(storedValue.timestamp).getTime();

                        if (ts > latestTimestamp) {
                            latestTimestamp = ts;
                            lastApplication = storedValue;
                        }
                    }
                } catch (e) {
                    console.error('Error parsing localStorage item:', key, e);
                }
            }
        }

        if (lastApplication) {
            document.getElementById('saved-pnr').textContent = lastApplication.pnr;
            document.getElementById('saved-application-section').classList.remove('hidden');
        } else {
            console.log('No UK Visa Application found.');
        }
    }

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
        // Set up event listeners
        document.getElementById('start-application').addEventListener('click', startApplication);
        document.getElementById('load-application').addEventListener('click', loadSavedApplication);
        document.getElementById('prev-btn').addEventListener('click', previousStep);
        document.getElementById('next-btn').addEventListener('click', nextStep);
        document.getElementById('next-applicant-btn').addEventListener('click', nextApplicant);
        document.getElementById('submit-btn').addEventListener('click', submitApplication);
        document.getElementById('save-exit').addEventListener('click', saveAndExit);
        document.getElementById('back-to-dashboard').addEventListener('click', function() {
            window.location.href = 'index.php';
        });

        // Check for URL parameters first - WITH DB PRIORITY
        checkURLParameters();

        // Check for saved applications in localStorage (for initial screen)
        checkForSavedApplication();
    });

    // Load saved application from localStorage
    function loadSavedApplication() {
        const pnr = document.getElementById('saved-pnr').textContent.trim();
        if (!pnr) {
            console.error("No PNR found.");
            return;
        }

        const savedApplication = localStorage.getItem('ukVisaApplication-' + pnr);
        if (!savedApplication) {
            console.error("No saved application found for PNR:", pnr);
            return;
        }

        const applicationData = JSON.parse(savedApplication);

        state.totalApplicants = applicationData.totalApplicants;
        state.pnr = applicationData.pnr;
        state.applicants = applicationData.applicants;
        state.currentApplicant = applicationData.currentApplicant || 0;
        state.currentStep = applicationData.currentStep || 0;

        document.getElementById('initial-screen').classList.add('hidden');
        document.getElementById('multi-applicant-form').classList.remove('hidden');

        document.getElementById('pnr-display').textContent = state.pnr;
        document.getElementById('total-applicants').textContent = state.totalApplicants;

        generateTabs();
        generateStepNavigation();
        generateFormSteps();
        updateUI();
    }

    // Start the application process
    function startApplication() {
        const applicantCount = parseInt(document.getElementById('applicant-count').value);
        state.totalApplicants = applicantCount;

        // Generate PNR
        state.pnr = generatePNR();

        // Initialize all applicants
        for (let i = 0; i < applicantCount; i++) {
            initializeApplicant(i);
        }

        // Hide initial screen and show form
        document.getElementById('initial-screen').classList.add('hidden');
        document.getElementById('multi-applicant-form').classList.remove('hidden');

        // Display PNR
        document.getElementById('pnr-display').textContent = state.pnr;
        document.getElementById('total-applicants').textContent = state.totalApplicants;

        // Generate tabs
        generateTabs();

        // Generate step navigation
        generateStepNavigation();

        // Generate form steps for the first applicant
        generateFormSteps();

        // Update UI
        updateUI();

        // Save initial state
        saveToLocalStorage();
    }

    // Generate a unique PNR
    function generatePNR() {
        const timestamp = Date.now().toString().slice(-6);
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        return `TRH-UK-PNR-${timestamp}K${random}`;
    }

    // Generate applicant ID based on PNR
    function generateApplicantId(applicantIndex) {
        return `${state.pnr}-APPT-${(applicantIndex + 1).toString().padStart(3, '0')}`;
    }

    // Initialize an applicant with empty data
    function initializeApplicant(index) {
        state.applicants[index] = {
            id: generateApplicantId(index),
            pnr: state.pnr,
            user_pnr: generateApplicantId(index),
            completed: false,
            passportInfo: {},
            contactInfo: {
                primary_phone_no: '',
                secondary_phone_no: '',
                work_phone_no: '',
                emails: [''],
                phones: [''],
                addresses: [{
                    line1: '',
                    line2: '',
                    city: '',
                    state: '',
                    postalCode: '',
                    isCorrespondence: false,
                    livedInFor: '',
                    ownershipStatus: ''
                }],
                preferred_other_phone_no: ''
            },
            nidInfo: {
                has_nid: null
            },
            familyInfo: {
                relationshipStatus: '',
                familyMembers: [],
                hasRelativeInUK: null,
                relativeAddress: {
                    line1: '',
                    line2: '',
                    city: '',
                    state: '',
                    postalCode: ''
                }
            },
            accommodationDetails: {
                hasAddress: null,
                hotels: [''],
                addresses: [{
                    line1: '',
                    line2: '',
                    city: '',
                    state: '',
                    postalCode: ''
                }],
                custom_accommodation: ''
            },
            employmentInfo: {
                employmentStatus: '',
                jobDetails: '',
                yearlyEarning: '',
                jobTitle: '',
                monthlyIncome: '',
                jobDescription: ''
            },
            incomeExpenditure: {
                haveSavings: null,
                planningToExpense: '',
                totalExpenseInBd: '',
                paymentInfo: [{
                    currency: '',
                    amount: '',
                    paidFor: ''
                }]
            },
            travelInfo: {
                visitMainReason: '',
                businessReasonToVisitUk: '',
                tourismReasonToVisitUk: '',
                activities: '',
                arrivalDate: '',
                leaveDate: ''
            },
            travelHistory: {
                history: ''
            },
            travelInfoForUSA: {
                ti_travel_purpose: '',
                ti_have_travel_plan: '',
                // No travel plan fields
                ti_intended_arrival_date: '',
                ti_stay_length: '',
                ti_length_type: '',
                // Yes travel plan fields  
                ti_arrival_date: '',
                ti_arrival_flight_no: '',
                ti_arrival_city: '',
                ti_departure_date: '',
                ti_departure_flight_no: '',
                ti_departure_city: '',

                // Locations
                locations: [{
                    address_line_1: '',
                    address_line_2: '',
                    city: '',
                    state: '',
                    zip_code: ''
                }],

                // Payment
                trip_payment: '',
                // Other person payment
                trip_paying_person_surname: '',
                ti_trip_paying_person_given_name: '',
                ti_trip_paying_person_telephone: '',
                ti_trip_paying_person_email: '',
                _trip_paying_person_relationship: '',
                trip_paying_person_have_same_address: true,
                ti_trip_paying_person_address_line_1: '',
                ti_trip_paying_person_address_line_2: '',
                ti_trip_paying_person_address_city: '',
                ti_trip_paying_person_address_state: '',
                ti_trip_paying_person_address_zip_code: '',
                trip_paying_person_address_country: '',

                // Travel Companion (TCI)
                tci_have_anyone: false,
                tci_surname: '',
                tci_given_name: '',
                tci_relationship: '',
                tci_have_group: false,
                tci_group_name: ''
            },
            // Previous US Travel (PUST)
            travelHistoryForUSA: {
                pust_have_ever_issued: false,
                pust_last_issued_visa_date: '',
                pust_visa_no: '',
                pust_remember_visa_no: false,
                pust_have_applied_same_visa: false,
                pust_have_applied_same_country: false,
                pust_have_travelled_before: false,
                previousTravels: [{
                    arrival_date: '',
                    staying_length: ''
                }],
                pust_have_social_security_no: false,
                pust_social_security_no: '',
                pust_have_us_tin: false,
                pust_us_tin: '',
                pust_have_us_driving_license: false,
                driverLicenses: [{
                    license_no: '',
                    state: ''
                }],
                pust_have_ten_fingerprint: false,
                pust_have_refused_us_visa: false,
                pust_visa_refusal_explain: '',
                pust_have_legal_permanent_resident: false,
                pust_have_us_visa_lost: false,
                pust_have_us_visa_cancelled: false
            },
            // US Contact Information (USCI)
            usContactInfo: {
                usci_contact_type: '',
                // Person contact
                usci_contact_person_surname: '',
                usci_contact_person_given_name: '',
                'usci contact person telephone': '',
                'usci contact person email': '',
                'usci contact person relationship': '',
                'usci contact person address line 1': '',
                'usci contact person address line 2': '',
                'usci contact person address city': '',
                'usci contact person address state': '',
                'usci contact person address zip code': '',
                // Company contact (same fields reused)
                usci_contact_company_name: '',
                'usci contact company telephone': '',
                'usci contact company email': '',
                'usci contact company relationship': '',
                // Hotel contact
                usci_contact_hotel_name: ''
            },

            // Work Information (WI)
            workInfoForUSA: {
                wi_primary_occupation_type: '',
                // Employment fields
                wi_company_or_school_name: '',
                wi_salary: '',
                wi_your_duties: '',
                wi_company_or_school_address_line_1: '',
                wi_company_or_school_address_line_2: '',
                wi_company_or_school_address_city: '',
                wi_company_or_school_address_state: '',
                wi_company_or_school_address_zip_code: '',
                wi_company_or_school_address_country: '',
                wi_company_or_school_address_telephone: '',

                have_previous_experience: false,
                previousEmployment: [{
                    wi_pre_company_name: '',
                    wi_pre_company_job_title: '',
                    wi_pre_company_supervisor_surname: '',
                    wi_pre_company_supervisor_given_name: '',
                    wi_pre_employment_started: '',
                    wi_pre_employment_ended: '',
                    wi_pre_company_salary: '',
                    wi_pre_company_address_line_1: '',
                    wi_pre_company_address_line_2: '',
                    wi_pre_company_address_city: '',
                    wi_pre_company_address_state: '',
                    wi_pre_company_address_zip_code: '',
                    wi_pre_company_address_country: '',
                    wi_pre_company_address_telephone: '',
                    wi_pre_company_duties: ''
                }]
            },

            // Educational Information (EDI)
            educationalInfo: {
                edi_have_attended_secondary_level: false,
                institutions: [{
                    name: '',
                    course: '',
                    attendanceFrom: '',
                    attendanceTo: '',
                    edi_institution_address_line_1: '',
                    edi_institution_address_line_2: '',
                    edi_institution_address_city: '',
                    edi_institution_address_state: '',
                    edi_institution_address_zip_code: '',
                    edi_institution_address_country: ''
                }]
            },

            // Other Information (OI)
            otherInfo: {
                oi_spoken_language_list: '',
                oi_have_travel_country_5years: false,
                oi_travelled_country: [],
                oi_have_you_belong_orgntion: false,
                oi_organization_name: [],
                oi_have_special_skills: false,
                oi_special_skills: '',
                oi_have_served_military: false,
                oi_military_service: [{
                    oi_sm_country_name: '',
                    oi_sm_service_branch: '',
                    oi_sm_rank: '',
                    oi_militay_speciality: '',
                    oi_sm_serve_from: '',
                    oi_sm_serve_to: ''
                }]
            }

        };
    }

    // Generate tabs for each applicant with progress indicators
    function generateTabs() {
        const tabsContainer = document.getElementById('applicant-tabs');
        tabsContainer.innerHTML = '';

        for (let i = 0; i < state.totalApplicants; i++) {
            const applicant = state.applicants[i];
            const completedSteps = countCompletedSteps(i);
            const progressPercentage = (completedSteps / state.totalSteps) * 100;

            const tab = document.createElement('div');
            tab.className = `tab py-3 px-6 text-sm font-medium flex flex-col items-center min-w-32 ${i === state.currentApplicant ? 'active bg-blue-600 text-white' : 'text-gray-500 hover:text-gray-700'}`;
            tab.dataset.applicant = i;

            tab.innerHTML = `
                    <div class="flex justify-between w-full items-center mb-1">
                        <span>Applicant ${i + 1} &nbsp;</span> 
                        ${applicant.completed ? '<i class="fas fa-check-circle text-green-500"></i>' : ''}
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 applicant-progress">
                        <div class="h-1.5 rounded-full ${applicant.completed ? 'applicant-complete' : 'applicant-incomplete'}" style="width: ${progressPercentage}%"></div>
                    </div>
                    <div class="text-xs mt-1">${completedSteps}/${state.totalSteps}</div>
                `;

            tab.addEventListener('click', function() {
                switchApplicant(parseInt(this.dataset.applicant));
            });

            tabsContainer.appendChild(tab);
        }
    }

    // Generate step navigation sidebar
    function generateStepNavigation() {
        const stepNavContainer = document.getElementById('step-navigation');
        stepNavContainer.innerHTML = '';

        state.steps.forEach((step, index) => {
            const isCompleted = isStepCompleted(index);
            const isCurrent = index === state.currentStep;

            const stepNavItem = document.createElement('div');
            stepNavItem.className = `step-nav-item p-3 rounded-lg ${isCurrent ? 'active current' : ''} ${isCompleted ? 'completed' : ''}`;
            stepNavItem.dataset.step = index;

            stepNavItem.innerHTML = `
                    <div class="flex items-center">
                        <div class="step-icon w-8 h-8 rounded-full flex items-center justify-center mr-3 ${isCompleted ? 'bg-green-500 text-white' : isCurrent ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-600'}">
                            <i class="fas ${step.icon} text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-gray-800">${step.name}</div>
                            <div class="text-xs text-gray-500">${step.description}</div>
                        </div>
                        ${isCompleted ? '<i class="fas fa-check text-green-500 ml-2"></i>' : ''}
                    </div>
                `;

            stepNavItem.addEventListener('click', function() {
                const stepIndex = parseInt(this.dataset.step);
                jumpToStep(stepIndex);
            });

            stepNavContainer.appendChild(stepNavItem);
        });
    }

    // Check if a step is completed for the current applicant
    function isStepCompleted(stepIndex) {
        const applicant = state.applicants[state.currentApplicant];

        switch (stepIndex) {
            case 0: // Passport Information
                return applicant.passportInfo.pp_given_name &&
                    applicant.passportInfo.pp_family_name &&
                    applicant.passportInfo.pp_number;
            case 1: // Contact Information
                return applicant.contactInfo.emails[0] &&
                    applicant.contactInfo.phones[0] &&
                    applicant.contactInfo.addresses[0].line1;
            case 2: // NID Information
                return applicant.nidInfo.has_nid !== null;
            case 3: // Family Information
                return applicant.familyInfo.relationshipStatus;
            case 4: // Accommodation Details
                return applicant.accommodationDetails.hasAddress !== null;
            case 5: // Employment Information
                return applicant.employmentInfo.employmentStatus;
            case 6: // Income & Expenditure
                return applicant.incomeExpenditure.planningToExpense;
            case 7: // Travel Information
                return applicant.travelInfo.visitMainReason &&
                    applicant.travelInfo.arrivalDate;
            case 8: // Travel History
                return true;
            default:
                return false;
        }
    }

    // Jump to a specific step
    function jumpToStep(stepIndex) {
        state.currentStep = stepIndex;
        generateFormSteps();
        generateStepNavigation();
        updateUI();
        saveToLocalStorage();
    }

    // Count completed steps for an applicant
    function countCompletedSteps(applicantIndex) {
        const applicant = state.applicants[applicantIndex];
        let count = 0;

        for (let i = 0; i < state.totalSteps; i++) {
            if (isStepCompletedForApplicant(applicantIndex, i)) {
                count++;
            }
        }

        return count;
    }

    // Check if a step is completed for a specific applicant
    function isStepCompletedForApplicant(applicantIndex, stepIndex) {
        const applicant = state.applicants[applicantIndex];

        switch (stepIndex) {
            case 0: // Passport Information
                return applicant.passportInfo.pp_given_name &&
                    applicant.passportInfo.pp_family_name &&
                    applicant.passportInfo.pp_number;
            case 1: // Contact Information
                return applicant.contactInfo.emails[0] &&
                    applicant.contactInfo.phones[0] &&
                    applicant.contactInfo.addresses[0].line1;
            case 2: // NID Information
                return applicant.nidInfo.has_nid !== null;
            case 3: // Family Information
                return applicant.familyInfo.relationshipStatus;
            case 4: // Accommodation Details
                return applicant.accommodationDetails.hasAddress !== null;
            case 5: // Employment Information
                return applicant.employmentInfo.employmentStatus;
            case 6: // Income & Expenditure
                return applicant.incomeExpenditure.planningToExpense;
            case 7: // Travel Information
                return applicant.travelInfo.visitMainReason &&
                    applicant.travelInfo.arrivalDate;
            case 8: // Travel History
                return true;
            default:
                return false;
        }
    }

    // Check if an applicant has completed all steps
    function isApplicantComplete(applicantIndex) {
        for (let i = 0; i < state.totalSteps; i++) {
            if (!isStepCompletedForApplicant(applicantIndex, i)) {
                return false;
            }
        }
        return true;
    }

    // Switch between applicants
    function switchApplicant(applicantIndex) {
        state.currentApplicant = applicantIndex;
        state.currentStep = 0;

        // Update tabs
        document.querySelectorAll('.tab').forEach((tab, index) => {
            if (index === applicantIndex) {
                tab.classList.add('active', 'bg-blue-600', 'text-white');
                tab.classList.remove('text-gray-500');
            } else {
                tab.classList.remove('active', 'bg-blue-600', 'text-white');
                tab.classList.add('text-gray-500');
            }
        });

        // Regenerate form steps for the selected applicant
        generateFormSteps();

        // Regenerate step navigation
        generateStepNavigation();

        // Update UI
        updateUI();

        // Save state
        saveToLocalStorage();
    }

    // Generate form steps for the current applicant
    function generateFormSteps() {
        const formStepsContainer = document.getElementById('form-steps');
        formStepsContainer.innerHTML = '';

        state.steps.forEach((step, index) => {
            const stepElement = document.createElement('div');
            stepElement.className = `step fade-in ${index === state.currentStep ? 'active' : ''}`;
            stepElement.id = `step-${index}`;

            stepElement.innerHTML = `
                    <h2 class="text-xl font-bold text-gray-800 mb-6">${step.name} - Applicant ${state.currentApplicant + 1}</h2>
                    <div class="bg-gray-50 p-6 rounded-lg form-section">
                        ${generateStepContent(index)}
                    </div>
                `;

            formStepsContainer.appendChild(stepElement);
        });

        // Update the total steps display
        document.getElementById('total-steps').textContent = state.totalSteps;
    }

    // Generate content for each step
    function generateStepContent(stepIndex) {
        const applicant = state.applicants[state.currentApplicant];

        switch (stepIndex) {
            case 0: // Passport Information
                return generatePassportInfoStep(applicant);

            case 1: // Personal & Contact Information
                return generateContactInfoStep(applicant);

            case 2: // NID Information
                return generateNIDInfoStep(applicant);

            case 3: // Family Information
                return generateFamilyInfoStep(applicant);

            case 4: // Accommodation Details
                return generateAccommodationDetailsStep(applicant);

            case 5: // Employment Information
                return generateEmploymentInfoStep(applicant);

            case 6: // Income & Expenditure
                return generateIncomeExpenditureStep(applicant);

            case 7: // Travel Information
                return generateTravelInfoStep(applicant);

            case 8: // Travel History
                return generateTravelHistoryStep(applicant);

            case 9: // Travel Information for USA
                return generatetravelInfoForUSAStep(applicant);

            case 10: // Travel Companion Information for USA
                return generateTravelCompanionStepForUSA(applicant);

            case 11: // Previous U.S. Travel Step (Based on Excel PUST section)
                return generatePreviousTravelStepForUSA(applicant);

            case 12: // U.S. Contact Information Step (Based on Excel USCI section)
                return generateUSContactStep(applicant);

            case 13: // Work Information Step (Based on Excel WI section)
                return generateWorkInfoStepForUSA(applicant);

            case 14: // Educational Information Step (Based on Excel EDI section)
                return generateEducationInfoStepForUSA(applicant);

            case 15: // Other Information Step (Based on Excel OI section)
                return generateOtherInfoStepForUSA(applicant);

            default:
                return '<p>Step content not defined.</p>';
        }
    }

    // Country data - will be replaced with JSON API data
    const countries = [{
            code: 'USA',
            name: 'United States'
        },
        {
            code: 'UK',
            name: 'United Kingdom'
        },
        {
            code: 'BD',
            name: 'Bangladesh'
        },
        {
            code: 'IN',
            name: 'India'
        },
        {
            code: 'CA',
            name: 'Canada'
        },
        {
            code: 'AU',
            name: 'Australia'
        },
        {
            code: 'DE',
            name: 'Germany'
        },
        {
            code: 'FR',
            name: 'France'
        }
    ];

    // Generate Passport Information step
    function generatePassportInfoStep(applicant) {
        return `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Given Name *</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_given_name || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_given_name', this.value)" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Family Name *</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_family_name || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_family_name', this.value)" required>
                    </div>
                    <div>
                            <label class="block text-gray-700 mb-2">Passport Type *</label>
                            <select name="pp_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantData('passportInfo', 'pp_type', this.value)" required>
                                <option value="">Select Type</option>
                                <option value="Regular" ${(applicant.passportInfo.pp_type === 'Regular') ? 'selected' : ''}>Regular</option>
                                <option value="Official" ${(applicant.passportInfo.pp_type === 'Official') ? 'selected' : ''}>Official</option>
                                <option value="Diplomatic" ${(applicant.passportInfo.pp_type === 'Diplomatic') ? 'selected' : ''}>Diplomatic</option>
                            </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Gender *</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('passportInfo', 'pp_gender', this.value)" required>
                            <option value="">Select</option>
                            <option value="male" ${applicant.passportInfo.pp_gender === 'male' ? 'selected' : ''}>Male</option>
                            <option value="female" ${applicant.passportInfo.pp_gender === 'female' ? 'selected' : ''}>Female</option>
                            <option value="other" ${applicant.passportInfo.pp_gender === 'other' ? 'selected' : ''}>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Place of Birth *</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_pob || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_pob', this.value)" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Date of Birth *</label>
                        <input type="text" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 date-input" 
                            value="${applicant.passportInfo.pp_dob ? convertToDisplay(applicant.passportInfo.pp_dob) : ''}" 
                            onchange="handleDateChange('passportInfo', 'pp_dob', this.value)"
                            placeholder="DD/MM/YYYY"
                            required>
                        <p class="text-xs text-gray-500 mt-1">Format: DD/MM/YYYY</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Passport Number *</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_number || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_number', this.value)" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Issuing Authority *</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_issuing_authority || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_issuing_authority', this.value)" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Issue Date *</label>
                        <input type="text" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            value="${applicant.passportInfo.pp_issue_date ? convertToDisplay(applicant.passportInfo.pp_issue_date) : ''}" 
                            onchange="handleDateChange('passportInfo', 'pp_issue_date', this.value)"
                            placeholder="DD/MM/YYYY"
                            required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Expiry Date *</label>
                        <input type="text" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            value="${applicant.passportInfo.pp_expiry_date ? convertToDisplay(applicant.passportInfo.pp_expiry_date) : ''}" 
                            onchange="handleDateChange('passportInfo', 'pp_expiry_date', this.value)"
                            placeholder="DD/MM/YYYY"
                            required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Have you ever lost or had your passport stolen?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pp_have_stolen" value="1" 
                                    ${applicant.passportInfo.pp_have_stolen ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('lost-passport', true); updateApplicantData('passportInfo', 'pp_have_stolen', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pp_have_stolen" value="0" 
                                    ${!applicant.passportInfo.pp_have_stolen ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('lost-passport', false); updateApplicantData('passportInfo', 'pp_have_stolen', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                </div>
                <div id="lost-passport" class="conditional-block mt-4" style="display: ${applicant.passportInfo.pp_have_stolen ? 'block' : 'none'};">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Passport Number</label>
                            <input type="text" name="pp_lost_passport_no" 
                                value="${applicant.passportInfo.pp_lost_passport_no || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('passportInfo', 'pp_lost_passport_no', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Issuing Authority</label>
                            <input type="text" name="pp_lost_passport_authority" 
                                value="${applicant.passportInfo.pp_lost_passport_authority || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('passportInfo', 'pp_lost_passport_authority', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Explanation</label>
                            <textarea name="pp_lost_passport_explanation" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantData('passportInfo', 'pp_lost_passport_explanation', this.value)">${applicant.passportInfo.pp_lost_passport_explanation || ''}</textarea>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-4">* Required fields</p>
            `;
    }

    // Generate Contact Information step with multiple fields
    function generateContactInfoStep(applicant) {

        const generateCountryOptions = (selectedValue) => {
            return countries.map(country =>
                `<option value="${country.code}" ${(selectedValue === country.code) ? 'selected' : ''}>${country.name}</option>`
            ).join('');
        };

        let emailsHTML = '';
        applicant.contactInfo.emails.forEach((email, index) => {
            emailsHTML += `
                    <div class="dynamic-field-group flex items-end">
                        <div class="flex-1">
                            <label class="block text-gray-700 mb-2">Email Address ${index > 0 ? index + 1 : ''}</label>
                            <input type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="${email}" 
                                   onchange="updateContactArrayData('emails', ${index}, this.value)" ${index === 0 ? 'required' : ''}>
                        </div>
                        ${index > 0 ? `
                            <button type="button" class="ml-2 bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg" onclick="removeContactField('emails', ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
        });

        let phonesHTML = '';
        applicant.contactInfo.phones.forEach((phone, index) => {
            phonesHTML += `
                    <div class="dynamic-field-group flex items-end mt-4">
                        <div class="flex-1">
                            <label class="block text-gray-700 mb-2">Other Phone Number ${index > 0 ? index + 1 : ''}</label>
                            <input type="tel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="${phone}" 
                                   onchange="updateContactArrayData('phones', ${index}, this.value)" ${index === 0 ? 'required' : ''}>
                        </div>
                        ${index > 0 ? `
                            <button type="button" class="ml-2 bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg" onclick="removeContactField('phones', ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
        });

        let addressesHTML = '';
        applicant.contactInfo.addresses.forEach((address, index) => {
            addressesHTML += `
                    <div class="address-group">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-700">Address ${index + 1}</h4>
                            ${index > 0 ? `
                                <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removeContactField('addresses', ${index})">
                                    Remove Address
                                </button>
                            ` : ''}
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Line 1 *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${address.line1 || ''}" 
                                       onchange="updateContactAddressData(${index}, 'line1', this.value)" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Line 2</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${address.line2 || ''}" 
                                       onchange="updateContactAddressData(${index}, 'line2', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">City *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${address.city || ''}" 
                                       onchange="updateContactAddressData(${index}, 'city', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">State *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${address.state || ''}" 
                                       onchange="updateContactAddressData(${index}, 'state', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Postal Code *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${address.postalCode || ''}" 
                                       onchange="updateContactAddressData(${index}, 'postalCode', this.value)" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="h-4 w-4 text-blue-600" 
                                           ${address.isCorrespondence ? 'checked' : ''}
                                           onchange="updateContactAddressData(${index}, 'isCorrespondence', this.checked)">
                                    <span class="ml-2">Is this address also your correspondence address?</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">How long have you lived at this address?</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${address.livedInFor || ''}" 
                                       onchange="updateContactAddressData(${index}, 'livedInFor', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">What is the ownership status of your home?</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateContactAddressData(${index}, 'ownershipStatus', this.value)">
                                    <option value="">Select</option>
                                    <option value="owned" ${address.ownershipStatus === 'owned' ? 'selected' : ''}>Owned</option>
                                    <option value="rented" ${address.ownershipStatus === 'rented' ? 'selected' : ''}>Rented</option>
                                    <option value="leased" ${address.ownershipStatus === 'leased' ? 'selected' : ''}>Leased</option>
                                    <option value="other" ${address.ownershipStatus === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
        });

        // Generate options for preferred phone
        let phoneOptionsHTML = '';
        applicant.contactInfo.phones.forEach((phone, index) => {
            if (phone) {
                phoneOptionsHTML += `<option value="${phone}" ${applicant.contactInfo.preferred_other_phone_no === phone ? 'selected' : ''}>${phone}</option>`;
            }
        });

        return `
                <div class="space-y-6 pb-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Personal Information</h3>
                    <div>
                        <label class="block text-gray-700 mb-2">Do you have other name?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pi_have_other_name" value="1" ${applicant.passportInfo.pi_have_other_name ? 'checked' : ''} onchange="toggleConditionalBlock('other-name', true); updateApplicantData('passportInfo', 'pi_have_other_name', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pi_have_other_name" value="0" ${!applicant.passportInfo.pi_have_other_name ? 'checked' : ''} onchange="toggleConditionalBlock('other-name', false); updateApplicantData('passportInfo', 'pi_have_other_name', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <!-- Conditional Other Name Fields -->
                    <div id="other-name" class="conditional-block" style="display: ${applicant.passportInfo.pi_have_other_name ? 'block' : 'none'};">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Other Surname</label>
                                <input type="text" name="pi_other_sur_name" value="${applicant.passportInfo.pi_other_sur_name || ''}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('passportInfo', 'pi_other_sur_name', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Other Given Name</label>
                                <input type="text" name="pi_other_given_name" value="${applicant.passportInfo.pi_other_given_name || ''}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('passportInfo', 'pi_other_given_name', this.value)">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Marital/Relationship Status *</label>
                            <select name="pi_marital_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('passportInfo', 'pi_marital_status', this.value)" required>
                                <option value="">Select Marital Status</option>
                                <option value="Single" ${(applicant.passportInfo.pi_marital_status === 'Single') ? 'selected' : ''}>Single</option>
                                <option value="Married" ${(applicant.passportInfo.pi_marital_status === 'Married') ? 'selected' : ''}>Married</option>
                                <option value="Divorced" ${(applicant.passportInfo.pi_marital_status === 'Divorced') ? 'selected' : ''}>Divorced</option>
                                <option value="Widowed" ${(applicant.passportInfo.pi_marital_status === 'Widowed') ? 'selected' : ''}>Widowed</option>
                                <option value="Widowed" ${(applicant.passportInfo.pi_marital_status === 'Separated') ? 'selected' : ''}>Separated</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Country of Birth *</label>
                            <select name="pi_cob" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('passportInfo', 'pi_cob', this.value)" required>
                                <option value="">Select Country</option>
                                ${generateCountryOptions(applicant.passportInfo.pi_cob)}
                            </select>
                        </div>
                    </div>

                    <!-- Other Nationality -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Other Nationality</h3>
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Do you have any other nationality?</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="pi_have_other_nationality" value="1" ${applicant.passportInfo.pi_have_other_nationality ? 'checked' : ''} onchange="toggleConditionalBlock('other-nationality', true); updateApplicantData('passportInfo', 'pi_have_other_nationality', true)">
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="pi_have_other_nationality" value="0" ${!applicant.passportInfo.pi_have_other_nationality ? 'checked' : ''} onchange="toggleConditionalBlock('other-nationality', false); updateApplicantData('passportInfo', 'pi_have_other_nationality', false)">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>
                        <div id="other-nationality" class="conditional-block" style="display: ${applicant.passportInfo.pi_have_other_nationality ? 'block' : 'none'};">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Country</label>
                                    <select name="pi_other_nationality_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('passportInfo', 'pi_other_nationality_country', this.value)">
                                        <option value="">Select Country</option>
                                        ${generateCountryOptions(applicant.passportInfo.pi_other_nationality_country)}
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Do you have that country passport?</label>
                                    <div class="flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="pi_have_other_country_paasport" value="1" ${applicant.passportInfo.pi_have_other_country_paasport ? 'checked' : ''} onchange="toggleConditionalBlock('other-passport', true); updateApplicantData('passportInfo', 'pi_have_other_country_paasport', true)">
                                            <span class="ml-2">Yes</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" name="pi_have_other_country_paasport" value="0" ${!applicant.passportInfo.pi_have_other_country_paasport ? 'checked' : ''} onchange="toggleConditionalBlock('other-passport', false); updateApplicantData('passportInfo', 'pi_have_other_country_paasport', false)">
                                            <span class="ml-2">No</span>
                                        </label>
                                    </div>
                                </div>
                                <div id="other-passport" class="conditional-block" style="display: ${applicant.passportInfo.pi_have_other_country_paasport ? 'block' : 'none'};">
                                    <label class="block text-gray-700 mb-2">Passport Number</label>
                                    <input type="text" name="pi_other_country_passport" value="${applicant.passportInfo.pi_other_country_passport || ''}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('passportInfo', 'pi_other_country_passport', this.value)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-y-6 border-t pt-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Contact Details</h3>
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Email Addresses</h3>
                        ${emailsHTML}
                        <button type="button" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center" onclick="addContactField('emails')">
                            <i class="fas fa-plus mr-2"></i> Add Another Email
                        </button>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Phone Numbers</h3>
                        <div>
                            <label class="block text-gray-700 mb-2">Primary Phone Number *</label>
                            <input type="tel" name="pi_primary_no" value="${applicant.contactInfo.primary_no || ''}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('contactInfo', 'pi_primary_no', this.value)" required>
                        </div>
                        <div class="mt-4">
                            <label class="block text-gray-700 mb-2">Secondary Phone Number</label>
                            <input type="tel" name="pi_secondary_no" value="${applicant.contactInfo.secondary_no || ''}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('contactInfo', 'pi_secondary_no', this.value)">
                        </div>
                        <div class="mt-4">
                            <label class="block text-gray-700 mb-2">Work Phone Number</label>
                            <input type="tel" name="pi_work_no" value="${applicant.contactInfo.work_no || ''}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="updateApplicantData('contactInfo', 'pi_work_no', this.value)">
                        </div>
                        ${phonesHTML}
                        <button type="button" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center" onclick="addContactField('phones')">
                            <i class="fas fa-plus mr-2"></i> Add Other Phone
                        </button>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Addresses</h3>
                        ${addressesHTML}
                        <button type="button" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center" onclick="addContactField('addresses')">
                            <i class="fas fa-plus mr-2"></i> Add Another Address
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">What is your preferred contact number?</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('contactInfo', 'preferred_other_phone_no', this.value)">
                            <option value="">Select</option>
                            ${phoneOptionsHTML}
                        </select>
                    </div>
                </div>
            `;
    }

    // Generate NID Information step
    function generateNIDInfoStep(applicant) {
        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Do you have a valid national identity card? *</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="has_nid" value="yes" class="h-4 w-4 text-blue-600" 
                                       ${applicant.nidInfo.has_nid === true ? 'checked' : ''}
                                       onchange="updateApplicantData('nidInfo', 'has_nid', this.value === 'yes')" required>
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="has_nid" value="no" class="h-4 w-4 text-blue-600"
                                       ${applicant.nidInfo.has_nid === false ? 'checked' : ''}
                                       onchange="updateApplicantData('nidInfo', 'has_nid', this.value === 'yes')" required>
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="nid-details" class="${applicant.nidInfo.has_nid ? 'block' : 'hidden'}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 mb-2">NID Number *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.nidInfo.nid_number || ''}" 
                                       onchange="updateApplicantData('nidInfo', 'nid_number', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Issuing Authority *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.nidInfo.nid_issuing_authority || ''}" 
                                       onchange="updateApplicantData('nidInfo', 'nid_issuing_authority', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Issue Date (If applicable)</label>
                                <input type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${applicant.nidInfo.nid_isue_date ? convertToDisplay(applicant.nidInfo.nid_isue_date) : ''}" 
                                    onchange="handleDateChange('nidInfo', 'nid_isue_date', this.value)"
                                    placeholder="DD/MM/YYYY"
                                    required>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-4">* Required fields</p>
            `;
    }

    // Generate Family Information step
    function generateFamilyInfoStep(applicant) {
        let familyMembersHTML = '';
        applicant.familyInfo.familyMembers.forEach((member, index) => {
            const showSpouseSection = member.relation === 'spouse';
            const showSpouseAddress = member.have_same_address === 'Others';
            familyMembersHTML += `
                    <div class="family-member-group">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-700">Family Member ${index + 1}</h4>
                            <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removeFamilyMember(${index})">
                                Remove Member
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Relation *</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateFamilyMemberData(${index}, 'relation', this.value)" required>
                                    <option value="">Select</option>
                                    <option value="father" ${member.relation === 'father' ? 'selected' : ''}>Father</option>
                                    <option value="mother" ${member.relation === 'mother' ? 'selected' : ''}>Mother</option>
                                    <option value="spouse" ${member.relation === 'spouse' ? 'selected' : ''}>Spouse</option>
                                    <option value="son" ${member.relation === 'son' ? 'selected' : ''}>Son</option>
                                    <option value="daughter" ${member.relation === 'daughter' ? 'selected' : ''}>Daughter</option>
                                    <option value="brother" ${member.relation === 'brother' ? 'selected' : ''}>Brother</option>
                                    <option value="sister" ${member.relation === 'sister' ? 'selected' : ''}>Sister</option>
                                    <option value="other" ${member.relation === 'other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Given Name *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${member.givenName || ''}" 
                                       onchange="updateFamilyMemberData(${index}, 'givenName', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Family Name *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${member.familyName || ''}" 
                                       onchange="updateFamilyMemberData(${index}, 'familyName', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Date of Birth</label>
                                <input type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${member.dob ? convertToDisplay(member.dob) : ''}" 
                                    onchange="updateFamilyMemberData(${index}, 'dob', this.value)"
                                    placeholder="DD/MM/YYYY"
                                    required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Country of Nationality</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${member.nationality || ''}" 
                                       onchange="updateFamilyMemberData(${index}, 'nationality', this.value)">
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="h-4 w-4 text-blue-600" 
                                           ${member.liveWith ? 'checked' : ''}
                                           onchange="updateFamilyMemberData(${index}, 'liveWith', this.checked)">
                                    <span class="ml-2">Do they currently live with you?</span>
                                </label>
                            </div>
                            <div class="md:col-span-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" class="h-4 w-4 text-blue-600" 
                                           ${member.travellingUK ? 'checked' : ''}
                                           onchange="updateFamilyMemberData(${index}, 'travellingUK', this.checked)">
                                    <span class="ml-2">Will they be travelling with you to the UK?</span>
                                </label>
                            </div>
                            <div id="passport-section-${index}" class="md:col-span-2 ${member.travellingUK ? 'block' : 'hidden'}">
                                <label class="block text-gray-700 mb-2">Their Passport Number *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${member.passportNo || ''}" 
                                       onchange="updateFamilyMemberData(${index}, 'passportNo', this.value)" ${member.travellingUK ? 'required' : ''}>
                            </div>

                            <!-- In USA Toggle - FOR ALL MEMBERS -->
                            <div class="mt-4">
                                <label class="block text-gray-700 mb-2">Is this family member in the USA?</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="fm_in_usa_${index}" value="1" 
                                            ${member.in_usa ? 'checked' : ''}
                                            onchange="updateFamilyMemberData(${index}, 'in_usa', true)">
                                        <span class="ml-2">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="fm_in_usa_${index}" value="0" 
                                            ${!member.in_usa ? 'checked' : ''}
                                            onchange="updateFamilyMemberData(${index}, 'in_usa', false)">
                                        <span class="ml-2">No</span>
                                    </label>
                                </div>
                            </div>
                            <div id="usa-status-section-${index}" class="md:col-span-2 ${member.in_usa ? 'block' : 'hidden'} mt-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Status in USA</label>
                                    <select name="fm_person_status_${index}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateFamilyMemberData(${index}, 'person_status', this.value)">
                                        <option value="">Select Status</option>
                                        <option value="Citizen" ${(member.person_status === 'Citizen') ? 'selected' : ''}>Citizen</option>
                                        <option value="Permanent Resident" ${(member.person_status === 'Permanent Resident') ? 'selected' : ''}>Permanent Resident</option>
                                        <option value="Non immigrant" ${(member.person_status === 'Non immigrant') ? 'selected' : ''}>Non immigrant</option>
                                        <option value="Others" ${(member.person_status === 'Others') ? 'selected' : ''}>Others</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-4">
                            <!-- Spouse Specific Fields (Conditional) -->
                            <div id="spouse-fields-${index}" class="conditional-block mt-4" style="display: ${showSpouseSection ? 'block' : 'none'};">
                                <div class="border-t pt-4">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">Spouse Specific Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">Place of Birth</label>
                                            <input type="text" name="fm_pob_${index}" 
                                                value="${member.pob || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateFamilyMemberData(${index}, 'pob', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Country of Birth</label>
                                            <select name="fm_boc_country_${index}" 
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    onchange="updateFamilyMemberData(${index}, 'boc_country', this.value)">
                                                <option value="">Select Country</option>
                                                ${countries.map(country => 
                                                    `<option value="${country.code}" ${(member.boc_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                                ).join('')}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Telephone</label>
                                            <input type="tel" name="fm_spouse_telephone_${index}" 
                                                value="${member.spouse_telephone || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateFamilyMemberData(${index}, 'spouse_telephone', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Email</label>
                                            <input type="email" name="fm_spouse_email_${index}" 
                                                value="${member.spouse_email || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateFamilyMemberData(${index}, 'spouse_email', this.value)">
                                        </div>
                                    </div>

                                    <!-- Spouse Address Toggle -->
                                    <div class="mt-4">
                                        <label class="block text-gray-700 mb-2">Spouse Address</label>
                                        <select name="fm_have_same_address_${index}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateFamilyMemberData(${index}, 'have_same_address', this.value)">
                                            <option value="">Select Address Type</option>
                                            <option value="Same as home address" ${(member.have_same_address === 'Same as home address') ? 'selected' : ''}>Same as home address</option>
                                            <option value="Same as Billing" ${(member.have_same_address === 'Same as Billing') ? 'selected' : ''}>Same as Billing</option>
                                            <option value="Same as U.S. contact address" ${(member.have_same_address === 'Same as U.S. contact address') ? 'selected' : ''}>Same as U.S. contact address</option>
                                            <option value="Others" ${(member.have_same_address === 'Others') ? 'selected' : ''}>Others</option>
                                        </select>
                                    </div>

                                    <!-- Spouse Address Fields (Conditional) -->
                                    <div id="spouse-address-fields-${index}" class="conditional-block mt-4" style="display: ${showSpouseAddress ? 'block' : 'none'};">
                                        <div class="border-t pt-4">
                                            <h5 class="text-sm font-medium text-gray-700 mb-3">Spouse Address Details</h5>
                                            <div class="grid grid-cols-1 gap-4">
                                                <div>
                                                    <label class="block text-gray-700 mb-2">Address Line 1</label>
                                                    <input type="text" name="fm_spouse_address_line_1_${index}" 
                                                        value="${member.spouse_address_line_1 || ''}" 
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        onchange="updateFamilyMemberData(${index}, 'spouse_address_line_1', this.value)">
                                                </div>
                                                <div>
                                                    <label class="block text-gray-700 mb-2">Address Line 2</label>
                                                    <input type="text" name="fm_spouse_address_line_2_${index}" 
                                                        value="${member.spouse_address_line_2 || ''}" 
                                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        onchange="updateFamilyMemberData(${index}, 'spouse_address_line_2', this.value)">
                                                </div>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <label class="block text-gray-700 mb-2">City</label>
                                                        <input type="text" name="fm_spouse_address_city_${index}" 
                                                            value="${member.spouse_address_city || ''}" 
                                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            onchange="updateFamilyMemberData(${index}, 'spouse_address_city', this.value)">
                                                    </div>
                                                    <div>
                                                        <label class="block text-gray-700 mb-2">State</label>
                                                        <input type="text" name="fm_spouse_address_state_${index}" 
                                                            value="${member.spouse_address_state || ''}" 
                                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            onchange="updateFamilyMemberData(${index}, 'spouse_address_state', this.value)">
                                                    </div>
                                                    <div>
                                                        <label class="block text-gray-700 mb-2">Zip Code</label>
                                                        <input type="text" name="fm_spouse_address_zip_code_${index}" 
                                                            value="${member.spouse_address_zip_code || ''}" 
                                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            onchange="updateFamilyMemberData(${index}, 'spouse_address_zip_code', this.value)">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-gray-700 mb-2">Country</label>
                                                    <select name="fm_spouse_address_country_${index}" 
                                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            onchange="updateFamilyMemberData(${index}, 'spouse_address_country', this.value)">
                                                        <option value="">Select Country</option>
                                                        ${countries.map(country => 
                                                            `<option value="${country.code}" ${(member.spouse_address_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                                        ).join('')}
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        });

        return `
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Family Members</h3>
                        ${familyMembersHTML || '<p class="text-gray-500">No family members added yet.</p>'}
                        <button type="button" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center" onclick="addFamilyMember()">
                            <i class="fas fa-plus mr-2"></i> Add Family Member
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Do you have any family in the UK?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="has_relative_in_uk" value="yes" class="h-4 w-4 text-blue-600" 
                                       ${applicant.familyInfo.hasRelativeInUK === true ? 'checked' : ''}
                                       onchange="updateApplicantData('familyInfo', 'hasRelativeInUK', this.value === 'yes')">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="has_relative_in_uk" value="no" class="h-4 w-4 text-blue-600"
                                       ${applicant.familyInfo.hasRelativeInUK === false ? 'checked' : ''}
                                       onchange="updateApplicantData('familyInfo', 'hasRelativeInUK', this.value === 'yes')">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    
                    ${applicant.familyInfo.hasRelativeInUK ? `
                        <div class="address-group">
                            <h4 class="font-medium text-gray-700 mb-4">Relative's Address in UK</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">Line 1</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${applicant.familyInfo.relativeAddress.line1 || ''}" 
                                           onchange="updateFamilyRelativeAddress('line1', this.value)">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">Line 2</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${applicant.familyInfo.relativeAddress.line2 || ''}" 
                                           onchange="updateFamilyRelativeAddress('line2', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">City</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${applicant.familyInfo.relativeAddress.city || ''}" 
                                           onchange="updateFamilyRelativeAddress('city', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">State</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${applicant.familyInfo.relativeAddress.state || ''}" 
                                           onchange="updateFamilyRelativeAddress('state', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Postal Code</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${applicant.familyInfo.relativeAddress.postalCode || ''}" 
                                           onchange="updateFamilyRelativeAddress('postalCode', this.value)">
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
    }

    // Generate Accommodation Details step - FIXED VERSION
    function generateAccommodationDetailsStep(applicant) {
        // Ensure arrays exist
        if (!applicant.accommodationDetails.hotels) {
            applicant.accommodationDetails.hotels = [''];
        }
        if (!applicant.accommodationDetails.addresses) {
            applicant.accommodationDetails.addresses = [{
                line1: '',
                line2: '',
                city: '',
                state: '',
                postalCode: ''
            }];
        }

        let addressesHTML = '';
        applicant.accommodationDetails.addresses.forEach((address, index) => {
            const hotelValue = applicant.accommodationDetails.hotels[index] || '';

            addressesHTML += `
                    <div class="address-group">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-700">Accommodation Address ${index + 1}</h4>
                            ${index > 0 ? `
                                <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removeAccommodationAddress(${index})">
                                    Remove Address
                                </button>
                            ` : ''}
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Hotel Name (if applicable)</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${hotelValue}" 
                                    onchange="updateAccommodationHotel(${index}, this.value)">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Address Line 1 *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${address.line1 || ''}" 
                                    onchange="updateAccommodationAddressData(${index}, 'line1', this.value)" ${applicant.accommodationDetails.hasAddress ? 'required' : ''}>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Address Line 2</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${address.line2 || ''}" 
                                    onchange="updateAccommodationAddressData(${index}, 'line2', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">City *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${address.city || ''}" 
                                    onchange="updateAccommodationAddressData(${index}, 'city', this.value)" ${applicant.accommodationDetails.hasAddress ? 'required' : ''}>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">State/Province *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${address.state || ''}" 
                                    onchange="updateAccommodationAddressData(${index}, 'state', this.value)" ${applicant.accommodationDetails.hasAddress ? 'required' : ''}>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Postal Code *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${address.postalCode || ''}" 
                                    onchange="updateAccommodationAddressData(${index}, 'postalCode', this.value)" ${applicant.accommodationDetails.hasAddress ? 'required' : ''}>
                            </div>
                        </div>
                    </div>
                `;
        });

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Do you have accommodation arranged in the UK? *</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="ad_have_address" value="yes" class="h-4 w-4 text-blue-600" 
                                    ${applicant.accommodationDetails.hasAddress === true ? 'checked' : ''}
                                    onchange="updateApplicantData('accommodationDetails', 'hasAddress', this.value === 'yes')" required>
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="ad_have_address" value="no" class="h-4 w-4 text-blue-600"
                                    ${applicant.accommodationDetails.hasAddress === false ? 'checked' : ''}
                                    onchange="updateApplicantData('accommodationDetails', 'hasAddress', this.value === 'yes')" required>
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    
                    ${applicant.accommodationDetails.hasAddress === true ? `
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Accommodation Details</h3>
                            ${addressesHTML}
                            <button type="button" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center" onclick="addAccommodationAddress()">
                                <i class="fas fa-plus mr-2"></i> Add Another Accommodation
                            </button>
                        </div>
                    ` : ''}
                    
                    ${applicant.accommodationDetails.hasAddress === false ? `
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Accommodation Plans</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-gray-700 mb-2">Please describe your accommodation plans in the UK:</label>
                                <textarea 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    rows="4"
                                    placeholder="Example: I plan to stay in hotels and will book accommodation upon arrival. I'm considering staying in central London area and will make reservations through booking websites..."
                                    onchange="updateApplicantData('accommodationDetails', 'custom_accommodation', this.value)"
                                >${applicant.accommodationDetails.custom_accommodation || ''}</textarea>
                                <p class="text-sm text-gray-500 mt-2">Please provide details about your accommodation arrangements, such as hotel bookings, area preferences, or any other plans.</p>
                            </div>
                        </div>
                    ` : ''}
                </div>
                <p class="text-sm text-gray-500 mt-4">* Required fields</p>
            `;
    }

    // Generate Employment Information step
    function generateEmploymentInfoStep(applicant) {
        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">What is your employment status? *</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('employmentInfo', 'employmentStatus', this.value)" required>
                            <option value="">Select</option>
                            <option value="business-man" ${applicant.employmentInfo.employmentStatus === 'business-man' ? 'selected' : ''}>Businessman</option>
                            <option value="employed" ${applicant.employmentInfo.employmentStatus === 'employed' ? 'selected' : ''}>Employed</option>
                            <option value="self-employed" ${applicant.employmentInfo.employmentStatus === 'self-employed' ? 'selected' : ''}>Self-Employed</option>
                            <option value="student" ${applicant.employmentInfo.employmentStatus === 'student' ? 'selected' : ''}>Student</option>
                            <option value="unemployed" ${applicant.employmentInfo.employmentStatus === 'unemployed' ? 'selected' : ''}>Unemployed</option>
                            <option value="retired" ${applicant.employmentInfo.employmentStatus === 'retired' ? 'selected' : ''}>Retired</option>
                        </select>
                    </div>
                    
                    <div id="employment-details">
                        ${applicant.employmentInfo.employmentStatus === 'business-man' ? `
                            <div>
                                <label class="block text-gray-700 mb-2">What is your business title? *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.employmentInfo.businessTitle || ''}" 
                                       onchange="updateApplicantData('employmentInfo', 'businessTitle', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">What is your business details? *</label>
                                <textarea type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       onchange="updateApplicantData('employmentInfo', 'businessDetails', this.value)" required>${applicant.employmentInfo.businessDetails || ''}</textarea>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">How much do you earn from this business in a year? *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.employmentInfo.yearlyEarning || ''}" 
                                       onchange="updateApplicantData('employmentInfo', 'yearlyEarning', this.value)" required>
                            </div>
                        ` : ''}

                        ${applicant.employmentInfo.employmentStatus === 'self-employed' ? `
                            <div>
                                <label class="block text-gray-700 mb-2">What is your job? *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.employmentInfo.jobDetails || ''}" 
                                       onchange="updateApplicantData('employmentInfo', 'jobDetails', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">How much do you earn from this job in a year? *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.employmentInfo.yearlyEarning || ''}" 
                                       onchange="updateApplicantData('employmentInfo', 'yearlyEarning', this.value)" required>
                            </div>
                        ` : ''}
                        
                        ${applicant.employmentInfo.employmentStatus === 'employed' ? `
                            <div>
                                <label class="block text-gray-700 mb-2">Job Title *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.employmentInfo.jobTitle || ''}" 
                                       onchange="updateApplicantData('employmentInfo', 'jobTitle', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">How much do you earn each month - after tax? *</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.employmentInfo.monthlyIncome || ''}" 
                                       onchange="updateApplicantData('employmentInfo', 'monthlyIncome', this.value)" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Job Description</label>
                                <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="3"
                                          onchange="updateApplicantData('employmentInfo', 'jobDescription', this.value)">${applicant.employmentInfo.jobDescription || ''}</textarea>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-4">* Required fields</p>
            `;
    }

    // Generate Income & Expenditure step
    function generateIncomeExpenditureStep(applicant) {
        let paymentInfoHTML = '';
        if (applicant.incomeExpenditure.paymentInfo) {
            applicant.incomeExpenditure.paymentInfo.forEach((payment, index) => {
                paymentInfoHTML += `
                        <div class="dynamic-field-group">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-medium text-gray-700">Payment Source ${index + 1}</h4>
                                ${index > 0 ? `
                                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removePaymentInfo(${index})">
                                        Remove Payment
                                    </button>
                                ` : ''}
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Currency</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${payment.currency || ''}" 
                                           onchange="updatePaymentInfoData(${index}, 'currency', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Amount</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${payment.amount || ''}" 
                                           onchange="updatePaymentInfoData(${index}, 'amount', this.value)">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">What are you being paid for</label>
                                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                           value="${payment.paidFor || ''}" 
                                           onchange="updatePaymentInfoData(${index}, 'paidFor', this.value)">
                                </div>
                            </div>
                        </div>
                    `;
            });
        }

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Do you have another or any savings?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="have_savings" value="yes" class="h-4 w-4 text-blue-600" 
                                       ${applicant.incomeExpenditure.haveSavings === true ? 'checked' : ''}
                                       onchange="updateApplicantData('incomeExpenditure', 'haveSavings', this.value === 'yes')">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="have_savings" value="no" class="h-4 w-4 text-blue-600"
                                       ${applicant.incomeExpenditure.haveSavings === false ? 'checked' : ''}
                                       onchange="updateApplicantData('incomeExpenditure', 'haveSavings', this.value === 'yes')">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">How much money are you personally planning to spend on your visit to the UK? *</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.incomeExpenditure.planningToExpense || ''}" 
                               onchange="updateApplicantData('incomeExpenditure', 'planningToExpense', this.value)" required>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">What is the total amount of money you spend each month?</label>
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.incomeExpenditure.totalExpenseInBd || ''}" 
                               onchange="updateApplicantData('incomeExpenditure', 'totalExpenseInBd', this.value)">
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Payment Information</h3>
                        ${paymentInfoHTML}
                        <button type="button" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center" onclick="addPaymentInfo()">
                            <i class="fas fa-plus mr-2"></i> Add Payment Source
                        </button>
                    </div>
                </div>
            `;
    }

    // Generate Travel Information step
    function generateTravelInfoStep(applicant) {
        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">What is the main reason for your visit to the UK? *</label>
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('travelInfo', 'visitMainReason', this.value)" required>
                            <option value="">Select</option>
                            <option value="tourism" ${applicant.travelInfo.visitMainReason === 'tourism' ? 'selected' : ''}>Tourism</option>
                            <option value="business" ${applicant.travelInfo.visitMainReason === 'business' ? 'selected' : ''}>Business</option>
                            <option value="study" ${applicant.travelInfo.visitMainReason === 'study' ? 'selected' : ''}>Study</option>
                            <option value="family" ${applicant.travelInfo.visitMainReason === 'family' ? 'selected' : ''}>Family Visit</option>
                            <option value="medical" ${applicant.travelInfo.visitMainReason === 'medical' ? 'selected' : ''}>Medical Treatment</option>
                        </select>
                    </div>
                    
                    <div id="travel-reason-details">
                        ${applicant.travelInfo.visitMainReason === 'business' ? `
                            <div>
                                <label class="block text-gray-700 mb-2">What is the main reason for your business visit to the UK?</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantData('travelInfo', 'businessReasonToVisitUk', this.value)">
                                    <option value="">Select</option>
                                    <option value="meetings" ${applicant.travelInfo.businessReasonToVisitUk === 'meetings' ? 'selected' : ''}>Attend business meetings</option>
                                    <option value="training" ${applicant.travelInfo.businessReasonToVisitUk === 'training' ? 'selected' : ''}>Business-related training</option>
                                    <option value="conference" ${applicant.travelInfo.businessReasonToVisitUk === 'conference' ? 'selected' : ''}>Attend conference</option>
                                    <option value="negotiations" ${applicant.travelInfo.businessReasonToVisitUk === 'negotiations' ? 'selected' : ''}>Business negotiations</option>
                                </select>
                            </div>
                        ` : ''}
                        
                        ${applicant.travelInfo.visitMainReason === 'tourism' ? `
                            <div>
                                <label class="block text-gray-700 mb-2">What is the main reason for your holiday visit to the UK?</label>
                                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantData('travelInfo', 'tourismReasonToVisitUk', this.value)">
                                    <option value="">Select</option>
                                    <option value="tourist" ${applicant.travelInfo.tourismReasonToVisitUk === 'tourist' ? 'selected' : ''}>Tourist</option>
                                    <option value="family" ${applicant.travelInfo.tourismReasonToVisitUk === 'family' ? 'selected' : ''}>Visiting family</option>
                                    <option value="friends" ${applicant.travelInfo.tourismReasonToVisitUk === 'friends' ? 'selected' : ''}>Visiting friends</option>
                                </select>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Give details of the main purpose of your visit and anything else you plan to do on your trip.</label>
                        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="3"
                                  maxlength="500"
                                  onchange="updateApplicantData('travelInfo', 'activities', this.value)">${applicant.travelInfo.activities || ''}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Maximum 500 characters</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 mb-2">Date you plan to arrive in the UK *</label>
                            <input type="text" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                value="${applicant.travelInfo.arrivalDate ? convertToDisplay(applicant.travelInfo.arrivalDate) : ''}" 
                                onchange="handleDateChange('travelInfo', 'arrivalDate', this.value)"
                                placeholder="DD/MM/YYYY"
                                required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Date you plan to leave the UK *</label>
                            <input type="text" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                value="${applicant.travelInfo.leaveDate ? convertToDisplay(applicant.travelInfo.leaveDate) : ''}" 
                                onchange="handleDateChange('travelInfo', 'leaveDate', this.value)"
                                placeholder="DD/MM/YYYY"
                                required>
                        </div>
                    </div>
                </div>
            `;
    }

    // Generate Travel History step
    function generateTravelHistoryStep(applicant) {
        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Travel History</label>
                        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="25"
                                  placeholder="Please provide details of your travel history including countries visited, dates, and purpose of visit"
                                  onchange="updateApplicantData('travelHistory', 'history', this.value)">${applicant.travelHistory.history || ''}</textarea>
                        <p class="text-sm text-gray-500 mt-1">Provide details of countries visited, dates, and purpose of visit</p>
                    </div>
                </div>
            `;
    }





    // FOR USA Start
    // Travel Information Step (Based on Excel TI section)
    function generatetravelInfoForUSAStep(applicant) {
        const ti = applicant.travelInfoForUSA || {};
        const locations = ti.locations || [{
            address_line_1: '',
            address_line_2: '',
            city: '',
            state: '',
            zip_code: ''
        }];

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Purpose of Travel *</label>
                        <input type="text" name="ti_travel_purpose" 
                            value="${ti.ti_travel_purpose || ''}" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_travel_purpose', this.value)" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Have you made travel plans?</label>
                        <select name="ti_have_travel_plan" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="handleTravelPlanChange(this.value); updateApplicantDataForUSA('travelInfoForUSA', 'ti_have_travel_plan', this.value)">
                            <option value="">Select</option>
                            <option value="yes" ${(ti.ti_have_travel_plan === 'yes') ? 'selected' : ''}>Yes</option>
                            <option value="no" ${(ti.ti_have_travel_plan === 'no') ? 'selected' : ''}>No</option>
                        </select>
                    </div>

                    <!-- No Travel Plan Fields -->
                    <div id="no-travel-plan" class="conditional-block" style="display: ${ti.ti_have_travel_plan === 'no' ? 'block' : 'none'};">
                        <!-- ... no travel plan fields content ... -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Intended date of arrival</label>
                                <input type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${ti.ti_intended_arrival_date ? convertToDisplay(ti.ti_intended_arrival_date) : ''}" 
                                    onchange="handleDateChange('travelInfoForUSA', 'ti_intended_arrival_date', this.value)"
                                    placeholder="DD/MM/YYYY"
                                    required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Length of stay</label>
                                <input type="text" name="ti_stay_length" 
                                    value="${ti.ti_stay_length || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_stay_length', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Number + Select Option (Day, Month, Year)</label>
                                <select name="ti_length_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_length_type', this.value)">
                                    <option value="">Select</option>
                                    <option value="Days" ${(ti.ti_length_type === 'Days') ? 'selected' : ''}>Days</option>
                                    <option value="Months" ${(ti.ti_length_type === 'Months') ? 'selected' : ''}>Months</option>
                                    <option value="Years" ${(ti.ti_length_type === 'Years') ? 'selected' : ''}>Years</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Yes Travel Plan Fields -->
                    <div id="yes-travel-plan" class="conditional-block" style="display: ${ti.ti_have_travel_plan === 'yes' ? 'block' : 'none'};">
                        <!-- ... yes travel plan fields content ... -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Date of Arrival in the USA</label>
                                <input type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${ti.ti_arrival_date ? convertToDisplay(ti.ti_arrival_date) : ''}" 
                                    onchange="handleDateChange('travelInfoForUSA', 'ti_arrival_date', this.value)"
                                    placeholder="DD/MM/YYYY"
                                    required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Arrival Flight Number</label>
                                <input type="text" name="ti_arrival_flight_no" 
                                    value="${ti.ti_arrival_flight_no || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_arrival_flight_no', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Arrival City</label>
                                <input type="text" name="ti_arrival_city" 
                                    value="${ti.ti_arrival_city || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_arrival_city', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Date of Departure</label>
                                <input type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                    value="${ti.ti_departure_date ? convertToDisplay(ti.ti_departure_date) : ''}" 
                                    onchange="handleDateChange('travelInfoForUSA', 'ti_departure_date', this.value)"
                                    placeholder="DD/MM/YYYY"
                                    required>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Departure Flight Number</label>
                                <input type="text" name="ti_departure_flight_no" 
                                    value="${ti.ti_departure_flight_no || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_departure_flight_no', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Departure City</label>
                                <input type="text" name="ti_departure_city" 
                                    value="${ti.ti_departure_city || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_departure_city', this.value)">
                            </div>
                        </div>
                    </div>

                    <!-- Locations to Visit -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Locations You Plan to Visit</h3>
                        <div id="location-fields">
                            ${generateLocationFields(locations)}
                        </div>
                        <button type="button" onclick="addLocationField()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add Another Location
                        </button>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Who is paying for your trip?</label>
                        <select name="trip_payment" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="handlePaymentChange(this.value); updateApplicantDataForUSA('travelInfoForUSA', 'trip_payment', this.value)">
                            <option value="">Select</option>
                            <option value="Self" ${(ti.trip_payment === 'Self') ? 'selected' : ''}>Self</option>
                            <option value="Other person" ${(ti.trip_payment === 'Other person') ? 'selected' : ''}>Other person</option>
                            <option value="Present employer" ${(ti.trip_payment === 'Present employer') ? 'selected' : ''}>Present employer</option>
                            <option value="Employer in the USA" ${(ti.trip_payment === 'Employer in the USA') ? 'selected' : ''}>Employer in the USA</option>
                            <option value="Other Company" ${(ti.trip_payment === 'Other Company') ? 'selected' : ''}>Other Company</option>
                        </select>
                    </div>

                    <!-- Payment Fields for Other Person / Others- -->
                    <div id="other-person-payment" class="conditional-block" style="display: ${(ti.trip_payment === 'Other person') ? 'block' : 'none'};">
                        <!-- ... other person payment fields content ... -->
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Paying Person Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Surname of Paying Person</label>
                                <input type="text" name="trip_paying_person_surname" 
                                    value="${ti.trip_paying_person_surname || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'trip_paying_person_surname', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Given Name of Paying Person</label>
                                <input type="text" name="ti_trip_paying_person_given_name" 
                                    value="${ti.ti_trip_paying_person_given_name || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_given_name', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Telephone of Paying Person</label>
                                <input type="tel" name="ti_trip_paying_person_telephone" 
                                    value="${ti.ti_trip_paying_person_telephone || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_telephone', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Email of Paying Person</label>
                                <input type="email" name="ti_trip_paying_person_email" 
                                    value="${ti.ti_trip_paying_person_email || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_email', this.value)">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 mb-2">Relationship to Paying Person</label>
                                <input type="text" name="_trip_paying_person_relationship" 
                                    value="${ti._trip_paying_person_relationship || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', '_trip_paying_person_relationship', this.value)">
                            </div>
                        </div>


                        <!-- Address Toggle for Paying Person -->
                        <div class="mt-4">
                            <label class="block text-gray-700 mb-2">Is the address of paying person same as yours?</label>
                            <div class="flex space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="trip_paying_person_have_same_address" value="1" ${ti.trip_paying_person_have_same_address ? 'checked' : ''} onchange="toggleConditionalBlock('paying-person-address', false); updateApplicantDataForUSA('travelInfoForUSA', 'trip_paying_person_have_same_address', true)">
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="trip_paying_person_have_same_address" value="0" ${!ti.trip_paying_person_have_same_address ? 'checked' : ''} onchange="toggleConditionalBlock('paying-person-address', true); updateApplicantDataForUSA('travelInfoForUSA', 'trip_paying_person_have_same_address', false)">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>

                        <!-- Conditional Address Block for Paying Person -->
                        <div id="paying-person-address" class="conditional-block mt-4" style="display: ${!ti.trip_paying_person_have_same_address ? 'block' : 'none'};">
                            <!-- ... paying person address fields ... -->
                            <div class="border-t pt-6">
                                <!-- Conditional Address Block for Paying Person -->
                                <div id="paying-person-address" class="conditional-block mt-4" style="display: ${!ti.trip_paying_person_have_same_address ? 'block' : 'none'};">
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">Address Line 1</label>
                                            <input type="text" name="ti_trip_paying_person_address_line_1" 
                                                value="${ti.ti_trip_paying_person_address_line_1 || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_address_line_1', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Address Line 2</label>
                                            <input type="text" name="ti_trip_paying_person_address_line_2" 
                                                value="${ti.ti_trip_paying_person_address_line_2 || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_address_line_2', this.value)">
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-gray-700 mb-2">City</label>
                                                <input type="text" name="ti_trip_paying_person_address_city" 
                                                    value="${ti.ti_trip_paying_person_address_city || ''}" 
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_address_city', this.value)">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">State</label>
                                                <input type="text" name="ti_trip_paying_person_address_state" 
                                                    value="${ti.ti_trip_paying_person_address_state || ''}" 
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_address_state', this.value)">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Zip Code</label>
                                                <input type="text" name="ti_trip_paying_person_address_zip_code" 
                                                    value="${ti.ti_trip_paying_person_address_zip_code || ''}" 
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_person_address_zip_code', this.value)">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Country</label>
                                            <select name="trip_paying_person_address_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    onchange="updateApplicantDataForUSA('travelInfoForUSA', 'trip_paying_person_address_country', this.value)">
                                                <option value="">Select Country</option>
                                                ${countries.map(country => 
                                                    `<option value="${country.code}" ${(ti.trip_paying_person_address_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                                ).join('')}
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Fields for Other Company -->
                    <div id="other-company-payment" class="conditional-block" style="display: ${ti.trip_payment === 'Other Company' ? 'block' : 'none'};">
                        <!-- ... other company payment fields content ... -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-800 mb-4">Other Company Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Company Name</label>
                                    <input type="text" name="ti_trip_paying_company_name" 
                                        value="${ti.ti_trip_paying_company_name || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_name', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Company Telephone</label>
                                    <input type="tel" name="ti_trip_paying_company_telephone" 
                                        value="${ti.ti_trip_paying_company_telephone || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_telephone', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Company Email</label>
                                    <input type="email" name="ti_trip_paying_company_email" 
                                        value="${ti.ti_trip_paying_company_email || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_email', this.value)">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">Relationship to Company</label>
                                    <input type="text" name="ti_trip_paying_company_relationship" 
                                        value="${ti.ti_trip_paying_company_relationship || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_relationship', this.value)">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 mt-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Address Line 1</label>
                                    <input type="text" name="ti_trip_paying_company_address_line_1" 
                                        value="${ti.ti_trip_paying_company_address_line_1 || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_address_line_1', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Address Line 2</label>
                                    <input type="text" name="ti_trip_paying_company_address_line_2" 
                                        value="${ti.ti_trip_paying_company_address_line_2 || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_address_line_2', this.value)">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">City</label>
                                        <input type="text" name="ti_trip_paying_company_address_city" 
                                            value="${ti.ti_trip_paying_company_address_city || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_address_city', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">State</label>
                                        <input type="text" name="ti_trip_paying_company_address_state" 
                                            value="${ti.ti_trip_paying_company_address_state || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_address_state', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Zip Code</label>
                                        <input type="text" name="ti_trip_paying_company_address_zip_code" 
                                            value="${ti.ti_trip_paying_company_address_zip_code || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_address_zip_code', this.value)">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Country</label>
                                    <select name="ti_trip_paying_company_address_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'ti_trip_paying_company_address_country', this.value)">
                                        <option value="">Select Country</option>
                                        ${countries.map(country => 
                                            `<option value="${country.code}" ${(ti.ti_trip_paying_company_address_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
    }

    // Simplified Helper Functions using toggleConditionalBlock
    function handleTravelPlanChange(value) {
        toggleConditionalBlock('no-travel-plan', value === 'no');
        toggleConditionalBlock('yes-travel-plan', value === 'yes');
    }

    function handlePaymentChange(value) {
        const showOtherPerson = (value === 'Other person' || value === 'Others-');
        const showOtherCompany = (value === 'Other Company');

        toggleConditionalBlock('other-person-payment', showOtherPerson);
        toggleConditionalBlock('other-company-payment', showOtherCompany);
    }

    // Initialize function to set initial states
    function initializeTravelStep() {
        const travelPlanSelect = document.querySelector('select[name="ti_have_travel_plan"]');
        const paymentSelect = document.querySelector('select[name="trip_payment"]');

        if (travelPlanSelect) {
            handleTravelPlanChange(travelPlanSelect.value);
        }
        if (paymentSelect) {
            handlePaymentChange(paymentSelect.value);
        }
    }

    function generateLocationFields(locations) {
        return locations.map((location, index) => `
                <div class="dynamic-field-group">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-medium text-gray-700">Location ${index + 1}</h4>
                        ${index > 0 ? `
                        <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removeLocationField(${index})">
                            Remove Location
                        </button>
                        ` : ''}
                    </div>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Address Line 1</label>
                            <input type="text" 
                                   value="${location.address_line_1 || ''}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="updateLocationData(${index}, 'address_line_1', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Address Line 2</label>
                            <input type="text" 
                                   value="${location.address_line_2 || ''}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="updateLocationData(${index}, 'address_line_2', this.value)">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">City</label>
                                <input type="text" 
                                       value="${location.city || ''}" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       onchange="updateLocationData(${index}, 'city', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">State</label>
                                <input type="text" 
                                       value="${location.state || ''}" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       onchange="updateLocationData(${index}, 'state', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Zip Code</label>
                                <input type="text" 
                                       value="${location.zip_code || ''}" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       onchange="updateLocationData(${index}, 'zip_code', this.value)">
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
    }

    function addLocationField() {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.travelInfoForUSA.locations) {
            applicant.travelInfoForUSA.locations = [{
                address_line_1: '',
                address_line_2: '',
                city: '',
                state: '',
                zip_code: ''
            }];
        }
        applicant.travelInfoForUSA.locations.push({
            address_line_1: '',
            address_line_2: '',
            city: '',
            state: '',
            zip_code: ''
        });
        saveToLocalStorage(); // ✅ localStorage-এ save করুন
    }

    function removeLocationField(index) {
        const applicant = state.applicants[state.currentApplicant];
        if (applicant.travelInfoForUSA.locations && applicant.travelInfoForUSA.locations.length > 1) {
            applicant.travelInfoForUSA.locations.splice(index, 1);
            generateFormSteps();
            saveToLocalStorage();
        }
    }

    // Travel Companion Information Step (Based on Excel TCI section)
    function generateTravelCompanionStepForUSA(applicant) {
        const tci = applicant.travelInfoForUSA || {};

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Are you traveling with anyone?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="tci_have_anyone" value="1" 
                                    ${tci.tci_have_anyone ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('travel-companion', true); updateApplicantDataForUSA('travelInfoForUSA', 'tci_have_anyone', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="tci_have_anyone" value="0" 
                                    ${!tci.tci_have_anyone ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('travel-companion', false); updateApplicantDataForUSA('travelInfoForUSA', 'tci_have_anyone', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="travel-companion" class="conditional-block" style="display: ${tci.tci_have_anyone ? 'block' : 'none'};">
                        <div class="space-y-6">
                            <h4 class="text-lg font-medium text-gray-800">Travel Companion Details</h4>
                            
                            <!-- Companion Details (Multiple) -->
                            <div class="dynamic-field-group">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Surname</label>
                                        <input type="text" name="tci_surname" 
                                            value="${tci.tci_surname || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'tci_surname', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Given name</label>
                                        <input type="text" name="tci_given_name" 
                                            value="${tci.tci_given_name || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'tci_given_name', this.value)">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-gray-700 mb-2">Relationship to You</label>
                                        <input type="text" name="tci_relationship" 
                                            value="${tci.tci_relationship || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('travelInfoForUSA', 'tci_relationship', this.value)">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-gray-700 mb-2">Are you traveling as part of a group?</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="tci_have_group" value="1" 
                                            ${tci.tci_have_group ? 'checked' : ''}
                                            onchange="toggleConditionalBlock('group-travel', true); updateApplicantDataForUSA('travelInfoForUSA', 'tci_have_group', true)">
                                        <span class="ml-2">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="tci_have_group" value="0" 
                                            ${!tci.tci_have_group ? 'checked' : ''}
                                            onchange="toggleConditionalBlock('group-travel', false); updateApplicantDataForUSA('travelInfoForUSA', 'tci_have_group', false)">
                                        <span class="ml-2">No</span>
                                    </label>
                                </div>
                            </div>

                            <div id="group-travel" class="conditional-block" style="display: ${tci.tci_have_group ? 'block' : 'none'};">
                                <div>
                                    <label class="block text-gray-700 mb-2">Group Name</label>
                                    <input type="text" name="tci_group_name" 
                                        value="${tci.tci_group_name || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelInfoForUSA', 'tci_group_name', this.value)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
    }

    // Previous U.S. Travel Step (Based on Excel PUST section)
    function generatePreviousTravelStepForUSA(applicant) {
        const pust = applicant.travelHistoryForUSA || {};
        const previousTravels = pust.previousTravels || [{
            arrival_date: '',
            staying_length: ''
        }];
        const driverLicenses = pust.driverLicenses || [{
            license_no: '',
            state: ''
        }];

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Have you ever issued a visa to the USA?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_ever_issued" value="1" 
                                    ${pust.pust_have_ever_issued ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('previous-visa', true); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_ever_issued', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_ever_issued" value="0" 
                                    ${!pust.pust_have_ever_issued ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('previous-visa', false); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_ever_issued', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="previous-visa" class="conditional-block" style="display: ${pust.pust_have_ever_issued ? 'block' : 'none'};">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Date Last Issued Visa</label>
                                    <input type="text" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                        value="${pust.pust_last_issued_visa_date ? convertToDisplay(pust.pust_last_issued_visa_date) : ''}" 
                                        onchange="handleDateChange('travelHistoryForUSA', 'pust_last_issued_visa_date', this.value)"
                                        placeholder="DD/MM/YYYY"
                                        required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Visa Number</label>
                                    <input type="text" name="pust_visa_no" 
                                        value="${pust.pust_visa_no || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_visa_no', this.value)">
                                </div>
                            </div>
                            <div>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="pust_remember_visa_no" 
                                        ${pust.pust_remember_visa_no ? 'checked' : ''}
                                        onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_remember_visa_no', this.checked)">
                                    <span class="ml-2">Do not know visa number</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Are you applying for the same visa type?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_applied_same_visa" value="1" 
                                    ${pust.pust_have_applied_same_visa ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_applied_same_visa', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_applied_same_visa" value="0" 
                                    ${!pust.pust_have_applied_same_visa ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_applied_same_visa', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Are you applying in the same country?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_applied_same_country" value="1" 
                                    ${pust.pust_have_applied_same_country ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_applied_same_country', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_applied_same_country" value="0" 
                                    ${!pust.pust_have_applied_same_country ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_applied_same_country', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <!-- Missing Toggle Fields -->
                    <div>
                        <label class="block text-gray-700 mb-2">Have you traveled to the USA before?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_travelled_before" value="1" 
                                    ${pust.pust_have_travelled_before ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('previous-travel-details', true); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_travelled_before', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_travelled_before" value="0" 
                                    ${!pust.pust_have_travelled_before ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('previous-travel-details', false); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_travelled_before', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <!-- Multi-Entry Previous Travel Details -->
                    <div id="previous-travel-details" class="conditional-block" style="display: ${pust.pust_have_travelled_before ? 'block' : 'none'};">
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-800">Previous Travel Details</h4>
                            <div id="previous-travel-fields">
                                ${generatePreviousTravelFields(previousTravels)}
                            </div>
                            <button type="button" onclick="addPreviousTravelField()" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Another Previous Travel
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Do you have a U.S. Social Security Number?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_social_security_no" value="1" 
                                    ${pust.pust_have_social_security_no ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('social-security-field', true); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_social_security_no', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_social_security_no" value="0" 
                                    ${!pust.pust_have_social_security_no ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('social-security-field', false); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_social_security_no', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="social-security-field" class="conditional-block" style="display: ${pust.pust_have_social_security_no ? 'block' : 'none'};">
                        <div>
                            <label class="block text-gray-700 mb-2">U.S. Social Security Number</label>
                            <input type="text" name="pust_social_security_no" 
                                value="${pust.pust_social_security_no || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_social_security_no', this.value)">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Do you have a U.S. Taxpayer Identification Number?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_us_tin" value="1" 
                                    ${pust.pust_have_us_tin ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('tin-field', true); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_tin', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_us_tin" value="0" 
                                    ${!pust.pust_have_us_tin ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('tin-field', false); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_tin', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="tin-field" class="conditional-block" style="display: ${pust.pust_have_us_tin ? 'block' : 'none'};">
                        <div>
                            <label class="block text-gray-700 mb-2">U.S. Taxpayer Identification Number</label>
                            <input type="text" name="pust_us_tin" 
                                value="${pust.pust_us_tin || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_us_tin', this.value)">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Do you ever hold a U.S. Driver License?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_us_driving_license" value="1" 
                                    ${pust.pust_have_us_driving_license ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('driver-license-section', true); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_driving_license', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_us_driving_license" value="0" 
                                    ${!pust.pust_have_us_driving_license ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('driver-license-section', false); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_driving_license', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="driver-license-section" class="conditional-block" style="display: ${pust.pust_have_us_driving_license ? 'block' : 'none'};">
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-800">U.S. Driver License Details</h4>
                            <div id="driver-license-fields">
                                ${generateDriverLicenseFields(driverLicenses)}
                            </div>
                            <button type="button" onclick="addDriverLicenseField()" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Another Driver License
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Have you been ten fingerprinted?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_ten_fingerprint" value="1" 
                                    ${pust.pust_have_ten_fingerprint ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_ten_fingerprint', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_ten_fingerprint" value="0" 
                                    ${!pust.pust_have_ten_fingerprint ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_ten_fingerprint', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Have you ever been refused a visa to the USA?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_refused_us_visa" value="1" 
                                    ${pust.pust_have_refused_us_visa ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('visa-refusal-explain', true); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_refused_us_visa', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_refused_us_visa" value="0" 
                                    ${!pust.pust_have_refused_us_visa ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('visa-refusal-explain', false); updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_refused_us_visa', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="visa-refusal-explain" class="conditional-block" style="display: ${pust.pust_have_refused_us_visa ? 'block' : 'none'};">
                        <div>
                            <label class="block text-gray-700 mb-2">Explain Visa Refusal</label>
                            <textarea name="pust_visa_refusal_explain" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_visa_refusal_explain', this.value)">${pust.pust_visa_refusal_explain || ''}</textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Are you or have you ever been in the USA as a legal permanent resident?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_legal_permanent_resident" value="1" 
                                    ${pust.pust_have_legal_permanent_resident ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_legal_permanent_resident', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_legal_permanent_resident" value="0" 
                                    ${!pust.pust_have_legal_permanent_resident ? 'checked' : ''}
                                    onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_legal_permanent_resident', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <!-- Complex Conditional Logic -->
                    <div id="complex-visa-questions" class="conditional-block" style="display: ${(pust.pust_have_ever_issued && pust.pust_have_travelled_before) ? 'block' : 'none'};">
                        <div class="space-y-6 border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-800">Additional Visa Information</h4>
                            
                            <div>
                                <label class="block text-gray-700 mb-2">Have your U.S. visa ever been lost or stolen?</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="pust_have_us_visa_lost" value="1" 
                                            ${pust.pust_have_us_visa_lost ? 'checked' : ''}
                                            onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_visa_lost', true)">
                                        <span class="ml-2">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="pust_have_us_visa_lost" value="0" 
                                            ${!pust.pust_have_us_visa_lost ? 'checked' : ''}
                                            onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_visa_lost', false)">
                                        <span class="ml-2">No</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-gray-700 mb-2">Have your U.S. visa ever been cancelled or revoked?</label>
                                <div class="flex space-x-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="pust_have_us_visa_cancelled" value="1" 
                                            ${pust.pust_have_us_visa_cancelled ? 'checked' : ''}
                                            onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_visa_cancelled', true)">
                                        <span class="ml-2">Yes</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="pust_have_us_visa_cancelled" value="0" 
                                            ${!pust.pust_have_us_visa_cancelled ? 'checked' : ''}
                                            onchange="updateApplicantDataForUSA('travelHistoryForUSA', 'pust_have_us_visa_cancelled', false)">
                                        <span class="ml-2">No</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
    }

    // Helper Functions for Multi-Entry Sections
    function generatePreviousTravelFields(travels) {
        return travels.map((travel, index) => `
                <div class="previous-travel-field border-b pb-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Date of Arrival</label>
                            <input type="text" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                value="${travel.arrival_date ? convertToDisplay(travel.arrival_date) : ''}" 
                                onchange="handleDateChange(${index}, 'arrival_date', this.value)"
                                placeholder="DD/MM/YYYY"
                                required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Length of Stay</label>
                            <input type="text" name="pust_previous_staying_length" 
                                value="${travel.staying_length || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousTravelData(${index}, 'staying_length', this.value)">
                        </div>
                    </div>
                    ${index > 0 ? `
                        <button type="button" onclick="removePreviousTravelField(${index})" class="mt-2 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg flex items-center">
                            <i class="fas fa-times mr-2"></i> Remove Travel
                        </button>
                    ` : ''}
                </div>
            `).join('');
    }

    function addPreviousTravelField() {
        const container = document.getElementById('previous-travel-fields');
        const index = container.children.length;
        const newField = document.createElement('div');
        newField.className = 'previous-travel-field border-b pb-4 mb-4';
        newField.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Date of Arrival</label>
                        <input type="date" name="pust_arrival_date" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updatePreviousTravelData(${index}, 'arrival_date', this.value)">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Length of Stay</label>
                        <input type="text" name="pust_previous_staying_length" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updatePreviousTravelData(${index}, 'staying_length', this.value)">
                    </div>
                </div>
                <button type="button" onclick="removePreviousTravelField(${index})" class="mt-2 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg flex items-center">
                    <i class="fas fa-times mr-2"></i> Remove Travel
                </button>
            `;
        container.appendChild(newField);
    }

    function removePreviousTravelField(index) {
        const field = document.querySelector(`.previous-travel-field:nth-child(${index + 1})`);
        if (field) {
            field.remove();
            updatePreviousTravelsData();
        }
    }

    function updatePreviousTravelData(index, field, value) {
        if (!currentApplicant.travelHistoryForUSA.previousTravels) {
            currentApplicant.travelHistoryForUSA.previousTravels = [];
        }
        if (!currentApplicant.travelHistoryForUSA.previousTravels[index]) {
            currentApplicant.travelHistoryForUSA.previousTravels[index] = {};
        }
        currentApplicant.travelHistoryForUSA.previousTravels[index][field] = value;
    }

    function updatePreviousTravelsData() {
        const travelFields = document.querySelectorAll('.previous-travel-field');
        currentApplicant.travelHistoryForUSA.previousTravels = Array.from(travelFields).map((field, index) => {
            return {
                arrival_date: field.querySelector('input[name="pust_arrival_date"]')?.value || '',
                staying_length: field.querySelector('input[name="pust_previous_staying_length"]')?.value || ''
            };
        });
    }

    function generateDriverLicenseFields(licenses) {
        return licenses.map((license, index) => `
                <div class="driver-license-field border-b pb-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">License Number</label>
                            <input type="text" name="pust_driving_license_no" 
                                value="${license.license_no || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateDriverLicenseData(${index}, 'license_no', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">State</label>
                            <input type="text" name="pust_driving_license_state" 
                                value="${license.state || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateDriverLicenseData(${index}, 'state', this.value)">
                        </div>
                    </div>
                    ${index > 0 ? `
                        <button type="button" onclick="removeDriverLicenseField(${index})" class="mt-2 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg flex items-center">
                            <i class="fas fa-times mr-2"></i> Remove License
                        </button>
                    ` : ''}
                </div>
            `).join('');
    }

    function addDriverLicenseField() {
        const container = document.getElementById('driver-license-fields');
        const index = container.children.length;
        const newField = document.createElement('div');
        newField.className = 'driver-license-field border-b pb-4 mb-4';
        newField.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 mb-2">License Number</label>
                        <input type="text" name="pust_driving_license_no" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updateDriverLicenseData(${index}, 'license_no', this.value)">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">State</label>
                        <input type="text" name="pust_driving_license_state" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updateDriverLicenseData(${index}, 'state', this.value)">
                    </div>
                </div>
                <button type="button" onclick="removeDriverLicenseField(${index})" class="mt-2 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg flex items-center">
                    <i class="fas fa-times mr-2"></i> Remove License
                </button>
            `;
        container.appendChild(newField);
    }

    function removeDriverLicenseField(index) {
        const field = document.querySelector(`.driver-license-field:nth-child(${index + 1})`);
        if (field) {
            field.remove();
            updateDriverLicensesData();
        }
    }

    function updateDriverLicenseData(index, field, value) {
        if (!currentApplicant.travelHistoryForUSA.driverLicenses) {
            currentApplicant.travelHistoryForUSA.driverLicenses = [];
        }
        if (!currentApplicant.travelHistoryForUSA.driverLicenses[index]) {
            currentApplicant.travelHistoryForUSA.driverLicenses[index] = {};
        }
        currentApplicant.travelHistoryForUSA.driverLicenses[index][field] = value;
    }

    function updateDriverLicensesData() {
        const licenseFields = document.querySelectorAll('.driver-license-field');
        currentApplicant.travelHistoryForUSA.driverLicenses = Array.from(licenseFields).map((field, index) => {
            return {
                license_no: field.querySelector('input[name="pust_driving_license_no"]')?.value || '',
                state: field.querySelector('input[name="pust_driving_license_state"]')?.value || ''
            };
        });
    }

    // U.S. Contact Information Step (Based on Excel USCI section)
    function generateUSContactStep(applicant) {
        const usci = applicant.usContactInfo || {};

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Contact Type</label>
                        <select name="usci_contact_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="toggleContactTypeFields(this.value); updateApplicantDataForUSA('usContactInfo', 'usci_contact_type', this.value)">
                            <option value="">Select Type</option>
                            <option value="Person" ${(usci.usci_contact_type === 'Person') ? 'selected' : ''}>Person</option>
                            <option value="Company" ${(usci.usci_contact_type === 'Company') ? 'selected' : ''}>Company</option>
                            <option value="Hotel" ${(usci.usci_contact_type === 'Hotel') ? 'selected' : ''}>Hotel</option>
                        </select>
                    </div>

                    <!-- Person Contact -->
                    <div id="person-contact" class="conditional-block" style="display: ${usci.usci_contact_type === 'Person' ? 'block' : 'none'};">
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-800">Person Contact Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Contact Person Surname</label>
                                    <input type="text" name="usci_contact_person_surname" 
                                        value="${usci.usci_contact_person_surname || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci_contact_person_surname', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Contact Person Given Name</label>
                                    <input type="text" name="usci_contact_person_given_name" 
                                        value="${usci.usci_contact_person_given_name || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci_contact_person_given_name', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Contact Person Telephone</label>
                                    <input type="tel" name="usci contact person telephone" 
                                        value="${usci['usci contact person telephone'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person telephone', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Contact Person Email</label>
                                    <input type="email" name="usci contact person email" 
                                        value="${usci['usci contact person email'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person email', this.value)">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">Contact Person Relationship</label>
                                    <input type="text" name="usci contact person relationship" 
                                        value="${usci['usci contact person relationship'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person relationship', this.value)">
                                </div>
                            </div>
                            
                            <!-- Person Address Block -->
                            <div class="border-t pt-4">
                                <h5 class="text-md font-medium text-gray-700 mb-3">Contact Person Address</h5>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Address Line 1</label>
                                        <input type="text" name="usci contact person address line 1" 
                                            value="${usci['usci contact person address line 1'] || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person address line 1', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Address Line 2</label>
                                        <input type="text" name="usci contact person address line 2" 
                                            value="${usci['usci contact person address line 2'] || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person address line 2', this.value)">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">City</label>
                                            <input type="text" name="usci contact person address city" 
                                                value="${usci['usci contact person address city'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person address city', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">State</label>
                                            <input type="text" name="usci contact person address state" 
                                                value="${usci['usci contact person address state'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person address state', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Zip Code</label>
                                            <input type="text" name="usci contact person address zip code" 
                                                value="${usci['usci contact person address zip code'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact person address zip code', this.value)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Company Contact -->
                    <div id="company-contact" class="conditional-block" style="display: ${usci.usci_contact_type === 'Company' ? 'block' : 'none'};">
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-800">Company Contact Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Company Surname</label>
                                    <input type="text" name="usci_contact_company_name" 
                                        value="${usci.usci_contact_company_name || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci_contact_company_name', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Company Telephone</label>
                                    <input type="tel" name="usci contact company telephone" 
                                        value="${usci['usci contact company telephone'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company telephone', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Company Email</label>
                                    <input type="email" name="usci contact company email" 
                                        value="${usci['usci contact company email'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company email', this.value)">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">Company Relationship</label>
                                    <input type="text" name="usci contact company relationship" 
                                        value="${usci['usci contact company relationship'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company relationship', this.value)">
                                </div>
                            </div>
                            
                            <!-- Company Address Block -->
                            <div class="border-t pt-4">
                                <h5 class="text-md font-medium text-gray-700 mb-3">Company Address</h5>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Address Line 1</label>
                                        <input type="text" name="usci contact company address line 1" 
                                            value="${usci['usci contact company address line 1'] || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address line 1', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Address Line 2</label>
                                        <input type="text" name="usci contact company address line 2" 
                                            value="${usci['usci contact company address line 2'] || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address line 2', this.value)">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">City</label>
                                            <input type="text" name="usci contact company address city" 
                                                value="${usci['usci contact company address city'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address city', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">State</label>
                                            <input type="text" name="usci contact company address state" 
                                                value="${usci['usci contact company address state'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address state', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Zip Code</label>
                                            <input type="text" name="usci contact company address zip code" 
                                                value="${usci['usci contact company address zip code'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address zip code', this.value)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel Contact -->
                    <div id="hotel-contact" class="conditional-block" style="display: ${usci.usci_contact_type === 'Hotel' ? 'block' : 'none'};">
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-800">Hotel Contact Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Hotel Surname</label>
                                    <input type="text" name="usci_contact_hotel_name" 
                                        value="${usci.usci_contact_hotel_name || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci_contact_hotel_name', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Hotel Telephone</label>
                                    <input type="tel" name="usci contact hotel telephone" 
                                        value="${usci['usci contact hotel telephone'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact hotel telephone', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Hotel Email</label>
                                    <input type="email" name="usci contact hotel email" 
                                        value="${usci['usci contact hotel email'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact hotel email', this.value)">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">Hotel Relationship</label>
                                    <input type="text" name="usci contact hotel relationship" 
                                        value="${usci['usci contact hotel relationship'] || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact hotel relationship', this.value)">
                                </div>
                            </div>
                            
                            <!-- Hotel Address Block (using same input names as Company) -->
                            <div class="border-t pt-4">
                                <h5 class="text-md font-medium text-gray-700 mb-3">Hotel Address</h5>
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Address Line 1</label>
                                        <input type="text" name="usci contact company address line 1" 
                                            value="${usci['usci contact company address line 1'] || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address line 1', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Address Line 2</label>
                                        <input type="text" name="usci contact company address line 2" 
                                            value="${usci['usci contact company address line 2'] || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address line 2', this.value)">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">City</label>
                                            <input type="text" name="usci contact company address city" 
                                                value="${usci['usci contact company address city'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address city', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">State</label>
                                            <input type="text" name="usci contact company address state" 
                                                value="${usci['usci contact company address state'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address state', this.value)">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Zip Code</label>
                                            <input type="text" name="usci contact company address zip code" 
                                                value="${usci['usci contact company address zip code'] || ''}" 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('usContactInfo', 'usci contact company address zip code', this.value)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
    }

    // Toggle function using your existing toggleConditionalBlock
    function toggleContactTypeFields(value) {
        toggleConditionalBlock('person-contact', value === 'Person');
        toggleConditionalBlock('company-contact', value === 'Company');
        toggleConditionalBlock('hotel-contact', value === 'Hotel');
    }

    // Initialize function for US Contact step
    function initializeUSContactStep() {
        const contactTypeSelect = document.querySelector('select[name="usci_contact_type"]');
        if (contactTypeSelect) {
            toggleContactTypeFields(contactTypeSelect.value);
        }
    }

    // Work Information Step (Based on Excel WI section)
    function generateWorkInfoStepForUSA(applicant) {
        const wi = applicant.workInfoForUSA || {};
        const previousEmployment = wi.previousEmployment || [];

        return `
                <div class="space-y-6">
                    <!-- Primary Occupation Type -->
                    <div>
                        <label class="block text-gray-700 mb-2">Primary Occupation Type *</label>
                        <select name="wi_primary_occupation_type" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="handleOccupationChange(this.value); updateApplicantDataForUSA('workInfoForUSA', 'wi_primary_occupation_type', this.value)" required>
                            <option value="">Select Category</option>
                            <option value="Student" ${(wi.wi_primary_occupation_type === 'Student') ? 'selected' : ''}>Student</option>
                            <option value="Homemaker" ${(wi.wi_primary_occupation_type === 'Homemaker') ? 'selected' : ''}>Homemaker</option>
                            <option value="Retired" ${(wi.wi_primary_occupation_type === 'Retired') ? 'selected' : ''}>Retired</option>
                            <option value="Government" ${(wi.wi_primary_occupation_type === 'Government') ? 'selected' : ''}>Government</option>
                            <option value="Private Sector" ${(wi.wi_primary_occupation_type === 'Private Sector') ? 'selected' : ''}>Private Sector</option>
                            <option value="Military" ${(wi.wi_primary_occupation_type === 'Military') ? 'selected' : ''}>Military</option>
                            <option value="Unemployed" ${(wi.wi_primary_occupation_type === 'Unemployed') ? 'selected' : ''}>Unemployed</option>
                            <option value="Other" ${(wi.wi_primary_occupation_type === 'Other') ? 'selected' : ''}>Other</option>
                        </select>
                    </div>

                    <!-- Employment Fields (Conditional) -->
                    <div id="employment-fields" style="display: ${isEmploymentType(wi.wi_primary_occupation_type) ? 'block' : 'none'};">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Present Company Name *</label>
                                <input type="text" name="wi_company_or_school_name" 
                                    value="${wi.wi_company_or_school_name || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_name', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Monthly Salary</label>
                                <input type="text" name="wi_salary" 
                                    value="${wi.wi_salary || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_salary', this.value)"
                                    placeholder="Enter monthly salary">
                            </div>
                        </div>

                        <!-- Description of Duties -->
                        <div class="mt-4">
                            <label class="block text-gray-700 mb-2">Describe Your Duties</label>
                            <textarea name="wi_your_duties" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    rows="4"
                                    placeholder="Describe your duties..."
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_your_duties', this.value)">${wi.wi_your_duties || ''}</textarea>
                        </div>

                        <!-- Company Address -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-800 mb-4">Present Company Address</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address Line 1</label>
                                        <input type="text" name="wi_company_or_school_address_line_1" 
                                            value="${wi.wi_company_or_school_address_line_1 || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_line_1', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address Line 2</label>
                                        <input type="text" name="wi_company_or_school_address_line_2" 
                                            value="${wi.wi_company_or_school_address_line_2 || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_line_2', this.value)">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address City</label>
                                        <input type="text" name="wi_company_or_school_address_city" 
                                            value="${wi.wi_company_or_school_address_city || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_city', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address State</label>
                                        <input type="text" name="wi_company_or_school_address_state" 
                                            value="${wi.wi_company_or_school_address_state || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_state', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address Zip Code</label>
                                        <input type="text" name="wi_company_or_school_address_zip_code" 
                                            value="${wi.wi_company_or_school_address_zip_code || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_zip_code', this.value)">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address Country</label>
                                        <select name="wi_company_or_school_address_country" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_country', this.value)">
                                            <option value="">Select Country</option>
                                            ${countries.map(country => 
                                                `<option value="${country.code}" ${(wi.wi_company_or_school_address_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                            ).join('')}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Present Company Address Telephone</label>
                                        <input type="tel" name="wi_company_or_school_address_telephone" 
                                            value="${wi.wi_company_or_school_address_telephone || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_telephone', this.value)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Fields (Conditional) -->
                    <div id="student-fields" style="display: ${wi.wi_primary_occupation_type === 'Student' ? 'block' : 'none'};">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">School/University Name *</label>
                                <input type="text" name="wi_company_or_school_name" 
                                    value="${wi.wi_company_or_school_name || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_name', this.value)">
                            </div>
                        </div>

                        <!-- School Address -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-800 mb-4">School/University Address</h4>
                            <div class="grid grid-cols-1 gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">School Address Line 1</label>
                                        <input type="text" name="wi_company_or_school_address_line_1" 
                                            value="${wi.wi_company_or_school_address_line_1 || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_line_1', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">School Address Line 2</label>
                                        <input type="text" name="wi_company_or_school_address_line_2" 
                                            value="${wi.wi_company_or_school_address_line_2 || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_line_2', this.value)">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">School Address City</label>
                                        <input type="text" name="wi_company_or_school_address_city" 
                                            value="${wi.wi_company_or_school_address_city || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_city', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">School Address State</label>
                                        <input type="text" name="wi_company_or_school_address_state" 
                                            value="${wi.wi_company_or_school_address_state || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_state', this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">School Address Zip Code</label>
                                        <input type="text" name="wi_company_or_school_address_zip_code" 
                                            value="${wi.wi_company_or_school_address_zip_code || ''}" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updateApplicantDataForUSA('workInfoForUSA', 'wi_company_or_school_address_zip_code', this.value)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Employment Toggle -->
                    <div id="previous-employment-toggle" style="display: ${isEmploymentType(wi.wi_primary_occupation_type) ? 'block' : 'none'};">
                        <label class="block text-gray-700 mb-2">Were you previously employed?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="have_previous_experience" value="1" 
                                    ${wi.have_previous_experience ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('previous-employment', true); updateApplicantDataForUSA('workInfoForUSA', 'have_previous_experience', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="have_previous_experience" value="0" 
                                    ${!wi.have_previous_experience ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('previous-employment', false); updateApplicantDataForUSA('workInfoForUSA', 'have_previous_experience', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <!-- Previous Employment Section -->
                    <div id="previous-employment" class="conditional-block" style="display: ${(wi.have_previous_experience && isEmploymentType(wi.wi_primary_occupation_type)) ? 'block' : 'none'};">
                        <div class="space-y-6">
                            <h4 class="text-lg font-medium text-gray-800">Previous Employment History</h4>
                            <div id="previous-employment-fields">
                                ${generatePreviousEmploymentFields(previousEmployment)}
                            </div>
                            <button type="button" onclick="addPreviousEmploymentField()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Previous Employment
                            </button>
                        </div>
                    </div>
                </div>
            `;
    }

    // Helper Functions
    function isEmploymentType(occupation) {
        return ['Government', 'Private Sector', 'Military'].includes(occupation);
    }

    function handleOccupationChange(value) {
        const isEmployment = isEmploymentType(value);
        const isStudent = value === 'Student';

        toggleConditionalBlock('employment-fields', isEmployment);
        toggleConditionalBlock('student-fields', isStudent);
        toggleConditionalBlock('previous-employment-toggle', isEmployment);

        // Hide previous employment if not employment type
        if (!isEmployment) {
            toggleConditionalBlock('previous-employment', false);
        }
    }

    function generatePreviousEmploymentFields(previousEmployment) {
        if (!previousEmployment || previousEmployment.length === 0) {
            previousEmployment = [{}];
        }

        return previousEmployment.map((employment, index) => `
                <div class="previous-employment-field border border-gray-300 rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Previous Company Name</label>
                            <input type="text" name="wi_pre_company_name" 
                                value="${employment.wi_pre_company_name || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_name', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Previous Company Job Title</label>
                            <input type="text" name="wi_pre_company_job_title" 
                                value="${employment.wi_pre_company_job_title || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_job_title', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Previous Company Supervisor Surname</label>
                            <input type="text" name="wi_pre_company_supervisor_surname" 
                                value="${employment.wi_pre_company_supervisor_surname || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_supervisor_surname', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Previous Company Supervisor Given Name</label>
                            <input type="text" name="wi_pre_company_supervisor_given_name" 
                                value="${employment.wi_pre_company_supervisor_given_name || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_supervisor_given_name', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Employment Date Started From</label>
                            <input type="date" name="wi_pre_employment_started" 
                                value="${employment.wi_pre_employment_started || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_employment_started', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Employment Date Ended To</label>
                            <input type="date" name="wi_pre_employment_ended" 
                                value="${employment.wi_pre_employment_ended || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_employment_ended', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Previous Company Monthly Salary</label>
                            <input type="text" name="wi_pre_company_salary" 
                                value="${employment.wi_pre_company_salary || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_salary', this.value)">
                        </div>
                    </div>

                    <!-- Previous Company Address -->
                    <div class="border-t pt-4 mt-4">
                        <h5 class="text-md font-medium text-gray-700 mb-3">Previous Company Address</h5>
                        <div class="grid grid-cols-1 gap-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address Line 1</label>
                                    <input type="text" name="wi_pre_company_address_line_1" 
                                        value="${employment.wi_pre_company_address_line_1 || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_line_1', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address Line 2</label>
                                    <input type="text" name="wi_pre_company_address_line_2" 
                                        value="${employment.wi_pre_company_address_line_2 || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_line_2', this.value)">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address City</label>
                                    <input type="text" name="wi_pre_company_address_city" 
                                        value="${employment.wi_pre_company_address_city || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_city', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address State</label>
                                    <input type="text" name="wi_pre_company_address_state" 
                                        value="${employment.wi_pre_company_address_state || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_state', this.value)">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address Zip Code</label>
                                    <input type="text" name="wi_pre_company_address_zip_code" 
                                        value="${employment.wi_pre_company_address_zip_code || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_zip_code', this.value)">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address Country</label>
                                    <select name="wi_pre_company_address_country" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_country', this.value)">
                                        <option value="">Select Country</option>
                                        ${countries.map(country => 
                                            `<option value="${country.code}" ${(employment.wi_pre_company_address_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                        ).join('')}
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Previous Company Address Telephone</label>
                                    <input type="tel" name="wi_pre_company_address_telephone" 
                                        value="${employment.wi_pre_company_address_telephone || ''}" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_address_telephone', this.value)">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Company Duties -->
                    <div class="mt-4">
                        <label class="block text-gray-700 mb-2">Previous Company Describe Your Duties</label>
                        <textarea name="wi_pre_company_duties" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                rows="3"
                                placeholder="Describe your duties at this company..."
                                onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'wi_pre_company_duties', this.value)">${employment.wi_pre_company_duties || ''}</textarea>
                    </div>

                    ${index > 0 ? `
                        <div class="mt-4">
                            <button type="button" onclick="removePreviousEmploymentField(${index})" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-times mr-2"></i> Remove Employment
                            </button>
                        </div>
                    ` : ''}
                </div>
            `).join('');
    }

    // Keep the same add/remove functions as before
    function addPreviousEmploymentField() {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.workInfoForUSA.previousEmployment) { // workInfo -> workInfoForUSA
            applicant.workInfoForUSA.previousEmployment = [];
        }
        const container = document.getElementById('previous-employment-fields');
        const index = container.children.length;

        if (!state.applicants[state.currentApplicant].workInfoForUSA.previousEmployment) {
            state.applicants[state.currentApplicant].workInfoForUSA.previousEmployment = [];
        }
        state.applicants[state.currentApplicant].workInfoForUSA.previousEmployment.push({});
        saveToLocalStorage();

        const previousEmployment = state.applicants[state.currentApplicant].workInfoForUSA.previousEmployment;
        container.innerHTML = generatePreviousEmploymentFields(previousEmployment);
    }

    function removePreviousEmploymentField(index) {
        const field = document.querySelector(`.previous-employment-field:nth-child(${index + 1})`);
        if (field) {
            state.applicants[state.currentApplicant].workInfoForUSA.previousEmployment.splice(index, 1);
            saveToLocalStorage();

            const container = document.getElementById('previous-employment-fields');
            const previousEmployment = state.applicants[state.currentApplicant].workInfoForUSA.previousEmployment;
            container.innerHTML = generatePreviousEmploymentFields(previousEmployment);
        }
    }

    function updatePreviousEmploymentArray(scope, arrayName, index, fieldName, value) {
        if (!state.applicants[state.currentApplicant][scope][arrayName]) {
            state.applicants[state.currentApplicant][scope][arrayName] = [];
        }
        if (!state.applicants[state.currentApplicant][scope][arrayName][index]) {
            state.applicants[state.currentApplicant][scope][arrayName][index] = {};
        }
        state.applicants[state.currentApplicant][scope][arrayName][index][fieldName] = value;
        saveToLocalStorage();
    }

    // Educational Information Step (Based on Excel EDI section)
    function generateEducationInfoStepForUSA(applicant) {
        const edi = applicant.educationalInfo || {};
        const institutions = edi.institutions || [];

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Have you attended any educational institution at a secondary level or above?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="edi_have_attended_secondary_level" value="1" 
                                       ${edi.edi_have_attended_secondary_level ? 'checked' : ''}
                                       onchange="toggleConditionalBlock('education-history', this.checked); updateApplicantDataForUSA('educationalInfo', 'edi_have_attended_secondary_level', this.checked)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="edi_have_attended_secondary_level" value="0" 
                                       ${!edi.edi_have_attended_secondary_level ? 'checked' : ''}
                                       onchange="toggleConditionalBlock('education-history', this.checked); updateApplicantDataForUSA('educationalInfo', 'edi_have_attended_secondary_level', this.checked)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="education-history" class="conditional-block ${edi.edi_have_attended_secondary_level ? 'active' : ''}">
                        <div class="space-y-6">
                            <h4 class="text-lg font-medium text-gray-800">Educational Institutions</h4>
                            <div id="institution-fields">
                                ${generateInstitutionFields(institutions)}
                            </div>
                            <button type="button" onclick="addInstitutionField()" class="mt-2 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg flex items-center">
                                <i class="fas fa-plus mr-2"></i> Add Institution
                            </button>
                        </div>
                    </div>
                </div>
            `;
    }

    function generateInstitutionFields(institutions) {
        return institutions.map((institution, index) => `
                <div class="dynamic-field-group">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-medium text-gray-700">Institution ${index + 1}</h4>
                        ${index > 0 ? `
                        <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removeInstitutionField(${index})">
                            Remove Institution
                        </button>
                        ` : ''}
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Institution Name</label>
                            <input type="text" 
                                   value="${institution.name || ''}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="updateInstitutionData(${index}, 'name', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Course of Study</label>
                            <input type="text" 
                                   value="${institution.course || ''}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="updateInstitutionData(${index}, 'course', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Attendance From</label>
                            <input type="date" 
                                   value="${institution.attendanceFrom || ''}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="updateInstitutionData(${index}, 'attendanceFrom', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Attendance To</label>
                            <input type="date" 
                                   value="${institution.attendanceTo || ''}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   onchange="updateInstitutionData(${index}, 'attendanceTo', this.value)">
                        </div>
                        <div>
                            <div>
                                <label class="block text-gray-700 mb-2">School Address Line 1</label>
                                <input type="text" name="edi_institution_address_line_1" 
                                    value="${institution.edi_institution_address_line_1 || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'edi_institution_address_line_1', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">School Address Line 2</label>
                                <input type="text" name="edi_institution_address_line_2" 
                                    value="${institution.edi_institution_address_line_2 || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'edi_institution_address_line_2', this.value)">
                            </div>
                        </div>
                        <div>
                            <div>
                                <label class="block text-gray-700 mb-2">School Address City</label>
                                <input type="text" name="edi_institution_address_city" 
                                    value="${institution.edi_institution_address_city || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'edi_institution_address_city', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">School Address State</label>
                                <input type="text" name="edi_institution_address_state" 
                                    value="${institution.edi_institution_address_state || ''}" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateApplicantDataForUSA('workInfoForUSA', 'edi_institution_address_state', this.value)">
                            </div>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">School Address Zip Code</label>
                            <input type="text" name="edi_institution_address_zip_code" 
                                value="${institution.edi_institution_address_zip_code || ''}" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantDataForUSA('workInfoForUSA', 'edi_institution_address_zip_code', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">School Address Country</label>
                            <select name="edi_institution_address_country" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updatePreviousEmploymentArray('workInfoForUSA', 'previousEmployment', ${index}, 'edi_institution_address_country', this.value)">
                                <option value="">Select Country</option>
                                ${countries.map(country => 
                                    `<option value="${country.code}" ${(institution.edi_institution_address_country === country.code) ? 'selected' : ''}>${country.name}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                </div>
            `).join('');
    }

    function addInstitutionField() {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.educationalInfo.institutions) {
            applicant.educationalInfo.institutions = [];
        }
        applicant.educationalInfo.institutions.push({
            name: '',
            course: '',
            attendanceFrom: '',
            attendanceTo: ''
        });
        generateFormSteps();
        saveToLocalStorage();
    }

    // Other Information Step (Based on Excel OI section)
    function generateOtherInfoStepForUSA(applicant) {
        const oi = applicant.otherInfo || {};

        return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">List of Languages Spoken</label>
                        <textarea name="oi_spoken_language_list" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                rows="3"
                                onchange="updateApplicantDataForUSA('otherInfo', 'oi_spoken_language_list', this.value)">${oi.oi_spoken_language_list || ''}</textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Have you traveled to any countries in the last five years?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_travel_country_5years" value="true" 
                                    ${oi.oi_have_travel_country_5years ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('traveled-countries', true); updateApplicantDataForUSA('otherInfo', 'oi_have_travel_country_5years', this.value === 'true')">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_travel_country_5years" value="false" 
                                    ${!oi.oi_have_travel_country_5years ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('traveled-countries', false); updateApplicantDataForUSA('otherInfo', 'oi_have_travel_country_5years', this.value === 'true')">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="traveled-countries" class="conditional-block" style="display: ${oi.oi_have_travel_country_5years ? 'block' : 'none'}">
                        <div>
                            <label class="block text-gray-700 mb-2">Traveled Countries</label>
                            <div id="travelled-countries-container">
                                ${generateTravelledCountryFields(oi.oi_travelled_country || [])}
                            </div>
                            <button type="button" 
                                    class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                                    onclick="addTravelledCountryField()">
                                Add Another Country
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Have you belonged to any professional, social, or charitable organizations?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_you_belong_orgntion" value="true" 
                                    ${oi.oi_have_you_belong_orgntion ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('organization-info', true); updateApplicantDataForUSA('otherInfo', 'oi_have_you_belong_orgntion', this.value === 'true')">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_you_belong_orgntion" value="false" 
                                    ${!oi.oi_have_you_belong_orgntion ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('organization-info', false); updateApplicantDataForUSA('otherInfo', 'oi_have_you_belong_orgntion', this.value === 'true')">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="organization-info" class="conditional-block" style="display: ${oi.oi_have_you_belong_orgntion ? 'block' : 'none'}">
                        <div>
                            <label class="block text-gray-700 mb-2">Organization Name*</label>
                            <div id="organizations-container">
                                ${generateOrganizationFields(oi.oi_organization_name || [])}
                            </div>
                            <button type="button" 
                                    class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                                    onclick="addOrganizationField()">
                                Add Another Organization
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Do you have any special skills or training in fire arms, explosives, or nuclear materials?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_special_skills" value="true" 
                                    ${oi.oi_have_special_skills ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('special-skills-info', true); updateApplicantDataForUSA('otherInfo', 'oi_have_special_skills', this.value === 'true')">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_special_skills" value="false" 
                                    ${!oi.oi_have_special_skills ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('special-skills-info', false); updateApplicantDataForUSA('otherInfo', 'oi_have_special_skills', this.value === 'true')">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="special-skills-info" class="conditional-block" style="display: ${oi.oi_have_special_skills ? 'block' : 'none'}">
                        <div>
                            <label class="block text-gray-700 mb-2">Explain your special skills or training</label>
                            <textarea name="oi_special_skills" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    rows="4"
                                    onchange="updateApplicantDataForUSA('otherInfo', 'oi_special_skills', this.value)">${oi.oi_special_skills || ''}</textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2">Have you ever served in the military?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_served_military" value="true" 
                                    ${oi.oi_have_served_military ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('military-service-history', true); updateApplicantDataForUSA('otherInfo', 'oi_have_served_military', this.value === 'true')">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="oi_have_served_military" value="false" 
                                    ${!oi.oi_have_served_military ? 'checked' : ''}
                                    onchange="toggleConditionalBlock('military-service-history', false); updateApplicantDataForUSA('otherInfo', 'oi_have_served_military', this.value === 'true')">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>

                    <div id="military-service-history" class="conditional-block" style="display: ${oi.oi_have_served_military ? 'block' : 'none'}">
                        <div>
                            <label class="block text-gray-700 mb-2">Military Service History</label>
                            <div id="military-service-container">
                                ${generateMilitaryServiceFields(oi.oi_military_service || [])}
                            </div>
                            <button type="button" 
                                    class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                                    onclick="addMilitaryServiceField()">
                                Add Another Service Period
                            </button>
                        </div>
                    </div>
                </div>
            `;
    }

    // Keep all your existing helper functions as they are
    function updateTravelledCountryArray(scope, arrayName, index, fieldName, value) {
        if (!state.applicants[state.currentApplicant][scope]) {
            state.applicants[state.currentApplicant][scope] = {};
        }
        if (!state.applicants[state.currentApplicant][scope][arrayName]) {
            state.applicants[state.currentApplicant][scope][arrayName] = [];
        }

        // For travelled countries, we're storing just the country code as string
        state.applicants[state.currentApplicant][scope][arrayName][index] = value;
        saveToLocalStorage();
    }

    function addTravelledCountryField() {
        if (!state.applicants[state.currentApplicant].otherInfo) {
            state.applicants[state.currentApplicant].otherInfo = {};
        }
        if (!state.applicants[state.currentApplicant].otherInfo.oi_travelled_country) {
            state.applicants[state.currentApplicant].otherInfo.oi_travelled_country = [];
        }

        state.applicants[state.currentApplicant].otherInfo.oi_travelled_country.push('');

        const container = document.getElementById('travelled-countries-container');
        if (container) {
            container.innerHTML = generateTravelledCountryFields(state.applicants[state.currentApplicant].otherInfo.oi_travelled_country);
        }

        saveToLocalStorage();
    }

    function removeTravelledCountryField(index) {
        if (state.applicants[state.currentApplicant].otherInfo &&
            state.applicants[state.currentApplicant].otherInfo.oi_travelled_country) {
            state.applicants[state.currentApplicant].otherInfo.oi_travelled_country.splice(index, 1);

            const container = document.getElementById('travelled-countries-container');
            if (container) {
                container.innerHTML = generateTravelledCountryFields(state.applicants[state.currentApplicant].otherInfo.oi_travelled_country);
            }

            saveToLocalStorage();
        }
    }

    function generateTravelledCountryFields(selectedCountries) {
        if (!selectedCountries || !Array.isArray(selectedCountries)) {
            selectedCountries = [''];
        }

        return selectedCountries.map((countryCode, index) => `
                <div class="flex items-center space-x-2 mb-2">
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updateTravelledCountryArray('otherInfo', 'oi_travelled_country', ${index}, 'country', this.value)">
                        <option value="">Select Country</option>
                        ${countries.map(country => `
                            <option value="${country.code}" ${countryCode === country.code ? 'selected' : ''}>${country.name}</option>
                        `).join('')}
                    </select>
                    <button type="button" 
                            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
                            onclick="removeTravelledCountryField(${index})">
                        Remove
                    </button>
                </div>
            `).join('');
    }

    function updateOrganizationArray(scope, arrayName, index, fieldName, value) {
        if (!state.applicants[state.currentApplicant][scope]) {
            state.applicants[state.currentApplicant][scope] = {};
        }
        if (!state.applicants[state.currentApplicant][scope][arrayName]) {
            state.applicants[state.currentApplicant][scope][arrayName] = [];
        }

        if (!state.applicants[state.currentApplicant][scope][arrayName][index]) {
            state.applicants[state.currentApplicant][scope][arrayName][index] = {};
        }
        state.applicants[state.currentApplicant][scope][arrayName][index][fieldName] = value;
        saveToLocalStorage();
    }

    function addOrganizationField() {
        if (!state.applicants[state.currentApplicant].otherInfo) {
            state.applicants[state.currentApplicant].otherInfo = {};
        }
        if (!state.applicants[state.currentApplicant].otherInfo.oi_organization_name) {
            state.applicants[state.currentApplicant].otherInfo.oi_organization_name = [];
        }

        state.applicants[state.currentApplicant].otherInfo.oi_organization_name.push({
            name: ''
        });

        const container = document.getElementById('organizations-container');
        if (container) {
            container.innerHTML = generateOrganizationFields(state.applicants[state.currentApplicant].otherInfo.oi_organization_name);
        }

        saveToLocalStorage();
    }

    function removeOrganizationField(index) {
        if (state.applicants[state.currentApplicant].otherInfo &&
            state.applicants[state.currentApplicant].otherInfo.oi_organization_name) {
            state.applicants[state.currentApplicant].otherInfo.oi_organization_name.splice(index, 1);

            const container = document.getElementById('organizations-container');
            if (container) {
                container.innerHTML = generateOrganizationFields(state.applicants[state.currentApplicant].otherInfo.oi_organization_name);
            }

            saveToLocalStorage();
        }
    }

    function generateOrganizationFields(organizations) {
        if (!organizations || !Array.isArray(organizations)) {
            organizations = [{
                name: ''
            }];
        }

        return organizations.map((org, index) => `
                <div class="flex items-center space-x-2 mb-2">
                    <input type="text" 
                        value="${org.name || ''}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="updateOrganizationArray('otherInfo', 'oi_organization_name', ${index}, 'name', this.value)">
                    <button type="button" 
                            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
                            onclick="removeOrganizationField(${index})">
                        Remove
                    </button>
                </div>
            `).join('');
    }

    function updateMilitaryServiceArray(scope, arrayName, index, fieldName, value) {
        if (!state.applicants[state.currentApplicant][scope]) {
            state.applicants[state.currentApplicant][scope] = {};
        }
        if (!state.applicants[state.currentApplicant][scope][arrayName]) {
            state.applicants[state.currentApplicant][scope][arrayName] = [];
        }

        if (!state.applicants[state.currentApplicant][scope][arrayName][index]) {
            state.applicants[state.currentApplicant][scope][arrayName][index] = {};
        }
        state.applicants[state.currentApplicant][scope][arrayName][index][fieldName] = value;
        saveToLocalStorage();
    }

    function addMilitaryServiceField() {
        if (!state.applicants[state.currentApplicant].otherInfo) {
            state.applicants[state.currentApplicant].otherInfo = {};
        }
        if (!state.applicants[state.currentApplicant].otherInfo.oi_military_service) {
            state.applicants[state.currentApplicant].otherInfo.oi_military_service = [];
        }

        state.applicants[state.currentApplicant].otherInfo.oi_military_service.push({
            oi_sm_country_name: '',
            oi_sm_service_branch: '',
            oi_sm_rank: '',
            oi_militay_speciality: '',
            oi_sm_serve_from: '',
            oi_sm_serve_to: ''
        });

        const container = document.getElementById('military-service-container');
        if (container) {
            container.innerHTML = generateMilitaryServiceFields(state.applicants[state.currentApplicant].otherInfo.oi_military_service);
        }

        saveToLocalStorage();
    }

    function removeMilitaryServiceField(index) {
        if (state.applicants[state.currentApplicant].otherInfo &&
            state.applicants[state.currentApplicant].otherInfo.oi_military_service) {
            state.applicants[state.currentApplicant].otherInfo.oi_military_service.splice(index, 1);

            const container = document.getElementById('military-service-container');
            if (container) {
                container.innerHTML = generateMilitaryServiceFields(state.applicants[state.currentApplicant].otherInfo.oi_military_service);
            }

            saveToLocalStorage();
        }
    }

    function generateMilitaryServiceFields(serviceHistory) {
        if (!serviceHistory || !Array.isArray(serviceHistory)) {
            serviceHistory = [{
                oi_sm_country_name: '',
                oi_sm_service_branch: '',
                oi_sm_rank: '',
                oi_militay_speciality: '',
                oi_sm_serve_from: '',
                oi_sm_serve_to: ''
            }];
        }

        return serviceHistory.map((service, index) => `
                <div class="border border-gray-300 p-4 rounded-lg mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Name of Country</label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    onchange="updateMilitaryServiceArray('otherInfo', 'oi_military_service', ${index}, 'oi_sm_country_name', this.value)">
                                <option value="">Select Country</option>
                                ${countries.map(country => `
                                    <option value="${country.code}" ${service.oi_sm_country_name === country.code ? 'selected' : ''}>${country.name}</option>
                                `).join('')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Branch of Service</label>
                            <input type="text" 
                                value="${service.oi_sm_service_branch || ''}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateMilitaryServiceArray('otherInfo', 'oi_military_service', ${index}, 'oi_sm_service_branch', this.value)">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Rank or Rating</label>
                            <input type="text" 
                                value="${service.oi_sm_rank || ''}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateMilitaryServiceArray('otherInfo', 'oi_military_service', ${index}, 'oi_sm_rank', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Military Speciality</label>
                            <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    rows="3"
                                    onchange="updateMilitaryServiceArray('otherInfo', 'oi_military_service', ${index}, 'oi_militay_speciality', this.value)">${service.oi_militay_speciality || ''}</textarea>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Service Date From</label>
                            <input type="date" 
                                value="${service.oi_sm_serve_from || ''}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateMilitaryServiceArray('otherInfo', 'oi_military_service', ${index}, 'oi_sm_serve_from', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Service Date To</label>
                            <input type="date" 
                                value="${service.oi_sm_serve_to || ''}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateMilitaryServiceArray('otherInfo', 'oi_military_service', ${index}, 'oi_sm_serve_to', this.value)">
                        </div>
                    </div>
                    <button type="button" 
                            class="mt-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
                            onclick="removeMilitaryServiceField(${index})">
                        Remove Service Record
                    </button>
                </div>
            `).join('');
    }

    // Utility
    function updateApplicantDataForUSA(category, field, value) {
        if (!state.applicants[state.currentApplicant][category]) {
            state.applicants[state.currentApplicant][category] = {};
        }
        state.applicants[state.currentApplicant][category][field] = value;
        saveToLocalStorage(); // ✅ এই line অবশ্যই থাকতে হবে
    }

    function updateLocationData(index, field, value) {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.travelInfo.locations) {
            applicant.travelInfo.locations = [];
        }
        if (!applicant.travelInfo.locations[index]) {
            applicant.travelInfo.locations[index] = {};
        }
        applicant.travelInfo.locations[index][field] = value;
        saveToLocalStorage();
    }

    // For USA end

    // ========== ACCOMMODATION RELATED FUNCTIONS ==========
    function addAccommodationAddress() {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.accommodationDetails.addresses) {
            applicant.accommodationDetails.addresses = [];
        }
        if (!applicant.accommodationDetails.hotels) {
            applicant.accommodationDetails.hotels = [];
        }

        applicant.accommodationDetails.addresses.push({
            line1: '',
            line2: '',
            city: '',
            state: '',
            postalCode: ''
        });
        applicant.accommodationDetails.hotels.push('');
        generateFormSteps();
        saveToLocalStorage();
    }

    function removeAccommodationAddress(index) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.accommodationDetails.addresses.splice(index, 1);
        applicant.accommodationDetails.hotels.splice(index, 1);
        generateFormSteps();
        saveToLocalStorage();
    }

    function updateAccommodationHotel(index, value) {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.accommodationDetails.hotels) {
            applicant.accommodationDetails.hotels = [];
        }
        applicant.accommodationDetails.hotels[index] = value;
        saveToLocalStorage();
    }

    function updateAccommodationAddressData(addressIndex, field, value) {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.accommodationDetails.addresses) {
            applicant.accommodationDetails.addresses = [];
        }
        applicant.accommodationDetails.addresses[addressIndex][field] = value;
        saveToLocalStorage();
    }

    // ========== CONTACT RELATED FUNCTIONS ==========
    function addContactField(type) {
        const applicant = state.applicants[state.currentApplicant];

        if (type === 'emails') {
            applicant.contactInfo.emails.push('');
        } else if (type === 'phones') {
            applicant.contactInfo.phones.push('');
        } else if (type === 'addresses') {
            applicant.contactInfo.addresses.push({
                line1: '',
                line2: '',
                city: '',
                state: '',
                postalCode: '',
                isCorrespondence: false,
                livedInFor: '',
                ownershipStatus: ''
            });
        }

        generateFormSteps();
        saveToLocalStorage();
    }

    function removeContactField(type, index) {
        const applicant = state.applicants[state.currentApplicant];

        if (type === 'emails') {
            applicant.contactInfo.emails.splice(index, 1);
        } else if (type === 'phones') {
            applicant.contactInfo.phones.splice(index, 1);
        } else if (type === 'addresses') {
            applicant.contactInfo.addresses.splice(index, 1);
        }

        generateFormSteps();
        saveToLocalStorage();
    }

    function updateContactArrayData(field, index, value) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.contactInfo[field][index] = value;
        saveToLocalStorage();
    }

    function updateContactAddressData(addressIndex, field, value) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.contactInfo.addresses[addressIndex][field] = value;
        saveToLocalStorage();
    }

    // ========== FAMILY RELATED FUNCTIONS ==========
    function addFamilyMember() {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.familyInfo.familyMembers) {
            applicant.familyInfo.familyMembers = [];
        }
        applicant.familyInfo.familyMembers.push({
            relation: '',
            givenName: '',
            familyName: '',
            dob: '',
            nationality: '',
            liveWith: false,
            travellingUK: false,
            passportNo: ''
        });

        generateFormSteps();
        saveToLocalStorage();
    }

    function removeFamilyMember(index) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.familyInfo.familyMembers.splice(index, 1);

        generateFormSteps();
        saveToLocalStorage();
    }

    function updateFamilyMemberData(memberIndex, field, value) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.familyInfo.familyMembers[memberIndex][field] = value;

        if (field === 'travellingUK') {
            const passportSection = document.getElementById(`passport-section-${memberIndex}`);
            if (passportSection) {
                if (value) {
                    passportSection.classList.remove('hidden');
                    passportSection.classList.add('block');
                } else {
                    passportSection.classList.remove('block');
                    passportSection.classList.add('hidden');
                }
            }
        }

        if (field === 'in_usa') {
            const usaStatusSection = document.getElementById(`usa-status-section-${memberIndex}`);
            if (usaStatusSection) {
                if (value) {
                    usaStatusSection.classList.remove('hidden');
                    usaStatusSection.classList.add('block');
                } else {
                    usaStatusSection.classList.remove('block');
                    usaStatusSection.classList.add('hidden');
                }
            }
        }

        if (field === 'relation') {
            handleFamilyRelationChange(memberIndex, value);
        }

        if (field === 'have_same_address') {
            handleSpouseAddressChange(memberIndex, value);
        }

        if (field === 'dob') {
            const isoDate = convertToISO(value);
            applicant.familyInfo.familyMembers[memberIndex][field] = isoDate;
        }

        saveToLocalStorage();
    }

    function handleFamilyRelationChange(index, relation) {
        toggleConditionalBlock(`spouse-fields-${index}`, relation === 'spouse');
    }

    function handleSpouseAddressChange(index, addressType) {
        toggleConditionalBlock(`spouse-address-fields-${index}`, addressType === 'Others');
    }

    function updateFamilyRelativeAddress(field, value) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.familyInfo.relativeAddress[field] = value;
        saveToLocalStorage();
    }

    // ========== PAYMENT RELATED FUNCTIONS ==========
    function addPaymentInfo() {
        const applicant = state.applicants[state.currentApplicant];
        if (!applicant.incomeExpenditure.paymentInfo) {
            applicant.incomeExpenditure.paymentInfo = [];
        }
        applicant.incomeExpenditure.paymentInfo.push({
            currency: '',
            amount: '',
            paidFor: ''
        });

        generateFormSteps();
        saveToLocalStorage();
    }

    function removePaymentInfo(index) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.incomeExpenditure.paymentInfo.splice(index, 1);

        generateFormSteps();
        saveToLocalStorage();
    }

    function updatePaymentInfoData(paymentIndex, field, value) {
        const applicant = state.applicants[state.currentApplicant];
        applicant.incomeExpenditure.paymentInfo[paymentIndex][field] = value;
        saveToLocalStorage();
    }

    // ========== CORE APPLICATION FUNCTIONS ==========
    function updateApplicantData(category, field, value) {
        state.applicants[state.currentApplicant][category][field] = value;

        // Special handling for NID info
        if (category === 'nidInfo' && field === 'has_nid') {
            const nidDetails = document.getElementById('nid-details');
            if (value) {
                nidDetails.classList.remove('hidden');
                nidDetails.classList.add('block');
            } else {
                nidDetails.classList.remove('block');
                nidDetails.classList.add('hidden');
            }
        }

        // Special handling for employment status
        if (category === 'employmentInfo' && field === 'employmentStatus') {
            generateFormSteps();
        }

        // Special handling for travel reason
        if (category === 'travelInfo' && field === 'visitMainReason') {
            generateFormSteps();
        }

        // Special handling for accommodation hasAddress
        if (category === 'accommodationDetails' && field === 'hasAddress') {
            generateFormSteps();
        }

        // Check if current applicant is now complete
        if (isApplicantComplete(state.currentApplicant)) {
            state.applicants[state.currentApplicant].completed = true;
        }

        generateStepNavigation();
        saveToLocalStorage();
        updateProgressIndicators();
    }

    function nextStep() {
        if (!validateCurrentStep()) {
            alert('Please fill in all required fields before proceeding.');
            return;
        }

        if (state.currentStep < state.totalSteps - 1) {
            state.currentStep++;
            generateFormSteps();
            generateStepNavigation();
            updateUI();
        } else {
            state.applicants[state.currentApplicant].completed = true;

            const allApplicantsComplete = state.applicants.every(applicant => applicant.completed);

            if (allApplicantsComplete) {
                showSummary();
            } else if (state.currentApplicant < state.totalApplicants - 1) {
                document.getElementById('next-applicant-btn').classList.remove('hidden');
                document.getElementById('next-btn').classList.add('hidden');
            }

            updateProgressIndicators();
        }

        saveToLocalStorage();
    }

    function nextApplicant() {
        if (state.currentApplicant < state.totalApplicants - 1) {
            state.currentApplicant++;
            state.currentStep = 0;

            document.getElementById('next-applicant-btn').classList.add('hidden');
            document.getElementById('next-btn').classList.remove('hidden');

            switchApplicant(state.currentApplicant);
        } else {
            showSummary();
        }
    }

    function previousStep() {
        if (state.currentStep > 0) {
            state.currentStep--;
            generateFormSteps();
            generateStepNavigation();
            updateUI();
        } else if (state.currentStep === 0 && state.currentApplicant > 0) {
            state.currentApplicant--;
            state.currentStep = state.totalSteps - 1;
            switchApplicant(state.currentApplicant);
        }

        saveToLocalStorage();
    }

    function validateCurrentStep() {
        return true;
    }

    function updateUI() {
        document.querySelectorAll('.step').forEach((step, index) => {
            if (index === state.currentStep) {
                step.classList.add('active');
            } else {
                step.classList.remove('active');
            }
        });

        const individualProgressPercentage = ((state.currentStep + 1) / state.totalSteps) * 100;
        document.getElementById('individual-progress-bar').style.width = `${individualProgressPercentage}%`;

        document.getElementById('current-step').textContent = state.currentStep + 1;
        document.getElementById('current-applicant-number').textContent = state.currentApplicant + 1;

        if (state.currentStep === 0 && state.currentApplicant === 0) {
            document.getElementById('prev-btn').classList.add('hidden');
        } else {
            document.getElementById('prev-btn').classList.remove('hidden');
        }

        const isSummaryStep = document.getElementById('form-steps').innerHTML.includes('Application Summary');

        if (isSummaryStep) {
            document.getElementById('submit-btn').classList.add('hidden');
            document.getElementById('next-btn').classList.add('hidden');
            document.getElementById('next-applicant-btn').classList.add('hidden');
        } else if (state.currentStep === state.totalSteps - 1) {
            document.getElementById('submit-btn').classList.add('hidden');
            document.getElementById('next-btn').classList.remove('hidden');
            document.getElementById('next-applicant-btn').classList.add('hidden');
        } else {
            document.getElementById('submit-btn').classList.add('hidden');
            document.getElementById('next-btn').classList.remove('hidden');
            document.getElementById('next-applicant-btn').classList.add('hidden');
        }

        updateProgressIndicators();
    }

    function updateProgressIndicators() {
        const completedApplicants = state.applicants.filter(app => app.completed).length;
        const overallProgressPercentage = (completedApplicants / state.totalApplicants) * 100;
        document.getElementById('overall-progress-bar').style.width = `${overallProgressPercentage}%`;
        document.getElementById('completed-applicants').textContent = completedApplicants;

        generateTabs();
    }

    function showSummary() {
        const formStepsContainer = document.getElementById('form-steps');
        formStepsContainer.innerHTML = '';

        const summaryElement = document.createElement('div');
        summaryElement.className = 'step active fade-in';
        summaryElement.innerHTML = `
                <h2 class="text-xl font-bold text-gray-800 mb-6">Application Summary</h2>
                <div class="bg-gray-50 p-6 rounded-lg">
                    <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                            <div>
                                <h3 class="font-medium text-green-800">All Applicants Completed</h3>
                                <p class="text-green-700 text-sm mt-1">All ${state.totalApplicants} applicants have completed their forms. Review the information below before submission.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="summary-content">
                        ${generateSummaryContent()}
                    </div>
                    
                    <div class="mt-8 flex justify-between">
                        <button id="download-json" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download JSON
                        </button>
                        <button id="submit-final" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
                            Submit Application
                        </button>
                    </div>
                    
                    <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>By submitting this application, you confirm that all information provided is true and accurate to the best of your knowledge.</p>
                                    <p class="mt-2">Providing false information may result in your application being refused and could affect future visa applications.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

        formStepsContainer.appendChild(summaryElement);

        document.getElementById('download-json').addEventListener('click', downloadJSON);
        document.getElementById('submit-final').addEventListener('click', submitApplication);

        document.getElementById('prev-btn').classList.remove('hidden');
        document.getElementById('next-btn').classList.add('hidden');
        document.getElementById('next-applicant-btn').classList.add('hidden');
        document.getElementById('submit-btn').classList.add('hidden');

        document.getElementById('individual-progress-bar').style.width = '100%';
        document.getElementById('overall-progress-bar').style.width = '100%';
        document.getElementById('current-step').textContent = 'Summary';
    }

    function generateSummaryContent() {
        let summaryHTML = '';

        for (let i = 0; i < state.totalApplicants; i++) {
            const applicant = state.applicants[i];

            summaryHTML += `
                    <div class="mb-8 border border-gray-200 rounded-lg overflow-hidden">
                        <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">Applicant ${i + 1}</h3>
                            <span class="text-xs font-mono bg-blue-100 text-blue-800 py-1 px-2 rounded">${applicant.id}</span>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">Passport Information</h4>
                                    <p class="text-gray-600">Given Name: ${applicant.passportInfo.pp_given_name || 'Not provided'}</p>
                                    <p class="text-gray-600">Family Name: ${applicant.passportInfo.pp_family_name || 'Not provided'}</p>
                                    <p class="text-gray-600">Gender: ${applicant.passportInfo.pp_gender || 'Not provided'}</p>
                                    <p class="text-gray-600">Passport Number: ${applicant.passportInfo.pp_number || 'Not provided'}</p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">NID Information</h4>
                                    <p class="text-gray-600">Has NID: ${applicant.nidInfo.has_nid !== null ? (applicant.nidInfo.has_nid ? 'Yes' : 'No') : 'Not provided'}</p>
                                    ${applicant.nidInfo.has_nid ? `
                                        <p class="text-gray-600">NID Number: ${applicant.nidInfo.nid_number || 'Not provided'}</p>
                                    ` : ''}
                                </div>
                                
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">Contact Information</h4>
                                    <p class="text-gray-600">Emails: ${applicant.contactInfo.emails.filter(e => e).join(', ') || 'Not provided'}</p>
                                    <p class="text-gray-600">Phones: ${applicant.contactInfo.phones.filter(p => p).join(', ') || 'Not provided'}</p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">Family Information</h4>
                                    <p class="text-gray-600">Relationship Status: ${applicant.familyInfo.relationshipStatus || 'Not provided'}</p>
                                    <p class="text-gray-600">Family Members: ${applicant.familyInfo.familyMembers.length || '0'}</p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">Accommodation Details</h4>
                                    <p class="text-gray-600">Has Accommodation: ${applicant.accommodationDetails.hasAddress !== null ? (applicant.accommodationDetails.hasAddress ? 'Yes' : 'No') : 'Not provided'}</p>
                                    ${applicant.accommodationDetails.hasAddress === false ? `
                                        <p class="text-gray-600">Plans: ${applicant.accommodationDetails.custom_accommodation || 'Not provided'}</p>
                                    ` : ''}
                                </div>
                                
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">Employment Information</h4>
                                    <p class="text-gray-600">Employment Status: ${applicant.employmentInfo.employmentStatus || 'Not provided'}</p>
                                </div>
                                
                                <div class="summary-item">
                                    <h4 class="font-medium text-gray-700">Travel Information</h4>
                                    <p class="text-gray-600">Main Reason: ${applicant.travelInfo.visitMainReason || 'Not provided'}</p>
                                    <p class="text-gray-600">Arrival Date: ${applicant.travelInfo.arrivalDate || 'Not provided'}</p>
                                    <p class="text-gray-600">Departure Date: ${applicant.travelInfo.leaveDate || 'Not provided'}</p>
                                </div>
                                
                                <div class="summary-item md:col-span-2">
                                    <h4 class="font-medium text-gray-700">Application Status</h4>
                                    <p class="text-gray-600">Completed: ${applicant.completed ? 'Yes' : 'No'}</p>
                                    <p class="text-gray-600">Steps filled: ${countCompletedSteps(i)}/${state.totalSteps}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
        }

        return summaryHTML;
    }

    // Updated toggle function to properly handle conditional blocks
    function toggleConditionalBlock(blockId, show) {
        const block = document.getElementById(blockId);
        if (block) {
            if (show) {
                block.style.display = 'block';
                block.classList.add('active');
            } else {
                block.style.display = 'none';
                block.classList.remove('active');
            }
        }
    }

    function downloadJSON() {
        const applicationData = {
            pnr: state.pnr,
            totalApplicants: state.totalApplicants,
            applicants: state.applicants,
            timestamp: new Date().toISOString()
        };

        const dataStr = JSON.stringify(applicationData, null, 2);
        const dataBlob = new Blob([dataStr], {
            type: 'application/json'
        });

        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = `uk-visa-application-${state.pnr}.json`;
        link.click();
    }

    function saveToLocalStorage() {
        const applicationData = {
            pnr: state.pnr,
            nameOfApplicant: state.applicants[0].passportInfo.pp_family_name,
            totalApplicants: state.totalApplicants,
            applicants: state.applicants,
            currentApplicant: state.currentApplicant,
            currentStep: state.currentStep,
            timestamp: new Date().toISOString()
        };

        localStorage.setItem('ukVisaApplication-' + state.pnr, JSON.stringify(applicationData));
    }

    function saveAndExit() {
        saveToLocalStorage();
        alert('Your application has been saved. You can return later to complete it.');
    }


    // API Section Start
    function submitApplication() {
        const applicationData = {
            pnr: state.pnr,
            nameOfApplicant: state.applicants[0].passportInfo.pp_family_name,
            totalApplicants: state.totalApplicants,
            applicants: state.applicants,
            status: "completed",
            timestamp: new Date().toISOString()
        };

        console.log(applicationData);

        fetch('/server/submit-application.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(applicationData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                alert(`Application with PNR ${state.pnr} submitted successfully!`);

                localStorage.removeItem('ukVisaApplication-' + state.pnr);

                document.getElementById('initial-screen').classList.remove('hidden');
                document.getElementById('multi-applicant-form').classList.add('hidden');
                document.getElementById('saved-application-section').classList.add('hidden');

                state.currentApplicant = 0;
                state.currentStep = 0;
                state.totalApplicants = 1;
                state.pnr = '';
                state.applicants = [];
                initializeApplicant(0);

                document.getElementById('applicant-count').value = '1';

                window.location.href = 'application-form.php';
            })
            .catch(error => {
                console.error('Error submitting application:', error);
                alert('There was an error submitting your application. Please try again.');
            });
    }
    // API Section End


    // আপনার existing code এর নিচে এই ফাংশনগুলো যোগ করুন

    // তারিখ validation
    function isValidDate(dateString) {
        const pattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        if (!pattern.test(dateString)) return false;

        const day = parseInt(dateString.split('/')[0]);
        const month = parseInt(dateString.split('/')[1]);
        const year = parseInt(dateString.split('/')[2]);

        // সহজ validation
        if (day < 1 || day > 31) return false;
        if (month < 1 || month > 12) return false;
        if (year < 1900 || year > 2100) return false;

        return true;
    }

    // DD/MM/YYYY থেকে YYYY-MM-DD
    function convertToISO(dateString) {
        if (!isValidDate(dateString)) return '';
        const parts = dateString.split('/');
        return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
    }

    // YYYY-MM-DD থেকে DD/MM/YYYY
    function convertToDisplay(isoDate) {
        if (!isoDate) return '';
        const parts = isoDate.split('-');
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    // তারিখ change handler
    function handleDateChange(category, field, value) {
        if (value === '') {
            updateApplicantData(category, field, '');
            return;
        }

        if (isValidDate(value)) {
            const isoDate = convertToISO(value);
            updateApplicantData(category, field, isoDate);
        } else {
            alert('Invalid date format. Please use DD/MM/YYYY');
            event.target.value = '';
            updateApplicantData(category, field, '');
        }
    }
</script>