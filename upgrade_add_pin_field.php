<?php

include 'config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Database Upgrade - Add Pin and Ad Fields</h2>";

try {
    $checkResult = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_pinned'");
    
    if ($checkResult->num_rows > 0) {
        echo "<p style='color: green;'>Field 'is_pinned' already exists</p>";
    } else {
        $sql = "ALTER TABLE posts ADD COLUMN is_pinned TINYINT(1) DEFAULT 0 COMMENT 'Pin level: 0=not pinned, 1=pin slot 1, 2=pin slot 2, 3=pin slot 3' AFTER music";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>Successfully added field 'is_pinned' to posts table</p>";
        } else {
            throw new Exception("Failed to add field: " . $conn->error);
        }
    }
    
    $checkResult2 = $conn->query("SHOW COLUMNS FROM posts LIKE 'is_ad'");
    
    if ($checkResult2->num_rows > 0) {
        echo "<p style='color: green;'>Field 'is_ad' already exists</p>";
    } else {
        $sql2 = "ALTER TABLE posts ADD COLUMN is_ad TINYINT(1) DEFAULT 0 COMMENT 'Ad level: 0=not ad, 1=ad slot 1, 2=ad slot 2, 3=ad slot 3' AFTER is_pinned";
        
        if ($conn->query($sql2)) {
            echo "<p style='color: green;'>Successfully added field 'is_ad' to posts table</p>";
        } else {
            throw new Exception("Failed to add field: " . $conn->error);
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
