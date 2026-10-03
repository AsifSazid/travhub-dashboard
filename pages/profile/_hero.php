    <!-- ── Hero Card ──────────────────────────────────────────── -->
    <div class="bg-gradient-to-r from-slate-800 to-blue-900 rounded-2xl p-5 mb-5 flex flex-col sm:flex-row items-center sm:items-start gap-4">
        <div class="mp-photo-ring" id="mpPhotoRing" title="Click to change photo">
            <img id="mpProfileImg"
                 src="<?= $photoUrl ? htmlspecialchars($photoUrl) : $photoFallback ?>"
                 onerror="this.src='<?= $photoFallback ?>'"
                 alt="Profile Photo">
            <div class="mp-photo-overlay"><i class="fas fa-camera text-white text-xl"></i></div>
        </div>
        <input type="file" id="mpPhotoInput" accept="image/jpeg,image/png,image/webp" class="hidden">

        <div class="text-center sm:text-left flex-1">
            <h2 class="text-2xl font-bold text-white"><?= htmlspecialchars($myName) ?></h2>
            <p class="text-blue-200 mt-0.5"><?= mpVal($myDesg ?: ($companyInfo['designation'] ?? '')) ?: '—' ?></p>
            <p class="text-blue-300 text-sm"><?= mpVal($emp['department_name'] ?? '') ?: '—' ?></p>
            <div class="mt-2 flex flex-wrap gap-2 justify-center sm:justify-start">
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $empStatus==='active' ? 'bg-green-400/20 text-green-300' : 'bg-red-400/20 text-red-300' ?>">
                    <span class="w-1.5 h-1.5 rounded-full inline-block mr-1 <?= $empStatus==='active'?'bg-green-400':'bg-red-400' ?>"></span>
                    <?= ucfirst($empStatus) ?>
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/10 text-white/80">
                    ID: <?= htmlspecialchars($myEmpId) ?>
                </span>
            </div>
        </div>

        <div id="mpPhotoMsg" class="hidden text-xs text-blue-200 self-center text-center"></div>

        <!-- Check-in status widget in hero -->
        <div class="ml-auto self-center flex-shrink-0">
            <div id="mpCheckinWidget" class="bg-white/10 backdrop-blur rounded-xl p-3 text-center min-w-[130px]">
                <div class="text-xs text-blue-200 mb-1"><i class="fas fa-clock mr-1"></i>Today</div>
                <div id="mpCheckinStatus" class="text-sm font-semibold text-white">—</div>
                <div id="mpCheckinTime" class="text-xs text-blue-300 mt-0.5"></div>
                <button id="mpSignOutBtn" class="hidden mt-2 px-3 py-1.5 bg-red-500/80 hover:bg-red-500 text-white text-xs font-semibold rounded-lg transition w-full">
                    <i class="fas fa-sign-out-alt mr-1"></i> Sign Out
                </button>
                <button id="mpCheckInManualBtn" class="hidden mt-2 px-3 py-1.5 bg-green-500/80 hover:bg-green-500 text-white text-xs font-semibold rounded-lg transition w-full">
                    <i class="fas fa-fingerprint mr-1"></i> Check In
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-5">
        <?php
        $quickActions = [
            ['icon'=>'fas fa-file-contract',  'label'=>'Appointment Letter', 'id'=>'qaAppoint',  'color'=>'blue'],
            ['icon'=>'fas fa-passport',        'label'=>'NOC Letter',        'id'=>'qaNoc',       'color'=>'purple'],
            ['icon'=>'fas fa-file-invoice-dollar','label'=>'Salary Slip',   'id'=>'qaSalary',    'color'=>'green'],
            ['icon'=>'fas fa-id-card',         'label'=>'ID Card',           'id'=>'qaIdCard',    'color'=>'orange'],
            ['icon'=>'fas fa-share-alt',       'label'=>'Share Profile',     'id'=>'qaShare',     'color'=>'indigo'],
            ['icon'=>'fas fa-business-time',   'label'=>'Visiting Card',     'id'=>'qaVisit',     'color'=>'pink'],
        ];
        $colorMap = [
            'blue'   => 'bg-blue-50 text-blue-600 hover:bg-blue-100 border-blue-100',
            'purple' => 'bg-purple-50 text-purple-600 hover:bg-purple-100 border-purple-100',
            'green'  => 'bg-green-50 text-green-600 hover:bg-green-100 border-green-100',
            'orange' => 'bg-orange-50 text-orange-600 hover:bg-orange-100 border-orange-100',
            'indigo' => 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border-indigo-100',
            'pink'   => 'bg-pink-50 text-pink-600 hover:bg-pink-100 border-pink-100',
        ];
        foreach ($quickActions as $qa):
        $cls = $colorMap[$qa['color']];
        ?>
        <button id="<?= $qa['id'] ?>" class="<?= $cls ?> border rounded-xl p-3 flex flex-col items-center gap-1.5 transition cursor-pointer text-center" title="<?= $qa['label'] ?>">
            <i class="<?= $qa['icon'] ?> text-xl"></i>
            <span class="text-xs font-semibold leading-tight"><?= $qa['label'] ?></span>
        </button>
        <?php endforeach; ?>
    </div>

