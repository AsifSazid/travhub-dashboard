/**
 * FILE PATH: /pages/task-tabs/ww-visa/_state.js
 * Visa Service Module — shared state
 */

window._vs = window._vs || {
    cfg:       null,   // initWorkVisaTab(config) config
    data:      null,   // visa_services row (with parsed JSON fields)
    activeTab: 'info', // 'info' | 'travelers' | 'status'
};