<?php
if (!function_exists('create_alert')) {
    /**
     * Create an alert for a user.
     *
     * @param mysqli $conn
     * @param int $user_id
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string|null $related_type
     * @param int|null $related_id
     * @param string|null $link_url
     * @param int|null $dedupe_hours  Prevent duplicates within timeframe
     * @return bool
     */
    function create_alert($conn, $user_id, $type, $title, $message, $related_type = null, $related_id = null, $link_url = null, $dedupe_hours = null)
    {
        if (!$conn || $conn->connect_error) {
            return false;
        }

        if ($dedupe_hours !== null) {
            $dedupeSql = "SELECT id FROM alerts 
                WHERE user_id = ? 
                  AND alert_type = ? 
                  AND ((related_type IS NULL AND ? IS NULL) OR related_type <=> ?) 
                  AND ((related_id IS NULL AND ? IS NULL) OR related_id <=> ?)
                  AND created_at >= (NOW() - INTERVAL ? HOUR)
                LIMIT 1";
            $stmt = $conn->prepare($dedupeSql);
            $stmt->bind_param(
                'isssiii',
                $user_id,
                $type,
                $related_type,
                $related_type,
                $related_id,
                $related_id,
                $dedupe_hours
            );
            $stmt->execute();
            $dedupeResult = $stmt->get_result();
            $hasRecent = $dedupeResult && $dedupeResult->num_rows > 0;
            $stmt->close();

            if ($hasRecent) {
                return false;
            }
        }

        $insertSql = "INSERT INTO alerts (user_id, alert_type, title, message, related_type, related_id, link_url) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param(
            'issssis',
            $user_id,
            $type,
            $title,
            $message,
            $related_type,
            $related_id,
            $link_url
        );

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}

