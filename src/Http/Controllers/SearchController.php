<?php

namespace Haxibiao\Breeze\Http\Controllers;

use App\Article;
use App\Category;
use App\Collection;
use App\Query;
use App\Querylog;
use App\Tag;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as LCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $page_size = 10;
        $page      = request('page') ? request('page') : 1;
        $query     = request('q');
        $articles  = Article::where(function ($qb) use ($query) {
            $qb->where('title', 'like', '%' . $query . '%');
            $qb->orWhere('keywords', 'like', '%' . $query . '%');
            $qb->orWhere('description', 'like', '%' . $query . '%');
        })->exclude(['body', 'json'])
            ->where('status', 1)
            ->whereIn('type', ['article', 'diagrams'])
            ->orderBy('id', 'desc')
            ->paginate(10);
        $total = $articles->total();

        //高亮关键词
        foreach ($articles as $article) {
            $article->title       = str_replace($query, '<em>' . $query . '</em>', $article->title);
            $article->description = str_replace($query, '<em>' . $query . '</em>', $article->summary);
        }

        //如果标题无结果，搜索标签库
        if (!$total) {
            list($articles_taged, $matched_tags) = $this->search_tags($query);
            $total                               = count($articles_taged);

            //给标签搜索到的分页
            $articles = new LengthAwarePaginator($articles_taged->forPage($page, $page_size),
                $total, $page_size, $page, ['path' => '/search']);

            //高亮标签
            foreach ($articles as $article) {
                $article->description = ' 关键词:' . $article->keywords . '， 简介：' . $article->summary;
                foreach ($matched_tags as $tag) {
                    $article->title       = str_replace($tag, '<em>' . $tag . '</em>', $article->title);
                    $article->description = str_replace($tag, '<em>' . $tag . '</em>', $article->description);
                }
            }
        }

        //TODO:: 关联搜索整个哈希表旗下产品里的文章了，后期专注做搜索才处理
        // if (!$total) {
        //     $articles_hxb = $this->search_hxb($query);
        //     $total = count($articles_hxb);
        // }

        //用户，专题
        $data['users'] = User::where('name', 'like', "%$query%")
            ->where('status', '>=', 0)
            ->paginate($page_size);
        $data['categories'] = Category::where('name', 'like', "%$query%")
            ->where('status', '>=', 0)
            ->orderBy('parent_id', 'asc')
            ->orderBy('count_follows', 'desc')
            ->paginate($page_size);
        $data['movies'] = \App\Movie::where('name', 'like', "%$query%")->paginate($page_size);

        $data['articles'] = $articles;
        $data['query']    = $query;
        $data['total']    = $total;

        return view('search.articles')->withData($data);
    }

    public function searchVideos()
    {
        $page_size     = 10;
        $page          = request('page') ? request('page') : 1;
        $query         = request('q');
        $data['video'] = Article::whereType('video')
            ->whereStatus(1)
            ->where('title', 'like', "%$query%")
            ->paginate($page_size);
        $data['query'] = $query;

        return view('search.video')->withData($data);
    }

    public function searchMovies()
    {
        $page_size     = 10;
        $page          = request('page') ? request('page') : 1;
        $query         = request('q');
        $data['movie'] = \App\Movie::where('name', 'like', "%$query%")->paginate($page_size);
        $data['query'] = $query;
        $total         = count($data['movie']);

        if (!empty($query)) {
            //保存全局搜索
            $query_item = Query::firstOrNew([
                'query' => $query,
            ]);
            $query_item->results = $total;
            $query_item->hits++;
            $query_item->save();

            //保存个人搜索
            $query_log = Querylog::firstOrNew([
                'user_id' => Auth::id(),
                'query'   => $query,
            ]);
            $query_log->save();
        }
        return view('search.movie')->withData($data);
    }

    public function searchUsers()
    {
        $page_size     = 10;
        $page          = request('page') ? request('page') : 1;
        $query         = request('q');
        $data['users'] = User::where('status', '>=', 0)->where('name', 'like', "%$query%")->paginate($page_size);
        $data['query'] = $query;
        return view('search.users')->withData($data);
    }

    public function searchCategories()
    {
        $page_size          = 10;
        $page               = request('page') ? request('page') : 1;
        $query              = request('q');
        $data['categories'] = Category::where('status', '>=', 0)
            ->where('name', 'like', "%$query%")
            ->orderBy('parent_id', 'asc')
            ->orderBy('count_follows', 'desc')
            ->paginate($page_size);
        $data['query'] = $query;
        return view('search.categories')->withData($data);
    }

    public function searchCollections()
    {
        $page_size           = 10;
        $page                = request('page') ? request('page') : 1;
        $query               = request('q');
        $data['collections'] = Collection::where('status', '>=', 0)
            ->where('name', 'like', "%$query%")
            ->paginate($page_size);
        $data['query'] = $query;
        return view('search.collections')->withData($data);
    }

    public function search_tags($query)
    {
        $articles     = [];
        $tags         = Tag::all();
        $matched_tags = [];
        foreach ($tags as $tag) {
            if ( $query && str_contains($query, $tag->name)) {
                foreach ($tag->articles as $article) {
                    $articles[] = $article;
                }
                $matched_tags[] = $tag->name;
            }
        }
        return [LCollection::make($articles), $matched_tags];
    }

    public function search_hxb($query)
    {
        $results = Cache::get('query_' . $query);
        if ($results) {
            return $results;
        }
        $articles_hxb = [];
        $api_url      = 'http://haxibiao.com/api/articles';
        $api_url .= '?query=' . $query;
        $api_url .= "&page=" . (request('page') ? request('page') : 1);
        if ($json = @file_get_contents($api_url)) {
            $json_data    = json_decode($json);
            $articles_hxb = collect($json_data->data);
            Cache::put('query_' . $query, $articles_hxb, 60 * 24);
        }
        //修复json过来的数据差异
        foreach ($articles_hxb as $article) {
            $article->created_at  = \Carbon\Carbon::parse($article->created_at);
            $article->updated_at  = \Carbon\Carbon::parse($article->updated_at);
            $article->description = str_limit(strip_tags($article->body), 250);
            $article->target_url  = "http://haxibiao.com/article/" . $article->id;
        }
        return $articles_hxb;
    }

    public function search_all()
    {
        $users          = User::all();
        $querys         = Query::where('status', '>=', 0)->orderBy('hits', 'desc')->paginate();
        $data           = [];
        $data['update'] = Query::where('status', '>=', 0)->orderBy('updated_at', 'desc')->paginate(10);
        return view('search.search_all')
            ->withData($data)
            ->withUsers($users)
            ->withQuerys($querys);
    }
}
