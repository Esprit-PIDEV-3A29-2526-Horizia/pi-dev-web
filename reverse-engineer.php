<?php
// reverse-engineer.php - Version corrigée sans `else` et complexité réduite
// Exécuter : php reverse-engineer.php
// Modifier les paramètres de connexion ci-dessous

require_once 'vendor/autoload.php';

// Configuration de la base de données
$dbHost = 'localhost';
$dbName = 'horozia';
$dbUser = 'root';
$dbPass = '';
$dbPort = 3306;

// Namespace et dossier de sortie
$namespace = 'App\\Entity';
$outputDir = __DIR__ . '/src/Entity';

// Créer le dossier de sortie s'il n'existe pas
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion à la base de données réussie !\n";
} catch (PDOException $e) {
    die("Échec de la connexion : " . $e->getMessage() . "\n");
}

// Récupérer toutes les tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$tableInfo = [];
$foreignKeys = [];
$manyToManyTables = [];
$uniqueConstraints = [];

// Premier passage : collecte des informations
foreach ($tables as $table) {
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false) {
        continue;
    }

    echo "Analyse de la table : $table\n";

    // Nom de la classe (singularisé)
    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
    if (substr($className, -1) === 's' && substr($className, -2) !== 'ss') {
        $className = substr($className, 0, -1);
    }

    // Colonnes
    $stmtCol = $pdo->query("DESCRIBE `$table`");
    $columns = $stmtCol->fetchAll(PDO::FETCH_ASSOC);

    // Clé primaire
    $primaryKey = null;
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI') {
            $primaryKey = $col['Field'];
            break;
        }
    }

    $tableInfo[$table] = [
        'className' => $className,
        'columns' => $columns,
        'primaryKey' => $primaryKey
    ];

    // Clés étrangères
    try {
        $stmtFk = $pdo->query("
            SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$table' AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fks = $stmtFk->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fks as $fk) {
            $foreignKeys[$table][] = [
                'column' => $fk['COLUMN_NAME'],
                'refTable' => $fk['REFERENCED_TABLE_NAME'],
                'refColumn' => $fk['REFERENCED_COLUMN_NAME']
            ];
        }
    } catch (PDOException $e) {
        echo "Attention : impossible de récupérer les clés étrangères pour $table : " . $e->getMessage() . "\n";
    }

    // Contraintes uniques (pour OneToOne)
    try {
        $stmtUniq = $pdo->query("
            SELECT COLUMN_NAME, INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = '$dbName' AND TABLE_NAME = '$table' AND NON_UNIQUE = 0 AND INDEX_NAME != 'PRIMARY'
        ");
        $uniques = $stmtUniq->fetchAll(PDO::FETCH_ASSOC);
        foreach ($uniques as $unique) {
            $uniqueConstraints[$table][] = [
                'column' => $unique['COLUMN_NAME'],
                'indexName' => $unique['INDEX_NAME']
            ];
        }
    } catch (PDOException $e) {
        echo "Attention : impossible de récupérer les contraintes uniques pour $table : " . $e->getMessage() . "\n";
    }
}

// Détection des tables ManyToMany
foreach ($tables as $table) {
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false) {
        continue;
    }
    if (isset($foreignKeys[$table]) && count($foreignKeys[$table]) >= 2) {
        $columnsCount = count($tableInfo[$table]['columns']);
        $fkCount = count($foreignKeys[$table]);
        if ($fkCount >= 2 && $fkCount >= ($columnsCount - 2)) {
            echo "Table de jointure ManyToMany détectée : $table\n";
            $manyToManyTables[$table] = ['foreignKeys' => $foreignKeys[$table]];
        }
    }
}

