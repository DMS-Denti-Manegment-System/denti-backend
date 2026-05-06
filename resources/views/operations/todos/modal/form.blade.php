@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="todoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('todos.update', $editingTodo) : route('todos.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Todo Duzenle' : 'Yeni Todo' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-12">
                                <label class="form-label">Baslik</label>
                                <input class="form-control form-control-solid" name="title" value="{{ old('title', $editingTodo?->title) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select class="form-select form-select-solid" name="category_id">
                                    <option value="">Kategori secin</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('category_id', $editingTodo?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if($modalMode === 'edit')
                                <div class="col-md-6 d-flex align-items-center">
                                    <label class="form-check form-check-custom form-check-solid mt-8">
                                        <input class="form-check-input" type="checkbox" name="completed" value="1" @checked(old('completed', $editingTodo?->completed)) />
                                        <span class="form-check-label">Tamamlandi</span>
                                    </label>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label">Aciklama</label>
                                <textarea class="form-control form-control-solid" name="description" rows="4">{{ old('description', $editingTodo?->description) }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">{{ $modalMode === 'edit' ? 'Guncelle' : 'Olustur' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
