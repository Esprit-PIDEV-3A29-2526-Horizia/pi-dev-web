<?php
// public/test-simple.php

try {
    // Test de connexion directe PDO
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=horizia;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM vehicule LIMIT 10");
    $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Test PDO - Horizia</title>
        <style>
            body { font-family: Arial; background: #F3F6FA; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 16px; padding: 20px; }
            h1 { color: #23779C; text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #23779C; color: white; padding: 12px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚗 Véhicules (test PDO direct)</h1>
            <div class="success">✅ Connexion réussie ! <?= count($vehicules) ?> véhicule(s)</div>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Immatriculation</th>
                    <th>Année</th>
                    <th>Carburant</th>
                    <th>Prix</th>
                    <th>État</th>
                </tr>
                <?php foreach ($vehicules as $v): ?>
                <tr>
                    <td><?= $v['id_vehicule'] ?></td>
                    <td><?= htmlspecialchars($v['immatriculation']) ?></td>
                    <td><?= $v['annee'] ?></td>
                    <td><?= $v['carburant'] ?></td>
                    <td><?= $v['prix_par_jour'] ?> TND</td>
                    <td><?= $v['etat'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </body>
    </html>
    <?php
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px;'>❌ Erreur : " . $e->getMessage() . "</div>";
}