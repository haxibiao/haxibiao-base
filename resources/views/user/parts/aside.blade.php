<div class="aside sm-right hidden-xs">
      <ul class="icon-text-list distance">
        @if($user->is_signed)
          <li><a href="javascript:;"><img class="badge-icon" src="/images/signed.png" alt=""></i>{{ seo_site_name() }}签约作者</a></li>
          @endif
          @if($user->is_editor)
          <li><a href="javascript:;"><img class="badge-icon" src="/images/editor.png" alt=""></i>{{ seo_site_name() }}小编</a></li>
           @endif
      </ul>

    @include('user.aside.introduction')

    <ul class="icon-text-list icon-fix distance">
      <li><a href="/user/{{ $user->id }}/followed-categories"><i class="iconfont icon-duoxuan"></i> <span>{{ $user->ta() }}关注的专题</span></a></li>
      <li><a href="/user/{{ $user->id }}/followed-collections"><i class="iconfont icon-wenji"></i> <span>{{ $user->ta() }}关注的文集</span></a></li>
      <li><a href="/user/{{ $user->id }}/likes"><i class="iconfont icon-xin"></i> <span>{{ $user->ta() }}喜欢的作品</span></a></li>
      <li>
        <a href="/user/{{ $user->id }}/videos">
          <i class="iconfont icon-smile"></i> <span>{{ $user->ta() }}管理的视频</span>
        </a>
      </li>
    </ul>

    @include('user.aside.managed_categories')
    @include('user.aside.collections')

    <p class="plate-title"><a data-target=".modal-blacklist" data-toggle="modal">加入黑名单</a> · <a data-target=".modal-report" data-toggle="modal">举报用户</a></p>
</div>
