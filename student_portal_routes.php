<?php
/**
 * Student Portal API Routes
 * Add these routes to your /rista_ci4/app/Config/Routes.php file
 *
 * All routes require JWT authentication via 'jwtauth' filter
 * Admin routes require 'adminonly' filter
 */

// ==================== STUDENT PORTAL API ROUTES ====================

$routes->group('api/student-portal', ['filter' => 'jwtauth'], function($routes) {

    // PROFILE CHANGE REQUESTS
    $routes->post('profile/change-request', 'StudentPortalAPI::createProfileChangeRequest');
    $routes->get('profile/change-requests', 'StudentPortalAPI::listProfileChangeRequests');
    $routes->get('profile/change-request/(:num)', 'StudentPortalAPI::getProfileChangeRequest/$1');

    // ABSENCE REQUESTS
    $routes->post('absence-request', 'StudentPortalAPI::createAbsenceRequest');
    $routes->get('absence-requests', 'StudentPortalAPI::listAbsenceRequests');
    $routes->get('absence-request/(:num)', 'StudentPortalAPI::getAbsenceRequest/$1');

    // SPECIAL REQUESTS
    $routes->post('special-request', 'StudentPortalAPI::createSpecialRequest');
    $routes->get('special-requests', 'StudentPortalAPI::listSpecialRequests');
    $routes->get('special-request/(:num)', 'StudentPortalAPI::getSpecialRequest/$1');
    $routes->get('request-types', 'StudentPortalAPI::getRequestTypes');

    // DOCUMENTS
    $routes->post('document/upload', 'StudentPortalAPI::uploadDocument');
    $routes->get('documents', 'StudentPortalAPI::listDocuments');
    $routes->get('document/(:num)', 'StudentPortalAPI::getDocument/$1');
    $routes->get('document/(:num)/download', 'StudentPortalAPI::downloadDocument/$1');
    $routes->delete('document/(:num)', 'StudentPortalAPI::deleteDocument/$1');

    // CONVERSATIONS
    $routes->post('request/(:alpha)/(:num)/message', 'StudentPortalAPI::addMessage/$1/$2');
    $routes->get('request/(:alpha)/(:num)/conversation', 'StudentPortalAPI::getConversation/$1/$2');
});

// ==================== ADMIN APPROVAL CENTER ROUTES ====================

$routes->group('api/admin/approval-center', ['filter' => 'jwtauth,adminonly'], function($routes) {

    // DASHBOARD
    $routes->get('dashboard', 'StudentPortalAPI::getApprovalDashboard');
    $routes->get('all-pending', 'StudentPortalAPI::getAllPendingRequests');
    $routes->get('workload', 'StudentPortalAPI::getAdminWorkload');

    // PROFILE CHANGE APPROVAL
    $routes->post('profile-change/(:num)/approve', 'StudentPortalAPI::approveProfileChange/$1');
    $routes->post('profile-change/(:num)/reject', 'StudentPortalAPI::rejectProfileChange/$1');

    // ABSENCE APPROVAL
    $routes->post('absence/(:num)/approve', 'StudentPortalAPI::approveAbsence/$1');
    $routes->post('absence/(:num)/reject', 'StudentPortalAPI::rejectAbsence/$1');

    // SPECIAL REQUEST MANAGEMENT
    $routes->patch('special-request/(:num)', 'StudentPortalAPI::updateSpecialRequest/$1');
    $routes->post('special-request/(:num)/assign', 'StudentPortalAPI::assignSpecialRequest/$1');

    // DOCUMENT REVIEW
    $routes->post('document/(:num)/approve', 'StudentPortalAPI::approveDocument/$1');
    $routes->post('document/(:num)/reject', 'StudentPortalAPI::rejectDocument/$1');

    // BULK OPERATIONS
    $routes->post('bulk-approve', 'StudentPortalAPI::bulkApprove');
    $routes->post('bulk-reject', 'StudentPortalAPI::bulkReject');

    // REQUEST TYPE CONFIGURATION
    $routes->get('request-types/all', 'StudentPortalAPI::getAllRequestTypes');
    $routes->post('request-type', 'StudentPortalAPI::createRequestType');
    $routes->patch('request-type/(:num)', 'StudentPortalAPI::updateRequestType/$1');
});

// ==================== TEACHER ROUTES ====================

$routes->group('api/teacher', ['filter' => 'jwtauth'], function($routes) {
    // View absences for their classes
    $routes->get('class/(:num)/absences', 'StudentPortalAPI::getAbsencesForClass/$1');
});
