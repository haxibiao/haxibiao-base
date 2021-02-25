<?php

namespace Haxibiao\Breeze\Traits;

use App\Gold;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Haxibiao\Breeze\Exceptions\GQLException;
use Haxibiao\Breeze\Ip;
use Haxibiao\Breeze\User;
use Haxibiao\Breeze\UserProfile;
use Haxibiao\Task\Task;
use Haxibiao\Task\UserTask;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

trait UserResolvers
{
    public static function hasRewardResolver($root, array $args, $context, $info)
    {
        $user_id = data_get($args, 'user_id');
        $remark  = data_get($args, 'remark');
        return self::hasReward($user_id, $remark);
    }

    public static function resolveReward($root, array $args, $context, $info)
    {
        $user   = getUser();
        $reason = $args['reason'];

        $rewardValues = data_get(self::getUserRewardEnum(), $reason . '.value');
        $rewardReason = data_get(self::getUserRewardEnum(), $reason . '.description');

        app_track_event("奖励", $rewardReason);
        return User::userReward($user, $rewardValues);

    }

    public static function getUserRewardEnum()
    {
        return [
            'NEW_USER_REWARD'      => [
                'value'       => [
                    'gold'   => Gold::NEW_USER_GOLD,
                    'remark' => '新人注册奖励',
                    'action' => 'NEW_USER_REWARD',
                ],
                'description' => '新人注册奖励',
            ],
            'NEW_YEAR_REWARD'      => [
                'value'       => [
                    'gold'   => Gold::NEW_YEAR_GOLD,
                    'remark' => '新年奖励-牛年',
                    'action' => 'NEW_YEAR_REWARD',
                ],
                'description' => '新年奖励-牛年',
            ],
            'WATCH_REWARD_VIDEO'   => [
                'value'       => [
                    'gold'   => 10,
                    'remark' => '观看激励视频奖励',
                    'action' => 'WATCH_REWARD_VIDEO',
                ],
                'description' => '观看激励视频奖励',
            ],
            'SIGNIN_VIDEO_REWARD'  => [
                'value'       => [
                    'remark' => '签到视频观看奖励',
                    'action' => 'SIGNIN_VIDEO_REWARD',
                ],
                'description' => '签到视频观看奖励',
            ],
            'TICKET_SIGNIN_REWARD' => [
                'value'       => [
                    'remark' => '签到精力点奖励',
                    'action' => 'TICKET_SIGNIN_REWARD',
                    'gold'   => 50,
                    'ticket' => 10,
                ],
                'description' => '签到精力点奖励',
            ],
            'GOLD_SIGNIN_REWARD'   => [
                'value'       => [
                    'remark' => '签到金币奖励',
                    'action' => 'GOLD_SIGNIN_REWARD',
                    'gold'   => 100,
                ],
                'description' => '签到金币奖励',
            ],
            'DOUBLE_SIGNIN_REWARD' => [
                'value'       => [
                    'remark' => '双倍签到奖励',
                    'action' => 'DOUBLE_SIGNIN_REWARD',
                ],
                'description' => '双倍签到奖励',
            ],
            'KEEP_SIGNIN_REWARD'   => [
                'value'       => [
                    'remark' => '连续签到奖励',
                    'action' => 'KEEP_SIGNIN_REWARD',
                ],
                'description' => '连续签到奖励',
            ],
            'CLICK_REWARD_VIDEO'   => [
                'value'       => [
                    'ticket'     => \App\User::VIDEO_REWARD_TICKET,
                    'contribute' => User::VIDEO_REWARD_CONTRIBUTE,
                    'gold'       => User::VIDEO_REWARD_GOLD,
                    'remark'     => '点击激励视频奖励',
                    'action'     => 'CLICK_REWARD_VIDEO',
                ],
                'description' => '点击激励视频奖励',
            ],
        ];
    }
    /**
     * @param $root
     * @param array $args
     * @param $context
     * @param $info
     * 第三方账号登录：微信，手机号，支付宝
     */
    public function resolveAuthSignIn($root, array $args, $context, $info)
    {

        $code = $args['code'];
        $type = $args['type'];
        app_track_event("用户登录", "第三方登录", $type);
        return $this->authSignIn($code, $type);

    }
    public function resolveSMSSignIn($root, array $args, $context, $info)
    {

        $sms_code = $args['sms_code'];
        $phone    = $args['phone'];
        app_track_event("用户登录", "验证码登录", $phone);
        return $this->smsSignIn($sms_code, $phone);

    }

