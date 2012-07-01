<?php

class Hello_Controller extends Base_Controller {

	public function action_index()
	{
		// code here..

		return View::make('hello.index');
	}

}
