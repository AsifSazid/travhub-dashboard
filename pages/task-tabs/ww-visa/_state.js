/**
 * FILE PATH: /pages/task-tabs/ww-visa/_state.js
 * Visa Service Module — shared state
 */

window._vs = window._vs || {
    cfg:            null,   // initWorkVisaTab(config)
    data:           null,   // visa_services row (parsed JSON fields)
    activeTab:      'mindboard', // 'mindboard'|'info'|'travelers'|'status'
    activeTraveler: null,   // sys_id of selected traveler pill (Layer 2)
    activeTravSub:  'docs', // 'docs'|'appinfo'|'coverletter'|'export'|'travstatus' (Layer 3)
};