    public function me($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        if ($user = getUser()) {

            //注意，前端headers 传的是 deviceHeaders.uniqueId = DeviceInfo.getUniqueID();
            $uuid = request()->header('uniqueId', null);
            if (!empty($uuid)) {
                // 修复旧版本无uuid
                if (empty($user->uuid)) {
                    $user->update(['uuid' => $uuid]);
                }
                // 手机系统升级UUID变更
                if (!empty($user->uuid) && $user->uuid !== $uuid) {
                    $user->update(['uuid' => $uuid]);
                }
            }
            return $user;
        }
    }

    public function resolveFriends($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user    = \App\User::findOrFail($args['user_id']);
        $follows = $user->followingUsers()->take(500)->get();
        $friends = [];
        foreach ($follows as $follow) {
            $friend = $follow->followed; //被关注的人
            $ffuids = $friend->followingUsers()->pluck('followable_id')->toArray();
            if (in_array($user->id, $ffuids)) {
                $friends[] = $friend;
            }
        }
        return $friends;
    }

    public function removeBlockUser($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = getUser();
        $user->blockUser($args['user_id']);
        return $user;
    }

    public function signIn($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $account = $args['account'] ?? $args['email'];
        $qb      = User::where('phone', $account)->orWhere('email', $account)->orWhere('account', $account);
        if ($qb->exists()) {
            $user = $qb->first();
            if (!password_verify($args['password'], $user->password)) {
                throw new GQLException('登录失败！账号或者密码不正确');
            }

            if ($user->status === User::STATUS_OFFLINE) {
                throw new GQLException('登录失败！账户已被封禁');
            } else if ($user->status === User::STATUS_DESTORY) {
                throw new GQLException('登录失败！账户已被注销');
            }

            $user->touch(); //更新用户的更新时间来统计日活用户
            return $user;
        } else {
            throw new GQLException('登录失败！邮箱或者密码不正确');
        }
    }

    public function signUp($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {

        if (isset($args['account'])) {

            $account = $args['account'];

            $exists = User::where('phone', $account)->orWhere('account', $account)->exists();
            //手机号格式验证
            $flag = preg_match('/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/', $account);
            if (!$flag) {
                throw new GQLException('注册失败，手机号格式不正确，请检查是否输入正确');
            }

            if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $args['password'])) {
                throw new GQLException('密码中不能包含中文');
            }

            if ($exists) {
                throw new GQLException('该账号已经存在');
            }
            $name = $args['name'] ?? User::DEFAULT_NAME;
            return self::createUser($name, $account, $args['password']);
        }

        $email  = $args['email'];
        $exists = User::Where('email', $email)->exists();

        if ($exists) {
            throw new GQLException('该邮箱已经存在');
        }

        $user        = self::createUser(User::DEFAULT_NAME, $email, $args['password']);
        $user->phone = null;
        $user->email = $email;
        $user->save();
        app_track_event('用户', '用户注册');

