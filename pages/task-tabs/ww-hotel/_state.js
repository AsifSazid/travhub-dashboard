/**
 * FILE PATH: /pages/task-tabs/ww-hotel/_state.js
 * Shared module state — all files read/write via window._ht
 */

window._ht = {
    cfg:          {},      // initWorkHotelTab config
    data:         null,    // hotel_services row (ht_quotations, ht_bookings, ht_confirmations)
    activeTab:    'mindboard',
    currentNotes: [],      // mindboard notes cache
    activeQSysId: null,
    activeBSysId: null,
    activeConfId: null,
    qSelectedIds: new Set(),
    pendingFile:  null,
    pendingFiles: [],
    recorder:     null,
    recChunks:    [],
    recording:    false,
    sttRec:       null,
    sttFinal:     '',
    sttActive:    false,
    sttPaused:    false,
};