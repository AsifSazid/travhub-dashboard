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
    // Lead segments — city/hotel info from lead creation
    leadSegments:    [],   // [{ city_sys_id, city_name, hotel_name, hotel_sys_id, check_in, check_out }]
    activeSegFilter: null, // city_sys_id to filter quotations by, null = show all
};