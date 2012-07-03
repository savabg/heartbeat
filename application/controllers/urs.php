<?php

class Urs_Controller extends Controller {

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
	 * View all of the urs.
	 *
	 * @return void
	 */
	public function get_index()
	{
		$urs = Urs::all();

		$this->layout->title   = 'User Requirements';
		$this->layout->content = View::make('urs.index')->with('urs', $urs);
	}

	/**
	 * Show the form to create a new urs.
	 *
	 * @return void
	 */
	public function get_create()
	{
		$this->layout->title   = 'New User Requirement';
		$this->layout->content = View::make('urs.create');
	}

	/**
	 * Create a new urs.
	 *
	 * @return Response
	 */
	public function post_create()
	{
		$validation = Validator::make(Input::all(), array(
			'urs_id' => array('required'),
			'urs_description' => array('required'),
			'urs_status' => array('required', 'integer'),
			'urs_created_by' => array('required', 'integer'),
			'urs_type' => array('required', 'integer'),
			'project_id' => array('required', 'integer'),
			'attribute1' => array('required', 'integer'),
		));

		if($validation->valid())
		{
			$urs = new Urs;

			$urs->urs_id = Input::get('urs_id');
			$urs->urs_description = Input::get('urs_description');
			$urs->urs_status = Input::get('urs_status');
			$urs->urs_created_by = Input::get('urs_created_by');
			$urs->urs_type = Input::get('urs_type');
			$urs->project_id = Input::get('project_id');
			$urs->attribute1 = Input::get('attribute1');

			$urs->save();

			Session::flash('message', 'Added urs #'.$urs->id);

			return Redirect::to('urs');
		}

		else
		{
			return Redirect::to('urs/create')->with_errors($validation->errors);
		}
	}

	/**
	 * View a specific urs.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_view($id)
	{
		$urs = Urs::find($id);

		if(is_null($urs))
		{
			return Redirect::to('urs');
		}

		$this->layout->title   = 'Viewing Urs #'.$id;
		$this->layout->content = View::make('urs.view')->with('urs', $urs);
	}

	/**
	 * Show the form to edit a specific urs.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_edit($id)
	{
		$urs = Urs::find($id);

		if(is_null($urs))
		{
			return Redirect::to('urs');
		}

		$this->layout->title   = 'Editing Urs';
		$this->layout->content = View::make('urs.edit')->with('urs', $urs);
	}

	/**
	 * Edit a specific urs.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function post_edit($id)
	{

		$validation = Validator::make(Input::all(), array(
			'urs_id' => array('required'),
			'urs_description' => array('required'),
			'urs_status' => array('required', 'integer'),
			'urs_created_by' => array('required', 'integer'),
			'urs_type' => array('required', 'integer'),
			'project_id' => array('required', 'integer'),
			'attribute1' => array('required', 'integer'),
		));

		if($validation->valid())
		{
			$urs = Urs::find($id);

			if(is_null($urs))
			{
				return Redirect::to('urs');
			}

			$urs->urs_id = Input::get('urs_id');
			$urs->urs_description = Input::get('urs_description');
			$urs->urs_status = Input::get('urs_status');
			$urs->urs_created_by = Input::get('urs_created_by');
			$urs->urs_type = Input::get('urs_type');
			$urs->project_id = Input::get('project_id');
			$urs->attribute1 = Input::get('attribute1');

			$urs->save();
            Version::add($urs);
			Session::flash('message', 'Updated urs #'.$urs->id);

			return Redirect::to('urs');
		}

		else
		{
			return Redirect::to('urs/edit/'.$id)->with_errors($validation->errors);
		}
	}

	/**
	 * Delete a specific urs.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function get_delete($id)
	{
		$urs = Urs::find($id);

		if( ! is_null($urs))
		{
			$urs->delete();

			Session::flash('message', 'Deleted urs #'.$urs->id);
		}

		return Redirect::to('urs');
	}
}