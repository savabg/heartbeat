<?php

class Projects_Controller extends Controller {

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
	 * View all of the projects.
	 *
	 * @return void
	 */
	public function get_index()
	{
		$projects = Project::all();

		$this->layout->title   = 'Projects';
		$this->layout->content = View::make('projects.index')->with('projects', $projects);
	}

	/**
	 * Show the form to create a new project.
	 *
	 * @return void
	 */
	public function get_create()
	{
		$this->layout->title   = 'New Project';
		$this->layout->content = View::make('projects.create');
	}

	/**
	 * Create a new project.
	 *
	 * @return Response
	 */
	public function post_create()
	{
		$validation = Validator::make(Input::all(), array(
			'project_clarity_id' => array('required'),
			'project_name' => array('required'),
			'project_scope' => array('required'),
			'project_pm' => array('required', 'integer'),
			'project_sponsor' => array('required', 'integer'),
			'project_description' => array('required'),
			'project_start' => array('required'),
			'project_end' => array('required'),
		));

		if($validation->valid())
		{
			$project = new Project;

			$project->project_clarity_id = Input::get('project_clarity_id');
			$project->project_name = Input::get('project_name');
			$project->project_scope = Input::get('project_scope');
			$project->project_pm = Input::get('project_pm');
			$project->project_sponsor = Input::get('project_sponsor');
			$project->project_description = Input::get('project_description');
			$project->project_start = Input::get('project_start');
			$project->project_end = Input::get('project_end');

			$project->save();

			Session::flash('message', 'Added project #'.$project->id);

			return Redirect::to('projects');
		}

		else
		{
			return Redirect::to('projects/create')->with_errors($validation->errors);
		}
	}

	/**
	 * View a specific project.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_view($id)
	{
        Session::put('project_id', $id);
		$project = Project::find($id);
        $urs = $project->urs();

		if(is_null($project))
		{
			return Redirect::to('projects');
		}

		$this->layout->title   = 'Viewing Project #'.$id;
		$this->layout->content = View::make('projects.view')->with('project', $project);
	}
    public function get_template($id)
    {
        $project = Project::find($id);

        $urs = Urs::where('urs_type', '=', 1)->get();
        //die(print_r($urs));

        foreach ($urs as $urs)
        {
            $project->urs()->attach($urs->id);

        }
        $this->layout->title   = 'Viewing Project #'.$id;
        $this->layout->content = View::make('projects.view')->with('project', $project);
    }
	/**
	 * Show the form to edit a specific project.
	 *
	 * @param  int   $id
	 * @return void
	 */
	public function get_edit($id)
	{
		$project = Project::find($id);

		if(is_null($project))
		{
			return Redirect::to('projects');
		}

		$this->layout->title   = 'Editing Project';
		$this->layout->content = View::make('projects.edit')->with('project', $project);
	}
    public function get_edit_urs($id)
    {
        $urs = Urs::find($id);

        if(is_null($urs))
        {
            return Redirect::to('urs');
        }

        $this->layout->title   = 'Editing Urs';
        $this->layout->content = View::make('urs.edit')->with('urs', $urs);
    }

    public function post_edit_urs($id)
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
            $project_id = Session::get('project_id');
            return Redirect::to('projects/view/'.Session::get('project_id'));
        }

        else
        {
            return Redirect::to('urs/edit/'.$id)->with_errors($validation->errors);
        }
    }

    public function get_delete_urs($id)
    {
        $project_id = Session::get('project_id');
        $project= Project::find($project_id);

        //$urs = Urs::find($id);

        if( ! is_null($project))
        {
            $project->urs()->detach($id);

            Session::flash('message', 'Removed Requirement From Project #'.$project->project_name);
        }

        return Redirect::to('projects/view/'.Session::get('project_id'));
    }

    public function get_create_urs()
    {
        $this->layout->title   = 'New User Requirement';
        $this->layout->content = View::make('urs.create');
    }

    public function post_create_urs($id)
    {
        $validation = Validator::make(Input::all(), array(
            'urs_id' => array('required'),
            'urs_description' => array('required'),
            'urs_status' => array('required', 'integer'),
            'urs_created_by' => array('required', 'integer'),
            'urs_type' => array('required', 'integer'),
            'attribute1' => array('required', 'integer'),
        ));

        if($validation->valid())
        {
            $project_id = Session::get('project_id');

            $urs = new Urs;

            $urs->urs_id = Input::get('urs_id');
            $urs->urs_description = Input::get('urs_description');
            $urs->urs_status = Input::get('urs_status');
            $urs->urs_created_by = Input::get('urs_created_by');
            $urs->urs_type = Input::get('urs_type');
            //$urs->project_id = Input::get('project_id');
            $urs->attribute1 = Input::get('attribute1');

            //$urs->save();
            $project = Project::find($project_id);
            $u_id = $project->urs()->insert($urs);
            Session::flash('message', 'Added urs');

            return Redirect::to('projects/view/'.Session::get('project_id'));
        }
    }
	/**
	 * Edit a specific project.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function post_edit($id)
	{
		$validation = Validator::make(Input::all(), array(
			'project_clarity_id' => array('required'),
			'project_name' => array('required'),
			'project_scope' => array('required'),
			'project_pm' => array('required', 'integer'),
			'project_sponsor' => array('required', 'integer'),
			'project_description' => array('required'),
			'project_start' => array('required'),
			'project_end' => array('required'),
		));

		if($validation->valid())
		{
			$project = Project::find($id);

			if(is_null($project))
			{
				return Redirect::to('projects');
			}

			$project->project_clarity_id = Input::get('project_clarity_id');
			$project->project_name = Input::get('project_name');
			$project->project_scope = Input::get('project_scope');
			$project->project_pm = Input::get('project_pm');
			$project->project_sponsor = Input::get('project_sponsor');
			$project->project_description = Input::get('project_description');
			$project->project_start = Input::get('project_start');
			$project->project_end = Input::get('project_end');

			$project->save();

			Session::flash('message', 'Updated project #'.$project->id);

			return Redirect::to('projects');
		}

		else
		{
			return Redirect::to('projects/edit/'.$id)->with_errors($validation->errors);
		}
	}

	/**
	 * Delete a specific project.
	 *
	 * @param  int       $id
	 * @return Response
	 */
	public function get_delete($id)
	{
		$project = Project::find($id);

		if( ! is_null($project))
		{
			$project->delete();

			Session::flash('message', 'Deleted project #'.$project->id);
		}

		return Redirect::to('projects');
	}
}