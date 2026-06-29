<?php
// FILE PATH: /pages/create-smpost.php
include_once('./authenticate.php');
$ip_port     = @file_get_contents('../ippath.txt') ?: 'http://103.104.219.3:898';
$socialApi   = $ip_port . 'api/ai/social-media.php';
$smpostApi   = $ip_port . 'api/social/endpoints.php';

// Edit mode
$editSysId   = $_GET['id'] ?? '';
$isEdit      = !empty($editSysId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isEdit ? "Edit Post" : "Create Post"; ?> – TravHub Social</title>
<link rel="icon" type="image/png" href="../assets/images/logo/round-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
:root {
    --accent:    #6c47d9;
    --accent-lt: #ede9fe;
    --border:    #e8e4f3;
    --surface:   #f8f7ff;
    --muted:     #8b85a0;
    --text:      #1a1630;
}
body { background: var(--surface); color: var(--text); }

/* Platform */
.plat-btn { display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 14px;border-radius:12px;border:2px solid var(--border);background:#fff;cursor:pointer;transition:all .15s;font-size:.68rem;font-weight:700;color:var(--muted);white-space:nowrap; }
.plat-btn i { font-size:1.2rem; }
.plat-btn:hover,.plat-btn.active { border-color:var(--accent);background:var(--accent-lt);color:var(--accent); }
.plat-btn.fb.active  { border-color:#1877f2;background:#e8f0fe;color:#1877f2; }
.plat-btn.ig.active  { border-color:#e1306c;background:#fce4ec;color:#e1306c; }
.plat-btn.li.active  { border-color:#0a66c2;background:#e3f0fb;color:#0a66c2; }
.plat-btn.tw.active  { border-color:#111;background:#f2f2f2;color:#111; }
.plat-btn.tt.active  { border-color:#fe2c55;background:#fde8ec;color:#fe2c55; }

/* Tone / Lang chips */
.chip-btn { padding:5px 13px;border-radius:999px;font-size:.73rem;font-weight:600;border:1.5px solid var(--border);color:var(--muted);cursor:pointer;transition:all .12s;background:#fff; }
.chip-btn.active,.chip-btn:hover { border-color:var(--accent);background:var(--accent-lt);color:var(--accent); }

/* Slider */
.range-sl { -webkit-appearance:none;width:100%;height:6px;border-radius:3px;outline:none; }
.range-sl::-webkit-slider-thumb { -webkit-appearance:none;width:17px;height:17px;border-radius:50%;background:var(--accent);cursor:pointer;box-shadow:0 2px 6px rgba(108,71,217,.35); }

/* Toggle switch */
.toggle-wrap { display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none; }
.toggle { width:44px;height:24px;border-radius:999px;background:#d1d5db;position:relative;transition:background .2s;flex-shrink:0; }
.toggle.on { background:var(--accent); }
.toggle::after { content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;top:3px;left:3px;transition:transform .2s;box-shadow:0 1px 4px rgba(0,0,0,.2); }
.toggle.on::after { transform:translateX(20px); }

/* Ratio selector */
.ratio-btn { display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:8px;border-radius:10px;border:2px solid var(--border);cursor:pointer;transition:all .12s;background:#fff;min-width:60px; }
.ratio-btn .ratio-box { border:2px solid currentColor;background:transparent; }
.ratio-btn:hover,.ratio-btn.active { border-color:var(--accent);color:var(--accent); }
.ratio-btn span { font-size:.65rem;font-weight:700; }

/* Size pills */
.size-btn { padding:5px 13px;border-radius:999px;font-size:.73rem;font-weight:600;border:1.5px solid var(--border);color:var(--muted);cursor:pointer;transition:all .12s;background:#fff; }
.size-btn.active,.size-btn:hover { border-color:var(--accent);background:var(--accent-lt);color:var(--accent); }

/* Output card */
.out-card { background:#fff;border:1.5px solid var(--border);border-radius:16px;overflow:hidden; }
.out-section { padding:18px 22px;border-bottom:1px solid var(--border); }
.out-section:last-child { border-bottom:none; }
.sec-label { font-size:.63rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:6px;margin-bottom:10px; }
.post-text { font-size:.9rem;line-height:1.75;white-space:pre-wrap;word-break:break-word;background:#fafafa;border:1px solid var(--border);border-radius:10px;padding:14px;min-height:60px; }
.ht-chip { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:.71rem;font-weight:600;cursor:pointer;background:var(--accent-lt);color:var(--accent);transition:all .12s; }
.ht-chip:hover { background:var(--accent);color:#fff; }
.kw-chip { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:.71rem;font-weight:600;cursor:pointer;background:#ecfdf5;color:#059669;transition:all .12s; }
.kw-chip:hover { background:#059669;color:#fff; }
.char-bar { height:4px;border-radius:2px;background:var(--border);overflow:hidden;margin-top:8px; }
.char-fill { height:100%;border-radius:2px;background:#10b981;transition:width .3s,background .3s; }
.char-fill.warn { background:#f59e0b; }
.char-fill.over { background:#ef4444; }

/* STT */
.stt-dot { width:8px;height:8px;border-radius:50%;background:#d1d5db;flex-shrink:0;transition:background .2s; }
.stt-dot.live { background:#ef4444;animation:blink 1s infinite; }
@keyframes blink { 0%,100%{opacity:1}50%{opacity:.3} }

/* Spinner */
.spin { animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* Toast */
#toast { position:fixed;bottom:24px;right:24px;z-index:999;transform:translateY(60px);opacity:0;transition:all .3s; }
#toast.show { transform:translateY(0);opacity:1; }

/* Empty */
.empty-state { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 24px;text-align:center; }

/* Image section */
#imgOutputCard img { width:100%;border-radius:12px;border:2px solid var(--border); }
</style>
</head>
<body class="font-sans">
<?php include '../elements/header.php'; ?>
<?php include '../elements/aside.php'; ?>
<?php include '../elements/preview-model.php'; ?>

<main id="mainContent" class="pt-16 pl-64 transition-all duration-300">
<div class="p-6 max-w-6xl mx-auto">

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-magic mr-2 text-violet-500"></i><?php echo $isEdit ? "Edit Post" : "Create Post"; ?></h1>
        <p class="text-sm text-gray-500 mt-0.5"><?php echo $isEdit ? "Update your social media post" : "AI-powered content for your travel brand"; ?> · <a href="index-smpost.php" class="text-violet-500 hover:text-violet-700 font-semibold">← All Posts</a></p>
    </div>
    <span class="text-xs text-gray-400 flex items-center gap-1.5"><i class="fas fa-robot text-violet-400"></i>Powered by Gemini</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

<!-- ══════════ LEFT — INPUT ══════════ -->
<div class="space-y-4">

    <!-- Platform -->
    <div class="bg-white rounded-2xl p-5 border border-[var(--border)] shadow-sm">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Platform</p>
        <div class="flex flex-wrap gap-2">
            <button onclick="setPlatform('facebook')"  class="plat-btn fb active" id="plat-facebook"><i class="fab fa-facebook-f" style="color:#1877f2"></i>Facebook</button>
            <button onclick="setPlatform('instagram')" class="plat-btn ig"         id="plat-instagram"><i class="fab fa-instagram" style="color:#e1306c"></i>Instagram</button>
            <button onclick="setPlatform('linkedin')"  class="plat-btn li"         id="plat-linkedin"><i class="fab fa-linkedin-in" style="color:#0a66c2"></i>LinkedIn</button>
            <button onclick="setPlatform('twitter')"   class="plat-btn tw"         id="plat-twitter"><i class="fab fa-x-twitter"></i>X / Twitter</button>
            <button onclick="setPlatform('tiktok')"    class="plat-btn tt"         id="plat-tiktok"><i class="fab fa-tiktok" style="color:#fe2c55"></i>TikTok</button>
        </div>
    </div>

    <!-- Content Input -->
    <div class="bg-white rounded-2xl p-5 border border-[var(--border)] shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Your Content</p>
            <div class="flex items-center gap-2">
                <span class="stt-dot" id="sttDot"></span>
                <span class="text-[11px] text-gray-400" id="sttStatus">Ready</span>
                <select id="sttLang" class="text-xs border border-gray-200 rounded-md px-2 py-1 focus:outline-none text-gray-500 bg-white">
                    <option value="bn-BD">বাংলা</option>
                    <option value="en-US">English</option>
                </select>
                <button type="button" id="sttStartBtn" onclick="sttStart()"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border-2 border-violet-300 bg-white text-violet-600 text-xs font-semibold hover:bg-violet-50 transition">
                    <i class="fas fa-microphone text-xs"></i> Start
                </button>
                <button type="button" id="sttPauseBtn" onclick="sttPause()" disabled
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-400 text-xs font-semibold transition disabled:opacity-40">
                    <i class="fas fa-pause text-xs"></i> Pause
                </button>
                <button type="button" id="sttStopBtn" onclick="sttStop()" disabled
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-white text-gray-400 text-xs font-semibold transition disabled:opacity-40">
                    <i class="fas fa-stop text-xs"></i> Stop
                </button>
            </div>
        </div>
        <textarea id="contentInput" rows="5"
            class="w-full border border-gray-200 rounded-xl p-4 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none resize-none placeholder-gray-300"
            placeholder="লিখুন বা বলুন… e.g. Dubai 5 days 4 nights package available, flight + hotel + visa, 75,000 BDT per person, limited seats!"
            oninput="updateInputCount()"></textarea>
        <div id="sttPreview" class="hidden mt-2 px-3 py-2 bg-violet-50 rounded-lg text-xs text-violet-500 italic border border-violet-100"></div>
        <div class="flex items-center justify-between mt-2">
            <span class="text-[11px] text-gray-400" id="inputCount">0 characters</span>
            <button onclick="document.getElementById('contentInput').value='';updateInputCount();" class="text-[11px] text-gray-300 hover:text-red-400 transition"><i class="fas fa-times mr-1"></i>Clear</button>
        </div>
    </div>

    <!-- Options -->
    <div class="bg-white rounded-2xl p-5 border border-[var(--border)] shadow-sm space-y-5">

        <!-- Tone -->
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Tone</p>
            <div class="flex flex-wrap gap-2" id="toneGroup">
                <button class="chip-btn active" onclick="setGroup('tone',this,'professional')">Professional</button>
                <button class="chip-btn" onclick="setGroup('tone',this,'casual')">Casual</button>
                <button class="chip-btn" onclick="setGroup('tone',this,'funny')">Funny 😄</button>
                <button class="chip-btn" onclick="setGroup('tone',this,'inspirational')">Inspirational ✨</button>
                <button class="chip-btn" onclick="setGroup('tone',this,'informative')">Informative</button>
            </div>
        </div>

        <!-- Language -->
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Language</p>
            <div class="flex flex-wrap gap-2" id="langGroup">
                <button class="chip-btn active" onclick="setGroup('lang',this,'english')">🇬🇧 English</button>
                <button class="chip-btn" onclick="setGroup('lang',this,'bangla')">🇧🇩 বাংলা</button>
                <button class="chip-btn" onclick="setGroup('lang',this,'banglish')">🔤 Banglish</button>
            </div>
        </div>

        <!-- Content Size -->
        <div>
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Content Size</p>
            <div class="flex flex-wrap gap-2 mb-2" id="sizeGroup">
                <button class="size-btn" onclick="setSize(this,'short','~50 words')">Short</button>
                <button class="size-btn active" onclick="setSize(this,'medium','~150 words')">Medium</button>
                <button class="size-btn" onclick="setSize(this,'long','~300 words')">Long</button>
                <button class="size-btn" onclick="setSize(this,'custom','')">Custom</button>
            </div>
            <div id="customSizeWrap" class="hidden flex items-center gap-2 mt-2">
                <input type="number" id="customWords" min="20" max="1000" value="100" placeholder="e.g. 200"
                    class="w-24 border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none">
                <span class="text-xs text-gray-400">words</span>
            </div>
            <p class="text-[11px] text-gray-300 mt-1" id="sizehint">~150 words</p>
        </div>

        <!-- Creativity slider -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Creativity</p>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400" id="tempLabel">Balanced</span>
                    <span class="text-xs font-bold text-violet-600 bg-violet-50 px-2 py-0.5 rounded-md" id="tempVal">0.7</span>
                </div>
            </div>
            <input type="range" id="tempSlider" min="0.1" max="1.0" step="0.1" value="0.7"
                class="range-sl" oninput="updateTemp(this.value)"
                style="background:linear-gradient(to right,#6c47d9 67%,#e8e4f3 67%)">
            <div class="flex justify-between text-[10px] text-gray-300 mt-1"><span>Factual</span><span>Balanced</span><span>Wild</span></div>
        </div>
    </div>

    <!-- Generate Image? Toggle -->
    <div class="bg-white rounded-2xl p-5 border border-[var(--border)] shadow-sm">
        <div class="toggle-wrap mb-1" onclick="toggleImage()">
            <div class="toggle" id="imgToggle"></div>
            <div>
                <p class="text-sm font-bold text-gray-700">Generate Image?</p>
                <p class="text-[11px] text-gray-400">AI will create a matching visual for your post</p>
            </div>
        </div>

        <!-- Image options — shown when toggled on -->
        <div id="imgOptions" class="hidden mt-4 space-y-4 pt-4 border-t border-[var(--border)]">

            <!-- Aspect Ratio -->
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Aspect Ratio</p>
                <div class="flex flex-wrap gap-2" id="ratioGroup">
                    <button class="ratio-btn active" onclick="setRatio(this,'1:1')" title="Square — Instagram/Facebook">
                        <div class="ratio-box w-7 h-7"></div>
                        <span>1:1</span>
                    </button>
                    <button class="ratio-btn" onclick="setRatio(this,'4:5')" title="Portrait — Instagram Feed">
                        <div class="ratio-box w-6 h-7"></div>
                        <span>4:5</span>
                    </button>
                    <button class="ratio-btn" onclick="setRatio(this,'9:16')" title="Vertical — Stories/Reels">
                        <div class="ratio-box w-5 h-8"></div>
                        <span>9:16</span>
                    </button>
                    <button class="ratio-btn" onclick="setRatio(this,'16:9')" title="Landscape — Facebook/LinkedIn">
                        <div class="ratio-box w-9 h-5"></div>
                        <span>16:9</span>
                    </button>
                    <button class="ratio-btn" onclick="setRatio(this,'3:2')" title="Standard photo">
                        <div class="ratio-box w-8 h-5"></div>
                        <span>3:2</span>
                    </button>
                </div>
            </div>

            <!-- Image Creativity -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Image Creativity</p>
                    <span class="text-xs font-bold text-pink-600 bg-pink-50 px-2 py-0.5 rounded-md" id="imgTempVal">0.8</span>
                </div>
                <input type="range" id="imgTempSlider" min="0.1" max="1.0" step="0.1" value="0.8"
                    class="range-sl" oninput="updateImgTemp(this.value)"
                    style="background:linear-gradient(to right,#ec4899 77%,#fce7f3 77%)">
                <div class="flex justify-between text-[10px] text-gray-300 mt-1"><span>Realistic</span><span>Artistic</span><span>Abstract</span></div>
            </div>
        </div>
    </div>

    <!-- Generate Button -->
    <button onclick="generateAll()" id="generateBtn"
        class="w-full py-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white rounded-2xl font-bold text-base shadow-lg shadow-violet-200 flex items-center justify-center gap-3 transition">
        <i class="fas fa-bolt"></i><span id="generateBtnLabel">Generate Content</span>
    </button>

    <!-- Save button (shown after content is generated) -->
    <div id="saveRow" class="hidden flex gap-3">
        <button onclick="savePost('draft')" id="saveDraftBtn"
            class="flex-1 py-3 bg-white border-2 border-violet-200 hover:border-violet-400 text-violet-600 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition">
            <i class="fas fa-floppy-disk"></i>Save Draft
        </button>
        <button onclick="savePost('published')" id="savePublishBtn"
            class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition shadow-md">
            <i class="fas fa-check"></i>Save & Publish
        </button>
    </div>
</div>

<!-- ══════════ RIGHT — OUTPUT ══════════ -->
<div class="space-y-4">

    <!-- Empty state -->
    <div id="emptyState" class="out-card">
        <div class="empty-state">
            <div class="w-16 h-16 bg-violet-50 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-wand-magic-sparkles text-2xl text-violet-300"></i>
            </div>
            <p class="text-sm font-semibold text-gray-400">Your content will appear here</p>
            <p class="text-xs text-gray-300 mt-1">Fill in the details and click Generate</p>
        </div>
    </div>

    <!-- Content output -->
    <div id="contentOutputCard" class="out-card hidden">

        <!-- Post text -->
        <div class="out-section">
            <div class="flex items-center justify-between mb-2">
                <div class="sec-label mb-0"><i class="fas fa-file-alt text-violet-400"></i>Polished Post <span id="platBadge" class="ml-2 text-[10px] px-2 py-0.5 bg-violet-50 text-violet-500 rounded-full font-bold"></span></div>
                <button onclick="regenContent()" id="regenContentBtn"
                    class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-rotate-right text-xs"></i>Regenerate
                </button>
            </div>
            <div class="post-text mt-3" id="postText"></div>
            <!-- Char bar -->
            <div class="flex justify-between text-[11px] text-gray-400 mt-2">
                <span id="charCountLabel">0 chars</span>
                <span id="charLimitLabel"></span>
            </div>
            <div class="char-bar"><div class="char-fill" id="charFill" style="width:0"></div></div>
            <!-- Copy -->
            <button onclick="copyPost()"
                class="mt-3 flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-bold transition">
                <i class="fas fa-copy"></i>Copy Post
            </button>
        </div>

        <!-- Hook + CTA -->
        <div class="out-section grid grid-cols-2 gap-4">
            <div>
                <div class="sec-label"><i class="fas fa-bolt text-amber-400"></i>Hook</div>
                <p class="text-sm text-gray-600 italic" id="hookText">—</p>
            </div>
            <div>
                <div class="sec-label"><i class="fas fa-hand-pointer text-green-400"></i>Call to Action</div>
                <p class="text-sm text-gray-700 font-medium" id="ctaText">—</p>
            </div>
        </div>

        <!-- Hashtags -->
        <div class="out-section">
            <div class="flex items-center justify-between mb-3">
                <div class="sec-label mb-0"><i class="fas fa-hashtag text-violet-400"></i>Hashtags</div>
                <button onclick="copyHashtags()" class="text-xs text-violet-500 hover:text-violet-700 font-semibold"><i class="fas fa-copy mr-1"></i>Copy all</button>
            </div>
            <div id="hashtagsWrap" class="flex flex-wrap gap-2"></div>
        </div>

        <!-- Keywords -->
        <div class="out-section">
            <div class="flex items-center justify-between mb-3">
                <div class="sec-label mb-0"><i class="fas fa-key text-green-400"></i>Keywords</div>
                <button onclick="copyKeywords()" class="text-xs text-green-500 hover:text-green-700 font-semibold"><i class="fas fa-copy mr-1"></i>Copy all</button>
            </div>
            <div id="keywordsWrap" class="flex flex-wrap gap-2"></div>
        </div>

        <!-- Tips -->
        <div class="out-section" id="tipsSection">
            <div class="sec-label"><i class="fas fa-lightbulb text-amber-400"></i>Platform Tips</div>
            <ul id="tipsList" class="space-y-1.5"></ul>
        </div>
    </div>

    <!-- Image output -->
    <div id="imgOutputCard" class="out-card hidden">
        <div class="out-section">
            <div class="flex items-center justify-between mb-3">
                <div class="sec-label mb-0"><i class="fas fa-image text-pink-400"></i>Generated Image</div>
                <div class="flex gap-2">
                    <button onclick="regenImage()" id="regenImgBtn"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-rotate-right text-xs"></i>Regenerate
                    </button>
                    <button onclick="downloadImage()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-pink-50 hover:bg-pink-100 text-pink-600 rounded-lg text-xs font-semibold transition">
                        <i class="fas fa-download text-xs"></i>Download
                    </button>
                </div>
            </div>
            <div id="imageContainer"></div>
            <p class="text-[11px] text-gray-300 mt-2 italic leading-relaxed" id="imgPromptLabel"></p>
        </div>
    </div>

    <!-- Image loading placeholder -->
    <div id="imgLoadingCard" class="out-card hidden">
        <div class="empty-state">
            <div class="w-12 h-12 bg-pink-50 rounded-2xl flex items-center justify-center mb-3">
                <i class="fas fa-spinner spin text-xl text-pink-400"></i>
            </div>
            <p class="text-sm font-semibold text-gray-400" id="imgLoadingLabel">Generating image…</p>
        </div>
    </div>

</div>
</div>
</div>
</main>

<!-- Toast -->
<div id="toast">
    <div id="toastInner" class="flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium bg-green-600">
        <i id="toastIcon" class="fas fa-check-circle text-lg"></i><span id="toastMsg"></span>
    </div>
</div>

<?php include '../elements/floating-menus.php'; ?>
<script src="../assets/js/script.js?t=<?php echo time(); ?>"></script>
<script>
const SOCIAL_API  = '<?php echo $socialApi; ?>';
const SMPOST_API  = '<?php echo $smpostApi; ?>';
const EDIT_SYS_ID = '<?php echo $editSysId; ?>';
const IS_EDIT     = <?php echo $isEdit ? 'true' : 'false'; ?>;

const PLAT_LIMITS = { facebook:63206, instagram:2200, linkedin:3000, twitter:280, tiktok:2200 };
const PLAT_NAMES  = { facebook:'Facebook', instagram:'Instagram', linkedin:'LinkedIn', twitter:'X / Twitter', tiktok:'TikTok' };
const PLAT_ICONS  = { facebook:'fab fa-facebook-f', instagram:'fab fa-instagram', linkedin:'fab fa-linkedin-in', twitter:'fab fa-x-twitter', tiktok:'fab fa-tiktok' };

/* ── State ── */
let state = {
    platform:    'facebook',
    tone:        'professional',
    lang:        'english',
    size:        'medium',
    customWords: 100,
    temp:        0.7,
    wantImage:   false,
    ratio:       '1:1',
    imgTemp:     0.8,
};

let _lastImagePrompt = '';
let _imageUrl        = null;

/* ── Platform ── */
function setPlatform(p) {
    state.platform = p;
    document.querySelectorAll('.plat-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('plat-' + p)?.classList.add('active');
}

/* ── Generic group setter ── */
function setGroup(key, btn, val) {
    state[key] = val;
    const groupId = key === 'tone' ? 'toneGroup' : 'langGroup';
    document.querySelectorAll(`#${groupId} .chip-btn`).forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

/* ── Content Size ── */
const SIZE_HINTS = { short:'~50 words', medium:'~150 words', long:'~300 words', custom:'' };
function setSize(btn, size, hint) {
    state.size = size;
    document.querySelectorAll('#sizeGroup .size-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('sizehint').textContent = hint;
    document.getElementById('customSizeWrap').classList.toggle('hidden', size !== 'custom');
}

/* ── Creativity ── */
function updateTemp(v) {
    state.temp = parseFloat(v);
    document.getElementById('tempVal').textContent = state.temp.toFixed(1);
    const labels = {'0.1':'Factual','0.2':'Factual','0.3':'Precise','0.4':'Grounded','0.5':'Balanced','0.6':'Balanced','0.7':'Balanced','0.8':'Creative','0.9':'Expressive','1.0':'Wild 🌀'};
    document.getElementById('tempLabel').textContent = labels[state.temp.toFixed(1)] ?? 'Balanced';
    const pct = ((state.temp - 0.1) / 0.9 * 100).toFixed(0);
    document.getElementById('tempSlider').style.background = `linear-gradient(to right,#6c47d9 ${pct}%,#e8e4f3 ${pct}%)`;
}

/* ── Image toggle ── */
function toggleImage() {
    state.wantImage = !state.wantImage;
    document.getElementById('imgToggle').classList.toggle('on', state.wantImage);
    document.getElementById('imgOptions').classList.toggle('hidden', !state.wantImage);
    updateGenerateBtn();
}

function updateGenerateBtn() {
    document.getElementById('generateBtnLabel').textContent = state.wantImage ? 'Generate Content & Image' : 'Generate Content';
}

/* ── Aspect Ratio ── */
function setRatio(btn, r) {
    state.ratio = r;
    document.querySelectorAll('#ratioGroup .ratio-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

/* ── Image Creativity ── */
function updateImgTemp(v) {
    state.imgTemp = parseFloat(v);
    document.getElementById('imgTempVal').textContent = state.imgTemp.toFixed(1);
    const pct = ((state.imgTemp - 0.1) / 0.9 * 100).toFixed(0);
    document.getElementById('imgTempSlider').style.background = `linear-gradient(to right,#ec4899 ${pct}%,#fce7f3 ${pct}%)`;
}

/* ── Input char count ── */
function updateInputCount() {
    document.getElementById('inputCount').textContent = document.getElementById('contentInput').value.length + ' characters';
}

/* ── Resolve size ── */
function getWordLimit() {
    const map = { short: 50, medium: 150, long: 300 };
    if (state.size === 'custom') {
        const v = parseInt(document.getElementById('customWords').value) || 100;
        state.customWords = v;
        return v;
    }
    return map[state.size] ?? 150;
}

/* ══════════════════════════════════════
   GENERATE ALL
══════════════════════════════════════ */
async function generateAll() {
    const content = document.getElementById('contentInput').value.trim();
    if (!content) { showToast('error', 'Please enter some content first'); return; }

    // Show loading
    setGenerating(true, 'Generating content…');
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('contentOutputCard').classList.add('hidden');
    document.getElementById('imgOutputCard').classList.add('hidden');
    document.getElementById('imgLoadingCard').classList.add('hidden');

    // 1. Generate content
    const ok = await doGenerateContent(content);
    if (!ok) { setGenerating(false); return; }

    setGenerating(false);

    // 2. Generate image (independent, non-blocking)
    if (state.wantImage) {
        startImageGeneration(document.getElementById('postText').textContent.trim());
    }
}

/* ── Content generation ── */
async function doGenerateContent(content) {
    try {
        const res  = await fetch(SOCIAL_API, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                content,
                platform:    state.platform,
                tone:        state.tone,
                language:    state.lang,
                temperature: state.temp,
                word_limit:  getWordLimit(),
                action:      'generate',
            }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Generation failed');
        renderContent(json);
        showToast('success', 'Content generated ✓');
        return true;
    } catch(e) {
        showToast('error', e.message ?? 'Failed');
        document.getElementById('emptyState').classList.remove('hidden');
        return false;
    }
}

/* ── Render content output ── */
function renderContent(d) {
    document.getElementById('postText').textContent = d.post ?? '';
    document.getElementById('hookText').textContent = d.hook || '—';
    document.getElementById('ctaText').textContent  = d.cta  || '—';

    document.getElementById('platBadge').innerHTML =
        `<i class="${PLAT_ICONS[state.platform]} mr-1"></i>${PLAT_NAMES[state.platform]}`;

    // Char bar
    const len   = mb_strlen(d.post ?? '');
    const limit = PLAT_LIMITS[state.platform] ?? 3000;
    const pct   = Math.min(100, len / limit * 100).toFixed(1);
    const fill  = document.getElementById('charFill');
    fill.style.width = pct + '%';
    fill.className = 'char-fill' + (pct > 90 ? ' over' : pct > 70 ? ' warn' : '');
    document.getElementById('charCountLabel').textContent = len.toLocaleString() + ' chars';
    document.getElementById('charLimitLabel').textContent = 'Limit: ' + limit.toLocaleString();

    // Hashtags
    document.getElementById('hashtagsWrap').innerHTML =
        (d.hashtags ?? []).length
            ? d.hashtags.map(h => `<span class="ht-chip" onclick="copyText('#${esc(h)}')">#${esc(h)}</span>`).join('')
            : '<span class="text-xs text-gray-300 italic">None</span>';

    // Keywords
    document.getElementById('keywordsWrap').innerHTML =
        (d.keywords ?? []).length
            ? d.keywords.map(k => `<span class="kw-chip" onclick="copyText(${JSON.stringify(k)})">${esc(k)}</span>`).join('')
            : '<span class="text-xs text-gray-300 italic">None</span>';

    // Tips
    const tips = d.tips ?? [];
    if (tips.length) {
        document.getElementById('tipsList').innerHTML = tips.map(t =>
            `<li class="flex gap-2 text-xs text-gray-600"><i class="fas fa-circle-check text-amber-400 mt-0.5 flex-shrink-0 text-[10px]"></i>${esc(t)}</li>`
        ).join('');
        document.getElementById('tipsSection').classList.remove('hidden');
    } else {
        document.getElementById('tipsSection').classList.add('hidden');
    }

    document.getElementById('contentOutputCard').classList.remove('hidden');
}

/* ── JS mb_strlen approximation ── */
function mb_strlen(s) { return [...s].length; }

/* ══════════════════════════════════════
   IMAGE GENERATION (independent)
══════════════════════════════════════ */
async function startImageGeneration(postText) {
    document.getElementById('imgLoadingCard').classList.remove('hidden');
    document.getElementById('imgOutputCard').classList.add('hidden');
    document.getElementById('imgLoadingLabel').textContent = 'Writing image prompt…';

    try {
        // Step 1: Image prompt
        const promptRes = await fetch(SOCIAL_API, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: postText, platform: state.platform, ratio: state.ratio, action: 'image_prompt' }),
        });
        const promptJson = await promptRes.json();
        if (promptJson.status !== 'success') throw new Error(promptJson.message ?? 'Prompt failed');
        _lastImagePrompt = promptJson.image_prompt;

        document.getElementById('imgLoadingLabel').textContent = 'Generating image…';

        // Step 2: Image
        await doGenerateImage(_lastImagePrompt);

    } catch(e) {
        document.getElementById('imgLoadingCard').classList.add('hidden');
        showToast('error', 'Image: ' + (e.message ?? 'Failed'));
    }
}

async function doGenerateImage(prompt) {
    try {
        const res  = await fetch(SOCIAL_API, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                content:     prompt,
                platform:    state.platform,
                ratio:       state.ratio,
                temperature: state.imgTemp,
                action:      'generate_image',
            }),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Image failed');

        // API returns url (saved on server) or img (base64 fallback)
        const imgSrc = json.url ?? json.img;
        _imageUrl = imgSrc;
        document.getElementById('imageContainer').innerHTML =
            `<img src="${imgSrc}" alt="Generated image" loading="lazy">`;
        document.getElementById('imgPromptLabel').textContent = 'Prompt: ' + prompt;
        document.getElementById('imgLoadingCard').classList.add('hidden');
        document.getElementById('imgOutputCard').classList.remove('hidden');
        showToast('success', 'Image generated ✓');
    } catch(e) {
        document.getElementById('imgLoadingCard').classList.add('hidden');
        throw e;
    }
}

/* ── Regenerate content only ── */
async function regenContent() {
    const content = document.getElementById('contentInput').value.trim();
    if (!content) { showToast('error', 'No content to regenerate'); return; }
    const btn = document.getElementById('regenContentBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner spin text-xs"></i> Generating…';
    await doGenerateContent(content);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-rotate-right text-xs"></i>Regenerate';
}

/* ── Regenerate image only ── */
async function regenImage() {
    const postText = document.getElementById('postText').textContent.trim();
    if (!postText) { showToast('error', 'Generate content first'); return; }
    const btn = document.getElementById('regenImgBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner spin text-xs"></i> Generating…';
    document.getElementById('imgOutputCard').classList.add('hidden');
    document.getElementById('imgLoadingCard').classList.remove('hidden');
    document.getElementById('imgLoadingLabel').textContent = 'Regenerating image…';
    try {
        // Use last prompt or re-generate one
        if (!_lastImagePrompt) {
            const promptRes = await fetch(SOCIAL_API, {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ content: postText, platform: state.platform, ratio: state.ratio, action: 'image_prompt' }),
            });
            const pj = await promptRes.json();
            if (pj.status !== 'success') throw new Error(pj.message);
            _lastImagePrompt = pj.image_prompt;
        }
        await doGenerateImage(_lastImagePrompt);
    } catch(e) {
        document.getElementById('imgLoadingCard').classList.add('hidden');
        document.getElementById('imgOutputCard').classList.remove('hidden');
        showToast('error', 'Image: ' + (e.message ?? 'Failed'));
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-rotate-right text-xs"></i>Regenerate';
}

/* ── Download ── */
function downloadImage() {
    if (!_imageUrl) return;
    const a = document.createElement('a');
    a.href = _imageUrl;
    a.download = 'travhub-' + Date.now() + '.jpg';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}

/* ── Generate button loading state ── */
function setGenerating(on, label = '') {
    const btn = document.getElementById('generateBtn');
    btn.disabled = on;
    if (on) {
        btn.innerHTML = `<i class="fas fa-spinner spin"></i> ${label}`;
    } else {
        btn.innerHTML = `<i class="fas fa-bolt"></i><span id="generateBtnLabel">${state.wantImage ? 'Generate Content & Image' : 'Generate Content'}</span>`;
    }
}

/* ── Copy helpers ── */
function copyPost() { copyText(document.getElementById('postText').textContent); }
function copyHashtags() { copyText(Array.from(document.querySelectorAll('.ht-chip')).map(c=>c.textContent.trim()).join(' ')); }
function copyKeywords() { copyText(Array.from(document.querySelectorAll('.kw-chip')).map(c=>c.textContent.trim()).join(', ')); }
async function copyText(text) {
    try { await navigator.clipboard.writeText(text); }
    catch { const el=document.createElement('textarea');el.value=text;document.body.appendChild(el);el.select();document.execCommand('copy');document.body.removeChild(el); }
    showToast('success','Copied ✓');
}

/* ── STT ── */
let _sttRec = null, _sttRecording = false, _sttPaused = false;

function sttStart() {
    if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
        showToast('error', 'Use Chrome for voice input.'); return;
    }
    if (!_sttRec) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        _sttRec = new SR();
        _sttRec.continuous     = true;
        _sttRec.interimResults = true;
        let _base = '';
        _sttRec.onstart = () => { _base = document.getElementById('contentInput').value; };
        _sttRec.onresult = e => {
            let interim = '', final_ = '';
            for (let i = e.resultIndex; i < e.results.length; i++) {
                if (e.results[i].isFinal) final_ += e.results[i][0].transcript;
                else interim += e.results[i][0].transcript;
            }
            if (final_) _base += (_base ? ' ' : '') + final_.trim();
            document.getElementById('contentInput').value = _base + (interim ? ' ' + interim : '');
            updateInputCount();
        };
        _sttRec.onerror = e => {
            if (e.error !== 'aborted') sttSetStatus('stopped', 'Error: ' + e.error);
        };
        _sttRec.onend = () => {
            // Auto-restart if still in recording state (continuous mode)
            if (_sttRecording && !_sttPaused) {
                _sttRec.lang = document.getElementById('sttLang').value;
                _sttRec.start();
            }
        };
    }
    _sttRecording = true;
    _sttPaused    = false;
    _sttRec.lang  = document.getElementById('sttLang').value || 'bn-BD';
    _sttRec.start();
    document.getElementById('sttStartBtn').disabled = true;
    document.getElementById('sttPauseBtn').disabled = false;
    document.getElementById('sttStopBtn').disabled  = false;
    sttSetStatus('active', 'Recording…');
}

function sttPause() {
    if (!_sttPaused) {
        _sttPaused = true;
        _sttRec?.stop();
        document.getElementById('sttPauseBtn').innerHTML = '<i class="fas fa-play text-xs"></i> Resume';
        sttSetStatus('paused', 'Paused');
    } else {
        _sttPaused = false;
        _sttRec.lang = document.getElementById('sttLang').value;
        _sttRec?.start();
        document.getElementById('sttPauseBtn').innerHTML = '<i class="fas fa-pause text-xs"></i> Pause';
        sttSetStatus('active', 'Recording…');
    }
}

function sttStop() {
    _sttRecording = false;
    _sttPaused    = false;
    _sttRec?.stop();
    document.getElementById('sttStartBtn').disabled = false;
    document.getElementById('sttPauseBtn').disabled = true;
    document.getElementById('sttStopBtn').disabled  = true;
    document.getElementById('sttPauseBtn').innerHTML = '<i class="fas fa-pause text-xs"></i> Pause';
    sttSetStatus('stopped', 'Done');
}

function sttSetStatus(state, text) {
    const dot = document.getElementById('sttDot');
    const txt = document.getElementById('sttStatus');
    if (txt) txt.textContent = text;
    const dotMap = {
        idle:    'stt-dot',
        active:  'stt-dot live',
        paused:  'stt-dot',
        stopped: 'stt-dot',
    };
    if (dot) dot.className = dotMap[state] ?? 'stt-dot';
}

/* ── Toast ── */
function showToast(type, msg) {
    const t=document.getElementById('toast'), i=document.getElementById('toastInner');
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').className  = `fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'} text-lg`;
    i.className = `flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-white text-sm font-medium ${type==='success'?'bg-green-600':'bg-red-500'}`;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

/* ══════════════════════════════════════
   SAVE POST
══════════════════════════════════════ */
let _currentSysId = EDIT_SYS_ID || null;

async function savePost(status = 'draft') {
    const postText = document.getElementById('postText').textContent.trim();
    if (!postText) { showToast('error', 'Generate content first'); return; }

    const btn = status === 'draft'
        ? document.getElementById('saveDraftBtn')
        : document.getElementById('savePublishBtn');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner spin"></i> Saving…';

    try {
        // Image already saved to server by generate_image action
        // _imageUrl is either a server URL or base64 fallback
        const savedImageUrl = _imageUrl || null;
        const savedImgRatio = _imageUrl ? state.ratio : null;

        const payload = {
            action:       'save',
            sys_id:       _currentSysId || undefined,
            platform:     state.platform,
            tone:         state.tone,
            language:     state.lang,
            content_size: state.size,
            word_limit:   getWordLimit(),
            temperature:  state.temp,
            raw_input:    document.getElementById('contentInput').value.trim(),
            post_text:    postText,
            hook:         document.getElementById('hookText').textContent,
            cta:          document.getElementById('ctaText').textContent,
            hashtags:     Array.from(document.querySelectorAll('.ht-chip')).map(c => c.textContent.replace('#','')),
            keywords:     Array.from(document.querySelectorAll('.kw-chip')).map(c => c.textContent.trim()),
            tips:         Array.from(document.querySelectorAll('#tipsList li')).map(li => li.textContent.trim()),
            has_image:    savedImageUrl ? 1 : 0,
            image_url:    savedImageUrl,
            image_prompt: _lastImagePrompt || null,
            image_ratio:  savedImgRatio,
            status,
        };

        const res  = await fetch(SMPOST_API, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.status !== 'success') throw new Error(json.message ?? 'Save failed');

        _currentSysId = json.sys_id;
        showToast('success', status === 'published' ? 'Published ✓' : 'Draft saved ✓');

        // Update URL without reload
        if (!IS_EDIT) {
            history.replaceState({}, '', 'create-smpost.php?id=' + _currentSysId);
        }

    } catch(e) {
        showToast('error', e.message ?? 'Save failed');
    }

    btn.disabled = false;
    btn.innerHTML = origHtml;
}

/* ══════════════════════════════════════
   EDIT MODE — load existing post
══════════════════════════════════════ */
async function loadEditPost() {
    if (!IS_EDIT || !EDIT_SYS_ID) return;
    try {
        const res  = await fetch(SMPOST_API + '?action=get&sys_id=' + encodeURIComponent(EDIT_SYS_ID));
        const json = await res.json();
        if (json.status !== 'success') return;
        const d = json.data;

        // Fill input
        document.getElementById('contentInput').value = d.raw_input ?? '';
        updateInputCount();

        // Set platform
        if (d.platform) setPlatform(d.platform);

        // Set tone
        document.querySelectorAll('#toneGroup .chip-btn').forEach(b => {
            if (b.getAttribute('onclick')?.includes(`'${d.tone}'`)) {
                b.click();
            }
        });

        // Set language
        document.querySelectorAll('#langGroup .chip-btn').forEach(b => {
            if (b.getAttribute('onclick')?.includes(`'${d.language}'`)) {
                b.click();
            }
        });

        // Set size
        document.querySelectorAll('#sizeGroup .size-btn').forEach(b => {
            if (b.getAttribute('onclick')?.includes(`'${d.content_size}'`)) b.click();
        });
        if (d.content_size === 'custom') {
            document.getElementById('customWords').value = d.word_limit ?? 100;
        }

        // Set temperature
        if (d.temperature) {
            document.getElementById('tempSlider').value = d.temperature;
            updateTemp(d.temperature);
        }

        // Render content
        renderContent({
            post:     d.post_text    ?? '',
            hook:     d.hook         ?? '',
            cta:      d.cta          ?? '',
            hashtags: d.hashtags     ?? [],
            keywords: d.keywords     ?? [],
            tips:     d.tips         ?? [],
        });

        // Image
        if (d.has_image && d.image_url) {
            _imageUrl = d.image_url;
            _lastImagePrompt = d.image_prompt ?? '';
            document.getElementById('imageContainer').innerHTML =
                `<img src="${esc(d.image_url)}" alt="Post image" loading="lazy">`;
            document.getElementById('imgPromptLabel').textContent = d.image_prompt ? 'Prompt: ' + d.image_prompt : '';
            document.getElementById('imgOutputCard').classList.remove('hidden');

            // Restore ratio
            if (d.image_ratio) {
                document.querySelectorAll('#ratioGroup .ratio-btn').forEach(b => {
                    if (b.getAttribute('onclick')?.includes(`'${d.image_ratio}'`)) b.click();
                });
                state.wantImage = true;
                document.getElementById('imgToggle').classList.add('on');
                document.getElementById('imgOptions').classList.remove('hidden');
                updateGenerateBtn();
            }
        }

        document.getElementById('saveRow').classList.remove('hidden');
        document.getElementById('emptyState').classList.add('hidden');
        _currentSysId = EDIT_SYS_ID;

    } catch(e) {
        console.error('Failed to load post:', e);
    }
}

/* ── Show save row when content is rendered ── */
const _origRenderContent = renderContent;
renderContent = function(d) {
    _origRenderContent(d);
    document.getElementById('saveRow').classList.remove('hidden');
};

/* ── Init ── */
updateTemp(0.7);
loadEditPost();
</script>
</body>
</html>