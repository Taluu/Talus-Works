				<div class="nav">
					Vous êtes ici : <a href="<?php echo DOMAIN_REDIRECT; ?>"><img src="./images/design/home.png" class="home_img" alt="Accueil" /> Home</a>
					<?php foreach ($tpl->vars['FIL'] as $__tpl_foreach_key['FIL'] => &$__tpl_foreach_value['FIL']) : ?>
						<span class="grand">&#187;</span> <?php if ($__tpl_foreach_value['FIL']) : ?><a href="<?php echo $__tpl_foreach_value['FIL']; ?>"><?php echo $__tpl_foreach_key['FIL']; ?></a><?php else :  echo $__tpl_foreach_key['FIL'];  endif; ?>
					<?php endforeach; ?>
				</div>