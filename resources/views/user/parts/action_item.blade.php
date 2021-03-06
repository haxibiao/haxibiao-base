@if($action->actionable && (get_class($action->actionable) == 'App\Article' || get_class($action->actionable) == 'App\Video'))
@php
    if($action->actionable_type == 'videos'){
        $item = $action->actionable->article;
        $status = $item->status;
    }else{
        $item = $action->actionable;
        $status = $item->status;
    }
    //避免脏数据
    if(empty($item) || $status < 1){
        return;
    }
@endphp
{{-- 发布 --}}
<li class="{{ $item->cover ? 'content-item have-img' : 'content-item' }}">
  <a class="wrap-img" href="{{ $item->type == 'videos' ? '/video/'.$item->video_id : '/article/'.$item->id }}"  >
      <img src="{{ $item->cover }}" alt="">
      @if($item->type == 'video')
        <span class="rotate-play">
            <i class="iconfont icon-shipin"></i>
        </span>
        <i class="duration">{{ gmdate('i:s', $item->video->duration) }}</i>
      @endif
  </a>
  <div class="content">
    <div class="author">
      <a class="avatar"   href="/user/{{ $action->user->id }}">
        <img src="{{ $action->user->avatarUrl }}" alt="">
      </a>
      <div class="info">
        @if($action->user->is_signed)
                              <img class="badge-icon"  src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                            @endif
                            @if($action->user->is_editor)
                              <img class="badge-icon"  src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}小编" alt="">
                            @endif
        <a class="nickname"   href="/user/{{ $action->user->id }}">{{ $action->user->name }}</a>
        <span class="time" data-shared-at="2017-11-06T09:20:28+08:00">发表了作品 @timeago($action->created_at)</span>
      </div>
    </div>
    <a class="title"   href="/article/{{ $item->id }}"><span>{{ $item->title }}</span></a>
    <p class="abstract">
      {{ $item->description }}
    </p>
    <div class="meta">
      <a   href="/article/{{ $item->id }}">
        <i class="iconfont icon-liulan"></i> {{ $item->hits }}
      </a>
      <a   href="/article/{{ $item->id }}/#comments">
        <i class="iconfont icon-svg37"></i> {{ $item->count_replies }}
      </a>
      <span><i class="iconfont icon-03xihuan"></i> {{ $item->count_likes }}</span>
    </div>
  </div>
</li>
{{-- 评论 --}}
@elseif($action->actionable && get_class($action->actionable) == 'App\Comment')
@php
    $comment = $action->actionable;
    $item = optional($comment)->commentable;
@endphp
@if($item)

<li class="article-item have-img">
    <a class="wrap-img" href="{{ $item->url }}"  >
      <img src="{{ $item->cover }}" alt="">
    </a>
    <div class="content">
        <div class="author">
            <a class="avatar"   href="/user/{{ $action->user->id }}">
        <img src="{{ $action->user->avatarUrl }}" alt="">
      </a>
            <div class="info">
                @if($action->user->is_signed)
                              <img class="badge-icon"  src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                            @endif
                            @if($action->user->is_editor)
                              <img class="badge-icon"  src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}小编" alt="">
                            @endif
                <a class="nickname"   href="/user/{{ $action->user->id }}">{{ $action->user->name }}</a>
                <span class="time"> 发表了评论 · @timeago($action->created_at)</span>
            </div>
        </div>
        <div class="comment"><p>{!! $comment->body !!}</p></div>
        <blockquote>
            <a class="title"   href="{{ $item->url }}"><span>{{ $item->title }}</span></a>
            <p class="abstract">
                {{ $item->description }}
            </p>
            <div class="meta">
                <div class="origin-author">
                    <a   href="/user/{{ $action->user->id }}">{{ $item->user->name }}</a>
                </div>
                <a   href="/article/{{ $item->id }}">
            <i class="iconfont icon-liulan"></i> {{ $item->hits }}
          </a>
                <a   href="/article/{{ $item->id }}/#comments">
            <i class="iconfont icon-svg37"></i> {{ $item->count_replies }}
          </a>
                <span><i class="iconfont icon-03xihuan"></i> {{ $item->count_likes }}</span>
            </div>
        </blockquote>
    </div>
</li>
@endif
{{-- 点赞article --}}
@elseif($action->actionable && get_class($action->actionable) == 'App\Like')
@php
    $like = $action->actionable;
    $item = $like->liked;
