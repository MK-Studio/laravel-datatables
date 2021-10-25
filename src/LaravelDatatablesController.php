<?php

namespace MKStudio\LaravelDatatables;

use App\Http\Controllers\Controller;

class LaravelDatatablesController extends Controller
{

    protected $model = null;

    protected $columns = [];

    protected $lengthMenu = [10, 25, 50, 100];

    protected $defaultOrder = [
        'column_index' => 0,
        'direction' => 'DESC'
    ];

}
