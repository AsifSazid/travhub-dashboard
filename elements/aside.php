<?php
// elements/aside.php
// Get current page file name
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside id="sidebar" class="fixed top-16 left-0 h-[calc(100vh-4rem)] bg-slate-800 text-white z-20 w-64 flex flex-col transition-all duration-300">
    <nav class="flex-1 overflow-y-auto p-4 ">
        <ul class="space-y-2">

            <li>
                <a href="index.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'index.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-home mr-3"></i>
                    <span class="sidebar-text transition-all duration-300">Dashboard</span>
                </a>
            </li>

            <li>
                <a href="my-pms.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'my-pms.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fa-solid fa-wallet mr-3"></i>
                    <span class="sidebar-text transition-all duration-300">My PMS</span>
                </a>
            </li>

            <!-- Stake Holder Accordion -->
            <div class="accordion-item mt-4" data-accordion="stake-holder">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Stake Holders</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-clients.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-clients.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Clients</span>
                    </a>
                    <a href="index-travelers.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-travelers.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Travelers</span>
                    </a>
                    <a href="index-vendors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-vendors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-users mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Vendors</span>
                    </a>
                </div>
            </div>
            
            <!-- Working Area Accordion -->
            <div class="accordion-item mt-4" data-accordion="work-areas">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-briefcase mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Working Area</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-leads.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-tasks mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Lead List</span>
                    </a>
                    <a href="create-leads.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'create-leads.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-circle-plus mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Generate Lead</span>
                    </a>
                    <a href="index-works.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-works.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-tasks mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Work List</span>
                    </a>
                    <a href="create-work.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'create-work.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-circle-plus mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Generate Work</span>
                    </a>
                    <a href="completed-work-entry.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'completed-work-entry.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-tasks mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Completed Work Entry</span>
                    </a>
                </div>
            </div>
            
            <!-- Finance Accordion -->
            <div class="accordion-item mt-4" data-accordion="finance">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-coins mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Finance</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="accounts.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'accounts.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-bangladeshi-taka-sign mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Accounting</span>
                    </a>
                    <a href="index-invoice.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-invoice.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-receipt mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Invoice Lists</span>
                    </a>
                    <a href="analytics.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'analytics.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-chart-bar mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Analytics</span>
                    </a>
                </div>
            </div>

            <!-- Report Accordion -->
            <div class="accordion-item mt-4" data-accordion="report">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fa-solid fa-chart-simple mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Reports</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="report-profit.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-profit.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-money-bill-trend-up mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Profit</span>
                    </a>
                    <a href="report-cashflow.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-cashflow.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-water mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">CashFlow</span>
                    </a>
                    <a href="report-payment.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-payment.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-square-caret-up mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Payment</span>
                    </a>
                    <a href="report-receive.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-receive.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-square-caret-down mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Receive</span>
                    </a>
                    <a href="report-sale.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-sale.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-circle-check mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Sale</span>
                    </a>
                    <a href="report-purchase.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-purchase.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-square-check mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Purchase</span>
                    </a>
                    <a href="report-ac_payable.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-ac_payable.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-circle-up mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">A/C Payable</span>
                    </a>
                    <a href="report-ac_receivable.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'report-ac_receivable.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-circle-down mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">A/C Recievable</span>
                    </a>
                </div>
            </div>
            
            <!-- HR Accordion -->
            <div class="accordion-item mt-4" data-accordion="hr">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-user-tie mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Human Resource (HR)</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-employees.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-employees.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-user-tie mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Employees</span>
                    </a>
                    <?php if ($_SESSION['role'] == '0') { ?>
                        <a href="pms.php"
                           class="flex items-center p-3 rounded-lg 
                           <?= $currentPage == 'pms.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                            <i class="fa-solid fa-wallet mr-3"></i>
                            <span class="sidebar-text transition-all duration-300">PMS</span>
                        </a>
                    <?php } ?>
                </div>
            </div>
            
            <!-- Quotation Accordion -->
            <div class="accordion-item mt-4" data-accordion="quotation">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-5 h-5 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" id="Layer_1" data-name="quotations" viewBox="0 0 430.65 487.86" class="w-full h-full" fill="currentColor">
                                <circle cx="256.16" cy="348.29" r="5.64" />
                                <path d="M330.9,359.49H309.66a5,5,0,0,1-5-5v-1.41a5,5,0,0,1,5-5H330.9a5,5,0,0,1,5,5v1.41A5,5,0,0,1,330.9,359.49Z" transform="translate(-35.39 -5.43)" />
                                <path d="M195,74.33H131.57a5,5,0,0,1-5-5V67.87a5,5,0,0,1,5-5H195a5,5,0,0,1,5,5v1.42A5,5,0,0,1,195,74.33Z" transform="translate(-35.39 -5.43)" />
                                <path d="M216.68,94.85H131.56a5,5,0,0,1-5-5V88.4a5,5,0,0,1,5-5h85.12a5,5,0,0,1,5,5v1.42A5,5,0,0,1,216.68,94.85Z" transform="translate(-35.39 -5.43)" />
                                <path d="M268.94,331H139.4c-7.35,0-8.65-1.3-8.65-8.58q0-80.81,0-161.61c0-7,1.71-8.68,8.73-8.68H399c7.19,0,9,1.85,9,9q0,80.57,0,161.13c0,7.11-1.58,8.71-8.64,8.72Q334.2,331,268.94,331Zm-48.18-11.92H396.21V197.34H220.76Zm.08-155.16v21.35H396.25V163.89Zm-78.5,155.3h28.45V197.53H142.34Zm40.75-122.07v122h25.74c.07-1.54.18-2.78.18-4q0-56.64,0-113.28c0-.8.25-1.75-.09-2.35-.54-.92-1.48-2.27-2.27-2.29C198.89,197.05,191.13,197.12,183.09,197.12Zm-12.25-11.73V164H142.47v21.4Zm38.15-.16c0-6.49.08-12.81-.1-19.13a3.24,3.24,0,0,0-2.16-2.3q-10.72-.28-21.47,0c-.85,0-2.37,1.34-2.39,2.1-.19,6.46-.11,12.94-.11,19.77,8.09,0,15.55,0,23,0C206.67,185.61,207.59,185.4,209,185.23Z" transform="translate(-35.39 -5.43)" />
                                <path d="M438.05,471.67c6.39,1.53,11.52,3,16.75,3.93,4.22.73,6.69,2.62,7,7,.23,3.38-1.4,5.24-4.54,6.33-16,5.54-31.64,6.79-46.93-2.29-1.52-.9-4-.76-5.9-.39-40.68,7.94-81.68-21.46-87.21-62.64-5.82-43.34,23.27-80.77,66.76-85.89,39.73-4.68,78.22,26.83,81.71,66.67,2.26,25.75-6.23,47.17-25.18,64.58C440.06,469.41,439.68,469.89,438.05,471.67ZM432.59,483c.27-.62.55-1.24.82-1.85-1.37-1-2.73-2-4.1-3-8.14-5.93-8.13-8.59-.32-14.65,19.2-14.9,27.85-34.74,24.7-58.64-4.64-35.17-35.35-55.81-65.94-54.13-36,2-64.29,36.94-57.84,72.57,6.54,36.13,40.19,58.73,76.06,50.85,3.66-.8,6.18.12,9.31,1.73C420.81,478.73,426.8,480.7,432.59,483Z" transform="translate(-35.39 -5.43)" />
                                <path d="M238.59,90.18c-2,1.29-3.88,3.4-6,3.67a4.18,4.18,0,0,1-4.76-5.07c.41-1.86,3.09-4.38,4.85-4.47,1.92-.09,3.95,2.3,5.94,3.61Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M417.78,95.81H351V26.23Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M220.76,319.05V197.34H396.21V319.05Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M220.84,163.89H396.25v21.35H220.84Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M142.34,319.19V197.53h28.45V319.19Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M183.09,197.12c8,0,15.8-.07,23.55.1.79,0,1.73,1.36,2.27,2.29.35.6.09,1.56.09,2.35V315.14c0,1.24-.11,2.48-.18,4H183.08C183.09,278.64,183.09,238.13,183.09,197.12Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M170.84,185.39H142.47V164h28.37Z" transform="translate(-35.39 -5.43)" />
                                <path fill="#fff" d="M209,185.23c-1.41.17-2.32.38-3.23.39-7.46,0-14.91,0-23,0,0-6.84-.08-13.31.11-19.77,0-.76,1.54-2.08,2.39-2.1q10.73-.24,21.47,0a3.24,3.24,0,0,1,2.16,2.3C209.07,172.41,209,178.74,209,185.23Z" transform="translate(-35.39 -5.43)" />
                                <circle cx="197.78" cy="83.68" r="5.64" />
                                <path fill="none" d="M351,95.81h66.77L351,26.23Z" transform="translate(-35.39 -5.43)" />
                                <path d="M393.17,462.88h-1.4a5,5,0,0,1-5-5V445.32h11.38v12.57A5,5,0,0,1,393.17,462.88Z" transform="translate(-35.39 -5.43)" />
                                <path d="M395.25,410.23c-8.44-.79-13.31-6.88-11-13.67,1.42-4.18,6.28-7.37,10.87-7.12,5.74.31,9.53,4,10.67,10.28.85,4.73,3.22,7.13,6.61,6.35,4.29-1,5.19-4.12,4.75-8.06-.83-7.52-4.47-13.24-11.1-16.78-2.74-1.46-4.72-2.64-4.92-6v-.68c0-.06,0-.11,0-.17v0a1.61,1.61,0,0,0,0-.37,5.67,5.67,0,0,0-5.63-5.12h0a5.69,5.69,0,0,0-5.69,5.69v1.69l-1,1c-4.45,3.84-10,6.94-13.16,11.65-9.25,13.86.61,30.75,18.56,32.8,6.38.73,10.35,4.72,10.29,10.35a10.17,10.17,0,0,1-9.68,9.88c-6.5.48-11.36-3.21-12.3-9.35-.85-5.49-3-7.66-6.9-7-3.59.65-5.36,4.22-4.41,9.2a21.74,21.74,0,0,0,12.52,16.4c2.16,1,3.7,1.85,3.41,4.76-.35,3.51,1.51,5.8,5.21,6s5.8-2.07,6-5.42c.15-3,1.62-3.87,4.07-4.86,11.23-4.55,16.72-16.58,12.32-27.4C411.12,415.82,404.51,411.1,395.25,410.23Z" transform="translate(-35.39 -5.43)" />
                                <path fill="none" d="M351.31,95.63h66.77L351.31,26.05Z" transform="translate(-35.39 -5.43)" />
                                <path fill="none" d="M302.79,466.63Z" transform="translate(-35.39 -5.43)" />
                                <path d="M302.79,466.63Z" transform="translate(-35.39 -5.43)" />
                                <path d="M437.34,327.35q0-48.8,0-97.58c0-41.62.06-83.24-.09-124.85,0-2.8-.68-6.38-2.45-8.27Q392.92,52.32,350.56,8.47A10.23,10.23,0,0,0,344,5.55q-117.42-.22-234.83-.11c-5.23,0-7,1.88-7.25,7.23-.08,2.07,0,4.15,0,6.22v368.9c-2,.11-3.39.26-4.79.26-18.17,0-36.35,0-54.52,0-5.44,0-7.3,1.8-7.14,7.13A91.63,91.63,0,0,0,36.47,408c5.29,31.22,30.49,59.62,67.71,59,63.56-1.09,127.15-.35,190.73-.32l7.89-.06h.67a5.7,5.7,0,0,0,5.7-5.7h0a5.7,5.7,0,0,0-5.7-5.7h-2.7c-.81,0-1.62,0-2.42,0q-97.8,0-195.61,0a56.94,56.94,0,0,1-10.94-.62c-24.78-4.9-43.27-27.61-44.61-55H268.92c.11,0,.21,0,.32,0h33.92a5.7,5.7,0,0,0,0-11.4h-.57l-.32,0-4.26-.09H113.67V17.26h226c0,27.64,0,54.89,0,82.15,0,6.15,1.77,7.94,8,8,14.35,0,28.7,0,43,0h35.19v6.75q0,106.2,0,212.39c0,2.05-.35,4.56.66,6,1.23,1.78,3.84,3.8,5.67,3.63s3.66-2.73,4.94-4.61C437.81,330.58,437.34,328.79,437.34,327.35Zm-86-231.72V26.05l66.77,69.58Z" transform="translate(-35.39 -5.43)" />
                            </svg>
                        </div>
                        <span class="font-medium sidebar-text transition-all duration-300">Quotations</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>
            
                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-air-ticket-quotations.php" class="flex items-center p-3 rounded-lg <?= $currentPage == 'index-air-ticket-quotations.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-ticket mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Air Ticket Quotations</span>
                    </a>
                    <a href="index-hotel-quotations.php" class="flex items-center p-3 rounded-lg <?= $currentPage == 'index-hotel-quotations.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hotel mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Hotel Booking Quotations</span>
                    </a>
                </div>
            </div>
            
            <!-- Tools Accordion -->
            <div class="accordion-item mt-4" data-accordion="tools">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-gears mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Tools</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-at-calculation.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-at-calculation.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-passport mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Air Ticket Price Calculation</span>
                    </a>
                    <a href="passport-info-extraction.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'passport-info-extraction.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-passport mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Passport Info Extraction</span>
                    </a>
                    <a href="hotel-info-extraction.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'hotel-info-extraction.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hotel mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Hotel Info Extraction</span>
                    </a>
                    <a href="file-compressor.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'file-compressor.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hotel mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">File Compressor</span>
                    </a>
                    <a href="index-smpost.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-smpost.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-brands fa-flickr mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Social Media Post</span>
                    </a>
                </div>
            </div>
            
            <!-- Managements Accordion -->
            <div class="accordion-item mt-4" data-accordion="managements">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fa-solid fa-users-gear mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Managements</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="index-directors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-directors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-chess-knight mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Directors</span>
                    </a>
                    <a href="directors-dashboard.php"
                       class="flex items-center p-3 rounded-lg <?= $currentPage == 'index-directors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>"
                       target="_blank">
                        <i class="fa-solid fa-chess-knight mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Directors Dashboard</span>
                    </a>
                    <a href="index-investors.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'index-investors.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hand-holding-dollar mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Investors</span>
                    </a>
                </div>
            </div>
            
            <!-- Masterdata Accordion -->
            <div class="accordion-item mt-4" data-accordion="masterdata">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-database mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Master Data</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>
                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="masterdata-visa.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-visa.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-passport mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Visa</span>
                    </a>
                    <a href="masterdata-services.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-services.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-brands fa-whmcs mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Services</span>
                    </a>
                    <a href="masterdata-countries.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-countries.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-globe mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Countries</span>
                    </a>
                    <a href="masterdata-activities.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-activities.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-person-hiking mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Activities</span>
                    </a>
                    <a href="masterdata-components.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-components.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-puzzle-piece mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Components</span>
                    </a>
                    <a href="masterdata-currencies.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-currencies.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-money-bills mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Currencies</span>
                    </a>
                    <a href="masterdata-hotels.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-hotels.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-hotel mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Hotels</span>
                    </a>
                    <a href="masterdata-transport.php"
                        class="flex items-center p-3 rounded-lg
                       <?= $currentPage == 'masterdata-transport.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-route mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Transports</span>
                    </a>
                </div>
            </div>

            <!-- Packages Accordion -->
            <div class="accordion-item mt-4" data-accordion="packages">
                <button type="button" class="accordion-toggle flex items-center justify-between w-full p-3 text-left rounded-lg text-gray-300 hover:bg-slate-700 transition">
                    <div class="flex items-center">
                        <i class="fa-solid fa-suitcase-rolling mr-3"></i>
                        <span class="font-medium sidebar-text transition-all duration-300">Packages</span>
                    </div>
                    <i class="fas fa-chevron-down accordion-arrow transition-transform duration-200 sidebar-text"></i>
                </button>

                <div class="accordion-content ml-7 mt-1 space-y-1 hidden transition-all duration-300">
                    <a href="create-package.php"
                        class="flex items-center p-3 rounded-lg 
                       <?= $currentPage == 'create-package.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fas fa-circle-plus mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Create</span>
                    </a>
                    <a href="index-packages.php"
                       class="flex items-center p-3 rounded-lg <?= $currentPage == 'index-packages.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                        <i class="fa-solid fa-table-list mr-3"></i>
                        <span class="sidebar-text transition-all duration-300">Lists</span>
                    </a>
                </div>
            </div>
            
            <li>
                <a href="settings.php"
                    class="flex items-center p-3 rounded-lg 
                   <?= $currentPage == 'settings.php' ? 'bg-slate-700 text-white' : 'text-gray-300 hover:bg-slate-700' ?>">
                    <i class="fas fa-cog mr-3"></i>
                    <span class="sidebar-text transition-all duration-300">Settings</span>
                </a>
            </li>

        </ul>
    </nav>
    
    <!-- User info section at bottom -->
    <div class="border-t border-slate-700 p-4 user-info-section">
        <div class="flex items-center justify-between">
            <div class="flex items-center min-w-0">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0">
                    <?php echo $initialName ?>
                </div>
                <div class="ml-3 overflow-hidden user-info-text">
                    <p class="text-white font-medium truncate"><?php echo $_SESSION['user_name'] ?></p>
                    <p class="text-gray-400 text-sm truncate"><?php echo $_SESSION['designation'] ?></p>
                </div>
            </div>
            <a href="../auth/logout.php"
                class="flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-700 transition-colors flex-shrink-0 logout-icon" title="logout">
                <i class="fa-solid fa-arrow-right-from-bracket text-white text-xl"></i>
            </a>
        </div>
    </div>
