<?php

namespace Haxibiao\Breeze\Traits;

use App\SignIn;

trait DimensionTrendResolver
{
    public function resolveUsersTrend($root, $args, $context, $info)
    {
        $range = data_get($args, 'range', 7);
        $data  = get_users_trend($range);

        return [
            'name'    => '用户增长趋势',
            'summary' => [
                'max'       => max($data),
                'yesterday' => array_values($data)[$range - 2],
            ],
            'data'    => $data,
        ];
    }

    public function resolvePostsTrend($root, $args, $context, $info)
    {
        $range = data_get($args, 'range', 7);
        $data  = get_posts_trend($range);

        return [
            'name'    => '动态增长趋势',
            'summary' => [
                'max'       => max($data),
                'yesterday' => array_values($data)[$range - 2],
            ],
            'data'    => $data,
        ];
    }

    public function resolveCommentsTrend($root, $args, $context, $info)
    {
        $range = data_get($args, 'range', 7);
        $data  = get_comments_trend($range);

        return [
            'name'    => '评论增长趋势',
            'summary' => [
                'max'       => max($data),
                'yesterday' => array_values($data)[$range - 2],
            ],
            'data'    => $data,
        ];
    }

    public function resolveActiveUsersTrend($root, $args, $context, $info)
    {
        $range = data_get($args, 'range', 7);

        return $this->buildTrend($this->groupByDay(new SignIn, $range), '活跃用户趋势');

    }

    public function resolveMockTrend($root, $args, $context, $info)
    {
        $range = data_get($args, 'range', 7);
        $data  = $this->initData($range);

        return $this->buildTrend($data, 'mock trend:' . $info->fieldName);
    }

    public function resolveMockPartition($root, $args, $context, $info)
    {
        $range = data_get($args, 'range', 7);
        for ($j = $range - 1; $j >= 0; $j--) {
            $intervalDate = date('Y-m-d', strtotime(now() . '-' . $j . 'day'));
            $data[]       = [
                'name'  => $intervalDate,
                'value' => mt_rand(1, 30),
            ];
        }
        $result = [
            'name' => 'mock partition:' . $info->fieldName,
            'data' => $data,
        ];

        return $result;
    }

    public function initData($range)
    {
        for ($j = $range - 1; $j >= 0; $j--) {
            $intervalDate        = date('Y-m-d', strtotime(now() . '-' . $j . 'day'));
            $data[$intervalDate] = 0;
        }

        return $data;
    }

    public function buildTrend(array $data, $name = '')
    {
        return [
            'name'    => $name,
            'summary' => [
                'max'       => max($data),
                'yesterday' => $data[today()->subDay()->toDateString()] ?? 0,
            ],
            'data'    => $data,
        ];
    }

    public function resolveBusinessDimension()
    {
        $data = [
            [
                'name'  => '昨日新增用户',
                'value' => mt_rand(1, 99),
                'tips'  => '累计用户:99999',
                'style' => 1,
            ],
            [
                'name'  => '昨日收益(元)',
                'value' => mt_rand(1, 99),
                'tips'  => '累计收益:99999',
                'style' => 2,
            ],
            [
                'name'  => '用户次日留存率',
                'value' => mt_rand(1, 99),
                'tips'  => '七日留存率:33%',
                'style' => 3,
            ],
        ];

        return $data;
    }
}