// Génération des entités
foreach ($tables as $table) {
    if (strpos($table, 'migration') !== false || strpos($table, 'doctrine') !== false || isset($manyToManyTables[$table])) {
        continue;
    }

    echo "Génération de l'entité pour : $table\n";

    $className = $tableInfo[$table]['className'];
    $columns = $tableInfo[$table]['columns'];
    $primaryKey = $tableInfo[$table]['primaryKey'];

    $entityCode = "<?php\n\n";
    $entityCode .= "namespace $namespace;\n\n";
    $entityCode .= "use Doctrine\\ORM\\Mapping as ORM;\n";
    $entityCode .= "use Doctrine\\Common\\Collections\\ArrayCollection;\n";
    $entityCode .= "use Doctrine\\Common\\Collections\\Collection;\n\n";
    $entityCode .= "use App\\Repository\\" . $className . "Repository;\n\n";
    $entityCode .= "#[ORM\\Entity(repositoryClass: " . $className . "Repository::class)]\n";
    $entityCode .= "#[ORM\\Table(name: '$table')]\n";
    $entityCode .= "class $className\n";
    $entityCode .= "{\n";

    // Propriétés et relations
    foreach ($columns as $column) {
        $fieldName = $column['Field'];
        $fieldType = mapMySQLTypeToPhpType($column['Type']);
        $doctrineType = mapMySQLTypeToDoctrineType($column['Type']);

        $isForeignKey = false;
        $relationshipCode = "";

        if (isset($foreignKeys[$table])) {
            foreach ($foreignKeys[$table] as $fk) {
                if ($fk['column'] === $fieldName) {
                    $isForeignKey = true;
                    $refClassName = $tableInfo[$fk['refTable']]['className'];

                    $isOneToOne = false;
                    if (isset($uniqueConstraints[$table])) {
                        foreach ($uniqueConstraints[$table] as $unique) {
                            if ($unique['column'] === $fieldName) {
                                $isOneToOne = true;
                                break;
                            }
                        }
                    }

                    if ($isOneToOne) {
                        $relationshipCode .= "    #[ORM\\OneToOne(targetEntity: $refClassName::class, inversedBy: '" . lcfirst($className) . "')]\n";
                        $relationshipCode .= "    #[ORM\\JoinColumn(name: '$fieldName', referencedColumnName: '{$fk['refColumn']}', unique: true)]\n";
                        $relationshipCode .= "    private ?$refClassName \$" . lcfirst($refClassName) . " = null;\n\n";
                        $relationshipCode .= "    public function get$refClassName(): ?$refClassName\n    {\n        return \$this->" . lcfirst($refClassName) . ";\n    }\n\n";
                        $relationshipCode .= "    public function set$refClassName(?$refClassName \$" . lcfirst($refClassName) . "): self\n    {\n        \$this->" . lcfirst($refClassName) . " = \$" . lcfirst($refClassName) . ";\n        return \$this;\n    }\n\n";
                    } else {
                        $relationshipCode .= "    #[ORM\\ManyToOne(targetEntity: $refClassName::class, inversedBy: '" . lcfirst($className) . "s')]\n";
                        $relationshipCode .= "    #[ORM\\JoinColumn(name: '$fieldName', referencedColumnName: '{$fk['refColumn']}')]\n";
                        $relationshipCode .= "    private ?$refClassName \$" . lcfirst($refClassName) . " = null;\n\n";
                        $relationshipCode .= "    public function get$refClassName(): ?$refClassName\n    {\n        return \$this->" . lcfirst($refClassName) . ";\n    }\n\n";
                        $relationshipCode .= "    public function set$refClassName(?$refClassName \$" . lcfirst($refClassName) . "): self\n    {\n        \$this->" . lcfirst($refClassName) . " = \$" . lcfirst($refClassName) . ";\n        return \$this;\n    }\n\n";
                    }
                    break;
                }
            }
        }

        if (!$isForeignKey) {
            // Propriété simple
            $entityCode .= "    #[ORM\\";
            if ($fieldName === $primaryKey) {
                $entityCode .= "Id]\n    #[ORM\\GeneratedValue]\n    #[ORM\\Column(type: '$doctrineType')]\n";
            } else {
                $nullable = $column['Null'] === 'YES' ? 'true' : 'false';
                $entityCode .= "Column(type: '$doctrineType', nullable: $nullable)]\n";
            }
            $entityCode .= "    private ?$fieldType \$$fieldName = null;\n\n";

            // Getter
            $getterMethod = ($fieldType === 'bool') ? (str_starts_with($fieldName, 'is_') ? $fieldName : 'is' . ucfirst($fieldName)) : 'get' . ucfirst($fieldName);
            $entityCode .= "    public function $getterMethod(): ?$fieldType\n    {\n        return \$this->$fieldName;\n    }\n\n";

            // Setter
            $setterMethod = 'set' . ucfirst($fieldName);
            $entityCode .= "    public function $setterMethod(";
            if ($column['Null'] === 'YES') {
                $entityCode .= "?";
            }
            $entityCode .= "$fieldType \$$fieldName): self\n    {\n        \$this->$fieldName = \$$fieldName;\n        return \$this;\n    }\n\n";
        } else {
            $entityCode .= $relationshipCode;
        }
    }

    // Relations inverses (OneToMany et OneToOne inverses)
    foreach ($tables as $otherTable) {
        if (isset($foreignKeys[$otherTable]) && !isset($manyToManyTables[$otherTable])) {
            foreach ($foreignKeys[$otherTable] as $fk) {
                if ($fk['refTable'] === $table) {
                    $otherClassName = $tableInfo[$otherTable]['className'];

                    $isOneToOne = false;
                    if (isset($uniqueConstraints[$otherTable])) {
                        foreach ($uniqueConstraints[$otherTable] as $unique) {
                            if ($unique['column'] === $fk['column']) {
                                $isOneToOne = true;
                                break;
                            }
                        }
                    }

                    if ($isOneToOne) {
                        $entityCode .= "    #[ORM\\OneToOne(targetEntity: $otherClassName::class, mappedBy: '" . lcfirst($className) . "')]\n";
                        $entityCode .= "    private ?$otherClassName \$" . lcfirst($otherClassName) . " = null;\n\n";
                        $entityCode .= "    public function get$otherClassName(): ?$otherClassName\n    {\n        return \$this->" . lcfirst($otherClassName) . ";\n    }\n\n";
                        $entityCode .= "    public function set$otherClassName(?$otherClassName \$" . lcfirst($otherClassName) . "): self\n    {\n        \$this->" . lcfirst($otherClassName) . " = \$" . lcfirst($otherClassName) . ";\n        return \$this;\n    }\n\n";
                    } else {
                        $collectionVar = lcfirst($otherClassName) . 's';
                        $entityCode .= "    #[ORM\\OneToMany(targetEntity: $otherClassName::class, mappedBy: '" . lcfirst($className) . "')]\n";
                        $entityCode .= "    private Collection \$$collectionVar;\n\n";
                        $entityCode .= "    /**\n     * @return Collection<int, $otherClassName>\n     */\n";
                        $entityCode .= "    public function get" . ucfirst($collectionVar) . "(): Collection\n    {\n";
                        $entityCode .= "        if (!\$this->$collectionVar instanceof Collection) {\n";
                        $entityCode .= "            \$this->$collectionVar = new ArrayCollection();\n        }\n";
                        $entityCode .= "        return \$this->$collectionVar;\n    }\n\n";
                        $entityCode .= "    public function add" . ucfirst($otherClassName) . "($otherClassName \$$otherClassName): self\n    {\n";
                        $entityCode .= "        if (!\$this->get" . ucfirst($collectionVar) . "()->contains(\$$otherClassName)) {\n";
                        $entityCode .= "            \$this->get" . ucfirst($collectionVar) . "()->add(\$$otherClassName);\n        }\n";
                        $entityCode .= "        return \$this;\n    }\n\n";
                        $entityCode .= "    public function remove" . ucfirst($otherClassName) . "($otherClassName \$$otherClassName): self\n    {\n";
                        $entityCode .= "        \$this->get" . ucfirst($collectionVar) . "()->removeElement(\$$otherClassName);\n";
                        $entityCode .= "        return \$this;\n    }\n\n";
                    }
                }
            }
        }
    }

    // Relations ManyToMany
    foreach ($manyToManyTables as $joinTable => $joinInfo) {
        $fks = $joinInfo['foreignKeys'];
        $thisFk = null;
        $otherFk = null;
        $otherTableName = null;
        $otherClassName = null;

        foreach ($fks as $fk) {
            if ($fk['refTable'] === $table) {
                $thisFk = $fk;
            } else {
                $otherFk = $fk;
                $otherTableName = $fk['refTable'];
                $otherClassName = $tableInfo[$otherTableName]['className'];
            }
        }

        if ($thisFk && $otherFk && $otherClassName) {
            $collectionVar = lcfirst($otherClassName) . 's';
            $entityCode .= "    #[ORM\\ManyToMany(targetEntity: $otherClassName::class, inversedBy: '" . lcfirst($className) . "s')]\n";
            $entityCode .= "    #[ORM\\JoinTable(\n        name: '$joinTable',\n";
            $entityCode .= "        joinColumns: [new ORM\\JoinColumn(name: '{$thisFk['column']}', referencedColumnName: '{$thisFk['refColumn']}')],\n";
            $entityCode .= "        inverseJoinColumns: [new ORM\\JoinColumn(name: '{$otherFk['column']}', referencedColumnName: '{$otherFk['refColumn']}')]\n";
            $entityCode .= "    )]\n";
            $entityCode .= "    private Collection \$$collectionVar;\n\n";
            $entityCode .= "    /**\n     * @return Collection<int, $otherClassName>\n     */\n";
            $entityCode .= "    public function get" . ucfirst($collectionVar) . "(): Collection\n    {\n";
            $entityCode .= "        if (!\$this->$collectionVar instanceof Collection) {\n";
            $entityCode .= "            \$this->$collectionVar = new ArrayCollection();\n        }\n";
            $entityCode .= "        return \$this->$collectionVar;\n    }\n\n";
            $entityCode .= "    public function add" . ucfirst($otherClassName) . "($otherClassName \$$otherClassName): self\n    {\n";
            $entityCode .= "        if (!\$this->get" . ucfirst($collectionVar) . "()->contains(\$$otherClassName)) {\n";
            $entityCode .= "            \$this->get" . ucfirst($collectionVar) . "()->add(\$$otherClassName);\n        }\n";
            $entityCode .= "        return \$this;\n    }\n\n";
            $entityCode .= "    public function remove" . ucfirst($otherClassName) . "($otherClassName \$$otherClassName): self\n    {\n";
            $entityCode .= "        \$this->get" . ucfirst($collectionVar) . "()->removeElement(\$$otherClassName);\n";
            $entityCode .= "        return \$this;\n    }\n\n";
        }
    }

    $entityCode .= "}\n";

    $filePath = "$outputDir/$className.php";
    file_put_contents($filePath, $entityCode);
    echo "Entité générée : $filePath\n";
}

