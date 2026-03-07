<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require_once '../config/db_connect.php';

// Get admin information
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Pending bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$stats['pending_bookings'] = $result->fetch_assoc()['count'];

// Total packages
$result = $conn->query("SELECT COUNT(*) as count FROM packages");
$stats['total_packages'] = $result->fetch_assoc()['count'];

// Total revenue
$result = $conn->query("SELECT SUM(total_price) as revenue FROM bookings WHERE status = 'confirmed'");
$stats['total_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;

// New messages
$result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
$stats['new_messages'] = $result->fetch_assoc()['count'];

// Handle actions
$action_message = '';
$action_type = '';

// Delete user
if (isset($_GET['delete_user'])) {
    $user_id = intval($_GET['delete_user']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'client'");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $action_message = "User deleted successfully!";
        $action_type = "success";
    } else {
        $action_message = "Failed to delete user.";
        $action_type = "error";
    }
    $stmt->close();
}

// Update booking status
if (isset($_GET['update_booking'])) {
    $booking_id = intval($_GET['update_booking']);
    $status = $_GET['status'];
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $booking_id);
    if ($stmt->execute()) {
        $action_message = "Booking status updated successfully!";
        $action_type = "success";
    } else {
        $action_message = "Failed to update booking status.";
        $action_type = "error";
    }
    $stmt->close();
}

// Delete package
if (isset($_GET['delete_package'])) {
    $package_id = intval($_GET['delete_package']);
    $stmt = $conn->prepare("DELETE FROM packages WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    if ($stmt->execute()) {
        $action_message = "Package deleted successfully!";
        $action_type = "success";
    } else {
        $action_message = "Failed to delete package.";
        $action_type = "error";
    }
    $stmt->close();
}

// Mark message as read
if (isset($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->close();
}

// Add new package
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_package'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $duration = trim($_POST['duration']);
    $location = trim($_POST['location']);
    $rating = floatval($_POST['rating']);
    $inclusions = trim($_POST['inclusions']);
    
    if (!empty($title) && !empty($description) && $price > 0 && !empty($duration) && !empty($location)) {
        $stmt = $conn->prepare("INSERT INTO packages (title, description, price, duration, location, rating, inclusions) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssds", $title, $description, $price, $duration, $location, $rating, $inclusions);
        
        if ($stmt->execute()) {
            $action_message = "Package added successfully!";
            $action_type = "success";
        } else {
            $action_message = "Failed to add package.";
            $action_type = "error";
        }
        $stmt->close();
    } else {
        $action_message = "Please fill in all required fields.";
        $action_type = "error";
    }
}

