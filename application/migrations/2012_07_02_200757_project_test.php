<?php

class Project_Test {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('project_test', function($table)
        {
            $table->increments('id');
            $table->string('project_id');
            $table->string('test_id');
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
		//
        Schema::drop('project_test');
	}

}