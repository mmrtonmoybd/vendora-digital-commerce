<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function phpType(string $dbType, bool $nullable): string
{
    $base = match (true) {
        str_contains($dbType, 'int') => 'int',
        str_contains($dbType, 'bool') || str_contains($dbType, 'tinyint(1)') => 'bool',
        str_contains($dbType, 'decimal'), str_contains($dbType, 'float'), str_contains($dbType, 'double') => 'float',
        str_contains($dbType, 'json') => 'array',
        str_contains($dbType, 'date'), str_contains($dbType, 'time') => '\Illuminate\Support\Carbon',
        default => 'string',
    };

    return $nullable ? "?{$base}" : $base;
}

function castType(string $cast): ?string
{
    if (class_exists($cast) && (enum_exists($cast) || is_subclass_of($cast, BackedEnum::class))) {
        return '\\'.ltrim($cast, '\\');
    }

    return match (true) {
        $cast === 'int' || $cast === 'integer' => 'int',
        $cast === 'bool' || $cast === 'boolean' => 'bool',
        $cast === 'float' || $cast === 'double' || str_starts_with($cast, 'decimal') => 'float',
        $cast === 'array' || $cast === 'json' || $cast === 'collection' => 'array',
        $cast === 'datetime' || $cast === 'date' || str_starts_with($cast, 'datetime:') || str_starts_with($cast, 'immutable_datetime') => '\Illuminate\Support\Carbon',
        enum_exists($cast) => '\\'.ltrim($cast, '\\'),
        class_exists($cast) => '\\'.ltrim($cast, '\\'),
        default => null,
    };
}

$updated = 0;
foreach (glob('app/Models/*.php') as $file) {
    $class = 'App\\Models\\'.basename($file, '.php');
    if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
        continue;
    }

    /** @var Model $model */
    $model = new $class;
    $table = $model->getTable();
    if (! Schema::hasTable($table)) {
        continue;
    }

    $casts = $model->getCasts();
    $lines = [];

    foreach (DB::select("SHOW COLUMNS FROM `{$table}`") as $col) {
        $name = $col->Field;
        $nullable = $col->Null === 'YES';
        $type = isset($casts[$name]) ? castType($casts[$name]) : null;
        $type ??= phpType($col->Type, $nullable);
        if ($nullable && ! str_starts_with($type, '?') && $type !== 'mixed') {
            $type = '?'.$type;
        }
        $lines[] = " * @property {$type} \${$name}";
    }

    if ($lines === []) {
        continue;
    }

    $src = file_get_contents($file);

    // Replace an existing generated block, or insert one above the class declaration.
    $block = "/**\n * @generated Property annotations, refreshed by scripts/model-properties.php.\n"
        ." * Larastan cannot read casts declared in a casts() method on a \$guarded model, so the\n"
        ." * enum and date types have to be written down for it — and the IDE benefits too.\n"
        ." *\n".implode("\n", $lines)."\n */\n";

    $src = preg_replace('#/\*\*\n \* @generated Property annotations.*?\*/\n#s', '', $src);

    if (! preg_match('/^(final )?class \w+ extends/m', $src, $m, PREG_OFFSET_CAPTURE)) {
        continue;
    }
    $pos = $m[0][1];
    $src = substr($src, 0, $pos).$block.substr($src, $pos);

    file_put_contents($file, $src);
    $updated++;
}
echo "annotated: {$updated}\n";
