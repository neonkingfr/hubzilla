<div id="thread-wrapper-{{$item.id}}" class="thread-wrapper{{if $item.toplevel}} {{$item.toplevel}} clearfix generic-content-wrapper{{/if}}" data-b64mids='{{$item.mids}}'>
	<a name="{{$item.id}}" ></a>
	<div class="clearfix wall-item-outside-wrapper {{$item.indent}}{{$item.previewing}}{{if $item.owner_url}} wallwall{{/if}}" id="wall-item-outside-wrapper-{{$item.id}}" >
		<div class="wall-item-content-wrapper {{$item.indent}}" id="wall-item-content-wrapper-{{$item.id}}">
			{{if $item.photo}}
			<div class="wall-photo-item" id="wall-photo-item-{{$item.id}}">
				{{$item.photo}}
			</div>
			{{/if}}
			{{if $item.event}}
			<div class="wall-event-item" id="wall-event-item-{{$item.id}}">
				{{$item.event}}
			</div>
			{{/if}}
			{{if $item.title && !$item.event}}
			<div class="p-2{{if $item.is_new}} bg-primary text-white{{/if}} wall-item-title h3{{if !$item.photo}} rounded-top{{/if}}" id="wall-item-title-{{$item.id}}">
				{{if $item.title_tosource}}{{if $item.plink}}<a href="{{$item.plink.href}}" title="{{$item.title}} ({{$item.plink.title}})">{{/if}}{{/if}}{{$item.title}}{{if $item.title_tosource}}{{if $item.plink}}</a>{{/if}}{{/if}}
			</div>
			{{if ! $item.is_new}}
			<hr class="m-0">
			{{/if}}
			{{/if}}
			<div class="p-2 wall-item-head{{if !$item.title && !$item.event && !$item.photo}} rounded-top{{/if}}{{if $item.is_new && !$item.event && !$item.is_comment}} wall-item-head-new{{/if}}" >
				<div class="text-end float-end">
					<div class="wall-item-ago opacity-75" id="wall-item-ago-{{$item.id}}">
						{{if $item.editedtime}}
						<i class="fa fa-pencil"></i>
						{{/if}}
						{{if $item.delayed}}
						<i class="fa fa-clock-o"></i>
						{{/if}}
						{{if $item.location}}
						<small class="wall-item-location p-location" id="wall-item-location-{{$item.id}}">{{$item.location}}</small>
						{{/if}}
						{{if $item.verified}}
						<i class="fa fa-check text-success" title="{{$item.verified}}"></i>
						{{elseif $item.forged}}
						<i class="fa fa-exclamation text-danger" title="{{$item.forged}}"></i>
						{{/if}}
						<small class="autotime" title="{{$item.isotime}}"><time class="dt-published" datetime="{{$item.isotime}}">{{$item.localtime}}</time>{{if $item.editedtime}}&nbsp;{{$item.editedtime}}{{/if}}{{if $item.expiretime}}&nbsp;{{$item.expiretime}}{{/if}}</small>
					</div>
					{{if $item.thr_parent}}
					<a href="javascript:doscroll('{{$item.thr_parent}}',{{$item.parent}});" class="ms-3" title="{{$item.top_hint}}"><i class="fa fa-angle-double-up"></i></a>
					{{/if}}
					{{if $item.pinned}}
					<div class="wall-item-pinned" title="{{$item.pinned}}" id="wall-item-pinned-{{$item.id}}"><i class="fa fa-thumb-tack"></i></div>
					{{/if}}
				</div>
				<div class="float-start wall-item-info pe-2" id="wall-item-info-{{$item.id}}" >
					<div class="wall-item-photo-wrapper{{if $item.owner_url}} wwfrom{{/if}} h-card p-author" id="wall-item-photo-wrapper-{{$item.id}}">
						{{if $item.contact_id}}
						<div class="spinner-wrapper contact-edit-rotator contact-edit-rotator-{{$item.contact_id}}"><div class="spinner s"></div></div>
						{{/if}}
						<img src="{{$item.thumb}}" class="fakelink wall-item-photo{{$item.sparkle}} u-photo p-name" id="wall-item-photo-{{$item.id}}" alt="{{$item.name}}" loading="lazy" data-bs-toggle="dropdown" />
						{{if $item.author_is_group_actor}}
						<i class="fa fa-comments-o wall-item-photo-group-actor" title="{{$item.author_is_group_actor}}"></i>
						{{/if}}
						{{if $item.thread_author_menu}}
						<i class="fa fa-caret-down wall-item-photo-caret cursor-pointer" data-bs-toggle="dropdown"></i>
						<div class="dropdown-menu">
							{{foreach $item.thread_author_menu as $mitem}}
							<a class="dropdown-item{{if $mitem.class}} {{$mitem.class}}{{/if}}" {{if $mitem.href}}href="{{$mitem.href}}"{{/if}} {{if $mitem.action}}onclick="{{$mitem.action}}"{{/if}} {{if $mitem.title}}title="{{$mitem.title}}"{{/if}}{{if $mitem.data}} {{$mitem.data}}{{/if}}>{{$mitem.title}}</a>
							{{/foreach}}
						</div>
						{{/if}}
					</div>
				</div>
				<div class="wall-item-author">
					{{if $item.previewing}}
					<div class="float-start me-1 preview-indicator">
						<i class="fa fa-eye" title="{{$item.preview_lbl}}"></i>
					</div>
					{{/if}}
					{{if $item.lock}}
					<div class="float-start dropdown wall-item-lock">
						<i class="fa {{if $item.locktype == 2}}fa-envelope-o{{else if $item.locktype == 1}}fa-lock{{else}}fa-unlock{{/if}} lockview{{if $item.privacy_warning}} text-danger{{/if}}" data-bs-toggle="dropdown" title="{{$item.lock}}" onclick="lockview('item',{{$item.id}});" ></i>&nbsp;
						<div id="panel-{{$item.id}}" class="dropdown-menu"></div>
					</div>
					{{/if}}
					<div class="text-truncate">
						<a href="{{$item.profile_url}}" class="lh-sm wall-item-name-link u-url"{{if $item.app}} title="{{$item.str_app}}"{{/if}}><span class="wall-item-name{{$item.sparkle}}" id="wall-item-name-{{$item.id}}" ><bdi>{{$item.name}}</bdi></span></a>{{if $item.owner_url}}&nbsp;{{$item.via}}&nbsp;<a href="{{$item.owner_url}}" title="{{$item.olinktitle}}" class="wall-item-name-link"><span class="wall-item-name{{$item.osparkle}}" id="wall-item-ownername-{{$item.id}}"><bdi>{{$item.owner_name}}</bdi></span></a>{{/if}}
					</div>
					<small class="lh-sm text-truncate d-block wall-item-addr opacity-75">{{$item.author_id}}</small>
				</div>
			</div>
			{{if $item.divider}}
			<hr class="wall-item-divider">
			{{/if}}
			{{if $item.body}}
			<div class="p-2 clrearfix {{if $item.is_photo}} wall-photo-item{{else}} wall-item-content{{/if}}" id="wall-item-content-{{$item.id}}">
				<div class="wall-item-body" id="wall-item-body-{{$item.id}}"{{if $item.rtl}} dir="rtl"{{/if}}>
					{{$item.body}}
				</div>
			</div>
			{{/if}}
			{{if $item.has_tags}}
			<div class="p-2 wall-item-tools clearfix">
				<div class="body-tags">
					<span class="tag">{{$item.mentions}} {{$item.tags}} {{$item.categories}} {{$item.folders}}</span>
				</div>
			</div>
			{{/if}}
			<div class="p-2 clearfix wall-item-tools">
				<div class="float-end wall-item-tools-right">
					<div class="btn-group">
						<div id="like-rotator-{{$item.id}}" class="spinner-wrapper">
							<div class="spinner s"></div>
						</div>
					</div>
					{{if $item.mode === 'moderate'}}
					<a href="moderate/{{$item.id}}/approve" class="btn btn-outline-success btn-sm">{{$item.approve}}</a>
					<a href="moderate/{{$item.id}}/drop" class="btn btn-outline-danger btn-sm">{{$item.delete}}</a>
					{{else}}
					{{if $item.star || $item.thread_action_menu || $item.drop.dropping}}
					<div class="btn-group">
						<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
							<i class="fa fa-cog"></i>
						</button>
						<div class="dropdown-menu dropdown-menu-end">
							{{if $item.star}}
							<a class="dropdown-item" href="#" onclick="dostar({{$item.id}}); return false;"><i id="starred-{{$item.id}}" class="fa fa-fw{{if $item.star.isstarred}} starred fa-star{{else}} unstarred fa-star-o{{/if}} generic-icons-nav" title="{{$item.star.toggle}}"></i>{{$item.star.toggle}}</a>
							{{/if}}
							{{if $item.thread_action_menu}}
							{{foreach $item.thread_action_menu as $mitem}}
							<a class="dropdown-item" {{if $mitem.href}}href="{{$mitem.href}}"{{/if}} {{if $mitem.action}}onclick="{{$mitem.action}}"{{/if}} {{if $mitem.title}}title="{{$mitem.title}}"{{/if}} ><i class="fa fa-fw fa-{{$mitem.icon}} generic-icons-nav"></i>{{$mitem.title}}</a>
							{{/foreach}}
							{{/if}}
							{{if $item.drop.dropping}}
							<a class="dropdown-item" href="#" onclick="dropItem('item/drop/{{$item.id}}', '#thread-wrapper-{{$item.id}}', '{{$item.mid}}'); return false;" title="{{$item.drop.delete}}" ><i class="generic-icons-nav fa fa-fw fa-trash-o"></i>{{$item.drop.delete}}</a>
							{{/if}}
						</div>
					</div>
					{{/if}}
					{{/if}}
				</div>
				{{if $item.star && $item.star.isstarred}}
				<div class="btn-group" id="star-button-{{$item.id}}">
					<button type="button" class="btn btn-outline-secondary btn-sm wall-item-like" onclick="dostar({{$item.id}});"><i class="fa fa-star"></i></button>
				</div>
				{{/if}}
				{{if $item.attachments}}
				<div class="wall-item-tools-left btn-group">
					<button type="button" class="btn btn-outline-secondary btn-sm wall-item-like dropdown-toggle" data-bs-toggle="dropdown" id="attachment-menu-{{$item.id}}"><i class="fa fa-paperclip"></i></button>
					<div class="dropdown-menu">{{$item.attachments}}</div>
				</div>
				{{/if}}
			</div>
		</div>
		{{if $item.conv}}
		<div class="p-2 wall-item-conv" id="wall-item-conv-{{$item.id}}" >
			<a href='{{$item.conv.href}}' id='context-{{$item.id}}' title='{{$item.conv.title}}'>{{$item.conv.title}}</a>
		</div>
		{{/if}}
	</div>
</div>

