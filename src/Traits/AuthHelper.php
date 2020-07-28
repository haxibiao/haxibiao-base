<?php

namespace Haxibiao\Base\Traits;

use App\Exceptions\ErrorCode\ErrorCode;
use App\Exceptions\GQLException;
use App\Exceptions\UserException;
use App\User;
use App\VerificationCode;
use Illuminate\Support\Facades\Auth;

trait AuthHelper
{
    /**
     * 静默登录/注册 - 一键登录/注册
     * @param $account 静默获取的手机号，优先尊重
     * @param $uuid  手机号为空，$account用$uuid
     */
    public static function autoSignIn($account, string $uuid)
    {
        if (!empty($account)) {
            throw_if(!is_phone_number($account), GQLException::class, '手机号格式不正确!');
            $user = User::where('account', $account)->first();
            if (empty($user)) {
                $user = User::create([
                    'uuid'      => $uuid,
                    'account'   => $account,
                    'password'  => bcrypt('123456789'),
                    'name'      => User::DEFAULT_USER_NAME, //FIXME: 每个项目不同，应该从config env里取
                    'api_token' => str_random(60),
                ]);
            }
        } else {
            $user = User::where('uuid', $uuid)->first();
            if (empty($user)) {
                //静默注册一个uuid为account的新账户
                $user = User::create([
                    'uuid'      => $uuid,
                    'account'   => $uuid,
                    'password'  => bcrypt('123456789'),
                    'name'      => User::DEFAULT_USER_NAME,
                    'api_token' => str_random(60),
                ]);
            }
        }

        //账号已注销
        throw_if($user->isDegregister(), UserException::class, '操作失败,账户已注销!', ErrorCode::DEREGISTER_USER);

        Auth::login($user);
        $user->retention; //完善留存档案
        return $user;
    }

    /**
     * 手动登录 - 密码
     * @param $account 手机号
     * @param $password 密码
     * @param $uuid 获取到的UUID，保留最新的
     */
    public static function signIn(string $account, string $password, string $uuid): User
    {
        throw_if(!is_phone_number($account) && !is_email($account), GQLException::class, '账号格式不正确!');
        $user = User::where('account', $account)->first();

        throw_if(empty($user), GQLException::class, '账号不存在,请先注册!');
        if (!password_verify($password, $user->password)) {
            throw new GQLException('登录失败,账号或者密码错误');
        }

        if (!empty($uuid) && !strcmp($user->uuid, $uuid)) {
            $user->update(['uuid' => $uuid]);
        }

        //账号已注销
        throw_if($user->isDegregister(), UserException::class, '操作失败,账户已注销!', ErrorCode::DEREGISTER_USER);

        Auth::login($user);
        return $user;
    }

    /**
     * 手动登录 - 验证码
     * @param $account 手机号
     * @param $sms_code 手机验证码
     * @param $uuid 获取到的UUID，保留最新的
     */
    public static function signInWithSMSCode(string $account, string $sms_code, string $uuid)
    {
        throw_if(!is_phone_number($account), GQLException::class, '手机号格式不正确!');
        throw_if(empty($sms_code), GQLException::class, '验证码不能为空!');

        $code = User::getLoginVerificationCode($account);

        if (empty($code) || !strcmp($code, $sms_code)) {
            throw new GQLException('验证码不正确!');
        }

        $user = User::where('account', $account)->first();
        throw_if(empty($user), GQLException::class, '账号不存在,请先注册~');

        //更新uuid
        if (!strcmp($user->uuid, $uuid)) {
            $user->update(['uuid' => $uuid]);
        }

        //登录
        Auth::login($user);
        return $user;
    }

    /**
     * 手动注册 - 一秒注册
     * @param $account 手机号
     * @param $password 密码
     * @param $uuid 获取到的UUID
     */
    public static function signUp(string $account, string $uuid, string $password): User
    {
        throw_if(User::where('account', $account)->exists(), GQLException::class, '账号已存在');

        $user = User::create([
            'uuid'      => $uuid,
            'account'   => $account,
            'password'  => bcrypt($password),
            'name'      => User::DEFAULT_USER_NAME,
            'api_token' => str_random(60),
        ]);

        Auth::login($user);
        $user->retention; //完善留存档案

        return $user;
    }

    /**
     * 手动注册 - 手机+验证码注册
     * @param $account 手机号
     * @param $uuid 获取到的UUID
     * @param $sms_code 手机验证码
     */
    public static function signUpWithSMSCode(string $account, string $uuid, string $sms_code): User
    {
        throw_if(empty($sms_code), GQLException::class, '验证码不能为空!');

        $code = self::getLoginVerificationCode($account);

        if (!strcmp($code, $sms_code)) {
            throw_if(empty($sms_code), GQLException::class, '验证码不正确!');
        }

        throw_if(User::where('account', $account)->exists(), GQLException::class, '账号已存在');

        $user = User::create([
            'uuid'      => $uuid,
            'account'   => $account,
            'password'  => bcrypt('12345678'),
            'name'      => User::DEFAULT_USER_NAME,
            'api_token' => str_random(60),
        ]);

        Auth::login($user);
        $user->retention; //完善留存档案

        return $user;
    }

    //FIXME: 这验证码功能，是基础 - @wuxiuzhang
    public static function getLoginVerificationCode($account, $action = VerificationCode::USER_LOGIN)
    {
        return VerificationCode::where('account', $account)
            ->byValid(VerificationCode::CODE_VALID_TIME)
            ->where('action', $action)
            ->latest('id')
            ->first();
    }

    //FIXME: 这个黑名单功能，也要迁移到base,是网警检查必须要有的功能，每个产品都要 - @zengdawei
    public function isBlack()
    {
        if (class_exists("App\\BlackList", true)) {
            $black    = \App\BlackList::where('user_id', $this->id);
            $is_black = $black->exists();
            return $is_black;
        }
    }
}
