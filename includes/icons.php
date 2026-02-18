<?php
// SVG Icon Functions
// Professional SVG icons for the application

function icon_cart($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <circle cx="9"cy="21"r="1"></circle>
        <circle cx="20"cy="21"r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>';
}

function icon_settings($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <circle cx="12"cy="12"r="3"></circle>
        <path d="M12 1v6m0 6v6m5.2-14.8l-4.2 4.2m-6 6l-4.2 4.2m0-14.8l4.2 4.2m6 6l4.2 4.2M23 12h-6M7 12H1"></path>
    </svg>';
}

function icon_mail($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
        <polyline points="22,6 12,13 2,6"></polyline>
    </svg>';
}

function icon_moon($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
    </svg>';
}

function icon_sun($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <circle cx="12"cy="12"r="5"></circle>
        <line x1="12"y1="1"x2="12"y2="3"></line>
        <line x1="12"y1="21"x2="12"y2="23"></line>
        <line x1="4.22"y1="4.22"x2="5.64"y2="5.64"></line>
        <line x1="18.36"y1="18.36"x2="19.78"y2="19.78"></line>
        <line x1="1"y1="12"x2="3"y2="12"></line>
        <line x1="21"y1="12"x2="23"y2="12"></line>
        <line x1="4.22"y1="19.78"x2="5.64"y2="18.36"></line>
        <line x1="18.36"y1="5.64"x2="19.78"y2="4.22"></line>
    </svg>';
}

function icon_user($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
        <circle cx="12"cy="7"r="4"></circle>
    </svg>';
}

function icon_check($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <polyline points="20 6 9 17 4 12"></polyline>
    </svg>';
}

function icon_x($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="18"y1="6"x2="6"y2="18"></line>
        <line x1="6"y1="6"x2="18"y2="18"></line>
    </svg>';
}

function icon_alert($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
        <line x1="12"y1="9"x2="12"y2="13"></line>
        <line x1="12"y1="17"x2="12.01"y2="17"></line>
    </svg>';
}

function icon_home($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        <polyline points="9 22 9 12 15 12 15 22"></polyline>
    </svg>';
}

function icon_arrow_left($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="19"y1="12"x2="5"y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
    </svg>';
}

function icon_package($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="16.5"y1="9.4"x2="7.5"y2="4.21"></line>
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12"y1="22.08"x2="12"y2="12"></line>
    </svg>';
}

function icon_box($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
        <line x1="12"y1="22.08"x2="12"y2="12"></line>
    </svg>';
}

function icon_check_circle($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>';
}

function icon_x_circle($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <circle cx="12"cy="12"r="10"></circle>
        <line x1="15"y1="9"x2="9"y2="15"></line>
        <line x1="9"y1="9"x2="15"y2="15"></line>
    </svg>';
}

function icon_folder($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
    </svg>';
}

function icon_tag($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
        <line x1="7"y1="7"x2="7.01"y2="7"></line>
    </svg>';
}

function icon_percent($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="19"y1="5"x2="5"y2="19"></line>
        <circle cx="6.5"cy="6.5"r="2.5"></circle>
        <circle cx="17.5"cy="17.5"r="2.5"></circle>
    </svg>';
}

function icon_megaphone($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M3 11l18-5v12L3 13v-2z"></path>
        <path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"></path>
    </svg>';
}

function icon_filter($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
    </svg>';
}

function icon_shopping_bag($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
        <line x1="3"y1="6"x2="21"y2="6"></line>
        <path d="M16 10a4 4 0 0 1-8 0"></path>
    </svg>';
}

function icon_sparkles($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"></path>
        <path d="M19 12l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"></path>
    </svg>';
}

function icon_video($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <polygon points="23 7 16 12 23 17 23 7"></polygon>
        <rect x="1"y="5"width="15"height="14"rx="2"ry="2"></rect>
    </svg>';
}

function icon_lock($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <rect x="3"y="11"width="18"height="11"rx="2"ry="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
    </svg>';
}

function icon_edit($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
    </svg>';
}

