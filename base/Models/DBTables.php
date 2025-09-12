<?php

namespace Base\Models;

use Illuminate\Http\JsonResponse;

class DBTables
{
    protected $tableName;
    protected $namespace;
    public function __construct($tableName,$namespace){
        $this->tableName = $tableName;
        $this->namespace = $namespace;
    }

    public function createTableModel() : JsonResponse {
        $baseFolder = 'accounting';
        $tableName = str_replace(' ', '', ucwords(str_replace('_', ' ', $this->tableName)));
        $table = $this->namespace.'/'.$tableName;
        $make = 'php ../artisan make:model ../../'.$baseFolder.'/'.$table;
        shell_exec($make).PHP_EOL;
        $path = file_get_contents(base_path().'/'.$baseFolder.'/'.$table.'.php');
        $path = str_replace('App\Models\..\..\\'.$baseFolder, ucfirst($baseFolder), $path);
        file_put_contents(base_path().'/'.$baseFolder.'/'.$table.'.php', $path);
        return response()->json($path, 200);
    }
}
