<div>

    <!-- Providing users the ability to search posts by Category. -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="" class="form-label">Search</label>
            <input type="text" class="form-control" placeholder="Keyword..." wire:model='search'>
        </div>
        <div class="col-md-2 mb-3">
            <label for="" class="form-label">Category</label>
            <select class="form-select" wire:model='category'>
                <option value="">-- No selected ---</option>
                @foreach (\App\Models\SubCategory::whereHas('posts')->get() as $category)
                    <option value="{{ $category->id }}">{{ $category->subcategory_name }}</option>
                @endforeach
                
            </select>
        </div>

        <!-- Only Admins can sort by User. Allows for Admin to sort posts by specific Users. -->
        @if (auth()->user()->type == 1)
        <div class="col-md-2 mb-3">
            <label for="" class="form-label">Author</label>
            <select  class="form-select" wire:model='author'>
                <option value="">-- No selected ---</option>
                @foreach(\App\Models\User::whereHas('posts')->get() as $author)
                <option value="{{ $author->id }}">{{ $author->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Providing user option to sort posts by ascending or descending -->
        <div class="col-md-2 mb-3">
            <label for="" class="form-label">orderBy</label>
            <select class="form-select" wire:model='orderBy'>
                <option value="asc">ASC</option>
                <option value="desc">DESC</option>
            </select>
        </div>

    </div>
   
    <!-- Sorts users posts individually, then presents them following the below specifics (only shows four posts at a times because pagination) -->
   <div class="row row-cards">
    @forelse($posts as $post)
    <div class="col-md-6 col-lg-3">
        <div class="card">
            <img src="/storage/images/post_images/thumbnails/resized_{{$post->featured_image}}" alt="" class="card-img-top">
            <div class="card-body p-2">
                <h3 class="m-0 mb-1">{{$post->post_title}}</h3>
            </div>
            <div class="d-flex">
                <a href="{{ route('author.posts.edit-post',['post_id'=>$post->id]) }}" class="card-btn">Edit</a>
                <a href="" class="card-btn">Delete</a>
            </div>
        </div>
    </div>

    <!-- Small validation that shows No Posts if a post not fitting the criteria can be met -->
    @empty
      <span class="text-danger">No Post Found</span>
    @endforelse

   </div>

   <!-- Bootstrap Implementation For Posts. -->
   <div class="d-block mt-2">
    {{ $posts->links('livewire::bootstrap') }}
   </div>

</div>
