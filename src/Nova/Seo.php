<?php

namespace Haxibiao\Breeze\Nova;

use App\Nova\Resource;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;

class Seo extends Resource
{

    public static $model = 'App\Seo';

    public static $title = 'id';

    public static $group = '配置中心';

    public static function label()
    {
        return "Seo";
    }

    public static $search = [
        'id', 'name', 'group',
    ];

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('组', 'group'),
            Text::make('名称', 'name'),
            Text::make('值', 'value'),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [];
    }
}
