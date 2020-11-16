<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Post;
use App\Presenters\CommentPresenter;
use App\Presenters\PostPresenter;
use Inertia\Inertia;

class ShowPost extends Controller
{
    public function __invoke(Post $post)
    {
        $this->authorize('view', $post);

        $this->incrementVisit($post);

        $post->load([
            'author' => function ($query) {
                return $query->withCount('publishedPosts', 'likedPosts');
            }
        ]);

        return Inertia::render('Post/Show', [
            'post' => PostPresenter::make($post)
                ->preset('show')
                ->get(),
            'postOnlyLikes' => PostPresenter::make($post)
                ->only('likes')
                ->with(function (Post $post) { 
                    return [
                    'is_liked' => $post->isLiked,
                    ];
                })
                ->get(),
            'comments' => function () { 
                return CommentPresenter::collection(
                    $post->comments()
                        ->with('commenter')
                        ->latest()
                        ->get()
                        ->each->setRelation('post', $post)
                )->get();
            },
        ]);
    }

    protected function incrementVisit(Post $post)
    {
        if (! optional($this->user())->can('view', $post) &&
            ! session("posts:visits:{$post->id}")
        ) {
            $post->increment('visits');
            session()->put("posts:visits:{$post->id}", true);
        }
    }
}
