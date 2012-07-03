<?php

class Project_Urs {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{
		//
        Schema::create('project_urs', function($table)
        {
            $table->increments('id');

            $table->string('project_id');
            $table->string('urs_id');
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
        Schema::drop('project_urs');
	}

}