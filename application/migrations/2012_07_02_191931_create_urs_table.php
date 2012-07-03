<?php

class Create_Urs_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('urs', function($table)
		{
			$table->increments('id');

			$table->string('urs_id');
			$table->text('urs_description');
			$table->integer('urs_status');
			$table->integer('urs_created_by');
			$table->integer('urs_type');
			$table->integer('project_id');
			$table->integer('attribute1');

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
		Schema::drop('urs');
	}

}