<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $data = json_decode(file_get_contents('php://input'), true);
    
    
    if (empty($data['name']) || empty($data['email']) || empty($data['subject']) || empty($data['message'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit();
    }
    
   
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address.'
        ]);
        exit();
    }
    

    $name = htmlspecialchars(trim($data['name']));
    $email = htmlspecialchars(trim($data['email']));
    $phone = isset($data['phone']) ? htmlspecialchars(trim($data['phone'])) : null;
    $subject = htmlspecialchars(trim($data['subject']));
    $message = htmlspecialchars(trim($data['message']));
    
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
    
    if ($stmt->execute()) {
       
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! We will get back to you soon.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send message. Please try again.'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

$conn->close();
?>
