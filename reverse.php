<?php

require_once 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname'   => 'horizia',
    'user'     => 'root',
    'password' => '',
    'host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
];
$conn = DriverManager::getConnection($connectionParams);
$sm = $conn->createSchemaManager();

$ignoreTables = ['doctrine_migration_versions', 'messenger_messages'];
$tables = array_filter($sm->listTableNames(), fn($t) => !in_array($t, $ignoreTables));

foreach ($tables as $tableName) {
    echo "Génération de l'entité pour : $tableName\n";
    $columns = $sm->listTableColumns($tableName);
    $foreignKeys = $sm->listTableForeignKeys($tableName);
    
    $className = ucfirst($tableName);
    $namespace = 'App\\Entity';
    $php = "<?php\n\nnamespace $namespace;\n\nuse Doctrine\\ORM\\Mapping as ORM;\n";
    
    // Use statements pour les relations
    $uses = [];
    foreach ($foreignKeys as $fk) {
        $relatedTable = $fk->getForeignTableName();
        $relatedClass = ucfirst($relatedTable);
        $uses[] = "use $namespace\\$relatedClass;";
    }
    if (!empty($uses)) {
        $php .= "\n" . implode("\n", array_unique($uses)) . "\n";
    }
    
    $php .= "\n#[ORM\\Table(name: \"$tableName\")]\n#[ORM\\Entity]\nclass $className\n{\n";
    
    // Propriétés (colonnes)
    foreach ($columns as $column) {
        $name = $column->getName();
        // Récupération du type Doctrine DBAL
        $typeObj = $column->getType();
        // Récupération du nom du type via getTypeRegistry si nécessaire
        if (method_exists($typeObj, 'getName')) {
            $type = $typeObj->getName();
        } else {
            // Fallback: extraire le nom depuis la classe
            $classPath = explode('\\', get_class($typeObj));
            $type = strtolower(end($classPath));
        }
        
        $nullable = !$column->getNotnull();
        $length = '';
        if ($type === 'string' && $column->getLength()) {
            $length = ", length: {$column->getLength()}";
        }
        
        $phpType = match($type) {
            'integer', 'smallint', 'bigint' => 'int',
            'string', 'text', 'guid' => 'string',
            'datetime_immutable', 'datetimetz_immutable' => '\\DateTimeImmutable',
            'date_immutable' => '\\DateTimeImmutable',
            'boolean' => 'bool',
            'float' => 'float',
            default => 'mixed',
        };
        
        $php .= "    #[ORM\\Column(name: \"$name\", type: \"$type\"$length" . ($nullable ? ", nullable: true" : '') . ")]\n";
        $php .= "    private \${$name};\n\n";
    }
    
    // Relations ManyToOne
    foreach ($foreignKeys as $fk) {
        $localColumns = $fk->getLocalColumns();
        $foreignTable = $fk->getForeignTableName();
        $foreignClass = ucfirst($foreignTable);
        $localColumn = $localColumns[0];
        $propertyName = lcfirst($foreignClass);
        $php .= "    #[ORM\\ManyToOne(targetEntity: $foreignClass::class)]\n";
        $php .= "    #[ORM\\JoinColumn(name: \"$localColumn\", referencedColumnName: \"id\", nullable: false)]\n";
        $php .= "    private \${$propertyName};\n\n";
    }
    
    // Getters et setters
    foreach ($columns as $column) {
        $name = $column->getName();
        $ucName = ucfirst($name);
        $typeObj = $column->getType();
        if (method_exists($typeObj, 'getName')) {
            $type = $typeObj->getName();
        } else {
            $classPath = explode('\\', get_class($typeObj));
            $type = strtolower(end($classPath));
        }
        $phpType = match($type) {
            'integer', 'smallint', 'bigint' => 'int',
            'string', 'text', 'guid' => 'string',
            'datetime_immutable', 'datetimetz_immutable' => '\\DateTimeImmutable',
            'date_immutable' => '\\DateTimeImmutable',
            'boolean' => 'bool',
            'float' => 'float',
            default => 'mixed',
        };
        $php .= "    public function get$ucName(): ?$phpType { return \$this->$name; }\n";
        $php .= "    public function set$ucName($phpType \$$name): self { \$this->$name = \$$name; return \$this; }\n\n";
    }
    foreach ($foreignKeys as $fk) {
        $foreignTable = $fk->getForeignTableName();
        $foreignClass = ucfirst($foreignTable);
        $propertyName = lcfirst($foreignClass);
        $php .= "    public function get$foreignClass(): ?$foreignClass { return \$this->$propertyName; }\n";
        $php .= "    public function set$foreignClass(?$foreignClass \$$propertyName): self { \$this->$propertyName = \$$propertyName; return \$this; }\n\n";
    }
    
    $php .= "}\n";
    
    $filename = "src/Entity/$className.php";
    file_put_contents($filename, $php);
    echo "  -> $filename créé\n";
}

echo "\n✅ Toutes les entités ont été générées !\n";
echo "⚠️  Les relations OneToMany ne sont pas générées automatiquement.\n";