echo "Génération des entités terminée avec toutes les relations (OneToOne, OneToMany, ManyToOne, ManyToMany) !\n";

// Fonctions de mapping sans `else`
function mapMySQLTypeToPhpType($mysqlType)
{
    if (strpos($mysqlType, 'tinyint(1)') !== false) {
        return 'bool';
    }
    if (strpos($mysqlType, 'int') !== false) {
        return 'int';
    }
    if (strpos($mysqlType, 'float') !== false || strpos($mysqlType, 'double') !== false || strpos($mysqlType, 'decimal') !== false) {
        return 'float';
    }
    if (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) {
        return '\\DateTimeInterface';
    }
    if (strpos($mysqlType, 'date') !== false) {
        return '\\DateTimeInterface';
    }
    if (strpos($mysqlType, 'blob') !== false || strpos($mysqlType, 'binary') !== false) {
        return 'string';
    }
    return 'string';
}

function mapMySQLTypeToDoctrineType($mysqlType)
{
    if (strpos($mysqlType, 'tinyint(1)') !== false) {
        return 'boolean';
    }
    if (strpos($mysqlType, 'int') !== false) {
        return 'integer';
    }
    if (strpos($mysqlType, 'float') !== false) {
        return 'float';
    }
    if (strpos($mysqlType, 'double') !== false || strpos($mysqlType, 'decimal') !== false) {
        return 'decimal';
    }
    if (strpos($mysqlType, 'datetime') !== false || strpos($mysqlType, 'timestamp') !== false) {
        return 'datetime';
    }
    if (strpos($mysqlType, 'date') !== false) {
        return 'date';
    }
    if (strpos($mysqlType, 'time') !== false) {
        return 'time';
    }
    if (strpos($mysqlType, 'blob') !== false || strpos($mysqlType, 'binary') !== false) {
        return 'blob';
    }
    if (strpos($mysqlType, 'text') !== false) {
        return 'text';
    }
    return 'string';
}