<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('posts')}}">Posts</a> <span class="divider">/</span>
		</li>
		<li class="active">Viewing Post</li>
	</ul>
</div>

<div class="span16">
<p>
	<strong>Title:</strong>
	{{$post->title}}
</p>
<p>
	<strong>Content:</strong>
	{{$post->content}}
</p>

<p><a href="{{URL::to('posts/edit/'.$post->id)}}">Edit</a> | <a href="{{URL::to('posts/delete/'.$post->id)}}" onclick="return confirm('Are you sure?')">Delete</a></p>
