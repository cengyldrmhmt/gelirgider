<?php
require_once 'app/core/Database.php';

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS gelirgider CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database checked/created.\n";
    
    // Select database
    $pdo->exec("USE gelirgider");
    echo "Database selected.\n";
    
    // Read the schema file
    $sql = file_get_contents('database/schema.sql');
    
    // Split the SQL file into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                // Skip CREATE DATABASE and USE statements
                if (stripos($statement, 'CREATE DATABASE') === 0 || stripos($statement, 'USE') === 0) {
                    continue;
                }
                
                // Skip INSERT statements for users and categories
                if (stripos($statement, 'INSERT INTO users') === 0 || stripos($statement, 'INSERT INTO categories') === 0) {
                    echo "Skipping default data insertion...\n";
                    continue;
                }
                
                $pdo->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (Exception $e) {
                // If it's a duplicate entry error, just continue
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "Entry already exists, skipping...\n";
                    continue;
                }
                
                // If it's a table already exists error, just continue
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "Table already exists, skipping...\n";
                    continue;
                }
                
                echo "Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . $statement . "\n";
                throw $e;
            }
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    echo "Note: Default data (admin user and categories) was not inserted to preserve existing data.\n";
    
} catch (Exception $e) {
    echo "\nError setting up database: " . $e->getMessage() . "\n";
    exit(1);
} 