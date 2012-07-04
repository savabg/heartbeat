@if(count($tests) == 0)
	<p>No tests.</p>
@else
	<table>
		<thead>
			<tr>
				<th>Test Id</th>
				<th>Test Description</th>
				<th>Test Author</th>
				<th>Test Status</th>
				<th>Test Automated</th>
				<th></th>
			</tr>
		</thead>

		<tbody>
			@foreach($tests as $test)
				<tr>
					<td>{{$test->test_id}}</td>
					<td>{{$test->test_description}}</td>
					<td>{{$test->test_author}}</td>
					<td>{{$test->test_status}}</td>
					<td>{{($test->test_automated) ? 'True' : 'False'}}</td>
					<td>
						<a href="{{URL::to('tests/view/'.$test->id)}}">View</a>
						<a href="{{URL::to('tests/edit/'.$test->id)}}">Edit</a>
						<a href="{{URL::to('tests/delete/'.$test->id)}}" onclick="return confirm('Are you sure?')">Delete</a>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif

<p><a class="btn success" href="{{URL::to('tests/create')}}">Create new Test</a></p>