<?php

class Posts_Controller extends Controller {

	/**
	 * The layout being used by the controller.
	 *
	 * @var string
	 */
	public $layout = 'layouts.scaffold';
     //more help
	/**
	 * Indicates if the controller uses RESTful routing.
	 *
	 * @var bool
	 */
	public $restful = true;

	/**
	 * View all of the posts.
	 *
	 * @return void
	 */
	public function get_index()
	{
		//return View::make('hello.index');
		$posts = Post::all();
		
		$this->layout->title   = 'Posts';
		$this->layout->content = View::make('posts.index')->with('posts', $posts);
	}

	/**
	 * Show the form to create a new post.
	 *
	 * @return void
	 */
	public function get_create()
	{
		$this->layout->title   = 'New Post';
		$this->layout->content = View::make('posts.create');
	}

	/**
	 * Create a new post.
	 *
	 * @return Response
	 */
	public function post_create()
	{
		$validation = Validator::make(Input::all(), array(
			'title' => array('required'),
			'content' => array('required'),
		));

		if($validation->valid())
		{
			$post = new Post;

			$post->title = Input::get('title');
			$post->content = Input::get('content');

			$post->save();

			Session::flash('message', 'Added post #'.$post->id);

			return Redirect::to('posts');
		}

		else
		{
			return Redirect::to('posts/create')->with_errors($validation->errors);
		}
	}

	/**
	 * View a specific post.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_view($id)
	{
		$post = Post::find($id);

		if(is_null($post))
		{
			return Redirect::to('posts');
		}

		$this->layout->title   = 'Viewing Post #'.$id;
		$this->layout->content = View::make('posts.view')->with('post', $post);
	}

	/**
	 * Show the form to edit a specific post.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_edit($id)
	{
		$post = Post::find($id);

		if(is_null($post))
		{
			return Redirect::to('posts');
		}

		$this->layout->title   = 'Editing Post';
		$this->layout->content = View::make('posts.edit')->with('post', $post);
	}

	/**
	 * Edit a specific post.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function post_edit($id)
	{
		$validation = Validator::make(Input::all(), array(
			'title' => array('required'),
			'content' => array('required'),
		));

		if($validation->valid())
		{
			$post = Post::find($id);

			if(is_null($post))
			{
				return Redirect::to('posts');
			}

			$post->title = Input::get('title');
			$post->content = Input::get('content');

			$post->save();

			Session::flash('message', 'Updated post #'.$post->id);

			return Redirect::to('posts');
		}

		else
		{
			return Redirect::to('posts/edit/'.$id)->with_errors($validation->errors);
		}
	}

	/**
	 * Delete a specific post.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function get_delete($id)
	{
		$post = Post::find($id);

		if( ! is_null($post))
		{
			$post->delete();

			Session::flash('message', 'Deleted post #'.$post->id);
		}

		return Redirect::to('posts');
	}
}