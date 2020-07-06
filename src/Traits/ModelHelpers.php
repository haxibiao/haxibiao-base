<?php

namespace Haxibiao\Base\Traits;

use Illuminate\Support\Facades\Auth;

trait ModelHelpers
{
    private $cachedAttributes = [];

    //只保存数据，不更新时间
    public function saveDataOnly()
    {
        //获取model里面的事件
        $dispatcher = self::getEventDispatcher();

        //不触发事件
        self::unsetEventDispatcher();
        $this->timestamps = false;
        $this->save();

        //启用事件
        self::setEventDispatcher($dispatcher);
    }

    //is self
    public function isSelf()
    {
        return isset($this->user_id) && Auth::check() && Auth::id() == $this->user_id;
    }
    public function isOfUser($user)
    {
        return $user && $user->id == $this->user_id;
    }

    //减少mysql select 返回text等大型字段用...
    public function scopeExclude($query, $value = [])
    {
        return $query->select(array_diff($this->getTableColumns(), (array) $value));
    }

    //自动获取当前model的所有columns
    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    public function scopeToday($query, $column = 'created_at')
    {
        return $query->where($column, '>=', today());
    }

    public function scopeThisWeek($query, $column = 'created_at')
    {
        return $query->where($column, '>=', today()->subDay(7));
    }

    public function scopeThisMonth($query, $column = 'created_at')
    {
        return $query->where($column, '>=', today()->subDay(30));
    }

    public function getCachedAttribute(string $key, callable $callable)
    {
        if (!array_key_exists($key, $this->cachedAttributes)) {
            $this->setCachedAttribute($key, call_user_func($callable));
        }

        return $this->cachedAttributes[$key];
    }

    public function setCachedAttribute(string $key, $value)
    {
        return $this->cachedAttributes[$key] = $value;
    }

    public function refresh()
    {
        unset($this->cachedAttributes);

        return parent::refresh();
    }

    //time的aliases 以前很多很多旧项目代码用过
    public function getTimeAgoAttribute()
    {
        return diffForHumansCN($this->created_at);
    }

    public function timeAgo()
    {
        return diffForHumansCN($this->created_at);
    }

    public function createdAt()
    {
        return diffForHumansCN($this->created_at);
    }

    public function updatedAt()
    {
        return diffForHumansCN($this->updated_at);
    }

    public function editedAt()
    {
        return diffForHumansCN($this->edited_at);
    }

    //旧项目读写json字段用过，兼容
    public function jsonData($key = null)
    {
        if (!empty($this->json) && is_string($this->json)) {
            $jsonData = json_decode($this->json, true);
            if (empty($jsonData)) {
                $jsonData = [];
            }

            if (!empty($key)) {
                if (array_key_exists($key, $jsonData)) {
                    return $jsonData[$key];
                }
                return null;
            }
            return $jsonData;
        }
    }

    public function setJsonData($key, $value)
    {
        $data       = (array) $this->json;
        $data[$key] = $value;
        $this->json = $data;

        return $this;
    }
}
