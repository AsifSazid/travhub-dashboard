            <!-- ═══════════════════════════════════════════════
                 SECTION: My Info
            ════════════════════════════════════════════════ -->
            <div id="mpsec-myinfo" class="mp-section bg-white rounded-2xl shadow overflow-hidden">

                <!-- Inner tab bar -->
                <div class="border-b border-gray-100 flex overflow-x-auto px-4" id="mpITabBar">
                    <button class="mp-itab-btn mp-active" data-mptab="info">
                        <i class="fas fa-user mr-1.5"></i>Personal
                    </button>
                    <button class="mp-itab-btn" data-mptab="contact">
                        <i class="fas fa-phone mr-1.5"></i>Contact
                    </button>
                    <button class="mp-itab-btn" data-mptab="emergency">
                        <i class="fas fa-heart-pulse mr-1.5"></i>Emergency
                    </button>
                    <button class="mp-itab-btn" data-mptab="security">
                        <i class="fas fa-lock mr-1.5"></i>Security
                    </button>
                </div>

                <div class="p-5 md:p-6">

                    <!-- Personal + Employment -->
                    <div id="mptab-info" class="mp-tab-pane">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Personal</p>
                                <?php
                                $personal = [
                                    'Full Name'     => mpVal($emp['name'] ?? ''),
                                    'Date of Birth' => mpVal($basicInfo['date_of_birth'] ?? ''),
                                    'Blood Group'   => mpVal($basicInfo['blood_group'] ?? ''),
                                    'NID No.'       => mpVal($companyInfo['nid_no'] ?? ''),
                                    'Father'        => mpVal($companyInfo['father_name'] ?? ''),
                                    'Mother'        => mpVal($companyInfo['mother_name'] ?? ''),
                                    'Spouse'        => mpVal($companyInfo['spouse_name'] ?? ''),
                                ];
                                foreach ($personal as $lbl => $val): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $lbl ?></span>
                                    <span class="mp-val <?= $val?'':'empty' ?>"><?= $val?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 md:mt-0">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Employment</p>
                                <?php
                                $repDesg = $companyInfo['reporting_to_designation'] ?? '';
                                $repTo   = trim(($companyInfo['reporting_to_name'] ?? '') . ($repDesg ? ' (' . $repDesg . ')' : ''));
                                $employment = [
                                    'Employee Type' => mpVal(ucfirst($emp['type'] ?? '')),
                                    'Department'    => mpVal($emp['department_name'] ?? ''),
                                    'Designation'   => mpVal($companyInfo['designation'] ?? ''),
                                    'Company Role'  => mpVal($companyInfo['company_role'] ?? ''),
                                    'Date of Join'  => mpVal($companyInfo['date_of_join'] ?? ''),
                                    'Reporting To'  => htmlspecialchars($repTo, ENT_QUOTES, 'UTF-8'),
                                ];
                                foreach ($employment as $lbl => $val): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $lbl ?></span>
                                    <span class="mp-val <?= $val?'':'empty' ?>"><?= $val?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div id="mptab-contact" class="mp-tab-pane" style="display:none">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Phone</p>
                                <?php
                                $phoneRows = [
                                    'Primary'   => mpVal(is_array($phoneData['primary_no']   ?? '') ? implode(', ', $phoneData['primary_no'])   : ($phoneData['primary_no']   ?? '')),
                                    'Secondary' => mpVal(is_array($phoneData['secondary_no'] ?? '') ? implode(', ', $phoneData['secondary_no']) : ($phoneData['secondary_no'] ?? '')),
                                ];
                                foreach ($phoneRows as $l => $v): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $l ?></span>
                                    <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>

                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 mt-6">Email</p>
                                <?php
                                $emailRows = [
                                    'Primary'   => mpVal(is_array($emailData['primary']   ?? '') ? implode(', ', $emailData['primary'])   : ($emailData['primary']   ?? '')),
                                    'Secondary' => mpVal(is_array($emailData['secondary'] ?? '') ? implode(', ', $emailData['secondary']) : ($emailData['secondary'] ?? '')),
                                ];
                                foreach ($emailRows as $l => $v): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $l ?></span>
                                    <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 md:mt-0">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Address</p>
                                <?php
                                $addrRows = [
                                    'Line 1'  => $addressData['address_line_1'] ?? '',
                                    'Line 2'  => $addressData['address_line_2'] ?? '',
                                    'City'    => $addressData['city']           ?? '',
                                    'State'   => $addressData['state']          ?? '',
                                    'Zip'     => $addressData['zip_code']       ?? '',
                                    'Country' => $addressData['country']        ?? '',
                                ];
                                $addrRows = array_map(fn($v) => mpVal(is_array($v) ? implode(', ', $v) : $v), $addrRows);
                                foreach ($addrRows as $l => $v): ?>
                                <div class="mp-info-row">
                                    <span class="mp-lbl"><?= $l ?></span>
                                    <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency -->
                    <div id="mptab-emergency" class="mp-tab-pane" style="display:none">
                        <div class="max-w-md">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Emergency Contact</p>
                            <?php
                            $ecRows = [
                                'Contact Person' => mpVal($emergencyData['person']   ?? ''),
                                'Relation'       => mpVal($emergencyData['relation'] ?? ''),
                                'Phone'          => mpVal(is_array($emergencyData['phone']   ?? '') ? implode(', ', $emergencyData['phone'])   : ($emergencyData['phone']   ?? '')),
                                'Address'        => mpVal(is_array($emergencyData['address'] ?? '') ? implode(', ', $emergencyData['address']) : ($emergencyData['address'] ?? '')),
                            ];
                            foreach ($ecRows as $l => $v): ?>
                            <div class="mp-info-row">
                                <span class="mp-lbl"><?= $l ?></span>
                                <span class="mp-val <?= $v?'':'empty' ?>"><?= $v?:'Not set' ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-6 flex items-center gap-1.5">
                            <i class="fas fa-info-circle"></i> To update emergency contact, please contact HR.
                        </p>
                    </div>

                    <!-- Security / Password -->
                    <div id="mptab-security" class="mp-tab-pane" style="display:none">
                        <div class="max-w-md">
                            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                <i class="fas fa-key text-blue-500"></i> Change Password
                            </h3>
                            <div id="mpPwAlert" class="hidden mb-4 p-3 rounded-lg text-sm font-medium"></div>
                            <div class="space-y-4">
                                <div>
                                    <label class="mp-lbl block mb-1">Current Password</label>
                                    <div class="relative">
                                        <input type="password" id="mpCurPw"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10"
                                            placeholder="Enter current password">
                                        <button type="button" class="mp-eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-mpfield="mpCurPw">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mp-lbl block mb-1">New Password</label>
                                    <div class="relative">
                                        <input type="password" id="mpNewPw"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10"
                                            placeholder="Minimum 6 characters">
                                        <button type="button" class="mp-eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-mpfield="mpNewPw">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                    <div class="mt-1.5 bg-gray-100 rounded-full h-1 overflow-hidden">
                                        <div id="mpStrBar" class="mp-pw-bar h-full w-0 bg-gray-300"></div>
                                    </div>
                                    <p id="mpStrLbl" class="text-xs text-gray-400 mt-0.5"></p>
                                </div>
                                <div>
                                    <label class="mp-lbl block mb-1">Confirm New Password</label>
                                    <div class="relative">
                                        <input type="password" id="mpConPw"
                                            class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 pr-10"
                                            placeholder="Re-enter new password">
                                        <button type="button" class="mp-eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" data-mpfield="mpConPw">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <button id="mpPwBtn"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg px-4 py-2.5 text-sm transition flex items-center justify-center gap-2">
                                    <i class="fas fa-lock-open"></i> <span>Update Password</span>
                                </button>
                            </div>
                            <div class="mt-8 p-4 rounded-xl border <?= $empStatus==='active'?'border-green-200 bg-green-50':'border-red-200 bg-red-50' ?>">
                                <p class="text-sm font-semibold <?= $empStatus==='active'?'text-green-700':'text-red-700' ?> mb-1 flex items-center gap-2">
                                    <i class="fas fa-circle-dot"></i> Account Status: <?= ucfirst($empStatus) ?>
                                </p>
                                <p class="text-xs <?= $empStatus==='active'?'text-green-600':'text-red-600' ?>">
                                    <?php if ($empStatus==='active'): ?>Your account is active and in good standing.
                                    <?php else: ?>Your account is inactive. Please contact HR.<?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div><!-- /myinfo -->


