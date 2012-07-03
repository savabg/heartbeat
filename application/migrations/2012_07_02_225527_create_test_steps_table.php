<?php

class Create_Test_Steps_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('test_steps', function($table)
		{
			$table->increments('id');

			$table->integer('step_id');
			$table->text('step_description');
			$table->text('step_expected_result');

			$table->timestamps();
		});
	}

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('test_steps');
	}

}