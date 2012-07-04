<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('test_steps')}}">Test Steps</a> <span class="divider">/</span>
		</li>
		<li class="active">Editing Test Step</li>
	</ul>
</div>

{{Form::open(null, 'post', array('class' => 'form-stacked'))}}
	<fieldset>
		<div class="clearfix">
			{{Form::label('step_id', 'Step Id')}}

			<div class="input">
				{{Form::text('step_id', Input::old('step_id', $test_step->step_id), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('step_description', 'Step Description')}}

			<div class="input">
				{{Form::textarea('step_description', Input::old('step_description', $test_step->step_description), array('class' => 'span10'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('step_expected_result', 'Step Expected Result')}}

			<div class="input">
				{{Form::textarea('step_expected_result', Input::old('step_expected_result', $test_step->step_expected_result), array('class' => 'span10'))}}
			</div>
		</div>

		<div class="actions">
			{{Form::submit('Save', array('class' => 'btn primary'))}}
		</div>
	</fieldset>
{{Form::close()}}