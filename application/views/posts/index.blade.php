@if(count($posts) == 0)
	<p>No posts.</p>
@else
	<table>
		<thead>
			<tr>
				<th>Title</th>
				<th>Content</th>
				<th></th>
			</tr>
		</thead>

		<tbody>
			@foreach($posts as $post)
				<tr>
					<td>{{$post->title}}</td>
					<td>{{$post->content}}</td>
					<td>
						<a href="{{URL::to('posts/view/'.$post->id)}}">View</a>
						<a href="{{URL::to('posts/edit/'.$post->id)}}">Edit</a>
						<a href="{{URL::to('posts/delete/'.$post->id)}}" onclick="return confirm('Are you sure?')">Delete</a>
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
@endif

<p><a class="btn success" href="{{URL::to('posts/create')}}">Create new Post</a></p>