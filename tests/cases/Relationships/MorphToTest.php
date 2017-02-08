<?php

use TestApp\Comment;
use TestApp\Blog;
use ProxyManager\Proxy\LazyLoadingInterface;

class MorphToTest extends DomainTestCase
{
    /** @test */
    public function we_can_store_a_related_entity()
    {
    	$comment = new Comment('Comment 1');
        $blog = $this->factoryMakeUid(Blog::class);
        $comment->commentable = $blog;
        $mapper = $this->mapper($comment);
        $mapper->store($comment);
        
        $this->seeInDatabase('comments', [
        	'text' => 'Comment 1',
        	'commentable_id' => $blog->id,
        	'commentable_type' => Blog::class,
        ]);
    }


    public function relationship_can_be_eager_loaded()
    {
        $comment = new Comment('Comment 1');
        $blog = $this->factoryMakeUid(Blog::class);
        $comment->commentable = $blog;
        $mapper = $this->mapper($comment);
        $mapper->store($comment);
        $loadedComment = $mapper->with('commentable')->whereId($comment->id)->first();

        $this->assertInstanceOf(Blog::class,$loadedComment->commentable);
        $this->assertEquals($blog->id, $loadedComment->commentable->id);
        $this->assertNotInstanceOf(LazyLoadingInterface::class, $loadedComment->commentable);
    }

    public function relationship_can_be_lazy_loaded()
    {
        $comment = new Comment('Comment 1');
        $blog = $this->factoryMakeUid(Blog::class);
        $comment->commentable = $blog;
        $mapper = $this->mapper($comment);
        $mapper->store($comment);
        $loadedComment = $mapper->with('commentable')->whereId($comment->id)->first();

        $this->assertInstanceOf(LazyLoadingInterface::class, $loadedComment->commentable);
        $this->assertInstanceOf(Blog::class,$loadedComment->commentable);
        $this->assertEquals($blog->id, $loadedComment->commentable->id);
    }

     /** @test */
    public function relation_is_set_to_null_when_foreign_key_is_null()
    {
        $comment = new Comment('Comment 1');
        $mapper = $this->mapper($comment);
        $mapper->store($comment);
        $loadedComment = $mapper->with('commentable')->whereId($comment->id)->first();
        $this->assertNull($loadedComment->commentable);
    }

    /** @test */
    public function dirty_attributes_on_related_entity_are_updated_on_store()
    {
        $comment = new Comment('Comment 1');
        $blog = $this->factoryCreateUid(Blog::class);
        $blog->title = 'New Title';
        $comment->commentable = $blog;
        $mapper = $this->mapper($comment);
        $mapper->store($comment);
        $this->seeInDatabase('blogs', [
            'id' => $blog->id,
            'title' => 'New Title',
        ]);
    }

    /** @test */
    public function foreign_key_is_set_on_null_when_detaching_related_entity()
    {
        $comment = new Comment('Comment 1');
        $blog = $this->factoryCreateUid(Blog::class);
        $comment->commentable = $blog;
        $mapper = $this->mapper($comment);
        $mapper->store($comment);
        
        $comment->commentable = null;
        $mapper->store($comment);

        $this->seeInDatabase('comments', [
            'id' => $comment->id,
            'commentable_id' => null,
            'commentable_type' => null,
        ]);
    }
}
