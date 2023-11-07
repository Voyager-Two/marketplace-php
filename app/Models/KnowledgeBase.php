<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    protected $table = 'kb';

    public $timestamps = false;

    public function getTitle()
    {
        return $this->steam_id;
    }

    public function getContent()
    {
        return $this->group_id;
    }

    public function getViews()
    {
        return $this->username;
    }

    public function incrementView($id)
    {
        // increment view count by 1
        $this->where('id', $id)->increment('views');
    }

    public function createArticle($title,$content,$public)
    {
        $this->title = $title;
        $this->content = $content;
        $this->public = $public;

        $this->save();

        return $this->id;
    }

    public function updateArticle($id,$title,$content,$public)
    {
        $article = $this->find($id);
        $article->title = $title;
        $article->content = $content;
        $article->public = $public;

        $article->save();
    }

}
