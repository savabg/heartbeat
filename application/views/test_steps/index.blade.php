@if(count($test_steps) == 0)
	<p>No test steps.</p>
@else
	<table>
		<thead>
			<tr>
				<th>Step Id</th>
				<th>Step Description</th>
				<th>Step Expected Result</th>
				<th></th>
			</tr>
		</thead>

		<tbody>
			@foreach($test_steps as $test_step)
				<tr>
					<td>{{$test_step->step_id}}</td>
					<td>{{$test_step->step_description}}</td>
					<td>{{$test_step->step_expected_result}}</td>
					<td>
						<a href="{{URL::to('test_steps/view/'.$test_step->id)}}">View</a>
						<a href="{{URL::to('test_steps/edit/'.$test_step->id)}}">Edit</a>
						<a href="{{URL::to('test_steps/delete/'.$test_step->id)}}" onclick="return confirm('Are you sure?')">Delete</a>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif

<p><a class="btn success" href="{{URL::to('test_steps/create')}}">Create new Test Step</a></p>