<div class="span16">
	<ul class="breadcrumb span6">
		<li>
			<a href="{{URL::to('posts')}}">Posts</a> <span class="divider">/</span>
		</li>
		<li class="active">Editing Post</li>
	</ul>
</div>

{{Form::open(null, 'post', array('class' => 'form-stacked'))}}
	<fieldset>
		<div class="clearfix">
			{{Form::label('title', 'Title')}}

			<div class="input">
				{{Form::text('title', Input::old('title', $post->title), array('class' => 'span6'))}}
			</div>
		</div>
		<div class="clearfix">
			{{Form::label('content', 'Content')}}

			<div class="input">
				{{Form::textarea('content', Input::old('content', $post->content), array('class' => 'span10'))}}
			</div>
		</div>

		<div class="actions">
			{{Form::submit('Save', array('class' => 'btn primary'))}}
		</div>
	</fieldset>
{{Form::close()}}