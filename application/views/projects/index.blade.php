@if(count($projects) == 0)
	<p>No projects.</p>
@else
	<table>
		<thead>
			<tr>
				<th>Project Clarity Id</th>
				<th>Project Name</th>
				<th>Project Scope</th>
				<th>Project Pm</th>
				<th>Project Sponsor</th>
				<th>Project Description</th>
				<th>Project Start</th>
				<th>Project End</th>
				<th></th>
			</tr>
		</thead>

		<tbody>
			@foreach($projects as $project)
				<tr>
					<td>{{$project->project_clarity_id}}</td>
					<td>{{$project->project_name}}</td>
					<td>{{$project->project_scope}}</td>
					<td>{{$project->project_pm}}</td>
					<td>{{$project->project_sponsor}}</td>
					<td>{{$project->project_description}}</td>
					<td>{{$project->project_start}}</td>
					<td>{{$project->project_end}}</td>
					<td>
						<a href="{{URL::to('projects/view/'.$project->id)}}">View</a>
						<a href="{{URL::to('projects/edit/'.$project->id)}}">Edit</a>
						<a href="{{URL::to('projects/delete/'.$project->id)}}" onclick="return confirm('Are you sure?')">Delete</a>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif

<p><a class="btn success" href="{{URL::to('projects/create')}}">Create new Project</a></p>