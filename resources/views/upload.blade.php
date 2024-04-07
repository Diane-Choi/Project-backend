<!-- HTML form example -->
<form action="/upload" method="post" enctype="multipart/form-data">
    @csrf
    <input type="file" name="image">
    <button type="submit">Upload Image</button>
</form>