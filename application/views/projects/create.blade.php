<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('projects')}}">Projects</a> <span class="divider">/</span>
		</li>
		<li class="active">New Project</li>
	</ul>
</div>

{{Form::open(null, 'post', array('class' => 'form-stacked'))}}
	<fieldset>
		<div class="clearfix">
			{{Form::label('project_clarity_id', 'Project Clarity Id')}}

			<div class="input">
				{{Form::text('project_clarity_id', Input::old('project_clarity_id'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_name', 'Project Name')}}

			<div class="input">
				{{Form::text('project_name', Input::old('project_name'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_scope', 'Project Scope')}}

			<div class="input">
				{{Form::textarea('project_scope', Input::old('project_scope'), array('class' => 'span10'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_pm', 'Project Pm')}}

			<div class="input">
				{{Form::text('project_pm', Input::old('project_pm'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_sponsor', 'Project Sponsor')}}

			<div class="input">
				{{Form::text('project_sponsor', Input::old('project_sponsor'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_description', 'Project Description')}}

			<div class="input">
				{{Form::textarea('project_description', Input::old('project_description'), array('class' => 'span10'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_start', 'Project Start')}}

			<div class="input">
				{{Form::text('project_start', Input::old('project_start'), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('project_end', 'Project End')}}

			<div class="input">
				{{Form::text('project_end', Input::old('project_end'), array('class' => 'span6'))}}
			</div>
		</div>

		<div class="actions">
			{{Form::submit('Save', array('class' => 'btn primary'))}}
		</div>
	</fieldset>
{{Form::close()}}