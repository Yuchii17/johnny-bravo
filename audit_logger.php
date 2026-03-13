<?php
if (!function_exists('log_audit')) {
    /**
     * Log user activity for auditing purposes
     * 
     * @param mysqli $conn The database connection
     * @param string|null $user_id The unique user ID
     * @param string $fullname Full name of the user
     * @param string|null $role Role of the user
     * @param string $action The action performed (e.g., 'LOGIN', 'ITEM_DECLARATION')
     * @param string|null $details Additional details about the action
     * @return bool True on success, False on failure
     */
    function log_audit($conn, $user_id, $fullname, $role, $action, $details = null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, fullname, role, action, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $user_id, $fullname, $role, $action, $details, $ip_address);
            $result = $stmt->execute();
            $stmt->close();
            
            // Auto-archive logs older than 24 hours when a new log is created
            archive_old_logs($conn);
            
            return $result;
        }
        return false;
    }

    /**
     * Archive logs older than 24 hours
     * Marks is_archived = 1 for logs > 24h
     */
    function archive_old_logs($conn) {
        $stmt = $conn->prepare("UPDATE audit_logs SET is_archived = 1 WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) AND is_archived = 0");
        if ($stmt) {
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>