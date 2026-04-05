<?php
// import.php

$host = '127.0.0.1';
$dbname = 'horizia';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer toutes les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables trouvées : " . implode(', ', $tables) . "\n\n";
    
    foreach ($tables as $table) {
        echo "Création de l'entité pour : $table\n";
        
        // Récupérer la structure de la table
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer les clés étrangères
        $foreignKeys = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '$dbname'
            AND TABLE_NAME = '$table'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        // Générer le nom de la classe
        $className = ucfirst($table);
        
        // Générer le contenu du fichier
        $content = "<?php\n\n";
        $content .= "namespace App\Entity;\n\n";
        $content .= "use Doctrine\\ORM\\Mapping as ORM;\n\n";
        $content .= "#[ORM\\Entity]\n";
        $content .= "#[ORM\\Table(name: '$table')]\n";
        $content .= "class $className\n";
        $content .= "{\n";
        
        // Ajouter les propriétés
        foreach ($columns as $column) {
            $field = $column['Field'];
            $type = $column['Type'];
            $nullable = $column['Null'] === 'YES';
            $isPrimary = $column['Key'] === 'PRI';
            
            $phpType = mapSqlTypeToPhp($type);
            
            if ($isPrimary) {
                $content .= "    #[ORM\\Id]\n";
                $content .= "    #[ORM\\GeneratedValue]\n";
                $content .= "    #[ORM\\Column(name: '$field', type: 'integer')]\n";
                $content .= "    private ?int \$$field = null;\n\n";
            } else {
                // Vérifier si c'est une clé étrangère
                $isForeignKey = false;
                foreach ($foreignKeys as $fk) {
                    if ($fk['COLUMN_NAME'] === $field) {
                        $isForeignKey = true;
                        $targetClass = ucfirst($fk['REFERENCED_TABLE_NAME']);
                        $content .= "    #[ORM\\ManyToOne(targetEntity: $targetClass::class)]\n";
                        $content .= "    #[ORM\\JoinColumn(name: '$field', referencedColumnName: '{$fk['REFERENCED_COLUMN_NAME']}')]\n";
                        $content .= "    private ?$targetClass \$$field = null;\n\n";
                        break;
                    }
                }
                
                if (!$isForeignKey) {
                    $doctrineType = mapSqlToDoctrineType($type);
                    $content .= "    #[ORM\\Column(name: '$field', type: '$doctrineType'";
                    if ($nullable) {
                        $content .= ", nullable: true";
                    }
                    if (strpos($type, 'varchar') !== false) {
                        preg_match('/varchar\((\d+)\)/', $type, $matches);
                        if (isset($matches[1])) {
                            $content .= ", length: {$matches[1]}";
                        }
                    }
                    if ($type === 'text') {
                        $content .= ", length: 65535";
                    }
                    $content .= ")]\n";
                    $content .= "    private ?$phpType \$$field = null;\n\n";
                }
            }
        }
        
        // Générer les getters et setters
        foreach ($columns as $column) {
            $field = $column['Field'];
            $type = $column['Type'];
            $phpType = mapSqlTypeToPhp($type);
            $isPrimary = $column['Key'] === 'PRI';
            
            // Vérifier si c'est une clé étrangère
            $isForeignKey = false;
            $targetClass = null;
            foreach ($foreignKeys as $fk) {
                if ($fk['COLUMN_NAME'] === $field) {
                    $isForeignKey = true;
                    $targetClass = ucfirst($fk['REFERENCED_TABLE_NAME']);
                    break;
                }
            }
            
            if ($isPrimary) {
                $content .= "    public function getId" . ucfirst($field) . "(): ?int\n";
                $content .= "    {\n";
                $content .= "        return \$this->$field;\n";
                $content .= "    }\n\n";
            } elseif ($isForeignKey) {
                $content .= "    public function get" . ucfirst($field) . "(): ?$targetClass\n";
                $content .= "    {\n";
                $content .= "        return \$this->$field;\n";
                $content .= "    }\n\n";
                $content .= "    public function set" . ucfirst($field) . "(?$targetClass \$$field): static\n";
                $content .= "    {\n";
                $content .= "        \$this->$field = \$$field;\n";
                $content .= "        return \$this;\n";
                $content .= "    }\n\n";
            } else {
                $content .= "    public function get" . ucfirst($field) . "(): ?$phpType\n";
                $content .= "    {\n";
                $content .= "        return \$this->$field;\n";
                $content .= "    }\n\n";
                $content .= "    public function set" . ucfirst($field) . "($phpType \$$field): static\n";
                $content .= "    {\n";
                $content .= "        \$this->$field = \$$field;\n";
                $content .= "        return \$this;\n";
                $content .= "    }\n\n";
            }
        }
        
        $content .= "}\n";
        
        // Créer le dossier si nécessaire
        if (!is_dir('src/Entity')) {
            mkdir('src/Entity', 0777, true);
        }
        
        // Écrire le fichier
        file_put_contents("src/Entity/$className.php", $content);
        echo "✅ Créé : src/Entity/$className.php\n\n";
    }
    
    echo "\n🎉 Toutes les entités ont été créées !\n";
    echo "📌 Exécute maintenant : php bin/console make:entity --regenerate\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

function mapSqlTypeToPhp($sqlType) {
    if (strpos($sqlType, 'int') !== false) return 'int';
    if (strpos($sqlType, 'float') !== false || strpos($sqlType, 'double') !== false) return 'float';
    if (strpos($sqlType, 'decimal') !== false) return 'float';
    if (strpos($sqlType, 'datetime') !== false) return '\\DateTimeInterface';
    if (strpos($sqlType, 'text') !== false) return 'string';
    return 'string';
}

function mapSqlToDoctrineType($sqlType) {
    if (strpos($sqlType, 'int') !== false) return 'integer';
    if (strpos($sqlType, 'float') !== false) return 'float';
    if (strpos($sqlType, 'double') !== false) return 'float';
    if (strpos($sqlType, 'decimal') !== false) return 'decimal';
    if (strpos($sqlType, 'datetime') !== false) return 'datetime';
    if (strpos($sqlType, 'text') !== false) return 'text';
    if (strpos($sqlType, 'varchar') !== false) return 'string';
    return 'string';
}