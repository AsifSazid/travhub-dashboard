/**
 * FILE PATH: /pages/task-tabs/ww-air-tickets/_state.js
 * Shared module state — all files read/write via window._at
 */

window._at = {
    cfg:          {},      // initWorkAirTicketTab config
    data:         null,    // air_tickets row (at_quotations, at_bookings, at_confirmations, commands...)
    activeTab:    'mindboard',
    currentNotes: [],      // mindboard notes cache
    activeQSysId: null,
    activeBSysId: null,
    activeConfId: null,
    gdsSegments:  [],
    gdsFares:     [],
    qSelectedIds: new Set(),
    sotoNotes:    [],
    sotoFile:     null,
    pendingFile:  null,
    pendingFiles: [],
    confPending:  {},      // confSysId → File[]
    recorder:     null,
    recChunks:    [],
    recording:    false,
    sttRec:       null,
    sttFinal:     '',
    sttActive:    false,
    sttPaused:    false,
    notesVisible: true,    // GDS notes toggle
};