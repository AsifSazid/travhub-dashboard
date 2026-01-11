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

        .form-section {
            border-left: 4px solid #3b82f6;
        }

        .summary-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 0;
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

        .conditional-block {
            display: none;
        }

        .conditional-block.active {
            display: block;
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

        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
    </style>

    <!-- Header -->
    <header class="text-center mb-12">
        <div class="flex items-center justify-center mb-4">
            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                <i class="fas fa-info-circle text-white text-xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Traveller Information Form</h1>
        </div>
    </header>

    <!-- Main Application Container -->
    <div class="bg-white rounded-xl shadow-lg">
        <!-- PNR Display -->
        <div class="px-8 pt-8 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Traveller PNR: <span id="pnr-display" class="font-mono text-blue-600"></span></h2>
                <p class="text-gray-600 text-sm">Your form data is automatically saved to Local Storage</p>
            </div>
            <div class="flex space-x-2">
                <button id="save-exit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 text-sm">
                    <i class="fas fa-save mr-2"></i>Save & Exit
                </button>
            </div>
        </div>

        <!-- Progress -->
        <div class="px-8 pt-4">
            <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Progress</span>
                <span class="text-sm font-medium text-gray-500"><span id="current-step">1</span> of <span id="total-steps">16</span></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progress-bar" class="bg-green-600 h-2.5 rounded-full progress-bar" style="width: 6.25%"></div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex flex-col md:flex-row p-8">
            <!-- Step Navigation Sidebar -->
            <div class="w-full md:w-1/4 mb-6 md:mb-0 md:pr-6">
                <div class="bg-gray-50 rounded-lg p-4 sticky top-4">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list-ol mr-2 text-blue-500"></i>
                        Form Steps
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
            <div>
                <button id="next-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                    Save & Next Step <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
            <button id="submit-btn" class="hidden bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300">
                Submit Form
            </button>
        </div>
    </div>


    <script>
        // Application state
        const state = {
            currentStep: 0,
            totalSteps: 16,
            pnr: '',
            applicant: {
                id: '',
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
                    ti_intended_arrival_date: '',
                    ti_stay_length: '',
                    ti_length_type: '',
                    ti_arrival_date: '',
                    ti_arrival_flight_no: '',
                    ti_arrival_city: '',
                    ti_departure_date: '',
                    ti_departure_flight_no: '',
                    ti_departure_city: '',
                    locations: [{
                        address_line_1: '',
                        address_line_2: '',
                        city: '',
                        state: '',
                        zip_code: ''
                    }],
                    trip_payment: '',
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
                    trip_paying_person_address_country: ''
                },
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
                usContactInfo: {
                    usci_contact_type: '',
                    usci_contact_person_surname: '',
                    usci_contact_person_given_name: '',
                    'usci contact person telephone': '',
                    'usci contact person email': '',
                    'usci contact person relationship': '',
                    'usci contact person address line 1': '',
                    'usci contact person address line 2': '',
                    'usci contact person address city': '',
                    'usci contact person address state': '',
                    'usci contact person address zip code': ''
                },
                workInfoForUSA: {
                    wi_primary_occupation_type: '',
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
            },
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

        // Country data
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

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Generate PNR
            state.pnr = generatePNR();
            state.applicant.id = state.pnr + '-APPT-001';
            document.getElementById('pnr-display').textContent = state.pnr;

            // Set up event listeners
            document.getElementById('prev-btn').addEventListener('click', previousStep);
            document.getElementById('next-btn').addEventListener('click', nextStep);
            document.getElementById('submit-btn').addEventListener('click', submitForm);
            document.getElementById('save-exit').addEventListener('click', saveAndExit);

            // Generate initial form
            generateFormSteps();
            generateStepNavigation();
            updateUI();

            // Load saved data if exists
            loadFromLocalStorage();
        });

        // Generate a unique PNR
        function generatePNR() {
            const timestamp = Date.now().toString().slice(-6);
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            return `TRH-PNR-${timestamp}K${random}`;
        }

        // Generate step navigation sidebar
        function generateStepNavigation() {
            const stepNavContainer = document.getElementById('step-navigation');
            stepNavContainer.innerHTML = '';

            state.steps.forEach((step, index) => {
                const isCurrent = index === state.currentStep;
                const isCompleted = isStepCompleted(index);

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

        // Check if a step is completed
        function isStepCompleted(stepIndex) {
            const applicant = state.applicant;

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
                    return true; // This is optional
                default:
                    return true; // All other steps are optional for completion check
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

        // Generate form steps
        function generateFormSteps() {
            const formStepsContainer = document.getElementById('form-steps');
            formStepsContainer.innerHTML = '';

            state.steps.forEach((step, index) => {
                const stepElement = document.createElement('div');
                stepElement.className = `step fade-in ${index === state.currentStep ? 'active' : ''}`;
                stepElement.id = `step-${index}`;

                stepElement.innerHTML = `
                    <h2 class="text-xl font-bold text-gray-800 mb-6">${step.name}</h2>
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
            const applicant = state.applicant;

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
                case 11: // Previous U.S. Travel Step
                    return generatePreviousTravelStepForUSA(applicant);
                case 12: // U.S. Contact Information Step
                    return generateUSContactStep(applicant);
                case 13: // Work Information Step
                    return generateWorkInfoStepForUSA(applicant);
                case 14: // Educational Information Step
                    return generateEducationInfoStepForUSA(applicant);
                case 15: // Other Information Step
                    return generateOtherInfoStepForUSA(applicant);
                default:
                    return '<p>Step content not defined.</p>';
            }
        }

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
                        <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                        <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_dob || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_dob', this.value)" required>
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
                        <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_issue_date || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_issue_date', this.value)" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Expiry Date *</label>
                        <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                               value="${applicant.passportInfo.pp_expiry_date || ''}" 
                               onchange="updateApplicantData('passportInfo', 'pp_expiry_date', this.value)" required>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-4">* Required fields</p>
            `;
        }

        // Generate Contact Information step
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
                        </div>
                    </div>
                `;
            });

            return `
                <div class="space-y-6">
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

                    <div class="border-t pt-6">
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
                                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${applicant.nidInfo.nid_isue_date || ''}" 
                                       onchange="updateApplicantData('nidInfo', 'nid_isue_date', this.value)">
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
                familyMembersHTML += `
                    <div class="family-member-group">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="font-medium text-gray-700">Family Member ${index + 1}</h4>
                            <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-lg text-sm" onclick="removeFamilyMember(${index})">
                                Remove Member
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                </select>
                            </div>
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
                                <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${member.dob || ''}" 
                                       onchange="updateFamilyMemberData(${index}, 'dob', this.value)">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Country of Nationality</label>
                                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                       value="${member.nationality || ''}" 
                                       onchange="updateFamilyMemberData(${index}, 'nationality', this.value)">
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

        // Generate Accommodation Details step
        function generateAccommodationDetailsStep(applicant) {
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
                                    placeholder="Example: I plan to stay in hotels and will book accommodation upon arrival..."
                                    onchange="updateApplicantData('accommodationDetails', 'custom_accommodation', this.value)"
                                >${applicant.accommodationDetails.custom_accommodation || ''}</textarea>
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
                            <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="${applicant.travelInfo.arrivalDate || ''}" 
                                   onchange="updateApplicantData('travelInfo', 'arrivalDate', this.value)" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Date you plan to leave the UK *</label>
                            <input type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="${applicant.travelInfo.leaveDate || ''}" 
                                   onchange="updateApplicantData('travelInfo', 'leaveDate', this.value)" required>
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

        // USA Steps (Simplified versions)
        function generatetravelInfoForUSAStep(applicant) {
            const ti = applicant.travelInfoForUSA || {};
            return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Purpose of Travel *</label>
                        <input type="text" name="ti_travel_purpose" 
                            value="${ti.ti_travel_purpose || ''}" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="updateApplicantData('travelInfoForUSA', 'ti_travel_purpose', this.value)" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Date of Arrival in the USA</label>
                            <input type="date" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                value="${ti.ti_arrival_date || ''}" 
                                onchange="updateApplicantData('travelInfoForUSA', 'ti_arrival_date', this.value)">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Date of Departure</label>
                            <input type="date" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                value="${ti.ti_departure_date || ''}" 
                                onchange="updateApplicantData('travelInfoForUSA', 'ti_departure_date', this.value)">
                        </div>
                    </div>
                </div>
            `;
        }

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
                                    onchange="updateApplicantData('travelInfoForUSA', 'tci_have_anyone', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="tci_have_anyone" value="0" 
                                    ${!tci.tci_have_anyone ? 'checked' : ''}
                                    onchange="updateApplicantData('travelInfoForUSA', 'tci_have_anyone', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
        }

        function generatePreviousTravelStepForUSA(applicant) {
            const pust = applicant.travelHistoryForUSA || {};
            return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Have you ever issued a visa to the USA?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_ever_issued" value="1" 
                                    ${pust.pust_have_ever_issued ? 'checked' : ''}
                                    onchange="updateApplicantData('travelHistoryForUSA', 'pust_have_ever_issued', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="pust_have_ever_issued" value="0" 
                                    ${!pust.pust_have_ever_issued ? 'checked' : ''}
                                    onchange="updateApplicantData('travelHistoryForUSA', 'pust_have_ever_issued', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
        }

        function generateUSContactStep(applicant) {
            const usci = applicant.usContactInfo || {};
            return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Contact Type</label>
                        <select name="usci_contact_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('usContactInfo', 'usci_contact_type', this.value)">
                            <option value="">Select Type</option>
                            <option value="Person" ${(usci.usci_contact_type === 'Person') ? 'selected' : ''}>Person</option>
                            <option value="Company" ${(usci.usci_contact_type === 'Company') ? 'selected' : ''}>Company</option>
                            <option value="Hotel" ${(usci.usci_contact_type === 'Hotel') ? 'selected' : ''}>Hotel</option>
                        </select>
                    </div>
                </div>
            `;
        }

        function generateWorkInfoStepForUSA(applicant) {
            const wi = applicant.workInfoForUSA || {};
            return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Primary Occupation Type *</label>
                        <select name="wi_primary_occupation_type" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                onchange="updateApplicantData('workInfoForUSA', 'wi_primary_occupation_type', this.value)" required>
                            <option value="">Select Category</option>
                            <option value="Student" ${(wi.wi_primary_occupation_type === 'Student') ? 'selected' : ''}>Student</option>
                            <option value="Homemaker" ${(wi.wi_primary_occupation_type === 'Homemaker') ? 'selected' : ''}>Homemaker</option>
                            <option value="Retired" ${(wi.wi_primary_occupation_type === 'Retired') ? 'selected' : ''}>Retired</option>
                            <option value="Government" ${(wi.wi_primary_occupation_type === 'Government') ? 'selected' : ''}>Government</option>
                            <option value="Private Sector" ${(wi.wi_primary_occupation_type === 'Private Sector') ? 'selected' : ''}>Private Sector</option>
                        </select>
                    </div>
                </div>
            `;
        }

        function generateEducationInfoStepForUSA(applicant) {
            const edi = applicant.educationalInfo || {};
            return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">Have you attended any educational institution at a secondary level or above?</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="edi_have_attended_secondary_level" value="1" 
                                       ${edi.edi_have_attended_secondary_level ? 'checked' : ''}
                                       onchange="updateApplicantData('educationalInfo', 'edi_have_attended_secondary_level', true)">
                                <span class="ml-2">Yes</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="edi_have_attended_secondary_level" value="0" 
                                       ${!edi.edi_have_attended_secondary_level ? 'checked' : ''}
                                       onchange="updateApplicantData('educationalInfo', 'edi_have_attended_secondary_level', false)">
                                <span class="ml-2">No</span>
                            </label>
                        </div>
                    </div>
                </div>
            `;
        }

        function generateOtherInfoStepForUSA(applicant) {
            const oi = applicant.otherInfo || {};
            return `
                <div class="space-y-6">
                    <div>
                        <label class="block text-gray-700 mb-2">List of Languages Spoken</label>
                        <textarea name="oi_spoken_language_list" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                rows="3"
                                onchange="updateApplicantData('otherInfo', 'oi_spoken_language_list', this.value)">${oi.oi_spoken_language_list || ''}</textarea>
                    </div>
                </div>
            `;
        }

        // ========== FORM FIELD FUNCTIONS ==========

        // Contact field functions
        function addContactField(type) {
            if (type === 'emails') {
                state.applicant.contactInfo.emails.push('');
            } else if (type === 'phones') {
                state.applicant.contactInfo.phones.push('');
            } else if (type === 'addresses') {
                state.applicant.contactInfo.addresses.push({
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
            if (type === 'emails') {
                state.applicant.contactInfo.emails.splice(index, 1);
            } else if (type === 'phones') {
                state.applicant.contactInfo.phones.splice(index, 1);
            } else if (type === 'addresses') {
                state.applicant.contactInfo.addresses.splice(index, 1);
            }
            generateFormSteps();
            saveToLocalStorage();
        }

        function updateContactArrayData(field, index, value) {
            state.applicant.contactInfo[field][index] = value;
            saveToLocalStorage();
        }

        function updateContactAddressData(addressIndex, field, value) {
            state.applicant.contactInfo.addresses[addressIndex][field] = value;
            saveToLocalStorage();
        }

        // Family member functions
        function addFamilyMember() {
            if (!state.applicant.familyInfo.familyMembers) {
                state.applicant.familyInfo.familyMembers = [];
            }
            state.applicant.familyInfo.familyMembers.push({
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
            state.applicant.familyInfo.familyMembers.splice(index, 1);
            generateFormSteps();
            saveToLocalStorage();
        }

        function updateFamilyMemberData(memberIndex, field, value) {
            state.applicant.familyInfo.familyMembers[memberIndex][field] = value;
            saveToLocalStorage();
        }

        function updateFamilyRelativeAddress(field, value) {
            state.applicant.familyInfo.relativeAddress[field] = value;
            saveToLocalStorage();
        }

        // Accommodation functions
        function addAccommodationAddress() {
            if (!state.applicant.accommodationDetails.addresses) {
                state.applicant.accommodationDetails.addresses = [];
            }
            if (!state.applicant.accommodationDetails.hotels) {
                state.applicant.accommodationDetails.hotels = [];
            }

            state.applicant.accommodationDetails.addresses.push({
                line1: '',
                line2: '',
                city: '',
                state: '',
                postalCode: ''
            });
            state.applicant.accommodationDetails.hotels.push('');
            generateFormSteps();
            saveToLocalStorage();
        }

        function removeAccommodationAddress(index) {
            state.applicant.accommodationDetails.addresses.splice(index, 1);
            state.applicant.accommodationDetails.hotels.splice(index, 1);
            generateFormSteps();
            saveToLocalStorage();
        }

        function updateAccommodationHotel(index, value) {
            if (!state.applicant.accommodationDetails.hotels) {
                state.applicant.accommodationDetails.hotels = [];
            }
            state.applicant.accommodationDetails.hotels[index] = value;
            saveToLocalStorage();
        }

        function updateAccommodationAddressData(addressIndex, field, value) {
            if (!state.applicant.accommodationDetails.addresses) {
                state.applicant.accommodationDetails.addresses = [];
            }
            state.applicant.accommodationDetails.addresses[addressIndex][field] = value;
            saveToLocalStorage();
        }

        // Payment functions
        function addPaymentInfo() {
            if (!state.applicant.incomeExpenditure.paymentInfo) {
                state.applicant.incomeExpenditure.paymentInfo = [];
            }
            state.applicant.incomeExpenditure.paymentInfo.push({
                currency: '',
                amount: '',
                paidFor: ''
            });
            generateFormSteps();
            saveToLocalStorage();
        }

        function removePaymentInfo(index) {
            state.applicant.incomeExpenditure.paymentInfo.splice(index, 1);
            generateFormSteps();
            saveToLocalStorage();
        }

        function updatePaymentInfoData(paymentIndex, field, value) {
            state.applicant.incomeExpenditure.paymentInfo[paymentIndex][field] = value;
            saveToLocalStorage();
        }

        // ========== CORE APPLICATION FUNCTIONS ==========

        function updateApplicantData(category, field, value) {
            state.applicant[category][field] = value;

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

            generateStepNavigation();
            saveToLocalStorage();
            updateProgressIndicators();
        }

        function nextStep() {
            if (state.currentStep < state.totalSteps - 1) {
                state.currentStep++;
                generateFormSteps();
                generateStepNavigation();
                updateUI();
            } else {
                // Last step - show submit button
                document.getElementById('submit-btn').classList.remove('hidden');
                document.getElementById('next-btn').classList.add('hidden');
            }
            saveToLocalStorage();
        }

        function previousStep() {
            if (state.currentStep > 0) {
                state.currentStep--;
                generateFormSteps();
                generateStepNavigation();
                updateUI();
                document.getElementById('submit-btn').classList.add('hidden');
                document.getElementById('next-btn').classList.remove('hidden');
            }
            saveToLocalStorage();
        }

        function updateUI() {
            document.querySelectorAll('.step').forEach((step, index) => {
                if (index === state.currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });

            const progressPercentage = ((state.currentStep + 1) / state.totalSteps) * 100;
            document.getElementById('progress-bar').style.width = `${progressPercentage}%`;
            document.getElementById('current-step').textContent = state.currentStep + 1;

            if (state.currentStep === 0) {
                document.getElementById('prev-btn').classList.add('hidden');
            } else {
                document.getElementById('prev-btn').classList.remove('hidden');
            }

            if (state.currentStep === state.totalSteps - 1) {
                document.getElementById('submit-btn').classList.remove('hidden');
                document.getElementById('next-btn').classList.add('hidden');
            } else {
                document.getElementById('submit-btn').classList.add('hidden');
                document.getElementById('next-btn').classList.remove('hidden');
            }
        }

        function updateProgressIndicators() {
            const completedSteps = state.steps.filter((step, index) => isStepCompleted(index)).length;
            const progressPercentage = (completedSteps / state.totalSteps) * 100;
            document.getElementById('progress-bar').style.width = `${progressPercentage}%`;
        }

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

        function saveToLocalStorage() {
            const formData = {
                pnr: state.pnr,
                applicant: state.applicant,
                currentStep: state.currentStep,
                timestamp: new Date().toISOString()
            };
            localStorage.setItem('formData-' + state.pnr, JSON.stringify(formData));
        }

        function loadFromLocalStorage() {
            const savedData = localStorage.getItem('formData-' + state.pnr);
            if (savedData) {
                const formData = JSON.parse(savedData);
                state.applicant = formData.applicant;
                state.currentStep = formData.currentStep;
                generateFormSteps();
                generateStepNavigation();
                updateUI();
                updateProgressIndicators();
            }
        }

        function saveAndExit() {
            saveToLocalStorage();
            alert('Your form data has been saved. You can return later to complete it.');
        }

        function submitForm() {
            const formData = {
                pnr: state.pnr,
                applicant: state.applicant,
                timestamp: new Date().toISOString()
            };

            console.log('Form data to submit:', formData);

            // Here you would typically send the data to your server
            // For now, just show a success message
            alert(`Form submitted successfully with PNR: ${state.pnr}`);

            // Clear localStorage after successful submission
            localStorage.removeItem('formData-' + state.pnr);
        }
    </script>