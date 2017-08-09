<?php
/**
 * Created by PhpStorm.
 * User: ZhangWei
 * Date: 2017/8/8
 * Time: 16:43
 */

namespace App\Http\Controllers\Api\V3;


use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Article;
use App\Models\ArticleTag;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input("page", 1);
        $tag_id = $request->input("tag_id", 0);
        $article = Article::query();
        if ($tag_id > 0) {
            $article_ids = ArticleTag::query()->where('tag_id', $tag_id)->get(['article_id'])->pluck('article_id')->toArray();
            if (is_array($article_ids)) {
                $article->whereIn('id', $article_ids);
            }
        }


        $list = $article->paginate(20, ['id', 'title', 'desc', 'cover', 'view_count']);

        foreach ($list as $v) {
            $v->tags = $v->tags()->get(['tag.id', 'tag.name']);
        }
        $swiper = Ad::query()->where('loca_id', 3)
            ->where('state', 1)->orderBy('order', 'desc')->limit(5)->get(['id', 'title', 'page', 'cover', 'loca_id', 'open_type']);

        if ($page == 1 && $tag_id == 0) {
            $json = json_encode($list);

            $list = json_decode($json);

            $list->swiper = $swiper;
        }


        return response()->json($list);
    }

    public function page(Request $request)
    {
        $id = $request->input("id");

        $page = Article::query()->find($id, ['id', 'title', 'content', 'updated_at']);
        $page->increment("view_count");


        return response()->json(['data' => $page, 'message' => '', 'status_code' => 1]);
    }

}