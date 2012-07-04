<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('tests')}}">Tests</a> <span class="divider">/</span>
		</li>
		<li class="active">Viewing Test</li>
	</ul>
</div>

<div class="span16">
<p>
	<strong>Test id:</strong>
	{{$test->test_id}}
</p>
<p>
	<strong>Test description:</strong>
	{{$test->test_description}}
</p>
<p>
	<strong>Test author:</strong>
	{{$test->test_author}}
</p>
<p>
	<strong>Test status:</strong>
	{{$test->test_status}}
</p>
<p>
	<strong>Test automated:</strong>
	{{($test->test_automated) ? 'True' : 'False'}}
</p>

<p><a href="{{URL::to('tests/edit/'.$test->id)}}">Edit</a> | <a href="{{URL::to('tests/delete/'.$test->id)}}" onclick="return confirm('Are you sure?')">Delete</a></p>
