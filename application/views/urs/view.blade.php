<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('urs')}}">Urs</a> <span class="divider">/</span>
		</li>
		<li class="active">Viewing User Requirement</li>
	</ul>
</div>

<div class="span16">
<p>
	<strong>ID</strong>
	{{$urs->urs_id}}
</p>
<p>
	<strong>Description:</strong>
	{{$urs->urs_description}}
</p>
<p>
	<strong>Status:</strong>
	{{$urs->urs_status}}
</p>
<p>
	<strong>Created by:</strong>
	{{$urs->urs_created_by}}
</p>
<p>
	<strong>Type:</strong>
	{{$urs->urs_type}}
</p>
<p>
	<strong>Project id:</strong>
	{{$urs->project_id}}
</p>
<p>
	<strong>Attribute1:</strong>
	{{$urs->attribute1}}
</p>

<p><a href="{{URL::to('urs/edit/'.$urs->id)}}">Edit</a> | <a href="{{URL::to('urs/delete/'.$urs->id)}}" onclick="return confirm('Are you sure?')">Delete</a></p>
