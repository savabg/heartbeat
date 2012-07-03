<?php

class Test_Steps_Controller extends Controller {

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
	 * View all of the test_steps.
	 *
	 * @return void
	 */
	public function get_index()
	{
		$test_steps = Test_Step::all();

		$this->layout->title   = 'Test Steps';
		$this->layout->content = View::make('test_steps.index')->with('test_steps', $test_steps);
	}

	/**
	 * Show the form to create a new test_step.
	 *
	 * @return void
	 */
	public function get_create()
	{
		$this->layout->title   = 'New Test Step';
		$this->layout->content = View::make('test_steps.create');
	}

	/**
	 * Create a new test_step.
	 *
	 * @return Response
	 */
	public function post_create()
	{
		$validation = Validator::make(Input::all(), array(
			'step_id' => array('required', 'integer'),
			'step_description' => array('required'),
			'step_expected_result' => array('required'),
		));

		if($validation->valid())
		{
			$test_step = new Test_Step;

			$test_step->step_id = Input::get('step_id');
			$test_step->step_description = Input::get('step_description');
			$test_step->step_expected_result = Input::get('step_expected_result');

			$test_step->save();

			Session::flash('message', 'Added test step #'.$test_step->id);

			return Redirect::to('test_steps');
		}

		else
		{
			return Redirect::to('test_steps/create')->with_errors($validation->errors);
		}
	}

	/**
	 * View a specific test_step.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_view($id)
	{
		$test_step = Test_Step::find($id);

		if(is_null($test_step))
		{
			return Redirect::to('test_steps');
		}

		$this->layout->title   = 'Viewing Test Step #'.$id;
		$this->layout->content = View::make('test_steps.view')->with('test_step', $test_step);
	}

	/**
	 * Show the form to edit a specific test_step.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_edit($id)
	{
		$test_step = Test_Step::find($id);

		if(is_null($test_step))
		{
			return Redirect::to('test_steps');
		}

		$this->layout->title   = 'Editing Test Step';
		$this->layout->content = View::make('test_steps.edit')->with('test_step', $test_step);
	}

	/**
	 * Edit a specific test_step.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function post_edit($id)
	{
		$validation = Validator::make(Input::all(), array(
			'step_id' => array('required', 'integer'),
			'step_description' => array('required'),
			'step_expected_result' => array('required'),
		));

		if($validation->valid())
		{
			$test_step = Test_Step::find($id);

			if(is_null($test_step))
			{
				return Redirect::to('test_steps');
			}

			$test_step->step_id = Input::get('step_id');
			$test_step->step_description = Input::get('step_description');
			$test_step->step_expected_result = Input::get('step_expected_result');

			$test_step->save();

			Session::flash('message', 'Updated test step #'.$test_step->id);

			return Redirect::to('test_steps');
		}

		else
		{
			return Redirect::to('test_steps/edit/'.$id)->with_errors($validation->errors);
		}
	}

	/**
	 * Delete a specific test_step.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function get_delete($id)
	{
		$test_step = Test_Step::find($id);

		if( ! is_null($test_step))
		{
			$test_step->delete();

			Session::flash('message', 'Deleted test step #'.$test_step->id);
		}

		return Redirect::to('test_steps');
	}
}