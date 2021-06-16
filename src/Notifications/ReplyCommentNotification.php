<?php

namespace Haxibiao\Breeze\Notifications;

use Haxibiao\Content\Post;
use Haxibiao\Sns\Comment;
use Illuminate\Bus\Queueable;

/**
 * 评论被回复通知
 */
class ReplyCommentNotification extends BreezeNotification
{
    use Queueable;

    public static $data_action = "回复了评论";
    protected $reply;
    protected $sender;

    public function __construct(Comment $comment)
    {
        $this->reply  = $comment;
        $this->sender = $comment->user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        //兼容旧互动content morph实现
        $comment = $this->reply->commentable;
        $data    = [
            'reply_content' => $this->reply->getContent(),
            'reply_id'      => $this->reply->id,
            'comment_id'    => $comment->id,
            'comment_body'  => $comment->body,
        ];

        //互动用户
        $data = array_merge($data, $this->senderToArray());

        //互动对象
        $commentable = $this->comment->commentable;
        // - 评论了动态
        if ($commentable instanceof Post) {
            $this->data_description = $commentable->description;
            $this->data_cover       = $commentable->cover;
        }
        // - 评论了楼中楼
        if ($commentable instanceof Comment) {
            $this->data_description = $commentable->body;
        }

        // - FIXME: 评论了电影/文章
        $data = array_merge($data, [
            'id'          => $this->comment->commentable_id,
            'type'        => $this->comment->commentable_type,
            'message'     => $this->comment->body, //评论
            'description' => $this->data_description, //评论的内容
            'cover'       => $this->data_cover, //内容的配图
        ]);
    }
}
