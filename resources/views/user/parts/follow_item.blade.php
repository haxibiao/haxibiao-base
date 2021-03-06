<li class="user-info info-md">
  @if($user)
  <a class="avatar" href="/user/{{ $user->id }}"><img src="{{ $user->avatarUrl }}" alt=""></a>

  <follow type="users" id="{{ $user->id }}" user-id="{{ user_id() }}" followed="{{ is_follow('users', $user->id) }}">
  </follow>

  <div class="title">
    <a href="/user/{{ $user->id }}" class="name">{{ $user->name }}</a>
  </div>
  <div class="info">
    <div class="meta">
      <span>关注 {{ $user->followingUsers()->count() }}</span><span>粉丝 {{ $user->count_follows }}</span><span>文章 {{ $user->count_articles }}</span>
    </div>
    <div class="meta">
      写了 {{ $user->count_words }} 字，获得了 {{ $user->count_likes }} 个喜欢
    </div>
  </div>
  @endif
</li>