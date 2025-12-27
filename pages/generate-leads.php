<?php
$ip_port = @file_get_contents('../ippath.txt');
if (empty($ip_port)) {
    $ip_port = "http://103.104.219.3:898";
}

$leadStore = $ip_port . "api/leads/store.php";
$getAllClientsApi = $ip_port . "api/clients/all-clients.php";

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Service Lead Generation</title>
    <link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png" sizes="16x16">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
    <style>
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .remove-btn {
            display: none;
        }

        .has-multiple .remove-btn {
            display: block;
        }

        input:invalid,
        textarea:invalid,
        select:invalid {
            border-color: #f87171;
        }
    </style>
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
                <!-- Left Column: Service Forms (col-span-4) -->
                <div class="col-span-4 bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <!-- Header -->
                    <div class="mb-2">
                        <h1 class="text-3xl font-bold text-gray-800">Generate Multi-Service Lead</h1>
                        <p class="text-gray-600 mt-2">Fill forms for all required services</p>
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
                            <div class="bg-white rounded-xl shadow-sm p-6 pt-4">
                                <h2 class="text-xl font-semibold text-gray-800 mb-2">VISA Details</h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Country *</label>
                                        <select class="visa-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="country">
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
                                        <label class="block text-gray-700 mb-2">Visa Category *</label>
                                        <select class="visa-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="visaCategory">
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
                                        <select class="visa-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="visaSubCategory">
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
                                        <label class="block text-gray-700 mb-2">Date of Travel *</label>
                                        <input type="date" class="visa-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="dateOfTravel">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date of Return *</label>
                                        <input type="date" class="visa-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="dateOfReturn">
                                    </div>
                                </div>
                            </div>

                            <!-- Application Type Section -->
                            <div class="bg-white rounded-xl shadow-sm p-6 pt-4">
                                <h2 class="text-xl font-semibold text-gray-800 mb-2">Application Type</h2>
                                <div class="flex space-x-6 mb-6">
                                    <div class="flex items-center">
                                        <input type="radio" id="singleApp" name="applicationType" value="single" class="visa-field h-5 w-5 text-primary-600 focus:ring-primary-500" data-field="applicationType" checked>
                                        <label for="singleApp" class="ml-2 text-gray-700">Single</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="groupApp" name="applicationType" value="group" class="visa-field h-5 w-5 text-primary-600 focus:ring-primary-500" data-field="applicationType">
                                        <label for="groupApp" class="ml-2 text-gray-700">Group</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="familyApp" name="applicationType" value="family" class="visa-field h-5 w-5 text-primary-600 focus:ring-primary-500" data-field="applicationType">
                                        <label for="familyApp" class="ml-2 text-gray-700">Family</label>
                                    </div>
                                </div>

                                <!-- Cost Bearer -->
                                <div class="mb-6">
                                    <label class="block text-gray-700 mb-3">Cost will bear by</label>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="relative">
                                            <input type="radio" id="self" name="costBearer" value="self" class="visa-field hidden peer" data-field="costBearer" checked>
                                            <label for="self" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                                                <i class="fas fa-user text-2xl text-gray-600 mb-2"></i>
                                                <span class="font-medium">Self</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="organization" name="costBearer" value="organization" class="visa-field hidden peer" data-field="costBearer">
                                            <label for="organization" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-primary-400 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                                                <i class="fas fa-building text-2xl text-gray-600 mb-2"></i>
                                                <span class="font-medium">Organization</span>
                                            </label>
                                        </div>
                                        <div class="relative">
                                            <input type="radio" id="anotherPerson" name="costBearer" value="anotherPerson" class="visa-field hidden peer" data-field="costBearer">
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
                                            <input type="radio" id="invitationNo" name="invitationStatus" value="no" class="visa-field h-5 w-5 text-primary-600 focus:ring-primary-500" data-field="invitationStatus" checked>
                                            <label for="invitationNo" class="ml-2 text-gray-700">No</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="invitationOrg" name="invitationStatus" value="organization" class="visa-field h-5 w-5 text-primary-600 focus:ring-primary-500" data-field="invitationStatus">
                                            <label for="invitationOrg" class="ml-2 text-gray-700">Organization</label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="invitationPerson" name="invitationStatus" value="person" class="visa-field h-5 w-5 text-primary-600 focus:ring-primary-500" data-field="invitationStatus">
                                            <label for="invitationPerson" class="ml-2 text-gray-700">Another Person</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Hotel Booking Form -->
                        <form id="hotelForm" class="tab-content hidden">
                            <!-- Add More Button -->
                            <div class="flex justify-between items-center mb-6">
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
                                                <label class="block text-gray-700 mb-2">Check-In *</label>
                                                <input type="date" name="hotelCheckIn[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="checkIn">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Check-Out *</label>
                                                <input type="date" name="hotelCheckOut[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="checkOut">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PAX, Room, Nights -->
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Accommodation Details</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-gray-700 mb-2">Number of PAX *</label>
                                                <input type="number" min="1" value="1" name="hotelPax[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="pax">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Number of Room *</label>
                                                <input type="number" min="1" value="1" name="hotelRooms[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="rooms">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">Number Night Stay *</label>
                                                <input type="number" min="1" value="1" name="hotelNights[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="nights">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Destination/Property -->
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Property Details</h4>
                                        <div class="mb-4">
                                            <label class="block text-gray-700 mb-2">Destination or Property *</label>
                                            <input type="text" name="hotelDestination[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter destination or property name" data-field="destination">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Note</label>
                                            <textarea rows="3" name="hotelNote[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add any special requirements or notes..." data-field="note"></textarea>
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
                                        <input type="number" min="0" value="1" class="air-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="adult">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Child</label>
                                        <input type="number" min="0" value="0" class="air-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="child">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Infant</label>
                                        <input type="number" min="0" value="0" class="air-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="infant">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">Child Age(s)</label>
                                    <input type="text" class="air-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter child ages separated by comma" data-field="childAge">
                                </div>
                            </div>

                            <!-- Class & Date Flexibility -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Select Class Type</label>
                                        <select class="air-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="classType">
                                            <option value="economy">Economy</option>
                                            <option value="business">Business</option>
                                            <option value="first">First Class</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date Flexible</label>
                                        <div class="flex items-center mt-2">
                                            <label class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="dateFlexible" class="air-field sr-only peer" data-field="dateFlexible">
                                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
                                            </label>
                                            <span id="flexibleStatus" class="ml-3 font-medium text-gray-600">No</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Flight Routes -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-semibold text-gray-800">Flight Routes *</h2>
                                    <button type="button" id="addRouteBtn" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                                        <i class="fas fa-plus-circle mr-2"></i> Add Route
                                    </button>
                                </div>

                                <div id="routeFieldsContainer">
                                    <div class="route-field grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">Route *</label>
                                            <input type="text" name="route[]" class="air-route-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., DEL-LHR" data-field="route">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Date of Fly *</label>
                                            <input type="date" name="dateOfFly[]" class="air-route-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="dateOfFly">
                                        </div>
                                        <div class="relative">
                                            <label class="block text-gray-700 mb-2">Note</label>
                                            <div class="flex gap-2">
                                                <input type="text" name="routeNote[]" class="air-route-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add note" data-field="routeNote">
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
                                <label class="block text-gray-700 mb-2">Where to go (Country List) *</label>
                                <select class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="destination">
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
                                        <input type="number" min="0" value="1" class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="adult">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Child</label>
                                        <input type="number" min="0" value="0" class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="child">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Child Age</label>
                                        <input type="text" class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., 5, 8" data-field="childAge">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Infant</label>
                                        <input type="number" min="0" value="0" class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="infant">
                                    </div>
                                </div>
                            </div>

                            <!-- Tour Type & Hotel Category -->
                            <div class="bg-white rounded-xl shadow-sm p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-700 mb-2">Select Tour Type *</label>
                                        <select class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="tourType">
                                            <option value="single">Single</option>
                                            <option value="family">Family</option>
                                            <option value="corporate">Corporate Group</option>
                                            <option value="fit">FIT Group</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Select Hotel Category *</label>
                                        <select class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="hotelCategory">
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
                                        <label class="block text-gray-700 mb-2">Travel Date *</label>
                                        <input type="date" class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="travelDate">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Date Flexible</label>
                                        <div class="flex items-center mt-2">
                                            <label class="inline-flex relative items-center cursor-pointer">
                                                <input type="checkbox" id="tourDateFlexible" class="tour-field sr-only peer" data-field="dateFlexible">
                                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
                                            </label>
                                            <span id="tourFlexibleStatus" class="ml-3 font-medium text-gray-600">No</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Total Nights *</label>
                                        <input type="number" min="1" value="1" class="tour-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="totalNights">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">Total Tour Budget (Estimate)</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-3 text-gray-500">$</span>
                                            <input type="number" min="0" class="tour-field w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="0" data-field="totalBudget">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Client & Lead Info (col-span-2) -->
                <div class="col-span-2 bg-gray-100 rounded-lg p-4 flex flex-col h-[calc(100vh-8rem)]">
                    <div class="flex-1 overflow-y-auto">
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <!-- Client Information -->
                            <div class="mb-4">
                                <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b">Client Information</h2>
                                <div class="flex space-x-6 mb-2">
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

                                <!-- Client Search Section -->
                                <div id="clientSearchSection" class="relative w-full mb-2">
                                    <div class="flex">
                                        <input
                                            type="text"
                                            id="clientInput"
                                            placeholder="Search for a client..."
                                            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-purple-500 focus:outline-none"
                                            autocomplete="off">
                                        <button
                                            id="dropdownToggle"
                                            class="px-4 py-2 border border-gray-300 border-l-0 rounded-r-lg bg-gray-100 hover:bg-gray-200"
                                            type="button">
                                            ▼
                                        </button>
                                    </div>
                                    <ul id="clientDropdown" class="absolute w-full bg-white border border-gray-300 rounded-lg mt-1 max-h-60 overflow-auto hidden z-50">
                                        <!-- JS will populate options here -->
                                    </ul>
                                </div>

                                <!-- New Client Section -->
                                <div id="nonClientSection" class="hidden">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">New Client Details</h3>

                                    <!-- Name -->
                                    <div class="mb-4">
                                        <label class="block text-gray-700 mb-2">Name *</label>
                                        <input type="text" id="newClientName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter full name">
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
                                            <div class="w-10 flex items-center">
                                                <button type="button" class="remove-email-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50 hidden">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Position & Company -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-gray-700 mb-2">Position</label>
                                            <input type="text" id="position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Job position">
                                        </div>
                                        <div>
                                            <label class="block text-gray-700 mb-2">Company Name</label>
                                            <input type="text" id="companyName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Company name">
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
                                            <div class="w-10 flex items-center">
                                                <button type="button" class="remove-social-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50 hidden">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Conversation Note -->
                            <div class="mb-2">
                                <label for="conversationNote" class="block text-lg font-medium text-gray-700 mb-3">Conversation Note *</label>
                                <textarea id="conversationNote" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter notes from your conversation..."></textarea>
                            </div>

                            <!-- Work Priority -->
                            <div class="mb-2">
                                <h3 class="text-lg font-medium text-gray-700 mb-4">Work Priority *</h3>
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
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 pt-4 border-t flex space-x-3">
                        <button type="button" id="previewDataBtn" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg flex items-center">
                            <i class="fas fa-eye mr-2"></i>Preview
                        </button>
                        <button type="button" id="createLeadBtn" class="flex-1 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-3"></i> Create Lead
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Quick Access Tab -->
    <?php include '../elements/floating-menus.php'; ?>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-800">Data Preview</h3>
                <button type="button" onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <pre id="previewContent" class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto"></pre>
            </div>
            <div class="p-6 border-t flex justify-end space-x-3">
                <button type="button" onclick="closePreview()" class="px-4 py-2 border border-gray-300 rounded-lg">
                    Close
                </button>
                <button type="button" onclick="copyPreviewData()" class="px-4 py-2 bg-blue-600 text-white rounded-lg flex items-center">
                    <i class="fas fa-copy mr-2"></i>Copy JSON
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <!-- Custom JavaScript -->
    <script>
        const API_LEAD_STORE = "<?php echo $leadStore; ?>";
        const API_URL_FOR_ALL_CLIENTS = "<?php echo $getAllClientsApi; ?>";
        // Data Collector Class
        class DataCollector {
            constructor() {
                this.collectedData = {
                    serviceCount: 0,
                    serviceType: [],
                    clientInfo: {},
                    serviceData: {},
                    leadInfo: {}
                };

                // Track if client search is initialized
                this.clientSearchInitialized = false;
                this.clientsData = [];
                this.eventListeners = [];

                this.initialize();
            }

            initialize() {
                this.setupEventListeners();
                this.collectAllData();
            }

            setupEventListeners() {
                // Store references for cleanup
                const collectAllData = () => this.collectAllData();
                const collectAllDataDelayed = () => setTimeout(() => this.collectAllData(), 100);

                document.addEventListener('input', collectAllData);
                document.addEventListener('change', collectAllData);
                document.addEventListener('click', collectAllDataDelayed);

                // Collect on tab change
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    const tabClickHandler = () => {
                        setTimeout(() => this.collectAllData(), 300);
                    };
                    btn.addEventListener('click', tabClickHandler);
                    this.eventListeners.push({
                        element: btn,
                        event: 'click',
                        handler: tabClickHandler
                    });
                });

                // Store for cleanup
                this.eventListeners.push({
                    element: document,
                    event: 'input',
                    handler: collectAllData
                }, {
                    element: document,
                    event: 'change',
                    handler: collectAllData
                }, {
                    element: document,
                    event: 'click',
                    handler: collectAllDataDelayed
                });
            }

            cleanup() {
                // Remove all event listeners
                this.eventListeners.forEach(({
                    element,
                    event,
                    handler
                }) => {
                    element.removeEventListener(event, handler);
                });
                this.eventListeners = [];
            }

            collectAllData() {
                this.collectClientInfo();
                this.collectLeadInfo();
                this.collectServiceData();
                this.updateServiceTypes();
            }

            async initializeClientSearch() {
                try {
                    const response = await fetch(API_URL_FOR_ALL_CLIENTS);
                    const data = await response.json();
                    this.clientsData = data.clients || [];

                    const clientInput = document.getElementById('clientInput');
                    const clientDropdown = document.getElementById('clientDropdown');
                    const dropdownToggle = document.getElementById('dropdownToggle');

                    if (!clientInput || !clientDropdown || !dropdownToggle) return;

                    const renderDropdown = (list) => {
                        clientDropdown.innerHTML = '';
                        list.forEach(client => {
                            const li = document.createElement('li');
                            li.textContent = `${client.id} | ${client.name} | ${client.phone}`;
                            li.className = "px-4 py-2 cursor-pointer hover:bg-purple-100";
                            li.addEventListener('click', () => {
                                clientInput.value = li.textContent;
                                clientDropdown.classList.add('hidden');
                                this.collectAllData();
                            });
                            clientDropdown.appendChild(li);
                        });
                    };

                    // Filter on typing
                    const inputHandler = () => {
                        const value = clientInput.value.toLowerCase();
                        const filtered = this.clientsData.filter(c =>
                            `${c.id} | ${c.name} | ${c.phone}`.toLowerCase().includes(value)
                        );
                        renderDropdown(filtered);
                        clientDropdown.classList.remove('hidden');
                    };

                    // Toggle button click
                    const toggleHandler = () => {
                        if (clientDropdown.classList.contains('hidden')) {
                            renderDropdown(this.clientsData);
                            clientDropdown.classList.remove('hidden');
                        } else {
                            clientDropdown.classList.add('hidden');
                        }
                    };

                    // Hide dropdown on outside click
                    const outsideClickHandler = (e) => {
                        if (!clientInput.contains(e.target) &&
                            !clientDropdown.contains(e.target) &&
                            !dropdownToggle.contains(e.target)) {
                            clientDropdown.classList.add('hidden');
                        }
                    };

                    clientInput.addEventListener('input', inputHandler);
                    dropdownToggle.addEventListener('click', toggleHandler);
                    document.addEventListener('click', outsideClickHandler);

                    // Store for cleanup
                    this.eventListeners.push({
                        element: clientInput,
                        event: 'input',
                        handler: inputHandler
                    }, {
                        element: dropdownToggle,
                        event: 'click',
                        handler: toggleHandler
                    }, {
                        element: document,
                        event: 'click',
                        handler: outsideClickHandler
                    });

                } catch (err) {
                    console.error('Failed to load clients:', err);
                    this.clientsData = [];
                }
            }

            collectClientInfo() {
                const isClient = document.getElementById('clientYes')?.checked;

                if (isClient) {
                    if (!this.clientSearchInitialized) {
                        this.initializeClientSearch();
                        this.clientSearchInitialized = true;
                    }

                    const clientInput = document.getElementById('clientInput');
                    if (clientInput) {
                        const clientId = this.extractClientId(clientInput.value);
                        this.collectedData.clientInfo = {
                            type: 'existing',
                            clientId: clientId,
                            searchValue: clientInput.value,
                            name: this.extractClientName(clientInput.value),
                            phone: this.extractClientPhone(clientInput.value)
                        };
                    }
                } else {
                    // Collect phones
                    const phones = [];
                    const phoneTypes = [];
                    document.querySelectorAll('input[name="phoneNumber[]"]').forEach(input => {
                        if (input.value.trim()) phones.push(input.value);
                    });
                    document.querySelectorAll('select[name="phoneType[]"]').forEach(select => {
                        phoneTypes.push(select.value);
                    });

                    // Collect emails
                    const emails = [];
                    document.querySelectorAll('input[name="email[]"]').forEach(input => {
                        if (input.value.trim()) emails.push(input.value);
                    });

                    // Collect social media
                    const socialMedia = [];
                    const platforms = document.querySelectorAll('select[name="socialPlatform[]"]');
                    const usernames = document.querySelectorAll('input[name="socialUsername[]"]');

                    platforms.forEach((platform, index) => {
                        if (usernames[index]?.value.trim()) {
                            socialMedia.push({
                                platform: platform.value,
                                username: usernames[index].value
                            });
                        }
                    });

                    this.collectedData.clientInfo = {
                        type: 'new',
                        name: document.getElementById('newClientName')?.value || '',
                        phones: phones,
                        phoneTypes: phoneTypes,
                        emails: emails,
                        position: document.getElementById('position')?.value || '',
                        company: document.getElementById('companyName')?.value || '',
                        socialMedia: socialMedia
                    };
                }
            }

            extractClientId(searchValue) {
                const match = searchValue.match(/(\d+)\s*\|\s*/);
                return match ? match[1] : null;
            }

            extractClientName(searchValue) {
                const parts = searchValue.split('|');
                return parts.length > 1 ? parts[1].trim() : '';
            }

            extractClientPhone(searchValue) {
                const parts = searchValue.split('|');
                return parts.length > 2 ? parts[2].trim() : '';
            }

            collectLeadInfo() {
                const priority = document.querySelector('input[name="priority"]:checked')?.value ||
                    document.querySelector('input[name="workPriority"]:checked')?.value ||
                    'easy';

                this.collectedData.leadInfo = {
                    conversationNote: document.getElementById('conversationNote')?.value || '',
                    workPriority: priority
                };
            }

            collectServiceData() {
                // Collect from all forms, not just active tab
                const services = ['visa', 'hotel', 'air', 'tour'];

                services.forEach(service => {
                    const form = document.getElementById(service + 'Form');
                    if (form && !form.classList.contains('hidden')) {
                        switch (service) {
                            case 'visa':
                                this.collectedData.serviceData.visa = this.collectVisaData();
                                break;
                            case 'hotel':
                                this.collectedData.serviceData.hotel = this.collectHotelData();
                                break;
                            case 'air':
                                this.collectedData.serviceData.air = this.collectAirTicketData();
                                break;
                            case 'tour':
                                this.collectedData.serviceData.tour = this.collectTourPackageData();
                                break;
                        }
                    }
                });
            }

            collectVisaData() {
                const form = document.getElementById('visaForm');
                if (!form || form.classList.contains('hidden')) return null;

                const data = {
                    country: form.querySelector('select[data-field="country"]')?.value || '',
                    visaCategory: form.querySelector('select[data-field="visaCategory"]')?.value || '',
                    visaSubCategory: form.querySelector('select[data-field="visaSubCategory"]')?.value || '',
                    dateOfTravel: form.querySelector('input[data-field="dateOfTravel"]')?.value || '',
                    dateOfReturn: form.querySelector('input[data-field="dateOfReturn"]')?.value || '',
                    applicationType: form.querySelector('input[data-field="applicationType"]:checked')?.value || '',
                    costBearer: form.querySelector('input[data-field="costBearer"]:checked')?.value || '',
                    invitationStatus: form.querySelector('input[data-field="invitationStatus"]:checked')?.value || ''
                };

                return this.hasServiceData(data, 'visa') ? data : null;
            }

            collectHotelData() {
                const form = document.getElementById('hotelForm');
                if (!form || form.classList.contains('hidden')) return null;

                const bookings = [];
                document.querySelectorAll('.hotel-booking-entry').forEach((entry, index) => {
                    const checkIn = entry.querySelector('input[data-field="checkIn"]')?.value || '';
                    const checkOut = entry.querySelector('input[data-field="checkOut"]')?.value || '';
                    const destination = entry.querySelector('input[data-field="destination"]')?.value || '';

                    if (checkIn || checkOut || destination) {
                        bookings.push({
                            bookingNumber: index + 1,
                            checkIn: checkIn,
                            checkOut: checkOut,
                            pax: parseInt(entry.querySelector('input[data-field="pax"]')?.value) || 1,
                            rooms: parseInt(entry.querySelector('input[data-field="rooms"]')?.value) || 1,
                            nights: parseInt(entry.querySelector('input[data-field="nights"]')?.value) || 1,
                            destination: destination,
                            note: entry.querySelector('textarea[data-field="note"]')?.value || ''
                        });
                    }
                });

                const data = {
                    totalBookings: bookings.length,
                    bookings: bookings
                };

                return this.hasServiceData(data, 'hotel') ? data : null;
            }

            collectAirTicketData() {
                const form = document.getElementById('airForm');
                if (!form || form.classList.contains('hidden')) return null;

                const routes = [];
                document.querySelectorAll('.route-field').forEach((route, index) => {
                    const routeInput = route.querySelector('input[data-field="route"]');
                    const dateInput = route.querySelector('input[data-field="dateOfFly"]');

                    if (routeInput?.value.trim() || dateInput?.value.trim()) {
                        routes.push({
                            routeNumber: index + 1,
                            route: routeInput?.value || '',
                            dateOfFly: dateInput?.value || '',
                            note: route.querySelector('input[data-field="routeNote"]')?.value || ''
                        });
                    }
                });

                const data = {
                    adult: parseInt(form.querySelector('input[data-field="adult"]')?.value) || 0,
                    child: parseInt(form.querySelector('input[data-field="child"]')?.value) || 0,
                    infant: parseInt(form.querySelector('input[data-field="infant"]')?.value) || 0,
                    childAge: form.querySelector('input[data-field="childAge"]')?.value || '',
                    classType: form.querySelector('select[data-field="classType"]')?.value || '',
                    dateFlexible: document.getElementById('dateFlexible')?.checked || false,
                    routes: routes
                };

                return this.hasServiceData(data, 'air') ? data : null;
            }

            collectTourPackageData() {
                const form = document.getElementById('tourForm');
                if (!form || form.classList.contains('hidden')) return null;

                const data = {
                    destination: form.querySelector('select[data-field="destination"]')?.value || '',
                    adult: parseInt(form.querySelector('input[data-field="adult"]')?.value) || 0,
                    child: parseInt(form.querySelector('input[data-field="child"]')?.value) || 0,
                    childAge: form.querySelector('input[data-field="childAge"]')?.value || '',
                    infant: parseInt(form.querySelector('input[data-field="infant"]')?.value) || 0,
                    tourType: form.querySelector('select[data-field="tourType"]')?.value || '',
                    hotelCategory: form.querySelector('select[data-field="hotelCategory"]')?.value || '',
                    travelDate: form.querySelector('input[data-field="travelDate"]')?.value || '',
                    dateFlexible: document.getElementById('tourDateFlexible')?.checked || false,
                    totalNights: parseInt(form.querySelector('input[data-field="totalNights"]')?.value) || 1,
                    totalBudget: parseInt(form.querySelector('input[data-field="totalBudget"]')?.value) || 0
                };

                return this.hasServiceData(data, 'tour') ? data : null;
            }

            updateServiceTypes() {
                const servicesWithData = [];
                const services = ['visa', 'hotel', 'air', 'tour'];

                services.forEach(service => {
                    const data = this.collectedData.serviceData[service];
                    if (data && this.hasServiceData(data, service)) {
                        servicesWithData.push(service);
                    } else {
                        delete this.collectedData.serviceData[service];
                    }
                });

                this.collectedData.serviceType = servicesWithData;
                this.collectedData.serviceCount = servicesWithData.length;
            }

            hasServiceData(data, serviceType) {
                if (!data) return false;

                switch (serviceType) {
                    case 'visa':
                        return Object.values(data).some(val =>
                            (typeof val === 'string' && val.trim() !== '') ||
                            (typeof val === 'number' && val > 0));
                    case 'hotel':
                        return data.bookings && data.bookings.length > 0;
                    case 'air':
                        return (data.adult > 0 || data.child > 0 || data.infant > 0 ||
                            (data.routes && data.routes.length > 0));
                    case 'tour':
                        return Object.values(data).some(val =>
                            (typeof val === 'string' && val.trim() !== '') ||
                            (typeof val === 'number' && val > 0));
                    default:
                        return false;
                }
            }

            getAllData() {
                this.collectAllData();
                return this.collectedData;
            }
        }

        // Main Application
        let dataCollector;

        document.addEventListener('DOMContentLoaded', function() {
            initializeUI();
            dataCollector = new DataCollector();

            // Preview Button
            document.getElementById('previewDataBtn')?.addEventListener('click', function() {
                if (dataCollector) {
                    const allData = dataCollector.getAllData();
                    showPreview(allData);
                }
            });

            // Create Lead Button
            document.getElementById('createLeadBtn')?.addEventListener('click', async function() {
                if (!validateAllForms()) {
                    alert('Please fill in all required fields (marked with *).');
                    return;
                }

                if (dataCollector) {
                    const allData = dataCollector.getAllData();

                    if (allData.serviceCount === 0) {
                        alert('Please fill in at least one service form.');
                        return;
                    }

                    // Validate at least one service has data
                    if (!validateServiceForms()) {
                        alert('Please fill in required fields in the service forms.');
                        return;
                    }

                    // Show confirmation
                    const serviceNames = {
                        'visa': 'VISA Application',
                        'hotel': 'Hotel Booking',
                        'air': 'Air Ticket',
                        'tour': 'Tour Package'
                    };

                    const servicesList = allData.serviceType.map(s => serviceNames[s]).join(', ');

                    if (confirm(`Create lead with ${allData.serviceCount} service(s): ${servicesList}?`)) {
                        await submitToAPI(allData);
                    }
                }
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', () => {
                if (dataCollector) {
                    dataCollector.cleanup();
                }
            });
        });

        function initializeUI() {
            // Tab Navigation
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');

                    // Update buttons
                    tabBtns.forEach(b => {
                        b.classList.remove('active', 'border-primary-600', 'text-primary-600');
                        b.classList.add('border-transparent');
                    });
                    this.classList.add('active', 'border-primary-600', 'text-primary-600');
                    this.classList.remove('border-transparent');

                    // Update contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });

                    const targetForm = document.getElementById(tabId + 'Form');
                    if (targetForm) {
                        targetForm.classList.remove('hidden');
                        targetForm.classList.add('active');
                    }
                });
            });

            // Client/Non-Client Toggle
            const clientYes = document.getElementById('clientYes');
            const clientNo = document.getElementById('clientNo');
            const clientSearchSection = document.getElementById('clientSearchSection');
            const nonClientSection = document.getElementById('nonClientSection');

            function toggleClientSections() {
                if (clientYes && clientYes.checked) {
                    if (clientSearchSection) clientSearchSection.classList.remove('hidden');
                    if (nonClientSection) nonClientSection.classList.add('hidden');
                } else {
                    if (clientSearchSection) clientSearchSection.classList.add('hidden');
                    if (nonClientSection) nonClientSection.classList.remove('hidden');
                }

                // Reset data collector's client search initialization
                if (dataCollector && !clientYes.checked) {
                    dataCollector.clientSearchInitialized = false;
                }
            }

            if (clientYes && clientNo) {
                clientYes.addEventListener('change', toggleClientSections);
                clientNo.addEventListener('change', toggleClientSections);
                toggleClientSections();
            }

            // Set min date to today
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.min = today;
                if (!input.value) input.value = today;
            });

            // Initialize dynamic fields
            initializeDynamicFields();
        }

        function initializeDynamicFields() {
            // Hotel Booking
            document.getElementById('addHotelBookingBtn')?.addEventListener('click', addHotelBooking);

            // Air Ticket Routes
            document.getElementById('addRouteBtn')?.addEventListener('click', addAirRoute);

            // Client Fields
            document.getElementById('addPhoneBtn')?.addEventListener('click', addPhoneField);
            document.getElementById('addEmailBtn')?.addEventListener('click', addEmailField);
            document.getElementById('addSocialBtn')?.addEventListener('click', addSocialField);

            // Toggle switches
            const dateFlexible = document.getElementById('dateFlexible');
            const flexibleStatus = document.getElementById('flexibleStatus');
            const tourDateFlexible = document.getElementById('tourDateFlexible');
            const tourFlexibleStatus = document.getElementById('tourFlexibleStatus');

            if (dateFlexible && flexibleStatus) {
                dateFlexible.addEventListener('change', function() {
                    flexibleStatus.textContent = this.checked ? 'Yes' : 'No';
                });
            }

            if (tourDateFlexible && tourFlexibleStatus) {
                tourDateFlexible.addEventListener('change', function() {
                    tourFlexibleStatus.textContent = this.checked ? 'Yes' : 'No';
                });
            }
        }

        function addHotelBooking() {
            const container = document.getElementById('hotelBookingsContainer');
            const count = container.querySelectorAll('.hotel-booking-entry').length + 1;
            const today = new Date().toISOString().split('T')[0];

            const entry = document.createElement('div');
            entry.className = 'hotel-booking-entry bg-white rounded-xl shadow-sm p-6 mb-6';
            entry.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Hotel Booking #${count}</h3>
                    <button type="button" class="remove-hotel-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                
                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Booking Dates</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Check-In *</label>
                            <input type="date" name="hotelCheckIn[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="checkIn" value="${today}">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Check-Out *</label>
                            <input type="date" name="hotelCheckOut[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="checkOut" value="${today}">
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Accommodation Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Number of PAX *</label>
                            <input type="number" min="1" value="1" name="hotelPax[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="pax">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Number of Room *</label>
                            <input type="number" min="1" value="1" name="hotelRooms[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="rooms">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Number Night Stay *</label>
                            <input type="number" min="1" value="1" name="hotelNights[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="nights">
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-md font-medium text-gray-700 mb-3">Property Details</h4>
                    <div class="mb-4">
                        <label class="block text-gray-700 mb-2">Destination or Property *</label>
                        <input type="text" name="hotelDestination[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Enter destination or property name" data-field="destination">
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2">Note</label>
                        <textarea rows="3" name="hotelNote[]" class="hotel-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add any special requirements or notes..." data-field="note"></textarea>
                    </div>
                </div>
            `;

            container.appendChild(entry);

            // Add remove functionality
            const removeBtn = entry.querySelector('.remove-hotel-btn');
            removeBtn.addEventListener('click', function() {
                entry.remove();
                updateHotelNumbers();
                updateRemoveButtons('.hotel-booking-entry', '.remove-hotel-btn');
            });

            updateHotelNumbers();
            updateRemoveButtons('.hotel-booking-entry', '.remove-hotel-btn');
        }

        function updateHotelNumbers() {
            document.querySelectorAll('.hotel-booking-entry').forEach((entry, index) => {
                const title = entry.querySelector('h3');
                if (title) title.textContent = `Hotel Booking #${index + 1}`;
            });
        }

        function addAirRoute() {
            const container = document.getElementById('routeFieldsContainer');
            const today = new Date().toISOString().split('T')[0];

            const route = document.createElement('div');
            route.className = 'route-field grid grid-cols-1 md:grid-cols-3 gap-4 mb-4';
            route.innerHTML = `
                <div>
                    <label class="block text-gray-700 mb-2">Route *</label>
                    <input type="text" name="route[]" class="air-route-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="e.g., DEL-LHR" data-field="route">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Date of Fly *</label>
                    <input type="date" name="dateOfFly[]" class="air-route-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" data-field="dateOfFly" value="${today}">
                </div>
                <div class="relative">
                    <label class="block text-gray-700 mb-2">Note</label>
                    <div class="flex gap-2">
                        <input type="text" name="routeNote[]" class="air-route-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Add note" data-field="routeNote">
                        <button type="button" class="remove-route-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 self-end">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(route);

            // Add remove functionality
            const removeBtn = route.querySelector('.remove-route-btn');
            removeBtn.addEventListener('click', function() {
                route.remove();
                updateRemoveButtons('.route-field', '.remove-route-btn');
            });

            updateRemoveButtons('.route-field', '.remove-route-btn');
        }

        function addPhoneField() {
            const container = document.getElementById('phoneFieldsContainer');

            const field = document.createElement('div');
            field.className = 'phone-field flex gap-2 mb-2';
            field.innerHTML = `
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

            container.appendChild(field);

            // Add remove functionality
            const removeBtn = field.querySelector('.remove-phone-btn');
            removeBtn.addEventListener('click', function() {
                field.remove();
                updateRemoveButtons('.phone-field', '.remove-phone-btn');
            });

            updateRemoveButtons('.phone-field', '.remove-phone-btn');
        }

        function addEmailField() {
            const container = document.getElementById('emailFieldsContainer');

            const field = document.createElement('div');
            field.className = 'email-field flex mb-2';
            field.innerHTML = `
                <div class="flex-1">
                    <input type="email" name="email[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm" placeholder="Email address">
                </div>
                <div class="w-10 flex items-center">
                    <button type="button" class="remove-email-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
            `;

            container.appendChild(field);

            // Add remove functionality
            const removeBtn = field.querySelector('.remove-email-btn');
            removeBtn.addEventListener('click', function() {
                field.remove();
                updateRemoveButtons('.email-field', '.remove-email-btn');
            });

            updateRemoveButtons('.email-field', '.remove-email-btn');
        }

        function addSocialField() {
            const container = document.getElementById('socialFieldsContainer');

            const field = document.createElement('div');
            field.className = 'social-field flex gap-2 mb-2';
            field.innerHTML = `
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

            container.appendChild(field);

            // Add remove functionality
            const removeBtn = field.querySelector('.remove-social-btn');
            removeBtn.addEventListener('click', function() {
                field.remove();
                updateRemoveButtons('.social-field', '.remove-social-btn');
            });

            updateRemoveButtons('.social-field', '.remove-social-btn');
        }

        function updateRemoveButtons(containerSelector, buttonSelector) {
            const containers = document.querySelectorAll(containerSelector);
            containers.forEach(container => {
                const removeBtn = container.querySelector(buttonSelector);
                if (removeBtn) {
                    removeBtn.style.display = containers.length > 1 ? 'block' : 'none';
                }
            });
        }

        function validateAllForms() {
            let isValid = true;

            // Validate conversation note
            const conversationNote = document.getElementById('conversationNote');
            if (conversationNote && !conversationNote.value.trim()) {
                conversationNote.classList.add('border-red-500');
                isValid = false;
            } else if (conversationNote) {
                conversationNote.classList.remove('border-red-500');
            }

            // Validate client info
            if (document.getElementById('clientYes')?.checked) {
                const clientInput = document.getElementById('clientInput');
                if (clientInput && !clientInput.value.trim()) {
                    clientInput.classList.add('border-red-500');
                    isValid = false;
                } else if (clientInput) {
                    clientInput.classList.remove('border-red-500');
                }
            } else {
                const newClientName = document.getElementById('newClientName');
                if (newClientName && !newClientName.value.trim()) {
                    newClientName.classList.add('border-red-500');
                    isValid = false;
                } else if (newClientName) {
                    newClientName.classList.remove('border-red-500');
                }
            }

            // Validate work priority - check both possible names
            const priority = document.querySelector('input[name="priority"]:checked') ||
                document.querySelector('input[name="workPriority"]:checked');
            if (!priority) {
                // Highlight priority field
                document.querySelectorAll('input[name="priority"], input[name="workPriority"]').forEach(el => {
                    el.closest('.flex')?.classList.add('border-red-500', 'p-2', 'rounded');
                });
                isValid = false;
            } else {
                document.querySelectorAll('input[name="priority"], input[name="workPriority"]').forEach(el => {
                    el.closest('.flex')?.classList.remove('border-red-500', 'p-2', 'rounded');
                });
            }

            return isValid;
        }

        function validateServiceForms() {
            const activeTab = document.querySelector('.tab-content:not(.hidden)');
            if (!activeTab) return false;

            const service = activeTab.id.replace('Form', '');
            let isValid = true;

            switch (service) {
                case 'visa':
                    isValid = validateVisaForm();
                    break;
                case 'hotel':
                    isValid = validateHotelForm();
                    break;
                case 'air':
                    isValid = validateAirForm();
                    break;
                case 'tour':
                    isValid = validateTourForm();
                    break;
            }

            return isValid;
        }

        function validateVisaForm() {
            const form = document.getElementById('visaForm');
            if (!form || form.classList.contains('hidden')) return false;

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        function validateHotelForm() {
            const form = document.getElementById('hotelForm');
            if (!form || form.classList.contains('hidden')) return false;

            const bookings = form.querySelectorAll('.hotel-booking-entry');
            if (bookings.length === 0) return false;

            let isValid = true;
            bookings.forEach(booking => {
                const requiredFields = booking.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
            });

            return isValid;
        }

        function validateAirForm() {
            const form = document.getElementById('airForm');
            if (!form || form.classList.contains('hidden')) return false;

            let isValid = true;

            // Check at least one passenger
            const adult = parseInt(form.querySelector('input[data-field="adult"]')?.value) || 0;
            const child = parseInt(form.querySelector('input[data-field="child"]')?.value) || 0;
            const infant = parseInt(form.querySelector('input[data-field="infant"]')?.value) || 0;

            if (adult + child + infant === 0) {
                alert('Please enter at least one passenger');
                return false;
            }

            // Check routes
            const routes = form.querySelectorAll('.route-field');
            if (routes.length === 0) {
                alert('Please add at least one route');
                return false;
            }

            routes.forEach(route => {
                const routeInput = route.querySelector('input[data-field="route"]');
                const dateInput = route.querySelector('input[data-field="dateOfFly"]');

                if (!routeInput.value.trim() || !dateInput.value.trim()) {
                    routeInput?.classList.add('border-red-500');
                    dateInput?.classList.add('border-red-500');
                    isValid = false;
                } else {
                    routeInput?.classList.remove('border-red-500');
                    dateInput?.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        function validateTourForm() {
            const form = document.getElementById('tourForm');
            if (!form || form.classList.contains('hidden')) return false;

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        async function submitToAPI(formData) {
            const btn = document.getElementById('createLeadBtn');
            if (!btn) return;

            const originalText = btn.innerHTML;

            // Show loading
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i> Creating Lead...';
            btn.disabled = true;

            try {
                // Prepare final data
                const finalData = {
                    serviceCount: formData.serviceCount,
                    serviceType: formData.serviceType,
                    clientInfo: formData.clientInfo,
                    serviceData: formData.serviceData,
                    leadInfo: formData.leadInfo,
                    metadata: {
                        submittedAt: new Date().toISOString(),
                        source: 'web_dashboard'
                    }
                };

                console.log('Submitting data:', finalData);

                const response = await fetch(API_LEAD_STORE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
                    },
                    body: JSON.stringify(finalData)
                });

                if (response.ok) {
                    const result = await response.json();
                    showSuccess(`Lead created successfully! Lead ID: ${result.leadId || 'N/A'}`);
                    // Optionally reset form
                    resetForm();
                } else {
                    const errorText = await response.text();
                    throw new Error(`Server error: ${response.status} - ${errorText}`);
                }

            } catch (error) {
                console.error('Error:', error);
                showError('Failed to create lead. Please try again. Error: ' + error.message);

                // Save locally if offline
                if (error.message.includes('network') || !navigator.onLine) {
                    saveDataLocally(formData);
                    showOfflineMessage();
                }

            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function showSuccess(message) {
            alert('Success: ' + message);
            // Optional: Show nice toast notification
        }

        function showError(message) {
            alert('Error: ' + message);
            // Optional: Show nice error notification
        }

        function showOfflineMessage() {
            alert('You are offline. Data has been saved locally and will sync when online.');
        }

        function saveDataLocally(data) {
            try {
                const pending = JSON.parse(localStorage.getItem('pendingLeads') || '[]');
                pending.push({
                    data: data,
                    timestamp: new Date().toISOString(),
                    id: Date.now()
                });
                localStorage.setItem('pendingLeads', JSON.stringify(pending));
                console.log('Saved locally:', data);
            } catch (e) {
                console.error('Failed to save locally:', e);
            }
        }

        function showPreview(data) {
            const modal = document.getElementById('previewModal');
            const content = document.getElementById('previewContent');

            if (!modal || !content) return;

            content.textContent = JSON.stringify(data, null, 2);
            modal.classList.remove('hidden');
        }

        function closePreview() {
            const modal = document.getElementById('previewModal');
            if (modal) modal.classList.add('hidden');
        }

        function copyPreviewData() {
            const content = document.getElementById('previewContent');
            if (!content) return;

            navigator.clipboard.writeText(content.textContent).then(() => {
                alert('JSON copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }

        function resetForm() {
            // Reset all form fields
            document.querySelectorAll('form input, form select, form textarea').forEach(field => {
                if (field.type !== 'button' && field.type !== 'submit') {
                    field.value = '';
                }
            });

            // Reset to today's date for date fields
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.value = today;
            });

            // Reset dynamic fields
            document.getElementById('hotelBookingsContainer').innerHTML = '';
            document.getElementById('routeFieldsContainer').innerHTML = '';
            document.getElementById('phoneFieldsContainer').innerHTML = `
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
            <div class="w-10 flex items-center">
                <button type="button" class="remove-phone-btn text-red-600 hover:text-red-800 p-1 rounded-lg hover:bg-red-50" style="display: none;">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </div>
        </div>
    `;

            // Reset tabs to first one
            document.querySelector('.tab-btn').click();

            // Reset data collector
            if (dataCollector) {
                dataCollector.collectedData = {
                    serviceCount: 0,
                    serviceType: [],
                    clientInfo: {},
                    serviceData: {},
                    leadInfo: {}
                };
            }
        }
    </script>
</body>

</html>