        Ip::createIpRecord('users', $user->id, $user->id);
        return $user;
    }

    public function signOut($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user_id = $args['user_id'];
        return \App\User::findOrFail($user_id);
    }

    public function resolveRecommendAuthors($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        //TODO: 实现真正的个性推荐算法
        return self::latest('id');
    }

    public function resolveSearchUsers($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        //TODO: 替换更好的scout search
        return self::where('name', 'like', '%' . $args['keyword'] . '%');
    }

    public function resolveNotifications($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user                = getUser();
        $notifications       = \App\Notification::where('notifiable_type', 'users')->where('notifiable_id', $user->id);
        $unreadNotifications = \App\Notification::where('notifiable_type', 'users')->where('notifiable_id', $user->id)->whereNull('read_at');
        switch ($args['type']) {
            case 'GROUP_COMMENT':
                $qb = $notifications->orderBy('created_at', 'desc')
                    ->whereIn('type', [
                        'Haxibiao\Breeze\Notifications\ReplyComment',
                        'Haxibiao\Breeze\Notifications\ArticleCommented',
                        'Haxibiao\Breeze\Notifications\CommentedNotification',
                    ]);
                //mark as read
                $unread_notifications = $unreadNotifications
                    ->whereIn('type', [
                        'Haxibiao\Breeze\Notifications\ReplyComment',
                        'Haxibiao\Breeze\Notifications\ArticleCommented',
                        'Haxibiao\Breeze\Notifications\CommentedNotification',
                    ])->get();
                $unread_notifications->markAsRead();
                break;
            case 'GROUP_OTHERS':
                $qb = $notifications->orderBy('created_at', 'desc')
                    ->whereIn('type', [
                        'Haxibiao\Breeze\Notifications\CollectionFollowed',
                        'Haxibiao\Breeze\Notifications\CategoryFollowed',
                        'Haxibiao\Breeze\Notifications\ArticleApproved',
                        'Haxibiao\Breeze\Notifications\ArticleRejected',
                        'Haxibiao\Breeze\Notifications\CommentAccepted',
                    ]);

                //mark as read
                $unread_notifications = $unreadNotifications
                    ->whereIn('type', [
                        'Haxibiao\Breeze\Notifications\CollectionFollowed',
                        'Haxibiao\Breeze\Notifications\CategoryFollowed',
                        'Haxibiao\Breeze\Notifications\ArticleApproved',
                        'Haxibiao\Breeze\Notifications\ArticleRejected',
                        'Haxibiao\Breeze\Notifications\CommentAccepted',
                    ])->get();
                $unread_notifications->markAsRead();
                break;
            case 'GROUP_LIKES':
                $qb = $notifications->orderBy('created_at', 'desc')
                    ->whereIn('type', [
                        'Haxibiao\Breeze\Notifications\ArticleLiked',
                        'Haxibiao\Breeze\Notifications\CommentLiked',
                        'Haxibiao\Breeze\Notifications\LikedNotification',
                    ]);
                //mark as read
                $unread_notifications = $unreadNotifications
                    ->whereIn('type', [
                        'Haxibiao\Breeze\Notifications\ArticleLiked',
                        'Haxibiao\Breeze\Notifications\CommentLiked',
                        'Haxibiao\Breeze\Notifications\LikedNotification',
                    ])->get();
                $unread_notifications->markAsRead();
                break;

            default:
                $qb = $notifications->orderBy('created_at', 'desc')->where('type', $args['type']);
                //mark as read
                $unread_notifications = $unreadNotifications->where('type', $args['type'])->get();
                $unread_notifications->markAsRead();
                break;
        }
        Cache::forget('unreads_' . $user->id);
        return $qb;
    }

    /**
     * 静默登录，uuid 必须传递，手机号可选
     */
    public function autoSignIn($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {

        $qb = User::where('uuid', $args['uuid']);

        // 不是首次登录
        if ($qb->exists()) {
            $user = $qb->first();
            if ($user->status === User::STATUS_OFFLINE) {
                throw new GQLException('登录失败！账户已被封禁');
            } else if ($user->status === User::STATUS_DESTORY) {
                throw new GQLException('登录失败！账户已被注销');
            }
        } else {
            $user = User::create([
                'uuid'      => $args['uuid'],
                'account'   => $args['phone'] ?? $args['uuid'],
                'name'      => User::DEFAULT_NAME,
                'api_token' => Str::random(60),
            ]);
            UserProfile::create([
                'user_id'      => $user->id,
                'introduction' => '这个人暂时没有 freestyle ',
                'app_version'  => request()->header('version', null),
            ]);

            Ip::createIpRecord('users', $user->id, $user->id);
        }
        $user->updateProfileAppVersion($user);

        app_track_event('用户', '静默登录');
        return $user;
    }

    public function updateUserInfo($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {

        $islegal  = app('SensitiveUtils')->islegal(Arr::get($args, 'name'));
        $islegal2 = app('SensitiveUtils')->islegal(Arr::get($args, 'introduction'));
        if ($islegal || $islegal2) {
            throw new GQLException('修改的内容中含有包含非法内容,请删除后再试!');
        }

        // 去除 lighthouse 自动传递的参数
        unset($args['directive']);

        if ($user = checkUser()) {
            if (isset($args['phone'])) {
                // 验证手机号
                $flag = preg_match('/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/', $args['phone']);
                if (!$flag) {
                    throw new GQLException('修改失败，手机号格式不正确，请检查是否输入正确');
                }

                // 查询是否已经存在
                $flag = User::where('phone', $args['phone'])
                    ->orWhere('account', $args['phone'])->exists();
                if ($flag) {
                    throw new GQLException('该手机号已被绑定，请检查是否输入正确');
                }
            }

            //TODO:暂时不牵涉前端的gql,后期需要修改掉的gql,有关用户信息修改的
            $args_profile_infos = ["age", "gender", "introduction", "birthday"];
            $profile_infos      = [];
            foreach ($args_profile_infos as $profile_info) {
                foreach ($args as $index => $value) {
                    if ($index == $profile_info) {
                        $profile_infos[$index] = $args[$index];
                        if ($index == "gender") {
                            $profile_infos[$index] = User::getGenderNumber($args[$index]);
                        }
                        if ($index == "birthday") {
                            if (Str::contains($args[$index], "1970-1-1")) {
                                $profile_infos[$index] = Carbon::parse($args[$index])->addHour(23)->addMinute(59)->addSecond(59);
                            }
                        }
                    }
                }
            }

            if ($args['name'] ?? null) {
                $user_infos['name'] = $args['name'];
            }
            if ($args['phone'] ?? null) {
                $user_infos['phone'] = $args['phone'];
            }
            if ($args['password'] ?? null) {
                $user_infos['password'] = $args['password'];
            }
            if (!empty($user_infos)) {
                $user->update($user_infos);
            }

            if (!empty($profile_infos)) {
                $profile = $user->profile;
                $profile->update($profile_infos);
            }

            return $user;
        } else {
            throw new GQLException('未登录，请先登录！');
        }
    }

    public function destoryUserByToken($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        if ($user = checkUser()) {
            $user->destoryUser();
            return true;
        }
        throw_if(!isset($user->id) || is_null($user), GQLException::class, '请登录后再尝试哦~');
    }

    //观看新手教程或采集视频教程任务状态变更
    public function newUserReword($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $user = checkUser();
        $type = $args['type'];
        if ($type === 'newUser') {
            $task             = Task::where("name", "观看新手视频教程")->first();
            $userTask         = UserTask::where("task_id", $task->id)->where("user_id", $user->id)->first();
            $userTask->status = UserTask::TASK_REACH;
            $userTask->save();
            return 1;
        } else if ($type === 'douyin') {
            $task             = Task::where("name", "观看采集视频教程")->first();
            $userTask         = UserTask::where("task_id", $task->id)->where("user_id", $user->id)->first();
            $userTask->status = UserTask::TASK_REACH;
            $userTask->save();
            return 1;
        }
        return -1;
    }

    public function bindDongdezhuan($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        if ($user = checkUser()) {
            //不允许用户从APP手动指定绑懂得赚账户，必须默认本手机...
            $user->bindDDZ();
            return true;
        }
    }

    /**
     * @param $user
     */
    public function updateProfileAppVersion(User $user): void
    {
        if ($version = request()->header('version', null)) {
            $user->getProfileAttribute()->update([
                'app_version' => $version,
            ]);
        }
    }

    public function resolveUserQuery($root, array $args, $context, $info)
    {
        $user        = getUser(false);
        $loginUserId = data_get($user, 'id');
        if ($loginUserId) {
            if ($loginUserId != data_get($args, 'id')) {
                app_track_event("用户", "查询用户详情", "谁看谁:" . $loginUserId . "-" . $args['id']);
            }
        }
        return \App\User::find(data_get($args, 'id'));
    }

    public static function hasNewUserReward($root, array $args, $context, $info)
    {
        $user_id = data_get($args, 'user_id');
        return self::hasReward($user_id, '新人注册奖励');

    }
}
