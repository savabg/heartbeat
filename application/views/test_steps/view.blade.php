<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('test_steps')}}">Test Steps</a> <span class="divider">/</span>
		</li>
		<li class="active">Viewing Test Step</li>
	</ul>
</div>

<div class="span16">
<p>
	<strong>Step id:</strong>
	{{$test_step->step_id}}
</p>
<p>
	<strong>Step description:</strong>
	{{$test_step->step_description}}
</p>
<p>
	<strong>Step expected result:</strong>
	{{$test_step->step_expected_result}}
</p>

<p><a href="{{URL::to('test_steps/edit/'.$test_step->id)}}">Edit</a> | <a href="{{URL::to('test_steps/delete/'.$test_step->id)}}" onclick="return confirm('Are you sure?')">Delete</a></p>
