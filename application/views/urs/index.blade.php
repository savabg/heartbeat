@if(count($urs) == 0)
	<p>No user requirements.</p>
@else
	<table>
		<thead>
			<tr>
                <th>Project</th>
                <th>Type</th>
                <th>ID</th>
				<th>Description</th>
				<th>Status</th>
				<th>Created By</th>
                <th>Attribute1</th>
				<th>Actions</th>
			</tr>
		</thead>

		<tbody>
			@foreach($urs as $urs)
				<tr>
                    <td>{{$urs->project_id}}</td>
                    <td>{{$urs->urs_type}}</td>
                    <td>{{$urs->urs_id}}</td>
					<td>{{$urs->urs_description}}</td>
					<td>{{$urs->urs_status}}</td>
					<td>{{$urs->urs_created_by}}</td>
					<td>{{$urs->attribute1}}</td>
					<td>
						<a href="{{URL::to('urs/view/'.$urs->id)}}">View</a>
						<a href="{{URL::to('urs/edit/'.$urs->id)}}">Edit</a>
						<a href="{{URL::to('urs/delete/'.$urs->id)}}" onclick="return confirm('Are you sure?')">Delete</a>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif

<p><a class="btn success" href="{{URL::to('urs/create')}}">Create new User Requirement</a></p>