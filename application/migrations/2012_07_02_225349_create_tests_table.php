<?php

class Create_Tests_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::create('tests', function($table)
		{
			$table->increments('id');

			$table->string('test_id');
			$table->text('test_description');
			$table->integer('test_author');
			$table->integer('test_status');
			$table->boolean('test_automated');

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
		Schema::drop('tests');
	}

}