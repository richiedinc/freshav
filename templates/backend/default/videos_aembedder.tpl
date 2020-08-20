	<!-- BEGIN PAGE CONTAINER-->
	<div class="page-content"> 
		<div class="content">
			<!-- BEGIN PAGE TITLE -->
			<div class="page-title">
				<i class="icon-custom-left"></i>
				<h3>Videos - <span class="semi-bold">Auto Embedder</span></h3>
			</div>
			{include file="errmsg.tpl"}
			<!-- END PAGE TITLE -->
			<!-- BEGIN PlACE PAGE CONTENT HERE -->															
			<div class="col-md-12">
				<div class="grid simple">
					<div class="grid-title no-border">
						<h4>Auto Embedder <span class="semi-bold">Sources</span></h4>
					</div>
					<div class="grid-body no-border">
					<div class="row m-b-20">
						<div class="col-xs-12">											
							{foreach from=$sites key=domain item=website}
								<div class="col-md-3 col-sm-6 grabber-label">{$domain}</div>
							{/foreach}
							<div class="clearfix"></div>
						</div>
					</div>					
						<div class="btn-group"> <a class="btn dropdown-toggle btn-success" data-toggle="dropdown" href="#">Run - <span id="ae_run">{if $option.run == '1'}Every Hour{elseif $option.run == '3'}Every 3 Hours{elseif $option.run == '6'}Every 6 Hours{elseif $option.run == '12'}Every 12 Hours{elseif $option.run == '24'}Every Day{elseif $option.run == '168'}Every Week{else}Never{/if}</span> <span class="caret"></span> </a>
							<ul class="dropdown-menu">
								<li><a href="#" id="run_0">Never</a></li>					
								<li><a href="#" id="run_1">Every Hour</a></li>													
								<li><a href="#" id="run_3">Every 3 Hours</a></li>								
								<li><a href="#" id="run_6">Every 6 Hours</a></li>
								<li><a href="#" id="run_12">Every 12 Hours</a></li>
								<li><a href="#" id="run_24">Every Day</a></li>
								<li><a href="#" id="run_168">Every Week</a></li>								
							</ul>
						</div>
						<div class="m-b-20"></div>
						<div class="row">
							<div class="col-xs-12">
								<form class="form-no-horizontal-spacing form-grey" name="add_url" method="POST" enctype="multipart/form-data" action="videos.php?m=aembedder">
									<div class="row">
										<div class="col-xs-12 col-sm-6 col-md-3">
											<div class="form-group">
												 <input type="text" name="url" value="{$source.url}" class="form-control {if $err.url}error{/if}" placeholder="Page URL">
											</div>
										</div>
										<div class="col-xs-12 col-sm-6 col-md-3">
											<div class="form-group">
												 <input type="text" name="username" value="{$source.username}" class="form-control {if $err.username}error{/if}" placeholder="Username">
											</div>
										</div>										
										<div class="col-xs-12 col-sm-6 col-md-3">
											<div class="form-group">
												<select id="category" name="category" style="width:100%">
													<option value="0">Category - Autodetect</option>
													{section name=i loop=$categories}
													<option value="{$categories[i].CHID}"{if $categories[i].CHID == $source.category } selected="selected"{/if}>{$categories[i].name}</option>
													{/section}
												</select>  												
											</div>
										</div>						
										<div class="col-xs-12 col-sm-6 col-md-3">
											<div class="form-group">
												<input type="submit" name="add_source" value="Add Source" class="btn btn-success btn-cons btn-icon m-0 pull-right">
												<div class="clearfix"></div>
											</div>
										</div>
									</div>			
								</form>						
								<form class="form-no-horizontal-spacing search-filters" name="search_sources_form" method="POST" action="videos.php?m={$module}">
									<input id="sort" name="sort" type="hidden" value={$option.sort}>
									<input id="order" name="order" type="hidden" value={$option.order}>
									<div class="pull-left">
										<div class="btn-group"> <a class="btn dropdown-toggle btn-demo-space" data-toggle="dropdown" href="#">Order by <span id="sort_items">{if $option.sort == 'website'}Website{elseif $option.sort == 'category'}Category{else}ID{/if}</span> <span class="caret"></span> </a>
											<ul class="dropdown-menu">
												<li><a href="#" onClick="document.getElementById('sort_items').innerText = 'ID'; document.getElementById('sort').value = 'id'" >ID</a></li>
												<li><a href="#" onClick="document.getElementById('sort_items').innerText = 'Category'; document.getElementById('sort').value = 'category'" >Category</a></li>
												<li><a href="#" onClick="document.getElementById('sort_items').innerText = 'Website'; document.getElementById('sort').value = 'website'" >Website</a></li>												
											</ul>
										</div>									
										<div class="btn-group"> <a class="btn dropdown-toggle btn-demo-space" data-toggle="dropdown" href="#"><span id="order_items">{if $option.order == 'ASC'}Ascending{else}Descending{/if}</span> <span class="caret"></span> </a>
											<ul class="dropdown-menu">
												<li><a href="#" onClick="document.getElementById('order_items').innerText = 'Ascending'; document.getElementById('order').value = 'ASC'" >Ascending</a></li>
												<li><a href="#" onClick="document.getElementById('order_items').innerText = 'Descending'; document.getElementById('order').value = 'DESC'" >Descending</a></li>
											</ul>
										</div>
									</div>
									<div class="pull-right">
										<button type="button" id="reset_search" name="reset_search" class="btn btn-white btn-cons btn-icon"><i class="fa fa-times"></i></button>									
										<button type="submit" name="search_sources" class="btn btn-success btn-cons btn-icon m-r-0"><i class="fa fa-search"></i></button>									
									</div>
									<div class="clearfix"></div>
								</form>
							</div>
						</div>
						<!-- END SEARCH FILTERS -->						
						<div class="row">
							<div class="col-xs-12">
								<div>
									{if $sources}
										<form class="form-no-horizontal-spacing" name="category_select" method="post" id="category_select" action="">
										{section name=i loop=$sources}
											<div id="item-{$sources[i].id}" class="item-main-container small-thumb">
												<div class="item-col-left">
													<div class="item-main">
														<div class="item-thumb">
															<div class="thumb-overlay">	
																<img id="thumb-{$sources[i].id}" src="{$baseurl}/templates/backend/default/assets/img/aembedder/{$sources[i].website}.png" class="img-responsive">
																<div class="item-id">
																	<b>ID</b> {$sources[i].id}
																</div>																
															</div>												
														</div>
													</div>
												</div>
												<div class="item-col-right">
													<div class="item-details">
														<div class="item-title">
															<span class="text-info" id="url-{$sources[i].id}">{$sources[i].url|escape:'html'}</span>
														</div>
														<div class="row">						
															<div class="col-xs-6 col-sm-4 col-md-2 col-lg-2">
																<div class="d-label">Status</div>
																<span id="status-{$sources[i].id}">
																	{if $sources[i].status == 1}
																		<span class="text-green" alt="Active" title="Active">Active</span>
																	{else}
																		<span class="text-red" alt="Inactive" title="Inactive">Inactive</span>
																	{/if}
																</span>
															</div>														
															<div class="col-xs-6 col-sm-4 col-md-2 col-lg-2">
																<div class="d-label">Category</div>
																<span id="category-{$sources[i].id}"><b>{if $sources[i].name}{$sources[i].name}{else}<i>Autodetect</i>{/if}</b></span>
															</div>
															<div class="col-xs-6 col-sm-4 col-md-2 col-lg-2">
																<div class="d-label">Username</div>
																<span id="username-{$sources[i].id}"><b>{$sources[i].username}</b></span>
															</div>
															<div class="col-xs-6 col-sm-4 col-md-2 col-lg-2">
																<div class="d-label">Total Videos</div>
																<span id="views-{$sources[i].id}">{$sources[i].total}</span>
															</div>
															<div class="col-xs-6 col-sm-4 col-md-2 col-lg-2">
																<div class="d-label">Added Last Run</div>
																<span id="views-{$sources[i].id}">{$sources[i].added}</span>
															</div>															
															<div class="col-xs-6 col-sm-4 col-md-2 col-lg-2">
																<div class="d-label">Last Run</div>
																<span id="views-{$sources[i].id}">{if $sources[i].last_run}{$sources[i].last_run}{else}n/a{/if}</span>
															</div>															
														</div>
													</div>
												</div>
												<div class="clearfix"></div>
												<div class="item-actions">																									
													<div class="btn-group">
														<div class="btn-group">
															<a id="delete__source_{$sources[i].id}" class="btn btn-success" data-toggle="dropdown" href="#" alt="Delete" title="Delete"><i class="fa fa-trash-o"></i></a>
															<ul class="dropdown-menu">
																<li><a id="delete_source_{$sources[i].id}" href="#">Delete</a></li>
															</ul>
														</div>
														{if $sources[i].status == '1'}
															<a id="status_source_{$sources[i].id}" class="btn btn-success" href="#" alt="Suspend" title="Suspend" data-processing="0" data-status="1"><i class="fa fa-times"></i></a>
														{else}
															<a id="status_source_{$sources[i].id}" class="btn btn-success" href="#" alt="Activate" title="Activate" data-processing="0" data-status="0"><i class="fa fa-check"></i></a>
														{/if}															
													</div>												
												</div>												
											</div>
										{/section}									
										</form>
									{else}
									<div class="row">
										<div class="col-xs-12">
											<div class="alert alert-info">
												<button class="close" data-dismiss="alert"></button>
												No Sources Found
											</div>
										</div>
									</div>
									{/if}	
								</div>
							
							</div>
						</div>
					</div>
				</div>
			</div>			
			<!-- END PLACE PAGE CONTENT HERE -->
		</div>
	</div>
	<!-- END PAGE CONTAINER -->