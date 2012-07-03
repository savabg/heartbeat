<?php

class Create_Projects_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('projects', function($table)
		{
			$table->increments('id');

			$table->string('project_clarity_id');
			$table->string('project_name');
			$table->text('project_scope');
			$table->integer('project_pm');
			$table->integer('project_sponsor');
			$table->text('project_description');
			$table->date('project_start');
			$table->date('project_end');

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
		Schema::drop('projects');
	}

}