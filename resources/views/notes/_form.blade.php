{{-- Shared form fields for create and edit --}}
<label for="title">Title</label>
<input id="title" type="text" name="title" value="{{ old('title', $note->title ?? '') }}" required autofocus>
@error('title') <p class="error">{{ $message }}</p> @enderror

<label for="body">Note</label>
<textarea id="body" name="body" rows="8">{{ old('body', $note->body ?? '') }}</textarea>
@error('body') <p class="error">{{ $message }}</p> @enderror
