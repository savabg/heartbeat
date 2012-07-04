<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('urs')}}">Urs</a> <span class="divider">/</span>
		</li>
		<li class="active">New Urs</li>
	</ul>
</div>

{{Form::open(null, 'post', array('class' => 'form-stacked'))}}
	<fieldset>
		<div class="clearfix">
			{{Form::label('urs_id', 'Urs Id')}}

			<div class="input">
				{{Form::text('urs_id', Input::old('urs_id'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('urs_description', 'Urs Description')}}

			<div class="input">
				{{Form::textarea('urs_description', Input::old('urs_description'), array('class' => 'span10'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('urs_status', 'Urs Status')}}

			<div class="input">
				{{Form::text('urs_status', Input::old('urs_status'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('urs_created_by', 'Urs Created By')}}

			<div class="input">
				{{Form::text('urs_created_by', Input::old('urs_created_by'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('urs_type', 'Urs Type')}}

			<div class="input">
				{{Form::text('urs_type', Input::old('urs_type'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_id', 'Project Id')}}

			<div class="input">
				{{Form::text('project_id', Input::old('project_id'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('attribute1', 'Attribute1')}}

			<div class="input">
				{{Form::text('attribute1', Input::old('attribute1'), array('class' => 'span6'))}}
			</div>
		</div>

		<div class="actions">
			{{Form::submit('Save', array('class' => 'btn primary'))}}
		</div>
	</fieldset>
{{Form::close()}}