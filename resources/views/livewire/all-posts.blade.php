<div>
    <div class="row row-cards">

        @forelse($posts as $post)
        <div class="col-md-6 col-lg-3">
            <div class="card">
                <img src="/storage/images/post_images/thumbnails/resized_{{$post->featured_image}}" alt="" class="card-img-top">
                <div class="card-body p-2">
                    <h3 class="m-0 mb-1">{{$post->post_title}}</h3>
                </div>
                <div class="d-flex">
                    <a href="" class="card-btn">Edit</a>
                    <a href="" class="card-btn">Delete</a>
                </div>
            </div>
        </div>
        @empty
            <span class="text-danger">No Posts found</span>
        @endforelse
    </div>
</div>
