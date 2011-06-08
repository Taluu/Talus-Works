<?php $tpl->includeTpl('head.html', false, 0); ?>
				<?php echo $tpl->vars['TITRE']; ?>
				<?php if ($tpl->vars['DESCRIPTION']) : ?><div class="forum_description"><?php echo $tpl->vars['DESCRIPTION']; ?></div><?php endif; ?>
				
				<table class="list_forums">
					<thead>
						<tr>
							<th colspan="2" class="lbl_cat">Catégorie</th>
							<th class="lbl_nbr">Sujets</th>
							<th class="lbl_nbr">Messages</th>
							<th class="lbl_last">Dernier Message</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="2" class="lbl_cat">Catégorie</th>
							<th class="lbl_nbr">Sujets</th>
							<th class="lbl_nbr">Messages</th>
							<th class="lbl_last">Dernier Message</th>
						</tr>
					</tfoot>
					<tbody>
						<?php if ($tpl->getBlock('cat')) : $__tpl_38ded6c946d4323f0533ae9b942dcb924ad5b93c = &$tpl->getBlock('cat'); foreach ($__tpl_38ded6c946d4323f0533ae9b942dcb924ad5b93c as &$__tplBlock['cat']){ ?>
							<tr class="cats">
								<td colspan="2" class="cat_name">
									<?php if (!$__tplBlock['cat']['IS_READ']) : ?><a href="markread-cat-<?php echo $__tplBlock['cat']['ID']; ?>.html"><img src="./images/icones/markread.png" alt="Marquer cette catégorie comme lue" title="Marquer cette catégorie comme lue" /></a><?php endif; ?>
									<a href="<?php echo $__tplBlock['cat']['URL']; ?>"><?php echo $__tplBlock['cat']['NAME']; ?></a>
								</td>
								<td class="cat_stats"><?php echo $__tplBlock['cat']['TOPICS']; ?></td>
								<td class="cat_stats"><?php echo $__tplBlock['cat']['POSTS']; ?></td>
								<td class="cat_empty"></td>
							</tr>
							
							<?php if (isset($__tplBlock['cat']['forums'])) : $__tpl_ccf179676845e3dcb6fc868d1865b98ad96eb4fd = &$__tplBlock['cat']['forums']; foreach ($__tpl_ccf179676845e3dcb6fc868d1865b98ad96eb4fd as &$__tplBlock['forums']){ ?>
								<tr class="forums">
									<td class="flag_read">
										<?php if (!$__tplBlock['forums']['IS_READ']) : ?><a href="markread-forums-<?php echo $__tplBlock['forums']['ID']; ?>.html"><?php endif; ?>
										<img src="./images/icones/forum_<?php echo $__tplBlock['forums']['ICON_PREFIX']; ?>read.png" <?php if ($__tplBlock['forums']['IS_READ']) : ?>alt="Vous avez lu tous les sujets de ce forum" title="Vous avez lu tous les sujets de ce forum"<?php else : ?>alt="Vous avez des sujets non lus dans ce forum" title="Vous avez des sujets non lus dans ce forum"<?php endif; ?> />
										<?php if (!$__tplBlock['forums']['IS_READ']) : ?></a><?php endif; ?>								
									</td>
									<td class="forums_name">
										<a href="<?php echo $__tplBlock['forums']['URL']; ?>"><?php echo $__tplBlock['forums']['NAME']; ?></a>
										<?php if ($__tplBlock['forums']['DESCRIPTION']) : ?>
											<br /><?php echo $__tplBlock['forums']['DESCRIPTION']; ?>
										<?php endif; ?>
										<?php if (isset($__tplBlock['forums']['subs'])) : $__tpl_3990b08abcc24b4f4917ffc3ec90da2cee16c332 = &$__tplBlock['forums']['subs']; foreach ($__tpl_3990b08abcc24b4f4917ffc3ec90da2cee16c332 as &$__tplBlock['subs']){ ?>
											<?php if ($__tplBlock['subs']['FIRST']) : ?><br /><img src="./images/icones/list_sub.png" alt="Sous Forums : "/><?php endif; ?>
											<a href="<?php echo $__tplBlock['subs']['URL']; ?>"><?php echo $__tplBlock['subs']['NAME']; ?></a><?php if (!$__tplBlock['subs']['LAST']) : ?>, <?php endif; ?>
										<?php } endif; ?>
									</td>
									<td class="stats">
										<?php echo $__tplBlock['forums']['TOPICS']; ?>
									</td>
									<td class="stats">
										<?php echo $__tplBlock['forums']['POSTS']; ?>
									</td>
									<td class="last_answer">
										<?php echo $__tplBlock['forums']['LAST_TOPIC']; ?>
										<?php echo $__tplBlock['forums']['LAST_DATE']; ?>
										<?php echo $__tplBlock['forums']['LAST_USER']; ?>
									</td>
								</tr>
							<?php } endif; ?>
						<?php } endif; ?>
					</tbody>
				</table>
<?php $tpl->includeTpl('foot.html', false, 0); ?>