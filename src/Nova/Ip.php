<?php

namespace Haxibiao\Breeze\Nova;

use App\Nova\Article;
use App\Nova\Comment;
use App\Nova\User;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Resource;

class Ip extends Resource
{
    public static $model  = 'Haxibiao\Breeze\Ip';
    public static $title  = 'id';
    public static $search = [
        'id', 'ip',
    ];

    public static $group = '数据中心';
    public static function label()
    {
        return "IP地址";
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('IP地址', 'ip'),
            DateTime::make('创建时间', 'created_at'),
            BelongsTo::make('用户', 'user', User::class),
            MorphTo::make('用户行为', 'ipable')->types([
                Article::class,
                Comment::class,
                User::class,
            ]),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
