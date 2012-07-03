<?php

class Tests_Controller extends Controller {

	/**
	 * The layout being used by the controller.
	 *
	 * @var string
	 */
	public $layout = 'layouts.scaffold';

	/**
	 * Indicates if the controller uses RESTful routing.
	 *
	 * @var bool
	 */
	public $restful = true;

	/**
	 * View all of the tests.
	 *
	 * @return void
	 */
	public function get_index()
	{
		$tests = Test::all();

		$this->layout->title   = 'Tests';
		$this->layout->content = View::make('tests.index')->with('tests', $tests);
	}

	/**
	 * Show the form to create a new test.
	 *
	 * @return void
	 */
	public function get_create()
	{
		$this->layout->title   = 'New Test';
		$this->layout->content = View::make('tests.create');
	}

	/**
	 * Create a new test.
	 *
	 * @return Response
	 */
	public function post_create()
	{
		$validation = Validator::make(Input::all(), array(
			'test_id' => array('required'),
			'test_description' => array('required'),
			'test_author' => array('required', 'integer'),
			'test_status' => array('required', 'integer'),
			'test_automated' => array('in:0,1'),
		));

		if($validation->valid())
		{
			$test = new Test;

			$test->test_id = Input::get('test_id');
			$test->test_description = Input::get('test_description');
			$test->test_author = Input::get('test_author');
			$test->test_status = Input::get('test_status');
			$test->test_automated = Input::get('test_automated', '0');

			$test->save();

			Session::flash('message', 'Added test #'.$test->id);

			return Redirect::to('tests');
		}

		else
		{
			return Redirect::to('tests/create')->with_errors($validation->errors);
		}
	}

	/**
	 * View a specific test.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_view($id)
	{
		$test = Test::find($id);

		if(is_null($test))
		{
			return Redirect::to('tests');
		}

		$this->layout->title   = 'Viewing Test #'.$id;
		$this->layout->content = View::make('tests.view')->with('test', $test);
	}

	/**
	 * Show the form to edit a specific test.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_edit($id)
	{
		$test = Test::find($id);

		if(is_null($test))
		{
			return Redirect::to('tests');
		}

		$this->layout->title   = 'Editing Test';
		$this->layout->content = View::make('tests.edit')->with('test', $test);
	}

	/**
	 * Edit a specific test.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function post_edit($id)
	{
		$validation = Validator::make(Input::all(), array(
			'test_id' => array('required'),
			'test_description' => array('required'),
			'test_author' => array('required', 'integer'),
			'test_status' => array('required', 'integer'),
			'test_automated' => array('in:0,1'),
		));

		if($validation->valid())
		{
			$test = Test::find($id);

			if(is_null($test))
			{
				return Redirect::to('tests');
			}

			$test->test_id = Input::get('test_id');
			$test->test_description = Input::get('test_description');
			$test->test_author = Input::get('test_author');
			$test->test_status = Input::get('test_status');
			$test->test_automated = Input::get('test_automated');

			$test->save();

			Session::flash('message', 'Updated test #'.$test->id);

			return Redirect::to('tests');
		}

		else
		{
			return Redirect::to('tests/edit/'.$id)->with_errors($validation->errors);
		}
	}

	/**
	 * Delete a specific test.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function get_delete($id)
	{
		$test = Test::find($id);

		if( ! is_null($test))
		{
			$test->delete();

			Session::flash('message', 'Deleted test #'.$test->id);
		}

		return Redirect::to('tests');
	}
}