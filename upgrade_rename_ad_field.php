<?php

include 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Database Upgrade - Rename Ad Field</h2>";

try {
    // Check if is_ad field exists
    $checkResult = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_ad'");
    
    if ($checkResult->num_rows > 0) {
        // Rename is_ad to is_marked
        $sql = "ALTER TABLE posts CHANGE COLUMN is_ad is_marked TINYINT(1) DEFAULT 0 COMMENT 'Mark level: 0=normal, 1=slot 1, 2=slot 2, 3=slot 3'";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>Successfully renamed 'is_ad' to 'is_marked'</p>";
        } else {
            throw new Exception("Failed to rename field: " . $conn->error);
        }
    } else {
        // Check if is_marked already exists
        $checkResult2 = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_marked'");
        if ($checkResult2->num_rows > 0) {
            echo "<p style='color: green;'>Field 'is_marked' already exists</p>";
        } else {
            // Create is_marked field
            $sql2 = "ALTER TABLE posts ADD COLUMN is_marked TINYINT(1) DEFAULT 0 COMMENT 'Mark level: 0=normal, 1=slot 1, 2=slot 2, 3=slot 3' AFTER is_pinned";
            
            if ($conn->query($sql2)) {
                echo "<p style='color: green;'>Successfully added field 'is_marked' to posts table</p>";
            } else {
                throw new Exception("Failed to add field: " . $conn->error);
            }
        }
    }
    
    echo "<h3>Current posts table structure:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Default</th><th>Comment</th></tr>";
    
    $columnsResult = $conn->query("SHOW COLUMNS FROM posts");
    while ($column = $columnsResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Comment'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='index.php' style='color: #07c160;'>Back to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
