
            <!-- ═══════════════════════════════════════════════
                 SECTION: Explore (TravHub sitemap)
            ════════════════════════════════════════════════ -->
            <div id="mpsec-explore" class="mp-section" style="display:none">
                <div class="bg-white rounded-2xl shadow p-5">
                    <h3 class="font-semibold text-gray-800 mb-1 flex items-center gap-2">
                        <i class="fas fa-compass text-blue-500"></i> Explore TravHub
                    </h3>
                    <p class="text-xs text-gray-400 mb-5">Quick access to everything you have permission to use.</p>
                    <?php
                    // Build permission-based sitemap
                    $exploreGroups = [];

                    // Always visible (every logged-in user)
                    $exploreGroups['My Workspace'] = [
                        ['fas fa-home',         'Dashboard',    'index.php'],
                        ['fas fa-user-circle',  'My Profile',   'my-profile.php'],
                        ['fas fa-wallet',       'My PMS',       'my-pms.php'],
                    ];

                    // HR & People
                    $hrItems = [];
                    if (canAccess($pdo,'hrm_employee_view')) {
                        $hrItems[] = ['fas fa-user-tie',        'Employees',        'index-employees.php'];
                        $hrItems[] = ['fas fa-calendar-check',  'Leave Management', 'hrm-leave-management.php'];
                        $hrItems[] = ['fas fa-clock-rotate-left','Attendance',      'hrm-attendance.php'];
                    }
                    if (canAccess($pdo,'eps_view')) {
                        $hrItems[] = ['fas fa-wallet',          'PMS (HR)',         'pms.php'];
                    }
                    if ($hrItems) $exploreGroups['HR & People'] = $hrItems;

                    // Stake Holders
                    $exploreGroups['Stake Holders'] = [
                        ['fas fa-users',    'Clients',   'index-clients.php'],
                        ['fas fa-users',    'Travelers', 'index-travelers.php'],
                        ['fas fa-handshake','Vendors',   'index-vendors.php'],
                    ];

                    // Working Area
                    $exploreGroups['Working Area'] = [
                        ['fas fa-tasks',       'Leads',          'index-leads.php'],
                        ['fas fa-circle-plus', 'New Lead',       'create-leads.php'],
                        ['fas fa-tasks',       'Works',          'index-works.php'],
                        ['fas fa-circle-plus', 'New Work',       'create-work.php'],
                        ['fas fa-check-circle','Completed Entry','completed-work-entry.php'],
                    ];

                    // Finance
                    $finItems = [
                        ['fas fa-bangladeshi-taka-sign','Accounting','accounts.php'],
                        ['fas fa-receipt',              'Invoices',  'index-invoice.php'],
                        ['fas fa-chart-bar',            'Analytics', 'analytics.php'],
                    ];
                    if (canAccess($pdo,'entry_expense'))  $finItems[] = ['fas fa-receipt',        'Record Expense',   'accounts-expense.php'];
                    if (canAccess($pdo,'entry_asset'))    $finItems[] = ['fas fa-desktop',         'Record Asset',     'accounts-asset.php'];
                    if (canAccess($pdo,'loans_view'))     $finItems[] = ['fas fa-hand-holding-dollar','Loans',         'loans-list.php'];
                    if (canAccess($pdo,'gateway_settle')) $finItems[] = ['fas fa-building-columns','Settlements',      'pending-gateway-settlements.php'];
                    $exploreGroups['Finance'] = $finItems;

                    // Reports
                    $rpItems = [];
                    if (canAccess($pdo,'report_profit'))     $rpItems[] = ['fas fa-money-bill-trend-up','Profit','report-profit.php'];
                    if (canAccess($pdo,'report_cashflow'))   $rpItems[] = ['fas fa-water',              'Cashflow','report-cashflow.php'];
                    if (canAccess($pdo,'report_payment'))    $rpItems[] = ['fas fa-square-caret-up',    'Payment','report-payment.php'];
                    if (canAccess($pdo,'report_receive'))    $rpItems[] = ['fas fa-square-caret-down',  'Receive','report-receive.php'];
                    if (canAccess($pdo,'report_sale'))       $rpItems[] = ['fas fa-circle-check',       'Sale','report-sale.php'];
                    if (canAccess($pdo,'report_purchase'))   $rpItems[] = ['fas fa-square-check',       'Purchase','report-purchase.php'];
                    if (canAccess($pdo,'report_payable'))    $rpItems[] = ['fas fa-circle-up',          'A/C Payable','report-ac_payable.php'];
                    if (canAccess($pdo,'report_receivable')) $rpItems[] = ['fas fa-circle-down',        'A/C Receivable','report-ac_receivable.php'];
                    if (canAccess($pdo,'report_expense'))    $rpItems[] = ['fas fa-receipt',            'Expense','report-expense.php'];
                    if (canAccess($pdo,'report_asset'))      $rpItems[] = ['fas fa-desktop',            'Fixed Assets','report-asset.php'];
                    $rpItems[] = ['fas fa-gauge-high',       'KPI Report','report-kpi.php'];
                    $rpItems[] = ['fas fa-diagram-project',  'Work Coverage','report-work-coverage.php'];
                    if ($rpItems) $exploreGroups['Reports'] = $rpItems;

                    // Tools
                    $exploreGroups['Tools'] = [
                        ['fas fa-passport',    'Air Ticket Calc',       'index-at-calculation.php'],
                        ['fas fa-passport',    'Passport Extraction',   'passport-info-extraction.php'],
                        ['fas fa-hotel',       'Hotel Extraction',      'hotel-info-extraction.php'],
                        ['fas fa-compress',    'File Compressor',       'file-compressor.php'],
                        ['fab fa-flickr',      'Social Media Post',     'index-smpost.php'],
                    ];

                    // Quotations
                    $exploreGroups['Quotations'] = [
                        ['fas fa-ticket',  'Air Ticket Quotations', 'index-air-ticket-quotations.php'],
                        ['fas fa-hotel',   'Hotel Quotations',      'index-hotel-quotations.php'],
                    ];

                    // Packages
                    $exploreGroups['Packages'] = [
                        ['fas fa-circle-plus',     'Create Package', 'create-package.php'],
                        ['fas fa-table-list',       'Package List',  'index-packages.php'],
                    ];

                    // Settings
                    $settingsItems = [['fas fa-cog','Settings','settings.php']];
                    if (($_SESSION['user_role'] ?? '') === '0') $settingsItems[] = ['fas fa-user-shield','Permissions','manage-permissions.php'];
                    $exploreGroups['Settings'] = $settingsItems;

                    foreach ($exploreGroups as $group => $items):
                    ?>
                    <div class="mb-5">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2"><?= htmlspecialchars($group) ?></p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                            <?php foreach($items as [$icon,$label,$href]): ?>
                            <a href="<?= htmlspecialchars($href) ?>" class="flex items-center gap-2.5 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition group">
                                <span class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-blue-100 flex items-center justify-center text-gray-400 group-hover:text-blue-600 transition flex-shrink-0">
                                    <i class="<?= $icon ?> text-sm"></i>
                                </span>
                                <span class="text-sm text-gray-600 group-hover:text-blue-700 font-medium leading-tight"><?= htmlspecialchars($label) ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            </div><!-- /mpsec-explore -->