</aside>

<style>
    /* Accordion Styles */
    .accordion-item {
        position: relative;
    }

    .accordion-toggle {
        cursor: pointer;
        outline: none;
        user-select: none;
        border: none;
        background: none;
        width: 100%;
    }

    .accordion-arrow {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.875rem;
    }

    .accordion-content {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .accordion-content a {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-radius: 0.5rem;
        color: rgb(209 213 219);
        text-decoration: none;
        transition: all 0.2s;
    }

    .accordion-content a:hover {
        background-color: rgb(51 65 85);
        color: white;
    }

    .accordion-content a.active {
        background-color: rgb(51 65 85);
        color: white;
        font-weight: 500;
    }

    /* Sidebar collapsed styles */
    .sidebar-collapsed {
        width: 4rem !important; /* 64px */
    }

    .sidebar-collapsed .sidebar-text {
        opacity: 0;
        width: 0;
        margin-left: 0;
        overflow: hidden;
        white-space: nowrap;
    }

    .sidebar-collapsed .accordion-toggle {
        justify-content: center;
        padding: 0.75rem;
    }

    .sidebar-collapsed .accordion-toggle i:first-child {
        margin-right: 0 !important;
    }

    .sidebar-collapsed .accordion-arrow {
        display: none;
    }

    .sidebar-collapsed .accordion-content {
        display: none !important;
    }

    /* Hide user info text when collapsed */
    .sidebar-collapsed .user-info-text {
        opacity: 0;
        width: 0;
        overflow: hidden;
    }

    /* Center logout icon when collapsed */
    .sidebar-collapsed .user-info-section {
        padding: 1rem 0.5rem;
    }

    .sidebar-collapsed .user-info-section .flex {
        justify-content: center;
    }

    .sidebar-collapsed .user-info-section .logout-icon {
        margin-left: 0;
    }

    /* Hide user image when collapsed? (optional - remove if you want to keep it) */
    .sidebar-collapsed .user-info-section .w-10.h-10 {
        margin-right: 0;
    }

    /* Transition for smooth animation */
    #sidebar,
    .sidebar-text,
    .user-info-text,
    .accordion-toggle i,
    .user-info-section {
        transition: all 0.3s ease;
    }

    /* Remove margin from icons when collapsed */
    .sidebar-collapsed a i,
    .sidebar-collapsed .accordion-toggle i {
        margin-right: 0 !important;
    }

    /* Center icons in regular links when collapsed */
    .sidebar-collapsed a {
        justify-content: center;
        padding: 0.75rem;
    }

    .sidebar-collapsed a i {
        margin-right: 0;
    }
</style>