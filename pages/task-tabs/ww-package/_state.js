/**
 * FILE PATH: /pages/task-tabs/ww-package/_state.js
 * Package module shared state — all files read/write via window._pk
 */

window._pk = {
    cfg:          {},      // initWorkPackageTab config
    data:         null,    // package_services row (pk_quotations, pk_confirmation)
    activeTab:    'mindboard',
    currentNotes: [],      // mindboard notes cache
    activeQSysId: null,    // selected quotation sys_id
    selectedNoteIds: new Set(),

    // File / voice
    pendingFile:  null,
    pendingFiles: [],
    recorder:     null,
    recChunks:    [],
    recording:    false,

    // STT (Web Speech API)
    sttRec:    null,
    sttFinal:  '',
    sttActive: false,
    sttPaused: false,
    sttTarget: null,   // 'mindboard' | 'quotation'
};