<?php $tpl->includeTpl('head.html', false, 0); ?>
				<h1>Répondre à un sujet (<a href="topic-<?php echo $tpl->vars['ID']; ?>.html"><?php echo Talus_TPL_Filters::protect($tpl->vars['T_TITLE']); ?></a>)</h1>
				<p>Vous voulez participer à ce sujet ? Pas de problèmes, postez juste un message ici :)</p>
				<p>Veuillez simplement respecter les règles (voir les CGU)...</p>
				<p class="centre grand rouge">Notez que Tous les champs sont obligatoires !</p>
				<?php if ($tpl->vars['ERR_MSG'] != '') : ?>
					<p class="centre rouge gras"><?php echo $tpl->vars['ERR_MSG']; ?></p>
				<?php endif; ?>
				
				<form action="" method="post">
					<fieldset class="reply">
						<legend>Ajout d'une réponse au sujet <a href="topic-<?php echo $tpl->vars['ID']; ?>.html"><?php echo Talus_TPL_Filters::protect($tpl->vars['T_TITLE']); ?></a></legend>
						<textarea id="reply" cols="40" rows="10" name="reply"><?php echo Talus_TPL_Filters::protect($tpl->vars['CONTENT']); ?></textarea>
						<?php if ($tpl->vars['CAN_SOLVE']) : ?>
							<br /><label>Marquer comme résolu : <input type="checkbox" name="t_solved" <?php if ($tpl->vars['T_SOLVED']) : ?>checked="checked" <?php endif; ?>/></label>
							<?php if ($tpl->vars['IS_FORUM_MODO']) : ?>
								<br /><label>Fermer le sujet : <input type="checkbox" name="t_closed" <?php if ($tpl->vars['T_CLOSED']) : ?>checked="checked" <?php endif; ?>/></label>
							<?php endif; ?>
							<br />
						<?php endif; ?>
						<br />
						<input type="submit" name="send" value="Répondre" />&nbsp;<input type="button" value="Prévisualiser" onclick="prev('reply', 'prev');" />
					</fieldset>
				</form>
			
				<fieldset class="prev">
					<legend>Prévisualisation :</legend>
					<div id="prev"></div>
				</fieldset>
				
				<!-- Reviews ! //-->
				<fieldset class="reviews">
					<legend>Review du sujet</legend>
					<div class="reviews">
						<table class="list_messages">
							<tbody>
								<?php if ($tpl->getBlock('review')) : $__tpl_22548352bf3cbc6f64e7241d2a2e9de119c806c4 = &$tpl->getBlock('review'); foreach ($__tpl_22548352bf3cbc6f64e7241d2a2e9de119c806c4 as &$__tplBlock['review']){ ?>
									<tr class="post_date">
										<td class="date_left" id="p<?php echo $__tplBlock['review']['ID']; ?>">#<?php echo $__tplBlock['review']['ID']; ?> - <?php echo $__tplBlock['review']['DATE']; ?></td>
										<td class="date_right">
											<img src="./images/icones/quote_off.gif" id="quote_<?php echo $__tplBlock['review']['ID']; ?>" alt="Citer" title="Citer ce post" onclick="quote(<?php echo $__tplBlock['review']['ID']; ?>, 'reply');" onmouseover="this.style.cursor = 'pointer';" onmouseout="this.style.cursor = 'default';" />									
											<a href="#header"><img src="./images/icones/up.gif" alt="Haut de Page" title="Haut de Page" /></a>
										</td>
									</tr>
									<tr class="messages">
										<td class="userinfo">
											<ul class="userinfo">
												<li class="user_name"><?php echo $__tplBlock['review']['U_NAME']; ?></li>
												<?php if ($__tplBlock['review']['U_AVATAR']) : ?><li class="user_avatar"><img src="<?php echo $__tplBlock['review']['U_AVATAR']; ?>" alt="Avatar" /></li><?php endif; ?>
												
												<li class="user_level"><span class="gras">Statut :</span> <span class="<?php echo $__tplBlock['review']['U_CLASS']; ?>"><?php echo $__tplBlock['review']['U_GRP']; ?></span></li>
											</ul>
										</td>
										
										<td class="content">
											<div class="message">
												<?php if ($__tplBlock['review']['LAST']) : ?>
													<strong>Voici le premier post du sujet :</strong><br />
												<?php endif; ?>
												<?php echo $__tplBlock['review']['CONTENT']; ?>
											</div>
										</td>
									</tr>
								<?php } endif; ?>
							</tbody>
						</table>
					</div>
				</fieldset>
<?php $tpl->includeTpl('foot.html', false, 0); ?>