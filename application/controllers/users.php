<?php

class Users_Controller extends Base_Controller {


    public function __construct() {
        $this->filter('before', 'auth');
            //help
    }

	public function action_index()
	{
		// code here..
        $users = User::all();
        //var_dump($users);
        //test
		return View::make('users.index')->with(array('users'=>$users));
	}

	public function action_add()
	{
		// code here..

		return View::make('users.add');
	}

	public function action_view()
	{
		// code here..

		return View::make('users.view');
	}

	public function action_edit()
	{
		// code here..

		return View::make('users.edit');
	}

	public function action_delete()
	{
		// code here..

		return View::make('users.delete');
	}

}
