<?php

namespace Haxibiao\Breeze\Nova\Actions\User;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\Actionable;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Nova;

class UpdateUserRole extends Action
{

    public $name = '用户角色变更';
    use InteractsWithQueue, Queueable, SerializesModels, Actionable;

    public function uriKey()
    {
        return str_slug(Nova::humanize($this));
    }

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if (!isset($fields->type) and !isset($fields->role_id)) {
            return Action::danger('不能为空');
        }

        DB::beginTransaction();
        try {
            foreach ($models as $model) {
                $model->role_id = $fields->role_id;
                $model->save();
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            DB::rollBack();
            return Action::danger('数据批量变更失败，数据回滚');
        }
        DB::commit();

    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Select::make('角色', 'role_id')->options(
                User::getRolesMap()
            ),
        ];
    }
}