function icon_grid($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <rect x="3"y="3"width="7"height="7"></rect>
        <rect x="14"y="3"width="7"height="7"></rect>
        <rect x="14"y="14"width="7"height="7"></rect>
        <rect x="3"y="14"width="7"height="7"></rect>
    </svg>';
}

function icon_calendar($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <rect x="3"y="4"width="18"height="18"rx="2"ry="2"></rect>
        <line x1="16"y1="2"x2="16"y2="6"></line>
        <line x1="8"y1="2"x2="8"y2="6"></line>
        <line x1="3"y1="10"x2="21"y2="10"></line>
    </svg>';
}

function icon_clipboard($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
        <rect x="8"y="2"width="8"height="4"rx="1"ry="1"></rect>
    </svg>';
}

function icon_phone($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
    </svg>';
}

function icon_truck($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <rect x="1"y="3"width="15"height="13"></rect>
        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
        <circle cx="5.5"cy="18.5"r="2.5"></circle>
        <circle cx="18.5"cy="18.5"r="2.5"></circle>
    </svg>';
}

function icon_gift($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <polyline points="20 12 20 22 4 22 4 12"></polyline>
        <rect x="2"y="7"width="20"height="5"></rect>
        <line x1="12"y1="22"x2="12"y2="7"></line>
        <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
        <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
    </svg>';
}

function icon_trash($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <polyline points="3 6 5 6 21 6"></polyline>
        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        <line x1="10"y1="11"x2="10"y2="17"></line>
        <line x1="14"y1="11"x2="14"y2="17"></line>
    </svg>';
}

function icon_dollar($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="12"y1="1"x2="12"y2="23"></line>
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
    </svg>';
}

function icon_zap($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
    </svg>';
}

function icon_credit_card($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <rect x="1"y="4"width="22"height="16"rx="2"ry="2"></rect>
        <line x1="1"y1="10"x2="23"y2="10"></line>
    </svg>';
}

function icon_bank($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="3"y1="21"x2="21"y2="21"></line>
        <path d="M3 10h18"></path>
        <path d="M5 6l7-3 7 3"></path>
        <path d="M4 10v11"></path>
        <path d="M20 10v11"></path>
        <path d="M8 10v11"></path>
        <path d="M12 10v11"></path>
        <path d="M16 10v11"></path>
    </svg>';
}

function icon_users($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
        <circle cx="9"cy="7"r="4"></circle>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
    </svg>';
}

function icon_tool($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
    </svg>';
}

function icon_loudspeaker($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M8.8 20v-4.1l1.9.2a2.3 2.3 0 0 0 2.164-2.1V8.3A5.5 5.5 0 0 0 2 8.25c0 2.8.656 3.054 1 4.55a5.77 5.77 0 0 1 .029 2.758L2 20"></path>
        <path d="M19.8 17.8a7.5 7.5 0 0 0 0-11.6"></path>
        <path d="M16 14a3 3 0 0 0 0-4"></path>
    </svg>';
}

function icon_eye($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12"cy="12"r="3"></circle>
    </svg>';
}

function icon_arrow_right($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <line x1="5"y1="12"x2="19"y2="12"></line>
        <polyline points="12 5 19 12 12 19"></polyline>
    </svg>';
}

function icon_heart($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
    </svg>';
}

function icon_log_out($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21"y1="12"x2="9"y2="12"></line>
    </svg>';
}

function icon_upload($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="17 8 12 3 7 8"></polyline>
        <line x1="12"y1="3"x2="12"y2="15"></line>
    </svg>';
}

function icon_save($size = 20, $color = 'currentColor') {
    return '<svg width="' . $size . '"height="' . $size . '"viewBox="0 0 24 24"fill="none"stroke="' . $color . '"stroke-width="2"stroke-linecap="round"stroke-linejoin="round"style="display: inline-block; vertical-align: middle;">
        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
        <polyline points="17 21 17 13 7 13 7 21"></polyline>
        <polyline points="7 3 7 8 15 8"></polyline>
    </svg>';
}
