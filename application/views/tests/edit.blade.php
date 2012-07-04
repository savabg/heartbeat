<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('tests')}}">Tests</a> <span class="divider">/</span>
		</li>
		<li class="active">Editing Test</li>
	</ul>
</div>

{{Form::open(null, 'post', array('class' => 'form-stacked'))}}
	<fieldset>
		<div class="clearfix">
			{{Form::label('test_id', 'Test Id')}}

			<div class="input">
				{{Form::text('test_id', Input::old('test_id', $test->test_id), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('test_description', 'Test Description')}}

			<div class="input">
				{{Form::textarea('test_description', Input::old('test_description', $test->test_description), array('class' => 'span10'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('test_author', 'Test Author')}}

			<div class="input">
				{{Form::text('test_author', Input::old('test_author', $test->test_author), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('test_status', 'Test Status')}}

			<div class="input">
				{{Form::text('test_status', Input::old('test_status', $test->test_status), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('test_automated', 'Test Automated')}}

			<div class="input">
				{{Form::checkbox('test_automated', '1', Input::old('test_automated', $test->test_automated))}}
			</div>
		</div>

		<div class="actions">
			{{Form::submit('Save', array('class' => 'btn primary'))}}
		</div>
	</fieldset>
{{Form::close()}}