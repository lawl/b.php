<div>
<form action="{{SELF}}" method="post">
	Title <input name="posttitle" type="text" value="{{POSTTITLE}}"><br />
	Post<br />
	<textarea name="postcontent" rows="10" cols="70">{{POSTCONTENT}}</textarea><br />
	<input name="postid" type="hidden" value="{{POSTID}}" />
	<input name="submitpost" type="submit" value="commit" />
</form>
<a href="?rbindex">rebuild index</a>
</div>