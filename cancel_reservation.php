<?php
require_once 'config.php';
require_once 'if.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isLoggedIn() || $_SESSION['user_type'] != 'user') {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservation_id'])) {
    $reservationId = $_POST['reservation_id'];
    
    // Optional: Verify the reservation belongs to the current user before canceling
    // This is a security best practice to prevent users from canceling others' reservations
    $currentUserId = $_SESSION['user_id'];
    $reservationDetails = getReservationDetails($conn, $reservationId); // You might need to implement this function in if.php

    if ($reservationDetails && $reservationDetails['CustomerID'] == $currentUserId) {
        if (updateReservationStatus($conn, $reservationId, 'Cancelled')) {
            $response['success'] = true;
            $response['message'] = 'Reservation cancelled successfully.';
        } else {
            $response['message'] = 'Failed to cancel reservation.';
        }
    } else {
        $response['message'] = 'Reservation not found or you do not have permission to cancel it.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

echo json_encode($response);
?>