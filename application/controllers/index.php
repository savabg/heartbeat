<?php

class Index_Controller extends Base_Controller {

	public function action_index()
	{
		// code here..

		return View::make('index.index');
	}

	public function action_add()
	{
		// code here..

		return View::make('index.add');
	}

	public function action_edit()
	{
		// code here..

		return View::make('index.edit');
	}

	public function action_delete()
	{
		// code here..

		return View::make('index.delete');
	}

	public function action_save()
	{
		// code here..

		return View::make('index.save');
	}

}
