<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VISA Application Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include '../elements/header.php'; ?>

    <!-- Sidebar -->
    <?php include '../elements/aside.php'; ?>

    <!-- Main Content -->
    <main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
        <div class="p-6">
            <div class="grid grid-cols-6 gap-4">
                <!-- Left Column: Application Forms (col-span-4) -->
                <div class="col-span-4 bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <!-- Header -->
                    <div class="mb-2">
                        <h1 class="text-3xl font-bold text-gray-800">LEAD Generation</h1>
                        <p class="text-gray-600 mt-2">Select service type and complete the form</p>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="mb-4">
                        <div class="flex space-x-1 border-b">
                            <button class="tab-btn px-4 py-3 text-lg font-medium border-b-2 border-transparent hover:border-primary-600 hover:text-primary-600 transition-all duration-200 active" data-tab="visa">
                                <i class="fas fa-passport mr-2"></i>VISA Application
                            </button>
                            <button class="tab-btn px-4 py-3 text-lg font-medium border-b-2 border-transparent hover:border-primary-600 hover:text-primary-600 transition-all duration-200" data-tab="hotel">
                                <i class="fas fa-hotel mr-2"></i>Hotel Booking
                            </button>
                            <button class="tab-btn px-4 py-3 text-lg font-medium border-b-2 border-transparent hover:border-primary-600 hover:text-primary-600 transition-all duration-200" data-tab="air">
                                <i class="fas fa-plane mr-2"></i>Air Ticket
                            </button>
                            <button class="tab-btn px-4 py-3 text-lg font-medium border-b-2 border-transparent hover:border-primary-600 hover:text-primary-600 transition-all duration-200" data-tab="tour">
                                <i class="fas fa-suitcase-rolling mr-2"></i>Tour Package
                            </button>
                        </div>
                    </div>

                    <!-- Forms Container -->
                    <div class="flex-1 overflow-y-auto pr-2">
                        <!-- VISA Application Form -->
                        <form id="visaForm" class="tab-content active space-y-2">
                            <!-- Country Section -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-2">VISA Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Country</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="">Choose country</option>
                                            <option value="usa">United States</option>
                                            <option value="uk">United Kingdom</option>
                                            <option value="canada">Canada</option>
                                            <option value="australia">Australia</option>
                                            <option value="schengen">Schengen Area</option>
                                            <option value="uae">UAE</option>
                                            <option value="singapore">Singapore</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Visa Category</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="">Choose visa category</option>
                                            <option value="tourist">Tourist Visa</option>
                                            <option value="business">Business Visa</option>
                                            <option value="student">Student Visa</option>
                                            <option value="work">Work Visa</option>
                                            <option value="transit">Transit Visa</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Visa Sub Category</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="">Choose visa sub category</option>
                                            <option value="single">Single Entry</option>
                                            <option value="multiple">Multiple Entry</option>
                                            <option value="long">Long Term</option>
                                            <option value="short">Short Term</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Travel Dates Section -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date of Travel</label>
                                        <input type="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date of Return</label>
                                        <input type="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <!-- Application Type Section -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-2">Application Type</h2>
                                <div class="flex space-x-6 mb-6">
                                    <div class="flex items-center">
                                        <input type="radio" id="singleApp" name="applicationType" value="single" class="h-5 w-5 text-primary-600 focus:ring-primary-500" checked>
                                        <label for="singleApp" class="ml-2 text-gray-700">Single</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="groupApp" name="applicationType" value="group" class="h-5 w-5 text-primary-600 focus:ring-primary-500">
                                        <label for="groupApp" class="ml-2 text-gray-700">Group</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="familyApp" name="applicationType" value="family" class="h-5 w-5 text-primary-600 focus:ring-primary-500">
                                        <label for="familyApp" class="ml-2 text-gray-700">Family</label>
                                    </div>
                                </div>

                                <!-- Cost Bearer -->
                                <div class="mb-6">
                                    <label class="block text-gray-700 mb-3">Cost will bear by</label>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="relative">
                                            <input type="radio" id="self" name="costBearer" value="self" class="hidden peer" checked>
                                            <label for="self" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                                                <i class="fas fa-user text-2xl text-gray-600 mb-2"></i>
                                                <span class="font-medium">Self</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="organization" name="costBearer" value="organization" class="hidden peer">
                                            <label for="organization" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                                                <i class="fas fa-building text-2xl text-gray-600 mb-2"></i>
                                                <span class="font-medium">Organization</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="anotherPerson" name="costBearer" value="anotherPerson" class="hidden peer">
                                            <label for="anotherPerson" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                                                <i class="fas fa-users text-2xl text-gray-600 mb-2"></i>
                                                <span class="font-medium">Another Person</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Invitation Status -->
                                <div>
                                    <label class="block text-gray-700 mb-3">Invitation Status</label>
                                    <div class="flex space-x-6">
                                        <div class="flex items-center">
                                            <input type="radio" id="invitationNo" name="invitationStatus" value="no" class="h-5 w-5 text-primary-600 focus:ring-primary-500" checked>
                                            <label for="invitationNo" class="ml-2 text-gray-700">No</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="invitationOrg" name="invitationStatus" value="organization" class="h-5 w-5 text-primary-600 focus:ring-primary-500">
                                            <label for="invitationOrg" class="ml-2 text-gray-700">Organization</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="invitationPerson" name="invitationStatus" value="person" class="h-5 w-5 text-primary-600 focus:ring-primary-500">
                                            <label for="invitationPerson" class="ml-2 text-gray-700">Another Person</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Hotel Booking Form -->
                        <form id="hotelForm" class="tab-content hidden space-y-2">
                            <!-- Add More Button -->
                            <div class="flex justify-between items-center">
                                <h2 class="text-2xl font-bold text-gray-800">Hotel Bookings</h2>
                                <button type="button" id="addHotelBookingBtn" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center">
                                    <i class="fas fa-plus-circle mr-2"></i> Add Hotel Booking
                                </button>
                            </div>

                            <!-- Hotel Booking Container -->
                            <div id="hotelBookingsContainer">
                                <!-- First Hotel Booking Entry -->
                                <div class="hotel-booking-entry bg-white rounded-xl shadow-sm p-6 mb-6">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-800">Hotel Booking #1</h3>
                                        <button type="button" class="remove-hotel-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 hidden">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Check-in/Check-out -->
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Booking Dates</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-gray-700 mb-2">Check-In</label>
                                                <input type="date" name="hotelCheckIn[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Check-Out</label>
                                                <input type="date" name="hotelCheckOut[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PAX, Room, Nights -->
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Accommodation Details</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-gray-700 mb-2">Number of PAX</label>
                                                <input type="number" min="1" value="1" name="hotelPax[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Number of Room</label>
                                                <input type="number" min="1" value="1" name="hotelRooms[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Number Night Stay</label>
                                                <input type="number" min="1" value="1" name="hotelNights[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Destination/Property -->
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Property Details</h4>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 mb-2">Destination or Property</label>
                                            <input type="text" name="hotelDestination[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter destination or property name">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Note</label>
                                            <textarea rows="3" name="hotelNote[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add any special requirements or notes..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Air Ticket Form -->
                        <form id="airForm" class="tab-content hidden space-y-2">
                            <!-- Passenger Details -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4">Passenger Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Adult</label>
                                        <input type="number" min="0" value="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Child</label>
                                        <input type="number" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Infant</label>
                                        <input type="number" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Child Age(s)</label>
                                    <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter child ages separated by comma">
                                </div>
                            </div>

                            <!-- Class & Date Flexibility -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Select Class Type</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="economy">Economy</option>
                                            <option value="business">Business</option>
                                            <option value="first">First Class</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date Flexible</label>
                                        <div class="flex items-center mt-2">
                                            <label class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="dateFlexible" name="dateFlexible" class="sr-only peer">
                                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
                                            </label>
                                            <span id="flexibleStatus" class="ml-3 font-medium text-gray-600">No</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Flight Routes (Repeatable) -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-semibold text-gray-800">Flight Routes</h2>
                                    <button type="button" id="addRouteBtn" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Add Route
                                    </button>
                                </div>

                                <div id="routeFieldsContainer">
                                    <div class="route-field grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">Route</label>
                                            <input type="text" name="route[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., DEL-LHR">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Date of Fly</label>
                                            <input type="date" name="dateOfFly[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                        <div class="relative">
                                            <label class="block text-gray-700 mb-2">Note</label>
                                            <div class="flex gap-2">
                                                <input type="text" name="routeNote[]" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add note">
                                                <button type="button" class="remove-route-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 self-end hidden">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Tour Package Form -->
                        <form id="tourForm" class="tab-content hidden space-y-2">
                            <!-- Destination -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <label class="block text-gray-700 mb-2">Where to go (Country List)</label>
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Select Destination Country</option>
                                    <option value="thailand">Thailand</option>
                                    <option value="singapore">Singapore</option>
                                    <option value="malaysia">Malaysia</option>
                                    <option value="dubai">Dubai</option>
                                    <option value="maldives">Maldives</option>
                                    <option value="europe">Europe</option>
                                    <option value="usa">USA</option>
                                </select>
                            </div>

                            <!-- Passenger Details -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4">Passenger Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Adult</label>
                                        <input type="number" min="0" value="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Child</label>
                                        <input type="number" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Child Age</label>
                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., 5, 8">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Infant</label>
                                        <input type="number" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>

                            <!-- Tour Type & Hotel Category -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Select Tour Type</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="single">Single</option>
                                            <option value="family">Family</option>
                                            <option value="corporate">Corporate Group</option>
                                            <option value="fit">FIT Group</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Select Hotel Category</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="budget">Budget Hotel</option>
                                            <option value="3star">3 Star</option>
                                            <option value="4star">4 Star</option>
                                            <option value="5star">5 Star</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Travel Details -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Travel Date</label>
                                        <input type="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date Flexible</label>
                                        <div class="flex items-center mt-2">
                                            <label class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="tourDateFlexible" name="tourDateFlexible" class="sr-only peer">
                                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
                                            </label>
                                            <span id="tourFlexibleStatus" class="ml-3 font-medium text-gray-600">No</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Total Nights</label>
                                        <input type="number" min="1" value="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Total Tour Budget (Estimate)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-3 text-gray-500">$</span>
                                            <input type="number" min="0" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Lead Form (col-span-2) -->
                <div class="col-span-2 bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <div class="flex-1 overflow-y-auto">
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <form id="leadForm">
                                <!-- Step 1: Is our Client? -->
                                <div class="mb-8">
                                    <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">Client Information</h2>
                                    <div class="flex space-x-6 mb-6">
                                        <div class="flex items-center">
                                            <input type="radio" id="clientYes" name="isClient" value="yes" class="h-5 w-5 text-primary-600 focus:ring-primary-500" checked>
                                            <label for="clientYes" class="ml-3 block text-lg font-medium text-gray-700">
                                                <span class="flex items-center">
                                                    <span class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                                        <i class="fas fa-check text-green-600 text-sm"></i>
                                                    </span>
                                                    Client
                                                </span>
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="clientNo" name="isClient" value="no" class="h-5 w-5 text-primary-600 focus:ring-primary-500">
                                            <label for="clientNo" class="ml-3 block text-lg font-medium text-gray-700">
                                                <span class="flex items-center">
                                                    <span class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                                        <i class="fas fa-times text-red-600 text-sm"></i>
                                                    </span>
                                                    Not a Client
                                                </span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Client Search Dropdown (Visible only for Client) -->
                                    <div id="clientSearchSection" class="mb-6">
                                        <label class="block text-gray-700 mb-2">Search Client</label>
                                        <div class="relative">
                                            <input type="text" id="clientSearch" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Type client name to search...">
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                            <!-- Search Results Dropdown -->
                                            <div id="clientResults" class="hidden absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                                <div class="p-2 hover:bg-gray-100 cursor-pointer">John Doe (john@example.com)</div>
                                                <div class="p-2 hover:bg-gray-100 cursor-pointer">Jane Smith (jane@company.com)</div>
                                                <div class="p-2 hover:bg-gray-100 cursor-pointer">Robert Johnson (robert@test.com)</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Non-Client Form Section (Hidden by default) -->
                                <div id="nonClientSection" class="hidden mb-6">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">New Client Details</h3>

                                    <!-- Name -->
                                    <div class="mb-4">
                                        <label class="block text-gray-700 mb-2">Name</label>
                                        <input type="text" id="newClientName" name="newClientName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter full name">
                                    </div>

                                    <!-- Phone Numbers -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="block text-gray-700">Phone Numbers</label>
                                            <button type="button" id="addPhoneBtn" class="text-primary-600 hover:text-primary-800 text-sm flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add
                                            </button>
                                        </div>

                                        <div id="phoneFieldsContainer">
                                            <div class="phone-field flex gap-2 mb-2">
                                                <div class="flex-1">
                                                    <select name="phoneType[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                                                        <option value="whatsapp">WhatsApp</option>
                                                        <option value="primary">Primary</option>
                                                        <option value="secondary">Secondary</option>
                                                    </select>
                                                </div>
                                                <div class="flex-1">
                                                    <input type="tel" name="phoneNumber[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Phone number">
                                                </div>
                                            </div>
                                            <div class="w-10 flex items-center">
                                                <button type="button" class="remove-phone-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50 hidden">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Email Addresses -->
                                    <div class="mb-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="block text-gray-700">Email Addresses</label>
                                            <button type="button" id="addEmailBtn" class="text-primary-600 hover:text-primary-800 text-sm flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add
                                            </button>
                                        </div>

                                        <div id="emailFieldsContainer">
                                            <div class="email-field flex mb-2">
                                                <div class="flex-1">
                                                    <input type="email" name="email[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Email address">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-10 flex items-center">
                                            <button type="button" class="remove-email-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50 hidden">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Position & Company -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">Position</label>
                                            <input type="text" id="position" name="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Job position">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Company Name</label>
                                            <input type="text" id="companyName" name="companyName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Company name">
                                        </div>
                                    </div>

                                    <!-- Social Media -->
                                    <div class="mb-6">
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="block text-gray-700">Social Media</label>
                                            <button type="button" id="addSocialBtn" class="text-primary-600 hover:text-primary-800 text-sm flex items-center">
                                                <i class="fas fa-plus-circle mr-1"></i> Add
                                            </button>
                                        </div>

                                        <div id="socialFieldsContainer">
                                            <div class="social-field flex gap-2 mb-2">
                                                <div class="flex-1">
                                                    <select name="socialPlatform[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                                                        <option value="facebook">Facebook</option>
                                                        <option value="twitter">Twitter</option>
                                                        <option value="linkedin">LinkedIn</option>
                                                    </select>
                                                </div>
                                                <div class="flex-1">
                                                    <input type="text" name="socialUsername[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Username/URL">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-10 flex items-center">
                                            <button type="button" class="remove-social-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50 hidden">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Conversation Note (Always visible) -->
                                <div class="mb-6">
                                    <label for="conversationNote" class="block text-lg font-medium text-gray-700 mb-3">Conversation Note</label>
                                    <textarea id="conversationNote" name="conversationNote" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter notes from your conversation..."></textarea>
                                </div>

                                <!-- Work Priority (Always visible) -->
                                <div class="mb-8">
                                    <h3 class="text-lg font-medium text-gray-700 mb-4">Work Priority</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="relative">
                                            <input type="radio" id="urgent" name="workPriority" value="urgent" class="hidden peer">
                                            <label for="urgent" class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                                <span class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center mb-2">
                                                    <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                                                </span>
                                                <span class="font-medium text-gray-800 text-sm">Urgent</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="today" name="workPriority" value="today" class="hidden peer">
                                            <label for="today" class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                                <span class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center mb-2">
                                                    <i class="fas fa-calendar-day text-orange-600 text-sm"></i>
                                                </span>
                                                <span class="font-medium text-gray-800 text-sm">Today</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="easy" name="workPriority" value="easy" class="hidden peer" checked>
                                            <label for="easy" class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                                <span class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center mb-2">
                                                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                                                </span>
                                                <span class="font-medium text-gray-800 text-sm">Easy</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="research" name="workPriority" value="research" class="hidden peer">
                                            <label for="research" class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all duration-200">
                                                <span class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                                                    <i class="fas fa-search text-blue-600 text-sm"></i>
                                                </span>
                                                <span class="font-medium text-gray-800 text-sm">Research</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Create Lead Button (Fixed at bottom) -->
                    <div class="mt-4 pt-4 border-t">
                        <button type="button" id="createLeadBtn" class="w-full px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i> Create Lead
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <script src="assets/script.js"></script>
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab Navigation
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');

                    // Remove active class from all buttons
                    tabBtns.forEach(b => {
                        b.classList.remove('active', 'border-primary-600', 'text-primary-600');
                        b.classList.add('border-transparent');
                    });

                    // Add active class to clicked button
                    this.classList.add('active', 'border-primary-600', 'text-primary-600');
                    this.classList.remove('border-transparent');

                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });

                    // Show selected tab content
                    document.getElementById(tabId + 'Form').classList.remove('hidden');
                    document.getElementById(tabId + 'Form').classList.add('active');
                });
            });

            // Client/Non-Client Toggle
            const clientYes = document.getElementById('clientYes');
            const clientNo = document.getElementById('clientNo');
            const clientSearchSection = document.getElementById('clientSearchSection');
            const nonClientSection = document.getElementById('nonClientSection');

            function toggleClientSections() {
                if (clientYes.checked) {
                    clientSearchSection.classList.remove('hidden');
                    nonClientSection.classList.add('hidden');
                } else {
                    clientSearchSection.classList.add('hidden');
                    nonClientSection.classList.remove('hidden');
                }
            }

            clientYes.addEventListener('change', toggleClientSections);
            clientNo.addEventListener('change', toggleClientSections);
            toggleClientSections();

            // Client Search Functionality
            const clientSearch = document.getElementById('clientSearch');
            const clientResults = document.getElementById('clientResults');

            clientSearch.addEventListener('input', function() {
                if (this.value.length > 1) {
                    clientResults.classList.remove('hidden');
                } else {
                    clientResults.classList.add('hidden');
                }
            });

            // Hide search results when clicking outside
            document.addEventListener('click', function(e) {
                if (!clientSearch.contains(e.target) && !clientResults.contains(e.target)) {
                    clientResults.classList.add('hidden');
                }
            });

            // Select client from search results
            clientResults.querySelectorAll('div').forEach(item => {
                item.addEventListener('click', function() {
                    clientSearch.value = this.textContent;
                    clientResults.classList.add('hidden');
                });
            });

            // Hotel Booking: Add Complete Booking Entry
            const addHotelBookingBtn = document.getElementById('addHotelBookingBtn');
            const hotelBookingsContainer = document.getElementById('hotelBookingsContainer');

            addHotelBookingBtn?.addEventListener('click', function() {
                const bookingCount = document.querySelectorAll('.hotel-booking-entry').length + 1;
                const today = new Date().toISOString().split('T')[0];

                const newBookingEntry = document.createElement('div');
                newBookingEntry.className = 'hotel-booking-entry bg-white rounded-xl shadow-sm p-6 mb-6';
                newBookingEntry.innerHTML = `
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Hotel Booking #${bookingCount}</h3>
                        <button type="button" class="remove-hotel-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    
                    <!-- Check-in/Check-out -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Booking Dates</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Check-In</label>
                                <input type="date" name="hotelCheckIn[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" min="${today}">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Check-Out</label>
                                <input type="date" name="hotelCheckOut[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" min="${today}">
                            </div>
                        </div>
                    </div>

                    <!-- PAX, Room, Nights -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Accommodation Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Number of PAX</label>
                                <input type="number" min="1" value="1" name="hotelPax[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Number of Room</label>
                                <input type="number" min="1" value="1" name="hotelRooms[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Number Night Stay</label>
                                <input type="number" min="1" value="1" name="hotelNights[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Destination/Property -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-700 mb-3">Property Details</h4>
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-2">Destination or Property</label>
                            <input type="text" name="hotelDestination[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter destination or property name">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Note</label>
                            <textarea rows="3" name="hotelNote[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add any special requirements or notes..."></textarea>
                        </div>
                    </div>
                `;

                hotelBookingsContainer.appendChild(newBookingEntry);
                updateHotelRemoveButtons();

                // Add remove functionality
                const removeBtn = newBookingEntry.querySelector('.remove-hotel-btn');
                removeBtn.addEventListener('click', function() {
                    newBookingEntry.remove();
                    updateHotelNumbers();
                });

                updateHotelNumbers();
            });

            // Update hotel booking numbers
            function updateHotelNumbers() {
                const hotelEntries = document.querySelectorAll('.hotel-booking-entry');
                hotelEntries.forEach((entry, index) => {
                    const title = entry.querySelector('h3');
                    title.textContent = `Hotel Booking #${index + 1}`;
                });
            }

            // Update hotel remove buttons
            function updateHotelRemoveButtons() {
                const hotelEntries = document.querySelectorAll('.hotel-booking-entry');
                hotelEntries.forEach(entry => {
                    const removeBtn = entry.querySelector('.remove-hotel-btn');
                    removeBtn.style.display = hotelEntries.length > 1 ? 'block' : 'none';
                });
            }

            // Air Ticket: Toggle Switch
            const dateFlexibleToggle = document.getElementById('dateFlexible');
            const flexibleStatus = document.getElementById('flexibleStatus');

            dateFlexibleToggle?.addEventListener('change', function() {
                flexibleStatus.textContent = this.checked ? 'Yes' : 'No';
            });

            // Tour Package: Toggle Switch
            const tourDateFlexibleToggle = document.getElementById('tourDateFlexible');
            const tourFlexibleStatus = document.getElementById('tourFlexibleStatus');

            tourDateFlexibleToggle?.addEventListener('change', function() {
                tourFlexibleStatus.textContent = this.checked ? 'Yes' : 'No';
            });

            // Air Ticket: Add Route
            const addRouteBtn = document.getElementById('addRouteBtn');
            const routeFieldsContainer = document.getElementById('routeFieldsContainer');

            addRouteBtn?.addEventListener('click', function() {
                const newRouteField = document.createElement('div');
                newRouteField.className = 'route-field grid grid-cols-1 md:grid-cols-3 gap-4 mb-4';
                newRouteField.innerHTML = `
                    <div>
                        <label class="block text-gray-700 mb-2">Route</label>
                        <input type="text" name="route[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., DEL-LHR">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Date of Fly</label>
                        <input type="date" name="dateOfFly[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div class="relative">
                        <label class="block text-gray-700 mb-2">Note</label>
                        <div class="flex gap-2">
                            <input type="text" name="routeNote[]" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add note">
                            <button type="button" class="remove-route-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 self-end">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                routeFieldsContainer.appendChild(newRouteField);
                updateRouteRemoveButtons();

                // Set minimum date to today
                const dateInput = newRouteField.querySelector('input[type="date"]');
                dateInput.min = new Date().toISOString().split('T')[0];
            });

            // Update route remove buttons
            function updateRouteRemoveButtons() {
                const routeFields = document.querySelectorAll('.route-field');
                routeFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-route-btn');
                    removeBtn.style.display = routeFields.length > 1 ? 'block' : 'none';

                    removeBtn.addEventListener('click', function() {
                        if (routeFields.length > 1) {
                            field.remove();
                            updateRouteRemoveButtons();
                        }
                    });
                });
            }

            // Non-Client Form: Add Phone
            const addPhoneBtn = document.getElementById('addPhoneBtn');
            const phoneFieldsContainer = document.getElementById('phoneFieldsContainer');

            addPhoneBtn?.addEventListener('click', function() {
                const newPhoneField = document.createElement('div');
                newPhoneField.className = 'phone-field flex gap-2 mb-2';
                newPhoneField.innerHTML = `
                    <div class="flex-1">
                        <select name="phoneType[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <input type="tel" name="phoneNumber[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Phone number">
                    </div>
                    <div class="w-10 flex items-center">
                        <button type="button" class="remove-phone-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                `;

                phoneFieldsContainer.appendChild(newPhoneField);
                updatePhoneRemoveButtons();
            });

            // Non-Client Form: Add Email
            const addEmailBtn = document.getElementById('addEmailBtn');
            const emailFieldsContainer = document.getElementById('emailFieldsContainer');

            addEmailBtn?.addEventListener('click', function() {
                const newEmailField = document.createElement('div');
                newEmailField.className = 'email-field flex mb-2';
                newEmailField.innerHTML = `
                    <div class="flex-1">
                        <input type="email" name="email[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Email address">
                    </div>
                    <div class="w-10 flex items-center">
                        <button type="button" class="remove-email-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                `;

                emailFieldsContainer.appendChild(newEmailField);
                updateEmailRemoveButtons();
            });

            // Non-Client Form: Add Social Media
            const addSocialBtn = document.getElementById('addSocialBtn');
            const socialFieldsContainer = document.getElementById('socialFieldsContainer');

            addSocialBtn?.addEventListener('click', function() {
                const newSocialField = document.createElement('div');
                newSocialField.className = 'social-field flex gap-2 mb-2';
                newSocialField.innerHTML = `
                    <div class="flex-1">
                        <select name="socialPlatform[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                            <option value="facebook">Facebook</option>
                            <option value="twitter">Twitter</option>
                            <option value="linkedin">LinkedIn</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <input type="text" name="socialUsername[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Username/URL">
                    </div>
                    <div class="w-10 flex items-center">
                        <button type="button" class="remove-social-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                `;

                socialFieldsContainer.appendChild(newSocialField);
                updateSocialRemoveButtons();
            });

            // Update remove buttons
            function updatePhoneRemoveButtons() {
                const phoneFields = document.querySelectorAll('.phone-field');
                phoneFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-phone-btn');
                    removeBtn.style.display = phoneFields.length > 1 ? 'block' : 'none';

                    removeBtn.addEventListener('click', function() {
                        if (phoneFields.length > 1) {
                            field.remove();
                            updatePhoneRemoveButtons();
                        }
                    });
                });
            }

            function updateEmailRemoveButtons() {
                const emailFields = document.querySelectorAll('.email-field');
                emailFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-email-btn');
                    removeBtn.style.display = emailFields.length > 1 ? 'block' : 'none';

                    removeBtn.addEventListener('click', function() {
                        if (emailFields.length > 1) {
                            field.remove();
                            updateEmailRemoveButtons();
                        }
                    });
                });
            }

            function updateSocialRemoveButtons() {
                const socialFields = document.querySelectorAll('.social-field');
                socialFields.forEach((field, index) => {
                    const removeBtn = field.querySelector('.remove-social-btn');
                    removeBtn.style.display = socialFields.length > 1 ? 'block' : 'none';

                    removeBtn.addEventListener('click', function() {
                        if (socialFields.length > 1) {
                            field.remove();
                            updateSocialRemoveButtons();
                        }
                    });
                });
            }

            // Initialize remove buttons
            updatePhoneRemoveButtons();
            updateEmailRemoveButtons();
            updateSocialRemoveButtons();
            updateRouteRemoveButtons();
            updateHotelRemoveButtons();

            // Set minimum date for all date inputs to today
            const today = new Date().toISOString().split('T')[0];

            function setMinDates() {
                document.querySelectorAll('input[type="date"]').forEach(dateInput => {
                    dateInput.min = today;
                });
            }
            setMinDates();

            // Create Lead Button
            const createLeadBtn = document.getElementById('createLeadBtn');

            createLeadBtn.addEventListener('click', function() {
                // Get active tab
                const activeTab = document.querySelector('.tab-btn.active').getAttribute('data-tab');
                const activeForm = document.getElementById(activeTab + 'Form');
                const leadForm = document.getElementById('leadForm');

                let isValid = true;
                let errorMessage = '';

                // Validate based on active tab
                if (activeTab === 'hotel') {
                    // Validate hotel bookings
                    const hotelEntries = document.querySelectorAll('.hotel-booking-entry');
                    if (hotelEntries.length === 0) {
                        isValid = false;
                        errorMessage = 'Please add at least one hotel booking';
                    } else {
                        hotelEntries.forEach((entry, index) => {
                            const checkIn = entry.querySelector('input[name="hotelCheckIn[]"]');
                            const checkOut = entry.querySelector('input[name="hotelCheckOut[]"]');
                            const destination = entry.querySelector('input[name="hotelDestination[]"]');

                            if (!checkIn.value || !checkOut.value) {
                                checkIn.classList.add('border-red-500');
                                checkOut.classList.add('border-red-500');
                                isValid = false;
                                errorMessage = `Please fill in dates for Hotel Booking #${index + 1}`;
                            }

                            if (!destination.value.trim()) {
                                destination.classList.add('border-red-500');
                                isValid = false;
                                errorMessage = `Please enter destination for Hotel Booking #${index + 1}`;
                            }
                        });
                    }
                } else {
                    // Validate other forms
                    const requiredSelects = activeForm.querySelectorAll('select:not([multiple])');
                    requiredSelects.forEach(select => {
                        if (!select.value) {
                            select.classList.add('border-red-500');
                            isValid = false;
                            errorMessage = `Please select ${select.previous../elementsibling?.textContent?.toLowerCase() || 'required field'}`;
                        } else {
                            select.classList.remove('border-red-500');
                        }
                    });

                    const requiredInputs = activeForm.querySelectorAll('input[required], input[type="date"]');
                    requiredInputs.forEach(input => {
                        if (!input.value) {
                            input.classList.add('border-red-500');
                            isValid = false;
                            errorMessage = `Please fill in ${input.previous../elementsibling?.textContent?.toLowerCase() || 'required field'}`;
                        } else {
                            input.classList.remove('border-red-500');
                        }
                    });
                }

                // Validate conversation note
                const conversationNote = document.getElementById('conversationNote');
                if (!conversationNote.value.trim()) {
                    conversationNote.classList.add('border-red-500');
                    isValid = false;
                    errorMessage = 'Please enter conversation notes';
                } else {
                    conversationNote.classList.remove('border-red-500');
                }

                // Validate client information
                if (clientYes.checked) {
                    if (!clientSearch.value.trim()) {
                        clientSearch.classList.add('border-red-500');
                        isValid = false;
                        errorMessage = 'Please select a client';
                    } else {
                        clientSearch.classList.remove('border-red-500');
                    }
                } else {
                    const newClientName = document.getElementById('newClientName');
                    if (!newClientName.value.trim()) {
                        newClientName.classList.add('border-red-500');
                        isValid = false;
                        errorMessage = 'Please enter client name';
                    } else {
                        newClientName.classList.remove('border-red-500');
                    }
                }

                if (isValid) {
                    // Prepare data for submission
                    const serviceType = activeTab;
                    const isClient = clientYes.checked;
                    const priority = document.querySelector('input[name="workPriority"]:checked')?.value || 'easy';

                    // Collect all form data
                    const formData = {
                        serviceType: serviceType,
                        isClient: isClient,
                        priority: priority,
                        conversationNote: conversationNote.value,
                        clientInfo: {},
                        serviceData: {}
                    };

                    console.log(formData);


                    if (isClient) {
                        formData.clientInfo.type = 'existing';
                        formData.clientInfo.searchTerm = clientSearch.value;
                    } else {
                        formData.clientInfo.type = 'new';
                        formData.clientInfo.name = document.getElementById('newClientName').value;
                        formData.clientInfo.phones = Array.from(document.querySelectorAll('input[name="phoneNumber[]"]')).map(input => input.value);
                        formData.clientInfo.emails = Array.from(document.querySelectorAll('input[name="email[]"]')).map(input => input.value);
                        formData.clientInfo.position = document.getElementById('position').value;
                        formData.clientInfo.company = document.getElementById('companyName').value;
                    }

                    // Show success message
                    const serviceNames = {
                        'visa': 'VISA Application',
                        'hotel': 'Hotel Booking',
                        'air': 'Air Ticket',
                        'tour': 'Tour Package'
                    };

                    const clientType = isClient ? 'Existing Client' : 'New Client';
                    const hotelCount = activeTab === 'hotel' ? document.querySelectorAll('.hotel-booking-entry').length : 0;
                    const hotelMessage = hotelCount > 0 ? ` (${hotelCount} booking${hotelCount > 1 ? 's' : ''})` : '';

                    alert(`${serviceNames[activeTab]}${hotelMessage} Lead created successfully!\n\nClient: ${clientType}\nPriority: ${priority}`);

                    // In real application, submit to server
                    console.log('Form data to submit:', formData);

                } else {
                    alert(errorMessage || 'Please fill in all required fields.');
                }
            });

            // Remove red border when user starts typing
            const allInputs = document.querySelectorAll('input, textarea, select');
            allInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('border-red-500');
                });
            });
        });
    </script>
</body>

</html>