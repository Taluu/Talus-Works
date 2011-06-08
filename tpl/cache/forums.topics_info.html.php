<table class="topics_info">
					<tbody>
						<tr>
							<td class="post_new_topics"><?php if ($tpl->vars['FORUM_OPENED']) : ?><a href="new-topic-<?php echo $tpl->vars['FORUM_ID']; ?>.html">Nouveau Sujet</a><?php else : ?>Forum Fermé<?php endif; ?> - <?php if ($tpl->vars['TOPIC_OPENED']) : ?><a href="reply-topic-<?php echo $tpl->vars['TOPIC_ID']; ?>.html">Nouvelle Réponse</a><?php else : ?>Sujet Fermé<?php endif; ?></td>
							<td class="right">
								<?php if ($tpl->vars['USE_PAGINATION']) : ?>
									<span class="pagination_overall">
										Pages : <?php if ($tpl->getBlock('pagination')) : $__tpl_da42d57c879676b50d54011427db2bed494fe5fb = &$tpl->getBlock('pagination'); foreach ($__tpl_da42d57c879676b50d54011427db2bed494fe5fb as &$__tplBlock['pagination']){  echo $__tplBlock['pagination']['PAGE'];  } endif; ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>