// Get data for active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DreamTourSri Lanka</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .admin-header h1 {
            color: #1f2937;
            font-size: 2rem;
            margin: 0;
        }
        

        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-user span {
            color: #6b7280;
        }

        .btn-logout {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0 0 0.5rem 0;
            font-weight: 500;
        }

        .stat-card .stat-value {
            color: #1f2937;
            font-size: 2rem;
            font-weight: bold;
            margin: 0;
        }

        .stat-card.revenue .stat-value {
            color: #10b981;
        }

        .stat-card.pending .stat-value {
            color: #f59e0b;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab {
            padding: 1rem 1.5rem;
            background: transparent;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
        }

        .tab:hover {
            color: #1f2937;
        }

        .tab.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }

        .content-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tr:hover {
            background: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.new {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.read {
            background: #e5e7eb;
            color: #374151;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-confirm {
            background: #10b981;
            color: white;
        }

        .btn-confirm:hover {
            background: #059669;
        }

        .btn-cancel {
            background: #f59e0b;
            color: white;
        }

        .btn-cancel:hover {
            background: #d97706;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
        }

        .btn-view:hover {
            background: #2563eb;
        }

        .btn-add {
            background: #10b981;
            color: white;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }

        .btn-add:hover {
            background: #059669;
        }

        .section-header-with-button {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header-with-button h2 {
            margin: 0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .modal-header h2 {
            color: #1f2937;
            font-size: 1.5rem;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #9ca3af;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .modal-close:hover {
            background: #f3f4f6;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit-modal {
            width: 100%;
            padding: 0.875rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit-modal:hover {
            background: #059669;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .tabs {
                overflow-x: auto;
            }

            .data-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h1> Admin Dashboard</h1>
                <p style="color: #6b7280; margin: 0.5rem 0 0 0;">DreamTourSri Lanka - Management Panel</p>
            </div>
            <div class="admin-user">
                <span>Welcome, <strong><?php echo htmlspecialchars($admin['username']); ?></strong></span>
                <a href="../auth/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>

        <?php if ($action_message): ?>
        <div class="alert <?php echo $action_type; ?> show">
            <?php echo $action_message; ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-value"><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <p class="stat-value"><?php echo $stats['total_bookings']; ?></p>
            </div>
            <div class="stat-card pending">
                <h3>Pending Bookings</h3>
                <p class="stat-value"><?php echo $stats['pending_bookings']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Packages</h3>
                <p class="stat-value"><?php echo $stats['total_packages']; ?></p>
            </div>
            <div class="stat-card revenue">
                <h3>Total Revenue</h3>
                <p class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>New Messages</h3>
                <p class="stat-value"><?php echo $stats['new_messages']; ?></p>
            </div>
        </div>

        <div class="tabs">
            <a href="?tab=overview" class="tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">Overview</a>
            <a href="?tab=users" class="tab <?php echo $active_tab === 'users' ? 'active' : ''; ?>">Users</a>
            <a href="?tab=bookings" class="tab <?php echo $active_tab === 'bookings' ? 'active' : ''; ?>">Bookings</a>
            <a href="?tab=packages" class="tab <?php echo $active_tab === 'packages' ? 'active' : ''; ?>">Packages</a>
            <a href="?tab=messages" class="tab <?php echo $active_tab === 'messages' ? 'active' : ''; ?>">Messages</a>
        </div>

        <div class="content-section">
            <?php if ($active_tab === 'overview'): ?>
                <h2>Dashboard Overview</h2>
                <p>Welcome to the admin dashboard. Use the tabs above to manage users, bookings, packages, and messages.</p>
                
                <h3 style="margin-top: 2rem;">Recent Bookings (Last 5)</h3>
                <?php
                $result = $conn->query("SELECT b.*, u.username, p.title as package_name 
                                       FROM bookings b 
                                       JOIN users u ON b.user_id = u.id 
                                       JOIN packages p ON b.package_id = p.id 
                                       ORDER BY b.created_at DESC LIMIT 5");
                if ($result->num_rows > 0):
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>People</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['username']); ?></td>
                            <td><?php echo htmlspecialchars($booking['package_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo $booking['num_people']; ?></td>
                            <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                            <td><span class="badge <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>No bookings yet</p>
                </div>
                <?php endif; ?>

            <?php elseif ($active_tab === 'users'): ?>
                <h2>User Management</h2>
                <?php
                $result = $conn->query("SELECT * FROM users WHERE role = 'client' ORDER BY created_at DESC");
                if ($result->num_rows > 0):
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?tab=users&delete_user=<?php echo $user['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">👥</div>
                    <p>No users found</p>
                </div>
                <?php endif; ?>

            <?php elseif ($active_tab === 'bookings'): ?>
                <h2>Booking Management</h2>
                <?php
                $result = $conn->query("SELECT b.*, u.username, u.email, p.title as package_name 
                                       FROM bookings b 
                                       JOIN users u ON b.user_id = u.id 
                                       JOIN packages p ON b.package_id = p.id 
                                       ORDER BY b.created_at DESC");
                if ($result->num_rows > 0):
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Booking Date</th>
                            <th>People</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['username']); ?></td>
                            <td><?php echo htmlspecialchars($booking['email']); ?></td>
                            <td><?php echo htmlspecialchars($booking['package_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo $booking['num_people']; ?></td>
                            <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                            <td><span class="badge <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                    <a href="?tab=bookings&update_booking=<?php echo $booking['id']; ?>&status=confirmed" 
                                       class="btn-action btn-confirm">Confirm</a>
                                    <a href="?tab=bookings&update_booking=<?php echo $booking['id']; ?>&status=cancelled" 
                                       class="btn-action btn-cancel">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📅</div>
                    <p>No bookings found</p>
                </div>
                <?php endif; ?>

            <?php elseif ($active_tab === 'packages'): ?>
                <div class="section-header-with-button">
                    <h2>Package Management</h2>
                    <button class="btn-add" onclick="openAddPackageModal()">+ Add Package</button>
                </div>
                <?php
                $result = $conn->query("SELECT * FROM packages ORDER BY created_at DESC");
                if ($result->num_rows > 0):
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Location</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($package = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $package['id']; ?></td>
                            <td><?php echo htmlspecialchars($package['title']); ?></td>
                            <td><?php echo htmlspecialchars($package['location']); ?></td>
                            <td><?php echo htmlspecialchars($package['duration']); ?></td>
                            <td>$<?php echo number_format($package['price'], 2); ?></td>
                            <td><?php echo $package['rating']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?tab=packages&delete_package=<?php echo $package['id']; ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this package?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <p>No packages found</p>
                </div>
                <?php endif; ?>

            <?php elseif ($active_tab === 'messages'): ?>
                <h2>Contact Messages</h2>
                <?php
                $result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
                if ($result->num_rows > 0):
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($message = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $message['id']; ?></td>
                            <td><?php echo htmlspecialchars($message['name']); ?></td>
                            <td><?php echo htmlspecialchars($message['email']); ?></td>
                            <td><?php echo htmlspecialchars($message['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($message['subject']); ?></td>
                            <td><?php echo substr(htmlspecialchars($message['message']), 0, 50) . '...'; ?></td>
                            <td><span class="badge <?php echo $message['status']; ?>"><?php echo ucfirst($message['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                            <td>
                                <?php if ($message['status'] === 'new'): ?>
                                <a href="?tab=messages&mark_read=<?php echo $message['id']; ?>" 
                                   class="btn-action btn-view">Mark Read</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✉️</div>
                    <p>No messages found</p>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="addPackageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Package</h2>
                <button class="modal-close" onclick="closeAddPackageModal()">&times;</button>
            </div>
            <form method="POST" action="?tab=packages">
                <div class="form-group">
                    <label>Package Title *</label>
                    <input type="text" name="title" placeholder="e.g., Cultural Triangle Tour" required>
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" placeholder="Enter package description" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Price (USD) *</label>
                        <input type="number" name="price" placeholder="299.99" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Duration *</label>
                        <input type="text" name="duration" placeholder="5 Days / 4 Nights" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" name="location" placeholder="e.g., Central Province" required>
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <input type="number" name="rating" placeholder="4.5" step="0.1" min="0" max="5" value="5.0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Inclusions</label>
                    <textarea name="inclusions" placeholder="e.g., Accommodation, Breakfast, Guide, Transport"></textarea>
                </div>

                <button type="submit" name="add_package" class="btn-submit-modal">Add Package</button>
            </form>
        </div>
    </div>

    <script src="../main.js"></script>
    <script>
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.classList.remove('show');
            });
        }, 5000);

        function openAddPackageModal() {
            document.getElementById('addPackageModal').classList.add('active');
        }

        function closeAddPackageModal() {
            document.getElementById('addPackageModal').classList.remove('active');
        }

        document.getElementById('addPackageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddPackageModal();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
