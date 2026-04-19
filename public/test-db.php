<?php
// public/test-db.php

try {
    // Connexion directe à MySQL
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=horizia;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>✅ Connexion à la base réussie !</h1>";
    
    // Tester une requête
    $result = $pdo->query("SELECT 1 as test")->fetch();
    echo "<p>Test SELECT 1 : " . $result['test'] . "</p>";
    
    // Afficher les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables dans la base 'horizia' :</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Compter les véhicules
    $count = $pdo->query("SELECT COUNT(*) FROM vehicule")->fetchColumn();
    echo "<p>Nombre de véhicules : $count</p>";
    
} catch (PDOException $e) {
    echo "<h1 style='color:red'>❌ Erreur de connexion</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}