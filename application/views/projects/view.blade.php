<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('projects')}}">Projects</a> <span class="divider">/</span>
		</li>
		<li class="active">Viewing Project</li>
	</ul>
</div>

<div class="span16">
<p>
	<strong>Clarity ID:</strong>
	{{$project->project_clarity_id}}
</p>
<p>
	<strong>Name:</strong>
	{{$project->project_name}}
</p>
<p>
	<strong>Scope:</strong>
	{{$project->project_scope}}
</p>
<p>
	<strong>Project Manager:</strong>
	{{$project->project_pm}}
</p>
<p>
	<strong>Project Sponsor:</strong>
	{{$project->project_sponsor}}
</p>
<p>
	<strong>Description:</strong>
	{{$project->project_description}}
</p>
<p>
	<strong>Start Date:</strong>
	{{$project->project_start}}
</p>
<p>
	<strong>End Date:</strong>
	{{$project->project_end}}
</p>

    <h3>Requirements</h3>
    @if(count($project->urs) == 0)
    <p>No user requirements.</p>
    <a href="{{URL::to('projects/template/'.$project->id)}}">Apply All Template Requirements?</a>
    @else
    <table>
        <thead>
        <tr>

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
        @foreach($project->urs as $urs)
        <tr>

            <td>{{$urs->urs_type}}</td>
            <td>{{$urs->urs_id}}</td>
            <td>{{$urs->urs_description}}</td>
            <td>{{$urs->urs_status}}</td>
            <td>{{$urs->urs_created_by}}</td>
            <td>{{$urs->attribute1}}</td>
            <td>
                <a href="{{URL::to('urs/view/'.$urs->id)}}">View</a>
                <a href="{{URL::to('projects/edit_urs/'.$urs->id)}}">Edit</a>
                <a href="{{URL::to('projects/delete_urs/'.$urs->id)}}" onclick="return confirm('Are you sure?')">Remove</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    <p><a class="btn success" href="{{URL::to('projects/create_urs/'.$project->id)}}">Create new User Requirement</a></p>
<p><a href="{{URL::to('projects/edit/'.$project->id)}}">Edit</a> | <a href="{{URL::to('projects/delete/'.$project->id)}}" onclick="return confirm('Are you sure?')">Delete</a></p>
