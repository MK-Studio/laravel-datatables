<?php

namespace MKStudio\LaravelDatatables;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LaravelDatatablesController extends Controller
{

    protected $model = null;

    protected $columns = [];

    protected $lengthMenu = [10, 25, 50, 100];

    protected $defaultOrder = [
        'column_index' => 0,
        'direction' => 'DESC'
    ];

    /**
     * @param Model $model
     * @return QueryBuilder
     */
    public function eloquentBuilder(Model $model)
    {
        $columns = $this->getColumns()->pluck('dr_column')->toArray();
        $builder = $model::query();
        $builder->select($columns);
        $builder = $this->applyJoins($builder);
        return $builder;
    }

    /**
     * @return Collection
     */
    public function getColumns()
    {
        return collect($this->columns)->map(function ($item, $index) {
            if (is_array($item)) {
                return [
                    'dr_column' => $index,
                    'db_column' => str_contains($index, ' as ')
                        ? strstr($index, ' as ', true)
                        : $index,
                    'dt_column' => str_contains($index, ' as ')
                        ? substr($index, strpos($index, " as ") + 4)
                        : substr($index, strpos($index, ".") + 1),
                    'orderable' => isset($item['orderable']) ? $item['orderable'] : true,
                    'searchable' => isset($item['searchable']) ? $item['searchable'] : true,
                ];
            }
            return [
                'dr_column' => $item,
                'db_column' => str_contains($item, ' as ')
                    ? strstr($item, ' as ', true)
                    : $item,
                'dt_column' => str_contains($item, ' as ')
                    ? substr($item, strpos($item, " as ") + 4)
                    : (!strpos($item, ".") ? $item
                        : substr($item, strpos($item, ".") + 1)),
                'orderable' => true,
                'searchable' => true,
            ];
        })->values();
    }

    /**
     * @param EloquentBuilder $builder
     * @return QueryBuilder
     */
    public function applyJoins(EloquentBuilder $builder)
    {
        return $builder;
    }

    /**
     * @param $view
     * @return View | array
     */
    public function render($view)
    {
        if (request()->ajax()) {

            $search = request()->get('search');
            $start = (int) request()->get('start');
            $length = (int) request()->get('length');
            $order = request()->get('order');

            $builder = $this->eloquentBuilder($this->model);

            $totalCount = $builder->count();

            $builderColumns = $this->getColumns()->pluck('db_column');

            if (isset($search['value'])) {
                $searchValue = $search['value'];
                collect($builderColumns)->each(function($column, $index) use ($builder, $searchValue) {
                    $builder->orWhere($column, 'LIKE', "%" . $searchValue . "%");
                });
            }

            if (isset($order[0])) {
                $builder->orderBy($builderColumns[$order[0]['column']], $order[0]['dir']);
            }

            $filteredCount = $builder->count();

            $builder->skip($start);
            $builder->take($length);

            $data = $builder->get();

            return [
                'draw' => intval(request()->get('draw')),
                'recordsFiltered' => $filteredCount,
                'recordsTotal' => $totalCount,
                'data' => $data
            ];
        }

        $datatableColumns =  $this->getColumns()->pluck('dt_column')->map(function($item) {
            return ['data' => $item];
        })->toJson();

        $defaultOrder = [[$this->defaultOrder['column_index'], strtolower($this->defaultOrder['direction'])]];

        return view($view, [
            'columns' => $this->getColumns(),
            'datatableColumns' => $datatableColumns,
            'route' => route('index'),
            'lengthMenu' => json_encode($this->lengthMenu),
            'defaultOrder' => json_encode($defaultOrder)
        ]);

    }
}
