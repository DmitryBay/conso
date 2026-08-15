@extends('layouts.workspace')
@section('title',__('workspace.service_tree'))
@section('content')
<div class="d-flex align-items-end justify-content-between gap-3 mb-4"><div><div class="eyebrow">{{ __('workspace.guest_catalog') }}</div><h1 class="page-title">{{ __('workspace.service_builder') }}</h1><p class="page-subtitle">{{ __('workspace.builder_intro') }}</p></div><div class="page-actions d-flex gap-2"><form method="POST" action="{{ route('workspace.services.guides.bali') }}" onsubmit="return confirm('{{ __('workspace.bali_guides_confirm') }}')">@csrf<button class="btn btn-light"><i class="bi bi-map me-sm-2"></i><span>{{ __('workspace.add_bali_guides') }}</span></button></form><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNodeModal"><i class="bi bi-plus-lg me-sm-2"></i><span>{{ __('workspace.add_item') }}</span></button></div></div>
<div class="row g-3"><div class="col-xl-9"><div class="surface-card overflow-hidden"><div class="d-flex align-items-center justify-content-between p-4 border-bottom"><div><h2 class="section-title">{{ __('workspace.catalog_structure') }}</h2><div class="text-secondary mt-1" style="font-size:12px">{{ __('workspace.items_published',['items'=>$nodes->count(),'published'=>$nodes->where('is_active',true)->count()]) }}</div></div><span class="badge text-bg-light border"><i class="bi bi-phone me-1"></i> {{ __('workspace.guest_catalog_badge') }}</span></div>
@if($roots->isEmpty())<div class="empty-builder"><span class="empty-builder-icon"><i class="bi bi-diagram-3"></i></span><h3>{{ __('workspace.empty_tree') }}</h3><p>{{ __('workspace.empty_tree_hint') }}</p><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNodeModal">{{ __('workspace.create_first_section') }}</button></div>@else<ul class="service-tree root-tree">@foreach($roots as $node)@include('workspace.services._node',['node'=>$node,'level'=>0])@endforeach</ul>@endif
</div></div><div class="col-xl-3"><div class="surface-card p-4"><div class="metric-icon"><i class="bi bi-lightbulb"></i></div><h2 class="section-title mt-3">{{ __('workspace.how_build') }}</h2><ol class="builder-tips"><li>{{ __('workspace.tip_1') }}</li><li>{{ __('workspace.tip_2') }}</li><li>{{ __('workspace.tip_3') }}</li><li>{{ __('workspace.tip_4') }}</li></ol></div></div></div>
@endsection
@push('modals')
<div class="modal fade" id="addNodeModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('workspace.services.store') }}">@csrf<div class="modal-header"><div><h2 class="modal-title fs-5">{{ __('workspace.new_catalog_item') }}</h2><div class="text-secondary small">{{ __('workspace.category_or_service') }}</div></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">@include('workspace.services._fields',['translationScope'=>'add'])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('workspace.cancel') }}</button><button class="btn btn-primary">{{ __('workspace.add') }}</button></div></form></div></div>
<div class="modal fade" id="editNodeModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="">@csrf @method('PUT')<div class="modal-header"><div><h2 class="modal-title fs-5">{{ __('workspace.edit_item') }}</h2><div class="text-secondary small">{{ __('workspace.changes_live') }}</div></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4">@include('workspace.services._fields',['translationScope'=>'edit'])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('workspace.cancel') }}</button><button class="btn btn-primary">{{ __('workspace.save') }}</button></div></form></div></div>
@endpush
@push('scripts')
<script type="application/json" id="serviceNodeTranslations">{!! $nodes->mapWithKeys(fn($node) => [(string)$node->id => $node->translations ?: []])->toJson(JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/json" id="serviceNodeOptions">{!! $nodes->mapWithKeys(fn($node) => [(string)$node->id => $node->option_keys ?: []])->toJson(JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const translations = JSON.parse(document.getElementById('serviceNodeTranslations').textContent);
    const options = JSON.parse(document.getElementById('serviceNodeOptions').textContent);
    const toggleFields = modal => { const type=modal.querySelector('.node-type-select').value; modal.querySelector('.node-service-fields').classList.toggle('d-none',type!=='service'); };
    document.querySelectorAll('.node-type-select').forEach(el=>{el.addEventListener('change',()=>toggleFields(el.closest('.modal'))); toggleFields(el.closest('.modal'));});
    document.querySelectorAll('.add-child-node').forEach(btn=>btn.addEventListener('click',()=>{document.querySelector('#addNodeModal .node-parent').value=btn.dataset.parent;}));
    document.querySelectorAll('.edit-service-node').forEach(btn=>btn.addEventListener('click',()=>{
        const modal=document.getElementById('editNodeModal');
        modal.querySelector('form').action='/workspace/services/'+btn.dataset.id;
        modal.querySelector('.node-name').value=btn.dataset.name;
        modal.querySelector('.node-type-select').value=btn.dataset.type;
        modal.querySelector('.node-parent').value=btn.dataset.parent||'';
        modal.querySelector('.node-description').value=btn.dataset.description||'';
        modal.querySelector('.node-icon').value=btn.dataset.icon||'bi-stars';
        modal.querySelector('.node-price').value=btn.dataset.price||'';
        modal.querySelector('.node-sla').value=btn.dataset.sla||'';
        modal.querySelector('.node-order').value=btn.dataset.order||0;
        modal.querySelector('.node-active').checked=btn.dataset.active==='1';
        modal.querySelectorAll('.node-option').forEach(field=>field.checked=(options[btn.dataset.id]||[]).includes(field.value));
        modal.querySelectorAll('.node-translation-name,.node-translation-description').forEach(field=>field.value='');
        Object.entries(translations[btn.dataset.id]||{}).forEach(([locale,value])=>{
            const name=typeof value==='string'?value:(value?.name||'');
            const description=typeof value==='object'&&value?(value.description||''):'';
            const nameField=modal.querySelector('.node-translation-name[data-locale="'+locale+'"]');
            const descriptionField=modal.querySelector('.node-translation-description[data-locale="'+locale+'"]');
            if(nameField) nameField.value=name;
            if(descriptionField) descriptionField.value=description;
        });
        const background=modal.querySelector('.node-background[value="'+(btn.dataset.backgroundImage||'')+'"]');
        if(background) background.checked=true;
        toggleFields(modal);
    }));
});
</script>
@endpush