@endphp
    @if($item && $like->liked_type == "articles")
        <li class="article-item have-img">
            <a class="wrap-img" href="/article/{{ $item->id }}"  ><img src="{{ $item->cover }}" alt=""></a>
            <div class="content">
                <div class="author">
                    <a class="avatar"   href="/user/{{ $action->user->id }}"><img src="{{ $action->user->avatarUrl }}" alt=""></a>
                    <div class="info">
                        @if($action->user->is_signed)
                              <img class="badge-icon"  src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                            @endif
                            @if($action->user->is_editor)
                              <img class="badge-icon"  src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}小编" alt="">
                            @endif
                        <a class="nickname"   href="/user/{{ $action->user->id }}">{{ $action->user->name }}</a>
                        <span class="time"> 喜欢了作品 · @timeago($like->created_at)</span>
                    </div>
                </div>
                <a class="title"   href="/article/{{ $item->id }}"><span>{{ $item->title }}</span></a>
                <p class="abstract">
                    {{ $item->description }}
                </p>
                <div class="meta">
                    <div class="origin-author">
                        <a   href="/user/{{ $action->user->id }}">{{ $item->user->name }}</a>
                    </div>
                    <a   href="/article/{{ $item->id }}">
                <i class="iconfont icon-liulan"></i> {{ $item->hits }}
              </a>
                    <a   href="/article/{{ $item->id }}/#comments">
                <i class="iconfont icon-svg37"></i> {{ $item->count_replies }}
              </a>
                    <span><i class="iconfont icon-03xihuan"></i> {{ $item->count_likes }}</span>
                </div>
            </div>
        </li>
    @elseif($item && $like->liked_type == "comments")
        @php
        $article = $item->commentable;
        @endphp
        @if($article)
        {{-- 点赞comment --}}
        <li class="article-item have-img">
            <a class="wrap-img" href="/article/{{ $article->id }}"  ><img src="{{ $article->cover }}" alt=""></a>
            <div class="content">
                <div class="author">
                    <a class="avatar"   href="/user/{{ $action->user->id }}"><img src="{{ $action->user->avatarUrl }}" alt=""></a>
                    <div class="info">
                        @if($action->user->is_signed)
                              <img class="badge-icon"  src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                            @endif
                            @if($action->user->is_editor)
                              <img class="badge-icon"  src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}小编" alt="">
                            @endif
                        <a class="nickname"   href="/user/{{ $action->user->id }}">{{ $action->user->name }}</a>
                        <span class="time"> 喜欢了作品的评论 · @timeago($like->created_at)</span>
                    </div>
                </div>
                <a class="title"   href="/article/{{ $article->id }}"><span>{{ $article->subject }}</span></a>
                <p class="abstract">
                    {{ $item->body }}
                </p>
                <div class="meta">
                    <div class="origin-author">
                        <a   href="/user/{{ $article->user->id }}">{{ $article->user->name }}</a>
                    </div>
                    <a   href="/article/{{ $article->id }}">
                <i class="iconfont icon-liulan"></i> {{ $article->hits }}
              </a>
                    <a   href="/article/{{ $item->id }}/#comments">
                <i class="iconfont icon-svg37"></i> {{ $article->count_replies }}
              </a>
                    <span><i class="iconfont icon-03xihuan"></i> {{ $article->count_likes }}</span>
                </div>
            </div>
        </li>
        @endif
    @endif
{{-- 关注 --}}
@elseif($action->actionable && get_class($action->actionable) == 'App\Follow')
    @php
        $follow = $action->actionable;
        $item = $follow->followable;
        //避免脏数据
        if(empty($item)){
            return;
        }
    @endphp
    @if(get_class($item) == 'App\User')
    <li class="feed-info">
      <div class="content">
            <div class="author">
                <a class="avatar"   href="javascript:;"><img src="{{ $action->user->avatarUrl }}" alt=""></a>
                <div class="info">
                    @if($action->user->is_signed)
                              <img class="badge-icon"  src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                            @endif
                            @if($action->user->is_editor)
                              <img class="badge-icon"  src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}小编" alt="">
                            @endif
                    <a class="nickname"   href="/user/{{ $action->user->id }}">{{ $action->user->name }}</a>
                    <span class="time"> 关注了作者 · @timeago($action->created_at)</span>
                </div>
            </div>
            <div class="follow-card">
                <div class="note-info">
                    <a class="avatar" href="/user/{{ $item->id }}"><img src="{{ $item->avatarUrl }}" alt=""></a>
                    {{-- <a class="btn-base btn-follow"><span>＋ 关注</span></a> --}}
                    <follow
                        type="users"
                        id="{{ $item->id }}"
                        user-id="{{ user_id() }}"
                        followed="{{ is_follow('users',$item->id) }}">
                    </follow>
                    <div class="title">
                        <a class="name" href="/user/{{ $item->id }}">{{ $item->name }}</a>
                    </div>
                    <div class="info">
                        <p>写了 {{ $item->count_words }} 字，被 {{ $item->count_follows }} 人关注，获得了 {{ $item->count_likes }} 个喜欢</p>
                    </div>
                </div>
                <p class="signature">
                    {{ $item->introduction }}
                </p>
            </div>
        </div>
    </li>
    @elseif(get_class($item) == 'App\Category')
    <li class="feed-info">
      <div class="content">
        <div class="author">
          <a class="avatar"   href="/user/{{ $action->user->id }}">
            <img src="{{ $action->user->avatarUrl }}" alt="">
          </a>
          <div class="info">
            @if($action->user->is_signed)
                              <img class="badge-icon"  src="/images/signed.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}签约作者" alt="">
                            @endif
                            @if($action->user->is_editor)
                              <img class="badge-icon"  src="/images/editor.png" data-toggle="tooltip" data-placement="top" title="{{ config('app.name') }}小编" alt="">
                            @endif
            <a class="nickname"   href="/user/{{ $action->user->id }}">{{ $action->user->name }}</a>
            <span class="time"> 关注了专题 · @timeago($action->created_at)</span>
          </div>
        </div>
        <div class="follow-card">
            <div class="note-info">
                <a class="avatar" href="/category/{{ $item->id }}"><img src="{{ $item->logoUrl }}" alt=""></a>
                <follow
                    type="categories"
                    id="{{ $item->id }}"
                    user-id="{{ user_id() }}"
                    followed="{{ is_follow('categories', $item->id) }}">
                </follow>
                <div class="title">
                  <a class="name" href="/category/{{ $item->id }}">{{ $item->name }}</a>
                </div>
                @if(!empty($item->user))
                  <div class="info">
                    <p><a href="/user/{{ $item->user->id }}">{{ $item->user->name }}</a> 创建，{{ $item->count }} 篇作品，{{ $item->count_follows }} 人关注</p>
                  </div>
                @endif
            </div>
            <p class="signature">
                {{ $item->description }}
            </p>
        </div>
      </div>
    </li>
    @endif
@endif
